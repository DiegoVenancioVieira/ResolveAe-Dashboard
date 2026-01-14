<?php
/**
 * API: Obter Slides Visíveis
 * Retorna apenas os slides que devem aparecer na rotação
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../src/Config/SlidesConfig.php';

try {
    $slidesConfig = new SlidesConfig();
    $visibleSlides = $slidesConfig->getVisibleSlides();

    echo json_encode([
        'success' => true,
        'slides' => $visibleSlides,
        'stats' => $slidesConfig->getStats()
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
