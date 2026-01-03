# 🔧 Tech-Eletro - Sistema de Gestão

<div align="center">

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-00000F?style=for-the-badge&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-563D7C?style=for-the-badge&logo=bootstrap&logoColor=white)

**Sistema completo de gestão para assistência técnica de eletrônicos**

[Instalação](#-instalação) • [Funcionalidades](#-funcionalidades) • [Documentação](#-documentação) • [Contribuir](#-como-contribuir)

</div>

---

## 📋 Sobre o Projeto

O **Tech-Eletro** é um sistema de gestão desenvolvido especificamente para assistências técnicas de eletrônicos. Oferece controle completo de clientes, orçamentos, ordens de serviço, produtos, financeiro e muito mais.

### 🎯 Objetivo

Facilitar o dia a dia de assistências técnicas através de um sistema completo, intuitivo e seguro para gerenciar todos os aspectos do negócio.

---

## ✨ Funcionalidades

### 👥 Gestão de Clientes
- ✅ Cadastro completo de clientes
- ✅ Histórico de serviços por cliente
- ✅ Busca e filtros avançados
- ✅ Visualização detalhada de informações

### 💰 Orçamentos
- ✅ Criação e edição de orçamentos
- ✅ Aprovação digital de orçamentos
- ✅ Conversão automática para ordem de serviço
- ✅ Geração de PDF profissional
- ✅ Histórico de alterações
- ✅ Sistema de assinatura digital

### 📝 Ordens de Serviço (OS)
- ✅ Gerenciamento completo de OS
- ✅ Controle de status (Aberta, Em Andamento, Concluída)
- ✅ Calendário de eventos
- ✅ Geração de relatórios
- ✅ Impressão de OS

### 📦 Produtos e Estoque
- ✅ Cadastro de produtos/peças
- ✅ Controle de estoque
- ✅ Gestão de preços
- ✅ Sistema de busca rápida
- ✅ Histórico de movimentações

### 💵 Controle Financeiro
- ✅ Registro de gastos operacionais
- ✅ Controle de deslocamentos
- ✅ Reservas financeiras
- ✅ Histórico de saques
- ✅ Relatórios financeiros detalhados
- ✅ Gráficos e análises

### 🛡️ Garantias
- ✅ Gestão de garantias de serviços
- ✅ Alertas de vencimento
- ✅ Histórico completo

### 📊 Relatórios e Análises
- ✅ Dashboard com indicadores
- ✅ Relatórios customizáveis
- ✅ Gráficos interativos
- ✅ Exportação para CSV e PDF

### 🔐 Administração
- ✅ Sistema de autenticação seguro
- ✅ Recuperação de senha por e-mail
- ✅ Configurações da empresa
- ✅ Configurações de e-mail (SMTP)
- ✅ Backup automático do banco de dados
- ✅ Log de atividades

---

## 🚀 Instalação

### Requisitos do Sistema

- **PHP:** >= 7.4 (Recomendado: 8.0+)
- **MySQL:** >= 5.7 ou MariaDB >= 10.2
- **Apache/Nginx** com mod_rewrite habilitado
- **Composer** (gerenciador de dependências PHP)

#### Extensões PHP Necessárias:
- PDO e PDO_MySQL
- mbstring
- openssl
- curl
- gd (para manipulação de imagens)
- zip (para backups)

### Passo a Passo

#### 1. Clone o Repositório

```bash
git clone https://github.com/joaopaulopietchaki/Tech-Eletro-gest-o-.git
cd Tech-Eletro-gest-o-
```

#### 2. Instale as Dependências

```bash
composer install
```

#### 3. Configure o Banco de Dados

Crie um banco de dados MySQL:

```sql
CREATE DATABASE tech_eletro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Importe o schema do banco:

```bash
mysql -u seu_usuario -p tech_eletro < database/schema.sql
```

#### 4. Configure o Sistema

Copie o arquivo de exemplo de configuração:

```bash
cp config.example.php config.php
```

Edite o arquivo `config.php` e configure:
- Credenciais do banco de dados
- Configurações de e-mail (SMTP)
- URLs do sistema
- Chaves de segurança

**IMPORTANTE:** Gere chaves únicas para SECRET_KEY e PASSWORD_SALT:

```php
// No terminal PHP:
php -r "echo bin2hex(random_bytes(32));"
```

#### 5. Configure Permissões

```bash
# Linux/Mac
chmod 755 -R .
chmod 777 -R uploads/ backups/ logs/

# Ou crie os diretórios se não existirem:
mkdir -p uploads backups logs
chmod 777 uploads backups logs
```

#### 6. Configure o Servidor Web

**Apache (.htaccess já incluído):**
```apache
<VirtualHost *:80>
    ServerName tech-eletro.local
    DocumentRoot /caminho/para/tech-eletro/
    
    <Directory /caminho/para/tech-eletro/>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Nginx:**
```nginx
server {
    listen 80;
    server_name tech-eletro.local;
    root /caminho/para/tech-eletro;
    
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

#### 7. Acesse o Sistema

Abra seu navegador e acesse:
```
http://localhost/tech-eletro
```

**Login Padrão (primeira instalação):**
- Usuário: `admin`
- Senha: `admin123`

⚠️ **IMPORTANTE:** Altere a senha padrão imediatamente após o primeiro login!

---

## 🗂️ Estrutura de Diretórios

```
tech-eletro/
├── backups/              # Backups automáticos do banco
├── uploads/              # Arquivos enviados (fotos, documentos)
├── logs/                 # Logs do sistema
├── vendor/               # Dependências do Composer
├── assets/               # CSS, JS, imagens
│   ├── css/
│   ├── js/
│   └── images/
├── includes/             # Arquivos PHP incluídos
│   ├── layout_header.php
│   ├── layout_footer.php
│   └── layout_sidebar.php
├── config.php            # Configurações (NÃO VERSIONAR)
├── config.example.php    # Exemplo de configuração
├── composer.json         # Dependências
├── .gitignore           # Arquivos ignorados pelo Git
└── README.md            # Este arquivo
```

---

## 🗄️ Estrutura do Banco de Dados

### Principais Tabelas:

- **clientes** - Dados dos clientes
- **orcamentos** - Orçamentos criados
- **servicos (OS)** - Ordens de serviço
- **produtos** - Cadastro de produtos/peças
- **gastos** - Despesas operacionais
- **usuarios** - Usuários do sistema
- **garantias** - Controle de garantias
- **historico** - Log de alterações

---

## 🔒 Segurança

### Boas Práticas Implementadas

✅ **Prepared Statements** - Proteção contra SQL Injection  
✅ **Password Hashing** - Senhas criptografadas com bcrypt  
✅ **CSRF Protection** - Proteção contra ataques CSRF  
✅ **Input Sanitization** - Todos os inputs são validados e sanitizados  
✅ **Session Security** - Sessões configuradas com flags de segurança  
✅ **Error Handling** - Erros não expõem informações sensíveis

### Recomendações Adicionais

- 🔐 Use HTTPS em produção
- 🔑 Altere as chaves SECRET_KEY e PASSWORD_SALT
- 📧 Configure autenticação de dois fatores para e-mail
- 💾 Faça backups regulares
- 🔄 Mantenha o sistema sempre atualizado

---

## 🔧 Configuração do Ambiente

### Desenvolvimento

```bash
# No config.php:
define('APP_ENV', 'development');
```

No ambiente de desenvolvimento:
- Erros são exibidos na tela
- Logs detalhados são gerados
- Debug está habilitado

### Produção

```bash
# No config.php:
define('APP_ENV', 'production');
```

No ambiente de produção:
- Erros não são exibidos (apenas logados)
- Segurança máxima
- Performance otimizada

---

## 📚 Documentação

### Arquivos Principais

- **index.php** - Página inicial/login
- **dashboard.php** - Painel principal
- **clientes.php** - Gestão de clientes
- **orcamentos.php** - Gestão de orçamentos
- **os.php** - Ordens de serviço
- **produtos.php** - Gestão de produtos
- **relatorios.php** - Relatórios do sistema

### API

O sistema possui endpoints de API para integração:

```
/api.php?action=buscar_cliente&id=123
/api.php?action=listar_produtos
```

---

## 🧪 Testes

Para executar testes (quando implementados):

```bash
vendor/bin/phpunit tests/
```

---

## 📦 Backup e Restauração

### Backup Automático

O sistema realiza backups automáticos do banco de dados:
- Frequência configurável
- Envio por e-mail opcional
- Armazenamento local em `/backups/`

### Backup Manual

Acesse: `Configurações → Backup` e clique em "Fazer Backup Agora"

### Restauração

```bash
mysql -u usuario -p tech_eletro < backups/backup_YYYY-MM-DD.sql
```

---

## 🐛 Problemas Conhecidos

- Nenhum problema crítico conhecido no momento

Reporte bugs em: [Issues](https://github.com/joaopaulopietchaki/Tech-Eletro-gest-o-/issues)

---

## 🗺️ Roadmap

### Versão 2.0 (Planejado)
- [ ] API REST completa
- [ ] App mobile (Android/iOS)
- [ ] Integração com WhatsApp
- [ ] Relatórios avançados com BI
- [ ] Multi-empresa (SaaS)
- [ ] Testes automatizados

---

## 🤝 Como Contribuir

Contribuições são bem-vindas! Siga os passos:

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/MinhaFeature`)
3. Commit suas mudanças (`git commit -m 'Adiciona MinhaFeature'`)
4. Push para a branch (`git push origin feature/MinhaFeature`)
5. Abra um Pull Request

### Diretrizes de Contribuição

- Siga os padrões PSR-12 de código PHP
- Documente suas mudanças
- Adicione testes quando possível
- Mantenha o código limpo e legível

---

## 📝 Changelog

### [1.0.0] - 2026-01-03
- Versão inicial
- Sistema completo de gestão
- Todas as funcionalidades principais implementadas

---

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

---

## 👨‍💻 Autor

**João Paulo Pietchaki**

- GitHub: [@joaopaulopietchaki](https://github.com/joaopaulopietchaki)
- LinkedIn: [Seu LinkedIn]

---

## 🙏 Agradecimentos

- Comunidade PHP Brasil
- Todos os contribuidores
- Usuários e testadores beta

---

## 📞 Suporte

Precisa de ajuda?

- 📧 Email: seu-email@example.com
- 💬 Issues: [GitHub Issues](https://github.com/joaopaulopietchaki/Tech-Eletro-gest-o-/issues)
- 📖 Wiki: [Documentação Completa](https://github.com/joaopaulopietchaki/Tech-Eletro-gest-o-/wiki)

---

<div align="center">

**Desenvolvido com ❤️ para facilitar a gestão de assistências técnicas**

[⬆ Voltar ao topo](#-tech-eletro---sistema-de-gestão)

</div>
