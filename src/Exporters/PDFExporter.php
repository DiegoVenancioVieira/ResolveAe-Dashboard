<?php
/**
 * Exportador PDF
 * Gera relatórios PDF formatados usando TCPDF
 */

require_once __DIR__ . '/../../../../vendor/tecnickcom/tcpdf/tcpdf.php';

class PDFExporter extends TCPDF {
    private $report;
    private $reportTitle;
    private $reportPeriod;

    public function __construct($report) {
        parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $this->report = $report;
        $this->reportTitle = $report['metadata']['titulo'];
        $this->reportPeriod = $report['metadata']['periodo_formatado'];

        $this->setupPDF();
    }

    /**
     * Configura o PDF
     */
    private function setupPDF() {
        // Informações do documento
        $this->SetCreator('GLPI Dashboard ResolveAe');
        $this->SetAuthor('Sistema GLPI');
        $this->SetTitle($this->reportTitle);
        $this->SetSubject('Relatório de Chamados');

        // Margens
        $this->SetMargins(15, 40, 15);
        $this->SetHeaderMargin(10);
        $this->SetFooterMargin(15);

        // Auto page break
        $this->SetAutoPageBreak(TRUE, 25);

        // Font
        $this->SetFont('helvetica', '', 10);
    }

    /**
     * Header customizado
     */
    public function Header() {
        // Logo
        $logoPath = __DIR__ . '/../../pics/resolveae.png';
        if (file_exists($logoPath)) {
            $this->Image($logoPath, 15, 10, 40, 0, 'PNG');
        }

        // Título
        $this->SetFont('helvetica', 'B', 16);
        $this->SetY(12);
        $this->Cell(0, 10, 'Relatório GLPI - Dashboard ResolveAe', 0, 1, 'C');

        // Período
        $this->SetFont('helvetica', '', 10);
        $this->Cell(0, 5, 'Período: ' . $this->reportPeriod, 0, 1, 'C');

        // Linha
        $this->SetLineWidth(0.5);
        $this->Line(15, 35, $this->getPageWidth() - 15, 35);

        $this->SetY(40);
    }

    /**
     * Footer customizado
     */
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);

        // Linha
        $this->SetLineWidth(0.3);
        $this->Line(15, $this->getPageHeight() - 18, $this->getPageWidth() - 15, $this->getPageHeight() - 18);

        // Número de página e data
        $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages() . ' - Gerado em ' . $this->report['metadata']['data_geracao'], 0, 0, 'C');
    }

    /**
     * Exporta o relatório PDF
     */
    public function export() {
        // Adiciona primeira página
        $this->AddPage();

        // Índice
        $this->generateIndex();

        // Gera cada seção
        foreach ($this->report['data'] as $section => $data) {
            $methodName = 'generate' . str_replace('_', '', ucwords($section, '_')) . 'Section';

            if (method_exists($this, $methodName)) {
                $this->AddPage();
                $this->$methodName($data);
            }
        }

        // Retorna o PDF como string
        return $this->Output('', 'S');
    }

    /**
     * Gera índice
     */
    private function generateIndex() {
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 10, 'ÍNDICE', 0, 1, 'L');
        $this->Ln(5);

        $this->SetFont('helvetica', '', 11);
        $sectionNames = ReportGenerator::getSectionNames();
        $pageNum = 2;

        foreach ($this->report['metadata']['secoes'] as $section) {
            if (isset($sectionNames[$section])) {
                $this->Cell(150, 8, $sectionNames[$section], 0, 0, 'L');
                $this->Cell(0, 8, $pageNum++, 0, 1, 'R');
            }
        }
    }

    /**
     * Gera seção do Resumo Executivo
     */
    private function generateResumoExecutivoSection($data) {
        $this->sectionTitle('RESUMO EXECUTIVO');

        $html = '<table border="1" cellpadding="6" cellspacing="0" style="width: 100%;">
            <tr style="background-color: #4CAF50; color: white; font-weight: bold;">
                <td width="70%">Métrica</td>
                <td width="30%" align="center">Valor</td>
            </tr>
            <tr>
                <td>Total de Chamados Criados</td>
                <td align="center"><b>' . $data['total_criados'] . '</b></td>
            </tr>
            <tr style="background-color: #f0f0f0;">
                <td>Total de Chamados Abertos</td>
                <td align="center"><b>' . $data['total_abertos'] . '</b></td>
            </tr>
            <tr>
                <td>Total de Chamados Resolvidos</td>
                <td align="center"><b>' . $data['total_resolvidos'] . '</b></td>
            </tr>
            <tr style="background-color: #f0f0f0;">
                <td>Total de Chamados Fechados</td>
                <td align="center"><b>' . $data['total_fechados'] . '</b></td>
            </tr>
            <tr>
                <td>Tempo Médio de Resolução</td>
                <td align="center"><b>' . $data['tempo_medio_resolucao'] . '</b></td>
            </tr>
            <tr style="background-color: #f0f0f0;">
                <td>Satisfação Média</td>
                <td align="center"><b>' . $data['satisfacao_media'] . ' ★ (' . $data['satisfacao_percentual'] . ')</b></td>
            </tr>
            <tr>
                <td>Total de Chamados Atrasados</td>
                <td align="center"><b>' . $data['total_atrasados'] . '</b></td>
            </tr>
        </table>';

        $this->writeHTML($html, true, false, true, false, '');
    }

    /**
     * Gera seção de Chamados por Status
     */
    private function generateChamadosStatusSection($data) {
        $this->sectionTitle('CHAMADOS POR STATUS');

        $html = '<table border="1" cellpadding="6" cellspacing="0" style="width: 100%;">
            <tr style="background-color: #2196F3; color: white; font-weight: bold;">
                <td width="70%">Status</td>
                <td width="30%" align="center">Quantidade</td>
            </tr>
            <tr>
                <td>Total Criados</td>
                <td align="center"><b>' . $data['total_criados'] . '</b></td>
            </tr>
            <tr style="background-color: #fff3cd;">
                <td>Novos</td>
                <td align="center">' . $data['novos'] . '</td>
            </tr>
            <tr>
                <td>Atribuídos</td>
                <td align="center">' . $data['atribuidos'] . '</td>
            </tr>
            <tr style="background-color: #f0f0f0;">
                <td>Planejados</td>
                <td align="center">' . $data['planejados'] . '</td>
            </tr>
            <tr>
                <td>Pendentes</td>
                <td align="center">' . $data['pendentes'] . '</td>
            </tr>
            <tr style="background-color: #d4edda;">
                <td>Resolvidos</td>
                <td align="center">' . $data['resolvidos'] . '</td>
            </tr>
            <tr>
                <td>Fechados</td>
                <td align="center">' . $data['fechados'] . '</td>
            </tr>
            <tr style="background-color: #f8d7da;">
                <td><b>Total Abertos</b></td>
                <td align="center"><b>' . $data['total_abertos'] . '</b></td>
            </tr>
        </table>';

        $this->writeHTML($html, true, false, true, false, '');
    }

    /**
     * Gera seção de Chamados por Prioridade
     */
    private function generateChamadosPrioridadeSection($data) {
        $this->sectionTitle('CHAMADOS POR PRIORIDADE');

        $html = '<table border="1" cellpadding="6" cellspacing="0" style="width: 100%;">
            <tr style="background-color: #FF9800; color: white; font-weight: bold;">
                <td width="70%">Prioridade</td>
                <td width="30%" align="center">Quantidade</td>
            </tr>';

        $colors = ['#f0f0f0', '#ffffff'];
        $i = 0;
        foreach ($data as $row) {
            $bgcolor = $colors[$i++ % 2];
            $html .= '<tr style="background-color: ' . $bgcolor . ';">
                <td>' . htmlspecialchars($row['priority_name'] ?? '') . '</td>
                <td align="center">' . $row['total'] . '</td>
            </tr>';
        }

        $html .= '</table>';
        $this->writeHTML($html, true, false, true, false, '');
    }

    /**
     * Gera seção de Chamados por Categoria
     */
    private function generateChamadosCategoriaSection($data) {
        $this->sectionTitle('CHAMADOS POR CATEGORIA (TOP 10)');

        $html = '<table border="1" cellpadding="6" cellspacing="0" style="width: 100%;">
            <tr style="background-color: #9C27B0; color: white; font-weight: bold;">
                <td width="70%">Categoria</td>
                <td width="30%" align="center">Quantidade</td>
            </tr>';

        $colors = ['#f0f0f0', '#ffffff'];
        $i = 0;
        foreach ($data as $row) {
            $bgcolor = $colors[$i++ % 2];
            $html .= '<tr style="background-color: ' . $bgcolor . ';">
                <td>' . htmlspecialchars($row['categoria'] ?? '') . '</td>
                <td align="center">' . $row['total'] . '</td>
            </tr>';
        }

        $html .= '</table>';
        $this->writeHTML($html, true, false, true, false, '');
    }

    /**
     * Gera seção de Chamados por Setor
     */
    private function generateChamadosSetoresSection($data) {
        $this->sectionTitle('CHAMADOS POR SETOR/ENTIDADE (TOP 10)');

        $html = '<table border="1" cellpadding="6" cellspacing="0" style="width: 100%;">
            <tr style="background-color: #607D8B; color: white; font-weight: bold;">
                <td width="70%">Setor/Entidade</td>
                <td width="30%" align="center">Quantidade</td>
            </tr>';

        $colors = ['#f0f0f0', '#ffffff'];
        $i = 0;
        foreach ($data as $row) {
            $bgcolor = $colors[$i++ % 2];
            $html .= '<tr style="background-color: ' . $bgcolor . ';">
                <td>' . htmlspecialchars($row['entidade'] ?? '') . '</td>
                <td align="center">' . $row['total'] . '</td>
            </tr>';
        }

        $html .= '</table>';
        $this->writeHTML($html, true, false, true, false, '');
    }

    /**
     * Gera seção de Tendência Mensal
     */
    private function generateTendenciaMensalSection($data) {
        $this->sectionTitle('TENDÊNCIA MENSAL DE CHAMADOS');

        $html = '<table border="1" cellpadding="6" cellspacing="0" style="width: 100%;">
            <tr style="background-color: #00BCD4; color: white; font-weight: bold;">
                <td width="70%">Mês/Ano</td>
                <td width="30%" align="center">Quantidade</td>
            </tr>';

        $colors = ['#f0f0f0', '#ffffff'];
        $i = 0;
        foreach ($data as $row) {
            $bgcolor = $colors[$i++ % 2];
            $html .= '<tr style="background-color: ' . $bgcolor . ';">
                <td>' . htmlspecialchars($row['mes_formatado'] ?? '') . '</td>
                <td align="center">' . $row['total'] . '</td>
            </tr>';
        }

        $html .= '</table>';
        $this->writeHTML($html, true, false, true, false, '');
    }

    /**
     * Gera seção de Indicadores de Técnicos
     */
    private function generateIndicadoresTecnicosSection($data) {
        $this->sectionTitle('INDICADORES DE TÉCNICOS');

        $html = '<table border="1" cellpadding="5" cellspacing="0" style="width: 100%; font-size: 9px;">
            <tr style="background-color: #3F51B5; color: white; font-weight: bold;">
                <td width="35%">Técnico</td>
                <td width="15%" align="center">Total</td>
                <td width="15%" align="center">Fechados</td>
                <td width="15%" align="center">Abertos</td>
                <td width="20%" align="center">Taxa Resolução</td>
            </tr>';

        $colors = ['#f0f0f0', '#ffffff'];
        $i = 0;
        foreach ($data as $row) {
            $bgcolor = $colors[$i++ % 2];
            $html .= '<tr style="background-color: ' . $bgcolor . ';">
                <td>' . htmlspecialchars($row['tecnico'] ?? '') . '</td>
                <td align="center">' . $row['total_chamados'] . '</td>
                <td align="center">' . $row['fechados'] . '</td>
                <td align="center">' . $row['abertos'] . '</td>
                <td align="center">' . $row['taxa_resolucao'] . '%</td>
            </tr>';
        }

        $html .= '</table>';
        $this->writeHTML($html, true, false, true, false, '');
    }

    /**
     * Gera seção de Tempo de Resolução
     */
    private function generateTempoResolucaoSection($data) {
        $this->sectionTitle('TEMPO DE RESOLUÇÃO');

        $html = '<table border="1" cellpadding="6" cellspacing="0" style="width: 100%;">
            <tr style="background-color: #009688; color: white; font-weight: bold;">
                <td width="70%">Métrica</td>
                <td width="30%" align="center">Valor</td>
            </tr>
            <tr>
                <td>Tempo Médio de Resolução</td>
                <td align="center"><b>' . $data['media_formatada'] . '</b></td>
            </tr>
            <tr style="background-color: #f0f0f0;">
                <td>Tempo Mínimo (horas)</td>
                <td align="center">' . round($data['min_horas'], 1) . 'h</td>
            </tr>
            <tr>
                <td>Tempo Máximo (horas)</td>
                <td align="center">' . round($data['max_horas'], 1) . 'h</td>
            </tr>
            <tr style="background-color: #f0f0f0;">
                <td>Total de Chamados Resolvidos</td>
                <td align="center"><b>' . $data['total_resolvidos'] . '</b></td>
            </tr>
        </table>';

        $this->writeHTML($html, true, false, true, false, '');
    }

    /**
     * Gera seção de Chamados Atrasados
     */
    private function generateChamadosAtrasadosSection($data) {
        $this->sectionTitle('CHAMADOS ATRASADOS (SLA)');

        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 10, 'Total de Chamados Atrasados: ' . $data['total_vencidos'], 0, 1);
        $this->Ln(3);

        if (!empty($data['lista_array'])) {
            $this->SetFont('helvetica', '', 10);
            $this->Cell(0, 8, 'Lista de Chamados Atrasados:', 0, 1);
            $this->Ln(2);

            foreach ($data['lista_array'] as $chamado) {
                $this->Cell(0, 6, '• ' . $chamado, 0, 1);
            }
        }
    }

    /**
     * Gera seção de Satisfação
     */
    private function generateSatisfacaoSection($data) {
        $this->sectionTitle('AVALIAÇÕES DE SATISFAÇÃO');

        $html = '<table border="1" cellpadding="6" cellspacing="0" style="width: 100%;">
            <tr style="background-color: #FFC107; color: black; font-weight: bold;">
                <td width="70%">Métrica</td>
                <td width="30%" align="center">Valor</td>
            </tr>
            <tr>
                <td>Satisfação Média (Estrelas)</td>
                <td align="center"><b>' . $data['estrelas'] . ' ★</b></td>
            </tr>
            <tr style="background-color: #f0f0f0;">
                <td>Satisfação Média (Percentual)</td>
                <td align="center"><b>' . $data['percentual'] . '%</b></td>
            </tr>
            <tr>
                <td>Total de Avaliações</td>
                <td align="center">' . $data['total_avaliacoes'] . '</td>
            </tr>
        </table>';

        $this->writeHTML($html, true, false, true, false, '');
    }

    /**
     * Helper para título de seção
     */
    private function sectionTitle($title) {
        $this->SetFont('helvetica', 'B', 14);
        $this->SetFillColor(52, 152, 219);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 12, $title, 0, 1, 'L', true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(5);
        $this->SetFont('helvetica', '', 10);
    }
}
