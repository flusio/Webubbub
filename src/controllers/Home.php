<?php

namespace Webubbub\controllers;

use Minz\Request;
use Minz\Response;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Home
{
    /**
     * @response 200
     */
    public function index(Request $request): Response
    {
        return Response::ok('home/index.phtml', [
            'is_public_hub' => \Webubbub\Configuration::isPublicHub(),
        ]);
    }

    /**
     * @request_param ?string hub.challenge
     *
     * @response 200
     */
    public function dummySubscriber(Request $request): Response
    {
        $challenge = $request->param('hub_challenge', '');
        return Response::ok('home/dummySubscriber.txt', [
            'challenge' => $challenge,
        ]);
    }
}
