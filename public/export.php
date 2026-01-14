<?php
/**
 * Endpoint de Exportação de Relatórios
 * API REST para exportar relatórios em PDF, Excel e CSV
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Headers para evitar cache
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

try {
    // Includes
    require_once __DIR__ . '/../src/Generators/ReportGenerator.php';
    require_once __DIR__ . '/../src/Exporters/CSVExporter.php';
    require_once __DIR__ . '/../src/Exporters/PDFExporter.php';
    require_once __DIR__ . '/../src/Exporters/ExcelExporter.php';

    // Validação de parâmetros
    $format = isset($_GET['format']) ? strtolower(trim($_GET['format'])) : '';
    $dateFrom = isset($_GET['date_from']) ? trim($_GET['date_from']) : null;
    $dateTo = isset($_GET['date_to']) ? trim($_GET['date_to']) : null;
    $sections = isset($_GET['sections']) ? $_GET['sections'] : [];
    $entityId = isset($_GET['entity_id']) ? intval($_GET['entity_id']) : null;
    $technicianId = isset($_GET['technician_id']) ? intval($_GET['technician_id']) : null;

    // Validar formato
    $validFormats = ['pdf', 'excel', 'csv'];
    if (!in_array($format, $validFormats)) {
        throw new Exception('Formato inválido. Use: pdf, excel ou csv');
    }

    // Validar datas
    if ($dateFrom && !validateDate($dateFrom)) {
        throw new Exception('Data inicial inválida. Use o formato YYYY-MM-DD');
    }

    if ($dateTo && !validateDate($dateTo)) {
        throw new Exception('Data final inválida. Use o formato YYYY-MM-DD');
    }

    // Validar período máximo (12 meses)
    if ($dateFrom && $dateTo) {
        $from = new DateTime($dateFrom);
        $to = new DateTime($dateTo);
        $diff = $from->diff($to);
        $months = $diff->y * 12 + $diff->m;

        if ($months > 12) {
            throw new Exception('Período máximo permitido: 12 meses');
        }

        if ($from > $to) {
            throw new Exception('Data inicial não pode ser maior que a data final');
        }
    }

    // Converter sections de string para array se necessário
    if (!is_array($sections) && !empty($sections)) {
        $sections = explode(',', $sections);
    }

    // Rate limiting simples (máximo 10 requisições por minuto por IP)
    $rateLimitFile = sys_get_temp_dir() . '/glpi_export_rate_' . md5($_SERVER['REMOTE_ADDR']);
    if (file_exists($rateLimitFile)) {
        $requests = json_decode(file_get_contents($rateLimitFile), true);
        $requests = array_filter($requests, function($time) {
            return (time() - $time) < 60;
        });

        if (count($requests) >= 10) {
            throw new Exception('Limite de requisições excedido. Tente novamente em alguns segundos.');
        }

        $requests[] = time();
        file_put_contents($rateLimitFile, json_encode($requests));
    } else {
        file_put_contents($rateLimitFile, json_encode([time()]));
    }

    // Gerar relatório
    $generator = new ReportGenerator($dateFrom, $dateTo, $sections, $entityId, $technicianId);
    $report = $generator->generateReport();

    // Exportar no formato solicitado
    switch ($format) {
        case 'pdf':
            exportPDF($report);
            break;

        case 'excel':
            exportExcel($report);
            break;

        case 'csv':
            exportCSV($report);
            break;
    }

} catch (Exception $e) {
    // Retorna erro em JSON
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}

/**
 * Exporta relatório em PDF
 */
function exportPDF($report) {
    try {
        $exporter = new PDFExporter($report);
        $pdfContent = $exporter->export();

        // Gerar nome do arquivo
        $filename = generateFilename('pdf', $report);

        // Headers para download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdfContent));

        echo $pdfContent;
        exit;

    } catch (Exception $e) {
        throw new Exception('Erro ao gerar PDF: ' . $e->getMessage());
    }
}

/**
 * Exporta relatório em Excel
 */
function exportExcel($report) {
    try {
        $exporter = new ExcelExporter($report);
        $excelFile = $exporter->export();

        // Gerar nome do arquivo
        $filename = generateFilename('xlsx', $report);

        // Headers para download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($excelFile));

        readfile($excelFile);

        // Cleanup
        $exporter->cleanup();
        exit;

    } catch (Exception $e) {
        throw new Exception('Erro ao gerar Excel: ' . $e->getMessage());
    }
}

/**
 * Exporta relatório em CSV
 */
function exportCSV($report) {
    try {
        $exporter = new CSVExporter($report);
        $zipFile = $exporter->export();

        // Gerar nome do arquivo
        $filename = generateFilename('zip', $report);

        // Headers para download
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($zipFile));

        readfile($zipFile);

        // Cleanup
        $exporter->cleanup();
        exit;

    } catch (Exception $e) {
        throw new Exception('Erro ao gerar CSV: ' . $e->getMessage());
    }
}

/**
 * Gera nome do arquivo para download
 */
function generateFilename($extension, $report) {
    $timestamp = date('Y-m-d_His');
    $periodo = '';

    if ($report['metadata']['periodo_inicio'] !== 'Início' || $report['metadata']['periodo_fim'] !== 'Atual') {
        $from = str_replace('-', '', $report['metadata']['periodo_inicio']);
        $to = str_replace('-', '', $report['metadata']['periodo_fim']);
        $periodo = '_' . $from . '_' . $to;
    }

    return 'relatorio_glpi' . $periodo . '_' . $timestamp . '.' . $extension;
}

/**
 * Valida formato de data
 */
function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}
