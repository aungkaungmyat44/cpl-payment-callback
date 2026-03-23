<?php

require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/helper.php';

$db = new Database();

try {
    $row = $db->fetch("SELECT NOW() AS server_time");
    
    write_log("Greeting my friends");

    echo "Greeting from CPL payment callback";
    echo "<br>";
    echo 'Database connected. Server time: ' . $row['server_time'];
} catch (Exception $e) {
    echo 'Query failed: ' . $e->getMessage();
}