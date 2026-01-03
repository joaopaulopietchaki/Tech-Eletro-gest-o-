# 📁 GUIA DE REORGANIZAÇÃO DE DIRETÓRIOS

## Estrutura Atual vs Nova Estrutura

### ❌ ESTRUTURA ATUAL (Problemática)
```
tech-eletro/
├── api.php
├── assinar_orcamento.php
├── backup.php
├── cliente_add.php
├── cliente_delete.php
├── ... (99+ arquivos na raiz)
```

### ✅ NOVA ESTRUTURA (Recomendada)
```
tech-eletro/
├── public/                          # Único diretório acessível via web
│   ├── index.php                   # Entry point
│   ├── assets/
│   │   ├── css/
│   │   │   ├── style.css
│   │   │   └── bootstrap.min.css
│   │   ├── js/
│   │   │   ├── app.js
│   │   │   └── jquery.min.js
│   │   └── images/
│   │       ├── logo.png
│   │       └── favicon.ico
│   └── uploads/                    # Arquivos públicos enviados
│       ├── .htaccess               # Proteção adicional
│       └── .gitkeep
│
├── src/                            # Código-fonte da aplicação
│   ├── Controllers/
│   │   ├── ClienteController.php
│   │   ├── OrcamentoController.php
│   │   ├── ServicoController.php
│   │   └── ProdutoController.php
│   │
│   ├── Models/
│   │   ├── Cliente.php
│   │   ├── Orcamento.php
│   │   ├── Servico.php
│   │   └── Produto.php
│   │
│   ├── Views/
│   │   ├── layouts/
│   │   │   ├── header.php
│   │   │   ├── footer.php
│   │   │   └── sidebar.php
│   │   ├── clientes/
│   │   │   ├── index.php
│   │   │   ├── create.php
│   │   │   ├── edit.php
│   │   │   └── view.php
│   │   ├── orcamentos/
│   │   ├── servicos/
│   │   └── produtos/
│   │
│   ├── Config/
│   │   ├── database.php
│   │   └── app.php
│   │
│   ├── Helpers/
│   │   ├── functions.php
│   │   ├── validation.php
│   │   └── security.php
│   │
│   └── Services/
│       ├── EmailService.php
│       ├── PdfService.php
│       └── BackupService.php
│
├── database/                       # Scripts SQL e migrations
│   ├── schema.sql
│   ├── seeds/
│   │   └── initial_data.sql
│   └── migrations/
│       ├── 001_create_clientes.sql
│       └── 002_create_orcamentos.sql
│
├── storage/                        # Arquivos privados
│   ├── backups/
│   │   └── .gitkeep
│   ├── logs/
│   │   └── .gitkeep
│   └── temp/
│       └── .gitkeep
│
├── vendor/                         # Dependências Composer (gerado)
│
├── tests/                          # Testes automatizados
│   ├── Unit/
│   └── Integration/
│
├── docs/                           # Documentação adicional
│   ├── API.md
│   └── DEPLOYMENT.md
│
├── .gitignore
├── composer.json
├── composer.lock
└── README.md
```

## 📝 Mapeamento de Arquivos Atuais para Nova Estrutura

### Clientes
```
ATUAL → NOVO

clientes.php                 → src/Views/clientes/index.php
cliente_add.php             → src/Views/clientes/create.php
cliente_edit.php            → src/Views/clientes/edit.php
cliente_view.php            → src/Views/clientes/view.php
cliente_delete.php          → src/Controllers/ClienteController.php (método delete)
buscar_cliente.php          → src/Controllers/ClienteController.php (método search)
cliente_historico.php       → src/Views/clientes/historico.php
```

### Orçamentos
```
orcamentos.php              → src/Views/orcamentos/index.php
orcamento_add.php           → src/Views/orcamentos/create.php
orcamento_edit.php          → src/Views/orcamentos/edit.php
orcamento_view.php          → src/Views/orcamentos/view.php
orcamento_delete.php        → src/Controllers/OrcamentoController.php
orcamento_pdf.php           → src/Services/PdfService.php
assinar_orcamento.php       → src/Controllers/OrcamentoController.php
orcamento_to_servico.php    → src/Controllers/OrcamentoController.php
```

### Ordens de Serviço
```
os.php                      → src/Views/servicos/index.php
os_add.php                  → src/Views/servicos/create.php
os_edit.php                 → src/Views/servicos/edit.php
os_view.php                 → src/Views/servicos/view.php
os_delete.php               → src/Controllers/ServicoController.php
os_pdf.php                  → src/Services/PdfService.php
```

### Produtos
```
produtos.php                → src/Views/produtos/index.php
produto_add.php             → src/Views/produtos/create.php
produto_edit.php            → src/Views/produtos/edit.php
produto_delete.php          → src/Controllers/ProdutoController.php
produtos_search.php         → src/Controllers/ProdutoController.php
```

### Financeiro
```
gastos.php                  → src/Views/financeiro/gastos.php
gasto_add.php               → src/Views/financeiro/gasto_create.php
relatorios.php              → src/Views/financeiro/relatorios.php
graficos.php                → src/Views/financeiro/graficos.php
```

### Configurações e Sistema
```
config.php                  → src/Config/database.php
configuracoes.php           → src/Views/configuracoes/index.php
email_config.php            → src/Config/email.php
empresa_config.php          → src/Config/empresa.php
```

### Autenticação
```
login.php                   → public/login.php (ou src/Views/auth/login.php)
logout.php                  → src/Controllers/AuthController.php
forgot.php                  → src/Views/auth/forgot.php
reset.php                   → src/Views/auth/reset.php
```

### Backup e Manutenção
```
backup.php                  → src/Services/BackupService.php
backup_auto.php             → src/Services/BackupService.php
restore.php                 → src/Services/BackupService.php
```

### Layouts
```
layout_header.php           → src/Views/layouts/header.php
layout_footer.php           → src/Views/layouts/footer.php
layout_sidebar.php          → src/Views/layouts/sidebar.php
```

### API e Exportação
```
api.php                     → src/Controllers/ApiController.php
export_csv.php              → src/Services/ExportService.php
export_pdf.php              → src/Services/ExportService.php
```

## 🔧 Exemplo de Implementação

### Antes (Arquivo Atual)
```php
// clientes.php
<?php
include 'config.php';
include 'layout_header.php';

// Lógica + HTML tudo junto
$query = "SELECT * FROM clientes";
$result = mysqli_query($conn, $query);
?>
<html>
  <body>
    <!-- HTML aqui -->
  </body>
</html>
```

### Depois (Separado)

**src/Controllers/ClienteController.php:**
```php
<?php
namespace App\Controllers;

use App\Models\Cliente;

class ClienteController {
    public function index() {
        $clientes = Cliente::all();
        include __DIR__ . '/../Views/clientes/index.php';
    }
    
    public function create() {
        include __DIR__ . '/../Views/clientes/create.php';
    }
    
    public function store($data) {
        Cliente::create($data);
        redirect('/clientes');
    }
}
```

**src/Models/Cliente.php:**
```php
<?php
namespace App\Models;

class Cliente {
    public static function all() {
        global $pdo;
        $stmt = $pdo->query("SELECT * FROM clientes");
        return $stmt->fetchAll();
    }
    
    public static function create($data) {
        global $pdo;
        $stmt = $pdo->prepare(
            "INSERT INTO clientes (nome, email, telefone) VALUES (?, ?, ?)"
        );
        return $stmt->execute([
            $data['nome'],
            $data['email'],
            $data['telefone']
        ]);
    }
}
```

**src/Views/clientes/index.php:**
```php
<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container">
    <h1>Clientes</h1>
    <table class="table">
        <?php foreach ($clientes as $cliente): ?>
            <tr>
                <td><?= htmlspecialchars($cliente['nome']) ?></td>
                <td><?= htmlspecialchars($cliente['email']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
```

## 🚀 Processo de Migração

### Fase 1: Preparação
1. Fazer backup completo do sistema
2. Criar nova estrutura de diretórios
3. Configurar autoloading (Composer)

### Fase 2: Migração Gradual
1. Mover arquivos de configuração primeiro
2. Reorganizar Models
3. Reorganizar Controllers
4. Reorganizar Views
5. Atualizar todos os includes/requires

### Fase 3: Testes
1. Testar cada módulo após migração
2. Verificar todos os links
3. Testar funcionalidades críticas

### Fase 4: Deploy
1. Atualizar configurações do servidor
2. Apontar DocumentRoot para /public
3. Configurar permissões
4. Monitorar logs

## 📌 Importante

⚠️ **Não faça tudo de uma vez!** Migre módulo por módulo.

✅ **Mantenha backups** em cada etapa.

✅ **Teste extensivamente** após cada migração.

✅ **Documente** as mudanças feitas.
