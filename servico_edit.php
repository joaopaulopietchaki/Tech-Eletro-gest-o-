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

$serv = $conn->query("SELECT * FROM servicos WHERE id=$id")->fetch_assoc();

if (!$serv) {
    $_SESSION['msg'] = '⚠️ Serviço não encontrado';
    header("Location: servicos.php");
    exit;
}

$itens = $conn->query("SELECT * FROM servico_itens WHERE servico_id=$id");

include 'layout_header.php';
?>

<div class="container mt-3 mb-5">
<h4 class="mb-3">✏️ Editar Serviço</h4>

<form method="post" action="servico_update.php">

<input type="hidden" name="id" value="<?=$id?>">

<div class="row g-3">
<div class="col-12">
<label>Cliente</label>
<input class="form-control" name="cliente"
value="<?=htmlspecialchars($serv['nome_cliente'])?>" required>
</div>

<div class="col-12">
<label>Serviço Executado</label>
<input class="form-control" name="servico"
value="<?=htmlspecialchars($serv['servico_executado'])?>" required>
</div>

<div class="col-6">
<label>Valor Recebido</label>
<input id="valor" class="form-control money"
inputmode="numeric" name="valor_recebido"
value="<?=number_format($serv['valor_recebido'],2,',','.')?>">
</div>

<div class="col-6">
<label>Deslocamento</label>
<input id="deslocamento" class="form-control money"
inputmode="numeric" name="deslocamento"
value="<?=number_format($serv['desloc'],2,',','.')?>">
</div>

<div class="col-6">
<label>Status</label>
<select class="form-select" name="status">
<option <?=$serv['status_pagamento']=='Em aberto'?'selected':''?>>Em aberto</option>
<option <?=$serv['status_pagamento']=='Pago total'?'selected':''?>>Pago total</option>
</select>
</div>

<div class="col-6">
<label>Data</label>
<input type="date" class="form-control" name="data"
value="<?=date('Y-m-d',strtotime($serv['data']))?>">
</div>
</div>

<hr>

<h5>📦 Custos / Itens</h5>

<table class="table table-bordered">
<thead>
<tr>
<th>Descrição</th>
<th width="140">Valor</th>
<th width="50"></th>
</tr>
</thead>
<tbody id="itens">
<?php while($i=$itens->fetch_assoc()): ?>
<tr>
<td>
<input name="item_desc[]" class="form-control"
value="<?=htmlspecialchars($i['produto_nome'])?>" required>
<input type="hidden" name="item_id[]" value="<?=$i['id']?>">
</td>
<td>
<input name="item_valor[]" class="form-control money"
inputmode="numeric"
value="<?=number_format($i['valor_unit'],2,',','.')?>">
</td>
<td>
<button type="button" class="btn btn-danger btn-sm"
onclick="this.closest('tr').remove();calcular()">✖</button>
</td>
</tr>
<?php endwhile ?>
</tbody>
</table>

<button type="button"
class="btn btn-primary w-100"
onclick="addItem()">+ Adicionar Item</button>

<hr>

<div class="card bg-light p-3">
<h6 class="mb-3">📊 Resumo Financeiro</h6>
<p>Total Itens: R$ <span id="total_itens">0,00</span></p>
<p>Custo Total (itens + deslocamento): R$ <span id="custo_total">0,00</span></p>
<p>Reserva (10% do lucro): R$ <span id="reserva">0,00</span></p>
<p><b>Lucro Líquido: R$ <span id="lucro">0,00</span></b></p>
<p class="mt-2"><b>Cada Sócio: R$ <span id="socio">0,00</span></b></p>
</div>

<div class="mt-3 d-flex gap-2">
<button class="btn btn-success w-50">💾 Salvar</button>
<a href="servicos.php" class="btn btn-secondary w-50">↩ Cancelar</a>
</div>

</form>
</div>

<script>
function num(v){
    if(!v) return 0;
    v = v.toString().replace(/\./g,'').replace(',','.');
    return parseFloat(v)||0;
}

function moeda(v){
    return v.toLocaleString('pt-BR',{minimumFractionDigits:2});
}

function calcular(){
    let totalItens = 0;

    document.querySelectorAll('[name="item_valor[]"]').forEach(el=>{
        totalItens += num(el.value);
    });

    let valorRecebido = num(document.getElementById('valor').value);
    let deslocamento  = num(document.getElementById('deslocamento').value);

    let custoTotal = totalItens + deslocamento;

    let lucroBruto = valorRecebido - custoTotal;
    if(lucroBruto < 0) lucroBruto = 0;

    let reserva = lucroBruto * 0.10;
    let lucroLiquido = lucroBruto - reserva;
    let cadaSocio = lucroLiquido / 2;

    document.getElementById('total_itens').innerText = moeda(totalItens);
    document.getElementById('custo_total').innerText = moeda(custoTotal);
    document.getElementById('reserva').innerText     = moeda(reserva);
    document.getElementById('lucro').innerText       = moeda(lucroLiquido);
    document.getElementById('socio').innerText       = moeda(cadaSocio);
}

function addItem(){
    let tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <input name="item_desc[]" class="form-control" required>
            <input type="hidden" name="item_id[]" value="0">
        </td>
        <td>
            <input name="item_valor[]" class="form-control money"
            inputmode="numeric" value="0,00">
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-sm"
            onclick="this.closest('tr').remove();calcular()">✖</button>
        </td>
    `;
    document.getElementById('itens').appendChild(tr);
}

/* Máscara de moeda global */
document.addEventListener('input', e=>{
    if(e.target.classList.contains('money')){
        let n = e.target.value.replace(/\D/g,'');
        n = (parseInt(n || 0) / 100).toFixed(2);
        e.target.value = n.replace('.',',');
        calcular();
    }
});

window.onload = calcular;
</script>

<?php include 'layout_footer.php'; ?>