<?php

namespace Pushword\Core\Component\App;

use Exception;
use Pushword\Core\Entity\PageInterface;

final class AppPool
{
    /** @var array */
    private $apps = [];
    /** @var string */
    private $currentApp;
    /** @var string */
    private $host; // often same as currentApp

    /**
     * Why there ? Because often, need to check current page don't override App Config.
     *
     *  @var PageInterface */
    private $currentPage;

    public function __construct(?string $host, array $apps, \Twig\Environment $twig = null)
    {
        $this->host = $host;

        foreach ($apps as $mainHost => $app) {
            $this->apps[$mainHost] = new AppConfig($app, array_key_first($apps) == $mainHost ? true : false);
            if ($twig) {
                $this->apps[$mainHost]->setTwig($twig);
            }
        }

        $this->switchCurrentApp();
    }

    /**
     * Not good.
     */
    public function switchCurrentApp($host = null): self
    {
        if ($host instanceof PageInterface) {
            $this->currentPage = $host;
            $host = $host->getHost();
        }
        $this->host = $host;

        $app = $this->get($this->host);

        $this->currentApp = $app->getMainHost();

        return $this;
    }

    public function get(?string $host = ''): AppConfig
    {
        $host = ! $host ? $this->currentApp : $host;
        if (isset($this->apps[$host])) {
            return $this->apps[$host];
        }

        $apps = array_reverse($this->apps, true);
        foreach ($apps as $app) {
            if (\in_array($host, $app->getHosts())) {
                return $app;
            }
        }

        return $app; //throw new Exception('No AppConfig found (`'.$host.'`)');
    }

    public function getHosts(): array
    {
        return array_keys($this->apps);
    }

    /**
     * Get the value of apps.
     */
    public function getApps(): array
    {
        return $this->apps;
    }

    public function getCurrentPage(): ?PageInterface
    {
        return $this->currentPage;
    }

    public function isFirstApp($host = null): bool
    {
        $firstApp = array_key_first($this->apps);

        if (null === $host) {
            return $firstApp === $this->get()->getMainHost();
        }

        if (\is_string($host)) {
            return $firstApp === $host;
        }

        foreach ($host as $h) {
            if ($firstApp === $h) {
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

    public function sameHost($host): bool
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
        if (! $host) {
            $host = $this->currentApp;
        }

        return $this->get($host);
    }

    public function getAppValue(?string $key = null, string $host = '')
    {
        return $this->getApp($host)->get($key);
    }
}
