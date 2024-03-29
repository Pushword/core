<?php

namespace Pushword\Core\Controller;

use Symfony\Contracts\Service\Attribute\Required;
use Twig\Environment as Twig;

trait RenderTrait
{
    private Twig $twig;

    #[Required]
    public function setTwig(Twig $twig): void
    {
        $this->twig = $twig;
    }

    /**
     * Returns a rendered view.
     * Use by abstract controller without de deprecation message.
     *
     * @param array<mixed> $parameters
     */
    protected function renderView(string $view, array $parameters = []): string
    {
        return $this->twig->render($view, $parameters);
    }
}
