<?php

namespace Pushword\Core\Entity\SharedTrait;

interface TimestampableInterface
{
    /** @return $this */
    public function setCreatedAt(\DateTime $createdAt);

    /**
     * @return \DateTimeInterface
     */
    public function getCreatedAt();

    /**
     * @return $this
     */
    public function setUpdatedAt(\DateTime $updatedAt);

    /**
     * @return \DateTimeInterface
     */
    public function getUpdatedAt();
}
