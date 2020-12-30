<?php

namespace Pushword\Core\Entity;

use Pushword\Core\Component\Filter\FilterInterface;
use Pushword\Core\Entity\SharedTrait\HostInterface;
use Pushword\Core\Entity\SharedTrait\IdInterface;
use Pushword\Core\Entity\SharedTrait\TimestampableInterface;

interface PageInterface extends HostInterface, IdInterface, TimestampableInterface
{
    // PageTrait
    public function getSlug(): ?string;

    public function getRealSlug(): ?string;

    public function setSlug($slug, $set = false): self;

    public function getMainContent(): ?string;

    public function setMainContent(?string $mainContent): self;

    // ---

    // ParentTrait
    public function getParentPage(): ?self;

    public function setParentPage(?self $parentPage): self;

    public function getChildrenPages();

    // ---

    public function getRedirection();

    public function getRedirectionCode();

    public function getCreatedAt();

    public function getTemplate(); // todo change to getView

    public function getCustomProperty(string $name);

    public function getH1();

    public function getTitle();

    public function getName();

    public function setContent(FilterInterface $mainContentManager);

    // PageI18n
    public function getLocale();

    public function setLocale($locale);
}