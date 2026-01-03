<?php
require 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

// EXCLUIR
if(isset($_POST['excluir']) && !empty($_POST['ids'])){
    foreach($_POST['ids'] as $id){
        $conn->query("DELETE FROM gastos_extras WHERE id=".(int)$id);
    }
}

// EXPORTAR
if(isset($_POST['exportar']) && !empty($_POST['ids'])){
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=gastos_extras.csv");
    echo "Descricao;Valor;Data;Pago\n";
    foreach($_POST['ids'] as $id){
        $g = $conn->query("SELECT * FROM gastos_extras WHERE id=".(int)$id)->fetch_assoc();
        echo "{$g['descricao']};{$g['valor']};{$g['data']};".($g['pago']?'Sim':'Não')."\n";
    }
    exit;
}

$res = $conn->query("SELECT * FROM gastos_extras ORDER BY data DESC");

include 'layout_header.php';
?>

<div class="container-fluid">
<div class="d-flex justify-content-between mb-3">
<h4>💸 Gastos Extras</h4>
<a href="gasto_add.php" class="btn btn-success btn-sm">+ Novo Gasto</a>
</div>

<form method="post" id="formGastos">
<table class="table table-bordered table-hover align-middle">
<thead class="table-dark">
<tr>
<th><input type="checkbox" id="ckAll"></th>
<th>Data</th>
<th>Descrição</th>
<th>Valor</th>
<th>Status</th>
</tr>
</thead>
<tbody>
<?php while($g=$res->fetch_assoc()): ?>
<tr>
<td><input type="checkbox" name="ids[]" value="<?= $g['id'] ?>"></td>
<td><?= date('d/m/Y',strtotime($g['data'])) ?></td>
<td><?= htmlspecialchars($g['descricao']) ?></td>
<td>R$ <?= number_format($g['valor'],2,',','.') ?></td>
<td>
<span class="badge <?= $g['pago']?'bg-success':'bg-danger' ?>">
<?= $g['pago']?'Pago':'Em aberto' ?>
</span>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<button name="excluir" class="btn btn-danger">🗑 Excluir</button>
<button name="exportar" class="btn btn-primary">📤 Exportar</button>
</form>
</div>

<script>
document.getElementById('ckAll').onclick=function(){
 document.querySelectorAll('input[name="ids[]"]').forEach(c=>c.checked=this.checked);
}
</script>

<?php include 'layout_footer.php'; ?>