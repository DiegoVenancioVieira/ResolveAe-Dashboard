<?php
/**
 * Sistema de Metas Configuráveis
 * Permite definir e gerenciar metas/targets para KPIs
 */

class GoalsConfig {
    private $db;
    private $configFile;

    // Metas padrão
    private $defaultGoals = [
        'sla_compliance' => [
            'label' => 'SLA Compliance',
            'target' => 80.0,
            'unit' => '%',
            'warning_threshold' => 70.0,
            'critical_threshold' => 60.0,
            'type' => 'percentage',
            'description' => 'Percentual de chamados atendidos dentro do SLA'
        ],
        'avg_resolution_time' => [
            'label' => 'Tempo Médio de Solução',
            'target' => 24,
            'unit' => 'horas',
            'warning_threshold' => 36,
            'critical_threshold' => 48,
            'type' => 'hours',
            'description' => 'Tempo médio para resolver chamados'
        ],
        'satisfaction_score' => [
            'label' => 'Índice de Satisfação',
            'target' => 4.0,
            'unit' => '/5',
            'warning_threshold' => 3.5,
            'critical_threshold' => 3.0,
            'type' => 'rating',
            'description' => 'Avaliação média de satisfação dos usuários'
        ],
        'productivity' => [
            'label' => 'Produtividade',
            'target' => 5.0,
            'unit' => 'chamados/técnico/dia',
            'warning_threshold' => 3.0,
            'critical_threshold' => 2.0,
            'type' => 'number',
            'description' => 'Quantidade média de chamados resolvidos por técnico por dia'
        ],
        'first_response_time' => [
            'label' => 'Tempo de Primeira Resposta',
            'target' => 2,
            'unit' => 'horas',
            'warning_threshold' => 4,
            'critical_threshold' => 8,
            'type' => 'hours',
            'description' => 'Tempo médio até a primeira resposta ao usuário'
        ],
        'reopened_tickets' => [
            'label' => 'Taxa de Reabertura',
            'target' => 5.0,
            'unit' => '%',
            'warning_threshold' => 10.0,
            'critical_threshold' => 15.0,
            'type' => 'percentage',
            'description' => 'Percentual de chamados que foram reabertos'
        ],
        'tickets_closed_monthly' => [
            'label' => 'Meta de Chamados Fechados/Mês',
            'target' => 100,
            'unit' => 'chamados',
            'warning_threshold' => 80,
            'critical_threshold' => 60,
            'type' => 'number',
            'description' => 'Quantidade mínima de chamados a serem fechados por mês'
        ]
    ];

    public function __construct() {
        global $DB;
        $this->db = $DB;
        $this->configFile = __DIR__ . '/../config/goals.json';

        // Criar diretório de config se não existir
        $configDir = dirname($this->configFile);
        if (!is_dir($configDir)) {
            mkdir($configDir, 0755, true);
        }

        // Criar arquivo de configuração com valores padrão se não existir
        if (!file_exists($this->configFile)) {
            $this->saveGoals($this->defaultGoals);
        }
    }

    /**
     * Obter todas as metas configuradas
     */
    public function getGoals() {
        if (file_exists($this->configFile)) {
            $json = file_get_contents($this->configFile);
            $goals = json_decode($json, true);

            if ($goals && is_array($goals)) {
                return $goals;
            }
        }

        return $this->defaultGoals;
    }

    /**
     * Obter uma meta específica
     */
    public function getGoal($key) {
        $goals = $this->getGoals();
        return $goals[$key] ?? null;
    }

    /**
     * Salvar configuração de metas
     */
    public function saveGoals($goals) {
        $json = json_encode($goals, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents($this->configFile, $json) !== false;
    }

    /**
     * Atualizar uma meta específica
     */
    public function updateGoal($key, $goalData) {
        $goals = $this->getGoals();

        if (isset($goals[$key])) {
            $goals[$key] = array_merge($goals[$key], $goalData);
            return $this->saveGoals($goals);
        }

        return false;
    }

    /**
     * Adicionar nova meta
     */
    public function addGoal($key, $goalData) {
        $goals = $this->getGoals();

        // Validar estrutura mínima
        $requiredFields = ['label', 'target', 'unit', 'type'];
        foreach ($requiredFields as $field) {
            if (!isset($goalData[$field])) {
                return false;
            }
        }

        $goals[$key] = $goalData;
        return $this->saveGoals($goals);
    }

    /**
     * Remover uma meta
     */
    public function removeGoal($key) {
        $goals = $this->getGoals();

        if (isset($goals[$key])) {
            unset($goals[$key]);
            return $this->saveGoals($goals);
        }

        return false;
    }

    /**
     * Restaurar metas padrão
     */
    public function restoreDefaults() {
        return $this->saveGoals($this->defaultGoals);
    }

    /**
     * Avaliar se um valor atinge a meta
     * Retorna: 'success', 'warning', 'critical'
     */
    public function evaluateGoal($key, $value) {
        $goal = $this->getGoal($key);

        if (!$goal) {
            return 'unknown';
        }

        // Para tipos onde menor é melhor (tempo de resolução, reabertura)
        $lowerIsBetter = in_array($key, ['avg_resolution_time', 'first_response_time', 'reopened_tickets']);

        if ($lowerIsBetter) {
            if ($value <= $goal['target']) {
                return 'success';
            } elseif ($value <= $goal['warning_threshold']) {
                return 'warning';
            } else {
                return 'critical';
            }
        } else {
            // Para tipos onde maior é melhor (SLA, satisfação, produtividade)
            if ($value >= $goal['target']) {
                return 'success';
            } elseif ($value >= $goal['warning_threshold']) {
                return 'warning';
            } else {
                return 'critical';
            }
        }
    }

    /**
     * Obter progresso em relação à meta (0-100%)
     */
    public function getProgress($key, $value) {
        $goal = $this->getGoal($key);

        if (!$goal) {
            return 0;
        }

        $target = $goal['target'];
        $lowerIsBetter = in_array($key, ['avg_resolution_time', 'first_response_time', 'reopened_tickets']);

        if ($lowerIsBetter) {
            // Para metas onde menor é melhor
            if ($value <= $target) {
                return 100;
            }
            $critical = $goal['critical_threshold'];
            $progress = 100 - (($value - $target) / ($critical - $target)) * 100;
            return max(0, min(100, $progress));
        } else {
            // Para metas onde maior é melhor
            if ($value >= $target) {
                return 100;
            }
            $critical = $goal['critical_threshold'];
            $progress = (($value - $critical) / ($target - $critical)) * 100;
            return max(0, min(100, $progress));
        }
    }

    /**
     * Obter resumo de todas as metas com avaliação
     */
    public function getGoalsSummary($metrics) {
        $goals = $this->getGoals();
        $summary = [];

        foreach ($goals as $key => $goal) {
            $value = $metrics[$key] ?? null;

            if ($value !== null) {
                $summary[$key] = [
                    'goal' => $goal,
                    'current_value' => $value,
                    'status' => $this->evaluateGoal($key, $value),
                    'progress' => $this->getProgress($key, $value),
                    'achieved' => $this->evaluateGoal($key, $value) === 'success'
                ];
            }
        }

        return $summary;
    }

    /**
     * Obter estatísticas gerais das metas
     */
    public function getGoalsStatistics($metrics) {
        $summary = $this->getGoalsSummary($metrics);

        $total = count($summary);
        $achieved = count(array_filter($summary, fn($s) => $s['achieved']));
        $warning = count(array_filter($summary, fn($s) => $s['status'] === 'warning'));
        $critical = count(array_filter($summary, fn($s) => $s['status'] === 'critical'));

        return [
            'total_goals' => $total,
            'goals_achieved' => $achieved,
            'goals_warning' => $warning,
            'goals_critical' => $critical,
            'achievement_rate' => $total > 0 ? ($achieved / $total) * 100 : 0,
            'overall_status' => $critical > 0 ? 'critical' : ($warning > 0 ? 'warning' : 'success')
        ];
    }
}
