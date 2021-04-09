<?php

namespace Webubbub;

use Minz\Response;

class Home
{
    public function index($request)
    {
        $is_public_hub = \Minz\Configuration::$application['allowed_topic_origins'] === '';
        return Response::ok('home/index.phtml', [
            'is_public_hub' => $is_public_hub,
        ]);
    }

    public function dummySubscriber($request)
    {
        $challenge = $request->param('hub_challenge', '');
        return Response::ok('home/dummySubscriber.txt', [
            'challenge' => $challenge,
        ]);
    }
}
