<?php

namespace Pushword\Core\Component\EntityFilter\Filter;

class Image extends AbstractFilter
{
    use RequiredAppTrait;
    use RequiredTwigTrait;

    /**
     * @param string $string
     *
     * @return string
     */
    public function apply($string)
    {
        return $this->convertMarkdownImage($string);
    }

    public function convertMarkdownImage(string $body): string
    {
        preg_match_all('/(?:!\[(.*?)\]\((.*?)\))/', $body, $matches);

        if (! isset($matches[1])) {
            return $body;
        }

        $nbrMatch = \count($matches[0]);
        for ($k = 0; $k < $nbrMatch; ++$k) {
            $renderImg = '<div>'.$this->twig->render(
                $this->app->getView('/component/inline_image.html.twig', $this->twig),
                [
                    //"image_wrapper_class" : "mimg",'
                    'image_src' => $matches[2][$k],
                    'image_alt' => htmlspecialchars($matches[1][$k]),
                ]
            ).'</div>';
            $body = str_replace($matches[0][$k], $renderImg, $body);
        }

        return $body;
    }
}
