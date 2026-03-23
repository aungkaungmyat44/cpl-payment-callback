<?php

require_once __DIR__ . '/src/db.php';

$db = new Database();

try {
    $row = $db->fetch("SELECT NOW() AS server_time");
    echo 'Database connected. Server time: ' . $row['server_time'];
} catch (Exception $e) {
    echo 'Query failed: ' . $e->getMessage();
}