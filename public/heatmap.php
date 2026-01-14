<?php
/**
 * Visualização de Heatmap de Demanda
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Heatmap de Demanda - GLPI Dashboard</title>
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
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(133deg, #282828 0%, #4b66a2 100%);
            color: white;
            padding: 30px;
            position: relative;
        }

        .header h1 {
            font-size: 2em;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .back-link {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-link:hover {
            background: rgba(255,255,255,0.3);
            transform: translateX(-3px);
        }

        .content {
            padding: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-card.green {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .stat-card.orange {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9em;
            opacity: 0.9;
        }

        .heatmap-container {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            overflow-x: auto;
        }

        .heatmap-title {
            font-size: 1.3em;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .heatmap-grid {
            display: grid;
            grid-template-columns: 100px repeat(24, 40px);
            gap: 2px;
            min-width: max-content;
        }

        .heatmap-header {
            display: contents;
        }

        .hour-label {
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7em;
            color: #7f8c8d;
            font-weight: 600;
        }

        .day-label {
            height: 40px;
            display: flex;
            align-items: center;
            padding-left: 10px;
            font-weight: 600;
            color: #2c3e50;
            background: white;
            border-radius: 5px;
        }

        .heatmap-cell {
            height: 40px;
            border-radius: 3px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7em;
            font-weight: 600;
        }

        .heatmap-cell:hover {
            transform: scale(1.1);
            z-index: 10;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }

        .heatmap-cell.very-low {
            background: #ecf0f1;
            color: #95a5a6;
        }

        .heatmap-cell.low {
            background: #a8e6cf;
            color: #2c3e50;
        }

        .heatmap-cell.medium {
            background: #ffd93d;
            color: #2c3e50;
        }

        .heatmap-cell.high {
            background: #ffb347;
            color: #2c3e50;
        }

        .heatmap-cell.very-high {
            background: #ff6b6b;
            color: white;
        }

        .legend {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 20px;
            justify-content: center;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.85em;
        }

        .legend-color {
            width: 30px;
            height: 20px;
            border-radius: 3px;
        }

        .analysis-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .analysis-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        .analysis-title {
            font-size: 1.1em;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .list-item {
            padding: 10px;
            background: white;
            margin-bottom: 8px;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .recommendation-card {
            background: white;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }

        .recommendation-card.high {
            border-left-color: #e74c3c;
        }

        .recommendation-card.medium {
            border-left-color: #f39c12;
        }

        .recommendation-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #2c3e50;
        }

        .recommendation-desc {
            font-size: 0.9em;
            color: #7f8c8d;
        }

        .tooltip {
            position: absolute;
            background: rgba(0,0,0,0.9);
            color: white;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 0.85em;
            white-space: nowrap;
            pointer-events: none;
            z-index: 1000;
            display: none;
        }

        .loading {
            text-align: center;
            padding: 50px;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
            </a>
            <h1>
                <i class="fas fa-fire"></i>
                Heatmap de Demanda
            </h1>
            <p>Análise de padrões de demanda por dia da semana e hora do dia</p>
        </div>

        <div class="content">
            <div id="loadingIndicator" class="loading">
                <i class="fas fa-spinner fa-spin fa-3x"></i>
                <p>Carregando dados...</p>
            </div>

            <div id="heatmapContent" style="display: none;">
                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value" id="statTotal">0</div>
                        <div class="stat-label">Total de Chamados</div>
                    </div>
                    <div class="stat-card orange">
                        <div class="stat-value" id="statMax">0</div>
                        <div class="stat-label">Pico Máximo</div>
                    </div>
                    <div class="stat-card green">
                        <div class="stat-value" id="statAvg">0</div>
                        <div class="stat-label">Média por Hora</div>
                    </div>
                </div>

                <!-- Heatmap -->
                <div class="heatmap-container">
                    <div class="heatmap-title">
                        <i class="fas fa-th"></i>
                        Mapa de Calor - Demanda por Dia e Hora
                    </div>
                    <div id="heatmapGrid" class="heatmap-grid">
                        <!-- Será preenchido via JavaScript -->
                    </div>
                    <div class="legend">
                        <div class="legend-item">
                            <div class="legend-color" style="background: #ecf0f1;"></div>
                            <span>Muito Baixo</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #a8e6cf;"></div>
                            <span>Baixo</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #ffd93d;"></div>
                            <span>Médio</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #ffb347;"></div>
                            <span>Alto</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #ff6b6b;"></div>
                            <span>Muito Alto</span>
                        </div>
                    </div>
                </div>

                <!-- Análises -->
                <div class="analysis-grid">
                    <div class="analysis-card">
                        <div class="analysis-title">
                            <i class="fas fa-arrow-up"></i>
                            Horários de Pico
                        </div>
                        <div id="peakHoursList">
                            <!-- Será preenchido via JavaScript -->
                        </div>
                    </div>

                    <div class="analysis-card">
                        <div class="analysis-title">
                            <i class="fas fa-arrow-down"></i>
                            Horários Mais Tranquilos
                        </div>
                        <div id="quietHoursList">
                            <!-- Será preenchido via JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- Recomendações -->
                <div class="analysis-card">
                    <div class="analysis-title">
                        <i class="fas fa-lightbulb"></i>
                        Recomendações
                    </div>
                    <div id="recommendationsList">
                        <!-- Será preenchido via JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tooltip" id="tooltip"></div>

    <script>
        async function loadHeatmap() {
            try {
                const response = await fetch('api/heatmap-data.php');
                const result = await response.json();

                if (result.success) {
                    renderHeatmap(result.data);
                    document.getElementById('loadingIndicator').style.display = 'none';
                    document.getElementById('heatmapContent').style.display = 'block';
                } else {
                    alert('Erro ao carregar heatmap: ' + result.message);
                }
            } catch (error) {
                alert('Erro ao carregar dados: ' + error.message);
            }
        }

        function renderHeatmap(data) {
            // Atualizar stats
            document.getElementById('statTotal').textContent = data.stats.total;
            document.getElementById('statMax').textContent = data.stats.max;
            document.getElementById('statAvg').textContent = data.stats.avg.toFixed(1);

            // Renderizar grid do heatmap
            const grid = document.getElementById('heatmapGrid');
            let html = '<div></div>'; // Canto superior esquerdo vazio

            // Cabeçalho com horas
            for (let h = 0; h < 24; h++) {
                html += `<div class="hour-label">${String(h).padStart(2, '0')}h</div>`;
            }

            // Linhas com dias
            data.heatmap.forEach(dayData => {
                // Label do dia
                html += `<div class="day-label">${dayData.day_name}</div>`;

                // Células de hora
                dayData.hours.forEach(hourData => {
                    html += `
                        <div class="heatmap-cell ${hourData.level}"
                             data-count="${hourData.count}"
                             data-day="${dayData.day_name}"
                             data-hour="${hourData.hour_label}"
                             onmouseenter="showTooltip(event, this)"
                             onmouseleave="hideTooltip()">
                            ${hourData.count > 0 ? hourData.count : ''}
                        </div>
                    `;
                });
            });

            grid.innerHTML = html;

            // Renderizar horários de pico
            const peakList = document.getElementById('peakHoursList');
            peakList.innerHTML = data.peak_hours.map(peak => `
                <div class="list-item">
                    <span><strong>${peak.day_name}</strong> às ${peak.hour_label}</span>
                    <span style="color: #e74c3c; font-weight: bold;">${peak.count} chamados</span>
                </div>
            `).join('');

            // Renderizar horários tranquilos
            const quietList = document.getElementById('quietHoursList');
            quietList.innerHTML = data.quiet_hours.map(quiet => `
                <div class="list-item">
                    <span><strong>${quiet.day_name}</strong> às ${quiet.hour_label}</span>
                    <span style="color: #27ae60; font-weight: bold;">${quiet.count} chamados</span>
                </div>
            `).join('');

            // Renderizar recomendações
            const recList = document.getElementById('recommendationsList');
            recList.innerHTML = data.recommendations.map(rec => `
                <div class="recommendation-card ${rec.priority}">
                    <div class="recommendation-title">
                        <i class="fas fa-${rec.type === 'staffing' ? 'users' : rec.type === 'maintenance' ? 'tools' : 'info-circle'}"></i>
                        ${rec.title}
                    </div>
                    <div class="recommendation-desc">${rec.description}</div>
                </div>
            `).join('');
        }

        function showTooltip(event, element) {
            const tooltip = document.getElementById('tooltip');
            const count = element.dataset.count;
            const day = element.dataset.day;
            const hour = element.dataset.hour;

            tooltip.innerHTML = `<strong>${day} ${hour}</strong><br>${count} chamados`;
            tooltip.style.display = 'block';
            tooltip.style.left = event.pageX + 10 + 'px';
            tooltip.style.top = event.pageY + 10 + 'px';
        }

        function hideTooltip() {
            document.getElementById('tooltip').style.display = 'none';
        }

        // Carregar ao iniciar
        loadHeatmap();
    </script>
</body>
</html>
