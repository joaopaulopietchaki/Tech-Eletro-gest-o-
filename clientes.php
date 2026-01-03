<?php
// Arquivo: clientes.php (Versão Final: Link Histórico Corrigido para view)

require "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);


// ==========================================================
// 1. Lógica de Processamento do Formulário (POST) - Adição
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    
    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // Validação: Apenas Nome é obrigatório.
    if (empty($nome)) {
        $_SESSION['error'] = "O campo Nome é obrigatório para o cadastro de cliente.";
        header("Location: clientes.php");
        exit;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO clientes (nome, telefone, endereco, cidade, email) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nome, $telefone, $endereco, $cidade, $email);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Cliente **$nome** cadastrado com sucesso!";
        } else {
            $_SESSION['error'] = "Erro ao cadastrar o cliente: " . $stmt->error;
        }
        $stmt->close();

    } catch (mysqli_sql_exception $e) {
        $_SESSION['error'] = "Erro SQL: " . $e->getMessage();
    }
    
    header("Location: clientes.php");
    exit;
}


// ==========================================================
// 2. Lógica de Busca, Paginação e Listagem (GET)
// ==========================================================

// --- Definições de Paginação ---
$default_limit = 10;
$allowed_limits = [10, 20, 50];

// Obtém e valida o limite de resultados
$limit = intval($_GET['limit'] ?? $default_limit);
if (!in_array($limit, $allowed_limits)) {
    $limit = $default_limit; 
}

// Obtém a página atual
$page = intval($_GET['page'] ?? 1);
$page = max(1, $page); 

$search = trim($_GET['search'] ?? '');
$clientes = [];
$total_clientes = 0;
$total_pages = 0;

$sql = "SELECT id, nome, telefone, endereco, cidade, email FROM clientes ";
$where = "WHERE 1=1 "; 
$params_count = [];
$params_data = [];
$types = "";

// --- Lógica de Busca ---
if (!empty($search)) {
    $where .= " AND (nome LIKE ? OR telefone LIKE ? OR email LIKE ?)";
    $search_term = "%" . $search . "%";
    
    $params_count = [$search_term, $search_term, $search_term];
    $params_data = $params_count;
    $types = "sss";
}

try {
    // 1. Contagem total de clientes (com filtro de busca)
    $stmt_count = $conn->prepare("SELECT COUNT(id) AS total FROM clientes " . $where);
    
    if (!empty($params_count)) {
        $refs_count = [];
        foreach ($params_count as $key => $value) {
            $refs_count[$key] = &$params_count[$key];
        }
        $bind_args_count = array_merge([$types], $refs_count);
        call_user_func_array([$stmt_count, 'bind_param'], $bind_args_count);
    }
    $stmt_count->execute();
    $total_clientes = $stmt_count->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt_count->close();

    // 2. Cálculo de Paginação
    $total_pages = ceil($total_clientes / $limit);
    
    if ($page > $total_pages && $total_pages > 0) {
        $page = $total_pages;
    }
    $offset = ($page - 1) * $limit;
    if ($offset < 0) $offset = 0;
    
    // 3. Execução da busca com Paginação (LIMIT e OFFSET)
    $sql_final = $sql . $where . " ORDER BY nome ASC LIMIT ? OFFSET ?";

    $params_data[] = $limit;
    $params_data[] = $offset;
    $types_data = $types . "ii";

    $stmt = $conn->prepare($sql_final);
    
    if (!empty($params_data)) {
        $refs_data = [];
        foreach ($params_data as $key => $value) {
            $refs_data[$key] = &$params_data[$key];
        }
        $bind_args_data = array_merge([$types_data], $refs_data);
        call_user_func_array([$stmt, 'bind_param'], $bind_args_data);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $clientes = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} catch (mysqli_sql_exception $e) {
    $error = "Erro ao buscar clientes: " . $e->getMessage();
}


include "layout_header.php";
?>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>👥 Gerenciamento de Clientes (<?= $total_clientes ?>)</h4>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addClientModal">
            + Novo Cliente
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form class="d-flex mb-3" method="GET" action="clientes.php">
        <input type="hidden" name="limit" value="<?= $limit ?>">
        <input class="form-control me-2" type="search" placeholder="Buscar por Nome, Telefone ou Email..." aria-label="Search" name="search" value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-primary" type="submit">Buscar</button>
        <?php if (!empty($search)): ?>
            <a href="clientes.php?limit=<?= $limit ?>" class="btn btn-outline-secondary ms-2">Limpar Busca</a>
        <?php endif; ?>
    </form>
    
    <hr>
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <form method="GET" action="clientes.php" class="d-flex align-items-center me-3">
            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
            <label for="limit" class="form-label mb-0 me-2 text-nowrap">Clientes por página:</label>
            <select name="limit" id="limit" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php foreach ($allowed_limits as $l): ?>
                    <option value="<?= $l ?>" <?= ($l == $limit) ? 'selected' : '' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <small class="text-muted text-nowrap">Página **<?= $page ?>** de **<?= $total_pages ?>** (Total de Registros: **<?= $total_clientes ?>**)</small>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Telefone</th>
                    <th>Endereço</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clientes)): ?>
                    <tr>
                        <td colspan="5" class="text-center">Nenhum cliente encontrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td><?= $cliente['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($cliente['nome']) ?></strong>
                                <br><small class="text-muted"><?= htmlspecialchars($cliente['email']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($cliente['telefone']) ?></td>
                            <td><?= htmlspecialchars($cliente['endereco']) . (empty($cliente['cidade']) ? '' : ', ' . htmlspecialchars($cliente['cidade'])) ?></td>
                            <td>
                                <a href="cliente_edit.php?id=<?= $cliente['id'] ?>" class="btn btn-sm btn-info text-white">
                                    Editar
                                </a>
                                <a href="cliente_historico_view.php?id=<?= $cliente['id'] ?>" class="btn btn-sm btn-warning">
                                    Histórico OS
                                </a>
                                <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $cliente['id'] ?>)">
                                    Excluir
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
        <nav aria-label="Paginação de Clientes">
            <ul class="pagination justify-content-center mt-4">
                <?php $url_base = '?limit=' . $limit . '&search=' . urlencode($search); ?>

                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= $url_base ?>&page=<?= $page - 1 ?>">Anterior</a>
                </li>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);

                if ($start_page > 1) {
                    echo '<li class="page-item"><a class="page-link" href="' . $url_base . '&page=1">1</a></li>';
                    if ($start_page > 2) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                }

                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="<?= $url_base ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <?php
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    echo '<li class="page-item"><a class="page-link" href="' . $url_base . '&page=' . $total_pages . '">' . $total_pages . '</a></li>';
                }
                ?>

                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= $url_base ?>&page=<?= $page + 1 ?>">Próximo</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>

</div>

<div class="modal fade" id="addClientModal" tabindex="-1" aria-labelledby="addClientModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="clientes.php" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="addClientModalLabel">Cadastrar Novo Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome Completo/Empresa *</label>
                        <input type="text" class="form-control" id="nome" name="nome" required>
                    </div>
                    <div class="mb-3">
                        <label for="telefone" class="form-label">Telefone</label>
                        <input type="text" class="form-control" id="telefone" name="telefone">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                    <div class="mb-3">
                        <label for="endereco" class="form-label">Endereço</label>
                        <input type="text" class="form-control" id="endereco" name="endereco">
                    </div>
                    <div class="mb-3">
                        <label for="cidade" class="form-label">Cidade</label>
                        <input type="text" class="form-control" id="cidade" name="cidade">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-success">Salvar Cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id) {
        if (confirm("Tem certeza que deseja excluir o cliente ID " + id + "? Esta ação é irreversível.")) {
            window.location.href = 'cliente_delete.php?id=' + id;
        }
    }
</script>

<?php include "layout_footer.php"; ?>
