<?php

namespace Pushword\Core\Entity\SharedTrait;

use Doctrine\ORM\Mapping as ORM;

trait HostTrait
{
    /**
     * @ORM\Column(type="string", length=253, nullable=true)
     */
    protected $host;

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function setHost(?string $host): self
    {
        $this->host = $host;

        return $this;
    }
}