<?php
require "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// =============================
// VALIDAR ID DO CLIENTE
// =============================
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) die("ID inválido");


// =============================
// BUSCAR CLIENTE
// =============================
$stmt = $conn->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cliente) die("Cliente não encontrado");

// =============================
// BUSCAR HISTÓRICO DE OS
// =============================
$stmt = $conn->prepare("
    SELECT id, servico, data_agendada, status 
    FROM os 
    WHERE cliente_id = ?
    ORDER BY id DESC
");
$stmt->bind_param("i", $id);
$stmt->execute();
$os_lista = $stmt->get_result();
$stmt->close();

include "layout_header.php";
?>

<div class="container mt-4 mb-5">

    <h3 class="mb-3">📜 Histórico do Cliente</h3>

    <!-- ===================== DADOS DO CLIENTE ===================== -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white fw-bold">
            👤 Dados do Cliente
        </div>

        <div class="card-body">
            <p><strong>Nome:</strong> <?= htmlspecialchars($cliente['nome']) ?></p>
            <p><strong>Telefone:</strong> <?= htmlspecialchars($cliente['telefone']) ?></p>
            <p><strong>E-mail:</strong> <?= htmlspecialchars($cliente['email']) ?></p>
            <p><strong>Cidade:</strong> <?= htmlspecialchars($cliente['cidade']) ?></p>
            <p><strong>Endereço:</strong> <?= htmlspecialchars($cliente['endereco']) ?></p>

            <a href="clientes.php" class="btn btn-secondary mt-2">⬅ Voltar</a>
            <a href="cliente_edit.php?id=<?= $cliente['id'] ?>" class="btn btn-warning mt-2">✏️ Editar cliente</a>
        </div>
    </div>


    <!-- ===================== HISTÓRICO DE OS ===================== -->
    <div class="card shadow-sm">
        <div class="card-header bg-info text-white fw-bold">
            🧾 Ordens de Serviço
        </div>

        <div class="card-body p-0">

            <table class="table table-striped table-hover m-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Serviço</th>
                        <th>Data</th>
                        <th>Status</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($os_lista->num_rows > 0): ?>
                        <?php while ($os = $os_lista->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= $os['id'] ?></td>
                                <td><?= htmlspecialchars($os['servico']) ?></td>
                                <td><?= date("d/m/Y", strtotime($os['data_agendada'])) ?></td>
                                <td><?= htmlspecialchars($os['status']) ?></td>

                                <td class="text-center">
                                    <a href="os_view.php?id=<?= $os['id'] ?>" 
                                       class="btn btn-primary btn-sm">
                                        🔍 Ver OS
                                    </a>

                                    <a href="os_edit.php?id=<?= $os['id'] ?>" 
                                       class="btn btn-warning btn-sm">
                                        ✏️ Editar
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">
                                Nenhuma OS cadastrada para este cliente.
                            </td>
                        </tr>
                    <?php endif ?>
                </tbody>

            </table>

        </div>
    </div>

</div>

<?php include "layout_footer.php"; ?>