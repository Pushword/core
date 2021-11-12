<?php

namespace Pushword\Core\AutowiringTrait;

use Pushword\Core\Entity\MediaInterface;

trait RequiredMediaClass
{
    /**
     * @var class-string<MediaInterface>
     */
    private string $mediaClass;

    /** @required */
    public function setMediaClass(string $mediaClass): self
    {
        $this->mediaClass = $mediaClass;

        return $this;
    }

    public function getMediaClass(): string
    {
        return $this->mediaClass;
    }
}
