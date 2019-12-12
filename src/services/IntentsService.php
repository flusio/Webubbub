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

    /**
     * Confirm with a subscriber its (un)subscription is intended.
     *
     * This part could be largely improved by using curl_multi_exec
     * @see https://www.php.net/manual/en/function.curl-multi-exec.php
     *
     * @param string $intent_callback The callback to fetch, containing the
     *                                required arguments (hub.mode, hub.topic,
     *                                hub.challenge and hub.lease_seconds)
     *
     * @return string|boolean Return the value of the echoed challenge, or
     *                        false if a problem occured. The value must be
     *                        compared to the submitted one.
     */
    public function getChallengeFromCallback($intent_callback)
    {
        $curl_session = curl_init();
        curl_setopt($curl_session, CURLOPT_URL, $intent_callback);
        curl_setopt($curl_session, CURLOPT_HEADER, false);
        curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_session, CURLOPT_TIMEOUT, 5);
        $result = curl_exec($curl_session);
        $http_code = curl_getinfo($curl_session, CURLINFO_RESPONSE_CODE);
        if ($result === false) {
            $error = curl_error($curl_session);
            \Minz\Log::error("Curl error while validating a challenge: {$error}.");
        }
        curl_close($curl_session);

        if ($http_code >= 200 && $http_code < 300) {
            return $result;
        } else {
            return false;
        }
    }
}
