<?php
require 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'layout_header.php';

/* =========================
   PAGINAÇÃO E FILTROS
========================= */
$limite = max(1, (int)($_GET['limite'] ?? 10));
$pagina = max(1, (int)($_GET['p'] ?? 1));
$offset = ($pagina - 1) * $limite;

$status = $_GET['status'] ?? 'Em aberto';
$busca  = trim($_GET['busca'] ?? '');

/* =========================
   CONSTRUÇÃO DO WHERE
========================= */
$whereServ = [];
$paramsServ = [];
$typesServ = '';

if ($status !== 'Todos') {
    $whereServ[] = "s.status_pagamento = ?";
    $paramsServ[] = $status;
    $typesServ .= 's';
}
if ($busca !== '') {
    $whereServ[] = "(s.nome_cliente LIKE ? OR s.servico_executado LIKE ?)";
    $paramsServ[] = "%$busca%"; 
    $paramsServ[] = "%$busca%";
    $typesServ .= 'ss';
}
$sqlWhereServ = $whereServ ? 'WHERE ' . implode(' AND ', $whereServ) : '';

$whereGasto = [];
$paramsGasto = [];
$typesGasto = '';
if ($status !== 'Todos') {
    $whereGasto[] = ($status === 'Em aberto') ? "g.pago = 0" : "g.pago = 1";
}
if ($busca !== '') {
    $whereGasto[] = "g.descricao LIKE ?";
    $paramsGasto[] = "%$busca%";
    $typesGasto .= 's';
}
$sqlWhereGasto = $whereGasto ? 'WHERE ' . implode(' AND ', $whereGasto) : '';

/* =========================
   CÁLCULOS DOS CARDS
========================= */
$sqlResumo = "SELECT 
    SUM(valor_recebido) as tot_recebido, 
    SUM(desloc) as tot_desloc, 
    SUM(reserva_emergencia) as tot_reserva 
    FROM servicos s $sqlWhereServ";

$stmt = $conn->prepare($sqlResumo);
if ($typesServ) $stmt->bind_param($typesServ, ...$paramsServ);
$stmt->execute();
$resSum = $stmt->get_result()->fetch_assoc();

$recebido     = (float)($resSum['tot_recebido'] ?? 0);
$deslocamento = (float)($resSum['tot_desloc'] ?? 0);
$reserva      = (float)($resSum['tot_reserva'] ?? 0);

$sqlItens = "SELECT SUM(si.valor_unit) as tot_itens 
             FROM servico_itens si 
             INNER JOIN servicos s ON s.id = si.servico_id 
             $sqlWhereServ";
$stmt = $conn->prepare($sqlItens);
if ($typesServ) $stmt->bind_param($typesServ, ...$paramsServ);
$stmt->execute();
$itens = (float)($stmt->get_result()->fetch_assoc()['tot_itens'] ?? 0);

$sqlG = "SELECT SUM(valor) as tot_gastos FROM gastos_extras g $sqlWhereGasto";
$stmt = $conn->prepare($sqlG);
if ($typesGasto) $stmt->bind_param($typesGasto, ...$paramsGasto);
$stmt->execute();
$gastos = (float)($stmt->get_result()->fetch_assoc()['tot_gastos'] ?? 0);

$lucro_liquido = $recebido - $itens - $deslocamento - $reserva - $gastos;
$socio = $lucro_liquido / 2;

/* =========================
   LISTAGEM UNIFICADA
========================= */
$sqlLista = "(SELECT s.id, s.data, 'servico' AS tipo, s.nome_cliente AS cliente, s.servico_executado AS descr, s.valor_recebido AS v, s.status_pagamento AS st FROM servicos s $sqlWhereServ)
             UNION ALL
             (SELECT g.id, g.data, 'gasto_extra', '—', g.descricao, g.valor, IF(g.pago=1,'Pago total','Em aberto') FROM gastos_extras g $sqlWhereGasto)
             ORDER BY data DESC LIMIT ?, ?";

$paramsLista = array_merge($paramsServ, $paramsGasto, [$offset, $limite]);
$typesLista  = $typesServ . $typesGasto . 'ii';

$stmt = $conn->prepare($sqlLista);
$stmt->bind_param($typesLista, ...$paramsLista);
$stmt->execute();
$lista = $stmt->get_result();
?>

<div class="container-fluid mt-3">
    <h4 class="mb-3">🛠 Gestão de Serviços</h4>

    <form method="GET" class="row g-2 mb-4">
        <div class="col-7 col-md-4">
            <input type="text" name="busca" class="form-control" placeholder="Buscar..." value="<?=htmlspecialchars($busca)?>">
        </div>
        <div class="col-5 col-md-3">
            <select name="status" class="form-select">
                <option value="Todos" <?=$status=='Todos'?'selected':''?>>Todos</option>
                <option value="Em aberto" <?=$status=='Em aberto'?'selected':''?>>Em aberto</option>
                <option value="Pago total" <?=$status=='Pago total'?'selected':''?>>Pago total</option>
            </select>
        </div>
        <div class="col-12 col-md-1">
            <button type="submit" class="btn btn-secondary w-100">Filtrar</button>
        </div>
    </form>

    <div class="row g-2 mb-4 text-center">
        <div class="col-6 col-md-2">
            <div class="card card-body p-2 shadow-sm border-0 bg-light">
                <small class="text-muted">💰 Recebido</small>
                <h6 class="mb-0">R$ <?=number_format($recebido,2,',','.')?></h6>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card card-body p-2 shadow-sm border-0 bg-light">
                <small class="text-muted">📦 Itens</small>
                <h6 class="mb-0">R$ <?=number_format($itens,2,',','.')?></h6>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card card-body p-2 shadow-sm border-0 bg-light">
                <small class="text-muted">🚗 Desloc.</small>
                <h6 class="mb-0">R$ <?=number_format($deslocamento,2,',','.')?></h6>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card card-body p-2 shadow-sm border-0 bg-light">
                <small class="text-muted">🏦 Reserva</small>
                <h6 class="mb-0">R$ <?=number_format($reserva,2,',','.')?></h6>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card card-body p-2 shadow-sm border-danger">
                <small class="text-danger">💸 Gastos</small>
                <h6 class="mb-0 text-danger">R$ <?=number_format($gastos,2,',','.')?></h6>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card card-body p-2 shadow-sm border-success">
                <small class="text-success">👥 Cada Sócio</small>
                <h6 class="mb-0 text-success">R$ <?=number_format($socio,2,',','.')?></h6>
            </div>
        </div>
    </div>

    <form method="post" action="servicos_acoes.php" id="formAcoes">
        <div class="mb-2 d-flex gap-2 flex-wrap">
            <button type="submit" name="acao" value="pago" class="btn btn-success btn-sm">
                <i class="bi bi-check-circle"></i> Marcar Pago
            </button>
            <button type="submit" name="acao" value="aberto" class="btn btn-warning btn-sm">
                <i class="bi bi-clock"></i> Marcar Aberto
            </button>
            <button type="button" class="btn btn-info btn-sm" onclick="gerarPDFSelecionados()">
                <i class="bi bi-file-pdf"></i> PDF Selecionados
            </button>
            <button type="submit" name="acao" value="excluir" class="btn btn-danger btn-sm" onclick="return confirm('Excluir selecionados?')">
                <i class="bi bi-trash"></i> Excluir
            </button>
            <a href="servico_add.php" class="btn btn-primary btn-sm ms-auto">
                <i class="bi bi-plus-circle"></i> Novo
            </a>
        </div>

        <div class="table-responsive shadow-sm">
            <table class="table table-hover table-bordered text-center align-middle bg-white mb-0">
                <thead class="table-dark">
                    <tr>
                        <th width="30">
                            <input type="checkbox" id="checkAll" onclick="document.querySelectorAll('.ckItem').forEach(c=>c.checked=this.checked)">
                        </th>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Cliente / Descrição</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th width="120">Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($r=$lista->fetch_assoc()): ?>
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="<?=$r['tipo'].':'.$r['id']?>" class="ckItem"></td>
                        <td><?=date('d/m/Y', strtotime($r['data']))?></td>
                        <td>
                            <span class="badge <?=$r['tipo']=='servico'?'bg-primary':'bg-danger'?>">
                                <?=$r['tipo']=='servico'?'Serviço':'Gasto'?>
                            </span>
                        </td>
                        <td class="text-start">
                            <strong><?=htmlspecialchars($r['cliente'])?></strong><br>
                            <small class="text-muted"><?=htmlspecialchars($r['descr'])?></small>
                        </td>
                        <td>R$ <?=number_format($r['v'],2,',','.')?></td>
                        <td>
                            <span class="badge <?=$r['st']=='Pago total'?'bg-success':'bg-warning'?>">
                                <?=$r['st']?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <?php if($r['tipo']=='servico'): ?>
                                    <a href="servico_view.php?id=<?=$r['id']?>" 
                                       class="btn btn-info" 
                                       title="Visualizar">
                                        👁️
                                    </a>
                                    <a href="servico_edit.php?id=<?=$r['id']?>" 
                                       class="btn btn-warning" 
                                       title="Editar">
                                        ✏️
                                    </a>
                                <?php else: ?>
                                    <a href="gasto_edit.php?id=<?=$r['id']?>" 
                                       class="btn btn-warning" 
                                       title="Editar Gasto">
                                        ✏️
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </form>
</div>

<script>
function gerarPDFSelecionados() {
    const checkboxes = document.querySelectorAll('.ckItem:checked');
    
    if (checkboxes.length === 0) {
        alert('⚠️ Selecione pelo menos um item para gerar o PDF!');
        return;
    }
    
    const ids = [];
    checkboxes.forEach(cb => {
        ids.push(cb.value);
    });
    
    // Redireciona para página de PDF
    window.open('servicos_pdf_multi.php?ids=' + encodeURIComponent(ids.join(',')), '_blank');
}
</script>

<?php include 'layout_footer.php'; ?>