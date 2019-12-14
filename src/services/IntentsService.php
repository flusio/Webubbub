<?php

namespace Webubbub\services;

/**
 * Contain some side-effects methods to check intents of subscribers.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class IntentsService
{
    /**
     * Return a random string. It is NOT crypto-secure!
     *
     * @return string
     */
    public function generateChallenge()
    {
        return sha1(rand());
    }
}
