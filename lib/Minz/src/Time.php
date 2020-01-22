<?php

namespace Minz;

/**
 * Wrapper around `date_create()` function, to provide test capabilities.
 */
class Time
{
    /** @var \DateTime|integer|null */
    public static $freezed_timestamp;

    /**
     * @return \DateTime
     */
    public static function now()
    {
        if (self::$freezed_timestamp && is_int(self::$freezed_timestamp)) {
            $date = new \DateTime();
            $date->setTimestamp(self::$freezed_timestamp);
            return $date;
        } elseif (self::$freezed_timestamp && self::$freezed_timestamp instanceof \DateTime) {
            return self::$freezed_timestamp;
        } else {
            return \date_create();
        }
    }

    /**
     * Return a timestamp from the future.
     *
     * @see https://www.php.net/manual/en/datetime.formats.relative.php
     *
     * @param integer $number
     * @param string $unit
     *
     * @return \DateTime
     */
    public static function fromNow($number, $unit)
    {
        $from_now = self::now();
        $from_now->modify("+{$number} {$unit}");
        return $from_now;
    }

    /**
     * Return a timestamp from the past.
     *
     * @see https://www.php.net/manual/en/datetime.formats.relative.php
     *
     * @param integer $number
     * @param string $unit
     *
     * @return \DateTime
     */
    public static function ago($number, $unit)
    {
        $ago = self::now();
        $ago->modify("-{$number} {$unit}");
        return $ago;
    }

    /**
     * Freeze the time at a given timestamp.
     *
     * @param \DateTime|integer $timestamp
     */
    public static function freeze($timestamp)
    {
        self::$freezed_timestamp = $timestamp;
    }

    /**
     * Unfreeze the time.
     */
    public static function unfreeze()
    {
        self::$freezed_timestamp = null;
    }
}
