<?php

namespace AppTest\controllers\rabbits;

use Minz\Response;

function items($request)
{
    return Response::ok('rabbits#items.phtml');
}

function missingViewFile($request)
{
    return Response::ok('rabbits#missing.phtml');
}
