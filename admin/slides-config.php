<?php
/**
 * Interface de Configuração de Slides
 */

require_once __DIR__ . '/includes/SlidesConfig.php';

$slidesConfig = new SlidesConfig();
$availableSlides = $slidesConfig->getAvailableSlides();
$currentConfig = $slidesConfig->getConfig();
$stats = $slidesConfig->getStats();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuração de Slides - GLPI Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.css">
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
            max-width: 1400px;
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

        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 2px solid #e0e0e0;
        }

        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #667eea;
        }

        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #2c3e50;
        }

        .stat-label {
            font-size: 0.85em;
            color: #7f8c8d;
            margin-top: 5px;
        }

        .content {
            padding: 30px;
        }

        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .slides-list {
            display: grid;
            gap: 15px;
        }

        .slide-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            border-left: 4px solid #667eea;
            display: grid;
            grid-template-columns: 50px 1fr auto;
            gap: 20px;
            align-items: center;
            transition: all 0.3s;
            cursor: move;
        }

        .slide-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .slide-item.hidden {
            opacity: 0.5;
            border-left-color: #95a5a6;
        }

        .slide-drag-handle {
            font-size: 1.5em;
            color: #95a5a6;
            text-align: center;
            cursor: grab;
        }

        .slide-drag-handle:active {
            cursor: grabbing;
        }

        .slide-info {
            flex: 1;
        }

        .slide-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }

        .slide-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2em;
        }

        .slide-title {
            font-size: 1.1em;
            font-weight: bold;
            color: #2c3e50;
        }

        .slide-description {
            font-size: 0.9em;
            color: #7f8c8d;
        }

        .slide-controls {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .duration-control {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .duration-control input {
            width: 60px;
            padding: 8px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            text-align: center;
        }

        .toggle-switch {
            position: relative;
            width: 60px;
            height: 30px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 30px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #2ecc71;
        }

        input:checked + .slider:before {
            transform: translateX(30px);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }

        .sortable-ghost {
            opacity: 0.4;
            background: #667eea;
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
                <i class="fas fa-sliders-h"></i>
                Configuração de Slides
            </h1>
            <p>Selecione quais slides aparecem na rotação e configure a duração de cada um</p>
        </div>

        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-value"><?= $stats['visible_slides'] ?></div>
                <div class="stat-label">Slides Visíveis</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['total_slides'] ?></div>
                <div class="stat-label">Total de Slides</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['total_rotation_time'] ?>s</div>
                <div class="stat-label">Tempo Total de Rotação</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['avg_slide_duration'], 1) ?>s</div>
                <div class="stat-label">Duração Média</div>
            </div>
        </div>

        <div class="content">
            <div class="alert alert-info">
                <strong><i class="fas fa-info-circle"></i> Dica:</strong>
                Arraste os slides para reorganizar a ordem de exibição. Use o botão de alternância para mostrar/ocultar slides.
            </div>

            <div class="actions-bar">
                <div style="display: flex; gap: 10px;">
                    <button class="btn btn-primary" onclick="saveConfiguration()">
                        <i class="fas fa-save"></i> Salvar Configuração
                    </button>
                    <button class="btn btn-success" onclick="selectAll()">
                        <i class="fas fa-check-double"></i> Mostrar Todos
                    </button>
                    <button class="btn btn-secondary" onclick="deselectAll()">
                        <i class="fas fa-times"></i> Ocultar Todos
                    </button>
                </div>
                <button class="btn btn-danger" onclick="restoreDefaults()">
                    <i class="fas fa-undo"></i> Restaurar Padrões
                </button>
            </div>

            <div id="saveMessage" style="display: none;"></div>

            <div class="slides-list" id="slidesList">
                <?php
                // Ordenar slides pela ordem configurada
                $orderedSlides = [];
                foreach ($availableSlides as $key => $slide) {
                    $orderedSlides[$key] = array_merge($slide, [
                        'key' => $key,
                        'order' => $currentConfig[$key]['order'] ?? 999,
                        'visible' => $currentConfig[$key]['visible'] ?? false,
                        'duration' => $currentConfig[$key]['duration'] ?? 10
                    ]);
                }
                uasort($orderedSlides, fn($a, $b) => $a['order'] - $b['order']);

                foreach ($orderedSlides as $key => $slide):
                ?>
                    <div class="slide-item <?= !$slide['visible'] ? 'hidden' : '' ?>" data-key="<?= $key ?>">
                        <div class="slide-drag-handle">
                            <i class="fas fa-grip-vertical"></i>
                        </div>

                        <div class="slide-info">
                            <div class="slide-header">
                                <div class="slide-icon" style="background: <?= $slide['color'] ?>">
                                    <i class="fas <?= $slide['icon'] ?>"></i>
                                </div>
                                <div class="slide-title"><?= htmlspecialchars($slide['name']) ?></div>
                            </div>
                            <div class="slide-description">
                                <?= htmlspecialchars($slide['description']) ?>
                            </div>
                        </div>

                        <div class="slide-controls">
                            <div class="duration-control">
                                <i class="fas fa-clock" style="color: #7f8c8d;"></i>
                                <input type="number"
                                       min="5"
                                       max="60"
                                       value="<?= $slide['duration'] ?>"
                                       class="slide-duration"
                                       data-key="<?= $key ?>">
                                <span style="color: #7f8c8d; font-size: 0.9em;">s</span>
                            </div>

                            <label class="toggle-switch">
                                <input type="checkbox"
                                       class="slide-toggle"
                                       data-key="<?= $key ?>"
                                       <?= $slide['visible'] ? 'checked' : '' ?>
                                       onchange="toggleSlideVisibility(this)">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script>
        // Inicializar Sortable para drag and drop
        const slidesList = document.getElementById('slidesList');
        new Sortable(slidesList, {
            handle: '.slide-drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function() {
                updateStats();
            }
        });

        function toggleSlideVisibility(checkbox) {
            const item = checkbox.closest('.slide-item');
            if (checkbox.checked) {
                item.classList.remove('hidden');
            } else {
                item.classList.add('hidden');
            }
            updateStats();
        }

        function selectAll() {
            document.querySelectorAll('.slide-toggle').forEach(toggle => {
                toggle.checked = true;
                toggle.closest('.slide-item').classList.remove('hidden');
            });
            updateStats();
        }

        function deselectAll() {
            document.querySelectorAll('.slide-toggle').forEach(toggle => {
                toggle.checked = false;
                toggle.closest('.slide-item').classList.add('hidden');
            });
            updateStats();
        }

        function updateStats() {
            const visible = document.querySelectorAll('.slide-toggle:checked').length;
            const total = document.querySelectorAll('.slide-toggle').length;
            let totalTime = 0;

            document.querySelectorAll('.slide-toggle:checked').forEach(toggle => {
                const key = toggle.dataset.key;
                const duration = parseInt(document.querySelector(`.slide-duration[data-key="${key}"]`).value) || 10;
                totalTime += duration;
            });

            document.querySelector('.stat-card:nth-child(1) .stat-value').textContent = visible;
            document.querySelector('.stat-card:nth-child(3) .stat-value').textContent = totalTime + 's';
            document.querySelector('.stat-card:nth-child(4) .stat-value').textContent =
                visible > 0 ? (totalTime / visible).toFixed(1) + 's' : '0s';
        }

        function saveConfiguration() {
            const config = {};
            const items = document.querySelectorAll('.slide-item');

            items.forEach((item, index) => {
                const key = item.dataset.key;
                const visible = item.querySelector('.slide-toggle').checked;
                const duration = parseInt(item.querySelector('.slide-duration').value) || 10;

                config[key] = {
                    visible: visible,
                    duration: duration,
                    order: index
                };
            });

            fetch('api/save-slides-config.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ config })
            })
            .then(response => response.json())
            .then(data => {
                const msgDiv = document.getElementById('saveMessage');
                if (data.success) {
                    msgDiv.className = 'alert alert-success';
                    msgDiv.innerHTML = '<strong><i class="fas fa-check-circle"></i> Sucesso!</strong> Configuração salva com sucesso.';
                } else {
                    msgDiv.className = 'alert alert-warning';
                    msgDiv.innerHTML = '<strong><i class="fas fa-exclamation-triangle"></i> Erro!</strong> ' + data.message;
                }
                msgDiv.style.display = 'block';

                setTimeout(() => {
                    msgDiv.style.display = 'none';
                }, 3000);
            });
        }

        function restoreDefaults() {
            if (!confirm('Tem certeza que deseja restaurar a configuração padrão? Todas as alterações serão perdidas.')) {
                return;
            }

            fetch('api/restore-default-slides.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erro ao restaurar configuração padrão');
                }
            });
        }

        // Atualizar stats quando duração mudar
        document.querySelectorAll('.slide-duration').forEach(input => {
            input.addEventListener('change', updateStats);
        });
    </script>
</body>
</html>
