<?php

// A tiny echo server used by ClientTest/PoolTest/HttpTest: reports back
// exactly what it received (method, query string, headers, raw body,
// $_POST, $_FILES) as JSON, so assertions check what the server actually
// got, not just that curl_exec() didn't error.
//
// Started via PHP's built-in server (`php -S host:port
// tests/Support/echo-server.php`), which needs PHP 5.4+ — the tests that
// hit it run on whatever PHP is under test (including 5.3), but the
// server process itself can run on any available PHP (see
// .github/workflows/github-ci.yml, and TestCase::serverUrl()'s
// HTTP_TEST_SERVER env var override).

if (isset($_GET['redirect_to'])) {
    header('Location: ' . $_GET['redirect_to']);
    http_response_code(302);
    exit;
}

$status = isset($_GET['status']) ? (int) $_GET['status'] : 200;
http_response_code($status);

$headers = array();
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0) {
        $name = str_replace('_', '-', substr($key, 5));
        $headers[$name] = $value;
    }
}
if (isset($_SERVER['CONTENT_TYPE'])) {
    $headers['CONTENT-TYPE'] = $_SERVER['CONTENT_TYPE'];
}

$files = array();
foreach ($_FILES as $fieldName => $file) {
    $files[$fieldName] = array(
        'name' => $file['name'],
        'type' => $file['type'],
        'size' => $file['size'],
        'content' => file_get_contents($file['tmp_name']),
    );
}

$payload = array(
    'method'  => $_SERVER['REQUEST_METHOD'],
    'query'   => $_GET,
    'headers' => $headers,
    'body'    => file_get_contents('php://input'),
    'post'    => $_POST,
    'files'   => $files,
);

header('Content-Type: application/json; charset=utf-8');
header('X-Echo-Server: 1');
echo json_encode($payload);
