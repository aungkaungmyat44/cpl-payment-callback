<?php

$envFile = dirname(__DIR__) . '/.env';

if (!file_exists($envFile)) {
    throw new Exception('.env file not found');
}

$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line) {
    $line = trim($line);

    if ($line === '' || substr($line, 0, 1) === '#') {
        continue;
    }

    if (strpos($line, '#') === 0) {
        continue;
    }

    [$name, $value] = explode('=', $line, 2);

    $name = trim($name);
    $value = trim($value);

    $value = trim($value, "\"'");

    putenv("$name=$value");
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
}