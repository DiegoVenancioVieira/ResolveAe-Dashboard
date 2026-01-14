<?php
/**
 * Interface Visual para Exportação de Relatórios
 */

require_once __DIR__ . '/../src/Generators/ReportGenerator.php';
require_once __DIR__ . '/../src/Metrics/ExecutiveMetrics.php';

// Carregar listas de entidades e técnicos para filtros
$generator = new ReportGenerator();
$entities = $generator->getEntitiesList();
$technicians = $generator->getTechniciansList();
$sectionNames = ReportGenerator::getSectionNames();

// Data padrão: primeiro dia do mês atual até hoje
$hoje = new DateTime();
$primeiroDiaMesAtual = new DateTime();
$primeiroDiaMesAtual->modify('first day of this month');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exportar Relatórios - ResolveAe Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #79312cff 0%, #226660ff 100%);
            min-height: 100vh;
            padding: 20px;
            overflow-y: auto;
        }

        .export-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .export-header {
            background: linear-gradient(133deg, #282828 0%, #4b66a2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .export-header img {
            max-width: 150px;
            margin: 0 auto 15px;
            display: block;
            background: rgba(0,0,0,0.1);
            padding: 10px;
            border-radius: 10px;
        }

        .export-header h1 {
            font-size: 2em;
            margin-bottom: 10px;
        }

        .export-header p {
            opacity: 0.9;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 8px 15px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
            transition: all 0.3s;
            position: absolute;
            top: 20px;
            left: 20px;
        }

        .back-link:hover {
            background: rgba(255,255,255,0.3);
            transform: translateX(-3px);
        }

        .form-content {
            padding: 30px;
            max-height: calc(100vh - 400px);
            overflow-y: auto;
        }

        .form-section {
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        .form-section h2 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.2em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 0.9em;
        }

        .form-group input,
        .form-group select {
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .sections-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .section-checkbox {
            display: flex;
            align-items: center;
            padding: 12px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .section-checkbox:hover {
            border-color: #667eea;
            background: #f0f4ff;
            transform: translateX(3px);
        }

        .section-checkbox input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            cursor: pointer;
            accent-color: #667eea;
        }

        .section-checkbox label {
            cursor: pointer;
            flex: 1;
            margin: 0;
            font-size: 0.9em;
        }

        .section-checkbox input[type="checkbox"]:checked ~ label {
            color: #667eea;
            font-weight: 600;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }

        .export-footer {
            background: #f8f9fa;
            padding: 25px 30px;
            border-top: 2px solid #e0e0e0;
        }

        .export-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        @media (max-width: 768px) {
            .export-buttons {
                grid-template-columns: 1fr;
            }
        }

        .btn-export {
            padding: 20px;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .btn-export:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-pdf {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }

        .btn-pdf:hover:not(:disabled) {
            background: linear-gradient(135deg, #c0392b, #a93226);
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(231, 76, 60, 0.4);
        }

        .btn-excel {
            background: linear-gradient(135deg, #27ae60, #229954);
            color: white;
        }

        .btn-excel:hover:not(:disabled) {
            background: linear-gradient(135deg, #229954, #1e8449);
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(39, 174, 96, 0.4);
        }

        .btn-csv {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }

        .btn-csv:hover:not(:disabled) {
            background: linear-gradient(135deg, #2980b9, #21618c);
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(52, 152, 219, 0.4);
        }

        .icon {
            font-size: 28px;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 0.9em;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .loading.active {
            display: flex;
        }

        .loading-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Custom Scrollbar */
        .form-content::-webkit-scrollbar {
            width: 10px;
        }

        .form-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .form-content::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }

        .form-content::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Executive Dashboard Styles */
        .executive-dashboard {
            padding: 20px;
            background: white;
            border-radius: 10px;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .kpi-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .kpi-card:hover {
            transform: translateY(-5px);
        }

        .kpi-card.green {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .kpi-card.red {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
        }

        .kpi-card.blue {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .kpi-card.orange {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        .kpi-value {
            font-size: 2.5em;
            font-weight: bold;
            margin: 10px 0;
        }

        .kpi-label {
            font-size: 0.9em;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .kpi-trend {
            font-size: 0.8em;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .trends-section, .categories-section, .workload-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.3em;
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .trend-list, .category-list, .workload-list {
            list-style: none;
        }

        .trend-item, .category-item, .workload-item {
            padding: 12px;
            background: white;
            margin-bottom: 8px;
            border-radius: 5px;
            border-left: 4px solid #667eea;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .category-item {
            border-left-color: #e74c3c;
        }

        .workload-item {
            border-left-color: #3498db;
        }

        .item-label {
            font-weight: 600;
            color: #2c3e50;
        }

        .item-value {
            font-weight: bold;
            color: #667eea;
            font-size: 1.1em;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 5px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        .comparison-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
        }

        .comparison-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 2px solid #e0e0e0;
        }

        .comparison-month {
            font-size: 0.8em;
            color: #7f8c8d;
            margin-bottom: 5px;
        }

        .comparison-value {
            font-size: 1.5em;
            font-weight: bold;
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="export-container">
        <div class="export-header">
            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
            </a>
            <img src="pics/resolveae.png" alt="ResolveAe Logo">
            <h1><i class="fas fa-file-export"></i> Exportar Relatórios</h1>
            <p>Selecione o período, as seções desejadas e clique em gerar relatório</p>
        </div>

        <div class="form-content">
            <div class="alert alert-info">
                <strong><i class="fas fa-info-circle"></i> Dica:</strong> Selecione o período e as seções que deseja incluir no relatório. O relatório será gerado com os dados filtrados.
            </div>

            <form id="exportForm">
                <!-- Seção de Período -->
                <div class="form-section">
                    <h2><i class="fas fa-calendar-alt"></i> Período do Relatório</h2>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="date_from">Data Inicial</label>
                            <input type="date" id="date_from" name="date_from"
                                   value="<?= $primeiroDiaMesAtual->format('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label for="date_to">Data Final</label>
                            <input type="date" id="date_to" name="date_to"
                                   value="<?= $hoje->format('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="alert alert-warning">
                        <strong><i class="fas fa-exclamation-triangle"></i> Atenção:</strong> O período máximo permitido é de 12 meses.
                    </div>
                </div>

                <!-- Seção de Filtros Opcionais -->
                <div class="form-section">
                    <h2><i class="fas fa-filter"></i> Filtros Opcionais</h2>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="entity_id">Filtrar por Setor/Entidade</label>
                            <select id="entity_id" name="entity_id">
                                <option value="">Todos os setores</option>
                                <?php foreach ($entities as $entity): ?>
                                    <option value="<?= $entity['id'] ?>">
                                        <?= htmlspecialchars($entity['name'] ?? '') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="technician_id">Filtrar por Técnico</label>
                            <select id="technician_id" name="technician_id">
                                <option value="">Todos os técnicos</option>
                                <?php foreach ($technicians as $tech): ?>
                                    <option value="<?= $tech['id'] ?>">
                                        <?= htmlspecialchars($tech['nome'] ?? '') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Seção de Dados -->
                <div class="form-section">
                    <h2><i class="fas fa-list-check"></i> Seções do Relatório</h2>
                    <div class="button-group">
                        <button type="button" class="btn btn-secondary" onclick="selectAllSections()">
                            <i class="fas fa-check-double"></i> Selecionar Todos
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="clearAllSections()">
                            <i class="fas fa-times"></i> Limpar Seleção
                        </button>
                    </div>
                    <div class="sections-grid">
                        <?php foreach ($sectionNames as $key => $name): ?>
                            <div class="section-checkbox">
                                <input type="checkbox" id="section_<?= $key ?>"
                                       name="sections[]" value="<?= $key ?>" checked>
                                <label for="section_<?= $key ?>"><?= $name ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Dashboard Executivo -->
                <div class="form-section">
                    <h2><i class="fas fa-chart-line"></i> Visualização Rápida</h2>
                    <div class="alert alert-info">
                        <strong><i class="fas fa-info-circle"></i> Dashboard Gerencial:</strong> Visualize KPIs e métricas executivas do período selecionado.
                    </div>
                    <div id="executiveDashboard" style="display: none;">
                        <!-- Conteúdo será carregado dinamicamente -->
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="loadExecutiveDashboard()" style="width: 100%;">
                        <i class="fas fa-sync"></i> Visualizar KPIs do Período
                    </button>
                </div>
            </form>
        </div>

        <!-- Botão de Exportação (fixo no rodapé) -->
        <div class="export-footer">
            <div class="export-buttons" style="display: flex; justify-content: center;">
                <button type="button" class="btn-export btn-generate" onclick="openReportTypeModal()" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); font-size: 16px; font-weight: 600;">
                    <span class="icon"><i class="fas fa-file-export"></i></span>
                    <span>Gerar Relatório</span>
                </button>
            </div>
        </div>
    </div>

    <div class="loading" id="loading">
        <div class="loading-content">
            <div class="spinner"></div>
            <h3>Gerando relatório...</h3>
            <p>Por favor, aguarde. Isso pode levar alguns segundos.</p>
        </div>
    </div>

    <!-- Modal de Seleção de Tipo de Relatório -->
    <div class="report-type-modal" id="report-type-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 10000; justify-content: center; align-items: center;">
        <div class="report-type-modal-content" style="background: white; border-radius: 20px; padding: 40px; max-width: 700px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
            <div class="report-type-header" style="text-align: center; margin-bottom: 35px;">
                <h2 style="margin: 0; color: #2c3e50; font-size: 28px;">
                    <i class="fas fa-file-export"></i> Escolha o Tipo de Relatório
                </h2>
                <p style="margin-top: 10px; color: #7f8c8d; font-size: 14px;">Selecione o formato que melhor atende suas necessidades</p>
            </div>

            <div class="report-options" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                <!-- Opção 1: Dashboard Executivo Compacto -->
                <div class="report-option" onclick="selectExecutiveDashboard()" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 15px; padding: 30px; cursor: pointer; transition: all 0.3s ease; position: relative; overflow: hidden; color: white; text-align: center; box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);">
                    <i class="fas fa-chart-line" style="font-size: 48px; margin-bottom: 15px; opacity: 0.9;"></i>
                    <h3 style="margin: 0 0 10px 0; font-size: 18px; font-weight: 600;">Dashboard Executivo Compacto</h3>
                    <p style="margin: 0; font-size: 13px; opacity: 0.95; line-height: 1.5;">Relatório resumido de 1 página com KPIs principais e métricas executivas</p>
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.3); font-size: 11px; opacity: 0.85; line-height: 1.4;">
                        <i class="fas fa-check-circle"></i> Usa: Período + Entidade<br>
                        <i class="fas fa-clock"></i> Rápido • <i class="fas fa-file"></i> 1 página
                    </div>
                </div>

                <!-- Opção 2: PDF Completo -->
                <div class="report-option" onclick="selectFullPDF()" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 15px; padding: 30px; cursor: pointer; transition: all 0.3s ease; position: relative; overflow: hidden; color: white; text-align: center; box-shadow: 0 5px 15px rgba(245, 87, 108, 0.3);">
                    <i class="fas fa-file-pdf" style="font-size: 48px; margin-bottom: 15px; opacity: 0.9;"></i>
                    <h3 style="margin: 0 0 10px 0; font-size: 18px; font-weight: 600;">PDF Completo</h3>
                    <p style="margin: 0; font-size: 13px; opacity: 0.95; line-height: 1.5;">Relatório detalhado com todas as seções selecionadas e análises completas</p>
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.3); font-size: 11px; opacity: 0.85; line-height: 1.4;">
                        <i class="fas fa-check-circle"></i> Usa: Todos os campos<br>
                        <i class="fas fa-tasks"></i> Detalhado • <i class="fas fa-file-alt"></i> Multi-seções
                    </div>
                </div>
            </div>

            <div style="text-align: center;">
                <button onclick="closeReportTypeModal()" style="padding: 12px 30px; border: 2px solid #e0e0e0; border-radius: 8px; cursor: pointer; background: white; color: #666; font-weight: 600; font-size: 14px; transition: all 0.2s;">
                    <i class="fas fa-times"></i> Cancelar
                </button>
            </div>
        </div>
    </div>

    <style>
        .report-option:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2) !important;
        }

        .report-option:active {
            transform: translateY(-2px);
        }
    </style>

    <script>
        function selectAllSections() {
            document.querySelectorAll('input[name="sections[]"]').forEach(cb => {
                cb.checked = true;
            });
        }

        function clearAllSections() {
            document.querySelectorAll('input[name="sections[]"]').forEach(cb => {
                cb.checked = false;
            });
        }

        function exportReport(format) {
            // Validar seleção de seções
            const checkedSections = document.querySelectorAll('input[name="sections[]"]:checked');
            if (checkedSections.length === 0) {
                alert('⚠️ Por favor, selecione pelo menos uma seção para exportar.');
                return;
            }

            // Validar datas
            const dateFrom = document.getElementById('date_from').value;
            const dateTo = document.getElementById('date_to').value;

            if (!dateFrom || !dateTo) {
                alert('⚠️ Por favor, preencha as datas de início e fim.');
                return;
            }

            if (new Date(dateFrom) > new Date(dateTo)) {
                alert('⚠️ A data inicial não pode ser maior que a data final.');
                return;
            }

            // Construir URL
            const params = new URLSearchParams();
            params.append('format', format);
            params.append('date_from', dateFrom);
            params.append('date_to', dateTo);

            // Adicionar seções selecionadas
            checkedSections.forEach(cb => {
                params.append('sections[]', cb.value);
            });

            // Adicionar filtros opcionais
            const entityId = document.getElementById('entity_id').value;
            const technicianId = document.getElementById('technician_id').value;

            if (entityId) params.append('entity_id', entityId);
            if (technicianId) params.append('technician_id', technicianId);

            // Mostrar loading
            document.getElementById('loading').classList.add('active');
            document.querySelectorAll('.btn-export').forEach(btn => btn.disabled = true);

            // Fazer download
            const url = 'export.php?' + params.toString();
            window.location.href = url;

            // Esconder loading após 3 segundos
            setTimeout(() => {
                document.getElementById('loading').classList.remove('active');
                document.querySelectorAll('.btn-export').forEach(btn => btn.disabled = false);
            }, 3000);
        }

        // Validação de período máximo
        document.getElementById('date_from').addEventListener('change', validatePeriod);
        document.getElementById('date_to').addEventListener('change', validatePeriod);

        function validatePeriod() {
            const dateFrom = new Date(document.getElementById('date_from').value);
            const dateTo = new Date(document.getElementById('date_to').value);

            if (dateFrom && dateTo) {
                const diffTime = Math.abs(dateTo - dateFrom);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                const diffMonths = diffDays / 30;

                if (diffMonths > 12) {
                    alert('⚠️ O período máximo permitido é de 12 meses.');
                    document.getElementById('date_to').value = '';
                }
            }
        }

        // Executive Dashboard Functions
        async function loadExecutiveDashboard() {
            const dateFrom = document.getElementById('date_from').value;
            const dateTo = document.getElementById('date_to').value;
            const entityId = document.getElementById('entity_id').value;

            if (!dateFrom || !dateTo) {
                alert('⚠️ Por favor, preencha as datas antes de carregar o dashboard.');
                return;
            }

            // Mostrar loading
            document.getElementById('loading').classList.add('active');

            try {
                const params = new URLSearchParams({
                    date_from: dateFrom,
                    date_to: dateTo,
                    entity_id: entityId || ''
                });

                const response = await fetch('api/executive-metrics.php?' + params.toString());
                const data = await response.json();

                if (data.success) {
                    renderExecutiveDashboard(data.data);
                    document.getElementById('executiveDashboard').style.display = 'block';
                } else {
                    alert('❌ Erro ao carregar dashboard: ' + data.message);
                }
            } catch (error) {
                alert('❌ Erro ao carregar dashboard executivo: ' + error.message);
            } finally {
                document.getElementById('loading').classList.remove('active');
            }
        }

        function renderExecutiveDashboard(data) {
            const container = document.getElementById('executiveDashboard');

            const html = `
                <div class="executive-dashboard">
                    <!-- KPIs -->
                    <div class="kpi-grid">
                        <div class="kpi-card blue">
                            <div class="kpi-label">Total de Chamados</div>
                            <div class="kpi-value">${data.kpis.total_tickets}</div>
                            <div class="kpi-trend">
                                <i class="fas fa-chart-line"></i>
                                <span>${data.kpis.tickets_trend}</span>
                            </div>
                        </div>

                        <div class="kpi-card ${data.kpis.sla_compliance >= 80 ? 'green' : 'red'}">
                            <div class="kpi-label">SLA Compliance</div>
                            <div class="kpi-value">${data.kpis.sla_compliance.toFixed(1)}%</div>
                            <div class="kpi-trend">
                                <i class="fas ${data.kpis.sla_compliance >= 80 ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                                <span>${data.kpis.sla_compliance >= 80 ? 'Dentro da meta' : 'Abaixo da meta'}</span>
                            </div>
                        </div>

                        <div class="kpi-card orange">
                            <div class="kpi-label">Tempo Médio Solução</div>
                            <div class="kpi-value">${data.kpis.avg_resolution_time}</div>
                            <div class="kpi-trend">
                                <i class="fas fa-clock"></i>
                                <span>${data.kpis.resolution_trend}</span>
                            </div>
                        </div>

                        <div class="kpi-card ${data.kpis.satisfaction_score >= 4 ? 'green' : 'orange'}">
                            <div class="kpi-label">Satisfação Média</div>
                            <div class="kpi-value">${data.kpis.satisfaction_score.toFixed(1)}/5</div>
                            <div class="kpi-trend">
                                <i class="fas fa-star"></i>
                                <span>${data.kpis.satisfaction_responses} avaliações</span>
                            </div>
                        </div>

                        <div class="kpi-card green">
                            <div class="kpi-label">Produtividade</div>
                            <div class="kpi-value">${data.kpis.productivity.toFixed(1)}</div>
                            <div class="kpi-trend">
                                <i class="fas fa-trophy"></i>
                                <span>chamados/técnico/dia</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tendências -->
                    <div class="trends-section">
                        <h3 class="section-title">
                            <i class="fas fa-chart-area"></i>
                            Tendências dos Últimos 6 Meses
                        </h3>
                        <div class="chart-container">
                            <ul class="trend-list">
                                ${data.trends.map(trend => `
                                    <li class="trend-item">
                                        <div>
                                            <span class="item-label">${trend.month}</span>
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: ${(trend.tickets / Math.max(...data.trends.map(t => t.tickets))) * 100}%"></div>
                                            </div>
                                        </div>
                                        <span class="item-value">${trend.tickets} chamados</span>
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                    </div>

                    <!-- Top 5 Categorias Problemáticas -->
                    <div class="categories-section">
                        <h3 class="section-title">
                            <i class="fas fa-exclamation-triangle"></i>
                            Top 5 Categorias Problemáticas
                        </h3>
                        <div class="chart-container">
                            <ul class="category-list">
                                ${data.top_categories.map((cat, index) => `
                                    <li class="category-item">
                                        <div>
                                            <span class="item-label">#${index + 1} ${cat.category}</span>
                                            <div style="font-size: 0.8em; color: #7f8c8d; margin-top: 3px;">
                                                Tempo médio: ${cat.avg_resolution_time} | SLA: ${cat.sla_compliance.toFixed(1)}%
                                            </div>
                                        </div>
                                        <span class="item-value">${cat.tickets} chamados</span>
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                    </div>

                    <!-- Distribuição de Carga -->
                    <div class="workload-section">
                        <h3 class="section-title">
                            <i class="fas fa-users"></i>
                            Distribuição de Carga de Trabalho
                        </h3>
                        <div class="chart-container">
                            <ul class="workload-list">
                                ${data.workload.map(tech => `
                                    <li class="workload-item">
                                        <div style="flex: 1;">
                                            <span class="item-label">${tech.technician}</span>
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: ${(tech.tickets / Math.max(...data.workload.map(t => t.tickets))) * 100}%"></div>
                                            </div>
                                            <div style="font-size: 0.8em; color: #7f8c8d; margin-top: 5px;">
                                                Tempo médio: ${tech.avg_time} | Produtividade: ${tech.productivity.toFixed(1)}
                                            </div>
                                        </div>
                                        <span class="item-value">${tech.tickets}</span>
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                    </div>

                    <!-- Comparativo Mensal -->
                    <div class="trends-section">
                        <h3 class="section-title">
                            <i class="fas fa-calendar-check"></i>
                            Comparativo Mensal
                        </h3>
                        <div class="comparison-grid">
                            ${data.monthly_comparison.map(month => `
                                <div class="comparison-card">
                                    <div class="comparison-month">${month.month}</div>
                                    <div class="comparison-value">${month.tickets}</div>
                                    <div style="font-size: 0.7em; color: #7f8c8d; margin-top: 5px;">
                                        SLA: ${month.sla}%
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            `;

            container.innerHTML = html;
        }

        // Permitir fechar modal com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('report-type-modal').style.display === 'flex') {
                closeReportTypeModal();
            }
        });

        // ===== FUNÇÕES DO MODAL DE SELEÇÃO DE TIPO DE RELATÓRIO =====

        function openReportTypeModal() {
            // Validar datas antes de abrir o modal
            const dateFrom = document.getElementById('date_from').value;
            const dateTo = document.getElementById('date_to').value;

            if (!dateFrom || !dateTo) {
                alert('⚠️ Por favor, preencha as datas de início e fim antes de gerar o relatório.');
                return;
            }

            if (new Date(dateFrom) > new Date(dateTo)) {
                alert('⚠️ A data inicial não pode ser maior que a data final.');
                return;
            }

            // Abrir modal
            document.getElementById('report-type-modal').style.display = 'flex';
        }

        function closeReportTypeModal() {
            document.getElementById('report-type-modal').style.display = 'none';
        }

        function selectExecutiveDashboard() {
            // Fechar modal de seleção
            closeReportTypeModal();

            // Validar datas do formulário principal
            const dateFrom = document.getElementById('date_from').value;
            const dateTo = document.getElementById('date_to').value;

            if (!dateFrom || !dateTo) {
                alert('⚠️ Por favor, preencha as datas de início e fim antes de gerar o relatório.');
                return;
            }

            if (new Date(dateFrom) > new Date(dateTo)) {
                alert('⚠️ A data inicial não pode ser maior que a data final.');
                return;
            }

            // Construir URL com dados do formulário principal
            const params = new URLSearchParams();
            params.append('date_from', dateFrom);
            params.append('date_to', dateTo);

            // Adicionar entidade se selecionada
            const entityId = document.getElementById('entity_id').value;
            if (entityId) {
                params.append('entity_id', entityId);
            }

            // Mostrar loading
            document.getElementById('loading').classList.add('active');

            // Fazer download direto (sem segundo modal!)
            const url = 'api/export-executive-compact.php?' + params.toString();
            window.location.href = url;

            // Esconder loading após 3 segundos
            setTimeout(() => {
                document.getElementById('loading').classList.remove('active');
            }, 3000);
        }

        function selectFullPDF() {
            // Fechar modal de seleção
            closeReportTypeModal();

            // Exportar PDF completo (reutiliza função existente)
            exportReport('pdf');
        }

        // Fechar modal ao clicar fora
        document.getElementById('report-type-modal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeReportTypeModal();
            }
        });
    </script>
</body>
</html>
