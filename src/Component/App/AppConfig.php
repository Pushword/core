<?php

namespace Pushword\Core\Component\App;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Environment as Twig;

final class AppConfig
{
    private bool $isFirstApp = false;

    /** @var string[] */
    private array $hosts;

    /** @var array<(string|int), mixed> */
    private array $customProperties;

    private string $locale;

    /** @var string|array<string> */
    private $locales;

    private string $baseUrl;

    private string $name;

    private string $template;

    /** @var class-string[] */
    private array $filters;

    private bool $entityCanOverrideFilters;

    /** @var array<string, array<string>> */
    private array $assets;

    private Twig $twig;

    private ParameterBagInterface $params;

    /** @param array<string, mixed> $properties */
    public function __construct(ParameterBagInterface $parameterBag, array $properties, bool $isFirstApp = false)
    {
        $this->params = $parameterBag;

        foreach ($properties as $prop => $value) {
            $this->setCustomProperty($prop, $value);

            // TODO: solve why when i remove this, falt_import_dir disappear
            $prop = static::normalizePropertyName($prop);
            $this->$prop = $value;
        }

        $this->isFirstApp = $isFirstApp;
    }

    private static function normalizePropertyName(string $string): string
    {
        $string = str_replace('_', '', ucwords(strtolower($string), '_'));

        return lcfirst($string);
    }

    public function setTwig(Twig $twig): void
    {
        $this->twig = $twig;
    }

    /** @return array<string, mixed> */
    public function getParamsForRendering(): array
    {
        return [
            'app_base_url' => $this->getBaseUrl(),
            'app_name' => $this->name,
            'app_color' => $this->getCustomProperty('color'),
        ];
    }

    /**
     * Todo : change for getHost ?!
     */
    public function getMainHost(): string
    {
        return $this->hosts[0];
    }

    /**
     * Used in Router Extension.
     *
     * @return bool
     */
    public function isMainHost(?string $host)
    {
        return $this->getMainHost() === $host;
    }

    /** @return string[] */
    public function getHosts(): array
    {
        return $this->hosts;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function has(string $key): bool
    {
        return null !== $this->get($key);
    }

    /** @return mixed */
    public function get(string $key)
    {
        $camelCaseKey = static::normalizePropertyName($key);

        $method = 'get'.ucfirst($camelCaseKey);

        if (method_exists($this, $method)) {
            return $this->$method(); // @phpstan-ignore-line
        }

        if (isset($this->$camelCaseKey)) { // @phpstan-ignore-line
            return $this->$camelCaseKey; // @phpstan-ignore-line
        }

        return $this->getCustomProperty($key);
    }

    /**
     * @param mixed $value
     */
    public function setCustomProperty(string $key, $value): self
    {
        $camelCaseKey = static::normalizePropertyName($key);
        if (property_exists($this, $camelCaseKey)) {
            $this->$camelCaseKey = $value; // @phpstan-ignore-line
        }

        $this->customProperties[$key] = $value;

        return $this;
    }

    /** @return mixed */
    public function getCustomProperty(string $key)
    {
        return isset($this->customProperties[$key]) ? $this->customProperties[$key] : null;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    /** @return class-string[] */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /** @param class-string[] $filters */
    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    public function entityCanOverrideFilters(): bool
    {
        return $this->entityCanOverrideFilters;
    }

    /** @return array<string, array<string>> */
    public function getAssets(): array
    {
        return $this->assets;
    }

    /** @return array<string, array<string>> */
    public function getAssetsVersionned(): array
    {
        $assetsVersionned = ['javascripts' => [], 'stylesheets' => []];
        foreach (['javascripts', 'stylesheets'] as $row) {
            if (! isset($this->assets[$row])) {
                continue;
            }
            foreach ($this->assets[$row] as $key => $asset) {
                $filepath = \strval($this->params->get('pw.public_dir')).$asset;
                $assetsVersionned[$row][$key] = $asset.
                    (file_exists($filepath) ? '?'.\Safe\substr(md5(\Safe\filemtime($filepath).$filepath), 2, 9) : '');
            }
        }

        return $assetsVersionned;
    }

    /**
     * @psalm-suppress InternalMethod
     */
    public function getView(?string $path = null, string $fallback = '@Pushword'): string
    {
        if (null === $path) {
            return $this->template.'/page/page.html.twig';
        }

        if ($this->isFullPath($path)) { // permits to get a component from a dedicated extension eg @pwEgTheme/page...
            return $path;
        }

        if ('none' == $path) { // alias
            $path = '/page/raw.twig';
        }

        $overrided = $this->getOverridedView($path);
        if (null !== $overrided) {
            return $overrided;
        }

        $name = $this->template.$path;

        // check if twig template exist
        try {
            $this->twig->load($name);

            return $name; // @phpstan-ignore-line
        } finally {
            return $fallback.$path; // @phpstan-ignore-line
        }
    }

    private function getOverridedView(string $name): ?string
    {
        $name = ('/' === $name[0] ? '' : '/').$name;

        $templateDir = $this->get('template_dir');

        $templateOverridedForHost = $templateDir.'/'.$this->getMainHost().$name;
        if (file_exists($templateOverridedForHost)) {
            return '/'.$this->getMainHost().$name;
        }

        $templateOverrided = $templateDir.'/'.ltrim($this->getTemplate(), '@').$name;
        if (file_exists($templateOverrided)) {
            return '/'.ltrim($this->getTemplate(), '@').$name;
        }

        $globalOverride = $templateDir.$name;
        if (file_exists($globalOverride)) {
            return $name;
        }

        return null;
    }

    private function isFullPath(string $path): bool
    {
        return 0 === strpos($path, '@') && false !== strpos($path, '/');
    }

    public function isFirstApp(): bool
    {
        return $this->isFirstApp;
    }

    /**
     * Get the value of locale.
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getDefaultLocale(): string
    {
        return $this->locale;
    }

    /**
     * Get the value of locales.
     *
     * @return string[]
     */
    public function getLocales(): array
    {
        if (\is_string($this->locales)) {
            $this->locales = explode('|', $this->locales);
        }

        return $this->locales;
    }

    /**
     * Get the value of name.
     */
    public function getName(): string
    {
        return $this->name;
    }
}
