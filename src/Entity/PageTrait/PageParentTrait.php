<?php

namespace Pushword\Core\Entity\PageTrait;

use Doctrine\ORM\Mapping as ORM;
use LogicException;
use Pushword\Core\Entity\PageInterface;

trait PageParentTrait
{
    /**
     * @ORM\ManyToOne(targetEntity="Pushword\Core\Entity\PageInterface", inversedBy="childrenPages")
     * TODO: assert parentPage is not currentPage
     */
    protected $parentPage;

    /**
     * @ORM\OneToMany(targetEntity="Pushword\Core\Entity\PageInterface", mappedBy="parentPage")
     * @ORM\OrderBy({"publishedAt": "DESC", "priority": "DESC"})
     */
    protected $childrenPages;

    public function getParentPage(): ?self
    {
        return $this->parentPage;
    }

    // todo, move to assert
    private function validateParentPage(PageInterface $parentPage): bool
    {
        if ($parentPage === $this) {
            return false;
        }

        return $parentPage->getParentPage() ? $this->validateParentPage($parentPage->getParentPage()) : true;
    }

    public function setParentPage(?PageInterface $parentPage): self
    {
        if ($parentPage && ! $this->validateParentPage($parentPage)) {
            throw new LogicException('Current Page can\'t be it own parent page.');
        }

        $this->parentPage = $parentPage;

        return $this;
    }

    /**
     * @return PageInterface[]
     */
    public function getChildrenPages()
    {
        return null !== $this->childrenPages ? $this->childrenPages : [];
    }

    public function hasChildrenPages(): bool
    {
        return null !== $this->childrenPages && false === $this->childrenPages->isEmpty();
    }
}
