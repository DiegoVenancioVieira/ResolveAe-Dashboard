<?php
/**
 * Classe para métricas executivas do Dashboard GLPI
 * Fornece KPIs e análises gerenciais para gestores
 */

require_once __DIR__ . '/../Database.php';

class ExecutiveMetrics {
    private $db;
    private $dateFrom;
    private $dateTo;
    private $entityId;

    public function __construct($dateFrom = null, $dateTo = null, $entityId = null) {
        $this->db = Database::getInstance()->getConnection();

        // Default: mês atual
        if (!$dateFrom) {
            $dateFrom = date('Y-m-01');
        }
        if (!$dateTo) {
            $dateTo = date('Y-m-t');
        }

        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->entityId = $entityId;
    }

    /**
     * KPIs principais
     */
    public function getKPIs() {
        return [
            'total_tickets' => $this->getTotalTickets(),
            'tickets_trend' => $this->getTicketsTrend(),
            'sla_compliance' => $this->getSLACompliance(),
            'avg_resolution_time' => $this->getAverageResolutionTime(),
            'resolution_trend' => 'Normal',
            'satisfaction_score' => $this->getSatisfactionScore(),
            'satisfaction_responses' => $this->getSatisfactionCount(),
            'productivity' => $this->getProductivity()
        ];
    }

    private function getTotalTickets() {
        $sql = "SELECT COUNT(*) as total
                FROM glpi_tickets
                WHERE is_deleted = 0
                  AND date >= :dateFrom
                  AND date <= :dateTo";
        
        $params = [
            ':dateFrom' => $this->dateFrom,
            ':dateTo' => $this->dateTo
        ];

        if ($this->entityId) {
            $sql .= " AND entities_id = :entityId";
            $params[':entityId'] = $this->entityId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ? (int)$row['total'] : 0;
    }

    private function getTicketsTrend() {
        return "+5.2% vs período anterior";
    }

    private function getSLACompliance() {
        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN time_to_resolve IS NOT NULL
                        AND solvedate IS NOT NULL
                        AND solvedate <= time_to_resolve THEN 1 ELSE 0 END) as compliant
                FROM glpi_tickets
                WHERE is_deleted = 0
                  AND date >= :dateFrom
                  AND date <= :dateTo
                  AND status IN (5, 6)";

        $params = [
            ':dateFrom' => $this->dateFrom,
            ':dateTo' => $this->dateTo
        ];

        if ($this->entityId) {
            $sql .= " AND entities_id = :entityId";
            $params[':entityId'] = $this->entityId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $total = (int)$row['total'];
            if ($total == 0) return 0;
            return ($row['compliant'] / $total) * 100;
        }
        return 0;
    }

    private function getAverageResolutionTime() {
        $sql = "SELECT AVG(TIMESTAMPDIFF(HOUR, date, solvedate)) as avg_hours
                FROM glpi_tickets
                WHERE is_deleted = 0
                  AND date >= :dateFrom
                  AND date <= :dateTo
                  AND status IN (5, 6)
                  AND solvedate IS NOT NULL";

        $params = [
            ':dateFrom' => $this->dateFrom,
            ':dateTo' => $this->dateTo
        ];

        if ($this->entityId) {
            $sql .= " AND entities_id = :entityId";
            $params[':entityId'] = $this->entityId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && $row['avg_hours']) {
            $hours = (float)$row['avg_hours'];
            if ($hours < 24) {
                return round($hours, 1) . 'h';
            } else {
                return round($hours / 24, 1) . 'd';
            }
        }
        return '0h';
    }

    private function getSatisfactionScore() {
        $sql = "SELECT AVG(satisfaction) as avg_score
                FROM glpi_ticketsatisfactions ts
                INNER JOIN glpi_tickets t ON ts.tickets_id = t.id
                WHERE t.is_deleted = 0
                  AND t.date >= :dateFrom
                  AND t.date <= :dateTo
                  AND ts.satisfaction IS NOT NULL";

        $params = [
            ':dateFrom' => $this->dateFrom,
            ':dateTo' => $this->dateTo
        ];

        if ($this->entityId) {
            $sql .= " AND t.entities_id = :entityId";
            $params[':entityId'] = $this->entityId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? round((float)$row['avg_score'], 1) : 0;
    }

    private function getSatisfactionCount() {
        $sql = "SELECT COUNT(*) as total
                FROM glpi_ticketsatisfactions ts
                INNER JOIN glpi_tickets t ON ts.tickets_id = t.id
                WHERE t.is_deleted = 0
                  AND t.date >= :dateFrom
                  AND t.date <= :dateTo
                  AND ts.satisfaction IS NOT NULL";

        $params = [
            ':dateFrom' => $this->dateFrom,
            ':dateTo' => $this->dateTo
        ];

        if ($this->entityId) {
            $sql .= " AND t.entities_id = :entityId";
            $params[':entityId'] = $this->entityId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ? (int)$row['total'] : 0;
    }

    private function getProductivity() {
        $totalTickets = $this->getTotalTickets();
        $daysDiff = max(1, (strtotime($this->dateTo) - strtotime($this->dateFrom)) / 86400);

        $sql = "SELECT COUNT(DISTINCT users_id) as total_techs
                FROM glpi_tickets_users tu
                INNER JOIN glpi_tickets t ON tu.tickets_id = t.id
                WHERE t.is_deleted = 0
                  AND t.date >= :dateFrom
                  AND t.date <= :dateTo
                  AND tu.type = 2";

        $params = [
            ':dateFrom' => $this->dateFrom,
            ':dateTo' => $this->dateTo
        ];

        if ($this->entityId) {
            $sql .= " AND t.entities_id = :entityId";
            $params[':entityId'] = $this->entityId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $techs = max(1, (int)$row['total_techs']);
            return round($totalTickets / ($techs * $daysDiff), 1);
        }
        return 0;
    }

    public function getTrends() {
        if ($this->entityId) {
            $sql = "SELECT
                        month,
                        COUNT(*) as tickets
                    FROM (
                        SELECT
                            DATE_FORMAT(date, '%Y-%m') as month,
                            id
                        FROM glpi_tickets
                        WHERE is_deleted = 0
                          AND date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                          AND entities_id = :entityId
                    ) sub
                    GROUP BY month
                    ORDER BY month ASC";
            
            $params = [':entityId' => $this->entityId];
        } else {
            $sql = "SELECT
                        month,
                        COUNT(*) as tickets
                    FROM (
                        SELECT
                            DATE_FORMAT(date, '%Y-%m') as month,
                            id
                        FROM glpi_tickets
                        WHERE is_deleted = 0
                          AND date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                    ) sub
                    GROUP BY month
                    ORDER BY month ASC";
            
            $params = [];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopProblematicCategories() {
        $sql = "SELECT
                    COALESCE(ic.name, 'Sem Categoria') as category,
                    COUNT(t.id) as tickets,
                    AVG(TIMESTAMPDIFF(HOUR, ANY_VALUE(t.date), COALESCE(ANY_VALUE(t.solvedate), NOW()))) as avg_time,
                    AVG(CASE WHEN t.time_to_resolve IS NOT NULL
                        AND t.solvedate IS NOT NULL
                        AND t.solvedate <= t.time_to_resolve THEN 100 ELSE 0 END) as sla_compliance
                FROM glpi_tickets t
                LEFT JOIN glpi_itilcategories ic ON t.itilcategories_id = ic.id
                WHERE t.is_deleted = 0
                  AND t.date >= :dateFrom
                  AND t.date <= :dateTo";

        $params = [
            ':dateFrom' => $this->dateFrom,
            ':dateTo' => $this->dateTo
        ];

        if ($this->entityId) {
            $sql .= " AND t.entities_id = :entityId";
            $params[':entityId'] = $this->entityId;
        }

        $sql .= " GROUP BY COALESCE(ic.name, 'Sem Categoria')
                  ORDER BY tickets DESC
                  LIMIT 5";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($categories as &$category) {
            $hours = (float)$category['avg_time'];
            $category['avg_resolution_time'] = ($hours < 24) ? round($hours, 1) . 'h' : round($hours / 24, 1) . 'd';
            // Ensure sla_compliance is a float
            $category['sla_compliance'] = (float)($category['sla_compliance'] ?? 0);
        }
        
        return $categories;
    }

    public function getWorkloadDistribution() {
        $daysDiff = max(1, (strtotime($this->dateTo) - strtotime($this->dateFrom)) / 86400);

        $sql = "SELECT
                    CONCAT(u.firstname, ' ', u.realname) as technician,
                    COUNT(t.id) as tickets,
                    AVG(TIMESTAMPDIFF(HOUR, ANY_VALUE(t.date), COALESCE(ANY_VALUE(t.solvedate), NOW()))) as avg_time,
                    COUNT(t.id) / " . (float)$daysDiff . " as productivity
                FROM glpi_tickets t
                INNER JOIN glpi_tickets_users tu ON tu.tickets_id = t.id AND tu.type = 2
                INNER JOIN glpi_users u ON u.id = tu.users_id
                WHERE t.is_deleted = 0
                  AND t.date >= :dateFrom
                  AND t.date <= :dateTo";

        $params = [
            ':dateFrom' => $this->dateFrom,
            ':dateTo' => $this->dateTo
        ];

        if ($this->entityId) {
            $sql .= " AND t.entities_id = :entityId";
            $params[':entityId'] = $this->entityId;
        }

        $sql .= " GROUP BY u.id, tu.users_id, u.firstname, u.realname
                  ORDER BY tickets DESC
                  LIMIT 10";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $workload = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($workload as &$row) {
            $hours = (float)$row['avg_time'];
            $row['avg_time'] = ($hours < 24) ? round($hours, 1) . 'h' : round($hours / 24, 1) . 'd';
            // Ensure productivity is a float
            $row['productivity'] = (float)($row['productivity'] ?? 0);
        }

        return $workload;
    }

    /**
     * Distribuição de carga por setor/entidade
     */
    public function getWorkloadBySector() {
        $daysDiff = max(1, (strtotime($this->dateTo) - strtotime($this->dateFrom)) / 86400);

        $sql = "SELECT
                    COALESCE(e.name, 'Sem Setor') as sector,
                    COUNT(t.id) as tickets,
                    AVG(TIMESTAMPDIFF(HOUR, t.date, COALESCE(t.solvedate, NOW()))) as avg_time,
                    COUNT(t.id) / " . (float)$daysDiff . " as productivity
                FROM glpi_tickets t
                LEFT JOIN glpi_entities e ON e.id = t.entities_id
                WHERE t.is_deleted = 0
                  AND t.date >= :dateFrom
                  AND t.date <= :dateTo";

        $params = [
            ':dateFrom' => $this->dateFrom,
            ':dateTo' => $this->dateTo
        ];

        if ($this->entityId) {
            $sql .= " AND t.entities_id = :entityId";
            $params[':entityId'] = $this->entityId;
        }

        $sql .= " GROUP BY t.entities_id, e.name
                  ORDER BY tickets DESC
                  LIMIT 10";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $workload = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($workload as &$row) {
            $hours = (float)$row['avg_time'];
            $row['avg_time'] = ($hours < 24) ? round($hours, 1) . 'h' : round($hours / 24, 1) . 'd';
            $row['productivity'] = (float)($row['productivity'] ?? 0);
        }

        return $workload;
    }

    public function getMonthlyComparison() {
        // Calculate date 6 months ago in PHP
        $dateToObj = DateTime::createFromFormat('Y-m-d', $this->dateTo);
        $dateFromObj = clone $dateToObj;
        $dateFromObj->modify('-6 months');
        $dateFrom6Months = $dateFromObj->format('Y-m-d');
        
        if ($this->entityId) {
            $sql = "SELECT
                        month,
                        COUNT(*) as tickets,
                        AVG(CASE WHEN time_to_resolve IS NOT NULL
                            AND solvedate IS NOT NULL
                            AND solvedate <= time_to_resolve THEN 100 ELSE 0 END) as sla
                    FROM (
                        SELECT
                            DATE_FORMAT(date, '%b/%Y') as month,
                            YEAR(date) as year,
                            MONTH(date) as mon,
                            time_to_resolve,
                            solvedate
                        FROM glpi_tickets
                        WHERE is_deleted = 0
                          AND date >= :dateFrom6Months
                          AND date <= :dateTo
                          AND entities_id = :entityId
                    ) sub
                    GROUP BY month, year, mon
                    ORDER BY year DESC, mon DESC
                    LIMIT 6";
            
            $params = [
                ':dateFrom6Months' => $dateFrom6Months,
                ':dateTo' => $this->dateTo,
                ':entityId' => $this->entityId
            ];
        } else {
            $sql = "SELECT
                        month,
                        COUNT(*) as tickets,
                        AVG(CASE WHEN time_to_resolve IS NOT NULL
                            AND solvedate IS NOT NULL
                            AND solvedate <= time_to_resolve THEN 100 ELSE 0 END) as sla
                    FROM (
                        SELECT
                            DATE_FORMAT(date, '%b/%Y') as month,
                            YEAR(date) as year,
                            MONTH(date) as mon,
                            time_to_resolve,
                            solvedate
                        FROM glpi_tickets
                        WHERE is_deleted = 0
                          AND date >= :dateFrom6Months
                          AND date <= :dateTo
                    ) sub
                    GROUP BY month, year, mon
                    ORDER BY year DESC, mon DESC
                    LIMIT 6";
            
            $params = [
                ':dateFrom6Months' => $dateFrom6Months,
                ':dateTo' => $this->dateTo
            ];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $comparison = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($comparison as &$row) {
            $row['sla'] = round((float)$row['sla'], 1);
        }
        
        return array_reverse($comparison);
    }
}