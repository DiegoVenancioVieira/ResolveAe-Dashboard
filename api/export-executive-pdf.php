<?php
/**
 * Exportação em PDF do Dashboard Executivo
 * Gera um PDF com todos os dados em uma página única
 */

require_once __DIR__ . '/../includes/ExecutiveMetrics.php';

// Validar parâmetros
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-t');
$entityId = !empty($_GET['entity_id']) ? (int)$_GET['entity_id'] : null;

// Validar formato de data
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
    http_response_code(400);
    die('Formato de data inválido. Use Y-m-d.');
}

try {
    // Obter métricas
    $metrics = new ExecutiveMetrics($dateFrom, $dateTo, $entityId);
    
    $kpis = $metrics->getKPIs();
    $trends = $metrics->getTrends();
    $topCategories = $metrics->getTopProblematicCategories();
    $workload = $metrics->getWorkloadDistribution();
    $monthlyComparison = $metrics->getMonthlyComparison();
    
    // Criar HTML para o PDF
    $html = generatePdfHtml($kpis, $trends, $topCategories, $workload, $monthlyComparison, $dateFrom, $dateTo);
    
    // Gerar PDF usando técnica simples (sem biblioteca externa)
    generatePdfWithHtml($html, $dateFrom, $dateTo);
    
} catch (Exception $e) {
    http_response_code(500);
    die('Erro ao gerar PDF: ' . $e->getMessage());
}

/**
 * Gerar HTML do PDF
 */
function generatePdfHtml($kpis, $trends, $topCategories, $workload, $monthlyComparison, $dateFrom, $dateTo) {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: Arial, sans-serif; color: #333; line-height: 1.4; }
            .page { page-break-after: always; padding: 20px; }
            .header { 
                text-align: center; 
                margin-bottom: 30px; 
                border-bottom: 3px solid #2563eb; 
                padding-bottom: 15px;
            }
            .header h1 { font-size: 24px; color: #2563eb; margin-bottom: 5px; }
            .header p { color: #666; font-size: 12px; }
            
            .period-info {
                text-align: center;
                margin-bottom: 20px;
                font-size: 12px;
                color: #666;
                background: #f0f0f0;
                padding: 10px;
                border-radius: 5px;
            }
            
            .kpis-section {
                display: grid;
                grid-template-columns: 1fr 1fr 1fr 1fr;
                gap: 15px;
                margin-bottom: 30px;
            }
            
            .kpi-card {
                border: 1px solid #ddd;
                padding: 15px;
                border-radius: 5px;
                text-align: center;
                background: #fafafa;
            }
            
            .kpi-label { font-size: 11px; color: #666; text-transform: uppercase; margin-bottom: 5px; }
            .kpi-value { font-size: 22px; font-weight: bold; color: #2563eb; }
            
            .section { margin-bottom: 25px; }
            .section-title { 
                font-size: 14px; 
                font-weight: bold; 
                color: #2563eb;
                margin-bottom: 12px;
                border-left: 4px solid #2563eb;
                padding-left: 10px;
            }
            
            table {
                width: 100%;
                border-collapse: collapse;
                font-size: 11px;
                margin-bottom: 15px;
            }
            
            th {
                background: #2563eb;
                color: white;
                padding: 8px;
                text-align: left;
                font-weight: bold;
            }
            
            td {
                padding: 6px 8px;
                border-bottom: 1px solid #eee;
            }
            
            tr:nth-child(even) { background: #f9f9f9; }
            
            .grid-2 {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
            }
            
            .metric-box {
                border: 1px solid #ddd;
                padding: 12px;
                border-radius: 5px;
                background: #fafafa;
            }
            
            .metric-label { font-size: 11px; color: #666; margin-bottom: 5px; }
            .metric-value { font-size: 18px; font-weight: bold; color: #333; }
            
            .footer {
                text-align: center;
                font-size: 10px;
                color: #999;
                margin-top: 20px;
                border-top: 1px solid #ddd;
                padding-top: 10px;
            }
            
            .no-data { color: #999; font-style: italic; }
        </style>
    </head>
    <body>
        <div class="page">
            <div class="header">
                <h1>Dashboard Executivo GLPI</h1>
                <p>Relatório de Métricas e Performance</p>
            </div>
            
            <div class="period-info">
                Período: ' . formatDate($dateFrom) . ' a ' . formatDate($dateTo) . ' | 
                Gerado em: ' . date('d/m/Y H:i:s') . '
            </div>
            
            <!-- KPIs Principais -->
            <div class="kpis-section">
                <div class="kpi-card">
                    <div class="kpi-label">Total de Chamados</div>
                    <div class="kpi-value">' . $kpis['total_tickets'] . '</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Conformidade SLA</div>
                    <div class="kpi-value">' . round($kpis['sla_compliance'], 1) . '%</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Tempo Médio Resolução</div>
                    <div class="kpi-value">' . $kpis['avg_resolution_time'] . '</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Satisfação</div>
                    <div class="kpi-value">' . $kpis['satisfaction_score'] . '/5</div>
                </div>
            </div>
            
            <!-- Categorias Problemáticas -->
            <div class="section">
                <div class="section-title">Top 5 Categorias Mais Problemáticas</div>
                ' . generateCategoriesTable($topCategories) . '
            </div>
            
            <!-- Distribuição de Workload -->
            <div class="section">
                <div class="section-title">Distribuição de Workload - Top 10 Técnicos</div>
                ' . generateWorkloadTable($workload) . '
            </div>
            
            <!-- Comparação Mensal -->
            <div class="section">
                <div class="section-title">Comparação Mensal (Últimos 6 Meses)</div>
                ' . generateMonthlyTable($monthlyComparison) . '
            </div>
            
            <!-- Tendências -->
            <div class="section">
                <div class="section-title">Tendências (Últimos 6 Meses)</div>
                ' . generateTrendsTable($trends) . '
            </div>
            
            <div class="footer">
                Este é um relatório confidencial gerado automaticamente pelo Dashboard Executivo GLPI
            </div>
        </div>
    </body>
    </html>
    ';
    
    return $html;
}

/**
 * Formatar data para apresentação
 */
function formatDate($dateStr) {
    $date = DateTime::createFromFormat('Y-m-d', $dateStr);
    return $date ? $date->format('d/m/Y') : $dateStr;
}

/**
 * Gerar tabela de categorias
 */
function generateCategoriesTable($categories) {
    if (empty($categories)) {
        return '<p class="no-data">Nenhuma categoria encontrada</p>';
    }
    
    $html = '<table>
        <thead>
            <tr>
                <th>Categoria</th>
                <th>Chamados</th>
                <th>Tempo Médio Resolução</th>
                <th>Conformidade SLA</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($categories as $cat) {
        $html .= '<tr>
            <td>' . htmlspecialchars($cat['category']) . '</td>
            <td>' . $cat['tickets'] . '</td>
            <td>' . $cat['avg_resolution_time'] . '</td>
            <td>' . round($cat['sla_compliance'], 1) . '%</td>
        </tr>';
    }
    
    $html .= '</tbody></table>';
    return $html;
}

/**
 * Gerar tabela de workload
 */
function generateWorkloadTable($workload) {
    if (empty($workload)) {
        return '<p class="no-data">Nenhum dado disponível</p>';
    }
    
    $html = '<table>
        <thead>
            <tr>
                <th>#</th>
                <th>Técnico</th>
                <th>Chamados</th>
                <th>Tempo Médio</th>
                <th>Produtividade</th>
            </tr>
        </thead>
        <tbody>';
    
    $index = 1;
    foreach ($workload as $tech) {
        $html .= '<tr>
            <td>' . $index . '</td>
            <td>' . htmlspecialchars($tech['technician']) . '</td>
            <td>' . $tech['tickets'] . '</td>
            <td>' . $tech['avg_time'] . '</td>
            <td>' . round($tech['productivity'], 2) . ' chamados/dia</td>
        </tr>';
        $index++;
    }
    
    $html .= '</tbody></table>';
    return $html;
}

/**
 * Gerar tabela de comparação mensal
 */
function generateMonthlyTable($monthly) {
    if (empty($monthly)) {
        return '<p class="no-data">Nenhum dado disponível</p>';
    }
    
    $html = '<table>
        <thead>
            <tr>
                <th>Mês</th>
                <th>Chamados</th>
                <th>Conformidade SLA</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($monthly as $month) {
        $html .= '<tr>
            <td>' . $month['month'] . '</td>
            <td>' . $month['tickets'] . '</td>
            <td>' . $month['sla'] . '%</td>
        </tr>';
    }
    
    $html .= '</tbody></table>';
    return $html;
}

/**
 * Gerar tabela de tendências
 */
function generateTrendsTable($trends) {
    if (empty($trends)) {
        return '<p class="no-data">Nenhum dado disponível</p>';
    }
    
    $html = '<table>
        <thead>
            <tr>
                <th>Mês</th>
                <th>Total de Chamados</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($trends as $trend) {
        $html .= '<tr>
            <td>' . $trend['month'] . '</td>
            <td>' . $trend['tickets'] . '</td>
        </tr>';
    }
    
    $html .= '</tbody></table>';
    return $html;
}

/**
 * Gerar PDF usando mpdf ou convertendo HTML para PDF
 */
function generatePdfWithHtml($html, $dateFrom, $dateTo) {
    // Tenta usar mPDF se disponível
    if (class_exists('\Mpdf\Mpdf')) {
        try {
            $mpdf = new \Mpdf\Mpdf([
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 15,
                'margin_bottom' => 15,
            ]);
            $mpdf->WriteHTML($html);
            $filename = 'dashboard-executivo-' . $dateFrom . '_' . $dateTo . '.pdf';
            $mpdf->Output($filename, 'D');
            return;
        } catch (Exception $e) {
            // Continua com alternativa se mPDF falhar
        }
    }
    
    // Alternativa: Usar TCPDF se disponível
    if (class_exists('\TCPDF')) {
        try {
            $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetAutoPageBreak(TRUE, 15);
            $pdf->AddPage();
            $pdf->SetFont('helvetica', '', 10);
            $pdf->writeHTML($html, true, false, true, false, '');
            $filename = 'dashboard-executivo-' . $dateFrom . '_' . $dateTo . '.pdf';
            $pdf->Output($filename, 'D');
            return;
        } catch (Exception $e) {
            // Continua com alternativa se TCPDF falhar
        }
    }
    
    // Fallback: Gerar HTML que o navegador pode converter para PDF
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: inline; filename="dashboard-executivo-' . $dateFrom . '_' . $dateTo . '.html"');
    echo $html;
}
?>
