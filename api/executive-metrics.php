<?php
/**
 * API Endpoint for Executive Metrics
 * Returns JSON data for the Executive Dashboard
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/ExecutiveMetrics.php';

try {
    // Get parameters
    $dateFrom = $_GET['date_from'] ?? null;
    $dateTo = $_GET['date_to'] ?? null;
    $entityId = !empty($_GET['entity_id']) ? (int)$_GET['entity_id'] : null;

    // Validate required parameters
    if (!$dateFrom || !$dateTo) {
        echo json_encode([
            'success' => false,
            'message' => 'date_from and date_to are required'
        ]);
        exit;
    }

    // Validate date format
    $dateFromObj = DateTime::createFromFormat('Y-m-d', $dateFrom);
    $dateToObj = DateTime::createFromFormat('Y-m-d', $dateTo);

    if (!$dateFromObj || !$dateToObj) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid date format. Use Y-m-d format.'
        ]);
        exit;
    }

    // Initialize ExecutiveMetrics
    $metrics = new ExecutiveMetrics($dateFrom, $dateTo, $entityId);

    // Get all metrics
    $data = [];
    
    try {
        $data['kpis'] = $metrics->getKPIs();
    } catch (Exception $e) {
        throw new Exception('Error in getKPIs(): ' . $e->getMessage());
    }
    
    try {
        $data['trends'] = $metrics->getTrends();
    } catch (Exception $e) {
        throw new Exception('Error in getTrends(): ' . $e->getMessage());
    }
    
    try {
        $data['top_categories'] = $metrics->getTopProblematicCategories();
    } catch (Exception $e) {
        throw new Exception('Error in getTopProblematicCategories(): ' . $e->getMessage());
    }
    
    try {
        $data['workload'] = $metrics->getWorkloadDistribution();
    } catch (Exception $e) {
        throw new Exception('Error in getWorkloadDistribution(): ' . $e->getMessage());
    }
    
    try {
        $data['monthly_comparison'] = $metrics->getMonthlyComparison();
    } catch (Exception $e) {
        throw new Exception('Error in getMonthlyComparison(): ' . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'period' => [
            'from' => $dateFrom,
            'to' => $dateTo
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading executive metrics: ' . $e->getMessage()
    ]);
}
