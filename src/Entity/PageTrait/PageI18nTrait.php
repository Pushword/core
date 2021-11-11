<?php

namespace Pushword\Core\Entity\PageTrait;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Pushword\Core\Entity\PageInterface;

trait PageI18nTrait
{
    /**
     * //rfc5646.
     *
     * @ORM\Column(type="string", length=5)
     */
    protected string $locale = '';

    /**
     * @ORM\ManyToMany(targetEntity="Pushword\Core\Entity\PageInterface")
     *
     * @var Collection<string, PageInterface>|null
     */
    protected ?Collection $translations = null;

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @param Collection<string, PageInterface> $translations
     */
    public function setTranslations($translations): self
    {
        $this->translations = $translations;

        return $this;
    }

    /**
     * @return Collection<string, PageInterface>
     */
    public function getTranslations(): Collection
    {
        return null !== $this->translations ? $this->translations : new ArrayCollection();
    }

    public function addTranslation(PageInterface $translation, bool $recursive = true): self
    {
        if (! $this->getTranslations()->contains($translation) && $this != $translation) {
            $this->translations[] = $translation;
        }

        // Add the other ('ever exist') translations to the new added Translation
        if ($recursive) {
            foreach ($this->getTranslations() as $otherTranslation) {
                $translation->addTranslation($otherTranslation, false);
            }
        }

        // Reversing the syncing
        // Add this Page to the translated Page
        // + Add the translated Page to the other translation
        if ($recursive) {
            $translation->addTranslation($this, false);

            foreach ($this->getTranslations() as $otherTranslation) {
                if ($otherTranslation != $this // déjà fait
                    && $otherTranslation != $translation // on ne se référence pas soit-même
                ) {
                    $otherTranslation->addTranslation($translation, false);
                }
            }
        }

        return $this;
    }

    public function removeTranslation(PageInterface $translation, bool $recursive = true): self
    {
        if ($this->getTranslations()->contains($translation)) {
            $this->getTranslations()->removeElement($translation);

            if (true === $recursive) {
                foreach ($this->getTranslations() as $otherTranslation) {
                    $translation->removeTranslation($otherTranslation, false);
                }
            }
        }

        if (true === $recursive) {
            $translation->removeTranslation($this, false);

            foreach ($this->getTranslations() as $otherTranslation) {
                if ($otherTranslation != $this // déjà fait
                    && $otherTranslation != $translation // on ne se déréférence pas soit-même
                ) {
                    $otherTranslation->removeTranslation($translation, false);
                }
            }
        }

        return $this;
    }
}
