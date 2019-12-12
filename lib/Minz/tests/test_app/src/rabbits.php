<?php

namespace AppTest\controllers\rabbits;

use Minz\Response;

function items($request)
{
    $rabbits = [
        'Bugs',
        'Clémentine',
        'Jean-Jean',
    ];

    return Response::ok('rabbits/items.phtml', [
        'rabbits' => $rabbits,
    ]);
}

function missingViewFile($request)
{
    return Response::ok('rabbits/missing.phtml');
}

function noResponse($request)
{
    return 'It’s a string, not a Response!';
}
