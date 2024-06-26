<?php

namespace Pushword\Core\Entity\PageTrait;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Pushword\Core\Entity\User;

trait PageEditorTrait
{
    #[ORM\ManyToOne(targetEntity: User::class)]
    protected ?User $editedBy = null; // @phpstan-ignore-line

    /*
     * @ORM\OneToMany(
     *     targetEntity="Pushword\Core\Entity\PageHasEditor",
     *     mappedBy="page",
     *     cascade={"all"},
     *     orphanRemoval=true
     * )
     * @ORM\OrderBy({"editedAt": "DESC"})
     *
    protected ?ArrayCollection $pageHasEditors;
    /**/

    #[ORM\ManyToOne(targetEntity: User::class)]
    protected ?User $createdBy = null; // @phpstan-ignore-line

    #[ORM\Column(type: Types::TEXT, options: ['default' => ''])]
    protected string $editMessage = '';

    public function getEditedBy(): ?User
    {
        return $this->editedBy;
    }

    public function setEditedBy(?User $user): void
    {
        $this->editedBy = $user;
    }

    /**
     * Get targetEntity="Pushword\Core\Entity\User",.
     */
    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    /**
     * Set targetEntity="Pushword\Core\Entity\User",.
     */
    public function setCreatedBy(?User $user): void
    {
        $this->createdBy = $user;
    }

    /**
     * public function setPageHasEditors($pageHasEditors): void
     * {
     * $this->pageHasEditors = new ArrayCollection();
     * foreach ($pageHasEditors as $pageHasEditor) {
     * $this->addPageHasEditor($pageHasEditor);
     * }
     * }.
     *
     * public function getPageHasEditors(): ArrayCollection
     * {
     * return $this->pageHasEditors ?? new ArrayCollection();
     * }
     *
     * public function addPageHasEditor(PageHasEditor $pageHasEditor): void
     * {
     * $this->getPageHasEditors();
     * $pageHasEditor->setPage($this);
     * $this->pageHasEditors[] = $pageHasEditor;
     * }
     *
     * public function resetPageHasEditors(): void
     * {
     * foreach ($this->getPageHasEditors() as $pageHasEditor) {
     * $this->removePageHasEditor($pageHasEditor);
     * }
     * }
     *
     * public function removePageHasEditor(PageHasEditor $pageHasEditor): void
     * {
     * $this->getPageHasEditors()->removeElement($pageHasEditor);
     * }
     * /**/

    /**
     * Get the value of editMessage.
     */
    public function getEditMessage(): string
    {
        return $this->editMessage;
    }

    /**
     * Set the value of editMessage.
     */
    public function setEditMessage(?string $editMessage): self
    {
        $this->editMessage = (string) $editMessage;

        return $this;
    }
}
