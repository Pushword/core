<?php

namespace Pushword\Core\Twig;

use Exception;
use Pushword\Core\Component\App\AppConfig;
use Pushword\Core\Entity\PageInterface;
use Pushword\Core\Router\RouterInterface;

trait LinkTwigTrait
{
    private RouterInterface $router;

    abstract public function getApp(): AppConfig;

    public function renderLink($anchor, $path, $attr = [], bool $encrypt = true): string
    {
        if (\is_bool($attr)) {
            $encrypt = $attr;
            $attr = [];
        }

        if (\is_array($path)) {
            $attr = $path;
            if (! isset($attr['href'])) {
                throw new Exception('attr must contain href for render a link.');
            }
            $path = $attr['href'];
            unset($attr['href']);
        }

        if (\is_string($attr)) {
            $attr = ['class' => $attr];
        }

        if ($path instanceof PageInterface) {
            $path = $this->router->generate($path);
        }

        if ($encrypt) {
            if (false !== strpos($path, 'mailto:') && filter_var($anchor, \FILTER_VALIDATE_EMAIL)) {
                return $this->renderEncodedMail($anchor);
            }
            $attr = array_merge($attr, ['data-rot' => self::encrypt($path)]);
            $template = $this->getApp()->getView('/component/link_js.html.twig');
            $renderedLink = $this->twig->render($template, ['anchor' => $anchor, 'attr' => $attr]);
        } else {
            $attr = array_merge($attr, ['href' => $path]);
            $template = $this->getApp()->getView('/component/link.html.twig');
            $renderedLink = $this->twig->render($template, ['anchor' => $anchor, 'attr' => $attr]);
        }

        return $renderedLink;
    }

    public static function encrypt(string $path): string
    {
        if (0 === strpos($path, 'http://')) {
            $path = '-'.\Safe\substr($path, 7);
        } elseif (0 === strpos($path, 'https://')) {
            $path = '_'.\Safe\substr($path, 8);
        } elseif (0 === strpos($path, 'mailto:')) {
            $path = '@'.\Safe\substr($path, 7);
        }

        return str_rot13($path);
    }

    public static function decrypt(string $string)
    {
        $path = str_rot13($string);

        if (0 === strpos($path, '-')) {
            $path = 'http://'.\Safe\substr($path, 1);
        } elseif (0 === strpos($path, '_')) {
            $path = 'https://'.\Safe\substr($path, 1);
        } elseif (0 === strpos($path, '@')) {
            $path = 'mailto:'.\Safe\substr($path, 1);
        }

        return $path;
    }

    public function renderEncodedMail($mail, $class = '')
    {
        $template = $this->getApp()->getView('/component/encoded_mail.html.twig');

        return $this->twig->render($template, [
            'mail_readable' => self::readableEncodedMail($mail),
            'mail_encoded' => str_rot13($mail),
            'mail' => $mail,
            'class' => $class,
        ]);
    }

    public static function readableEncodedMail(string $mail)
    {
        return str_replace('@', '<svg width="1em" height="1em" viewBox="0 0 16 16" class="inline-block" '
        .'fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M13.106 '
        .'7.222c0-2.967-2.249-5.032-5.482-5.032-3.35 0-5.646 2.318-5.646 5.702 0 3.493 2.235 5.708 5.762'
        .' 5.708.862 0 1.689-.123 2.304-.335v-.862c-.43.199-1.354.328-2.29.328-2.926 0-4.813-1.88-4.813-4.798'
        .' 0-2.844 1.921-4.881 4.594-4.881 2.735 0 4.608 1.688 4.608 4.156 0 1.682-.554 2.769-1.416 2.769-.492'
        .' 0-.772-.28-.772-.76V5.206H8.923v.834h-.11c-.266-.595-.881-.964-1.6-.964-1.4 0-2.378 1.162-2.378 2.823 0'
        .' 1.737.957 2.906 2.379 2.906.8 0 1.415-.39 1.709-1.087h.11c.081.67.703 1.148 1.503 1.148 1.572 0 2.57-1.415'
        .' 2.57-3.643zm-7.177.704c0-1.197.54-1.907 1.456-1.907.93 0 1.524.738 1.524 1.907S8.308 9.84 7.371 9.84c-.895'
        .' 0-1.442-.725-1.442-1.914z"/></svg>', $mail);
    }
}
