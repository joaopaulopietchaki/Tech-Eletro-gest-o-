<?php
require "config.php";
if (session_status()===PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$id = intval($_GET['id'] ?? 0);

// Buscar orçamento
$q = $conn->prepare("
SELECT o.*, c.nome AS cliente 
FROM orcamentos o
LEFT JOIN clientes c ON c.id = o.cliente_id
WHERE o.id = ?
");
$q->bind_param("i",$id);
$q->execute();
$orc = $q->get_result()->fetch_assoc();

if(!$orc){
    echo "<div class='alert alert-danger'>Orçamento não encontrado.</div>";
    exit;
}

// Buscar histórico
$hist = $conn->prepare("
SELECT * FROM orcamento_historico 
WHERE orcamento_id=? 
ORDER BY data DESC
");
$hist->bind_param("i",$id);
$hist->execute();
$res = $hist->get_result();

include "layout_header.php";
?>

<h3>🕓 Histórico do Orçamento</h3>

<div class="card p-3 mb-3">
<b>Cliente:</b> <?= htmlspecialchars($orc['cliente']) ?><br>
<b>Descrição:</b> <?= htmlspecialchars($orc['descricao']) ?><br>
<b>Status atual:</b> <span class="badge badge-info"><?= $orc['status'] ?></span>
</div>

<table class="table table-bordered table-striped">
<thead class="table-dark">
<tr>
    <th>Data</th>
    <th>Status</th>
    <th>Observação</th>
</tr>
</thead>
<tbody>
<?php if($res->num_rows > 0): ?>
    <?php while($h = $res->fetch_assoc()): ?>
        <tr>
            <td><?= date("d/m/Y H:i", strtotime($h['data'])) ?></td>
            <td><b><?= $h['status'] ?></b></td>
            <td><?= nl2br(htmlspecialchars($h['observacao'])) ?></td>
        </tr>
    <?php endwhile; ?>
<?php else: ?>
<tr>
    <td colspan="3" class="text-center text-muted">Nenhum histórico registrado ainda</td>
</tr>
<?php endif; ?>
</tbody>
</table>

<a href="orcamento_view.php?id=<?= $id ?>" class="btn btn-secondary">⬅ Voltar</a>

<?php include "layout_footer.php"; ?>