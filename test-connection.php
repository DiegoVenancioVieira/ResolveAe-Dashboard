<?php
/**
 * Script de teste de conexão com o banco de dados GLPI
 * Execute este arquivo primeiro para verificar se a conexão está funcionando
 */

echo "<!DOCTYPE html>
<html>
<head>
    <title>Teste de Conexão - GLPI Dashboard</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 800px; 
            margin: 50px auto; 
            padding: 20px;
            background: #f4f4f4;
        }
        .success { 
            color: green; 
            background: #e8f5e9; 
            padding: 10px; 
            border-radius: 5px;
            margin: 10px 0;
        }
        .error { 
            color: red; 
            background: #ffebee; 
            padding: 10px; 
            border-radius: 5px;
            margin: 10px 0;
        }
        .info { 
            background: #e3f2fd; 
            padding: 10px; 
            border-radius: 5px;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background: #2196F3;
            color: white;
        }
        h1 { color: #333; }
        h2 { color: #555; margin-top: 30px; }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #2196F3;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .button:hover {
            background: #1976D2;
        }
    </style>
</head>
<body>
    <h1>🔧 Teste de Conexão - Dashboard GLPI</h1>";

// Carregar configuração
$configFile = __DIR__ . '/config/database.php';

if (!file_exists($configFile)) {
    echo "<div class='error'>❌ Arquivo de configuração não encontrado: $configFile</div>";
    echo "<div class='info'>📝 Certifique-se de que o arquivo config/database.php existe e está configurado corretamente.</div>";
    exit;
}

$config = require $configFile;

echo "<h2>1. Configuração do Banco de Dados</h2>";
echo "<table>";
echo "<tr><th>Parâmetro</th><th>Valor</th></tr>";
echo "<tr><td>Host</td><td>{$config['host']}</td></tr>";
echo "<tr><td>Porta</td><td>{$config['port']}</td></tr>";
echo "<tr><td>Banco de Dados</td><td>{$config['database']}</td></tr>";
echo "<tr><td>Usuário</td><td>{$config['username']}</td></tr>";
echo "<tr><td>Senha</td><td>" . str_repeat('*', strlen($config['password'])) . "</td></tr>";
echo "</table>";

echo "<h2>2. Teste de Conexão</h2>";

try {
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    echo "<div class='success'>✅ Conexão estabelecida com sucesso!</div>";
    
    // Verificar versão do MySQL
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo "<div class='info'>📊 Versão do MySQL: $version</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Erro ao conectar: " . $e->getMessage() . "</div>";
    echo "<h3>Possíveis soluções:</h3>";
    echo "<ul>";
    echo "<li>Verifique se o MySQL está rodando no host {$config['host']}:{$config['port']}</li>";
    echo "<li>Confirme se o usuário '{$config['username']}' tem permissão para acessar o banco '{$config['database']}'</li>";
    echo "<li>Verifique se a senha está correta</li>";
    echo "<li>Se estiver usando Docker, certifique-se de que a porta está mapeada corretamente</li>";
    echo "</ul>";
    exit;
}

echo "<h2>3. Verificação das Tabelas do GLPI</h2>";

try {
    // Verificar se as tabelas principais existem
    $tables = [
        'glpi_tickets' => 'Chamados',
        'glpi_users' => 'Usuários',
        'glpi_itilcategories' => 'Categorias',
        'glpi_tickets_users' => 'Relação Tickets-Usuários',
        'glpi_ticketsatisfactions' => 'Satisfação'
    ];
    
    echo "<table>";
    echo "<tr><th>Tabela</th><th>Descrição</th><th>Status</th><th>Registros</th></tr>";
    
    foreach ($tables as $table => $description) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        
        if ($stmt->rowCount() > 0) {
            // Contar registros
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "<tr>";
            echo "<td>$table</td>";
            echo "<td>$description</td>";
            echo "<td><span style='color:green'>✅ Existe</span></td>";
            echo "<td>$count registros</td>";
            echo "</tr>";
        } else {
            echo "<tr>";
            echo "<td>$table</td>";
            echo "<td>$description</td>";
            echo "<td><span style='color:red'>❌ Não encontrada</span></td>";
            echo "<td>-</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    
    // Teste rápido de consulta
    echo "<h2>4. Teste de Consulta</h2>";
    
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status IN (1,2,3) THEN 1 ELSE 0 END) as abertos,
                SUM(CASE WHEN status = 5 THEN 1 ELSE 0 END) as resolvidos,
                SUM(CASE WHEN status = 6 THEN 1 ELSE 0 END) as fechados
            FROM glpi_tickets 
            WHERE is_deleted = 0";
    
    $result = $pdo->query($sql)->fetch();
    
    echo "<div class='info'>";
    echo "<strong>Estatísticas Rápidas:</strong><br>";
    echo "Total de Chamados: {$result['total']}<br>";
    echo "Chamados Abertos: {$result['abertos']}<br>";
    echo "Chamados Resolvidos: {$result['resolvidos']}<br>";
    echo "Chamados Fechados: {$result['fechados']}";
    echo "</div>";
    
    echo "<div class='success'>✅ Todas as verificações foram concluídas com sucesso!</div>";
    echo "<div class='info'>📌 O sistema está pronto para uso.</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erro ao verificar tabelas: " . $e->getMessage() . "</div>";
    echo "<div class='info'>⚠️ Certifique-se de que este é realmente um banco de dados GLPI.</div>";
}

echo "<a href='index.php' class='button'>Acessar Dashboard</a>";
echo "<a href='api.php' class='button' style='margin-left: 10px;'>Testar API</a>";

echo "</body></html>";
