<?php

namespace Pushword\Core\Entity\PageTrait;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use LogicException;
use Pushword\Core\Entity\PageInterface;

trait PageParentTrait
{
    /**
     * @ORM\ManyToOne(targetEntity="Pushword\Core\Entity\PageInterface", inversedBy="childrenPages")
     * TODO: assert parentPage is not currentPage
     */
    protected ?PageInterface $parentPage = null;

    /**
     * @ORM\OneToMany(targetEntity="Pushword\Core\Entity\PageInterface", mappedBy="parentPage")
     * @ORM\OrderBy({"publishedAt": "DESC", "priority": "DESC"})
     *
     * @var PageInterface[]|Collection<int, PageInterface>|null
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
     * @return PageInterface[]|Collection<int, PageInterface>
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
