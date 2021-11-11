<?php

namespace Pushword\Core\Entity;

use DateTimeInterface;
use Doctrine\Common\Collections\Collection;
use Pushword\Core\Entity\PageTrait\PageEditorInterface;
use Pushword\Core\Entity\SharedTrait\CustomPropertiesInterface;
use Pushword\Core\Entity\SharedTrait\HostInterface;
use Pushword\Core\Entity\SharedTrait\IdInterface;
use Pushword\Core\Entity\SharedTrait\TimestampableInterface;

interface PageInterface extends HostInterface, IdInterface, TimestampableInterface, CustomPropertiesInterface, PageEditorInterface
{
    // PageTrait
    public function getSlug(): string;

    public function getRealSlug(): string;

    public function setSlug(?string $slug): self;

    public function getMainContent(): string;

    public function setMainContent(?string $mainContent): self;

    public function getPublishedAt(): DateTimeInterface;

    public function setPublishedAt(DateTimeInterface $publishedAt): self;

    // ---

    // ParentTrait
    public function getParentPage(): ?self;

    public function setParentPage(?self $page): self;

    /**
     * @return PageInterface[]|Collection<int, PageInterface>|array
     */
    public function getChildrenPages();

    public function hasChildrenPages(): bool;

    public function hasRedirection(): bool;

    public function getRedirection(): string;

    public function getRedirectionCode(): int;

    public function getCreatedAt();

    public function getTemplate(): ?string;

    public function getH1(): ?string;

    public function getTitle(): ?string;

    public function getName(): ?string;

    public function getPriority(): int;

    // Page Extended
    public function getExtendedPage(): ?self;

    public function setExtendPage(?self $page): self;

    public function getMetaRobots(): string;

    public function setMainImage(?MediaInterface $media): self;

    public function __toString(): string;

    // PageI18n
    public function getLocale(): string;

    public function setLocale(string $locale): self;

    public function addTranslation(self $page, bool $recursive = true): self;

    public function removeTranslation(self $page, bool $recursive = true): self;
}
