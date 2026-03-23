<?php

require_once __DIR__ . '/src/db.php';

try {
    $stmt = $pdo->query("SELECT NOW() AS server_time");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo 'Database connected. Server time: ' . $result['server_time'];
} catch (PDOException $e) {
    echo 'Query failed: ' . $e->getMessage();
}