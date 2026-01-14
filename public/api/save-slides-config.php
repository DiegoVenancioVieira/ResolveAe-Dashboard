<?php
/**
 * API: Salvar Configuração de Slides
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../src/Config/SlidesConfig.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['config']) || !is_array($input['config'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid configuration data'
        ]);
        exit;
    }

    $slidesConfig = new SlidesConfig();
    $success = $slidesConfig->saveConfig($input['config']);

    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Configuration saved successfully' : 'Failed to save configuration',
        'stats' => $slidesConfig->getStats()
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
