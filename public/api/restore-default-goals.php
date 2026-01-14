<?php
/**
 * API: Restaurar Metas Padrão
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../src/Config/GoalsConfig.php';

try {
    $goalsConfig = new GoalsConfig();
    $success = $goalsConfig->restoreDefaults();

    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Default goals restored successfully' : 'Failed to restore default goals'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
