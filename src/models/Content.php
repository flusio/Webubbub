<?php

namespace Webubbub\models;

/**
 * Represent a content created by publishers, it is delivered to subscribers.
 *
 * A content has a url, corresponding to a subscription topic. Content has to
 * be fetched before being delivered.
 *
 * Once delivered to all subscribers, it can be deleted.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Content extends \Minz\Model
{
    public const VALID_STATUSES = ['new', 'fetched'];

    public const PROPERTIES = [
        'id' => 'integer',

        'created_at' => 'datetime',

        'fetched_at' => 'datetime',

        'status' => [
            'type' => 'string',
            'required' => true,
            'validator' => '\Webubbub\models\Content::validateStatus',
        ],

        'url' => [
            'type' => 'string',
            'required' => true,
            'validator' => '\Webubbub\models\Content::validateUrl',
        ],

        'links' => 'string',

        'type' => 'string',

        'content' => 'string',
    ];

    /**
     * @param string $url
     *
     * @throws \Minz\Errors\ModelPropertyError if url is invalid
     */
    public static function new($url)
    {
        return new self([
            'url' => urldecode($url),
            'status' => 'new',
        ]);
    }

    /**
     * Initialize a Content from values (usually from database).
     *
     * @param array $values
     *
     * @throws \Minz\Error\ModelPropertyError if one of the value is invalid
     */
    public function __construct($values)
    {
        parent::__construct(self::PROPERTIES);
        $this->fromValues($values);
    }

    /**
     * Mark a content as fetched, setting the given values
     *
     * @param string $content
     * @param string $type
     * @param string $links
     */
    public function fetch($content, $type, $links)
    {
        $this->setProperty('content', $content);
        $this->setProperty('type', $type);
        $this->setProperty('links', $links);

        $fetched_at = new \DateTime();
        $fetched_at->setTimestamp(\Minz\Time::now());
        $this->setProperty('fetched_at', $fetched_at);
        $this->setProperty('status', 'fetched');
    }

    /**
     * Check that an URL is valid.
     *
     * @param string $url
     *
     * @return boolean Return true if the URL is valid, false otherwise
     */
    public static function validateUrl($url)
    {
        $url_components = parse_url($url);
        if (!$url_components || !isset($url_components['scheme'])) {
            return false;
        }

        $url_scheme = $url_components['scheme'];
        return $url_scheme === 'http' || $url_scheme === 'https';
    }

    /**
     * Check the given status is valid.
     *
     * @param string $status
     *
     * @return boolean|string It returns true if the status is valid, or a string
     *                        explaining the error otherwise.
     */
    public static function validateStatus($status)
    {
        if (!in_array($status, self::VALID_STATUSES)) {
            $statuses_as_string = implode(', ', self::VALID_STATUSES);
            return "valid values are {$statuses_as_string}";
        }

        return true;
    }
}
