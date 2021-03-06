<?php

namespace Pushword\Core\Component\Router;

use Pushword\Core\Entity\PageInterface as Page;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class RouterTwigExtension extends AbstractExtension
{
    /** @var RouterInterface */
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('homepage', [$this->router, 'generatePathForHomePage']),
            new TwigFunction('page', [$this->router, 'generate']),
            new TwigFunction('is_current_page', [$this, 'isCurrentPage']), // used ?
        ];
    }

    public function isCurrentPage(string $uri, ?Page $currentPage): bool
    {
        return
            null === $currentPage || $uri != $this->router->generate($currentPage->getRealSlug())
            ? false
            : true;
    }
}
