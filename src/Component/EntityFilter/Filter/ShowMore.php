<?php

namespace Pushword\Core\Component\EntityFilter\Filter;

use Pushword\Core\AutowiringTrait\RequiredAppTrait;
use Pushword\Core\AutowiringTrait\RequiredTwigTrait;

class ShowMore extends AbstractFilter
{
    use RequiredAppTrait;
    use RequiredTwigTrait;

    public function apply(mixed $propertyValue): string
    {
        return $this->showMore($this->string($propertyValue));
    }

    private function showMore(string $body): string
    {
        $bodyParts = explode('<!--start-show-more-->', $body);
        $body = '';
        $template = $this->twig->load($this->getApp()->getView('/component/show_more.html.twig'));
        foreach ($bodyParts as $bodyPart) {
            if (! str_contains($bodyPart, '<!--end-show-more-->')) {
                $body .= $bodyPart;

                continue;
            }

            $id = 'sh-'.substr(md5('sh'.$bodyPart), 0, 4);
            $body .= $template->renderBlock('before', ['id' => $id])
                .str_replace('<!--end-show-more-->', $template->renderBlock('after', ['id' => $id]), $bodyPart);
        }

        return $body;
    }
}
