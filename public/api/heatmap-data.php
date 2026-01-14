<?php
/**
 * API: Dados do Heatmap de Demanda
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../src/Generators/HeatmapGenerator.php';

try {
    $dateFrom = $_GET['date_from'] ?? null;
    $dateTo = $_GET['date_to'] ?? null;
    $entityId = !empty($_GET['entity_id']) ? (int)$_GET['entity_id'] : null;

    $heatmap = new HeatmapGenerator($dateFrom, $dateTo, $entityId);

    $data = [
        'heatmap' => $heatmap->getFormattedData(),
        'stats' => $heatmap->getHeatmapStats(),
        'peak_hours' => $heatmap->getPeakHours(5),
        'quiet_hours' => $heatmap->getQuietHours(5),
        'period_analysis' => $heatmap->getAnalysisByPeriod(),
        'weekday_analysis' => $heatmap->getAnalysisByWeekday(),
        'recommendations' => $heatmap->getRecommendations()
    ];

    echo json_encode([
        'success' => true,
        'data' => $data,
        'period' => [
            'from' => $dateFrom ?? date('Y-m-d', strtotime('-30 days')),
            'to' => $dateTo ?? date('Y-m-d')
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error generating heatmap: ' . $e->getMessage()
    ]);
}
