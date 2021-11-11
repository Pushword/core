<?php

namespace Pushword\Core\Router;

use Pushword\Core\Entity\PageInterface;
use Symfony\Component\Routing\RouterInterface as SfRouterInterface;

interface RouterInterface
{
    public const PATH = 'pushword_page';

    public const CUSTOM_HOST_PATH = 'custom_host_pushword_page';

    public function generatePathForHomePage(?PageInterface $page = null, bool $canonical = false): string;

    /**
     * @param string|PageInterface $slug
     * @param int|string|null      $pager
     */
    public function generate($slug = 'homepage', bool $canonical = false, $pager = null): string;

    public function setUseCustomHostPath(bool $useCustomHostPath = true): self;

    public function getRouter(): SfRouterInterface;
}
