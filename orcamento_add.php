<?php
// orcamento_add.php - Versão final com UI mobile (estilo B) + edição via modal + "novo item"
require "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$menu = "orcamentos";
include "layout_header.php";
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Novo Orçamento</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<style>
/* --- Visual geral --- */
body { background:#f5f5f5; }
.card{ margin-top:18px; border-radius:12px; }
.produto-thumb { width:56px; height:56px; object-fit:cover; border-radius:10px; background:#f7f7f7; }
.small-muted { font-size:0.85rem; color:#6c757d; }
.table-itens td, .table-itens th { vertical-align: middle; }
#clienteLista { z-index:1400; max-height:260px; overflow:auto; }
.input-moeda, .input-percent { text-align:right; }
.modal-prod-table td, .modal-prod-table th { vertical-align: middle; }

/* --- Estilo B: cards arredondados, ícone à esquerda --- */
.item-card {
  display:flex; gap:12px; align-items:center;
  background:#fff; border-radius:14px; padding:12px;
  box-shadow:0 1px 3px rgba(0,0,0,0.04); margin-bottom:10px;
  border:1px solid #ececec;
}
.item-card .thumb { width:52px; height:52px; border-radius:10px; background:#f7f7f7; display:flex; align-items:center; justify-content:center; }
.item-card .meta { flex:1; }
.item-card .meta .title { font-weight:600; font-size:1rem; margin-bottom:4px; }
.item-card .meta .sub { color:#0d6efd; font-weight:600; font-size:0.92rem; margin-bottom:6px; }
.item-card .actions { display:flex; gap:8px; align-items:center; }
.item-card .actions .btn { min-width:36px; }

/* mobile list container */
#itensMobile { margin-top:10px; }

/* desktop table hidden on small screens */
@media (max-width: 767px) {
  .desktop-only { display:none !important; }
}
@media (min-width: 768px) {
  .mobile-only { display:none !important; }
}

/* small helpers */
.text-muted-small { font-size:0.85rem; color:#6c757d; }
.form-control-compact { padding:.35rem .5rem; font-size:.9rem; }
</style>
</head>
<body>

<div class="container">
  <div class="card shadow">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> Novo Orçamento</h5>
      <small class="text-white-50">Layout móvel estilo B — editar por modal</small>
    </div>

    <div class="card-body">
      <?php if (!empty($_SESSION['message'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
      <?php endif; ?>
      <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
      <?php endif; ?>

      <form method="POST" action="orcamento_add_action.php" id="formOrcamento" autocomplete="off">

        <!-- CLIENTE -->
        <div class="row g-2 mb-3">
          <div class="col-lg-6 position-relative">
            <label class="form-label">Buscar cliente</label>
            <input type="hidden" name="cliente_id" id="cliente_id" value="0">
            <input type="text" class="form-control" id="cliente_busca" placeholder="Nome, telefone ou CPF..." />
            <div id="clienteLista" class="list-group position-absolute w-100 bg-white border d-none"></div>
            <div class="small-muted mt-1">Digite ao menos 2 caracteres. Selecione para preencher.</div>
          </div>

          <div class="col-lg-6">
            <label class="form-label">Nome (ou editar)</label>
            <input type="text" name="cliente_nome" id="cliente_nome" class="form-control" required>
          </div>

          <div class="col-md-4 mt-2">
            <label class="form-label">Telefone</label>
            <input type="text" name="telefone" id="cliente_telefone" class="form-control">
          </div>
          <div class="col-md-4 mt-2">
            <label class="form-label">Cidade</label>
            <input type="text" name="cidade" id="cliente_cidade" class="form-control">
          </div>
          <div class="col-md-4 mt-2">
            <label class="form-label">Endereço</label>
            <input type="text" name="endereco" id="cliente_endereco" class="form-control">
          </div>
        </div>

        <!-- STATUS / GARANTIA / DESCRIÇÃO -->
        <div class="row g-2 mb-3">
          <div class="col-md-4">
            <label class="form-label">Status</label>
            <select name="status" id="status" class="form-select">
              <option value="aberto" data-color="#0d6efd">Aberto</option>
              <option value="aprovado" data-color="#198754">Aprovado</option>
              <option value="vencido" data-color="#dc3545">Vencido</option>
              <option value="recusado" data-color="#fd7e14">Recusado</option>
              <option value="cancelado" data-color="#6c757d">Cancelado</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Garantia</label>
            <select name="garantia" id="garantia" class="form-select">
              <option value="1">1 mês</option>
              <option value="3">3 meses</option>
              <option value="6">6 meses</option>
              <option value="12">1 ano</option>
            </select>
          </div>
          <div class="col-md-5">
            <label class="form-label">Descrição</label>
            <input type="text" name="descricao" id="descricao" class="form-control" placeholder="Observações breves...">
          </div>
        </div>

        <!-- ITENS -->
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="mb-0">Itens do Orçamento</h6>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-success btn-sm" id="abrirProdutos"><i class="bi bi-box-seam"></i> Selecionar produtos</button>
            <button type="button" class="btn btn-outline-primary btn-sm" id="novoItemBtn"><i class="bi bi-plus-lg"></i> Novo item</button>
          </div>
        </div>

        <!-- Desktop table (visible em desktop) -->
        <div class="table-responsive desktop-only mb-3">
          <table class="table table-bordered table-itens" id="tabelaItens">
            <thead class="table-light">
              <tr>
                <th>Produto / Serviço</th>
                <th style="width:80px">Un</th>
                <th style="width:110px">Qtd</th>
                <th style="width:140px">Valor Unit. (R$)</th>
                <th style="width:140px">Subtotal (R$)</th>
                <th style="width:60px"></th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>

        <!-- Mobile cards (visual estilo B) -->
        <div id="itensMobile" class="mobile-only mb-3"></div>

        <!-- Desconto geral -->
        <div class="row g-2 align-items-center mt-2 mb-3">
          <div class="col-md-6 ms-auto">
            <div class="input-group">
              <span class="input-group-text">Desconto geral</span>
              <input type="text" id="desconto_pct" class="form-control input-percent" placeholder="% (ex: 10)" />
              <span class="input-group-text">ou R$</span>
              <input type="text" id="desconto_val" class="form-control input-moeda" placeholder="0,00" />
            </div>
            <div class="small-muted mt-1">Informe desconto em % ou em valor. Os dois ficam sincronizados.</div>
          </div>
        </div>

        <!-- Resumo -->
        <div class="row mb-3">
          <div class="col-md-6 ms-auto">
            <table class="table table-borderless">
              <tr>
                <th class="text-end">Total Venda:</th>
                <td id="totalVenda">R$ 0,00</td>
              </tr>
              <tr>
                <th class="text-end">Custo Total:</th>
                <td id="totalCusto">R$ 0,00</td>
              </tr>
              <tr>
                <th class="text-end">Desconto Geral:</th>
                <td id="totalDesconto">R$ 0,00</td>
              </tr>
              <tr>
                <th class="text-end text-success">Total Final:</th>
                <td id="totalFinal" class="text-success fw-bold">R$ 0,00</td>
              </tr>
            </table>
          </div>
        </div>

        <input type="hidden" name="valor_total" id="valor_total">
        <input type="hidden" name="valor_custo_total" id="valor_custo_total">
        <input type="hidden" name="valor_desconto_total" id="valor_desconto_total">

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Salvar Orçamento</button>
          <a href="orcamentos.php" class="btn btn-secondary">← Voltar</a>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Buscar produtos -->
<div class="modal fade" id="modalProdutos" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title"><i class="bi bi-search"></i> Selecionar produtos</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="text" id="prodBusca" class="form-control mb-3" placeholder="Buscar produto por nome, código, descrição..." />
        <div class="table-responsive">
          <table class="table table-hover modal-prod-table">
            <thead class="table-light">
              <tr>
                <th style="width:40px"><input type="checkbox" id="checkAllProds"></th>
                <th style="width:64px">Foto</th>
                <th>Nome / Modelo</th>
                <th style="width:120px">Venda (R$)</th>
                <th style="width:120px">Custo (R$)</th>
              </tr>
            </thead>
            <tbody id="produtosLista">
              <tr><td colspan="5" class="text-center small-muted">Digite para buscar produtos...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <div class="me-auto small-muted">Marque os itens e clique em "Adicionar selecionados". Seleções persistem entre buscas.</div>
        <button class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
        <button class="btn btn-success" id="adicionarSelecionados">Adicionar selecionados</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Editar item -->
<div class="modal fade" id="modalEditarItem" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header"><h6 class="modal-title">Editar item</h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form id="formEditarItem">
          <div class="mb-2"><label class="form-label">Quantidade</label><input type="number" step="0.01" min="0.01" class="form-control" id="editar_qtd"></div>
          <div class="mb-2"><label class="form-label">Valor unitário (R$)</label><input type="text" class="form-control" id="editar_valor"></div>
          <input type="hidden" id="editar_target_index">
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" id="salvarEdicaoItem">Salvar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Novo item manual -->
<div class="modal fade" id="modalNovoItem" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header"><h6 class="modal-title">Novo item (manual)</h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form id="formNovoItem">
          <div class="mb-2"><label class="form-label">Nome</label><input type="text" class="form-control" id="novo_nome" required></div>
          <div class="row g-2">
            <div class="col"><label class="form-label">Unidade</label><input type="text" class="form-control" id="novo_unidade" value="un"></div>
            <div class="col"><label class="form-label">Quantidade</label><input type="number" step="0.01" min="0.01" class="form-control" id="novo_qtd" value="1"></div>
            <div class="col"><label class="form-label">Valor unit. (R$)</label><input type="text" class="form-control" id="novo_valor" value="0,00"></div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-success" id="adicionarNovoItem">Adicionar</button>
      </div>
    </div>
  </div>
</div>

<script>
$(function(){

  /* ---------- util ---------- */
  function formatMoeda(v){ return Number(v || 0).toFixed(2).replace('.', ','); }
  function parseMoedaBr(str){
    if (str === undefined || str === null) return 0;
    if (typeof str === 'number') return str;
    str = String(str).trim();
    if (str === '') return 0;
    if (str.indexOf(',') !== -1 && str.indexOf('.') !== -1 && str.indexOf(',') > str.indexOf('.')) {
      str = str.replace(/\./g,'').replace(',','.');
    } else { str = str.replace(',','.'); }
    var f = parseFloat(str.replace(/[^0-9.\-]/g,'')); return isNaN(f) ? 0 : f;
  }
  function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g, function(m){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m];}); }

  /* ---------- CLIENTE AUTOCOMPLETE ---------- */
  var clienteTimer = null;
  $('#cliente_busca').on('input', function(){
    var q = $(this).val().trim(); clearTimeout(clienteTimer);
    if (q.length < 2) { $('#clienteLista').addClass('d-none').empty(); return; }
    clienteTimer = setTimeout(function(){
      $.ajax({
        url: 'clientes_search.php', dataType: 'json', data: { term: q },
        success: function(data){
          var list = $('#clienteLista').empty();
          if (!Array.isArray(data) || data.length === 0) { list.append('<div class="list-group-item small-muted">Nenhum cliente encontrado</div>').removeClass('d-none'); return; }
          data.forEach(function(it){
            var nome = escapeHtml(it.nome || it.value || '');
            var telefone = escapeHtml(it.telefone || '');
            var endereco = escapeHtml(it.endereco || '');
            var cidade = escapeHtml(it.cidade || '');
            var el = $('<button type="button" class="list-group-item list-group-item-action text-start"></button>');
            el.html('<div class="fw-bold">'+nome+'</div><div class="small-muted">'+telefone + (cidade? ' — '+cidade : '') +'</div><div class="small-muted">'+endereco+'</div>');
            el.on('click', function(){
              $('#cliente_id').val(it.id || 0);
              $('#cliente_nome').val(it.nome || '');
              $('#cliente_telefone').val(it.telefone || '');
              $('#cliente_endereco').val(it.endereco || '');
              $('#cliente_cidade').val(it.cidade || '');
              $('#clienteLista').addClass('d-none').empty(); $('#cliente_busca').val('');
            });
            list.append(el);
          });
          list.removeClass('d-none');
        },
        error: function(){ $('#clienteLista').addClass('d-none').empty(); }
      });
    }, 220);
  });
  $(document).on('click', function(e){ if (!$(e.target).closest('#cliente_busca, #clienteLista').length) $('#clienteLista').addClass('d-none'); });

  /* ---------- ITENS: manipulação e sincronização entre desktop <-> mobile ---------- */
  // products selected in modal persist across searches
  let produtosSelecionados = new Set();

  function adicionarLinhaItem(prod){
    prod = prod || {};
    var prodId = prod.id || '';
    var idField = prodId ? '<input type="hidden" name="produto_id[]" value="'+escapeHtml(prodId)+'">' : '';
    var nomeEsc = escapeHtml(prod.nome||'');
    var unidade = escapeHtml(prod.unidade||'un');
    var valorUnit = formatMoeda(prod.preco_venda !== undefined ? prod.preco_venda : (prod.preco !== undefined ? prod.preco : 0));
    var custoUnit = (prod.preco_custo !== undefined ? formatMoeda(prod.preco_custo) : '0,00');
    var imagem = prod.imagem ? 'uploads/produtos/'+escapeHtml(prod.imagem) : '';

    // --- desktop row ---
    var row = $(
      '<tr>' +
        '<td>' + idField +
          '<div class="d-flex align-items-center gap-2">' +
            '<div><img src="'+(imagem || 'https://via.placeholder.com/56?text=No')+'" class="produto-thumb" onerror="this.src=\'https://via.placeholder.com/56?text=No\';"></div>' +
            '<div style="flex:1"><input type="text" name="item_nome[]" class="form-control item_nome" value="'+nomeEsc+'"><div class="text-muted-small">Custo/un: R$ <span class="linha-custo-por-unidade">'+custoUnit+'</span></div></div>' +
          '</div>' +
        '</td>' +
        '<td><input type="text" name="unidade[]" class="form-control form-control-compact text-center unidade" value="'+unidade+'"></td>' +
        '<td><input type="number" step="0.01" name="item_qtd[]" class="form-control form-control-compact text-end item_qtd" value="1" min="0.01"></td>' +
        '<td><input type="text" name="item_valor[]" class="form-control form-control-compact text-end item_valor input-moeda" value="'+valorUnit+'"></td>' +
        '<td><input type="text" name="item_total[]" class="form-control form-control-compact text-end item_total" readonly></td>' +
        '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-secondary editar-item" title="Editar"><i class="bi bi-pencil"></i></button> <button type="button" class="btn btn-sm btn-outline-danger remove-item" title="Remover"><i class="bi bi-trash"></i></button></td>' +
      '</tr>'
    );

    // --- mobile card ---
    var card = $(
      '<div class="item-card" data-prod-id="'+escapeHtml(prodId||'')+'">' +
        '<div class="thumb"><img src="'+(imagem || 'https://via.placeholder.com/40?text=No')+'" style="width:40px;height:40px;border-radius:8px" onerror="this.src=\'https://via.placeholder.com/40?text=No\';"></div>' +
        '<div class="meta">' +
          '<div class="sub">R$ '+valorUnit+' | '+ (unidade) +'</div>' +
          '<div class="title">'+nomeEsc+'</div>' +
        '</div>' +
        '<div class="actions">' +
          '<button class="btn btn-sm btn-outline-secondary editar-item-mobile" title="Editar"><i class="bi bi-pencil"></i></button>' +
          '<button class="btn btn-sm btn-outline-danger remove-item-mobile" title="Remover"><i class="bi bi-trash"></i></button>' +
        '</div>' +
      '</div>'
    );

    // append both
    $('#tabelaItens tbody').append(row);
    $('#itensMobile').append(card);

    // bind events
    row.find('.item_qtd, .item_valor').on('input change', function(){ calcularTotais(); });
    row.find('.remove-item').on('click', function(){ // remove both
      var pid = row.find('input[name="produto_id[]"]').val() || '';
      row.remove();
      $('#itensMobile .item-card').filter(function(){ return $(this).data('prod-id') == pid; }).remove();
      calcularTotais();
    });

        // edit desktop -> open modal with index
    row.find('.editar-item').on('click', function(){
      var index = $('#tabelaItens tbody tr').index(row);
      var qtd = row.find('.item_qtd').val();
      var valor = row.find('.item_valor').val();
      $('#editar_target_index').val(index);
      $('#editar_qtd').val(qtd);
      $('#editar_valor').val(valor);
      $('#modalEditarItem').modal('show');
    });

    // remove mobile
    card.find('.remove-item-mobile').on('click', function(){
      var pid = card.data('prod-id') || '';
      card.remove();
      $('#tabelaItens tbody tr').filter(function(){ return ($(this).find('input[name="produto_id[]"]').val() || '') == pid; }).remove();
      calcularTotais();
    });

    // edit mobile: find corresponding desktop row and open edit modal
    card.find('.editar-item-mobile').on('click', function(){
      // if product has id, find the desktop row
      var pid = card.data('prod-id') || '';
      var rowFound = null;
      if (pid) {
        $('#tabelaItens tbody tr').each(function(){
          if ( ($(this).find('input[name="produto_id[]"]').val() || '') == pid) { rowFound = $(this); return false; }
        });
      } else {
        // if no id (manual), use index order
        rowFound = $('#tabelaItens tbody tr').eq($('#itensMobile .item-card').index(card));
      }
      if (!rowFound) return;
      var index = $('#tabelaItens tbody tr').index(rowFound);
      $('#editar_target_index').val(index);
      $('#editar_qtd').val(rowFound.find('.item_qtd').val());
      $('#editar_valor').val(rowFound.find('.item_valor').val());
      $('#modalEditarItem').modal('show');
    });

    calcularTotais();
  }

  // salvar edição do modal
  $('#salvarEdicaoItem').on('click', function(e){
    e.preventDefault();
    var index = parseInt($('#editar_target_index').val());
    if (isNaN(index)) { $('#modalEditarItem').modal('hide'); return; }
    var qtd = parseFloat($('#editar_qtd').val()) || 0;
    var valor = $('#editar_valor').val();
    // update desktop row
    var row = $('#tabelaItens tbody tr').eq(index);
    if (row.length) {
      row.find('.item_qtd').val(qtd);
      row.find('.item_valor').val(valor);
    }
    // update corresponding mobile card (by product_id if available)
    var pid = row.find('input[name="produto_id[]"]').val() || '';
    if (pid) {
      $('#itensMobile .item-card').filter(function(){ return $(this).data('prod-id') == pid; }).each(function(){
        $(this).find('.sub').text('R$ ' + formatMoeda(parseMoedaBr(valor)) + ' | ' + row.find('.unidade').val());
      });
    } else {
      // try sync by index
      $('#itensMobile .item-card').eq(index).find('.sub').text('R$ ' + formatMoeda(parseMoedaBr(valor)) + ' | ' + row.find('.unidade').val());
    }
    $('#modalEditarItem').modal('hide');
    calcularTotais();
  });

  // novo item modal
  $('#novoItemBtn').on('click', function(){ $('#modalNovoItem').modal('show'); });
  $('#adicionarNovoItem').on('click', function(e){
    e.preventDefault();
    var nome = $('#novo_nome').val().trim();
    if (!nome) { alert('Informe o nome do item'); return; }
    var unidade = $('#novo_unidade').val().trim() || 'un';
    var qtd = parseFloat($('#novo_qtd').val()) || 0;
    var valor = $('#novo_valor').val() || '0,00';
    // create pseudo product object (no id)
    adicionarLinhaItem({ id: '', nome: nome, unidade: unidade, preco_venda: parseMoedaBr(valor), preco_custo: 0, imagem: '' });
    $('#modalNovoItem').modal('hide');
    // clear form
    $('#novo_nome').val(''); $('#novo_qtd').val('1'); $('#novo_valor').val('0,00'); calcularTotais();
  });

  // delegated changes on desktop inputs (sync mobile)
  $(document).on('input change', '.item_qtd, .item_valor', function(){ calcularTotais(); });

  /* ---------- calcular totais ---------- */
  function calcularTotais(){
    var totalVenda = 0;
    var totalCusto = 0;

    // compute per desktop row
    $('#tabelaItens tbody tr').each(function(){
      var qtd = parseFloat($(this).find('.item_qtd').val()) || 0;
      var valor = parseMoedaBr($(this).find('.item_valor').val());
      var custoUn = parseMoedaBr($(this).find('.linha-custo-por-unidade').text() || '0');
      var subtotal = qtd * valor;
      $(this).find('.item_total').val(formatMoeda(subtotal));
      totalVenda += subtotal;
      totalCusto += qtd * custoUn;
    });

    // desconto geral sync
    var descontoPct = parseFloat($('#desconto_pct').val().toString().replace(',','.')) || 0;
    var descontoVal = parseMoedaBr($('#desconto_val').val());
    var active = document.activeElement;
    if ($(active).is('#desconto_pct')) {
      descontoVal = (descontoPct/100) * totalVenda;
      $('#desconto_val').val(formatMoeda(descontoVal));
    } else if ($(active).is('#desconto_val')) {
      descontoPct = totalVenda ? (descontoVal / totalVenda) * 100 : 0;
      $('#desconto_pct').val(descontoPct.toFixed(2).replace('.',','));
    } else {
      descontoVal = (descontoPct/100) * totalVenda;
      $('#desconto_val').val(formatMoeda(descontoVal));
    }

    var totalDesconto = descontoVal;
    var totalFinal = totalVenda - totalDesconto;
    if (totalFinal < 0) totalFinal = 0;

    $('#totalVenda').text('R$ ' + formatMoeda(totalVenda));
    $('#totalCusto').text('R$ ' + formatMoeda(totalCusto));
    $('#totalDesconto').text('R$ ' + formatMoeda(totalDesconto));
    $('#totalFinal').text('R$ ' + formatMoeda(totalFinal));

    $('#valor_total').val(totalVenda.toFixed(2));
    $('#valor_custo_total').val(totalCusto.toFixed(2));
    $('#valor_desconto_total').val(totalDesconto.toFixed(2));

    // update mobile cards subtotals + sub text
    $('#tabelaItens tbody tr').each(function(index){
      var pid = $(this).find('input[name="produto_id[]"]').val() || '';
      var subtotal = $(this).find('.item_total').val() || '0,00';
      var valor = $(this).find('.item_valor').val() || '0,00';
      var unidade = $(this).find('.unidade').val() || 'un';
      if (pid) {
        $('#itensMobile .item-card').filter(function(){ return $(this).data('prod-id') == pid; }).each(function(){
          $(this).find('.sub').text('R$ ' + formatMoeda(parseMoedaBr(valor)) + ' | ' + unidade);
        });
      } else {
        // sync by order if no product id
        $('#itensMobile .item-card').eq(index).find('.sub').text('R$ ' + formatMoeda(parseMoedaBr(valor)) + ' | ' + unidade);
      }
      // also update any displayed subtotal inside mobile if needed (not shown prominently here)
    });
  }

  /* ---------- PRODUTOS: busca + persistência de seleção ---------- */
  var prodTimer = null;
  function buscarProdutos(q){
    $.ajax({
      url: 'produtos_search.php',
      dataType: 'json',
      data: { q: q || '' },
      success: function(data){ renderProdutosLista(Array.isArray(data) ? data : []); },
      error: function(){ $('#produtosLista').html('<tr><td colspan="5" class="text-danger">Erro ao buscar produtos.</td></tr>'); }
    });
  }

  function renderProdutosLista(produtos){
    var tbody = $('#produtosLista').empty();
    if (!produtos.length){
      tbody.append('<tr><td colspan="5" class="text-muted text-center">Nenhum produto encontrado.</td></tr>');
      return;
    }
    produtos.forEach(function(p){
      var id = String(p.id || '');
      var nome = escapeHtml(p.nome || p.label || '');
      var imagem = p.imagem ? escapeHtml(p.imagem) : '';
      var venda = (p.preco_venda !== undefined ? p.preco_venda : (p.preco !== undefined ? p.preco : 0));
      var custo = (p.preco_custo !== undefined ? p.preco_custo : (p.custo !== undefined ? p.custo : 0));
      var unidade = escapeHtml(p.unidade || 'un');

      var checkbox = $('<input>', { type: 'checkbox', class: 'form-check checkProduto' })
        .data('id', id)
        .data('nome', nome)
        .data('preco', venda)
        .data('custo', custo)
        .data('imagem', imagem)
        .data('unidade', unidade);

      if (produtosSelecionados.has(id)) checkbox.prop('checked', true);

      checkbox.on('change', function(){
        if (this.checked) produtosSelecionados.add(id); else produtosSelecionados.delete(id);
      });

      var row = $('<tr>');
      row.append($('<td>').append(checkbox));
      row.append($('<td>').append($('<img>').attr('src', imagem ? 'uploads/produtos/'+imagem : 'https://via.placeholder.com/56?text=No').addClass('produto-thumb').on('error', function(){ this.src='https://via.placeholder.com/56?text=No'; })));
      row.append($('<td>').text(nome));
      row.append($('<td>').text('R$ ' + Number(venda||0).toFixed(2).replace('.',',')));
      row.append($('<td>').text('R$ ' + Number(custo||0).toFixed(2).replace('.',',')));
      tbody.append(row);
    });
    $('#checkAllProds').prop('checked', false);
  }

  // abrir modal
  $('#abrirProdutos').on('click', function(){ $('#modalProdutos').modal('show'); $('#prodBusca').val(''); buscarProdutos(''); });

  // busca debounce
  $('#prodBusca').on('input', function(){
    var q = $(this).val().trim();
    clearTimeout(prodTimer);
    prodTimer = setTimeout(function(){ buscarProdutos(q); }, 220);
  });

  // check all behavior (also affects produtosSelecionados)
  $(document).on('change', '#checkAllProds', function(){
    var marcar = $(this).prop('checked');
    $('.checkProduto').each(function(){ $(this).prop('checked', marcar); var id = String($(this).data('id')); if (marcar) produtosSelecionados.add(id); else produtosSelecionados.delete(id); });
  });

  // adicionar selecionados (evita duplicados)
  $('#adicionarSelecionados').on('click', function(){
    $('.checkProduto:checked').each(function(){
      var obj = {
        id: $(this).data('id'),
        nome: $(this).data('nome'),
        unidade: $(this).data('unidade'),
        preco_venda: $(this).data('preco'),
        preco_custo: $(this).data('custo'),
        imagem: $(this).data('imagem')
      };
      // check duplicates by produto_id[]
      var exists = false;
      $('#tabelaItens input[name="produto_id[]"]').each(function(){ if (String($(this).val()) === String(obj.id)) exists = true; });
      if (!exists) adicionarLinhaItem(obj);
    });
    $('#modalProdutos').modal('hide');
    calcularTotais();
  });

  /* ---------- Init: sem linha vazia por padrão (user adiciona) ---------- */
  calcularTotais();

}); // fim $
</script>

<?php include "layout_footer.php"; ?>
</body>
</html>
    
    