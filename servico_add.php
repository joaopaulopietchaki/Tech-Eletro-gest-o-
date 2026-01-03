<?php
require 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include 'layout_header.php';
?>

<div class="container-fluid">
<h4 class="mb-3">➕ Novo Lançamento</h4>

<form method="post" action="servico_add_salvar.php" class="card p-4">

<!-- TIPO -->
<div class="mb-3">
<label class="form-label"><b>Tipo de Lançamento</b></label>
<select name="tipo" id="tipo" class="form-select" onchange="toggleTipo()">
    <option value="servico">Serviço</option>
    <option value="gasto_extra">Gasto extra</option>
</select>
</div>

<!-- ================= SERVIÇO ================= -->
<div id="box-servico">

<div class="row mb-2">
<div class="col-md-6">
<label>Cliente</label>
<input name="cliente" class="form-control">
</div>
<div class="col-md-6">
<label>Serviço executado</label>
<input name="servico" class="form-control">
</div>
</div>

<div class="row mb-2">
<div class="col-md-4">
<label>Valor Recebido</label>
<input name="valor" class="form-control money" value="0,00">
</div>
<div class="col-md-4">
<label>Deslocamento</label>
<input name="deslocamento" class="form-control money" value="0,00">
</div>
<div class="col-md-4">
<label>Status</label>
<select name="status" class="form-select">
<option value="Em aberto">Em aberto</option>
<option value="Pago total">Pago total</option>
</select>
</div>
</div>

<div class="mb-3">
<label>Data</label>
<input type="date" name="data" class="form-control" value="<?= date('Y-m-d') ?>">
</div>

<hr>

<h6>📦 Custos</h6>
<table class="table table-bordered">
<thead>
<tr><th>Descrição</th><th width="140">Valor</th><th></th></tr>
</thead>
<tbody id="itens"></tbody>
</table>

<button type="button" class="btn btn-sm btn-primary" onclick="addItem()">+ Adicionar Custo</button>

<hr>

<h6>📊 Resumo</h6>
<ul class="list-group">
<li class="list-group-item">Total Itens: <b id="tItens">0,00</b></li>
<li class="list-group-item">Custo Total: <b id="tCusto">0,00</b></li>
<li class="list-group-item">Reserva: <b id="tReserva">0,00</b></li>
<li class="list-group-item text-success">Lucro Líquido: <b id="tLucro">0,00</b></li>
</ul>

</div>

<!-- ================= GASTO EXTRA ================= -->
<div id="box-extra" style="display:none">

<div class="mb-3">
<label>Descrição do gasto</label>
<input name="extra_desc" class="form-control">
</div>

<div class="mb-3">
<label>Valor do gasto</label>
<input name="extra_valor" class="form-control money" value="0,00">
</div>

<div class="mb-3">
<label>Data</label>
<input type="date" name="extra_data" class="form-control" value="<?= date('Y-m-d') ?>">
</div>

</div>

<hr>

<div class="text-end">
<button class="btn btn-success">Salvar</button>
<a href="servicos.php" class="btn btn-secondary">Cancelar</a>
</div>

</form>
</div>

<script>
function f(n){return Number(n||0).toFixed(2).replace('.',',')}
function p(v){return parseFloat(v.replace('.','').replace(',','.'))||0}

function toggleTipo(){
    const t=document.getElementById('tipo').value;
    document.getElementById('box-servico').style.display = t==='servico'?'block':'none';
    document.getElementById('box-extra').style.display   = t==='gasto_extra'?'block':'none';
}

function calc(){
 let itens=0;
 document.querySelectorAll('.valor').forEach(i=>itens+=p(i.value));
 let desloc=p(document.querySelector('[name="deslocamento"]')?.value||0);
 let valor=p(document.querySelector('[name="valor"]')?.value||0);

 let custo=itens+desloc;
 let lucro=Math.max(valor-custo,0);
 let reserva=lucro*0.10;
 let liquido=lucro-reserva;

 document.getElementById('tItens').innerText=f(itens);
 document.getElementById('tCusto').innerText=f(custo);
 document.getElementById('tReserva').innerText=f(reserva);
 document.getElementById('tLucro').innerText=f(liquido);
}

function addItem(){
 let tr=document.createElement('tr');
 tr.innerHTML=`
 <td><input name="produto[]" class="form-control"></td>
 <td><input name="valor_item[]" class="form-control valor" value="0,00" oninput="calc()"></td>
 <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove();calc()">✖</button></td>`;
 document.getElementById('itens').appendChild(tr);
}

document.addEventListener('input',calc);
document.addEventListener('DOMContentLoaded',toggleTipo);
</script>

<?php include 'layout_footer.php'; ?>