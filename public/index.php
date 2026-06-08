<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Calculator.php';
require_once __DIR__ . '/../src/Bitrix24Service.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Нет данных для обработки');
    }

    $action = $input['action'] ?? 'analyze';

    if ($action === 'analyze') {
        handleAnalyze($input);
    } elseif ($action === 'createDeal') {
        handleCreateDeal($input);
    } else {
        throw new Exception('Неизвестное действие: ' . $action);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function handleAnalyze(array $input): void {
    $formData = $input['formData'] ?? [];

    $required = ['type', 'purity', 'hasStones', 'condition'];
    foreach ($required as $field) {
        if (empty($formData[$field])) {
            throw new Exception("Поле {$field} обязательно для заполнения");
        }
    }

    $images = $input['images'] ?? [];
    if (count($images) === 0) {
        throw new Exception('Загрузите хотя бы одно фото');
    }

    $calculator = new Calculator();
    $result = $calculator->calculate($formData);

    echo json_encode([
        'success' => true,
        'result' => $result
    ]);
}

function handleCreateDeal(array $input): void {
    $clientData = $input['clientData'] ?? [];
    $calculationResult = $input['calculationResult'] ?? [];
    $images = $input['images'] ?? [];

    if (empty($clientData['fio']) || empty($clientData['phone'])) {
        throw new Exception('ФИО и телефон обязательны');
    }

    $bitrix = new Bitrix24Service();
    $result = $bitrix->createDeal($clientData, $calculationResult, $images);

    echo json_encode([
        'success' => true,
        'dealId' => $result['dealId'],
        'contactId' => $result['contactId'],
        'message' => 'Сделка создана в Битрикс24'
    ]);
}
?>