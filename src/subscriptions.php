<?php

namespace Webubbub\controllers\subscriptions;

use Minz\Response;
use Webubbub\models;

/**
 * @param \Minz\Request $request
 *
 * @return \Minz\Response
 */
function items($request)
{
    $dao = new models\dao\Subscription();
    $subscriptions = $dao->listAll();
    return Response::ok('subscriptions/items.txt', [
        'subscriptions' => $subscriptions,
    ]);
}
