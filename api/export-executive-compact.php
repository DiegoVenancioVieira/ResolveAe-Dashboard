<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Metrics/ExecutiveMetrics.php';
require_once __DIR__ . '/../../../vendor/tecnickcom/tcpdf/tcpdf.php';

// Parâmetros
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-t');
$entityId = isset($_GET['entity_id']) && $_GET['entity_id'] !== 'all' ? (int)$_GET['entity_id'] : null;

// Obter nome da entidade
$entityName = 'Geral';
if ($entityId) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT name FROM glpi_entities WHERE id = :id");
        $stmt->execute([':id' => $entityId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $entityName = $result['name'];
        }
    } catch (PDOException $e) {
        // Em caso de erro, mantém o nome padrão
    }
}

// Obter métricas
$metrics = new ExecutiveMetrics($dateFrom, $dateTo, $entityId);
$kpis = $metrics->getKPIs();
$trends = $metrics->getTrends();
$topCategories = $metrics->getTopProblematicCategories();
$workloadTechnicians = $metrics->getWorkloadDistribution();
$workloadSectors = $metrics->getWorkloadBySector();
$monthlyComparison = $metrics->getMonthlyComparison();

// Logo
$logoPath = realpath(__DIR__ . '/../public/pics/resolveae.png');
$logoData = base64_encode(file_get_contents($logoPath));
$logoSrc = '@' . $logoData;


// Gerar o HTML para o PDF
$html = generateCompactPdfHtml($kpis, $trends, $topCategories, $workloadTechnicians, $workloadSectors, $monthlyComparison, $dateFrom, $dateTo, $entityName, $logoSrc);

// Gerar o PDF
try {
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('ResolveAE');
    $pdf->SetTitle('Dashboard Executivo Compacto');
    $pdf->SetSubject('Relatório de Métricas GLPI');
    
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(8, 8, 8);
    $pdf->SetAutoPageBreak(TRUE, 8);
    
    $pdf->AddPage();
    
    $pdf->writeHTML($html, true, false, true, false, '');
    
    $pdf->Output('dashboard_executivo_compacto.pdf', 'I');

} catch (Exception $e) {
    header('Content-Type: text/plain');
    echo 'Erro ao gerar PDF: ' . $e->getMessage();
}

function generateCompactPdfHtml($kpis, $trends, $topCategories, $workloadTechnicians, $workloadSectors, $monthlyComparison, $dateFrom, $dateTo, $entityName, $logoSrc) {
    $period = date('d/m/Y', strtotime($dateFrom)) . ' a ' . date('d/m/Y', strtotime($dateTo));

    // Paleta de Cores e Estilos
    $colors = [
        'bg' => '#f4f7fa',
        'card_bg' => '#ffffff',
        'text' => '#34495e',
        'light_text' => '#7f8c8d',
        'primary' => '#3498db',
        'border' => '#e8ecef',
        'header_bg' => '#2c3e50',
        'sector_color' => '#27ae60',
        'tech_color' => '#2980b9'
    ];

    $css = <<<EOD
<style>
    body {
        font-family: helvetica, sans-serif;
        font-size: 9pt;
        color: {$colors['text']};
        background-color: {$colors['bg']};
    }
    .noborder, .noborder tr, .noborder td { border: none; }
    .card {
        background-color: {$colors['card_bg']};
        padding: 12px;
    }
    .section-title {
        font-size: 11pt;
        font-weight: bold;
        color: {$colors['primary']};
        margin-bottom: 8px;
    }
    .kpi-grid { width: 100%; border-spacing: 8px; border-collapse: separate; }
    .kpi-box {
        background-color: {$colors['card_bg']};
        text-align: center;
        padding: 10px;
        border-bottom: 3px solid {$colors['primary']};
    }
    .kpi-value {
        font-size: 16pt;
        font-weight: bold;
        color: {$colors['text']};
    }
    .kpi-label {
        font-size: 7.5pt;
        color: {$colors['light_text']};
        margin-top: 2px;
    }
    .data-table { width: 100%; border-collapse: collapse; font-size: 8pt; }
    .data-table th, .data-table td {
        border-bottom: 1px solid {$colors['border']};
        padding: 6px 4px;
        text-align: left;
    }
    .data-table th {
        font-weight: bold;
        background-color: #f8f9fa;
        color: {$colors['text']};
    }
    .data-table tr:nth-child(even) { background-color: #fdfdfd; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .font-bold { font-weight: bold; }
    .no-data { text-align: center; color: {$colors['light_text']}; padding: 20px; }
</style>
EOD;

    // --- ESTRUTURA HTML ---

    // 1. Cabeçalho
    $header = '
    <table class="noborder" style="width: 100%; margin-bottom: 15px;">
        <tr>
            <td style="width: 20%;">
                <img src="' . $logoSrc . '" height="32">
            </td>
            <td style="width: 60%; text-align: center;">
                <span style="font-size: 16pt; font-weight: bold; color: ' . $colors['header_bg'] . ';">Dashboard Executivo</span><br>
                <span style="font-size: 8pt; color: ' . $colors['light_text'] . ';">Período: ' . $period . ' | Entidade: ' . htmlspecialchars($entityName) . '</span>
            </td>
            <td style="width: 20%; text-align: right; font-size: 7pt; color: ' . $colors['light_text'] . ';">
                Gerado em:<br>' . date('d/m/Y H:i') . '
            </td>
        </tr>
    </table>';

    // 2. KPIs Principais (SLA removido)
    $kpiHtml = '
    <table class="kpi-grid">
        <tr>
            <td class="kpi-box">
                <div class="kpi-value">' . $kpis['total_tickets'] . '</div>
                <div class="kpi-label">Tickets Abertos</div>
            </td>
            <td class="kpi-box" style="border-bottom-color: #e74c3c;">
                <div class="kpi-value">' . $kpis['avg_resolution_time'] . '</div>
                <div class="kpi-label">Tempo Médio de Resolução</div>
            </td>
            <td class="kpi-box" style="border-bottom-color: #f39c12;">
                <div class="kpi-value">' . $kpis['satisfaction_score'] . '/5</div>
                <div class="kpi-label">Satisfação (' . $kpis['satisfaction_responses'] . ' av.)</div>
            </td>
            <td class="kpi-box" style="border-bottom-color: #2ecc71;">
                <div class="kpi-value">' . $kpis['productivity'] . '</div>
                <div class="kpi-label">Tickets / Técnico / Dia</div>
            </td>
        </tr>
    </table>';

    // 3. Grid Principal 2x2
    $mainGrid = '
    <table class="noborder" style="width: 100%; margin-top: 15px; border-spacing: 10px; border-collapse: separate;">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <div class="card">' . generateStyledTable(
                    'Tendência Mensal',
                    ['Mês', 'Tickets'],
                    $monthlyComparison,
                    ['month', 'tickets'],
                    ['text-left', 'text-center font-bold']
                ) . '</div>
            </td>
            <td style="width: 50%; vertical-align: top;">
                <div class="card">' . generateStyledTable(
                    'Top 5 Categorias Problemáticas',
                    ['Categoria', 'Tickets', 'TMR'],
                    $topCategories,
                    ['category', 'tickets', 'avg_resolution_time'],
                    ['text-left', 'text-center font-bold', 'text-center']
                ) . '</div>
            </td>
        </tr>
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <div class="card">' . generateWorkloadBlock(
                    'Top 5 Setores - Workload',
                    array_slice($workloadSectors, 0, 5),
                    'sector',
                    $colors['sector_color']
                ) . '</div>
            </td>
            <td style="width: 50%; vertical-align: top;">
                <div class="card">' . generateWorkloadBlock(
                    'Top 5 Técnicos - Workload',
                    array_slice($workloadTechnicians, 0, 5),
                    'technician',
                    $colors['tech_color']
                ) . '</div>
            </td>
        </tr>
    </table>';

    return $css . $header . $kpiHtml . $mainGrid;
}

function generateStyledTable($title, $headers, $data, $dataKeys, $alignments) {
    if (empty($data)) {
        return '<div class="section-title">' . $title . '</div><p class="no-data">Nenhum dado disponível</p>';
    }

    $html = '<div class="section-title">' . $title . '</div>';
    $html .= '<table class="data-table"><thead><tr>';
    foreach ($headers as $header) {
        $html .= '<th>' . $header . '</th>';
    }
    $html .= '</tr></thead><tbody>';

    foreach ($data as $row) {
        $html .= '<tr>';
        foreach ($dataKeys as $index => $key) {
            $value = $row[$key] ?? 'N/A';
            if ($key === 'category' || $key === 'technician' || $key === 'sector') {
                $value = htmlspecialchars(substr($value, 0, 25));
            }
            $alignClass = $alignments[$index] ?? 'text-left';
            $html .= '<td class="' . $alignClass . '">' . $value . '</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';
    return $html;
}

function generateWorkloadBlock($title, $workload, $keyName, $barColor) {
    if (empty($workload)) {
        return '<div class="section-title">' . $title . '</div><p class="no-data">Nenhum dado disponível</p>';
    }

    $html = '<div class="section-title">' . $title . '</div>';
    $maxValue = max(array_column($workload, 'tickets'));
    if ($maxValue == 0) $maxValue = 1;

    $html .= '<table class="noborder" style="font-size: 7.5pt;">';

    foreach ($workload as $item) {
        $label = htmlspecialchars(substr($item[$keyName], 0, 20));
        $value = $item['tickets'];
        $widthPercent = round(($value / $maxValue) * 100);
        $emptyWidthPercent = 100 - $widthPercent;

        $html .= '
        <tr>
            <td style="width: 30%; color: #34495e;">' . $label . '</td>
            <td style="width: 60%;">
                <table class="noborder" cellpadding="0" cellspacing="0" style="width: 100%; height: 12px;">
                    <tr>
                        <td style="background-color: ' . $barColor . '; width: ' . $widthPercent . '%;"></td>
                        <td style="background-color: #e8ecef; width: ' . $emptyWidthPercent . '%;"></td>
                    </tr>
                </table>
            </td>
            <td style="width: 10%; text-align: right; font-weight: bold;">' . $value . '</td>
        </tr>';
    }

    $html .= '</table>';
    return $html;
}

// As funções abaixo não são mais usadas, mas são mantidas para evitar quebras se algo ainda as chamar.
function generateAdvancedSLAGauge($sla) { return ''; }
function generateMonthlyComparisonTable($comparison) { return ''; }
function generateTopCategoriesTable($categories) { return ''; }
function generateSLAGauge($sla) { return ''; }
function generateCompactWorkloadTableSector($workload) { return ''; }
function generateWorkloadChartSector($workload) { return ''; }
function generateCompactWorkloadTableTechnician($workload) { return ''; }
function generateWorkloadChartTechnician($workload) { return ''; }
?>