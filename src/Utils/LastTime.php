<?php

namespace Pushword\Core\Utils;

use DateInterval;
use DateTime;

/**
 * Usage
 * (new LastTime($rootDir.'/../var/lastNoficationUpdatePageSendAt'))->wasRunSince(new DateInterval('P2H')).
 */
class LastTime
{
    protected string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function wasRunSince(DateInterval $dateInterval): bool
    {
        $dateTime = $this->get();

        if (null === $dateTime || $dateTime->add($dateInterval) < new DateTime('now')) {
            return false;
        }

        return true;
    }

    /**
     * Return false if never runned else last datetime it was runned.
     * If $default is set, return $default time if never runned.
     */
    public function get(?string $default = null): ?Datetime
    {
        if (! file_exists($this->filePath)) {
            return null === $default ? null : new DateTime($default);
        }

        return new DateTime('@'.\Safe\filemtime($this->filePath));
    }

    public function setWasRun(string $datetime = 'now', bool $setIfNotExist = true): void
    {
        if (! file_exists($this->filePath)) {
            if (false === $setIfNotExist) {
                return;
            }
            \Safe\file_put_contents($this->filePath, '');
        }

        \Safe\touch($this->filePath, (new DateTime($datetime))->getTimestamp());
    }

    /**
     * alias for set was run.
     */
    public function set(string $datetime = 'now'): void
    {
        $this->setWasRun($datetime);
    }
}
