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

    public function setSlug($slug, $set = false): self;

    public function getMainContent(): string;

    public function setMainContent($mainContent): self;

    public function getPublishedAt(): DateTimeInterface;

    public function setPublishedAt(DateTimeInterface $publishedAt): self;

    // ---

    // ParentTrait
    public function getParentPage(): ?self;

    public function setParentPage(?self $parentPage): self;

    /**
     * @return PageInterface[]|Collection<int, PageInterface>
     */
    public function getChildrenPages();

    public function hasChildrenPages();

    public function getRedirection();

    public function getRedirectionCode();

    public function getCreatedAt();

    public function getTemplate(): ?string;

    public function getH1();

    public function getTitle();

    public function getName();

    public function getPriority(): int;

    // PageI18n
    public function getLocale();

    public function setLocale($locale);

    // Page Extended
    public function getExtendedPage(): ?self;

    public function setExtendPage(?self $extendedPage): self;

    public function getMetaRobots(): string;

    public function setMainImage(?MediaInterface $mainImage): self;

    public function __toString(): string;
}
