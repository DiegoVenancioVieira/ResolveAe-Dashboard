<?php
/**
 * Sistema de Configuração de Slides Visíveis
 * Permite selecionar quais slides aparecem na rotação do dashboard
 */

class SlidesConfig {
    private $configFile;

    // Slides disponíveis no dashboard
    private $availableSlides = [
        'ticket_status' => [
            'name' => 'Status dos Chamados',
            'description' => 'Distribuição de chamados por status',
            'icon' => 'fa-chart-pie',
            'color' => '#3498db',
            'default_visible' => true,
            'default_duration' => 10
        ],
        'technician_performance' => [
            'name' => 'Desempenho dos Técnicos',
            'description' => 'Ranking e estatísticas dos técnicos',
            'icon' => 'fa-users',
            'color' => '#2ecc71',
            'default_visible' => true,
            'default_duration' => 15
        ],
        'sla_compliance' => [
            'name' => 'Cumprimento de SLA',
            'description' => 'Análise de cumprimento de SLA',
            'icon' => 'fa-clock',
            'color' => '#e74c3c',
            'default_visible' => true,
            'default_duration' => 10
        ],
        'category_distribution' => [
            'name' => 'Distribuição por Categoria',
            'description' => 'Chamados agrupados por categoria',
            'icon' => 'fa-folder-tree',
            'color' => '#9b59b6',
            'default_visible' => true,
            'default_duration' => 12
        ],
        'satisfaction_metrics' => [
            'name' => 'Métricas de Satisfação',
            'description' => 'Avaliações e feedback dos usuários',
            'icon' => 'fa-star',
            'color' => '#f39c12',
            'default_visible' => true,
            'default_duration' => 10
        ],
        'trends_analysis' => [
            'name' => 'Análise de Tendências',
            'description' => 'Tendências históricas e previsões',
            'icon' => 'fa-chart-line',
            'color' => '#1abc9c',
            'default_visible' => true,
            'default_duration' => 15
        ],
        'heat_map' => [
            'name' => 'Mapa de Calor',
            'description' => 'Heatmap de demanda por dia/hora',
            'icon' => 'fa-fire',
            'color' => '#e67e22',
            'default_visible' => true,
            'default_duration' => 12
        ],
        'executive_dashboard' => [
            'name' => 'Dashboard Executivo',
            'description' => 'KPIs e métricas gerenciais',
            'icon' => 'fa-chart-bar',
            'color' => '#34495e',
            'default_visible' => false,
            'default_duration' => 20
        ],
        'priority_analysis' => [
            'name' => 'Análise de Prioridade',
            'description' => 'Distribuição por nível de prioridade',
            'icon' => 'fa-exclamation-triangle',
            'color' => '#c0392b',
            'default_visible' => true,
            'default_duration' => 10
        ],
        'entity_comparison' => [
            'name' => 'Comparativo por Setor',
            'description' => 'Comparação de desempenho entre setores',
            'icon' => 'fa-building',
            'color' => '#16a085',
            'default_visible' => false,
            'default_duration' => 15
        ]
    ];

    public function __construct() {
        $this->configFile = __DIR__ . '/../config/slides.json';

        // Criar diretório de config se não existir
        $configDir = dirname($this->configFile);
        if (!is_dir($configDir)) {
            mkdir($configDir, 0755, true);
        }

        // Criar arquivo de configuração com valores padrão se não existir
        if (!file_exists($this->configFile)) {
            $this->saveConfig($this->getDefaultConfig());
        }
    }

    /**
     * Obter configuração padrão
     */
    private function getDefaultConfig() {
        $config = [];

        foreach ($this->availableSlides as $key => $slide) {
            $config[$key] = [
                'visible' => $slide['default_visible'],
                'duration' => $slide['default_duration'],
                'order' => array_search($key, array_keys($this->availableSlides))
            ];
        }

        return $config;
    }

    /**
     * Obter todos os slides disponíveis
     */
    public function getAvailableSlides() {
        return $this->availableSlides;
    }

    /**
     * Obter configuração atual dos slides
     */
    public function getConfig() {
        if (file_exists($this->configFile)) {
            $json = file_get_contents($this->configFile);
            $config = json_decode($json, true);

            if ($config && is_array($config)) {
                return $config;
            }
        }

        return $this->getDefaultConfig();
    }

    /**
     * Obter slides visíveis ordenados
     */
    public function getVisibleSlides() {
        $config = $this->getConfig();
        $visibleSlides = [];

        foreach ($config as $key => $slideConfig) {
            if ($slideConfig['visible'] && isset($this->availableSlides[$key])) {
                $visibleSlides[$key] = array_merge(
                    $this->availableSlides[$key],
                    $slideConfig
                );
            }
        }

        // Ordenar por ordem definida
        uasort($visibleSlides, function($a, $b) {
            return ($a['order'] ?? 999) - ($b['order'] ?? 999);
        });

        return $visibleSlides;
    }

    /**
     * Salvar configuração
     */
    public function saveConfig($config) {
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents($this->configFile, $json) !== false;
    }

    /**
     * Atualizar visibilidade de um slide
     */
    public function toggleSlideVisibility($key, $visible) {
        $config = $this->getConfig();

        if (isset($config[$key])) {
            $config[$key]['visible'] = (bool)$visible;
            return $this->saveConfig($config);
        }

        return false;
    }

    /**
     * Atualizar duração de um slide
     */
    public function updateSlideDuration($key, $duration) {
        $config = $this->getConfig();

        if (isset($config[$key])) {
            $config[$key]['duration'] = max(5, min(60, (int)$duration)); // Entre 5 e 60 segundos
            return $this->saveConfig($config);
        }

        return false;
    }

    /**
     * Atualizar ordem dos slides
     */
    public function updateSlidesOrder($orderedKeys) {
        $config = $this->getConfig();

        foreach ($orderedKeys as $index => $key) {
            if (isset($config[$key])) {
                $config[$key]['order'] = $index;
            }
        }

        return $this->saveConfig($config);
    }

    /**
     * Restaurar configuração padrão
     */
    public function restoreDefaults() {
        return $this->saveConfig($this->getDefaultConfig());
    }

    /**
     * Obter estatísticas da configuração
     */
    public function getStats() {
        $config = $this->getConfig();
        $visible = array_filter($config, fn($s) => $s['visible']);
        $totalDuration = array_sum(array_column($visible, 'duration'));

        return [
            'total_slides' => count($this->availableSlides),
            'visible_slides' => count($visible),
            'hidden_slides' => count($this->availableSlides) - count($visible),
            'total_rotation_time' => $totalDuration,
            'avg_slide_duration' => count($visible) > 0 ? $totalDuration / count($visible) : 0
        ];
    }

    /**
     * Exportar configuração para backup
     */
    public function exportConfig() {
        return json_encode([
            'version' => '1.0',
            'exported_at' => date('Y-m-d H:i:s'),
            'config' => $this->getConfig()
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Importar configuração de backup
     */
    public function importConfig($jsonData) {
        try {
            $data = json_decode($jsonData, true);

            if (!isset($data['config']) || !is_array($data['config'])) {
                return false;
            }

            return $this->saveConfig($data['config']);
        } catch (Exception $e) {
            return false;
        }
    }
}
