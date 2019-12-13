<?php

namespace Webubbub\models;

/**
 * Represent a content to deliver to subscribers.
 *
 * A content has a url, corresponding to a subscription topic.
 * Content has to be delivered, and is deleted after that.
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

    /** @var string */
    private $url;

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
     * @return string
     */
    public function url()
    {
        return $this->url;
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
            'url' => $this->url,
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
        $required_values = ['id', 'url', 'created_at'];
        foreach ($required_values as $value_name) {
            if (!isset($values[$value_name])) {
                throw new Errors\ContentError(
                    "{$value_name} value is required."
                );
            }
        }

        $integer_values = ['id', 'created_at'];
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

        $created_at = new \DateTime();
        $created_at->setTimestamp(intval($values['created_at']));
        $subscription->created_at = $created_at;

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
