<?php

$app_path = realpath(__DIR__ . '/..');

include $app_path . '/autoload.php';

$environment = getenv('APP_ENVIRONMENT');
if (!$environment) {
    $environment = 'development';
}
\Minz\Configuration::load($environment, $app_path);
\Minz\Environment::initialize();

// Initialize the database. If the DB exists, the request will fail since
// tables already exist. We don't care.
// I'll design a better system later but for now it's good enough.
$configuration_path = \Minz\Configuration::$configuration_path;
$schema = file_get_contents($configuration_path . '/schema.sql');
$database = \Minz\Database::get();
$database->exec($schema);

// Get the http information related to the current request
$http_method = $_SERVER['REQUEST_METHOD'];
$http_uri = $_SERVER['REQUEST_URI'];
$http_parameters = array_merge($_GET, $_POST);

// Initialize the needed objects, representing the app and the user request.
$request = new \Minz\Request($http_method, $http_uri, $http_parameters);
$router = new \Minz\Router();

$router->addRoute('/', 'home#index', 'get');
$router->addRoute('/', 'subscriptions#handleRequest', 'post');

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
