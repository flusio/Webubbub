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
     * @param integer $seconds
     *
     * @return \DateTime
     */
    public static function fromNow($seconds)
    {
        $from_now = self::now();
        $from_now->modify("+{$seconds} seconds");
        return $from_now;
    }

    /**
     * Return a timestamp from the past.
     *
     * @param integer $seconds
     *
     * @return \DateTime
     */
    public static function ago($seconds)
    {
        $ago = self::now();
        $ago->modify("-{$seconds} seconds");
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
