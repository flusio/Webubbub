<?php

namespace Webubbub\controllers\home;

use Minz\Response;

function index($request)
{
    return Response::ok('home/index.phtml');
}

function dummySubscriber($request)
{
    $challenge = $request->param('hub_challenge', '');
    return Response::ok('home/dummySubscriber.txt', [
        'challenge' => $challenge,
    ]);
}
