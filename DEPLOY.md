# 🚀 GUIA DE DEPLOY - TECH-ELETRO

## 📋 Pré-requisitos

### Servidor
- Ubuntu 20.04+ / Debian 10+ / CentOS 8+
- Mínimo 1GB RAM
- 20GB de espaço em disco
- Acesso SSH com sudo

### Software
- PHP 7.4+ (Recomendado 8.0+)
- MySQL 5.7+ / MariaDB 10.2+
- Apache 2.4+ ou Nginx 1.18+
- Composer
- Git

---

## 🔧 INSTALAÇÃO NO SERVIDOR

### 1. Atualizar Sistema

```bash
# Ubuntu/Debian
sudo apt update && sudo apt upgrade -y

# CentOS/RHEL
sudo yum update -y
```

### 2. Instalar PHP e Extensões

```bash
# Ubuntu/Debian
sudo apt install -y php8.0 php8.0-cli php8.0-fpm php8.0-mysql \
  php8.0-mbstring php8.0-xml php8.0-curl php8.0-zip \
  php8.0-gd php8.0-intl php8.0-bcmath

# CentOS/RHEL
sudo yum install -y php php-cli php-fpm php-mysqlnd \
  php-mbstring php-xml php-curl php-zip \
  php-gd php-intl php-bcmath
```

### 3. Instalar MySQL

```bash
# Ubuntu/Debian
sudo apt install -y mysql-server

# CentOS/RHEL
sudo yum install -y mysql-server

# Iniciar MySQL
sudo systemctl start mysql
sudo systemctl enable mysql

# Configurar MySQL (definir senha root)
sudo mysql_secure_installation
```

### 4. Instalar Apache ou Nginx

#### Opção A: Apache

```bash
# Ubuntu/Debian
sudo apt install -y apache2 libapache2-mod-php8.0

# Habilitar módulos necessários
sudo a2enmod rewrite
sudo a2enmod ssl
sudo systemctl restart apache2
```

#### Opção B: Nginx

```bash
# Ubuntu/Debian
sudo apt install -y nginx

# Iniciar Nginx
sudo systemctl start nginx
sudo systemctl enable nginx
```

### 5. Instalar Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

---

## 📦 DEPLOY DA APLICAÇÃO

### 1. Criar Usuário para Aplicação

```bash
# Criar usuário
sudo useradd -m -s /bin/bash techeletro

# Criar diretório da aplicação
sudo mkdir -p /var/www/techeletro
sudo chown techeletro:techeletro /var/www/techeletro
```

### 2. Clonar Repositório

```bash
# Trocar para o usuário
sudo su - techeletro

# Clonar repositório
cd /var/www/techeletro
git clone https://github.com/joaopaulopietchaki/Tech-Eletro-gest-o-.git .

# OU fazer upload via FTP/SFTP
```

### 3. Instalar Dependências

```bash
cd /var/www/techeletro
composer install --no-dev --optimize-autoloader
```

### 4. Criar Banco de Dados

```bash
# Entrar no MySQL
mysql -u root -p

# Criar banco
CREATE DATABASE tech_eletro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Criar usuário
CREATE USER 'techeletro_user'@'localhost' IDENTIFIED BY 'SENHA_FORTE_AQUI';
GRANT ALL PRIVILEGES ON tech_eletro.* TO 'techeletro_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Importar schema
mysql -u techeletro_user -p tech_eletro < database_schema.sql
```

### 5. Configurar Aplicação

```bash
# Copiar arquivo de configuração
cp config.example.php config.php

# Editar configurações
nano config.php

# Configurar:
# - Credenciais do banco de dados
# - URL base do sistema
# - Chaves de segurança (SECRET_KEY, PASSWORD_SALT)
# - Configurações de e-mail
```

### 6. Definir Permissões

```bash
# Permissões dos arquivos
cd /var/www/techeletro
sudo chown -R techeletro:www-data .
sudo find . -type f -exec chmod 644 {} \;
sudo find . -type d -exec chmod 755 {} \;

# Permissões especiais para diretórios de escrita
sudo chmod -R 775 uploads/ backups/ logs/
sudo chown -R techeletro:www-data uploads/ backups/ logs/
```

---

## 🌐 CONFIGURAÇÃO DO SERVIDOR WEB

### APACHE

#### 1. Criar VirtualHost

```bash
sudo nano /etc/apache2/sites-available/techeletro.conf
```

Adicionar:

```apache
<VirtualHost *:80>
    ServerName seu-dominio.com.br
    ServerAlias www.seu-dominio.com.br
    
    DocumentRoot /var/www/techeletro
    
    <Directory /var/www/techeletro>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Logs
    ErrorLog ${APACHE_LOG_DIR}/techeletro-error.log
    CustomLog ${APACHE_LOG_DIR}/techeletro-access.log combined
    
    # Segurança adicional
    <Directory /var/www/techeletro/backups>
        Require all denied
    </Directory>
    
    <Directory /var/www/techeletro/logs>
        Require all denied
    </Directory>
</VirtualHost>
```

#### 2. Habilitar Site

```bash
# Desabilitar site padrão
sudo a2dissite 000-default.conf

# Habilitar novo site
sudo a2ensite techeletro.conf

# Testar configuração
sudo apache2ctl configtest

# Reiniciar Apache
sudo systemctl restart apache2
```

### NGINX

#### 1. Criar Configuração

```bash
sudo nano /etc/nginx/sites-available/techeletro
```

Adicionar:

```nginx
server {
    listen 80;
    listen [::]:80;
    
    server_name seu-dominio.com.br www.seu-dominio.com.br;
    root /var/www/techeletro;
    
    index index.php index.html;
    
    # Logs
    access_log /var/log/nginx/techeletro-access.log;
    error_log /var/log/nginx/techeletro-error.log;
    
    # Segurança
    location ~ /\. {
        deny all;
    }
    
    location /backups {
        deny all;
    }
    
    location /logs {
        deny all;
    }
    
    # PHP
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Cache estático
    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

#### 2. Habilitar Site

```bash
# Criar link simbólico
sudo ln -s /etc/nginx/sites-available/techeletro /etc/nginx/sites-enabled/

# Remover site padrão
sudo rm /etc/nginx/sites-enabled/default

# Testar configuração
sudo nginx -t

# Reiniciar Nginx
sudo systemctl restart nginx
```

---

## 🔒 CONFIGURAR HTTPS (SSL/TLS)

### Usando Let's Encrypt (Certbot)

```bash
# Instalar Certbot
sudo apt install -y certbot

# Apache
sudo apt install -y python3-certbot-apache
sudo certbot --apache -d seu-dominio.com.br -d www.seu-dominio.com.br

# Nginx
sudo apt install -y python3-certbot-nginx
sudo certbot --nginx -d seu-dominio.com.br -d www.seu-dominio.com.br

# Renovação automática
sudo certbot renew --dry-run
```

### Configuração Manual SSL

Se você tem certificados próprios:

**Apache:**
```apache
<VirtualHost *:443>
    ServerName seu-dominio.com.br
    
    SSLEngine on
    SSLCertificateFile /caminho/para/certificado.crt
    SSLCertificateKeyFile /caminho/para/chave.key
    SSLCertificateChainFile /caminho/para/chain.crt
    
    # Restante da configuração...
</VirtualHost>
```

**Nginx:**
```nginx
server {
    listen 443 ssl http2;
    
    ssl_certificate /caminho/para/certificado.crt;
    ssl_certificate_key /caminho/para/chave.key;
    
    # Configurações SSL recomendadas
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    
    # Restante da configuração...
}
```

---

## 🔐 SEGURANÇA ADICIONAL

### 1. Firewall

```bash
# UFW (Ubuntu)
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw enable

# Firewalld (CentOS)
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --permanent --add-service=ssh
sudo firewall-cmd --reload
```

### 2. Fail2ban (Proteção contra Brute Force)

```bash
# Instalar
sudo apt install -y fail2ban

# Configurar
sudo nano /etc/fail2ban/jail.local
```

Adicionar:

```ini
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5

[sshd]
enabled = true

[apache-auth]
enabled = true

[nginx-limit-req]
enabled = true
```

```bash
# Reiniciar
sudo systemctl restart fail2ban
```

### 3. Configurações PHP Seguras

```bash
sudo nano /etc/php/8.0/fpm/php.ini
```

Ajustar:

```ini
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log
max_execution_time = 30
max_input_time = 60
memory_limit = 128M
post_max_size = 8M
upload_max_filesize = 5M
allow_url_fopen = Off
allow_url_include = Off
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
```

---

## 📊 MONITORAMENTO E LOGS

### Configurar Log Rotation

```bash
sudo nano /etc/logrotate.d/techeletro
```

Adicionar:

```
/var/www/techeletro/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 techeletro www-data
    sharedscripts
}
```

### Monitoramento de Espaço em Disco

```bash
# Criar script de monitoramento
sudo nano /usr/local/bin/check_disk_space.sh
```

```bash
#!/bin/bash
THRESHOLD=90
CURRENT=$(df -h / | tail -1 | awk '{print $5}' | sed 's/%//')

if [ $CURRENT -gt $THRESHOLD ]; then
    echo "ALERTA: Uso de disco em ${CURRENT}%" | mail -s "Alerta Disco" admin@seu-dominio.com
fi
```

```bash
# Tornar executável
sudo chmod +x /usr/local/bin/check_disk_space.sh

# Adicionar ao cron
sudo crontab -e
# Adicionar linha:
0 */6 * * * /usr/local/bin/check_disk_space.sh
```

---

## 🔄 BACKUP AUTOMATIZADO

### Script de Backup

```bash
sudo nano /usr/local/bin/backup_techeletro.sh
```

```bash
#!/bin/bash

# Configurações
DB_NAME="tech_eletro"
DB_USER="techeletro_user"
DB_PASS="sua_senha"
BACKUP_DIR="/var/backups/techeletro"
DATE=$(date +%Y%m%d_%H%M%S)

# Criar diretório se não existir
mkdir -p $BACKUP_DIR

# Backup do banco de dados
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup dos arquivos
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/techeletro --exclude='/var/www/techeletro/vendor'

# Remover backups com mais de 30 dias
find $BACKUP_DIR -type f -mtime +30 -delete

echo "Backup concluído: $DATE"
```

```bash
# Tornar executável
sudo chmod +x /usr/local/bin/backup_techeletro.sh

# Agendar backup diário às 2h
sudo crontab -e
# Adicionar:
0 2 * * * /usr/local/bin/backup_techeletro.sh >> /var/log/backup_techeletro.log 2>&1
```

---

## 🧪 VERIFICAÇÃO PÓS-DEPLOY

### Checklist

- [ ] Sistema acessível via navegador
- [ ] Login funciona corretamente
- [ ] HTTPS configurado (se aplicável)
- [ ] Backup automático funcionando
- [ ] Logs sendo gerados corretamente
- [ ] Permissões de arquivos corretas
- [ ] Firewall configurado
- [ ] Fail2ban ativo
- [ ] SSL válido (se aplicável)
- [ ] E-mails sendo enviados

### Comandos de Teste

```bash
# Testar conexão MySQL
mysql -u techeletro_user -p tech_eletro -e "SHOW TABLES;"

# Verificar permissões
ls -la /var/www/techeletro/

# Verificar logs
tail -f /var/www/techeletro/logs/error.log

# Testar HTTPS
curl -I https://seu-dominio.com.br

# Verificar certificado SSL
openssl s_client -connect seu-dominio.com.br:443 -servername seu-dominio.com.br
```

---

## 📝 ATUALIZAÇÃO DO SISTEMA

```bash
# 1. Fazer backup
/usr/local/bin/backup_techeletro.sh

# 2. Entrar no diretório
cd /var/www/techeletro

# 3. Salvar alterações locais (se houver)
git stash

# 4. Atualizar código
git pull origin main

# 5. Restaurar alterações (se necessário)
git stash pop

# 6. Atualizar dependências
composer install --no-dev --optimize-autoloader

# 7. Executar migrations (se houver)
# php migrate.php

# 8. Limpar cache
rm -rf cache/*

# 9. Verificar permissões
sudo chown -R techeletro:www-data .
```

---

## 🆘 TROUBLESHOOTING

### Erro 500 - Internal Server Error
```bash
# Verificar logs
tail -f /var/log/apache2/techeletro-error.log
tail -f /var/www/techeletro/logs/error.log

# Verificar permissões
ls -la /var/www/techeletro
```

### Erro de Conexão com Banco
```bash
# Verificar se MySQL está rodando
sudo systemctl status mysql

# Testar conexão
mysql -u techeletro_user -p

# Verificar credenciais no config.php
```

### Upload de Arquivos não Funciona
```bash
# Verificar permissões
sudo chmod 775 /var/www/techeletro/uploads
sudo chown techeletro:www-data /var/www/techeletro/uploads

# Verificar configuração PHP
php -i | grep upload_max_filesize
```

---

## 📞 SUPORTE

Em caso de problemas:

1. Verificar logs do sistema
2. Consultar documentação
3. Abrir issue no GitHub
4. Contatar suporte técnico

---

**Deploy realizado com sucesso! 🎉**
