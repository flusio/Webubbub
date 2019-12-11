<?php

namespace Webubbub\controllers\home;

use Minz\Response;

function index($request)
{
    return Response::ok('home#index.phtml');
}
