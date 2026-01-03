<?php
require "config.php";
if (session_status()===PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$id = intval($_GET['id'] ?? 0);

// Buscar orçamento
$q = $conn->prepare("SELECT * FROM orcamentos WHERE id=?");
$q->bind_param("i",$id);
$q->execute();
$orc = $q->get_result()->fetch_assoc();
if(!$orc){
    echo "<div class='alert alert-danger'>Orçamento não encontrado.</div>";
    exit;
}

// Salvar atualização
if ($_SERVER["REQUEST_METHOD"] == "POST"){

    $status = $_POST["status"];
    $garantia_manual = trim($_POST["garantia_manual"] ?? "");
    
    // Se usuario escolher manual, usa o campo digitado
    if($status == "Aprovado"){
        $garantia = ($orc['garantia_tipo'] == "Manual" && $garantia_manual != "")
            ? $garantia_manual
            : $orc['garantia_tipo'];

        $data_aprovacao = date("Y-m-d");

        $upd = $conn->prepare("
        UPDATE orcamentos
        SET status=?, garantia_tipo=?, data_aprovacao=?
        WHERE id=?
        ");
        $upd->bind_param("sssi",$status,$garantia,$data_aprovacao,$id);
    } else {
        $upd = $conn->prepare("
        UPDATE orcamentos
        SET status=?
        WHERE id=?
        ");
        $upd->bind_param("si",$status,$id);
    }

    $upd->execute();

    $_SESSION['msg'] = "✅ Status atualizado!";
    header("Location: orcamento_view.php?id=".$id);
    exit;
}

include "layout_header.php";
?>

<h3>✅ Atualizar Status do Orçamento</h3>

<form method="post" class="mt-3">
    <label>Status</label>
    <select name="status" class="form-control mb-3" onchange="toggleGarantia(this.value)">
        <option <?= $orc['status']=="Pendente"?"selected":"" ?>>Pendente</option>
        <option <?= $orc['status']=="Aprovado"?"selected":"" ?>>Aprovado</option>
        <option <?= $orc['status']=="Reprovado"?"selected":"" ?>>Reprovado</option>
    </select>

    <div id="garantiaBox" style="display:none">
        <label>Garantia Manual</label>
        <input name="garantia_manual" class="form-control" placeholder="Ex: 120 dias ou 6 meses">
        <small class="text-muted">Só necessário se o orçamento tiver garantia manual</small>
        <br>
    </div>

    <button class="btn btn-success">Salvar</button>
    <a href="orcamento_view.php?id=<?= $id ?>" class="btn btn-secondary">Voltar</a>
</form>

<script>
function toggleGarantia(status){
    document.getElementById("garantiaBox").style.display =
        (status === "Aprovado" && "<?= $orc['garantia_tipo'] ?>" === "Manual") 
        ? "block" : "none";
}
toggleGarantia("<?= $orc['status'] ?>");
</script>

<?php include "layout_footer.php"; ?>