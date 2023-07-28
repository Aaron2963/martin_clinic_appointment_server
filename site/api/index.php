<?php

// get resource and args from uri
$uri = $_SERVER['REQUEST_URI'];
$uri = explode('?', $uri)[0];
$uri = explode('#', $uri)[0];
$uri = trim($uri, '/');
$uri = explode('/', $uri);
$resource = $uri[0];
$URI_ARGUMENTS = [];
for ($i = 0; $i < count($uri); $i++) {
    if ($uri[$i] === 'api') {
        $resource = $uri[$i + 1];
        $URI_ARGUMENTS = array_slice($uri, $i + 2);
        break;
    }
}

// response 404 if resource not found
if (empty($resource) || !file_exists(__DIR__ . "/$resource.php")) {
    http_response_code(404);
    exit;
}

require_once __DIR__ . "/$resource.php";
exit;
