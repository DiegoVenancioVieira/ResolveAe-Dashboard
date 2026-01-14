<?php
// Disable error display for PDF generation to prevent header issues
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/Metrics/ExecutiveMetrics.php';
require_once __DIR__ . '/../../../../vendor/tecnickcom/tcpdf/tcpdf.php';

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

// Logo - remover se não existir ou se causar problemas com alpha channel
$logoSrc = '';
$logoPath = realpath(__DIR__ . '/../../public/pics/resolveae.png');
if ($logoPath && file_exists($logoPath)) {
    // Verificar se é PNG e tentar converter
    $imageInfo = getimagesize($logoPath);
    if ($imageInfo && $imageInfo['mime'] === 'image/png') {
        // Tentar usar o logo, mas se der erro o TCPDF vai ignorar
        try {
            $logoData = base64_encode(file_get_contents($logoPath));
            $logoSrc = '@' . $logoData;
        } catch (Exception $e) {
            // Se der erro, deixa sem logo
            $logoSrc = '';
        }
    }
}
$logoSrc = '';

// Gerar gráficos HTML (compatível com TCPDF)
$charts = generateChartsHTML($kpis, $trends, $topCategories, $workloadTechnicians, $workloadSectors, $monthlyComparison);

// Gerar o HTML para o PDF
$html = generateCompactPdfHtml($kpis, $trends, $topCategories, $workloadTechnicians, $workloadSectors, $monthlyComparison, $dateFrom, $dateTo, $entityName, $charts, $logoSrc);

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

function generateCompactPdfHtml($kpis, $trends, $topCategories, $workloadTechnicians, $workloadSectors, $monthlyComparison, $dateFrom, $dateTo, $entityName, $charts, $logoSrc) {
    $period = date('d/m/Y', strtotime($dateFrom)) . ' - ' . date('d/m/Y', strtotime($dateTo));

    // Estilos CSS
    $css = <<<EOD
<style>
    body { font-family: helvetica; font-size: 8pt; color: #333; }
    .header { background-color: #f2f2f2; padding: 10px; text-align: center; }
    .title { font-size: 14pt; font-weight: bold; color: #004a8f; }
    .subtitle { font-size: 9pt; color: #555; }
    .section-title { font-size: 10pt; font-weight: bold; color: #333; background-color: #e9ecef; padding: 4px; margin-top: 8px; margin-bottom: 4px; border-radius: 4px; }
    .kpi-grid { border-spacing: 4px; border-collapse: separate; width: 100%; }
    .kpi-box { background-color: #f8f9fa; text-align: center; padding: 6px; border: 1px solid #dee2e6; border-radius: 4px; }
    .kpi-value { font-size: 12pt; font-weight: bold; color: #004a8f; }
    .kpi-label { font-size: 7pt; color: #6c757d; }
    .kpi-trend { font-size: 7pt; }
    .chart-container { width: 100%; padding: 5px; background-color: #fdfdfd; border: 1px solid #eee; border-radius: 4px; }
    table { width: 100%; border-collapse: collapse; font-size: 7.5pt; }
    th, td { border: 1px solid #ddd; padding: 4px; }
    th { background-color: #f2f2f2; font-weight: bold; text-align: left; }
    tr.stripe { background-color: #f9f9f9; }
    .noborder, .noborder tr, .noborder td { border: 0; }
    .no-data { text-align: center; color: #888; padding: 10px; }
    .sla-gauge-container { text-align: center; }
    .sla-gauge-value { font-size: 24pt; font-weight: bold; }
    .trend-up { color: #28a745; }
    .trend-down { color: #dc3545; }
    .trend-stable { color: #6c757d; }
</style>
EOD;

    // Cabeçalho
    $header = '
    <table class="noborder">
        <tr>
            <td style="width: 25%; text-align: left;">
                <img src="' . $logoSrc . '" height="30">
            </td>
            <td style="width: 50%; text-align: center;">
                <div class="title">Dashboard Executivo</div>
                <div class="subtitle">Período: ' . $period . '</div>
                <div class="subtitle">Entidade: ' . htmlspecialchars($entityName) . '</div>
            </td>
            <td style="width: 25%; text-align: right; font-size: 7pt; color: #888;">
                Gerado em: ' . date('d/m/Y H:i') . '<br>ResolveAE BI
            </td>
        </tr>
    </table>';

    // KPIs
    $kpiHtml = '
    <table class="kpi-grid">
        <tr>
            <td class="kpi-box">
                <div class="kpi-value">' . $kpis['total_tickets'] . '</div>
                <div class="kpi-label">Tickets Abertos</div>
            </td>
            <td class="kpi-box">
                <div class="kpi-value">' . round($kpis['sla_compliance'], 1) . '%</div>
                <div class="kpi-label">SLA</div>
            </td>
            <td class="kpi-box">
                <div class="kpi-value">' . $kpis['avg_resolution_time'] . '</div>
                <div class="kpi-label">TMR</div>
            </td>
            <td class="kpi-box">
                <div class="kpi-value">' . $kpis['satisfaction_score'] . '/5</div>
                <div class="kpi-label">Satisfação (' . $kpis['satisfaction_responses'] . ' av.)</div>
            </td>
            <td class="kpi-box">
                <div class="kpi-value">' . $kpis['productivity'] . '</div>
                <div class="kpi-label">Produtividade (tkt/tec/dia)</div>
            </td>
        </tr>
    </table>';

    // Corpo Principal (2 colunas)
    $mainContent = '
    <table class="noborder" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 4px;">
                <!-- Coluna 1: Tendências e Categorias -->
                <div class="section-title">📈 Tendência Mensal (Últimos 6 Meses)</div>
                ' . (!empty($charts['trends']) ? $charts['trends'] : generateMonthlyComparisonTable($monthlyComparison)) . '

                <div class="section-title">🎯 Top 5 Categorias Problemáticas</div>
                ' . (!empty($charts['categories']) ? $charts['categories'] : generateTopCategoriesTable($topCategories)) . '
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 4px;">
                <!-- Coluna 2: Workload por Setor e SLA -->
                <div class="section-title">🏢 Top 5 Setores - Workload</div>
                ' . (!empty($charts['workloadSectors']) ? $charts['workloadSectors'] : generateCompactWorkloadTableSector(array_slice($workloadSectors, 0, 5))) . '

                <div class="section-title" style="margin-top: 10px;">⚡ SLA Compliance</div>
                ' . (!empty($charts['sla']) ? $charts['sla'] : generateSLAGauge($kpis['sla_compliance'])) . '
            </td>
        </tr>
    </table>';

    // Seção Workload por Técnico (abaixo)
    $technicianWorkloadSection = '
    <div class="section-title">👥 Técnicos - Workload</div>
    <table class="noborder" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 100%; vertical-align: top;">
                ' . (!empty($charts['workloadTechnicians']) ? $charts['workloadTechnicians'] : generateCompactWorkloadTableTechnician($workloadTechnicians)) . '
            </td>
        </tr>
    </table>';


    return $css . $header . $kpiHtml . $mainContent . $technicianWorkloadSection;
}

function generateChartsHTML($kpis, $trends, $topCategories, $workloadTechnicians, $workloadSectors, $monthlyComparison) {
    $charts = [];

    // Gráfico 1: Tendência Mensal (Tabela com barras)
    $charts['trends'] = generateLineChartHTML($monthlyComparison);

    // Gráfico 2: Top Categorias (Barras Horizontais)
    $charts['categories'] = generateHorizontalBarChartHTML($topCategories);

    // Gráfico 3: Workload por Setor (Barras Verticais - Top 5)
    $charts['workloadSectors'] = generateBarChartHTML(array_slice($workloadSectors, 0, 5), '#10b981');

    // Gráfico 4: Workload por Técnico (Barras Verticais - TODOS os técnicos)
    $charts['workloadTechnicians'] = generateBarChartHTML($workloadTechnicians, '#3b82f6');

    // Gráfico 5: SLA Compliance (Gauge visual)
    $charts['sla'] = generateGaugeChartHTML($kpis['sla_compliance']);

    return $charts;
}

// --- Funções de Geração de Gráficos HTML (100% compatível com TCPDF) ---

function generateLineChartHTML($data) {
    if (empty($data)) return '';

    $maxValue = 0;
    foreach ($data as $item) {
        if (isset($item['tickets'])) {
            $maxValue = max($maxValue, $item['tickets']);
        }
    }
    if ($maxValue == 0) $maxValue = 1;

    $html = '<table style="width: 100%; border-collapse: collapse; font-size: 7pt;">';
    $html .= '<tr>';
    foreach ($data as $item) {
        $tickets = isset($item['tickets']) ? $item['tickets'] : 0;
        $height = ($tickets / $maxValue) * 80;
        $month = isset($item['month']) ? substr($item['month'], 0, 3) : '';

        $html .= '<td style="text-align: center; vertical-align: bottom; width: ' . (100 / count($data)) . '%; padding: 2px;">';
        $html .= '<div style="background-color: #3b82f6; height: ' . $height . 'px; margin: 0 auto; width: 80%;"></div>';
        $html .= '<div style="font-size: 6pt; margin-top: 2px;">' . $tickets . '</div>';
        $html .= '<div style="font-size: 6pt; color: #666;">' . $month . '</div>';
        $html .= '</td>';
    }
    $html .= '</tr></table>';

    return $html;
}

function generateHorizontalBarChartHTML($data) {
    if (empty($data)) return '';

    $maxValue = 0;
    foreach ($data as $item) {
        if (isset($item['tickets'])) {
            $maxValue = max($maxValue, $item['tickets']);
        }
    }
    if ($maxValue == 0) $maxValue = 1;

    $html = '<table style="width: 100%; border-collapse: collapse; font-size: 7pt;">';
    foreach ($data as $item) {
        $tickets = isset($item['tickets']) ? $item['tickets'] : 0;
        $width = ($tickets / $maxValue) * 100;
        $category = isset($item['category']) ? substr($item['category'], 0, 25) : 'N/A';

        $html .= '<tr>';
        $html .= '<td style="width: 40%; padding: 3px; font-size: 6.5pt;">' . htmlspecialchars($category) . '</td>';
        $html .= '<td style="width: 50%; padding: 3px;">';
        $html .= '<div style="background-color: #f97316; height: 12px; width: ' . $width . '%;"></div>';
        $html .= '</td>';
        $html .= '<td style="width: 10%; text-align: right; padding: 3px; font-size: 6.5pt;">' . $tickets . '</td>';
        $html .= '</tr>';
    }
    $html .= '</table>';

    return $html;
}

function generateBarChartHTML($data, $color = '#3b82f6') {
    if (empty($data)) return '';

    $maxValue = 0;
    foreach ($data as $item) {
        if (isset($item['tickets'])) {
            $maxValue = max($maxValue, $item['tickets']);
        }
    }
    if ($maxValue == 0) $maxValue = 1;

    $html = '<table style="width: 100%; border-collapse: collapse; font-size: 7pt;">';
    $html .= '<tr>';
    foreach ($data as $item) {
        $tickets = isset($item['tickets']) ? $item['tickets'] : 0;
        $height = ($tickets / $maxValue) * 80;

        $name = '';
        if (isset($item['sector'])) {
            $name = substr($item['sector'], 0, 12);
        } elseif (isset($item['technician'])) {
            $name = substr(explode(' ', $item['technician'])[0], 0, 12);
        }

        $html .= '<td style="text-align: center; vertical-align: bottom; width: ' . (100 / count($data)) . '%; padding: 2px;">';
        $html .= '<div style="background-color: ' . $color . '; height: ' . $height . 'px; margin: 0 auto; width: 80%;"></div>';
        $html .= '<div style="font-size: 6pt; margin-top: 2px;">' . $tickets . '</div>';
        $html .= '<div style="font-size: 6pt; color: #666; word-wrap: break-word;">' . htmlspecialchars($name) . '</div>';
        $html .= '</td>';
    }
    $html .= '</tr></table>';

    return $html;
}

function generateGaugeChartHTML($value) {
    $color = '#28a745'; // green
    if ($value < 80) $color = '#ffc107'; // yellow
    if ($value < 60) $color = '#dc3545'; // red

    $html = '<div style="text-align: center; padding: 10px;">';
    $html .= '<div style="font-size: 32pt; font-weight: bold; color: ' . $color . ';">' . round($value, 1) . '%</div>';
    $html .= '<div style="font-size: 8pt; color: #666;">SLA Compliance</div>';

    // Barra visual
    $html .= '<div style="width: 80%; margin: 10px auto; height: 15px; background-color: #ddd; border-radius: 10px; overflow: hidden;">';
    $html .= '<div style="width: ' . $value . '%; height: 100%; background-color: ' . $color . ';"></div>';
    $html .= '</div>';

    $html .= '<div style="font-size: 6pt; color: #999;">0% ───────────────── 100%</div>';
    $html .= '</div>';

    return $html;
}

function generateChartsSVG($kpis, $trends, $topCategories, $workloadTechnicians, $workloadSectors, $monthlyComparison) {
    $charts = [];

    // Gráfico 1: Tendência Mensal (Linha)
    $charts['trends'] = generateLineChartSVG($monthlyComparison);

    // Gráfico 2: Top Categorias (Barras Horizontais)
    $charts['categories'] = generateHorizontalBarChartSVG($topCategories);

    // Gráfico 3: Workload por Setor (Barras Verticais)
    $charts['workloadSectors'] = generateBarChartSVG(array_slice($workloadSectors, 0, 5), '#10b981');

    // Gráfico 4: Workload por Técnico (Barras Verticais)
    $charts['workloadTechnicians'] = generateBarChartSVG(array_slice($workloadTechnicians, 0, 5), '#3b82f6');

    // Gráfico 5: SLA Compliance (Gauge)
    $charts['sla'] = generateGaugeChartSVG($kpis['sla_compliance']);

    return $charts;
}

// --- Funções de Geração de Gráficos SVG (sem dependência de GD) ---

function generateLineChartSVG($data) {
    if (empty($data)) return '';

    $width = 400;
    $height = 180;
    $padding = 40;
    $chartWidth = $width - ($padding * 2);
    $chartHeight = $height - 60;

    // Find max value
    $maxValue = 0;
    foreach ($data as $item) {
        if (isset($item['tickets'])) {
            $maxValue = max($maxValue, $item['tickets']);
        }
    }
    if ($maxValue == 0) $maxValue = 1;

    $svg = '<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">';
    $svg .= '<rect width="100%" height="100%" fill="white"/>';

    // Grid
    $svg .= '<rect x="' . $padding . '" y="30" width="' . $chartWidth . '" height="' . $chartHeight . '" fill="none" stroke="#ccc" stroke-width="1"/>';

    // Points and line
    $count = count($data);
    if ($count > 1) {
        $stepX = $chartWidth / ($count - 1);
        $points = [];
        $index = 0;

        foreach ($data as $item) {
            $tickets = isset($item['tickets']) ? $item['tickets'] : 0;
            $x = $padding + ($stepX * $index);
            $y = 30 + $chartHeight - (($tickets / $maxValue) * $chartHeight);
            $points[] = $x . ',' . $y;

            // Draw point
            $svg .= '<circle cx="' . $x . '" cy="' . $y . '" r="3" fill="#3b82f6"/>';

            // Label
            if (isset($item['month'])) {
                $label = substr($item['month'], 0, 3);
                $svg .= '<text x="' . $x . '" y="' . ($height - 5) . '" font-size="10" text-anchor="middle" fill="#000">' . htmlspecialchars($label) . '</text>';
            }

            $index++;
        }

        // Draw line
        $svg .= '<polyline points="' . implode(' ', $points) . '" fill="none" stroke="#3b82f6" stroke-width="2"/>';
    }

    $svg .= '</svg>';
    return $svg;
}

function generateHorizontalBarChartSVG($data) {
    if (empty($data)) return '';

    $width = 400;
    $height = 180;
    $labelWidth = 120;
    $chartX = $labelWidth;
    $chartWidth = $width - $labelWidth - 20;

    // Find max value
    $maxValue = 0;
    foreach ($data as $item) {
        if (isset($item['tickets'])) {
            $maxValue = max($maxValue, $item['tickets']);
        }
    }
    if ($maxValue == 0) $maxValue = 1;

    $svg = '<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">';
    $svg .= '<rect width="100%" height="100%" fill="white"/>';

    $count = count($data);
    $barHeight = ($height - 20) / $count - 8;
    $y = 10;

    foreach ($data as $item) {
        $tickets = isset($item['tickets']) ? $item['tickets'] : 0;
        $barWidth = ($tickets / $maxValue) * $chartWidth;

        // Bar background
        $svg .= '<rect x="' . $chartX . '" y="' . $y . '" width="' . $chartWidth . '" height="' . $barHeight . '" fill="none" stroke="#ccc" stroke-width="1"/>';

        // Bar
        if ($barWidth > 0) {
            $svg .= '<rect x="' . $chartX . '" y="' . $y . '" width="' . $barWidth . '" height="' . $barHeight . '" fill="#f97316"/>';
        }

        // Label
        $category = isset($item['category']) ? $item['category'] : 'N/A';
        $label = substr($category, 0, 18);
        $svg .= '<text x="5" y="' . ($y + $barHeight/2 + 4) . '" font-size="10" fill="#000">' . htmlspecialchars($label) . '</text>';

        // Value
        $svg .= '<text x="' . ($chartX + $barWidth + 5) . '" y="' . ($y + $barHeight/2 + 4) . '" font-size="10" fill="#000">' . $tickets . '</text>';

        $y += $barHeight + 8;
    }

    $svg .= '</svg>';
    return $svg;
}

function generateBarChartSVG($data, $color = '#3b82f6') {
    if (empty($data)) return '';

    $width = 400;
    $height = 180;

    // Find max value
    $maxValue = 0;
    foreach ($data as $item) {
        if (isset($item['tickets'])) {
            $maxValue = max($maxValue, $item['tickets']);
        }
    }
    if ($maxValue == 0) $maxValue = 1;

    $svg = '<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">';
    $svg .= '<rect width="100%" height="100%" fill="white"/>';

    $count = count($data);
    $barWidth = ($width - 40) / $count;
    $chartHeight = $height - 50;
    $x = 20;

    foreach ($data as $item) {
        $tickets = isset($item['tickets']) ? $item['tickets'] : 0;
        $barH = ($tickets / $maxValue) * $chartHeight;
        $barY = $height - 30 - $barH;

        // Bar
        $svg .= '<rect x="' . $x . '" y="' . $barY . '" width="' . ($barWidth - 5) . '" height="' . $barH . '" fill="' . $color . '"/>';

        // Label
        $name = '';
        if (isset($item['sector'])) {
            $name = substr($item['sector'], 0, 10);
        } elseif (isset($item['technician'])) {
            $name = substr(explode(' ', $item['technician'])[0], 0, 10);
        }
        $svg .= '<text x="' . ($x + $barWidth/2 - 5) . '" y="' . ($height - 15) . '" font-size="10" fill="#000">' . htmlspecialchars($name) . '</text>';

        // Value
        $svg .= '<text x="' . ($x + $barWidth/2 - 5) . '" y="' . ($barY - 5) . '" font-size="10" fill="#000">' . $tickets . '</text>';

        $x += $barWidth;
    }

    $svg .= '</svg>';
    return $svg;
}

function generateGaugeChartSVG($value) {
    $width = 200;
    $height = 200;
    $centerX = $width / 2;
    $centerY = $height / 2 + 20;
    $radius = 70;

    // Determine color
    $color = '#28a745'; // green
    if ($value < 80) $color = '#ffc107'; // yellow
    if ($value < 60) $color = '#dc3545'; // red

    $svg = '<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">';
    $svg .= '<rect width="100%" height="100%" fill="white"/>';

    // Background arc
    $svg .= '<path d="M ' . ($centerX - $radius) . ' ' . $centerY . ' A ' . $radius . ' ' . $radius . ' 0 0 1 ' . ($centerX + $radius) . ' ' . $centerY . '" fill="none" stroke="#ddd" stroke-width="15" stroke-linecap="round"/>';

    // Value arc
    $angle = ($value / 100) * 180;
    $endX = $centerX - ($radius * cos(deg2rad($angle)));
    $endY = $centerY - ($radius * sin(deg2rad($angle)));
    $largeArc = $angle > 90 ? 1 : 0;

    $svg .= '<path d="M ' . ($centerX - $radius) . ' ' . $centerY . ' A ' . $radius . ' ' . $radius . ' 0 ' . $largeArc . ' 1 ' . $endX . ' ' . $endY . '" fill="none" stroke="' . $color . '" stroke-width="15" stroke-linecap="round"/>';

    // Value text
    $svg .= '<text x="' . $centerX . '" y="' . ($centerY - 5) . '" font-size="24" font-weight="bold" text-anchor="middle" fill="' . $color . '">' . round($value, 1) . '%</text>';

    // Min/Max labels
    $svg .= '<text x="' . ($centerX - $radius) . '" y="' . ($centerY + 15) . '" font-size="10" fill="#000">0%</text>';
    $svg .= '<text x="' . ($centerX + $radius - 20) . '" y="' . ($centerY + 15) . '" font-size="10" fill="#000">100%</text>';

    $svg .= '</svg>';
    return $svg;
}

// --- Funções de Geração de Gráficos GD (DESATIVADAS - GD não disponível) ---

function generateLineChart($data, $title, $width, $height) {
    if (empty($data)) {
        error_log("generateLineChart: dados vazios");
        return '';
    }
    error_log("generateLineChart: " . count($data) . " itens");

    $img = imagecreatetruecolor($width, $height);
    if (!$img) {
        error_log("generateLineChart: falha ao criar imagem");
        return '';
    }
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);
    $blue = imagecolorallocate($img, 59, 130, 246); // #3b82f6
    $gray = imagecolorallocate($img, 200, 200, 200);
    $lightgray = imagecolorallocate($img, 240, 240, 240);
    imagefill($img, 0, 0, $white);

    // Title
    imagestring($img, 3, 10, 10, $title, $black);

    // Chart area
    $padding = 40;
    $chartX = $padding;
    $chartY = 30;
    $chartWidth = $width - ($padding * 2);
    $chartHeight = $height - 60;

    // Draw grid background
    imagerectangle($img, $chartX, $chartY, $chartX + $chartWidth, $chartY + $chartHeight, $gray);

    // Find min and max values
    $maxValue = 0;
    foreach ($data as $item) {
        if (isset($item['tickets'])) {
            $maxValue = max($maxValue, $item['tickets']);
        }
    }
    if ($maxValue == 0) $maxValue = 1;

    // Draw horizontal grid lines
    for ($i = 0; $i <= 4; $i++) {
        $y = (int)($chartY + ($chartHeight / 4) * $i);
        imageline($img, $chartX, $y, $chartX + $chartWidth, $y, $lightgray);
    }

    // Plot data points and lines
    $count = count($data);
    if ($count > 1) {
        $stepX = $chartWidth / ($count - 1);
        $prevX = null;
        $prevY = null;

        $index = 0;
        foreach ($data as $item) {
            $tickets = isset($item['tickets']) ? $item['tickets'] : 0;
            $x = (int)($chartX + ($stepX * $index));
            $y = (int)($chartY + $chartHeight - (($tickets / $maxValue) * $chartHeight));

            // Draw line from previous point
            if ($prevX !== null && $prevY !== null) {
                imageline($img, $prevX, $prevY, $x, $y, $blue);
            }

            // Draw point
            imagefilledellipse($img, $x, $y, 6, 6, $blue);

            // Draw label (month)
            if (isset($item['month'])) {
                $label = substr($item['month'], 0, 3);
                imagestring($img, 2, $x - 10, $chartY + $chartHeight + 5, $label, $black);
            }

            $prevX = $x;
            $prevY = $y;
            $index++;
        }
    }

    ob_start();
    imagepng($img);
    $imageData = ob_get_clean();
    imagedestroy($img);
    return 'data:image/png;base64,' . base64_encode($imageData);
}

function generateHorizontalBarChart($data, $title, $width, $height) {
    if (empty($data)) return '';

    $img = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);
    $orange = imagecolorallocate($img, 249, 115, 22); // #f97316
    $gray = imagecolorallocate($img, 200, 200, 200);
    imagefill($img, 0, 0, $white);

    // Title
    imagestring($img, 3, 10, 10, $title, $black);

    // Chart area
    $labelWidth = 120;
    $chartX = $labelWidth;
    $chartY = 35;
    $chartWidth = $width - $labelWidth - 20;
    $chartHeight = $height - 45;

    // Find max value
    $maxValue = 0;
    foreach ($data as $item) {
        if (isset($item['tickets'])) {
            $maxValue = max($maxValue, $item['tickets']);
        }
    }
    if ($maxValue == 0) $maxValue = 1;

    // Draw bars
    $count = count($data);
    $barHeight = ($chartHeight / $count) - 8;
    $y = $chartY;

    foreach ($data as $item) {
        $tickets = isset($item['tickets']) ? $item['tickets'] : 0;
        $barWidth = (int)(($tickets / $maxValue) * $chartWidth);

        // Draw bar background
        imagerectangle($img, $chartX, (int)$y, $chartX + $chartWidth, (int)($y + $barHeight), $gray);

        // Draw filled bar
        if ($barWidth > 0) {
            imagefilledrectangle($img, $chartX, (int)$y, $chartX + $barWidth, (int)($y + $barHeight), $orange);
        }

        // Draw category label
        $category = isset($item['category']) ? $item['category'] : 'N/A';
        $label = substr($category, 0, 18);
        imagestring($img, 2, 5, (int)($y + ($barHeight / 2) - 6), $label, $black);

        // Draw value
        imagestring($img, 2, $chartX + $barWidth + 5, (int)($y + ($barHeight / 2) - 6), $tickets, $black);

        $y += $barHeight + 8;
    }

    ob_start();
    imagepng($img);
    $imageData = ob_get_clean();
    imagedestroy($img);
    return 'data:image/png;base64,' . base64_encode($imageData);
}

function generateBarChart($data, $title, $width, $height, $colorHex = '#3b82f6') {
    if (empty($data)) return '';

    $img = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);
    
    // Convert hex to RGB
    list($r, $g, $b) = sscanf($colorHex, "#%02x%02x%02x");
    $barColor = imagecolorallocate($img, $r, $g, $b);
    
    imagefill($img, 0, 0, $white);

    // Simplified drawing for demonstration
    imagestring($img, 3, 10, 10, $title, $black);

    // Find max value for scaling
    $maxValue = 0;
    foreach ($data as $item) {
        if (isset($item['tickets'])) {
            $maxValue = max($maxValue, $item['tickets']);
        }
    }
    if ($maxValue == 0) $maxValue = 1;

    $barWidth = ($width - 40) / count($data); // 20px padding on each side
    $x = 20;
    $chartHeight = $height - 50; // Space for title and labels

    foreach ($data as $item) {
        $barHeight = ($item['tickets'] / $maxValue) * $chartHeight;
        imagefilledrectangle($img, (int)$x, (int)($height - 30 - $barHeight), (int)($x + $barWidth - 5), (int)($height - 30), $barColor);

        // Label
        $name = '';
        if (isset($item['sector'])) {
            $name = substr($item['sector'], 0, 10);
        } elseif (isset($item['technician'])) {
            $name = substr(explode(' ', $item['technician'])[0], 0, 10);
        }
        imagestring($img, 2, (int)$x, $height - 25, $name, $black);
        imagestring($img, 2, (int)$x, (int)($height - 40 - $barHeight), $item['tickets'], $black); // Value above bar
        $x += $barWidth;
    }

    ob_start();
    imagepng($img);
    $imageData = ob_get_clean();
    imagedestroy($img);
    return 'data:image/png;base64,' . base64_encode($imageData);
}

function generateGaugeChart($value, $title, $width, $height) {
    $img = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);
    $green = imagecolorallocate($img, 40, 167, 69); // #28a745
    $yellow = imagecolorallocate($img, 255, 193, 7); // #ffc107
    $red = imagecolorallocate($img, 220, 53, 69); // #dc3545
    $lightgray = imagecolorallocate($img, 220, 220, 220);
    imagefill($img, 0, 0, $white);

    // Title
    imagestring($img, 3, (int)(($width - (imagefontwidth(3) * strlen($title))) / 2), 10, $title, $black);

    // Determine color based on value
    $color = $green;
    if ($value < 80) $color = $yellow;
    if ($value < 60) $color = $red;

    // Gauge parameters
    $centerX = (int)($width / 2);
    $centerY = (int)($height / 2 + 20);
    $radius = (int)min($width, $height - 40) / 2 - 20;

    // Draw background arc (gray semicircle)
    imagesetthickness($img, 15);
    imagearc($img, $centerX, $centerY, $radius * 2, $radius * 2, 180, 0, $lightgray);

    // Draw value arc
    $endAngle = (int)(180 - ($value / 100 * 180));
    if ($endAngle < 0) $endAngle = 0;
    imagearc($img, $centerX, $centerY, $radius * 2, $radius * 2, 180, $endAngle, $color);

    imagesetthickness($img, 1);

    // Draw percentage text
    $text = round($value, 1) . '%';
    $fontSize = 5;
    $textWidth = imagefontwidth($fontSize) * strlen($text);
    imagestring($img, $fontSize, (int)(($width - $textWidth) / 2), $centerY - 10, $text, $color);

    // Draw min/max labels
    imagestring($img, 2, $centerX - $radius, $centerY + 5, '0%', $black);
    imagestring($img, 2, $centerX + $radius - 25, $centerY + 5, '100%', $black);

    ob_start();
    imagepng($img);
    $imageData = ob_get_clean();
    imagedestroy($img);
    return 'data:image/png;base64,' . base64_encode($imageData);
}

// --- Fallback functions (same as in the non-GD version) ---

function generateMonthlyComparisonTable($comparison) {
    if (empty($comparison)) {
        return '<p class="no-data">Nenhum dado disponível</p>';
    }
    $html = '<table><thead><tr><th>Mês</th><th style="text-align: center;">Tickets</th><th style="text-align: center;">SLA (%)</th></tr></thead><tbody>';
    $stripe = false;
    foreach ($comparison as $row) {
        $rowClass = $stripe ? ' class="stripe"' : '';
        $html .= '<tr' . $rowClass . '>
            <td>' . $row['month'] . '</td>
            <td style="text-align: center;">' . $row['tickets'] . '</td>
            <td style="text-align: center;">' . $row['sla'] . '</td>
        </tr>';
        $stripe = !$stripe;
    }
    $html .= '</tbody></table>';
    return $html;
}

function generateTopCategoriesTable($categories) {
    if (empty($categories)) {
        return '<p class="no-data">Nenhum dado disponível</p>';
    }
    $html = '<table><thead><tr><th>Categoria</th><th style="text-align: center;">Tickets</th><th style="text-align: center;">TMR</th><th style="text-align: center;">SLA (%)</th></tr></thead><tbody>';
    $stripe = false;
    foreach ($categories as $cat) {
        $rowClass = $stripe ? ' class="stripe"' : '';
        $html .= '<tr' . $rowClass . '>
            <td>' . htmlspecialchars(substr($cat['category'], 0, 25)) . '</td>
            <td style="text-align: center;">' . $cat['tickets'] . '</td>
            <td style="text-align: center;">' . $cat['avg_resolution_time'] . '</td>
            <td style="text-align: center;">' . round($cat['sla_compliance'], 1) . '</td>
        </tr>';
        $stripe = !$stripe;
    }
    $html .= '</tbody></table>';
    return $html;
}

function generateSLAGauge($sla) {
    $sla = round($sla, 1);
    $color = '#28a745'; // Verde
    if ($sla < 80) $color = '#ffc107'; // Amarelo
    if ($sla < 60) $color = '#dc3545'; // Vermelho

    return '
    <div class="sla-gauge-container">
        <div class="sla-gauge-value" style="color: ' . $color . ';">' . $sla . '%</div>
        <div class="kpi-label">Meta de SLA Atingida</div>
    </div>';
}

function generateCompactWorkloadTableSector($workload) {
    if (empty($workload)) {
        return '<p class="no-data">Nenhum dado disponível</p>';
    }

    $html = '<table><thead><tr>
        <th>Setor</th>
        <th style="text-align: center;">Tickets</th>
        <th style="text-align: center;">Produtividade</th>
    </tr></thead><tbody>';

    $stripe = false;
    foreach ($workload as $sector) {
        $rowClass = $stripe ? ' class="stripe"' : '';
        $html .= '<tr' . $rowClass . '>
            <td style="font-weight: bold;">' . htmlspecialchars(substr($sector['sector'], 0, 20)) . '</td>
            <td style="text-align: center; font-weight: bold;">' . $sector['tickets'] . '</td>
            <td style="text-align: center;">' . round($sector['productivity'], 1) . '/dia</td>
        </tr>';
        $stripe = !$stripe;
    }

    $html .= '</tbody></table>';
    return $html;
}

function generateCompactWorkloadTableTechnician($workload) {
    if (empty($workload)) {
        return '<p class="no-data">Nenhum dado disponível</p>';
    }

    $html = '<table><thead><tr>
        <th>Técnico</th>
        <th style="text-align: center;">Tickets</th>
        <th style="text-align: center;">Produtividade</th>
    </tr></thead><tbody>';

    $stripe = false;
    foreach ($workload as $tech) {
        $rowClass = $stripe ? ' class="stripe"' : '';
        $html .= '<tr' . $rowClass . '>
            <td style="font-weight: bold;">' . htmlspecialchars(substr($tech['technician'], 0, 20)) . '</td>
            <td style="text-align: center; font-weight: bold;">' . $tech['tickets'] . '</td>
            <td style="text-align: center;">' . round($tech['productivity'], 1) . '/dia</td>
        </tr>';
        $stripe = !$stripe;
    }

    $html .= '</tbody></table>';
    return $html;
}
?>