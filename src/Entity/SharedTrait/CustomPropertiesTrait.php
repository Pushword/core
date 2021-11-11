<?php

namespace Pushword\Core\Entity\SharedTrait;

use Exception;
use Pushword\Core\Utils\F;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

trait CustomPropertiesTrait
{
    /**
     * YAML Format.
     *
     * @ORM\Column(type="json")
     *
     * @var array<mixed>
     */
    protected array $customProperties = [];

    /**
     * Stand Alone for not indexed
     * Yaml format, use only for setting, else get always retrieve standAlone from $customProperties.
     */
    protected string $standAloneCustomProperties = '';

    protected string $buildValidationAtPath = 'standAloneCustomProperties';

    /**
     * @return array<mixed>
     */
    public function getCustomProperties(): array
    {
        return $this->customProperties;
    }

    /**
     * @param array<mixed> $customProperties
     */
    public function setCustomProperties(array $customProperties): self
    {
        $this->customProperties = $customProperties;

        return $this;
    }

    /**
     * Return custom properties without the ones wich have a get method.
     */
    public function getStandAloneCustomProperties(): string
    {
        if ([] === $this->getCustomProperties()) {
            return '';
        }
        $standStandAloneCustomProperties = array_filter(
            $this->getCustomProperties(),
            [$this, 'isStandAloneCustomProperty'],
            \ARRAY_FILTER_USE_KEY
        );
        if ([] === $standStandAloneCustomProperties) {
            return '';
        }

        return Yaml::dump($standStandAloneCustomProperties);
    }

    public function setStandAloneCustomProperties(?string $standStandAloneCustomProperties, bool $merge = false): self
    {
        $this->standAloneCustomProperties = (string) $standStandAloneCustomProperties;

        if ($merge) {
            $this->mergeStandAloneCustomProperties();
        }

        return $this;
    }

    protected function mergeStandAloneCustomProperties(): void
    {
        $standAloneProperties = '' !== $this->standAloneCustomProperties ? Yaml::parse($this->standAloneCustomProperties)
            : [];
        if (! \is_array($standAloneProperties)) {
            throw new Exception('standAloneProperties are not a valid yaml array');
        }
        $this->standAloneCustomProperties = '';

        // remove the standAlone which were removed
        $existingPropertyNames = array_keys($this->getCustomProperties());
        foreach ($existingPropertyNames as $existingPropertyName) {
            if ($this->isStandAloneCustomProperty($existingPropertyName) && ! isset($standAloneProperties[$existingPropertyName])) {
                $this->removeCustomProperty($existingPropertyName);
            }
        }

        // nothing to add
        if ([] === $standAloneProperties) {
            return;
        }

        foreach ($standAloneProperties as $name => $value) {
            if (! $this->isStandAloneCustomProperty($name)) {
                throw new CustomPropertiesException($name);
            }

            $this->setCustomProperty($name, $value);
        }
    }

    /**
     * @Assert\Callback
     */
    public function validateStandAloneCustomProperties(ExecutionContextInterface $executionContext): void
    {
        try {
            $this->mergeStandAloneCustomProperties();
        } catch (ParseException $exception) {
            $executionContext->buildViolation('page.customProperties.malformed') //'$exception->getMessage())
                    ->atPath($this->buildValidationAtPath)
                    ->addViolation();
        } catch (CustomPropertiesException $exception) {
            $executionContext->buildViolation('page.customProperties.notStandAlone') //'$exception->getMessage())
                    ->atPath($this->buildValidationAtPath)
                    ->addViolation();
        }

        //$this->validateCustomProperties($context); // too much
    }

    public function isStandAloneCustomProperty(string $name): bool
    {
        return ! method_exists($this, 'set'.ucfirst($name)) && ! method_exists($this, 'set'.$name);
    }

    /**
     * @param mixed $value
     */
    public function setCustomProperty(string $name, $value): self
    {
        $this->customProperties[$name] = $value;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCustomProperty(string $name)
    {
        return $this->customProperties[$name] ?? null;
    }

    public function removeCustomProperty(string $name): void
    {
        unset($this->customProperties[$name]);
    }

    /**
     * Magic getter for customProperties.
     * TODO/IDEA magic setter for customProperties.
     *
     * @param array<string, mixed> $arguments
     *
     * @return mixed
     */
    public function __call(string $method, array $arguments = [])
    {
        if ('_actions' == $method) {
            return; // avoid error with sonata
        }

        if (1 === \Safe\preg_match('/^get/', $method)) {
            $property = lcfirst(F::preg_replace_str('/^get/', '', $method));
            if (! property_exists(static::class, $property)) {
                return $this->getCustomProperty($property) ?? null;
            }

            return $this->$property; // may keep that ?! @phpstan-ignore-line
        } else {
            $vars = array_keys(get_object_vars($this));
            if (\in_array($method, $vars, true) && \is_callable($getter = [$this, 'get'.ucfirst($method)])) {
                return \call_user_func_array($getter, $arguments);
            }

            return $this->getCustomProperty(lcfirst($method)) ?? null;
        }
    }
}
