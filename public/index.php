<?php

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */

$app_path = realpath(__DIR__ . '/..');

assert($app_path !== false);

include $app_path . '/vendor/autoload.php';

\Minz\Configuration::load('dotenv', $app_path);

try {
    $application = new \Webubbub\Application();

    $request = \Minz\Request::initFromGlobals();

    $response = $application->run($request);
} catch (\Minz\Errors\RequestError $e) {
    $response = \Minz\Response::notFound('not_found.phtml', [
        'error' => $e,
    ]);
} catch (\Exception $e) {
    $response = \Minz\Response::internalServerError('internal_server_error.phtml', [
        'error' => $e,
    ]);
}

/** @var string */
$request_method = $_SERVER['REQUEST_METHOD'];
$is_head = strtoupper($request_method) === 'HEAD';

\Minz\Response::sendByHttp($response, echo_output: !$is_head);
