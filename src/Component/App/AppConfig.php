<?php

namespace Pushword\Core\Component\App;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Environment as Twig;

final class AppConfig
{
    /** @var string[] */
    private array $hosts = [];

    /** @var array<(string|int), mixed> */
    private array $customProperties = [];

    private string $locale;

    /** @var string|string[]|null */
    private string|array|null $locales = null;

    private string $baseUrl;

    private string $name;

    private string $template;

    /** @var array<string, string> */
    private array $filters = [];

    private bool $entityCanOverrideFilters;

    /** @var array<string, array<string>> */
    private array $assets = [];

    private Twig $twig;

    /** @param array<string, mixed> $properties */
    public function __construct(
        private readonly ParameterBagInterface $params,
        array $properties,
        private readonly bool $isFirstApp = false
    ) {
        foreach ($properties as $prop => $value) {
            $this->setCustomProperty($prop, $value);

            // TODO: solve why when i remove this, falt_import_dir disappear
            $prop = static::normalizePropertyName($prop);
            $this->$prop = $value; // @phpstan-ignore-line
        }
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

    /** @return array{app_base_url: string, app_name: string, app_color: mixed} */
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
     */
    public function isMainHost(?string $host): bool
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

    public function getStr(string $key, string $default = ''): string
    {
        $returnValue = $this->get($key) ?? $default;

        if (! \is_scalar($returnValue)) {
            throw new \LogicException('`'.$key.'` is not stringable');
        }

        return (string) $returnValue;
    }

    public function get(string $key): mixed
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

    public function setCustomProperty(string $key, mixed $value): self
    {
        $camelCaseKey = static::normalizePropertyName($key);
        if (property_exists($this, $camelCaseKey)) {
            $this->$camelCaseKey = $value; // @phpstan-ignore-line
        }

        $this->customProperties[$key] = $value;

        return $this;
    }

    public function getCustomProperty(string $key): mixed
    {
        return $this->customProperties[$key] ?? null;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    /** @return array<string, string> */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /** @param array<string, string> $filters */
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

    /** @return array{javascripts: string[], stylesheets: string[]} */
    public function getAssetsVersionned(): array
    {
        $assetsVersionned = ['javascripts' => [], 'stylesheets' => []];
        foreach (['javascripts', 'stylesheets'] as $row) {
            if (! isset($this->assets[$row])) {
                continue;
            }

            foreach ($this->assets[$row] as $key => $asset) {
                $filepath = $this->params->get('pw.public_dir').$asset;
                $assetsVersionned[$row][$key] = $asset.
                    (file_exists($filepath) ? '?'.substr(md5(\Safe\filemtime($filepath).$filepath), 2, 9) : '');
            }
        }

        return $assetsVersionned;
    }

    /**
     * @psalm-suppress InternalMethod
     */
    public function getView(string $path = null, string $fallback = '@Pushword'): string
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
        return str_starts_with($path, '@') && str_contains($path, '/');
    }

    public function isFirstApp(): bool
    {
        return $this->isFirstApp;
    }

    /**
     * @return string[]|string
     */
    public function getHostForDoctrineSearch(): array|string
    {
        return $this->isFirstApp ? ['', $this->getMainHost()] : $this->getMainHost();
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

        return $this->locales ?? throw new \Exception();
    }

    /**
     * Get the value of name.
     */
    public function getName(): string
    {
        return $this->name;
    }
}
