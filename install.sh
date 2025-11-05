#!/bin/bash

#############################################
# Script de Instalação - Dashboard GLPI
# Autor: Dashboard GLPI Installer
# Versão: 1.0
#############################################

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Função para imprimir mensagens coloridas
print_msg() {
    echo -e "${2}${1}${NC}"
}

# Banner
clear
echo "================================================"
echo "     INSTALADOR DO DASHBOARD GLPI v1.0         "
echo "================================================"
echo ""

# Verificar se está rodando como root
if [ "$EUID" -ne 0 ]; then 
    print_msg "Por favor, execute como root (sudo)" "$RED"
    exit 1
fi

# Detectar sistema operacional
if [ -f /etc/debian_version ]; then
    OS="debian"
elif [ -f /etc/redhat-release ]; then
    OS="redhat"
else
    print_msg "Sistema operacional não suportado" "$RED"
    exit 1
fi

print_msg "Sistema detectado: $OS" "$GREEN"
echo ""

# Menu de instalação
echo "Escolha o tipo de instalação:"
echo "1) Instalação completa (Apache + PHP + Dashboard)"
echo "2) Apenas instalar o Dashboard (Apache e PHP já configurados)"
echo "3) Apenas testar conexão com banco de dados"
echo "4) Sair"
echo ""
read -p "Opção: " opcao

case $opcao in
    1)
        print_msg "Iniciando instalação completa..." "$YELLOW"
        
        # Atualizar sistema
        print_msg "Atualizando sistema..." "$YELLOW"
        if [ "$OS" = "debian" ]; then
            apt-get update -y
            apt-get upgrade -y
        else
            yum update -y
        fi
        
        # Instalar Apache
        print_msg "Instalando Apache..." "$YELLOW"
        if [ "$OS" = "debian" ]; then
            apt-get install -y apache2
            systemctl enable apache2
            systemctl start apache2
        else
            yum install -y httpd
            systemctl enable httpd
            systemctl start httpd
        fi
        
        # Instalar PHP e extensões
        print_msg "Instalando PHP e extensões..." "$YELLOW"
        if [ "$OS" = "debian" ]; then
            apt-get install -y php php-mysql php-pdo php-json php-mbstring
            apt-get install -y libapache2-mod-php
        else
            yum install -y php php-mysqlnd php-pdo php-json php-mbstring
        fi
        
        # Reiniciar Apache
        print_msg "Reiniciando Apache..." "$YELLOW"
        if [ "$OS" = "debian" ]; then
            systemctl restart apache2
        else
            systemctl restart httpd
        fi
        ;;
    2)
        print_msg "Instalação apenas do Dashboard..." "$YELLOW"
        ;;
    3)
        print_msg "Modo de teste de conexão..." "$YELLOW"
        php test-connection.php
        exit 0
        ;;
    4)
        print_msg "Instalação cancelada" "$RED"
        exit 0
        ;;
    *)
        print_msg "Opção inválida" "$RED"
        exit 1
        ;;
esac

# Diretório de instalação
echo ""
read -p "Digite o diretório de instalação [/var/www/html/glpi-dashboard]: " install_dir
install_dir=${install_dir:-/var/www/html/glpi-dashboard}

# Criar diretório
print_msg "Criando diretório $install_dir..." "$YELLOW"
mkdir -p "$install_dir"

# Copiar arquivos
print_msg "Copiando arquivos do dashboard..." "$YELLOW"
cp -r ./* "$install_dir/"

# Configurar permissões
print_msg "Configurando permissões..." "$YELLOW"
if [ "$OS" = "debian" ]; then
    chown -R www-data:www-data "$install_dir"
else
    chown -R apache:apache "$install_dir"
fi
chmod -R 755 "$install_dir"

# Configurar banco de dados
echo ""
print_msg "=== CONFIGURAÇÃO DO BANCO DE DADOS ===" "$GREEN"
echo ""
read -p "Host do MySQL [localhost]: " db_host
db_host=${db_host:-localhost}

read -p "Porta do MySQL [3306]: " db_port
db_port=${db_port:-3306}

read -p "Nome do banco de dados GLPI: " db_name
while [ -z "$db_name" ]; do
    print_msg "O nome do banco é obrigatório!" "$RED"
    read -p "Nome do banco de dados GLPI: " db_name
done

read -p "Usuário do MySQL: " db_user
while [ -z "$db_user" ]; do
    print_msg "O usuário é obrigatório!" "$RED"
    read -p "Usuário do MySQL: " db_user
done

read -s -p "Senha do MySQL: " db_password
echo ""
while [ -z "$db_password" ]; do
    print_msg "A senha é obrigatória!" "$RED"
    read -s -p "Senha do MySQL: " db_password
    echo ""
done

# Criar arquivo de configuração
print_msg "Criando arquivo de configuração..." "$YELLOW"
cat > "$install_dir/config/database.php" << EOF
<?php
return [
    'host'     => '$db_host',
    'port'     => '$db_port',
    'database' => '$db_name',
    'username' => '$db_user',
    'password' => '$db_password',
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]
];
EOF

# Testar conexão
echo ""
print_msg "Testando conexão com o banco de dados..." "$YELLOW"
php "$install_dir/test-connection.php" > /tmp/test_result.txt 2>&1

if grep -q "Conexão estabelecida com sucesso" /tmp/test_result.txt; then
    print_msg "✅ Conexão com banco de dados OK!" "$GREEN"
else
    print_msg "❌ Erro na conexão com o banco de dados" "$RED"
    print_msg "Verifique as configurações em: $install_dir/config/database.php" "$YELLOW"
fi

# Configurar Apache (opcional)
echo ""
read -p "Deseja configurar um VirtualHost no Apache? (s/n): " config_apache
if [ "$config_apache" = "s" ] || [ "$config_apache" = "S" ]; then
    read -p "Digite o domínio [dashboard.glpi.local]: " domain
    domain=${domain:-dashboard.glpi.local}
    
    if [ "$OS" = "debian" ]; then
        vhost_file="/etc/apache2/sites-available/glpi-dashboard.conf"
    else
        vhost_file="/etc/httpd/conf.d/glpi-dashboard.conf"
    fi
    
    cat > "$vhost_file" << EOF
<VirtualHost *:80>
    ServerName $domain
    DocumentRoot $install_dir
    
    <Directory $install_dir>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/glpi-dashboard-error.log
    CustomLog \${APACHE_LOG_DIR}/glpi-dashboard-access.log combined
</VirtualHost>
EOF
    
    if [ "$OS" = "debian" ]; then
        a2ensite glpi-dashboard
        systemctl reload apache2
    else
        systemctl reload httpd
    fi
    
    print_msg "VirtualHost configurado para: $domain" "$GREEN"
    print_msg "Adicione '$domain' ao seu arquivo /etc/hosts ou DNS" "$YELLOW"
fi

# Criar script de inicialização para TV/Kiosk
echo ""
read -p "Deseja criar script para modo TV/Kiosk? (s/n): " create_kiosk
if [ "$create_kiosk" = "s" ] || [ "$create_kiosk" = "S" ]; then
    cat > "$install_dir/kiosk-mode.sh" << 'EOF'
#!/bin/bash
# Script para iniciar dashboard em modo Kiosk

# Desabilitar screensaver
xset s off
xset -dpms
xset s noblank

# Iniciar navegador em modo kiosk
chromium-browser \
    --noerrdialogs \
    --disable-infobars \
    --kiosk \
    --disable-session-crashed-bubble \
    --disable-restore-session-state \
    --start-fullscreen \
    --app=http://localhost/glpi-dashboard/ &

# Manter script rodando
while true; do
    sleep 10
done
EOF
    
    chmod +x "$install_dir/kiosk-mode.sh"
    print_msg "Script kiosk criado em: $install_dir/kiosk-mode.sh" "$GREEN"
fi

# Criar serviço systemd (opcional)
echo ""
read -p "Deseja criar serviço systemd para auto-inicialização? (s/n): " create_service
if [ "$create_service" = "s" ] || [ "$create_service" = "S" ]; then
    cat > "/etc/systemd/system/glpi-dashboard.service" << EOF
[Unit]
Description=GLPI Dashboard Kiosk Mode
After=network.target

[Service]
Type=simple
User=www-data
ExecStart=$install_dir/kiosk-mode.sh
Restart=always

[Install]
WantedBy=multi-user.target
EOF
    
    systemctl daemon-reload
    print_msg "Serviço criado. Para habilitar: systemctl enable glpi-dashboard" "$GREEN"
fi

# Finalização
echo ""
echo "================================================"
print_msg "     INSTALAÇÃO CONCLUÍDA COM SUCESSO!         " "$GREEN"
echo "================================================"
echo ""
print_msg "📊 Dashboard instalado em: $install_dir" "$GREEN"
print_msg "🌐 Acesse em: http://$(hostname -I | awk '{print $1}')/glpi-dashboard/" "$GREEN"
print_msg "🧪 Teste de conexão: http://$(hostname -I | awk '{print $1}')/glpi-dashboard/test-connection.php" "$GREEN"
print_msg "📖 Documentação: $install_dir/README.md" "$GREEN"
echo ""
print_msg "=== PRÓXIMOS PASSOS ===" "$YELLOW"
echo "1. Teste o dashboard no navegador"
echo "2. Configure a TV para acessar o dashboard"
echo "3. Ajuste os intervalos de atualização se necessário"
echo "4. Configure autenticação se desejar (veja README.md)"
echo ""
print_msg "Problemas? Verifique:" "$YELLOW"
echo "- Logs do Apache: /var/log/apache2/ ou /var/log/httpd/"
echo "- Teste de conexão: test-connection.php"
echo "- Console do navegador (F12)"
echo ""
print_msg "Dashboard GLPI instalado com sucesso! 🎉" "$GREEN"
