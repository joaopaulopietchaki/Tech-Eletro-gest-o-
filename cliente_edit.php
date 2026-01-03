<?php
// Arquivo: cliente_edit.php (Edição de Cliente)

require "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$cliente_id = intval($_GET['id'] ?? 0);
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

// ==========================================================
// 1. Lógica de Validação e Redirecionamento Inicial
// ==========================================================
// ❌ ATENÇÃO: Se o ID for 0, é aqui que deve REDIRECIONAR para a lista de CLIENTES
// Se o seu código aqui não tem "exit", o resto da página pode continuar rodando
if ($cliente_id === 0) {
    // Redireciona para a lista de clientes (clientes.php)
    $_SESSION['error'] = "ID de cliente inválido.";
    header("Location: clientes.php"); 
    exit;
}

$cliente = null;

// ==========================================================
// 2. Processamento do Formulário de Edição (POST)
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($nome)) {
        $_SESSION['error'] = "O campo Nome é obrigatório.";
        header("Location: cliente_edit.php?id=" . $cliente_id);
        exit;
    }

    try {
        $stmt = $conn->prepare("UPDATE clientes SET nome = ?, telefone = ?, endereco = ?, cidade = ?, email = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $nome, $telefone, $endereco, $cidade, $email, $cliente_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Cliente **$nome** (ID $cliente_id) atualizado com sucesso!";
        } else {
            $_SESSION['error'] = "Erro ao atualizar o cliente: " . $stmt->error;
        }
        $stmt->close();

    } catch (mysqli_sql_exception $e) {
        $_SESSION['error'] = "Erro SQL: " . $e->getMessage();
    }
    
    header("Location: cliente_edit.php?id=" . $cliente_id);
    exit;
}

// ==========================================================
// 3. Busca dos Dados do Cliente (GET)
// ==========================================================

try {
    $stmt = $conn->prepare("SELECT id, nome, telefone, endereco, cidade, email FROM clientes WHERE id = ?");
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Se o cliente não for encontrado (após a validação inicial)
        $_SESSION['error'] = "Cliente ID $cliente_id não encontrado.";
        header("Location: clientes.php"); // Redireciona para a lista de clientes
        exit;
    }
    
    $cliente = $result->fetch_assoc();
    $stmt->close();

} catch (mysqli_sql_exception $e) {
    // Em caso de erro de banco de dados
    $_SESSION['error'] = "Erro ao buscar cliente: " . $e->getMessage();
    header("Location: clientes.php"); // Redireciona para a lista de clientes
    exit;
}

// ---------------------------------------------------------------------------------------------------------------------------------------

include "layout_header.php";
?>

<div class="container mt-4 mb-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>✏️ Editando Cliente: <?= htmlspecialchars($cliente['nome']) ?> (ID: <?= $cliente['id'] ?>)</h4>
        <div class="btn-group" role="group">
            <a href="cliente_historico_view.php?id=<?= $cliente['id'] ?>" class="btn btn-info text-white">
                Ver Histórico
            </a>
            <a href="clientes.php" class="btn btn-secondary">
                Lista de Clientes
            </a>
        </div>
    </div>
    
    <hr>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="cliente_edit.php?id=<?= $cliente['id'] ?>" method="POST">
                
                <div class="mb-3">
                    <label for="nome" class="form-label">Nome Completo/Empresa *</label>
                    <input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($cliente['nome']) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="telefone" class="form-label">Telefone</label>
                    <input type="text" class="form-control" id="telefone" name="telefone" value="<?= htmlspecialchars($cliente['telefone']) ?>">
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($cliente['email']) ?>">
                </div>
                
                <div class="mb-3">
                    <label for="endereco" class="form-label">Endereço</label>
                    <input type="text" class="form-control" id="endereco" name="endereco" value="<?= htmlspecialchars($cliente['endereco']) ?>">
                </div>
                
                <div class="mb-3">
                    <label for="cidade" class="form-label">Cidade</label>
                    <input type="text" class="form-control" id="cidade" name="cidade" value="<?= htmlspecialchars($cliente['cidade']) ?>">
                </div>
                
                <button type="submit" class="btn btn-primary mt-3">Salvar Edição</button>
                
            </form>
        </div>
    </div>

</div>

<?php include "layout_footer.php"; ?>