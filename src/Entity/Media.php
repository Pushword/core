<?php

namespace Pushword\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Pushword\Core\Entity\MediaTrait\MediaLoaderTrait;
use Pushword\Core\Entity\MediaTrait\MediaTrait;
use Pushword\Core\Entity\SharedTrait\CustomPropertiesTrait;
use Pushword\Core\Entity\SharedTrait\IdTrait;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * #UniqueEntity({"media"}, message="Media name is ever taken by another media.")
 * #UniqueEntity({"name"}, message="Name is ever taken by another media..").
 */
#[Vich\Uploadable]
#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
class Media implements MediaInterface
{
    use CustomPropertiesTrait;
    use IdTrait;
    use MediaLoaderTrait;
    use MediaTrait;

    public function __construct()
    {
        $this->updatedAt ??= new \DateTime();
        $this->createdAt ??= new \DateTime();
    }
}
