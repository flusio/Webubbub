<?php

namespace Minz;

/**
 * Wrapper around `time()` function, to provide test capabilities.
 */
class Time
{
    /** @var integer|null */
    public static $freezed_timestamp;

    /**
     * @return integer
     */
    public static function now()
    {
        if (self::$freezed_timestamp) {
            return self::$freezed_timestamp;
        } else {
            return \time();
        }
    }

    /**
     * Return a timestamp from the future.
     *
     * @param integer $seconds
     *
     * @return integer
     */
    public static function fromNow($seconds)
    {
        return self::now() + $seconds;
    }

    /**
     * Return a timestamp from the past.
     *
     * @param integer $seconds
     *
     * @return integer
     */
    public static function ago($seconds)
    {
        return self::now() - $seconds;
    }

    /**
     * Freeze the time at a given timestamp.
     *
     * @param integer $timestamp
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
