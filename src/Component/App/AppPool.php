<?php

namespace Pushword\Core\Component\App;

use Exception;
use Pushword\Core\Entity\PageInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Environment as Twig;

final class AppPool
{
    /** @var array<string, AppConfig> */
    private $apps = [];

    private ?string $currentApp = null;

    /**
     * Why there ? Because often, need to check current page don't override App Config. */
    private ?\Pushword\Core\Entity\PageInterface $currentPage = null;

    /** @param array<string, array<string, mixed>> $rawApps */
    public function __construct(array $rawApps, Twig $twig, ParameterBagInterface $parameterBag)
    {
        $firstHost = \strval(array_key_first($rawApps));

        foreach ($rawApps as $mainHost => $app) {
            $this->apps[$mainHost] = new AppConfig($parameterBag, $app, $firstHost == $mainHost ? true : false);
            $this->apps[$mainHost]->setTwig($twig);
        }

        $this->switchCurrentApp($firstHost);
    }

    /**
     * @param string|PageInterface $host
     */
    public function switchCurrentApp($host): self
    {
        if ($host instanceof PageInterface) {
            $this->currentPage = $host;
            $host = $host->getHost();
        }

        $app = $this->get($host);

        $this->currentApp = $app->getMainHost();

        return $this;
    }

    public function get(?string $host = ''): AppConfig
    {
        $host = \in_array($host, [null, ''], true) ? $this->currentApp : $host;
        if (isset($this->apps[$host])) {
            return $this->apps[$host];
        }

        $apps = array_reverse($this->apps, true);
        foreach ($apps as $app) {
            if (\in_array($host, $app->getHosts(), true)) {
                return $app;
            }
        }

        if (! isset($app)) {
            throw new Exception('No AppConfig found (`'.$host.'`)');
        }

        return $app;
    }

    /** @return string[] */
    public function getHosts(): array
    {
        return array_keys($this->apps);
    }

    /** @return array<string, AppConfig> */
    public function getApps(): array
    {
        return $this->apps;
    }

    public function getCurrentPage(): ?PageInterface
    {
        return $this->currentPage;
    }

    /** @param string|array<string>|null $host */
    public function isFirstApp($host = null): bool
    {
        $firstApp = array_key_first($this->apps);

        if (null === $host) {
            return $firstApp === $this->get()->getMainHost();
        }

        if (\is_string($host)) {
            return $firstApp === $host;
        }

        foreach ($host as $singleHost) {
            if ($firstApp === $singleHost) {
                return true;
            }
        }

        return false;
    }

    /**
     * Alias for ->get()->getMainHost().
     */
    public function getMainHost(): ?string
    {
        return $this->currentApp;
    }

    public function sameHost(?string $host): bool
    {
        if ($this->isFirstApp() && null === $host) {
            return true;
        }

        if ($host === $this->currentApp) {
            return true;
        }

        return false;
    }

    public function getApp(string $host = ''): AppConfig
    {
        if ('' === $host) {
            $host = $this->currentApp;
        }

        return $this->get($host);
    }

    /** @return mixed */
    public function getAppValue(string $key, string $host = '')
    {
        return $this->getApp($host)->get($key);
    }
}
