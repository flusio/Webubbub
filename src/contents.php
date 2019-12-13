<?php

namespace Webubbub\controllers\contents;

use Minz\Response;
use Webubbub\models;

/**
 * @param \Minz\Request $request
 *
 * @return \Minz\Response
 */
function items($request)
{
    $dao = new models\dao\Content();
    $contents = $dao->listAll();
    return Response::ok('contents/items.txt', [
        'contents' => $contents,
    ]);
}
