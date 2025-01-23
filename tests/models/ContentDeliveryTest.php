<?php

namespace Webubbub\models;

use PHPUnit\Framework\TestCase;

class ContentDeliveryTest extends TestCase
{
    use \Minz\Tests\TimeHelper;

    #[\PHPUnit\Framework\Attributes\DataProvider('triesToDelayProvider')]
    public function testRetryLater(int $initial_tries, int $delay): void
    {
        $this->freeze();

        $content_delivery = new ContentDelivery(1, 1);
        $content_delivery->try_at = \Minz\Time::ago(1000, 'seconds');
        $content_delivery->tries_count = $initial_tries;

        $content_delivery->retryLater();

        $this->assertSame(
            \Minz\Time::fromNow($delay, 'seconds')->getTimestamp(),
            $content_delivery->try_at->getTimestamp()
        );
        $this->assertSame($initial_tries + 1, $content_delivery->tries_count);
    }

    public function testRetryLaterFailsIfMaxTriesReached(): void
    {
        $this->expectException(Errors\ContentDeliveryError::class);
        $this->expectExceptionMessage(
            'Content delivery has reached the maximum of allowed number of tries (7).'
        );

        $content_delivery = new ContentDelivery(1, 1);
        $content_delivery->try_at = \Minz\Time::ago(1000, 'seconds');
        $content_delivery->tries_count = ContentDelivery::MAX_TRIES_COUNT;

        $content_delivery->retryLater();
    }

    /**
     * @return array<array{int, int}>
     */
    public static function triesToDelayProvider(): array
    {
        return [
            [0, 5], // when initial number of tries is 0, try_at will be incremented
                    // by 5 seconds from now.
            [1, 25],
            [2, 125], // ~2 minutes
            [3, 625], // ~10 minutes
            [4, 3125], // ~52 minutes
            [5, 15625], // ~4 hours 20 minutes
            [6, 78125], // ~21 hours 42 minutes
        ];
    }
}
