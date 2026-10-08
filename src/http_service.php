<?php

final class HttpService
{
    public $endPoint;
    public $headers;
    public $fields;
    public $method;

    public function __construct($endPoint, $headers, $fields, $method)
    {
        $this->endPoint = $endPoint;
        $this->headers = $headers;
        $this->fields = $fields;
        $this->method = $method;
    }

    public function send()
    {
        $ch = curl_init($this->endPoint);
        $isPostMethod = strtolower($this->method) === 'post';
        curl_setopt_array($ch, [
            CURLOPT_POST => $isPostMethod,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_POSTFIELDS => $this->fields,
            CURLOPT_TIMEOUT => 180,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        return $this->executeCurl($ch);
    }

    public function sendXml(string $xml): array
    {
        $ch = curl_init($this->endPoint);
        $isPostMethod = strtolower($this->method) === 'post';
        curl_setopt_array($ch, [
            CURLOPT_POST => $isPostMethod,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $xml,
            CURLOPT_HTTPHEADER => [
                'Content-Type: text/xml; charset=utf-8',
                'Content-Length: ' . strlen($xml),
                'SOAPAction: "ServiceCPL_JSON"',
            ],
            CURLOPT_TIMEOUT => 180,
            CURLOPT_CONNECTTIMEOUT => 20,
        ]);

        $response = curl_exec($ch);
        $curlErrNo = curl_errno($ch);
        $curlErr = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlErrNo !== 0) {
            throw new Exception('SOAP request failed: ' . $curlErr);
        }

        $rawResponse = (string)$response;
        write_log('SOAP XML HTTP status: ' . $httpCode . ', response preview: ' . substr($rawResponse, 0, 1000), 'INFO');

        if (trim($rawResponse) === '') {
            throw new Exception('SOAP response is empty');
        }

        $previousLibxml = libxml_use_internal_errors(true);
        $xmlResponse = simplexml_load_string($rawResponse);
        $xmlErrors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxml);

        if ($xmlResponse === false) {
            $messages = array_map(static function ($error) {
                return trim($error->message);
            }, $xmlErrors);
            throw new Exception('SOAP response XML is invalid: ' . implode('; ', array_filter($messages)));
        }

        $servicePolicyUrl = defined('SERVICE_POLICY_URL') ? SERVICE_POLICY_URL : '';
        $xmlResponse->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xmlResponse->registerXPathNamespace('ns1', $servicePolicyUrl);

        $nodes = $xmlResponse->xpath('//ns1:ServiceCPL_JSONResponse/ServiceCPL_JSONResult');
        if (empty($nodes)) {
            throw new Exception('SOAP response missing ServiceCPL_JSONResult node');
        }

        $result = $nodes[0];

        return [
            'result' => (string)($result->Result ?? ''),
            'PolicyNo' => (string)($result->PolicyNo ?? ''),
            'Barcode' => (string)($result->Barcode ?? ''),
            'PolicyURL' => (string)($result->PolicyURL ?? ''),
            'errorCode' => (string)($result->errorCode ?? ''),
            'errorMessage' => (string)($result->errorMessage ?? ''),
            'premium' => (string)($result->premium ?? ''),
            'vat' => (string)($result->vat ?? ''),
            'duty' => (string)($result->duty ?? ''),
            'total' => (string)($result->total ?? ''),
            'p_code' => (string)($result->p_code ?? ''),
        ];
    }

    public function sendGet(array $params = []): array
    {
        $url = $this->endPoint;
        if (!empty($params)) {
            $querySeparator = strpos($url, '?') === false ? '?' : '&';
            $url .= $querySeparator . http_build_query($params);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_TIMEOUT => 180,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        return $this->executeCurl($ch);
    }

    private function executeCurl($ch): array
    {
        $rawResponse = curl_exec($ch);
        $curlErrNo = curl_errno($ch);
        $curlErr = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlErrNo !== 0) {
            throw new Exception('Fail to proceed the request: ' . $curlErr);
        }

        $decoded = json_decode((string)$rawResponse, true);
        if (!is_array($decoded)) {
            return [];
        }

        $decoded['http_code'] = $httpCode;
        return $decoded;
    }
}
