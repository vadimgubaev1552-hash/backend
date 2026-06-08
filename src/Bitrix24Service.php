<?php

declare(strict_types=1);

class Bitrix24Service {
    private string $webhook;

    public function __construct() {
        $this->webhook = BITRIX24_WEBHOOK;
    }

    public function createDeal(array $clientData, array $calculationResult, array $imagesBase64): array {
        $contactId = $this->createContact($clientData);
        $dealId = $this->createDealEntity($contactId, $calculationResult);

        if (!empty($imagesBase64) && $dealId) {
            $this->uploadImages($dealId, $imagesBase64);
        }

        return [
            'contactId' => $contactId,
            'dealId' => $dealId
        ];
    }

    private function createContact(array $clientData): ?int {
        $fields = [
            'NAME' => $clientData['fio'],
            'PHONE' => [
                [
                    'VALUE' => $clientData['phone'],
                    'VALUE_TYPE' => 'WORK'
                ]
            ]
        ];

        $result = $this->callApi('crm.contact.add', ['fields' => $fields]);
        return $result['result'] ?? null;
    }

    private function createDealEntity(?int $contactId, array $calculationResult): ?int {
        $dealName = "Заявка на оценку - " . ($calculationResult['type'] ?? 'Изделие');

        $fields = [
            'TITLE' => $dealName,
            'CONTACT_ID' => $contactId,
            'COMMENTS' => $this->formatComments($calculationResult),
            'UF_CRM_1780950128' => $calculationResult['type'] ?? '',
            'UF_CRM_1780950141' => $calculationResult['hasStones'] ?? '',
            'UF_CRM_1780950186' => $calculationResult['defects'] ?? '',
            'UF_CRM_1780950193' => $calculationResult['condition'] ?? '',
            'UF_CRM_1780950207' => $calculationResult['probability'] ?? '',
            'UF_CRM_1780950225' => $calculationResult['loanAmount'] ?? 0,
            'UF_CRM_1780950238' => $calculationResult['buyoutAmount'] ?? 0,
        ];

        $result = $this->callApi('crm.deal.add', ['fields' => $fields]);
        return $result['result'] ?? null;
    }

    private function uploadImages(int $dealId, array $imagesBase64): void {
        foreach ($imagesBase64 as $index => $imageBase64) {
            $tempFile = tempnam(sys_get_temp_dir(), 'bitrix_');
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $imageBase64));
            file_put_contents($tempFile, $imageData);

            $this->callApi('crm.deal.file.add', [
                'id' => $dealId,
                'file' => curl_file_create($tempFile, 'image/jpeg', "photo_" . ($index + 1) . ".jpg")
            ]);

            unlink($tempFile);
        }
    }

    private function formatComments(array $calculationResult): string {
        return "Результаты анализа:\n" .
            "Тип: {$calculationResult['type']}\n" .
            "Вставки: {$calculationResult['hasStones']}\n" .
            "Состояние: {$calculationResult['condition']}\n" .
            "Дефекты: {$calculationResult['defects']}\n" .
            "Вероятность: {$calculationResult['probability']}\n" .
            "Сумма займа: {$calculationResult['loanAmount']} руб.\n" .
            "Сумма выкупа: {$calculationResult['buyoutAmount']} руб.\n\n" .
            "⚠️ Расчет предварительный. Требуется очный осмотр.";
    }

    private function callApi(string $method, array $params): array {
        $url = $this->webhook . $method;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);

        // 👇 ВРЕМЕННО: выводим ответ Битрикс24
        file_put_contents(__DIR__ . '/../logs/bitrix.log', date('Y-m-d H:i:s') . ' - ' . $response . PHP_EOL, FILE_APPEND);

        return json_decode($response, true) ?? [];
    }
}
?>