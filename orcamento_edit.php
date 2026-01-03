<?php
require "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$id = (int)($_GET['id'] ?? 0);

// === BUSCA ORÇAMENTO ===
$stmt = $conn->prepare("SELECT * FROM orcamentos WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$orc = $stmt->get_result()->fetch_assoc();

if (!$orc) {
  echo "<div class='alert alert-danger'>Orçamento não encontrado.</div>";
  exit;
}

// === SALVAR ALTERAÇÕES ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $cliente_nome = trim($_POST['cliente_nome'] ?? '');
  $garantia_tipo = $_POST['garantia_tipo'] ?? 'Sem Garantia';
  $status = $_POST['status'] ?? 'Pendente';
  $descricao = $_POST['descricao'] ?? '';

  $upd = $conn->prepare("UPDATE orcamentos SET cliente_nome=?, descricao=?, garantia_tipo=?, status=? WHERE id=?");
  $upd->bind_param("ssssi", $cliente_nome, $descricao, $garantia_tipo, $status, $id);
  $upd->execute();

  // Deleta itens antigos
  $conn->query("DELETE FROM orcamento_itens WHERE orcamento_id=$id");

  // Adiciona novos itens
  if (!empty($_POST['produto'])) {
    $it = $conn->prepare("INSERT INTO orcamento_itens (orcamento_id, produto, unidade, quantidade, valor_unit, custo_unit) VALUES (?,?,?,?,?,?)");
    for ($i=0; $i<count($_POST['produto']); $i++) {
      $prod = $_POST['produto'][$i];
      $un = $_POST['unidade'][$i];
      $qtd = (float)$_POST['quantidade'][$i];
      $v = (float)$_POST['valor_unit'][$i];
      $c = (float)$_POST['custo'][$i];
      $it->bind_param("issddd", $id, $prod, $un, $qtd, $v, $c);
      $it->execute();
    }
  }

  $_SESSION['msg'] = "💾 Orçamento #$id atualizado com sucesso!";
  header("Location: orcamento_view.php?id=$id");
  exit;
}

// === BUSCA ITENS ===
$it = $conn->prepare("SELECT * FROM orcamento_itens WHERE orcamento_id=?");
$it->bind_param("i", $id);
$it->execute();
$itens = $it->get_result()->fetch_all(MYSQLI_ASSOC);

include "layout_header.php";
?>

<h3>✏️ Editar Orçamento #<?= $id ?></h3>

<form method="post">
<div class="card p-3 mb-3">
  <div class="row g-3">
    <div class="col-md-6">
      <label>Cliente</label>
      <input type="text" class="form-control" id="clienteBusca" name="cliente_nome" value="<?= htmlspecialchars($orc['cliente_nome']) ?>">
      <div id="resultadoBusca" class="list-group position-absolute w-100"></div>
    </div>
    <div class="col-md-3">
      <label>Garantia</label>
      <select name="garantia_tipo" class="form-select">
        <?php
        $options = ["Sem Garantia", "30 dias", "90 dias", "1 ano"];
        foreach ($options as $opt) {
          $sel = ($opt === $orc['garantia_tipo']) ? 'selected' : '';
          echo "<option $sel>$opt</option>";
        }
        ?>
      </select>
    </div>
    <div class="col-md-3">
      <label>Status</label>
      <select name="status" class="form-select">
        <?php
        $statusOpts = ["Pendente", "Aprovado", "Reprovado"];
        foreach ($statusOpts as $st) {
          $sel = ($st === $orc['status']) ? 'selected' : '';
          echo "<option $sel>$st</option>";
        }
        ?>
      </select>
    </div>
  </div>

  <div class="mt-3">
    <label>Descrição</label>
    <textarea name="descricao" class="form-control" rows="2"><?= htmlspecialchars($orc['descricao']) ?></textarea>
  </div>
</div>

<h5>🧮 Itens do Orçamento</h5>

<table class="table table-bordered table-sm align-middle">
  <thead class="table-light">
    <tr>
      <th>Produto</th>
      <th>Un</th>
      <th>Qtd</th>
      <th>Valor Unit</th>
      <th>Custo Estimado</th>
      <th>Subtotal</th>
      <th></th>
    </tr>
  </thead>
  <tbody id="itens">
    <?php foreach ($itens as $i): ?>
    <tr>
      <td><input name="produto[]" class="form-control" value="<?= htmlspecialchars($i['produto']) ?>"></td>
      <td><input name="unidade[]" class="form-control" value="<?= htmlspecialchars($i['unidade']) ?>" style="width:60px"></td>
      <td><input name="quantidade[]" type="number" step="0.01" class="form-control qtd" value="<?= $i['quantidade'] ?>" style="width:80px"></td>
      <td><input name="valor_unit[]" type="number" step="0.01" class="form-control valor" value="<?= $i['valor_unit'] ?>" style="width:100px"></td>
      <td><input name="custo[]" type="number" step="0.01" class="form-control custo" value="<?= $i['custo_unit'] ?>" style="width:100px"></td>
      <td class="text-end sub">R$ <?= number_format($i['quantidade']*$i['valor_unit'], 2, ',', '.') ?></td>
      <td><button type="button" class="btn btn-danger btn-sm">✖</button></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<button type="button" class="btn btn-secondary mb-3" id="addItem">➕ Adicionar Item</button>

<div class="text-end">
  <p><b>Total Venda:</b> R$ <span id="totalVenda">0,00</span></p>
  <p><b>Custo Total:</b> R$ <span id="totalCusto">0,00</span></p>
  <h5 class="text-success"><b>Lucro Estimado:</b> R$ <span id="lucroTotal">0,00</span></h5>
</div>

<div class="d-flex justify-content-between">
  <button class="btn btn-success">💾 Salvar Alterações</button>
  <a href="orcamento_view.php?id=<?= $id ?>" class="btn btn-secondary">← Voltar</a>
</div>
</form>

<script>
// ===== BUSCA CLIENTE =====
const clienteInput = document.querySelector('#clienteBusca');
const resultado = document.querySelector('#resultadoBusca');
clienteInput.addEventListener('input', async () => {
  const q = clienteInput.value.trim();
  if (q.length < 2) { resultado.innerHTML=''; return; }
  const r = await fetch('api.php?acao=buscar_cliente&q=' + encodeURIComponent(q));
  const data = await r.json();
  resultado.innerHTML='';
  data.forEach(c => {
    const b = document.createElement('button');
    b.type='button';
    b.className='list-group-item list-group-item-action';
    b.textContent = c.nome + ' — ' + c.telefone;
    b.onclick = ()=>{
      clienteInput.value = c.nome;
      resultado.innerHTML='';
    };
    resultado.appendChild(b);
  });
});

// ===== ITENS =====
const itens = document.querySelector('#itens');
document.querySelector('#addItem').addEventListener('click', ()=>{
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td><input name="produto[]" class="form-control" placeholder="Produto"></td>
    <td><input name="unidade[]" class="form-control" value="Un" style="width:60px"></td>
    <td><input name="quantidade[]" type="number" step="0.01" class="form-control qtd" value="1" style="width:80px"></td>
    <td><input name="valor_unit[]" type="number" step="0.01" class="form-control valor" value="0" style="width:100px"></td>
    <td><input name="custo[]" type="number" step="0.01" class="form-control custo" value="0" style="width:100px"></td>
    <td class="text-end sub">R$ 0,00</td>
    <td><button type="button" class="btn btn-danger btn-sm">✖</button></td>
  `;
  tr.querySelector('button').onclick = ()=> tr.remove();
  itens.appendChild(tr);
  atualizarTotais();
});
itens.addEventListener('input', atualizarTotais);

function atualizarTotais(){
  let totalVenda = 0, totalCusto = 0;
  itens.querySelectorAll('tr').forEach(tr=>{
    const qtd = parseFloat(tr.querySelector('.qtd').value) || 0;
    const valor = parseFloat(tr.querySelector('.valor').value) || 0;
    const custo = parseFloat(tr.querySelector('.custo').value) || 0;
    const sub = qtd * valor;
    tr.querySelector('.sub').textContent = 'R$ ' + sub.toFixed(2).replace('.',',');
    totalVenda += sub;
    totalCusto += qtd * custo;
  });
  const lucro = totalVenda - totalCusto;
  document.querySelector('#totalVenda').textContent = totalVenda.toFixed(2).replace('.',',');
  document.querySelector('#totalCusto').textContent = totalCusto.toFixed(2).replace('.',',');
  document.querySelector('#lucroTotal').textContent = lucro.toFixed(2).replace('.',',');
}

// Inicializa cálculo automático
atualizarTotais();
</script>

<?php include "layout_footer.php"; ?>