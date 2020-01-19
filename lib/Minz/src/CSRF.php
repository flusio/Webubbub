<?php

namespace Minz;

/**
 * The CSRF class is a helper to create secure forms.
 *
 * @see https://en.wikipedia.org/wiki/Cross-site_request_forgery
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class CSRF
{
    /**
     * Store a CSRF hexadecimal token in session and return it.
     *
     * No tokens are generated if $_SESSION['CSRF'] already contains a token.
     *
     * @return string
     */
    public function generateToken()
    {
        if (!isset($_SESSION['CSRF']) || !$_SESSION['CSRF']) {
            $_SESSION['CSRF'] = \bin2hex(\random_bytes(32));
        }
        return $_SESSION['CSRF'];
    }

    /**
     * Validate a token against the session-stored one.
     *
     * The token cannot be empty or the method will always return false.
     *
     * @param string $token The token to check
     *
     * @return boolean True if the token matches with $_SESSION['CSRF'], false otherwise
     */
    public function validateToken($token)
    {
        if (isset($_SESSION['CSRF'])) {
            $expected_token = $_SESSION['CSRF'];
        } else {
            $expected_token = '';
        }

        if (!$token) {
            return false;
        }

        if (\hash_equals($expected_token, $token)) {
            return true;
        } else {
            Log::notice(
                "[CSRF#validateToken] Failed: got {$token}, expected {$expected_token}"
            );
            return false;
        }
    }
}
