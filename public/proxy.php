<?php
// Set CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get the target path
$path = $_SERVER['PATH_INFO'] ?? '';
$targetUrl = 'https://net.rozitech.co.id/api' . $path;

// Append query parameters
if (!empty($_SERVER['QUERY_STRING'])) {
    $targetUrl .= '?' . $_SERVER['QUERY_STRING'];
}

// Forward headers
$headers = [];
foreach (getallheaders() as $name => $value) {
    $lowerName = strtolower($name);
    if ($lowerName !== 'host' && $lowerName !== 'content-length' && $lowerName !== 'connection' && $lowerName !== 'accept-encoding') {
        $headers[] = "$name: $value";
    }
}

// Read body
$body = file_get_contents('php://input');

// Create stream context options
$options = array(
    'http' => array(
        'method'  => $_SERVER['REQUEST_METHOD'],
        'header'  => implode("\r\n", $headers),
        'content' => $body,
        'ignore_errors' => true // Retrieve response body even on 4xx/5xx HTTP errors
    ),
    'ssl' => array(
        'verify_peer' => false,
        'verify_peer_name' => false,
    )
);

$context  = stream_context_create($options);
$response = @file_get_contents($targetUrl, false, $context);

// Extract HTTP status code from response headers
$httpStatus = 200;
if (isset($http_response_header)) {
    foreach ($http_response_header as $header) {
        if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/i', $header, $matches)) {
            $httpStatus = intval($matches[1]);
            break;
        }
    }
}

// Forward content type header from target response
if (isset($http_response_header)) {
    foreach ($http_response_header as $header) {
        if (stripos($header, 'Content-Type:') === 0) {
            header($header);
        }
    }
}

http_response_code($httpStatus);
echo $response;
