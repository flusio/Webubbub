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

// Get the http information and create a proper Request
$http_method = $_SERVER['REQUEST_METHOD'];
$http_uri = $_SERVER['REQUEST_URI'];
$http_parameters = array_merge($_GET, $_POST);

$request = new \Minz\Request($http_method, $http_uri, $http_parameters);

// Initialize the Application and execute the request to get a Response
$application = new \Webubbub\Application();
$response = $application->run($request);

// Generate the HTTP headers and output. All the side effects must be contained
// here.
http_response_code($response->code());
foreach ($response->headers() as $header => $value) {
    header("{$header}: {$value}");
}
echo $response->render();
