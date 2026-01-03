<?php
// orcamento_view.php
// Visualização responsiva (Modelo C — Premium)
// Requer: config.php (com $conn mysqli) e sessão / permissões já implementadas.

require "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();

// validacao id
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: orcamentos.php");
    exit;
}

// carrega orçamento
$stmt = $conn->prepare("
    SELECT o.*, DATE_FORMAT(o.data_registro, '%d/%m/%Y %H:%i') AS data_fmt
    FROM orcamentos o
    WHERE o.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$orc = $stmt->get_result()->fetch_assoc();
if (!$orc) {
    header("Location: orcamentos.php");
    exit;
}

// carrega itens
$stmt2 = $conn->prepare("
    SELECT oi.*
    FROM orcamentos_itens oi
    WHERE oi.orcamento_id = ?
    ORDER BY oi.id ASC
");
$stmt2->bind_param("i", $id);
$stmt2->execute();
$itens_result = $stmt2->get_result();
$itens = [];
while ($r = $itens_result->fetch_assoc()) $itens[] = $r;

// helper format
function fm($v) { return number_format((float)$v, 2, ',', '.'); }

// status -> cor e etiqueta
$status_map = [
    'aberto'   => ['label' => 'Aberto',    'class' => 'badge bg-primary'],
    'aprovado' => ['label' => 'Aprovado',  'class' => 'badge bg-success'],
    'vencido'  => ['label' => 'Vencido',   'class' => 'badge bg-warning text-dark'],
    'recusado' => ['label' => 'Recusado',  'class' => 'badge bg-danger'],
    'cancelado'=> ['label' => 'Cancelado', 'class' => 'badge bg-secondary']
];

// garantia display
$garantia_map = [
    '3m' => '3 meses',
    '6m' => '6 meses',
    '1y' => '1 ano',
    'none' => 'Sem garantia'
];

// sanitização para exibição
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Orçamento #<?= h($orc['id']) ?> — Visualizar</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
/* Estilo premium compacto para mobile-first */
body { background:#f4f6f8; }
.container-orc { max-width:980px; margin:18px auto; }
.card-orc { border-radius:12px; overflow:hidden; box-shadow:0 6px 18px rgba(20,30,50,0.08); }
.header-orc { background:linear-gradient(90deg,#0d6efd 0%, #6610f2 100%); color:#fff; padding:16px 18px; }
.header-orc .meta { font-size:0.9rem; opacity:0.95; }
.section { padding:16px; background:#fff; border-bottom:1px solid #efefef; }
.client-row { display:flex; gap:12px; flex-wrap:wrap; align-items:center; }
.client-block { flex:1 1 200px; min-width:160px; }
.small-muted { color:#6c757d; font-size:0.9rem; }
.items-list { display:flex; flex-direction:column; gap:8px; }
.item-row { display:flex; align-items:center; gap:12px; padding:10px; border-radius:8px; background:linear-gradient(180deg,#fbfbfb,#fff); border:1px solid #f1f1f1; }
.item-summary { flex:1; display:flex; flex-direction:column; }
.item-top { display:flex; justify-content:space-between; gap:10px; align-items:center; }
.item-bottom { display:flex; justify-content:space-between; gap:8px; align-items:center; margin-top:6px; font-size:0.95rem; color:#495057; }
.item-qty { width:72px; text-align:center; font-weight:600; }
.item-val { width:120px; text-align:right; font-weight:600; }
.item-actions { width:60px; text-align:right; }
@media (min-width: 768px) {
  .item-qty { width:100px; }
  .item-val { width:170px; }
}

/* Totals */
.totals { display:flex; flex-direction:column; gap:6px; margin-top:6px; }
.totals .row { display:flex; justify-content:space-between; }

/* status color small square */
.status-dot { display:inline-block; width:10px; height:10px; border-radius:2px; margin-right:8px; vertical-align:middle; }

/* small responsive tweaks */
@media (max-width:420px){
  .header-orc h4 { font-size:1.05rem; }
  .item-val { width:110px; }
}
</style>
</head>
<body>

<div class="container-orc">

  <div class="card-orc">
    <div class="header-orc d-flex justify-content-between align-items-start">
      <div>
        <h4 class="mb-1">Orçamento #<?= h($orc['id']) ?></h4>
        <div class="meta small-muted">Emitido em: <?= h($orc['data_fmt']) ?> • Vendedor: <?= h($orc['usuario'] ?? '—') ?></div>
      </div>
      <div class="text-end">
        <?php
          $st = strtolower($orc['status'] ?? 'aberto');
          $st_label = $status_map[$st]['label'] ?? ucfirst($st);
          $st_class = $status_map[$st]['class'] ?? 'badge bg-secondary';
        ?>
        <div class="<?= h($st_class) ?>"><?= h($st_label) ?></div>
        <div class="small-muted mt-2">Garantia: <strong><?= h($garantia_map[$orc['garantia']] ?? ($orc['garantia'] ?: 'Sem garantia')) ?></strong></div>
      </div>
    </div>

    <!-- Cliente / Resumo -->
    <div class="section d-flex flex-column gap-2">
      <div class="client-row">
        <div class="client-block">
          <div class="small-muted">Cliente</div>
          <div class="fw-semibold"><?= h($orc['cliente_nome'] ?: '—') ?></div>
          <?php if (!empty($orc['telefone'])): ?><div class="small-muted"><?= h($orc['telefone']) ?></div><?php endif; ?>
        </div>
        <div class="client-block">
          <div class="small-muted">Endereço</div>
          <div><?= h($orc['endereco'] ?: '—') ?></div>
        </div>
        <div class="client-block text-end">
          <div class="small-muted">Cidade</div>
          <div><?= h($orc['cidade'] ?: '—') ?></div>
        </div>
      </div>

      <?php if (!empty($orc['descricao'])): ?>
      <div class="mt-2">
        <div class="small-muted">Descrição</div>
        <div class="border rounded p-2 mt-1"><?= nl2br(h($orc['descricao'])) ?></div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Itens -->
    <div class="section">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0">Itens</h6>
        <div class="small-muted">Mostrando quantidade, valor unit. e subtotal</div>
      </div>

      <div class="items-list">
        <?php foreach ($itens as $it): 
            $qtd = (float)$it['qtd'];
            $unit = (float)$it['preco_unit'];
            $subtotal = (float)$it['subtotal'];
            // fallback compute if subtotal missing/zero
            if (empty($subtotal)) $subtotal = $qtd * $unit;
        ?>
        <div class="item-row">
          <!-- imagem pequena se tiver (opcional) -->
          <div style="width:56px;">
            <?php if (!empty($it['imagem'])): ?>
              <img src="<?= h($it['imagem']) ?>" alt="" style="width:56px;height:56px;object-fit:cover;border-radius:6px;border:1px solid #eee;">
            <?php else: ?>
              <div style="width:56px;height:56px;border-radius:6px;background:#f1f3f5;display:flex;align-items:center;justify-content:center;color:#888;font-size:0.85rem;border:1px solid #eee;">No</div>
            <?php endif; ?>
          </div>

          <div class="item-summary">
            <div class="item-top">
              <div>
                <div class="fw-semibold"><?= h($it['nome']) ?></div>
                <div class="small-muted"><?= h($it['unidade']) ?></div>
              </div>
              <div class="item-actions">
                <!-- botões editar/excluir (apontam para edição separada) -->
                <a href="orcamento_item_edit.php?orc=<?= h($orc['id']) ?>&item=<?= h($it['id']) ?>" class="btn btn-sm btn-outline-secondary" title="Editar item">✎</a>
                <a href="orcamento_item_delete.php?orc=<?= h($orc['id']) ?>&item=<?= h($it['id']) ?>" class="btn btn-sm btn-outline-danger" title="Excluir item" onclick="return confirm('Remover item?')">🗑</a>
              </div>
            </div>

            <div class="item-bottom">
              <div class="item-qty">Qtd: <span><?= fm($qtd) ?></span></div>
              <div class="small-muted">Valor u.:</div>
              <div class="item-val">R$ <?= fm($unit) ?></div>
              <div class="small-muted">Subtotal</div>
              <div class="item-val">R$ <?= fm($subtotal) ?></div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Totais -->
    <div class="section">
      <?php
        // totals already saved in orçamento table; fallback compute if missing
        $total_venda = isset($orc['valor_total']) ? (float)$orc['valor_total'] : 0;
        $total_custo = isset($orc['valor_custo_total']) ? (float)$orc['valor_custo_total'] : 0;
        $total_desconto = isset($orc['valor_desconto_total']) ? (float)$orc['valor_desconto_total'] : 0;
        // if zeros, compute from itens
        if ($total_venda == 0) {
            $tv = 0; foreach ($itens as $it){ $tv += (float)$it['subtotal']; } $total_venda = $tv;
        }
      ?>

      <div class="totals">
        <div class="row"><div class="small-muted">Total Venda</div><div class="fw-bold">R$ <?= fm($total_venda) ?></div></div>
        <div class="row"><div class="small-muted">Custo Total</div><div class="fw-bold">R$ <?= fm($total_custo) ?></div></div>
        <div class="row"><div class="small-muted">Desconto Total</div><div class="fw-bold">R$ <?= fm($total_desconto) ?></div></div>
        <div class="row" style="border-top:1px dashed #e9ecef; padding-top:8px;"><div class="small-muted">Total a pagar</div>
          <div class="fw-bold fs-5">R$ <?= fm(max(0, $total_venda - $total_desconto)) ?></div></div>
      </div>
    </div>

    <!-- Ações -->
    <div class="section d-flex gap-2 justify-content-between align-items-center">
      <div class="small-muted">ID: <?= h($orc['id']) ?> • Emitido: <?= h($orc['data_fmt']) ?></div>
      <div class="d-flex gap-2">
        <a href="orcamento_edit.php?id=<?= h($orc['id']) ?>" class="btn btn-outline-primary">Editar</a>
        <a href="orcamentos.php" class="btn btn-outline-secondary">Voltar</a>
        <button class="btn btn-success" onclick="window.print()">Gerar PDF / Imprimir</button>
      </div>
    </div>

  </div>
</div>

<!-- scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>