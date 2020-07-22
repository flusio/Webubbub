<?php

namespace Webubbub;

use Minz\Response;

class Home
{
    public function index($request)
    {
        return Response::ok('home/index.phtml');
    }

    public function dummySubscriber($request)
    {
        $challenge = $request->param('hub_challenge', '');
        return Response::ok('home/dummySubscriber.txt', [
            'challenge' => $challenge,
        ]);
    }
}
