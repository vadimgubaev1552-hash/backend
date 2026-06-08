<?php

declare(strict_types=1);

class Calculator {

    public function calculate(array $userData): array {
        $tariffs = $GLOBALS['tariffs'];

        $weight = floatval($userData['weight']) ?: 1.0;
        $purity = intval($userData['purity']);
        $type = $userData['type'];
        $condition = $userData['condition'];
        $hasStones = $userData['hasStones'];

        $validConditions = ['Как новое', 'Среднее', 'Плохое'];
        if (!in_array($condition, $validConditions, true)) {
            throw new InvalidArgumentException("Некорректное состояние изделия");
        }

        $basePrice = $tariffs['gold_prices'][$purity] ?? throw new InvalidArgumentException("Некорректная проба: {$purity}");
        $metalValue = $basePrice * $weight;

        $typeCoeff = $tariffs['type_coefficients'][$type] ?? throw new InvalidArgumentException("Некорректный тип изделия: {$type}");
        $conditionCoeff = $tariffs['condition_coefficients'][$condition];
        $stonesCoeff = $tariffs['stones_correction'][$hasStones] ?? throw new InvalidArgumentException("Некорректное значение вставок: {$hasStones}");

        $preliminaryAmount = $metalValue * $typeCoeff * $conditionCoeff * $stonesCoeff;

        if ($condition !== 'Как новое') {
            $preliminaryAmount *= (1 - $tariffs['defects_penalty']);
        }

        $loanAmount = round($preliminaryAmount);
        $buyoutAmount = round($loanAmount * BUYOUT_MULTIPLIER);

        $probability = match($condition) {
            'Как новое' => 'Высокая',
            'Среднее'   => 'Средняя',
            'Плохое'    => 'Низкая',
        };

        $defects = match($condition) {
            'Как новое' => 'Видимых дефектов не обнаружено',
            'Среднее'   => 'Незначительные потертости',
            'Плохое'    => 'Царапины, потертости, деформация',
        };

        return [
            'loanAmount' => $loanAmount,
            'buyoutAmount' => $buyoutAmount,
            'probability' => $probability,
            'defects' => $defects,
            'type' => $type,
            'hasStones' => $hasStones,
            'condition' => $condition
        ];
    }
}
?>