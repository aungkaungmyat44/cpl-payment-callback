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
    
    write_log("Get params are : " . json_encode($getParams));
    write_log("Post params are : " . json_encode($postParams));

    echo "Greeting from CPL payment callback";
    echo "<br>";
    echo 'Database connected. Server time: ' . $row['server_time'];
} catch (Exception $e) {
    echo 'Query failed: ' . $e->getMessage();
}