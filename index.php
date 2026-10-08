<?php

require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/helper.php';
require_once __DIR__ . '/src/request.php';
require_once __DIR__ . '/src/http_service.php';

$db = new Database();
$request = Request::fromGlobals();

try {
    /* # Example Incoming Structure
    {
        "objectId":"chrg_test_2250085fc062695424a65b115944a73727d69","token":"tokn_test_22500cb6173561e08338325140bac3ddebf65",
        "status":"false",
        "message":"",
        "saveCard":"false"
    }*/
    $row = $db->fetch("SELECT NOW() AS server_time");
    $getParams = $request->getGetParams();
    $postParams = $request->getPostParams();
    $chargeId = $postParams['objectId'] ?? $getParams['charge_id'] ?? null;
    $status = $postParams['status'] ?? $getParams['status'] ?? 'false';

    write_log("Get params are : " . json_encode($getParams));
    write_log("Post params are : " . json_encode($postParams));

    $date = date('Ymd', time());
    $endpoint = 'https://dev-kpaymentgateway-services.kasikornbank.com/card/v2/charge/' . "$chargeId";
    $headers = ['Content-Type: application/json'];
    $kbankApiKey = getenv('KBANK_API_KEY') ?: '';
    if ($kbankApiKey !== '') {
        $headers[] = 'x-api-key: ' . $kbankApiKey;
    }

    $httpService = new HttpService($endpoint, $headers, '', 'get');
    $chargeResponse = $httpService->sendGet();
    $referenceNumber = '';
    $decodedChargeResponse = is_array($chargeResponse) ? $chargeResponse : json_decode($chargeResponse, true);
    if (is_array($decodedChargeResponse)) {
        $referenceNumber = $decodedChargeResponse['data']['reference_order'] ?? '';
    }

    $cplInquiryUrl = getenv('CPL_INQUIRY_URL') ?: '';
    $paiInquiryUrl = getenv('PAI_INQUIRY_URL') ?: '';
    if (strpos($referenceNumber, 'PAI_ORD_') === 0) {
        $inquiryBaseUrl = $paiInquiryUrl;
    } elseif (strpos($referenceNumber, 'CPL_ORD_') === 0) {
        $inquiryBaseUrl = $cplInquiryUrl;
    } else {
        $inquiryBaseUrl = $cplInquiryUrl ?: $paiInquiryUrl;
    }

    if ($inquiryBaseUrl === '') {
        throw new Exception('Inquiry URL is not configured for reference order: ' . $referenceNumber);
    }

    $separator = strpos($inquiryBaseUrl, '?') === false ? '?' : '&';
    $redirectUrl = $inquiryBaseUrl
        . $separator
        . 'charge_id=' . urlencode($chargeId)
        . '&status=' . urlencode($status);

    write_log("Redirecting to inquiry page: $redirectUrl");

    header('Location: ' . $redirectUrl);
    exit;
} catch (Exception $e) {
    echo 'Query failed: ' . $e->getMessage();
}
