<?php
/**
 * API Endpoint - Roteador de Métricas
 * Retorna dados em JSON para o dashboard
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Access-Control-Allow-Origin: *');

try {
    // Determinar qual endpoint chamar baseado no parâmetro
    $endpoint = $_GET['endpoint'] ?? 'standard';
    
    switch ($endpoint) {
        case 'executive':
            // Usar métricas executivas com parâmetros de data
            require_once __DIR__ . '/../src/Metrics/ExecutiveMetrics.php';
            
            $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
            $dateTo = $_GET['date_to'] ?? date('Y-m-d');
            $entityId = !empty($_GET['entity_id']) ? (int)$_GET['entity_id'] : null;
            
            // Validar datas
            $dateFromObj = DateTime::createFromFormat('Y-m-d', $dateFrom);
            $dateToObj = DateTime::createFromFormat('Y-m-d', $dateTo);
            
            if (!$dateFromObj || !$dateToObj) {
                throw new Exception('Invalid date format. Use Y-m-d format.');
            }
            
            $metrics = new ExecutiveMetrics($dateFrom, $dateTo, $entityId);
            
            $data = [
                'success' => true,
                'kpis' => $metrics->getKPIs(),
                'trends' => $metrics->getTrends(),
                'topCategories' => $metrics->getTopProblematicCategories(),
                'workload' => $metrics->getWorkloadDistribution(),
                'monthlyComparison' => $metrics->getMonthlyComparison()
            ];
            break;
            
        case 'standard':
        default:
            // Métricas padrão (sem parâmetros de data)
            require_once __DIR__ . '/../src/Metrics/GLPIMetrics.php';
            
            $metrics = new GLPIMetrics();
            $data = $metrics->getAllMetrics();
            $data['success'] = true;
            break;
    }
    
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
