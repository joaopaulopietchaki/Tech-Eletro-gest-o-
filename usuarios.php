<?php
require 'config.php';
if (session_status()===PHP_SESSION_NONE) session_start();

// Bloqueio
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$adm = $conn->query("SELECT role FROM usuarios WHERE id=".$_SESSION['user_id'])->fetch_assoc();
if($adm['role'] != 'admin'){
    die("<h3 style='color:red;text-align:center;'>❌ Acesso negado</h3>");
}

include 'layout_header.php';
?>

<h3>👥 Gerenciar Usuários</h3>
<a href="usuario_add.php" class="btn btn-success mb-3">➕ Novo Usuário</a>

<table class="table table-bordered table-hover">
<tr>
    <th>ID</th>
    <th>Nome</th>
    <th>Email</th>
    <th>Função</th>
    <th>Ações</th>
</tr>

<?php
$r = $conn->query("SELECT * FROM usuarios ORDER BY id ASC");
while($u = $r->fetch_assoc()):
?>
<tr>
    <td><?= $u['id'] ?></td>
    <td><?= htmlspecialchars($u['nome']) ?></td>
    <td><?= htmlspecialchars($u['email']) ?></td>
    <td><b><?= strtoupper($u['role']) ?></b></td>
    <td>
        <a href="usuario_edit.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-primary">✏ Editar</a>
        
        <?php if($u['id'] != 1): ?>
        <a href="usuario_delete.php?id=<?= $u['id'] ?>" 
           onclick="return confirm('Excluir este usuário?')" 
           class="btn btn-sm btn-danger">🗑 Excluir</a>
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>
</table>

<?php include 'layout_footer.php'; ?>