<?php

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/FoundItemModel.php';
require_once __DIR__ . '/models/LostReportModel.php';
require_once __DIR__ . '/models/MatchModel.php';

// Disable response helper exit behavior for testing
// The framework usually exits, so we just catch the output

function callApi($method, $uri, $postData, $authUserId, $role) {
    ob_start();
    
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI'] = $uri;
    
    if ($method === 'POST' || $method === 'PUT') {
        // mock input
        $_POST = $postData;
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        // We need to bypass php://input, but ValidationHelper uses php://input for non-multipart
        // Let's monkey patch ValidationHelper or just send JSON to a temp file and override php://input
        // Alternatively, use cURL over HTTP. Since Laragon is running? Wait. We don't know the port.
    }
}
