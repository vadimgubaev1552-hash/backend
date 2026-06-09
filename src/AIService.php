<?php

declare(strict_types=1);

class AIService {
    private string $aiUrl;

    public function __construct() {
        $this->aiUrl = 'http://ai-service:5001/analyze';
    }

    public function analyze(array $formData, array $imagesBase64): array {
        $payload = [
            'action' => 'analyze',
            'formData' => $formData,
            'images' => $imagesBase64
        ];

        $ch = curl_init($this->aiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("cURL error: {$error}");
        }

        if ($httpCode !== 200) {
            throw new Exception("AI Service error: HTTP {$httpCode}, response: {$response}");
        }

        $data = json_decode($response, true);

        if (!$data || !isset($data['success'])) {
            throw new Exception("Invalid AI Service response: {$response}");
        }

        if (!$data['success']) {
            throw new Exception($data['error'] ?? 'Unknown AI service error');
        }

        return $data['result'];
    }
}