<?php

require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/helper.php';
require_once __DIR__ . '/src/request.php';

$db = new Database();
$request = Request::fromGlobals();

try {
    $row = $db->fetch("SELECT NOW() AS server_time");
    $getParams = $request->getGetParams();
    $postParams = $request->getPostParams();
    $chargeId = $postParams['objectId'] ?? $getParams['charge_id'] ?? null;

    write_log("Get params are : " . json_encode($getParams));
    write_log("Post params are : " . json_encode($postParams));

    $inquiryBaseUrl = getenv('INQUIRY_URL') ?: 'http://localhost:8000/proc.php?action=payment_inquiry';
    $separator = strpos($inquiryBaseUrl, '?') === false ? '?' : '&';
    $redirectUrl = $inquiryBaseUrl . $separator . 'charge_id=' . urlencode($chargeId);

    write_log("Redirecting to inquiry page: $redirectUrl");

    header('Location: ' . $redirectUrl);
    exit;
} catch (Exception $e) {
    echo 'Query failed: ' . $e->getMessage();
}
