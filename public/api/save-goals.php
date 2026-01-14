<?php
/**
 * API: Salvar Configurações de Metas
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../src/Config/GoalsConfig.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['goals']) || !is_array($input['goals'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid goals data'
        ]);
        exit;
    }

    $goalsConfig = new GoalsConfig();
    $currentGoals = $goalsConfig->getGoals();

    // Atualizar apenas os campos modificados
    foreach ($input['goals'] as $key => $updates) {
        if (isset($currentGoals[$key])) {
            foreach ($updates as $field => $value) {
                $currentGoals[$key][$field] = $value;
            }
        }
    }

    $success = $goalsConfig->saveGoals($currentGoals);

    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Goals saved successfully' : 'Failed to save goals'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
