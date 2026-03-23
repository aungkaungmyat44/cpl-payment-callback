<?php

function write_log($message, $level = "INFO")
{
    $logFile = dirname(__DIR__) . "/logs/app.log";
	
    // Create logs folder if not exists
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0777, true);
    }

    $date = date("Y-m-d H:i:s");
    $logMessage = "[$date] [$level] $message" . PHP_EOL;

    file_put_contents($logFile, $logMessage, FILE_APPEND);
}