<?php
/**
 * Interface de Configuração de Metas
 */

require_once __DIR__ . '/includes/GoalsConfig.php';

$goalsConfig = new GoalsConfig();
$goals = $goalsConfig->getGoals();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuração de Metas - GLPI Dashboard</title>
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
            max-width: 1200px;
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

        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
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

        .btn-secondary:hover {
            background: #7f8c8d;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .goals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .goal-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            border-left: 4px solid #667eea;
            transition: transform 0.3s;
        }

        .goal-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .goal-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .goal-title {
            font-size: 1.1em;
            font-weight: bold;
            color: #2c3e50;
        }

        .goal-description {
            font-size: 0.85em;
            color: #7f8c8d;
            margin-bottom: 15px;
        }

        .goal-field {
            margin-bottom: 15px;
        }

        .goal-field label {
            display: block;
            font-size: 0.85em;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .goal-field input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .goal-field input:focus {
            outline: none;
            border-color: #667eea;
        }

        .goal-thresholds {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            padding: 15px;
            background: white;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .threshold-item {
            text-align: center;
        }

        .threshold-label {
            font-size: 0.75em;
            color: #7f8c8d;
            margin-bottom: 5px;
        }

        .threshold-value {
            font-weight: bold;
            font-size: 1.1em;
        }

        .threshold-value.warning {
            color: #f39c12;
        }

        .threshold-value.critical {
            color: #e74c3c;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
        }

        .modal-header {
            font-size: 1.5em;
            margin-bottom: 20px;
            color: #2c3e50;
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

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
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
                <i class="fas fa-bullseye"></i>
                Configuração de Metas
            </h1>
            <p>Defina e ajuste as metas para os indicadores de desempenho</p>
        </div>

        <div class="content">
            <div class="alert alert-info">
                <strong><i class="fas fa-info-circle"></i> Informação:</strong>
                As metas definidas aqui serão usadas para avaliar o desempenho nos dashboards e relatórios.
            </div>

            <div class="actions-bar">
                <div>
                    <button class="btn btn-primary" onclick="saveAllGoals()">
                        <i class="fas fa-save"></i> Salvar Todas as Metas
                    </button>
                    <button class="btn btn-secondary" onclick="showAddGoalModal()">
                        <i class="fas fa-plus"></i> Adicionar Nova Meta
                    </button>
                </div>
                <button class="btn btn-danger" onclick="restoreDefaults()">
                    <i class="fas fa-undo"></i> Restaurar Padrões
                </button>
            </div>

            <div id="saveMessage" style="display: none;"></div>

            <div class="goals-grid" id="goalsGrid">
                <?php foreach ($goals as $key => $goal): ?>
                    <div class="goal-card" data-key="<?= $key ?>">
                        <div class="goal-header">
                            <div class="goal-title"><?= htmlspecialchars($goal['label']) ?></div>
                        </div>

                        <div class="goal-description">
                            <?= htmlspecialchars($goal['description'] ?? '') ?>
                        </div>

                        <div class="goal-field">
                            <label>Meta (Target)</label>
                            <input type="number" step="0.1"
                                   class="goal-target"
                                   value="<?= $goal['target'] ?>"
                                   data-key="<?= $key ?>"
                                   data-field="target">
                            <small style="color: #7f8c8d;"><?= htmlspecialchars($goal['unit']) ?></small>
                        </div>

                        <div class="goal-thresholds">
                            <div class="threshold-item">
                                <div class="threshold-label">⚠️ Aviso</div>
                                <input type="number" step="0.1"
                                       class="threshold-value warning"
                                       value="<?= $goal['warning_threshold'] ?>"
                                       data-key="<?= $key ?>"
                                       data-field="warning_threshold"
                                       style="border: none; text-align: center; background: transparent; width: 100%;">
                            </div>
                            <div class="threshold-item">
                                <div class="threshold-label">🔴 Crítico</div>
                                <input type="number" step="0.1"
                                       class="threshold-value critical"
                                       value="<?= $goal['critical_threshold'] ?>"
                                       data-key="<?= $key ?>"
                                       data-field="critical_threshold"
                                       style="border: none; text-align: center; background: transparent; width: 100%;">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Modal para adicionar nova meta -->
    <div class="modal" id="addGoalModal">
        <div class="modal-content">
            <h2 class="modal-header">
                <i class="fas fa-plus-circle"></i> Adicionar Nova Meta
            </h2>
            <form id="addGoalForm">
                <div class="goal-field">
                    <label>Chave (ID único)</label>
                    <input type="text" id="newGoalKey" required>
                </div>
                <div class="goal-field">
                    <label>Nome da Meta</label>
                    <input type="text" id="newGoalLabel" required>
                </div>
                <div class="goal-field">
                    <label>Descrição</label>
                    <input type="text" id="newGoalDescription">
                </div>
                <div class="goal-field">
                    <label>Valor da Meta</label>
                    <input type="number" step="0.1" id="newGoalTarget" required>
                </div>
                <div class="goal-field">
                    <label>Unidade</label>
                    <input type="text" id="newGoalUnit" placeholder="%, horas, chamados, etc.">
                </div>
                <div class="goal-field">
                    <label>Tipo</label>
                    <select id="newGoalType" required>
                        <option value="percentage">Percentual</option>
                        <option value="number">Número</option>
                        <option value="hours">Horas</option>
                        <option value="rating">Avaliação</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        <i class="fas fa-plus"></i> Adicionar
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeAddGoalModal()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function saveAllGoals() {
            const goals = {};
            const cards = document.querySelectorAll('.goal-card');

            cards.forEach(card => {
                const key = card.dataset.key;
                const inputs = card.querySelectorAll('input[data-key]');

                goals[key] = {};
                inputs.forEach(input => {
                    const field = input.dataset.field;
                    goals[key][field] = parseFloat(input.value);
                });
            });

            fetch('api/save-goals.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ goals })
            })
            .then(response => response.json())
            .then(data => {
                const msgDiv = document.getElementById('saveMessage');
                if (data.success) {
                    msgDiv.className = 'alert alert-success';
                    msgDiv.innerHTML = '<strong><i class="fas fa-check-circle"></i> Sucesso!</strong> Metas salvas com sucesso.';
                } else {
                    msgDiv.className = 'alert alert-warning';
                    msgDiv.innerHTML = '<strong><i class="fas fa-exclamation-triangle"></i> Erro!</strong> ' + data.message;
                }
                msgDiv.style.display = 'block';

                setTimeout(() => {
                    msgDiv.style.display = 'none';
                }, 3000);
            })
            .catch(error => {
                alert('Erro ao salvar metas: ' + error.message);
            });
        }

        function restoreDefaults() {
            if (!confirm('Tem certeza que deseja restaurar as metas padrão? Todas as alterações serão perdidas.')) {
                return;
            }

            fetch('api/restore-default-goals.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erro ao restaurar metas padrão');
                }
            });
        }

        function showAddGoalModal() {
            document.getElementById('addGoalModal').classList.add('active');
        }

        function closeAddGoalModal() {
            document.getElementById('addGoalModal').classList.remove('active');
            document.getElementById('addGoalForm').reset();
        }

        document.getElementById('addGoalForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const goalData = {
                key: document.getElementById('newGoalKey').value,
                label: document.getElementById('newGoalLabel').value,
                description: document.getElementById('newGoalDescription').value,
                target: parseFloat(document.getElementById('newGoalTarget').value),
                unit: document.getElementById('newGoalUnit').value,
                type: document.getElementById('newGoalType').value,
                warning_threshold: parseFloat(document.getElementById('newGoalTarget').value) * 0.8,
                critical_threshold: parseFloat(document.getElementById('newGoalTarget').value) * 0.6
            };

            fetch('api/add-goal.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(goalData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erro ao adicionar meta: ' + data.message);
                }
            });
        });
    </script>
</body>
</html>
