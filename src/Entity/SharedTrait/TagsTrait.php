<?php

namespace Pushword\Core\Entity\SharedTrait;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait TagsTrait
{
    /** @var string[] */
    #[ORM\Column(type: Types::JSON)]
    protected array $tags = [];

    public function getTags(): string
    {
        $tags = implode(', ', $this->tags);

        return $tags.('' !== $tags ? ', ' : '');
    }

    /** @return string[] */
    public function getTagList(): array
    {
        return $this->tags;
    }

    /** @param string[]|string $tags */
    public function setTags(array|string $tags): self
    {
        if (\is_string($tags)) {
            $tags = explode(',', $tags);
            $tags = array_filter(array_map('trim', $tags));
        }

        $this->tags = $tags;

        return $this;
    }
}
