<?php
/**
 * API: Adicionar Nova Meta
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/GoalsConfig.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['key']) || !isset($input['label']) || !isset($input['target'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields: key, label, target'
        ]);
        exit;
    }

    $goalsConfig = new GoalsConfig();

    $goalData = [
        'label' => $input['label'],
        'target' => floatval($input['target']),
        'unit' => $input['unit'] ?? '',
        'type' => $input['type'] ?? 'number',
        'description' => $input['description'] ?? '',
        'warning_threshold' => floatval($input['warning_threshold'] ?? $input['target'] * 0.8),
        'critical_threshold' => floatval($input['critical_threshold'] ?? $input['target'] * 0.6)
    ];

    $success = $goalsConfig->addGoal($input['key'], $goalData);

    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Goal added successfully' : 'Failed to add goal'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
