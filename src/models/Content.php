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
class Content
{
    /** @var integer|null */
    private $id;

    /** @var \DateTime|null */
    private $created_at;

    /** @var \DateTime|null */
    private $fetched_at;

    /** @var string */
    private $status;

    /** @var string */
    private $url;

    /** @var string|null */
    private $links;

    /** @var string|null */
    private $type;

    /** @var string|null */
    private $content;

    /**
     * @param string $url
     *
     * @throws \Webubbub\models\Errors\ContentError if url is invalid
     */
    public function __construct($url)
    {
        if (!self::validateUrl($url)) {
            throw new Errors\ContentError("{$url} url is invalid.");
        }

        $this->url = urldecode($url);
        $this->status = 'new';
    }

    /**
     * @return integer|null
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * @return \DateTime|null
     */
    public function createdAt()
    {
        return $this->created_at;
    }

    /**
     * @return \DateTime|null
     */
    public function fetchedAt()
    {
        return $this->fetched_at;
    }

    /**
     * @return string
     */
    public function status()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function url()
    {
        return $this->url;
    }

    /**
     * @return string|null
     */
    public function links()
    {
        return $this->links;
    }

    /**
     * @return string|null
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * @return string|null
     */
    public function content()
    {
        return $this->content;
    }

    /**
     * Return the model values, in order to be passed to the DAO model. Note
     * that additional process might be needed (e.g. setting the required
     * `created_at` for a creation).
     *
     * @return mixed[]
     */
    public function toValues()
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at ? $this->created_at->getTimestamp() : null,
            'fetched_at' => $this->created_at ? $this->created_at->getTimestamp() : null,
            'status' => $this->status,
            'url' => $this->url,
            'links' => $this->links,
            'type' => $this->type,
            'content' => $this->content,
        ];
    }

    /**
     * Create a Content object from given values.
     *
     * It should be used with values coming from the database.
     *
     * @param mixed[] $values
     *
     * @throws \Webubbub\models\Errors\ContentError if a required value is missing
     *                                              or is not valid
     *
     * @return \Webubbub\models\Content
     */
    public static function fromValues($values)
    {
        $required_values = ['id', 'created_at', 'status', 'url'];
        foreach ($required_values as $value_name) {
            if (!isset($values[$value_name])) {
                throw new Errors\ContentError(
                    "{$value_name} value is required."
                );
            }
        }

        $integer_values = ['id', 'created_at', 'fetched_at'];
        foreach ($integer_values as $value_name) {
            if (
                isset($values[$value_name]) &&
                !filter_var($values[$value_name], FILTER_VALIDATE_INT)
            ) {
                throw new Errors\ContentError(
                    "{$value_name} value must be an integer."
                );
            }
        }

        $subscription = new self($values['url']);

        $subscription->id = intval($values['id']);
        $subscription->status = $values['status'];

        $created_at = new \DateTime();
        $created_at->setTimestamp(intval($values['created_at']));
        $subscription->created_at = $created_at;

        if (isset($values['links'])) {
            $subscription->links = $values['links'];
        }
        if (isset($values['type'])) {
            $subscription->type = $values['type'];
        }
        if (isset($values['content'])) {
            $subscription->content = $values['content'];
        }

        if (isset($values['fetched_at'])) {
            $fetched_at = new \DateTime();
            $fetched_at->setTimestamp(intval($values['fetched_at']));
            $subscription->fetched_at = $fetched_at;
        }

        return $subscription;
    }

    /**
     * Check that an URL is valid.
     *
     * @param string $url
     *
     * @return boolean Return true if the URL is valid, false otherwise
     */
    private static function validateUrl($url)
    {
        $url_components = parse_url($url);
        if (!$url_components || !isset($url_components['scheme'])) {
            return false;
        }

        $url_scheme = $url_components['scheme'];
        return $url_scheme === 'http' || $url_scheme === 'https';
    }
}
