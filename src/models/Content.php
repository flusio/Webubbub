<?php

namespace Webubbub\models;

use Minz\Database;
use Minz\Validable;
use Webubbub\utils;

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
#[Database\Table(name: 'contents')]
class Content
{
    use Database\Recordable;
    use Validable;

    public const VALID_STATUSES = ['new', 'fetched', 'delivered'];

    #[Database\Column]
    public int $id;

    #[Database\Column(format: 'U')]
    public \DateTimeImmutable $created_at;

    #[Database\Column(format: 'U')]
    public ?\DateTimeImmutable $fetched_at = null;

    #[Database\Column]
    #[Validable\Inclusion(in: self::VALID_STATUSES, message: 'status "{value}" is invalid')]
    public string $status;

    #[Database\Column]
    #[Validable\Presence(message: 'url "{value}" is invalid URL')]
    #[Validable\Url(message: 'url "{value}" is invalid URL')]
    public string $url;

    #[Database\Column]
    public ?string $links = null;

    #[Database\Column]
    public ?string $type = null;

    #[Database\Column]
    public ?string $content = null;

    public function __construct(string $url)
    {
        $this->url = urldecode($url);
        $this->status = 'new';
    }

    /**
     * Mark a content as fetched, setting the given values
     */
    public function fetch(string $content, string $type, string $links): void
    {
        $this->content = $content;
        $this->type = $type;
        $this->links = $links;

        $fetched_at = \Minz\Time::now();
        $this->fetched_at = $fetched_at;
        $this->status = 'fetched';
    }

    /**
     * Mark a content as delivered
     */
    public function deliver(): void
    {
        if ($this->status !== 'fetched') {
            throw new Errors\ContentError(
                "Content cannot be delivered with `{$this->status}` status."
            );
        }

        $this->status = 'delivered';
    }

    /**
     * Return wheter a content is allowed on the hub or not.
     */
    #[Validable\Check]
    public function checkIsAllowed(): void
    {
        $is_valid = utils\AllowedOriginHelper::isOriginAllowed($this->url);
        if (!$is_valid) {
            $this->addError('url', 'url_not_allowed', "url \"{$this->url}\" is not authorized");
        }
    }

    /**
     * Delete the Contents that can be deleted and return the number of
     * deletions.
     */
    public static function deleteOldContents(): int
    {
        $sql = <<<'SQL'
            DELETE FROM contents
            WHERE status = 'delivered'
            OR created_at < :older_than
        SQL;

        $older_than = \Minz\Time::ago(1, 'week');

        $database = \Minz\Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':older_than' => $older_than->format('U'),
        ]);

        return $statement->rowCount();
    }
}
