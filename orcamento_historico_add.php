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

// Salvar histórico
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    $status = trim($_POST["status"]);
    $obs    = trim($_POST["obs"]);

    $save = $conn->prepare("
        INSERT INTO orcamento_historico (orcamento_id, status, observacao)
        VALUES (?,?,?)
    ");
    $save->bind_param("iss", $id, $status, $obs);
    $save->execute();

    // Atualiza status na tabela principal
    $upd = $conn->prepare("UPDATE orcamentos SET status=? WHERE id=?");
    $upd->bind_param("si",$status,$id);
    $upd->execute();

    $_SESSION['msg'] = "✅ Atualização registrada!";
    header("Location: orcamento_view.php?id=".$id);
    exit;
}

include "layout_header.php";
?>

<h3>🕓 Nova Atualização do Orçamento</h3>

<div class="card p-3 mb-3">
<b>Cliente:</b> <?= htmlspecialchars($orc['cliente']) ?><br>
<b>Descrição:</b> <?= htmlspecialchars($orc['descricao']) ?><br>
<b>Status atual:</b> <span class="badge badge-info"><?= $orc['status'] ?></span>
</div>

<form method="post">

<label>Status</label>
<select name="status" class="form-control mb-3" required>
    <option value="Pendente" <?= $orc['status']=="Pendente"?"selected":"" ?>>Pendente</option>
    <option value="Aguardando Peça" <?= $orc['status']=="Aguardando Peça"?"selected":"" ?>>Aguardando Peça</option>
    <option value="Aprovado" <?= $orc['status']=="Aprovado"?"selected":"" ?>>Aprovado</option>
    <option value="Em Execução" <?= $orc['status']=="Em Execução"?"selected":"" ?>>Em Execução</option>
    <option value="Concluído" <?= $orc['status']=="Concluído"?"selected":"" ?>>Concluído</option>
    <option value="Cancelado" <?= $orc['status']=="Cancelado"?"selected":"" ?>>Cancelado</option>
</select>

<label>Observação</label>
<textarea name="obs" class="form-control mb-3" rows="3" placeholder="Ex: Cliente aprovou, iniciar serviço..."></textarea>

<button class="btn btn-primary">Salvar</button>
<a href="orcamento_view.php?id=<?= $id ?>" class="btn btn-secondary">Cancelar</a>

</form>

<?php include "layout_footer.php"; ?>