<?php
require "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: servicos.php");
    exit;
}

$gasto = $conn->query("SELECT * FROM gastos_extras WHERE id=$id")->fetch_assoc();

if (!$gasto) {
    $_SESSION['msg'] = '⚠️ Gasto não encontrado';
    header("Location: servicos.php");
    exit;
}

include 'layout_header.php';
?>

<div class="container mt-3 mb-5">
<h4 class="mb-3">✏️ Editar Gasto Extra</h4>

<form method="post" action="gasto_update.php" class="card p-4">

<input type="hidden" name="id" value="<?=$id?>">

<div class="mb-3">
<label class="form-label"><b>Descrição do Gasto</b></label>
<input type="text" name="descricao" class="form-control" 
       value="<?=htmlspecialchars($gasto['descricao'])?>" required>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label"><b>Valor</b></label>
        <input type="text" name="valor" class="form-control money" 
               value="<?=number_format($gasto['valor'],2,',','.')?>" required>
    </div>
    
    <div class="col-md-6">
        <label class="form-label"><b>Data</b></label>
        <input type="date" name="data" class="form-control" 
               value="<?=date('Y-m-d', strtotime($gasto['data']))?>" required>
    </div>
</div>

<div class="mb-3 mt-3">
    <label class="form-label"><b>Status</b></label>
    <select name="pago" class="form-select">
        <option value="0" <?=$gasto['pago']==0?'selected':''?>>Em aberto</option>
        <option value="1" <?=$gasto['pago']==1?'selected':''?>>Pago</option>
    </select>
</div>

<hr>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-success w-50">
        <i class="bi bi-save"></i> Salvar Alterações
    </button>
    <a href="servicos.php" class="btn btn-secondary w-50">
        <i class="bi bi-arrow-left"></i> Cancelar
    </a>
</div>

</form>
</div>

<script>
// Máscara de moeda
document.addEventListener('input', e => {
    if(e.target.classList.contains('money')){
        let n = e.target.value.replace(/\D/g,'');
        n = (parseInt(n || 0) / 100).toFixed(2);
        e.target.value = n.replace('.',',');
    }
});
</script>

<?php include 'layout_footer.php'; ?>