<?php
/**
 * API para retornar métricas do GLPI em formato JSON
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/includes/GLPIMetrics.php';

try {
    $metrics = new GLPIMetrics();
    $data = $metrics->getAllMetrics();
    
    // Adiciona status de sucesso
    $data['success'] = true;
    $data['error'] = null;
    
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Em caso de erro, retorna mensagem apropriada
    $error_data = [
        'success' => false,
        'error' => 'Erro ao obter dados do banco',
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s'),
        'tickets_status' => [
            'total_criados' => 0,
            'novos' => 0,
            'atribuidos' => 0,
            'planejados' => 0,
            'pendentes' => 0,
            'resolvidos' => 0,
            'fechados' => 0,
            'total_abertos' => 0
        ]
    ];
    
    http_response_code(500);
    echo json_encode($error_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
