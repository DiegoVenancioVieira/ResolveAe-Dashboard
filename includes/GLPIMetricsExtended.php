<?php
/**
 * Classe estendida para coletar métricas do GLPI com filtros de data
 * Extende GLPIMetrics com métodos que aceitam parâmetros de período
 */

require_once __DIR__ . '/GLPIMetrics.php';

class GLPIMetricsExtended extends GLPIMetrics {
    protected $db;

    /**
     * Construtor - chama o construtor do pai e inicializa $db
     */
    public function __construct() {
        parent::__construct();
        $this->db = Database::getInstance();
    }

    /**
     * Obtém o total de chamados por status com filtro de data
     */
    public function getTicketsByStatusWithDateRange($dateFrom = null, $dateTo = null) {
        $whereDate = $this->buildDateFilter('t.date_creation', $dateFrom, $dateTo);

        $sql = "
            SELECT
                COUNT(*) as total_criados,
                SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as novos,
                SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as atribuidos,
                SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as planejados,
                SUM(CASE WHEN status = 4 THEN 1 ELSE 0 END) as pendentes,
                SUM(CASE WHEN status = 5 THEN 1 ELSE 0 END) as resolvidos,
                SUM(CASE WHEN status = 6 THEN 1 ELSE 0 END) as fechados,
                SUM(CASE WHEN status IN (1,2,3) THEN 1 ELSE 0 END) as total_abertos
            FROM glpi_tickets t
            WHERE t.is_deleted = 0
                $whereDate
        ";

        $result = $this->db->query($sql);
        return $result ? $result->fetch() : $this->getEmptyStats();
    }

    /**
     * Obtém chamados por prioridade com filtro de data
     */
    public function getTicketsByPriorityWithDateRange($dateFrom = null, $dateTo = null) {
        $whereDate = $this->buildDateFilter('t.date_creation', $dateFrom, $dateTo);

        $sql = "
            SELECT
                priority,
                COUNT(*) as total,
                CASE
                    WHEN priority = 1 THEN 'Muito Baixa'
                    WHEN priority = 2 THEN 'Baixa'
                    WHEN priority = 3 THEN 'Média'
                    WHEN priority = 4 THEN 'Alta'
                    WHEN priority = 5 THEN 'Muito Alta'
                    WHEN priority = 6 THEN 'Crítica'
                    ELSE 'Não definida'
                END as priority_name
            FROM glpi_tickets t
            WHERE t.is_deleted = 0
                $whereDate
            GROUP BY priority
            ORDER BY priority DESC
        ";

        $result = $this->db->query($sql);
        return $result ? $result->fetchAll() : [];
    }

    /**
     * Obtém chamados por categoria com filtro de data
     */
    public function getTicketsByCategoryWithDateRange($dateFrom = null, $dateTo = null) {
        $whereDate = $this->buildDateFilter('t.date_creation', $dateFrom, $dateTo);

        $sql = "
            SELECT
                COALESCE(ic.name, 'Sem Categoria') as categoria,
                COUNT(t.id) as total
            FROM glpi_tickets t
            LEFT JOIN glpi_itilcategories ic ON t.itilcategories_id = ic.id
            WHERE t.is_deleted = 0
                $whereDate
            GROUP BY COALESCE(ic.name, 'Sem Categoria')
            ORDER BY total DESC
            LIMIT 10
        ";

        $result = $this->db->query($sql);
        return $result ? $result->fetchAll() : [];
    }

    /**
     * Obtém chamados por entidade com filtro de data
     */
    public function getTicketsByEntityWithDateRange($dateFrom = null, $dateTo = null, $entityId = null) {
        $whereDate = $this->buildDateFilter('t.date_creation', $dateFrom, $dateTo);
        $whereEntity = $entityId ? "AND t.entities_id = " . intval($entityId) : "";

        $sql = "
            SELECT
                COALESCE(e.name, 'Sem Entidade') as entidade,
                COUNT(t.id) as total
            FROM glpi_tickets t
            LEFT JOIN glpi_entities e ON t.entities_id = e.id
            WHERE t.is_deleted = 0
                $whereDate
                $whereEntity
            GROUP BY COALESCE(e.name, 'Sem Entidade')
            ORDER BY total DESC
            LIMIT 10
        ";

        $result = $this->db->query($sql);
        return $result ? $result->fetchAll() : [];
    }

    /**
     * Obtém ranking mensal de técnicos com filtro de data
     */
    public function getTechnicianRankingWithDateRange($dateFrom = null, $dateTo = null, $technicianId = null) {
        $whereDate = $this->buildDateFilter('t.date_creation', $dateFrom, $dateTo);
        $whereTechnician = $technicianId ? "AND u.id = " . intval($technicianId) : "";

        $sql = "
            SELECT
                CONCAT(u.firstname, ' ', u.realname) as tecnico,
                COUNT(DISTINCT t.id) as total_chamados,
                SUM(CASE WHEN t.status IN (5,6) THEN 1 ELSE 0 END) as fechados,
                SUM(CASE WHEN t.status IN (1,2,3,4) THEN 1 ELSE 0 END) as abertos,
                ROUND((SUM(CASE WHEN t.status IN (5,6) THEN 1 ELSE 0 END) / COUNT(DISTINCT t.id)) * 100, 1) as taxa_resolucao
            FROM glpi_tickets t
            INNER JOIN glpi_tickets_users tu ON t.id = tu.tickets_id
            INNER JOIN glpi_users u ON tu.users_id = u.id
            WHERE tu.type = 2
                AND t.is_deleted = 0
                $whereDate
                $whereTechnician
            GROUP BY u.id, u.firstname, u.realname
            HAVING total_chamados > 0
            ORDER BY fechados DESC, total_chamados DESC
            LIMIT 15
        ";

        $result = $this->db->query($sql);
        return $result ? $result->fetchAll() : [];
    }

    /**
     * Obtém tempo médio de resolução com filtro de data
     */
    public function getAverageResolutionTimeWithDateRange($dateFrom = null, $dateTo = null) {
        $whereDate = $this->buildDateFilter('t.solvedate', $dateFrom, $dateTo);

        $sql = "
            SELECT
                AVG(TIMESTAMPDIFF(HOUR, date_creation, solvedate)) as media_horas,
                MIN(TIMESTAMPDIFF(HOUR, date_creation, solvedate)) as min_horas,
                MAX(TIMESTAMPDIFF(HOUR, date_creation, solvedate)) as max_horas,
                COUNT(*) as total_resolvidos
            FROM glpi_tickets t
            WHERE status IN (5,6)
                AND t.is_deleted = 0
                AND solvedate IS NOT NULL
                $whereDate
        ";

        $result = $this->db->query($sql);
        $data = $result ? $result->fetch() : null;

        if ($data && $data['media_horas'] !== null) {
            $data['media_formatada'] = $this->formatHours($data['media_horas']);
            return $data;
        }

        return ['media_horas' => 0, 'media_formatada' => '0h', 'total_resolvidos' => 0];
    }

    /**
     * Obtém chamados atrasados com filtro de data
     */
    public function getOverdueTicketsWithDateRange($dateFrom = null, $dateTo = null) {
        $whereDate = $this->buildDateFilter('t.date_creation', $dateFrom, $dateTo);

        $sql = "
            SELECT
                COUNT(*) as total_vencidos,
                GROUP_CONCAT(
                    CONCAT('#', id, ' - ', SUBSTRING(name, 1, 50))
                    ORDER BY date_creation ASC
                    SEPARATOR '|||'
                ) as lista_vencidos
            FROM glpi_tickets t
            WHERE status IN (1,2,3)
                AND t.is_deleted = 0
                AND time_to_resolve IS NOT NULL
                AND time_to_resolve < NOW()
                $whereDate
        ";

        $result = $this->db->query($sql);
        $data = $result ? $result->fetch() : ['total_vencidos' => 0, 'lista_vencidos' => ''];

        if ($data['lista_vencidos']) {
            $data['lista_array'] = array_slice(explode('|||', $data['lista_vencidos']), 0, 10);
        } else {
            $data['lista_array'] = [];
        }

        return $data;
    }

    /**
     * Obtém estatísticas de satisfação com filtro de data
     */
    public function getSatisfactionStatsWithDateRange($dateFrom = null, $dateTo = null) {
        $whereDate = $this->buildDateFilter('date_answered', $dateFrom, $dateTo);

        $sql = "
            SELECT
                AVG(satisfaction) as media_satisfacao,
                COUNT(*) as total_avaliacoes
            FROM glpi_ticketsatisfactions
            WHERE satisfaction IS NOT NULL
                $whereDate
        ";

        $result = $this->db->query($sql);
        $data = $result ? $result->fetch() : ['media_satisfacao' => null, 'total_avaliacoes' => 0];

        if ($data['media_satisfacao'] !== null) {
            $data['percentual'] = round(($data['media_satisfacao'] / 5) * 100, 1);
            $data['estrelas'] = round($data['media_satisfacao'], 1);
        } else {
            $data['percentual'] = 0;
            $data['estrelas'] = 0;
        }

        return $data;
    }

    /**
     * Obtém chamados por mês dentro do período
     */
    public function getTicketsByMonthInRange($dateFrom = null, $dateTo = null) {
        $whereDate = $this->buildDateFilter('date_creation', $dateFrom, $dateTo);

        $sql = "
            SELECT
                mes,
                mes_formatado,
                total
            FROM (
                SELECT
                    DATE_FORMAT(date_creation, '%Y-%m') as mes,
                    DATE_FORMAT(date_creation, '%m/%Y') as mes_formatado,
                    COUNT(id) as total
                FROM glpi_tickets
                WHERE is_deleted = 0
                    $whereDate
                GROUP BY YEAR(date_creation), MONTH(date_creation)
            ) sub
            ORDER BY mes ASC
        ";

        $result = $this->db->query($sql);
        return $result ? $result->fetchAll() : [];
    }

    /**
     * Obtém lista de entidades para filtro
     */
    public function getEntitiesList() {
        $sql = "
            SELECT DISTINCT
                e.id,
                e.name
            FROM glpi_entities e
            INNER JOIN glpi_tickets t ON t.entities_id = e.id
            WHERE t.is_deleted = 0
            ORDER BY e.name ASC
        ";

        $result = $this->db->query($sql);
        return $result ? $result->fetchAll() : [];
    }

    /**
     * Obtém lista de técnicos para filtro
     */
    public function getTechniciansList() {
        $sql = "
            SELECT DISTINCT
                u.id,
                CONCAT(u.firstname, ' ', u.realname) as nome
            FROM glpi_users u
            INNER JOIN glpi_tickets_users tu ON tu.users_id = u.id
            WHERE tu.type = 2
            ORDER BY nome ASC
        ";

        $result = $this->db->query($sql);
        return $result ? $result->fetchAll() : [];
    }

    /**
     * Constrói filtro SQL de data
     */
    private function buildDateFilter($field, $dateFrom, $dateTo) {
        $filter = "";

        if ($dateFrom && $this->validateDate($dateFrom)) {
            $escapedFrom = htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8');
            $filter .= " AND $field >= '$escapedFrom 00:00:00'";
        }

        if ($dateTo && $this->validateDate($dateTo)) {
            $escapedTo = htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8');
            $filter .= " AND $field <= '$escapedTo 23:59:59'";
        }

        return $filter;
    }

    /**
     * Valida formato de data YYYY-MM-DD
     */
    private function validateDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Formata horas em formato legível (sobrescrito para acesso público)
     */
    public function formatHours($hours) {
        if ($hours < 1) {
            return round($hours * 60) . 'min';
        } elseif ($hours < 24) {
            return round($hours, 1) . 'h';
        } else {
            $days = floor($hours / 24);
            $remaining_hours = round($hours % 24);
            return $days . 'd ' . $remaining_hours . 'h';
        }
    }

    /**
     * Retorna estatísticas vazias para fallback
     */
    private function getEmptyStats() {
        return [
            'total_criados' => 0,
            'novos' => 0,
            'atribuidos' => 0,
            'planejados' => 0,
            'pendentes' => 0,
            'resolvidos' => 0,
            'fechados' => 0,
            'total_abertos' => 0
        ];
    }
}
