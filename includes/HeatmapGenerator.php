<?php
/**
 * Gerador de Heatmap de Demanda
 * Analisa padrões de abertura de chamados por dia da semana e hora
 */

// Incluir configuração do GLPI
if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', dirname(__DIR__, 3));
}
include_once(GLPI_ROOT . "/inc/includes.php");

class HeatmapGenerator {
    private $db;
    private $dateFrom;
    private $dateTo;
    private $entityId;

    public function __construct($dateFrom = null, $dateTo = null, $entityId = null) {
        global $DB;
        $this->db = $DB;

        // Default: último mês
        if (!$dateFrom) {
            $dateFrom = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$dateTo) {
            $dateTo = date('Y-m-d');
        }

        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->entityId = $entityId;
    }

    /**
     * Gerar matriz de heatmap (dia da semana x hora do dia)
     */
    public function generateHeatmap() {
        $sql = "SELECT
                    DAYOFWEEK(date) as day_of_week,
                    HOUR(date) as hour_of_day,
                    COUNT(*) as ticket_count
                FROM glpi_tickets
                WHERE date >= :date_from
                  AND date <= :date_to";

        if ($this->entityId) {
            $sql .= " AND entities_id = :entity_id";
        }

        $sql .= " GROUP BY DAYOFWEEK(date), HOUR(date)
                  ORDER BY day_of_week, hour_of_day";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':date_from', $this->dateFrom);
        $stmt->bindValue(':date_to', $this->dateTo);

        if ($this->entityId) {
            $stmt->bindValue(':entity_id', $this->entityId, PDO::PARAM_INT);
        }

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Criar matriz 7x24 (7 dias x 24 horas)
        $heatmap = [];
        for ($day = 1; $day <= 7; $day++) {
            for ($hour = 0; $hour < 24; $hour++) {
                $heatmap[$day][$hour] = 0;
            }
        }

        // Preencher com dados reais
        foreach ($results as $row) {
            $day = (int)$row['day_of_week']; // 1 = Domingo, 7 = Sábado
            $hour = (int)$row['hour_of_day'];
            $count = (int)$row['ticket_count'];

            $heatmap[$day][$hour] = $count;
        }

        return $heatmap;
    }

    /**
     * Obter estatísticas do heatmap
     */
    public function getHeatmapStats() {
        $heatmap = $this->generateHeatmap();

        $allValues = [];
        foreach ($heatmap as $day => $hours) {
            foreach ($hours as $hour => $count) {
                if ($count > 0) {
                    $allValues[] = $count;
                }
            }
        }

        if (empty($allValues)) {
            return [
                'max' => 0,
                'min' => 0,
                'avg' => 0,
                'total' => 0
            ];
        }

        return [
            'max' => max($allValues),
            'min' => min(array_filter($allValues)), // Ignorar zeros
            'avg' => array_sum($allValues) / count($allValues),
            'total' => array_sum($allValues)
        ];
    }

    /**
     * Identificar horários de pico
     */
    public function getPeakHours($topN = 5) {
        $heatmap = $this->generateHeatmap();
        $peaks = [];

        foreach ($heatmap as $day => $hours) {
            foreach ($hours as $hour => $count) {
                if ($count > 0) {
                    $peaks[] = [
                        'day' => $day,
                        'day_name' => $this->getDayName($day),
                        'hour' => $hour,
                        'hour_label' => sprintf('%02d:00', $hour),
                        'count' => $count
                    ];
                }
            }
        }

        // Ordenar por contagem decrescente
        usort($peaks, fn($a, $b) => $b['count'] - $a['count']);

        return array_slice($peaks, 0, $topN);
    }

    /**
     * Identificar horários de menor demanda
     */
    public function getQuietHours($topN = 5) {
        $heatmap = $this->generateHeatmap();
        $quiet = [];

        foreach ($heatmap as $day => $hours) {
            foreach ($hours as $hour => $count) {
                // Considerar apenas horário comercial (8h-18h) e dias úteis (2-6)
                if ($day >= 2 && $day <= 6 && $hour >= 8 && $hour <= 18) {
                    $quiet[] = [
                        'day' => $day,
                        'day_name' => $this->getDayName($day),
                        'hour' => $hour,
                        'hour_label' => sprintf('%02d:00', $hour),
                        'count' => $count
                    ];
                }
            }
        }

        // Ordenar por contagem crescente
        usort($quiet, fn($a, $b) => $a['count'] - $b['count']);

        return array_slice($quiet, 0, $topN);
    }

    /**
     * Análise por período do dia
     */
    public function getAnalysisByPeriod() {
        $heatmap = $this->generateHeatmap();

        $periods = [
            'madrugada' => ['start' => 0, 'end' => 5, 'count' => 0],   // 00h-05h
            'manha' => ['start' => 6, 'end' => 11, 'count' => 0],      // 06h-11h
            'tarde' => ['start' => 12, 'end' => 17, 'count' => 0],     // 12h-17h
            'noite' => ['start' => 18, 'end' => 23, 'count' => 0]      // 18h-23h
        ];

        foreach ($heatmap as $day => $hours) {
            // Apenas dias úteis
            if ($day >= 2 && $day <= 6) {
                foreach ($hours as $hour => $count) {
                    foreach ($periods as $key => &$period) {
                        if ($hour >= $period['start'] && $hour <= $period['end']) {
                            $period['count'] += $count;
                        }
                    }
                }
            }
        }

        return $periods;
    }

    /**
     * Análise por dia da semana
     */
    public function getAnalysisByWeekday() {
        $heatmap = $this->generateHeatmap();
        $weekdays = [];

        foreach ($heatmap as $day => $hours) {
            $total = array_sum($hours);
            $weekdays[] = [
                'day' => $day,
                'day_name' => $this->getDayName($day),
                'total' => $total,
                'avg_per_hour' => $total / 24,
                'percentage' => 0 // Será calculado depois
            ];
        }

        // Calcular percentuais
        $grandTotal = array_sum(array_column($weekdays, 'total'));
        if ($grandTotal > 0) {
            foreach ($weekdays as &$wd) {
                $wd['percentage'] = ($wd['total'] / $grandTotal) * 100;
            }
        }

        // Ordenar por dia da semana (segunda = primeiro)
        usort($weekdays, function($a, $b) {
            $order = [2, 3, 4, 5, 6, 7, 1]; // Seg-Dom
            $posA = array_search($a['day'], $order);
            $posB = array_search($b['day'], $order);
            return $posA - $posB;
        });

        return $weekdays;
    }

    /**
     * Recomendações baseadas no heatmap
     */
    public function getRecommendations() {
        $peaks = $this->getPeakHours(3);
        $quiet = $this->getQuietHours(3);
        $periods = $this->getAnalysisByPeriod();

        $recommendations = [];

        // Recomendação de escalação
        if (!empty($peaks)) {
            $peakHour = $peaks[0];
            $recommendations[] = [
                'type' => 'staffing',
                'priority' => 'high',
                'title' => 'Reforço de Equipe',
                'description' => "Considere aumentar a equipe às {$peakHour['hour_label']} de {$peakHour['day_name']}, onde há pico de {$peakHour['count']} chamados."
            ];
        }

        // Recomendação de manutenções
        if (!empty($quiet)) {
            $quietHour = $quiet[0];
            $recommendations[] = [
                'type' => 'maintenance',
                'priority' => 'medium',
                'title' => 'Janela de Manutenção',
                'description' => "O melhor horário para manutenções é {$quietHour['hour_label']} de {$quietHour['day_name']} ({$quietHour['count']} chamados)."
            ];
        }

        // Análise de períodos
        arsort($periods);
        $busiestPeriod = array_key_first($periods);
        $recommendations[] = [
            'type' => 'planning',
            'priority' => 'info',
            'title' => 'Período Crítico',
            'description' => "O período de maior demanda é à {$busiestPeriod} com {$periods[$busiestPeriod]['count']} chamados."
        ];

        return $recommendations;
    }

    /**
     * Obter nome do dia da semana
     */
    private function getDayName($day) {
        $days = [
            1 => 'Domingo',
            2 => 'Segunda-feira',
            3 => 'Terça-feira',
            4 => 'Quarta-feira',
            5 => 'Quinta-feira',
            6 => 'Sexta-feira',
            7 => 'Sábado'
        ];

        return $days[$day] ?? '';
    }

    /**
     * Formatar dados para visualização
     */
    public function getFormattedData() {
        $heatmap = $this->generateHeatmap();
        $stats = $this->getHeatmapStats();

        $formatted = [];

        foreach ($heatmap as $day => $hours) {
            $dayData = [
                'day' => $day,
                'day_name' => $this->getDayName($day),
                'hours' => []
            ];

            foreach ($hours as $hour => $count) {
                // Calcular intensidade relativa (0-1)
                $intensity = $stats['max'] > 0 ? $count / $stats['max'] : 0;

                $dayData['hours'][] = [
                    'hour' => $hour,
                    'hour_label' => sprintf('%02d:00', $hour),
                    'count' => $count,
                    'intensity' => $intensity,
                    'level' => $this->getIntensityLevel($intensity)
                ];
            }

            $formatted[] = $dayData;
        }

        return $formatted;
    }

    /**
     * Classificar intensidade em níveis
     */
    private function getIntensityLevel($intensity) {
        if ($intensity >= 0.8) return 'very-high';
        if ($intensity >= 0.6) return 'high';
        if ($intensity >= 0.4) return 'medium';
        if ($intensity >= 0.2) return 'low';
        return 'very-low';
    }
}
