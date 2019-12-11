<?php

$app_path = realpath(__DIR__ . '/..');

include $app_path . '/src/autoload.php';

$environment = getenv('APP_ENVIRONMENT');
if (!$environment) {
    $environment = 'development';
}
\Minz\Configuration::load($environment, $app_path);
\Minz\Environment::initialize();

// Get the http information related to the current request
$http_method = $_SERVER['REQUEST_METHOD'];
$http_uri = $_SERVER['REQUEST_URI'];
$http_parameters = array_merge($_GET, $_POST);

// Initialize the needed objects, representing the app and the user request.
$request = new \Minz\Request($http_method, $http_uri, $http_parameters);
$router = new \Minz\Router();

// Execute the request against the router and get a response from the executed
// action.
$engine = new \Minz\Engine($router);
$response = $engine->run($request);

// Generate the HTTP headers and output. All the side effects must be contained
// here.
http_response_code($response->code());
foreach ($response->headers() as $header => $value) {
    header("{$header}: {$value}");
}
echo $response->render();
