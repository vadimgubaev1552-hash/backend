<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

define('BUYOUT_MULTIPLIER', 1.3);

$GLOBALS['tariffs'] = [
    'gold_prices' => [
        375 => 2500,
        585 => 3800,
        750 => 5200
    ],
    'type_coefficients' => [
        'Кольцо' => 1.0,
        'Серьги' => 0.95,
        'Браслет' => 1.05,
        'Кулон' => 0.9,
        'Цепь' => 1.1,
        'Колье' => 1.15
    ],
    'condition_coefficients' => [
        'Как новое' => 1.0,
        'Среднее' => 0.85,
        'Плохое' => 0.7
    ],
    'stones_correction' => [
        'Да' => 0.9,
        'Нет' => 1.0
    ],
    'defects_penalty' => 0.15
];

if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env');
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            putenv("$key=$value");
        }
    }
}

define('BITRIX24_WEBHOOK', getenv('BITRIX24_WEBHOOK') ?: '');
?>