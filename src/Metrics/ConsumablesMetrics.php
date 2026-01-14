<?php
/**
 * ConsumablesMetrics - Métricas de Insumos e Consumíveis
 *
 * Classe responsável por calcular métricas relacionadas a estoque,
 * consumo e alertas de consumíveis/insumos do GLPI
 */

namespace Dashboard\Metrics;

require_once __DIR__ . '/../Database.php';

use PDO;

class ConsumablesMetrics
{
    private $db;

    public function __construct()
    {
        // Database não usa namespace, então usamos \ para referência global
        $this->db = \Database::getInstance()->getConnection();
    }

    /**
     * Retorna resumo geral do estoque de consumíveis
     *
     * @return array Total de itens, unidades, alertas e itens em falta
     */
    public function getStockSummary()
    {
        $query = "
            SELECT
                COUNT(DISTINCT ci.id) as total_items,
                COALESCE(SUM(stock.total), 0) as total_units,
                COALESCE(SUM(stock.available), 0) as available_units,
                COALESCE(SUM(stock.used), 0) as used_units,
                SUM(CASE WHEN COALESCE(stock.available, 0) <= ci.alarm_threshold THEN 1 ELSE 0 END) as low_stock_count,
                SUM(CASE WHEN COALESCE(stock.available, 0) = 0 THEN 1 ELSE 0 END) as out_of_stock_count
            FROM glpi_consumableitems ci
            LEFT JOIN (
                SELECT
                    consumableitems_id,
                    COUNT(id) as total,
                    SUM(CASE WHEN date_out IS NULL THEN 1 ELSE 0 END) as available,
                    SUM(CASE WHEN date_out IS NOT NULL THEN 1 ELSE 0 END) as used
                FROM glpi_consumables
                GROUP BY consumableitems_id
            ) stock ON ci.id = stock.consumableitems_id
            WHERE ci.is_deleted = 0
        ";

        $stmt = $this->db->query($query);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_items' => (int)$result['total_items'],
            'total_units' => (int)$result['total_units'],
            'available_units' => (int)$result['available_units'],
            'used_units' => (int)$result['used_units'],
            'low_stock_count' => (int)$result['low_stock_count'],
            'out_of_stock_count' => (int)$result['out_of_stock_count']
        ];
    }

    /**
     * Retorna lista de itens com estoque baixo (abaixo do limite de alerta)
     *
     * @param int $limit Número máximo de itens a retornar
     * @return array Lista de itens com estoque baixo
     */
    public function getLowStockAlerts($limit = 15)
    {
        $query = "
            SELECT
                ci.id,
                ci.name,
                ci.ref,
                ci.alarm_threshold,
                COALESCE(stock.available, 0) as available,
                COALESCE(stock.total, 0) as total_stock,
                cit.name as category,
                e.name as entity
            FROM glpi_consumableitems ci
            LEFT JOIN (
                SELECT
                    consumableitems_id,
                    COUNT(id) as total,
                    SUM(CASE WHEN date_out IS NULL THEN 1 ELSE 0 END) as available
                FROM glpi_consumables
                GROUP BY consumableitems_id
            ) stock ON ci.id = stock.consumableitems_id
            LEFT JOIN glpi_consumableitemtypes cit ON ci.consumableitemtypes_id = cit.id
            LEFT JOIN glpi_entities e ON ci.entities_id = e.id
            WHERE ci.is_deleted = 0
                AND COALESCE(stock.available, 0) <= ci.alarm_threshold * 1.5
            ORDER BY (COALESCE(stock.available, 0) - ci.alarm_threshold) ASC
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $alerts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $available = (int)$row['available'];
            $threshold = (int)$row['alarm_threshold'];

            // Determinar nível de criticidade
            if ($available == 0) {
                $status = 'critical';
                $status_label = 'Em Falta';
            } elseif ($threshold > 0 && $available <= $threshold * 0.5) {
                // Stock is at 50% or below threshold - VERY critical
                $status = 'critical';
                $status_label = 'Crítico';
            } elseif ($threshold > 0 && $available <= $threshold) {
                // Stock is between 50% and 100% of threshold - Warning
                $status = 'warning';
                $status_label = 'Atenção';
            } elseif ($threshold > 0 && $available <= $threshold * 1.5) {
                // Stock is between 100% and 150% of threshold - Info
                $status = 'info';
                $status_label = 'Baixo';
            } else {
                // Should not appear in this query, but just in case
                $status = 'ok';
                $status_label = 'Normal';
            }

            $alerts[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'ref' => $row['ref'],
                'available' => $available,
                'threshold' => $threshold,
                'total_stock' => (int)$row['total_stock'],
                'category' => $row['category'] ?? 'Sem Categoria',
                'entity' => $row['entity'] ?? 'Não definida',
                'status' => $status,
                'status_label' => $status_label
            ];
        }

        return $alerts;
    }

    /**
     * Retorna os itens mais consumidos em um período
     *
     * @param int $days Número de dias para análise (padrão: 30)
     * @param int $limit Número máximo de itens a retornar
     * @return array Top itens mais consumidos
     */
    public function getMostConsumedItems($days = 30, $limit = 10)
    {
        $query = "
            SELECT
                ci.name,
                ci.ref,
                COUNT(c.id) as consumed_count,
                cit.name as category,
                e.name as entity
            FROM glpi_consumables c
            INNER JOIN glpi_consumableitems ci ON c.consumableitems_id = ci.id
            LEFT JOIN glpi_consumableitemtypes cit ON ci.consumableitemtypes_id = cit.id
            LEFT JOIN glpi_entities e ON ci.entities_id = e.id
            WHERE c.date_out IS NOT NULL
                AND c.date_out >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                AND ci.is_deleted = 0
            GROUP BY ci.id
            ORDER BY consumed_count DESC
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = [
                'name' => $row['name'],
                'ref' => $row['ref'],
                'consumed_count' => (int)$row['consumed_count'],
                'category' => $row['category'] ?? 'Sem Categoria',
                'entity' => $row['entity'] ?? 'Não definida'
            ];
        }

        return $items;
    }

    /**
     * Retorna distribuição de estoque por categoria
     *
     * @return array Estoque disponível por categoria
     */
    public function getStockByCategory()
    {
        $query = "
            SELECT
                COALESCE(cit.name, 'Sem Categoria') as category,
                COUNT(DISTINCT ci.id) as item_count,
                COALESCE(SUM(stock.available), 0) as available,
                COALESCE(SUM(stock.total), 0) as total
            FROM glpi_consumableitems ci
            LEFT JOIN glpi_consumableitemtypes cit ON ci.consumableitemtypes_id = cit.id
            LEFT JOIN (
                SELECT
                    consumableitems_id,
                    COUNT(id) as total,
                    SUM(CASE WHEN date_out IS NULL THEN 1 ELSE 0 END) as available
                FROM glpi_consumables
                GROUP BY consumableitems_id
            ) stock ON ci.id = stock.consumableitems_id
            WHERE ci.is_deleted = 0
            GROUP BY cit.id, cit.name
            HAVING available > 0 OR total > 0
            ORDER BY available DESC
        ";

        $stmt = $this->db->query($query);

        $categories = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $categories[] = [
                'category' => $row['category'],
                'item_count' => (int)$row['item_count'],
                'available' => (int)$row['available'],
                'total' => (int)$row['total']
            ];
        }

        return $categories;
    }

    /**
     * Retorna atividade recente de consumo
     *
     * @param int $limit Número máximo de registros
     * @return array Lista de distribuições recentes
     */
    public function getRecentUsage($limit = 15)
    {
        $query = "
            SELECT
                ci.name as item_name,
                ci.ref,
                c.date_out,
                CASE
                    WHEN c.itemtype = 'User' THEN CONCAT(u.firstname, ' ', u.realname)
                    ELSE c.itemtype
                END as recipient,
                e.name as entity,
                cit.name as category
            FROM glpi_consumables c
            INNER JOIN glpi_consumableitems ci ON c.consumableitems_id = ci.id
            LEFT JOIN glpi_users u ON c.items_id = u.id AND c.itemtype = 'User'
            LEFT JOIN glpi_entities e ON c.entities_id = e.id
            LEFT JOIN glpi_consumableitemtypes cit ON ci.consumableitemtypes_id = cit.id
            WHERE c.date_out IS NOT NULL
                AND ci.is_deleted = 0
            ORDER BY c.date_out DESC
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $usage = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $usage[] = [
                'item_name' => $row['item_name'],
                'ref' => $row['ref'],
                'date_out' => $row['date_out'],
                'recipient' => $row['recipient'] ?? 'Não especificado',
                'entity' => $row['entity'] ?? 'Não definida',
                'category' => $row['category'] ?? 'Sem Categoria'
            ];
        }

        return $usage;
    }

    /**
     * Retorna tendências de consumo mensal
     *
     * @param int $months Número de meses para análise
     * @return array Consumo por mês
     */
    public function getConsumptionTrends($months = 6)
    {
        $query = "
            SELECT
                DATE_FORMAT(c.date_out, '%Y-%m') as month,
                DATE_FORMAT(c.date_out, '%b/%Y') as month_label,
                COUNT(c.id) as consumed_count
            FROM glpi_consumables c
            INNER JOIN glpi_consumableitems ci ON c.consumableitems_id = ci.id
            WHERE c.date_out IS NOT NULL
                AND c.date_out >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                AND ci.is_deleted = 0
            GROUP BY DATE_FORMAT(c.date_out, '%Y-%m'), DATE_FORMAT(c.date_out, '%b/%Y')
            ORDER BY month ASC
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':months', $months, PDO::PARAM_INT);
        $stmt->execute();

        $trends = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $trends[] = [
                'month' => $row['month'],
                'month_label' => $row['month_label'],
                'consumed_count' => (int)$row['consumed_count']
            ];
        }

        return $trends;
    }

    /**
     * Consolida todas as métricas de consumíveis
     *
     * @return array Todas as métricas consolidadas
     */
    public function getAllConsumablesMetrics()
    {
        return [
            'summary' => $this->getStockSummary(),
            'low_stock_alerts' => $this->getLowStockAlerts(15),
            'most_consumed' => $this->getMostConsumedItems(30, 10),
            'stock_by_category' => $this->getStockByCategory(),
            'recent_usage' => $this->getRecentUsage(10),
            'consumption_trends' => $this->getConsumptionTrends(6)
        ];
    }
}
