<?php
/**
 * API: Restaurar Configuração Padrão de Slides
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../src/Config/SlidesConfig.php';

try {
    $slidesConfig = new SlidesConfig();
    $success = $slidesConfig->restoreDefaults();

    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Default configuration restored successfully' : 'Failed to restore default configuration'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
