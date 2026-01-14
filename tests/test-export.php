<?php
/**
 * Script de teste da funcionalidade de exportação
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/GLPIMetricsExtended.php';
require_once __DIR__ . '/includes/ReportGenerator.php';

echo "<h1>Teste de Exportação GLPI</h1>";

// Teste 1: Inicialização da classe GLPIMetricsExtended
echo "<h2>Teste 1: Inicializar GLPIMetricsExtended</h2>";
try {
    $metrics = new GLPIMetricsExtended();
    echo "✅ GLPIMetricsExtended inicializado com sucesso!<br>";
} catch (Exception $e) {
    echo "❌ Erro ao inicializar GLPIMetricsExtended: " . $e->getMessage() . "<br>";
    exit;
}

// Teste 2: Buscar lista de entidades
echo "<h2>Teste 2: Buscar Entidades</h2>";
try {
    $entities = $metrics->getEntitiesList();
    echo "✅ Entidades encontradas: " . count($entities) . "<br>";
    if (count($entities) > 0) {
        echo "<ul>";
        foreach (array_slice($entities, 0, 5) as $entity) {
            echo "<li>ID: {$entity['id']} - {$entity['name']}</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "❌ Erro ao buscar entidades: " . $e->getMessage() . "<br>";
}

// Teste 3: Buscar lista de técnicos
echo "<h2>Teste 3: Buscar Técnicos</h2>";
try {
    $technicians = $metrics->getTechniciansList();
    echo "✅ Técnicos encontrados: " . count($technicians) . "<br>";
    if (count($technicians) > 0) {
        echo "<ul>";
        foreach (array_slice($technicians, 0, 5) as $tech) {
            echo "<li>ID: {$tech['id']} - {$tech['nome']}</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "❌ Erro ao buscar técnicos: " . $e->getMessage() . "<br>";
}

// Teste 4: Buscar dados com filtro de data
echo "<h2>Teste 4: Buscar Chamados por Status (Último Mês)</h2>";
try {
    $dateFrom = date('Y-m-01', strtotime('-1 month'));
    $dateTo = date('Y-m-t', strtotime('-1 month'));

    $status = $metrics->getTicketsByStatusWithDateRange($dateFrom, $dateTo);
    echo "✅ Dados obtidos para período: $dateFrom a $dateTo<br>";
    echo "<ul>";
    echo "<li>Total Criados: {$status['total_criados']}</li>";
    echo "<li>Novos: {$status['novos']}</li>";
    echo "<li>Atribuídos: {$status['atribuidos']}</li>";
    echo "<li>Resolvidos: {$status['resolvidos']}</li>";
    echo "<li>Fechados: {$status['fechados']}</li>";
    echo "</ul>";
} catch (Exception $e) {
    echo "❌ Erro ao buscar chamados por status: " . $e->getMessage() . "<br>";
}

// Teste 5: Inicializar ReportGenerator
echo "<h2>Teste 5: Inicializar ReportGenerator</h2>";
try {
    $dateFrom = date('Y-m-01', strtotime('-1 month'));
    $dateTo = date('Y-m-t', strtotime('-1 month'));
    $sections = ['resumo_executivo', 'chamados_status'];

    $generator = new ReportGenerator($dateFrom, $dateTo, $sections);
    echo "✅ ReportGenerator inicializado com sucesso!<br>";

    $report = $generator->generateReport();
    echo "✅ Relatório gerado com sucesso!<br>";
    echo "<ul>";
    echo "<li>Período: {$report['metadata']['periodo_formatado']}</li>";
    echo "<li>Seções incluídas: {$report['metadata']['secoes_incluidas']}</li>";
    echo "<li>Data de geração: {$report['metadata']['data_geracao']}</li>";
    echo "</ul>";

    // Mostrar resumo executivo
    if (isset($report['data']['resumo_executivo'])) {
        echo "<h3>Resumo Executivo:</h3>";
        $resumo = $report['data']['resumo_executivo'];
        echo "<ul>";
        echo "<li>Total Criados: {$resumo['total_criados']}</li>";
        echo "<li>Total Abertos: {$resumo['total_abertos']}</li>";
        echo "<li>Total Resolvidos: {$resumo['total_resolvidos']}</li>";
        echo "<li>Tempo Médio: {$resumo['tempo_medio_resolucao']}</li>";
        echo "</ul>";
    }

} catch (Exception $e) {
    echo "❌ Erro ao gerar relatório: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h2>✅ Todos os testes concluídos!</h2>";
echo "<p><a href='export-interface.php'>Ir para Interface de Exportação</a></p>";
?>
