# 📊 Dashboard GLPI - Sistema de Monitoramento em Tempo Real

Dashboard profissional para monitoramento de chamados do GLPI, desenvolvido para exibição em TVs no setor de suporte.

## ✨ Características

### 🎯 Funcionalidades Principais
- **Atualização automática** a cada 30 segundos
- **Rotação de slides** a cada 15 segundos
- **3 telas de informações** com métricas diferentes
- **Gráficos interativos** com Chart.js
- **Design responsivo** otimizado para TVs
- **Modo noturno** com tema escuro elegante

### 📈 Métricas Monitoradas

#### Slide 1 - Visão Geral
- Total de chamados abertos
- Chamados por status (Novo, Atribuído, Planejado, Pendente)
- Distribuição por prioridade (gráfico de barras)
- Lista de chamados vencidos (SLA)

#### Slide 2 - Performance da Equipe
- Ranking de técnicos por chamados
- Tempo médio de resolução
- Total de chamados resolvidos (30 dias)
- Índice de satisfação dos usuários

#### Slide 3 - Análise por Categoria
- Top 10 categorias mais demandadas (gráfico pizza)
- Comparação diária de chamados
- Tendências (hoje vs ontem)
- Volume semanal e mensal

## 📋 Pré-requisitos

- PHP 7.4 ou superior
- MySQL/MariaDB com banco de dados GLPI
- Servidor web (Apache/Nginx)
- Extensão PDO MySQL habilitada no PHP

## 🚀 Instalação

### 1️⃣ Clone ou baixe os arquivos

Copie toda a pasta do projeto para o diretório web do seu servidor:

```bash
# Apache (Ubuntu/Debian)
sudo cp -r dashboard /var/www/html/

# Ou para Nginx
sudo cp -r dashboard /usr/share/nginx/html/
```

### 2️⃣ Configure as permissões

```bash
cd /var/www/html/glpi-dashboard
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
```

### 3️⃣ Configure o banco de dados

Edite o arquivo `config/database.php` com os dados do seu banco GLPI:

```php
return [
    'host'     => 'localhost',     // IP ou hostname do servidor MySQL
    'port'     => '3306',         // Porta do MySQL (padrão 3306)
    'database' => 'glpi',         // Nome do banco do GLPI
    'username' => 'glpi_user',    // Usuário do banco
    'password' => '********',     // Senha do banco
    'charset'  => 'utf8mb4',
];
```

### 4️⃣ Teste a conexão

Acesse no navegador:
```
http://seu-servidor/glpi-dashboard/test-connection.php
```

Este script irá:
- Verificar a conexão com o banco
- Validar se as tabelas do GLPI existem
- Mostrar estatísticas básicas
- Indicar possíveis problemas

### 5️⃣ Acesse o dashboard

Se tudo estiver OK, acesse:
```
http://seu-servidor/glpi-dashboard/
```

## ⚙️ Configuração Avançada

### Ajustar intervalos de atualização

Edite o arquivo `assets/js/dashboard.js`:

```javascript
const CONFIG = {
    updateInterval: 30000,   // Dados (ms) - padrão 30s
    slideInterval: 15000,    // Slides (ms) - padrão 15s
    enableAutoSlide: true,   // Rotação automática
    debugMode: false        // Modo debug
};
```

### Configurar para TV/Kiosk Mode

#### Para TVs com navegador:
1. Acesse o dashboard
2. Pressione `F11` para modo tela cheia
3. Configure o navegador para iniciar automaticamente

#### Para Raspberry Pi / Mini PC:
```bash
# Instalar Chromium em modo kiosk
sudo apt-get install chromium-browser

# Criar script de inicialização
nano ~/kiosk.sh
```

Adicione:
```bash
#!/bin/bash
chromium-browser --noerrdialogs --disable-infobars --kiosk \
  --disable-session-crashed-bubble \
  http://seu-servidor/glpi-dashboard/ &
```

## 📁 Estrutura de Arquivos

```
dashboard/
├── index.php                 # Dashboard principal
├── api.php                   # API JSON para dados
├── test-connection.php       # Teste de conexão
├── config/
│   └── database.php         # Configuração do banco
├── includes/
│   ├── Database.php         # Classe de conexão
│   └── GLPIMetrics.php      # Classe de métricas
└── assets/
    ├── css/
    │   └── style.css        # Estilos do dashboard
    └── js/
        └── dashboard.js     # Lógica JavaScript
```

## 🔧 Troubleshooting

### Erro de conexão com o banco
- Verifique se o MySQL está acessível do servidor web
- Confirme usuário e senha no arquivo `config/database.php`
- Teste com: `mysql -h IP -u usuario -p banco`

### Dados não aparecem
- Verifique no console do navegador (F12) por erros
- Acesse `api.php` diretamente para ver o JSON
- Confirme se as tabelas GLPI têm a estrutura esperada

### Dashboard não atualiza
- Verifique se o JavaScript está carregado
- Confirme que não há bloqueios de CORS
- Teste o modo debug (pressione F12 no dashboard)

### Performance lenta
- Adicione índices nas tabelas do GLPI se necessário
- Considere usar cache para consultas pesadas
- Ajuste os intervalos de atualização

## 🎨 Personalização

### Cores e Tema

Edite `assets/css/style.css`:

```css
:root {
    --primary-color: #2563eb;    /* Cor principal */
    --bg-color: #0f172a;          /* Fundo */
    --card-bg: #1e293b;           /* Fundo dos cards */
}
```

### Adicionar novos KPIs

1. Adicione o método em `includes/GLPIMetrics.php`
2. Inclua no método `getAllMetrics()`
3. Adicione a visualização em `index.php`
4. Atualize via JavaScript em `assets/js/dashboard.js`

## 📝 Queries SQL Úteis

### Chamados por prioridade hoje
```sql
SELECT priority, COUNT(*) as total
FROM glpi_tickets
WHERE DATE(date_creation) = CURDATE()
  AND status IN (1,2,3)
GROUP BY priority;
```

### Técnicos com mais chamados abertos
```sql
SELECT u.firstname, u.realname, COUNT(*) as total
FROM glpi_tickets t
JOIN glpi_tickets_users tu ON t.id = tu.tickets_id
JOIN glpi_users u ON tu.users_id = u.id
WHERE t.status IN (1,2,3)
  AND tu.type = 2
GROUP BY u.id
ORDER BY total DESC;
```

## 🔒 Segurança

### Recomendações
1. **Acesso restrito**: Configure firewall/proxy para acesso apenas interno
2. **HTTPS**: Use certificado SSL para conexões seguras
3. **Autenticação**: Adicione autenticação básica no Apache/Nginx:

```apache
# .htaccess na pasta do dashboard
AuthType Basic
AuthName "Dashboard GLPI"
AuthUserFile /etc/apache2/.htpasswd
Require valid-user
```

4. **Usuário read-only** no MySQL:
```sql
CREATE USER 'glpi_dashboard'@'localhost' IDENTIFIED BY '********';
GRANT SELECT ON glpi.* TO 'glpi_dashboard'@'localhost';
FLUSH PRIVILEGES;
```

## 🚦 Status dos Chamados GLPI

Para referência, os status no GLPI são:
- **1** = Novo (New)
- **2** = Em andamento/Atribuído (Processing assigned)
- **3** = Em andamento/Planejado (Processing planned)
- **4** = Pendente (Pending)
- **5** = Resolvido (Solved)
- **6** = Fechado (Closed)

## 📱 Versão Mobile

O dashboard é responsivo, mas para melhor experiência em dispositivos móveis, você pode:
1. Desabilitar rotação automática
2. Aumentar intervalos de atualização
3. Simplificar gráficos

## 🆘 Suporte

Em caso de dúvidas ou problemas:
1. Verifique o arquivo de log do PHP
2. Teste com `test-connection.php`
3. Ative o modo debug (F12 no dashboard)
4. Verifique o console do navegador

## 📄 Licença

Este projeto é de código aberto e pode ser usado livremente.

## 🎉 Dicas para Melhor Visualização em TV

1. **Configure a TV**:
   - Modo de imagem: PC/Game (reduz processamento)
   - Desative economia de energia
   - Ajuste overscan se necessário

2. **Otimize o navegador**:
   - Desative extensões desnecessárias
   - Use modo incógnito para evitar cache
   - Configure zoom em 100% ou ajuste via CSS

3. **Hardware recomendado**:
   - Raspberry Pi 4 ou superior
   - Intel NUC
   - Chromecast com Google TV (modo apresentação)

---

**Desenvolvido para melhorar a gestão e visualização de chamados do GLPI** 🚀
