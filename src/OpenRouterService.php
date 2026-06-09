<?php

declare(strict_types=1);

require_once __DIR__ . '/AIService.php';

class OpenRouterService {
    private AIService $aiService;

    public function __construct() {
        $this->aiService = new AIService();
    }

    public function analyzeImages(array $imagesBase64, array $userData): array {
        return $this->aiService->analyze($userData, $imagesBase64);
    }
}