<?php

namespace Webubbub\cli;

use Minz\Request;
use Minz\Response;
use Webubbub\models;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Subscriptions
{
    /**
     * @response 200
     */
    public function items(Request $request): Response
    {
        return Response::ok('cli/subscriptions/items.txt', [
            'subscriptions' => models\Subscription::listAll(),
        ]);
    }
}
