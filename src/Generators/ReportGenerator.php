<?php
/**
 * Classe geradora de relatórios
 * Coordena a coleta de dados e preparação para exportação
 */

require_once __DIR__ . '/../Metrics/GLPIMetricsExtended.php';

class ReportGenerator {
    private $metrics;
    private $dateFrom;
    private $dateTo;
    private $sections;
    private $entityId;
    private $technicianId;

    public function __construct($dateFrom = null, $dateTo = null, $sections = [], $entityId = null, $technicianId = null) {
        $this->metrics = new GLPIMetricsExtended();
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->sections = empty($sections) ? $this->getAllSections() : $sections;
        $this->entityId = $entityId;
        $this->technicianId = $technicianId;
    }

    /**
     * Retorna todas as seções disponíveis
     */
    public function getAllSections() {
        return [
            'resumo_executivo',
            'chamados_status',
            'chamados_prioridade',
            'chamados_categoria',
            'chamados_setores',
            'tendencia_mensal',
            'indicadores_tecnicos',
            'tempo_resolucao',
            'chamados_atrasados',
            'satisfacao'
        ];
    }

    /**
     * Retorna os nomes amigáveis das seções
     */
    public static function getSectionNames() {
        return [
            'resumo_executivo' => 'Resumo Executivo',
            'chamados_status' => 'Chamados por Status',
            'chamados_prioridade' => 'Chamados por Prioridade',
            'chamados_categoria' => 'Chamados por Categoria',
            'chamados_setores' => 'Chamados por Setor/Entidade',
            'tendencia_mensal' => 'Tendência Mensal',
            'indicadores_tecnicos' => 'Indicadores de Técnicos',
            'tempo_resolucao' => 'Tempo Médio de Resolução',
            'chamados_atrasados' => 'Chamados Atrasados (SLA)',
            'satisfacao' => 'Avaliações de Satisfação'
        ];
    }

    /**
     * Gera relatório completo com todas as seções selecionadas
     */
    public function generateReport() {
        $report = [
            'metadata' => $this->getMetadata(),
            'data' => []
        ];

        foreach ($this->sections as $section) {
            $methodName = 'get' . str_replace('_', '', ucwords($section, '_')) . 'Data';

            if (method_exists($this, $methodName)) {
                $report['data'][$section] = $this->$methodName();
            }
        }

        return $report;
    }

    /**
     * Retorna metadados do relatório
     */
    private function getMetadata() {
        return [
            'titulo' => 'Relatório GLPI - Dashboard ResolveAe',
            'periodo_inicio' => $this->dateFrom ? $this->dateFrom : 'Início',
            'periodo_fim' => $this->dateTo ? $this->dateTo : 'Atual',
            'periodo_formatado' => $this->getFormattedPeriod(),
            'data_geracao' => date('d/m/Y H:i:s'),
            'entidade_filtro' => $this->entityId,
            'tecnico_filtro' => $this->technicianId,
            'secoes_incluidas' => count($this->sections),
            'secoes' => $this->sections
        ];
    }

    /**
     * Retorna período formatado
     */
    private function getFormattedPeriod() {
        $from = $this->dateFrom ? DateTime::createFromFormat('Y-m-d', $this->dateFrom) : null;
        $to = $this->dateTo ? DateTime::createFromFormat('Y-m-d', $this->dateTo) : null;

        if ($from && $to) {
            return $from->format('d/m/Y') . ' a ' . $to->format('d/m/Y');
        } elseif ($from) {
            return 'A partir de ' . $from->format('d/m/Y');
        } elseif ($to) {
            return 'Até ' . $to->format('d/m/Y');
        }

        return 'Todo o período';
    }

    /**
     * Dados do Resumo Executivo
     */
    private function getResumoExecutivoData() {
        $status = $this->metrics->getTicketsByStatusWithDateRange($this->dateFrom, $this->dateTo);
        $resolution = $this->metrics->getAverageResolutionTimeWithDateRange($this->dateFrom, $this->dateTo);
        $satisfaction = $this->metrics->getSatisfactionStatsWithDateRange($this->dateFrom, $this->dateTo);
        $overdue = $this->metrics->getOverdueTicketsWithDateRange($this->dateFrom, $this->dateTo);

        return [
            'total_criados' => $status['total_criados'],
            'total_abertos' => $status['total_abertos'],
            'total_resolvidos' => $status['resolvidos'],
            'total_fechados' => $status['fechados'],
            'tempo_medio_resolucao' => $resolution['media_formatada'],
            'satisfacao_media' => $satisfaction['estrelas'],
            'satisfacao_percentual' => $satisfaction['percentual'] . '%',
            'total_atrasados' => $overdue['total_vencidos']
        ];
    }

    /**
     * Dados de Chamados por Status
     */
    private function getChamadosStatusData() {
        return $this->metrics->getTicketsByStatusWithDateRange($this->dateFrom, $this->dateTo);
    }

    /**
     * Dados de Chamados por Prioridade
     */
    private function getChamadosPrioridadeData() {
        return $this->metrics->getTicketsByPriorityWithDateRange($this->dateFrom, $this->dateTo);
    }

    /**
     * Dados de Chamados por Categoria
     */
    private function getChamadosCategoriaData() {
        return $this->metrics->getTicketsByCategoryWithDateRange($this->dateFrom, $this->dateTo);
    }

    /**
     * Dados de Chamados por Setor
     */
    private function getChamadosSetoresData() {
        return $this->metrics->getTicketsByEntityWithDateRange($this->dateFrom, $this->dateTo, $this->entityId);
    }

    /**
     * Dados de Tendência Mensal
     */
    private function getTendenciaMensalData() {
        return $this->metrics->getTicketsByMonthInRange($this->dateFrom, $this->dateTo);
    }

    /**
     * Dados de Indicadores de Técnicos
     */
    private function getIndicadoresTecnicosData() {
        return $this->metrics->getTechnicianRankingWithDateRange($this->dateFrom, $this->dateTo, $this->technicianId);
    }

    /**
     * Dados de Tempo de Resolução
     */
    private function getTempoResolucaoData() {
        return $this->metrics->getAverageResolutionTimeWithDateRange($this->dateFrom, $this->dateTo);
    }

    /**
     * Dados de Chamados Atrasados
     */
    private function getChamadosAtrasadosData() {
        return $this->metrics->getOverdueTicketsWithDateRange($this->dateFrom, $this->dateTo);
    }

    /**
     * Dados de Satisfação
     */
    private function getSatisfacaoData() {
        return $this->metrics->getSatisfactionStatsWithDateRange($this->dateFrom, $this->dateTo);
    }

    /**
     * Retorna lista de entidades para filtro
     */
    public function getEntitiesList() {
        return $this->metrics->getEntitiesList();
    }

    /**
     * Retorna lista de técnicos para filtro
     */
    public function getTechniciansList() {
        return $this->metrics->getTechniciansList();
    }
}
