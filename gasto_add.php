<?php
require 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

if($_SERVER['REQUEST_METHOD']==='POST'){
    $desc  = trim($_POST['descricao']);
    $valor = floatval(str_replace(',','.',$_POST['valor']));
    $data  = $_POST['data'];
    $pago  = isset($_POST['pago']) ? 1 : 0;
    $forma = $_POST['forma_pagamento'] ?? null;
    $obs   = $_POST['observacao'] ?? null;

    $sql = $conn->prepare("
        INSERT INTO gastos_extras (descricao,valor,data,pago,forma_pagamento,observacao)
        VALUES (?,?,?,?,?,?)
    ");
    $sql->bind_param("sdsiss",$desc,$valor,$data,$pago,$forma,$obs);
    $sql->execute();

    header("Location: gastos.php");
    exit;
}

include 'layout_header.php';
?>

<div class="container">
<h4>➕ Novo Gasto Extra</h4>

<form method="post" class="card p-4 shadow-sm">

<label>Descrição</label>
<input name="descricao" class="form-control mb-2" required>

<label>Valor (R$)</label>
<input name="valor" class="form-control mb-2" required>

<label>Data</label>
<input type="date" name="data" value="<?= date('Y-m-d') ?>" class="form-control mb-2">

<label>
<input type="checkbox" name="pago"> Já foi pago
</label>

<label class="mt-2">Forma de Pagamento</label>
<input name="forma_pagamento" class="form-control mb-2">

<label>Observação</label>
<textarea name="observacao" class="form-control"></textarea>

<button class="btn btn-success mt-3">Salvar</button>
<a href="gastos.php" class="btn btn-secondary mt-3">Voltar</a>

</form>
</div>

<?php include 'layout_footer.php'; ?>