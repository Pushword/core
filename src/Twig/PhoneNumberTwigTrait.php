<?php

namespace Pushword\Core\Twig;

use Pushword\Core\Component\App\AppConfig;

trait PhoneNumberTwigTrait
{
    private \Twig\Environment $twig;

    abstract public function getApp(): AppConfig;

    public function renderPhoneNumber(string $number, string $class = ''): string
    {
        $template = $this->getApp()->getView('/component/phone_number.html.twig');

        return trim($this->twig->render($template, [
            'number' => str_replace([' ', '&nbsp;', '.'], '', $number),
            'number_readable' => str_replace(' ', '&nbsp;', (string) preg_replace('#^\+[0-9]{2} ?#', '0', $number)),
            'class' => $class,
        ]));
    }
}
