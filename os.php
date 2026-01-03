<?php
require "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include "layout_header.php";

// ==========================================================
// CONFIGURAÇÃO DE PAGINAÇÃO E FILTROS
// ==========================================================
$limite_por_pagina = 10;
$pagina_atual = intval($_GET['pagina'] ?? 1);
$offset = ($pagina_atual - 1) * $limite_por_pagina;

// Parâmetros de filtro
$search_term = $_GET['search_term'] ?? '';
$status_filtro = $_GET['status'] ?? '';
$data_ini = $_GET['data_ini'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';

$where = [];
$params = [];
$types = '';

// Filtro por busca (cliente, ID ou serviço)
if (!empty($search_term)) {
    $where[] = "(c.nome LIKE ? OR o.cliente_nome LIKE ? OR o.id LIKE ? OR o.servico LIKE ?)";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
    $types .= 'ssss';
}

// Filtro por status
if ($status_filtro !== '') {
    $where[] = "o.status = ?";
    $params[] = $status_filtro;
    $types .= 's';
}

// Filtro de datas
$data_ini_db = !empty($data_ini) ? date('Y-m-d', strtotime($data_ini)) : '';
$data_fim_db = !empty($data_fim) ? date('Y-m-d', strtotime($data_fim)) : '';

if ($data_ini_db !== '' && $data_fim_db !== '') {
    $where[] = "DATE(o.data_agendada) BETWEEN ? AND ?";
    $params[] = $data_ini_db;
    $params[] = $data_fim_db;
    $types .= 'ss';
} elseif ($data_ini_db !== '') {
    $where[] = "DATE(o.data_agendada) >= ?";
    $params[] = $data_ini_db;
    $types .= 's';
} elseif ($data_fim_db !== '') {
    $where[] = "DATE(o.data_agendada) <= ?";
    $params[] = $data_fim_db;
    $types .= 's';
}

$whereSQL = count($where) ? "WHERE " . implode(" AND ", $where) : "";

// ==========================================================
// TOTAL DE REGISTROS
// ==========================================================
$sql_total = "
    SELECT COUNT(o.id) AS total 
    FROM os o 
    LEFT JOIN clientes c ON c.id = o.cliente_id 
    $whereSQL
";

if ($types) {
    $stmt_total = $conn->prepare($sql_total);
    $stmt_total->bind_param($types, ...$params);
    $stmt_total->execute();
    $total_registros = $stmt_total->get_result()->fetch_assoc()['total'];
} else {
    $total_registros = $conn->query($sql_total)->fetch_assoc()['total'];
}

$total_paginas = ceil($total_registros / $limite_por_pagina);

// ==========================================================
// CONSULTA COM PAGINAÇÃO
// ==========================================================
$sql = "
    SELECT 
        o.*,
        COALESCE(c.nome, o.cliente_nome) AS cliente_nome_exibir,
        c.telefone AS cliente_telefone_cad
    FROM os o 
    LEFT JOIN clientes c ON c.id = o.cliente_id 
    $whereSQL
    ORDER BY o.id DESC
    LIMIT ? OFFSET ?
";

$params_lista = $params;
$params_lista[] = $limite_por_pagina;
$params_lista[] = $offset;
$types_lista = $types . 'ii';

$stmt = $conn->prepare($sql);
if ($types_lista) {
    $stmt->bind_param($types_lista, ...$params_lista);
}
$stmt->execute();
$resultado = $stmt->get_result();

$query_string = http_build_query([
    'search_term' => $search_term,
    'status' => $status_filtro,
    'data_ini' => $data_ini,
    'data_fim' => $data_fim
]);

?>

<div class="container-fluid">

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mt-4 mb-3 flex-wrap gap-2">
    <div>
        <h3 class="mb-1">📋 Ordens de Serviço</h3>
        <small class="text-muted">Gerencie todas as OS do sistema</small>
    </div>
    <div class="d-flex gap-2">
        <a href="os_calendario.php" class="btn btn-outline-info btn-sm">
            <i class="bi bi-calendar"></i> Calendário
        </a>
        <a href="os_add.php" class="btn btn-success btn-sm">
            <i class="bi bi-plus-circle"></i> Nova OS
        </a>
    </div>
</div>

<!-- Mensagens -->
<?php if (isset($_SESSION['msg'])): ?>
    <div class="alert alert-info alert-dismissible fade show">
        <?= htmlspecialchars($_SESSION['msg']); unset($_SESSION['msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['msg_sucesso'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= htmlspecialchars($_SESSION['msg_sucesso']); unset($_SESSION['msg_sucesso']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filtros -->
<form method="GET" class="card shadow-sm mb-4" style="border-radius: 12px;">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold">
                    <i class="bi bi-search"></i> Buscar
                </label>
                <input 
                    type="text" 
                    name="search_term" 
                    class="form-control" 
                    placeholder="Cliente, ID ou Serviço..." 
                    value="<?= htmlspecialchars($search_term) ?>"
                >
            </div>
            
            <div class="col-md-2">
                <label class="form-label fw-semibold">
                    <i class="bi bi-tag"></i> Status
                </label>
                <select name="status" class="form-select">
                    <option value="">Todos</option>
                    <?php
                    $status_list = ['Agendada', 'Em Execução', 'Concluída', 'Cancelada'];
                    foreach ($status_list as $s):
                        $sel = ($s == $status_filtro) ? 'selected' : '';
                        echo "<option value='$s' $sel>$s</option>";
                    endforeach;
                    ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label fw-semibold">
                    <i class="bi bi-calendar"></i> Data Inicial
                </label>
                <input type="date" name="data_ini" class="form-control" value="<?= htmlspecialchars($data_ini) ?>">
            </div>
            
            <div class="col-md-2">
                <label class="form-label fw-semibold">
                    <i class="bi bi-calendar-check"></i> Data Final
                </label>
                <input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars($data_fim) ?>">
            </div>
            
            <div class="col-md-2">
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                    <a href="os.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Limpar
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Tabela -->
<div class="card shadow-sm" style="border-radius: 12px;">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr class="text-center">
                    <th width="80">ID</th>
                    <th>Cliente</th>
                    <th>Serviço</th>
                    <th width="120">Data</th>
                    <th width="120">Status</th>
                    <th width="120">Valor</th>
                    <th width="220">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado->num_rows === 0): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                            <p class="mt-3 mb-0">
                                <?php if (!empty($search_term)): ?>
                                    Nenhum resultado encontrado para "<?= htmlspecialchars($search_term) ?>"
                                <?php else: ?>
                                    Nenhuma OS cadastrada
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php while ($os = $resultado->fetch_assoc()): ?>
                        <tr class="text-center">
                            <td class="fw-bold">#<?= $os['id'] ?></td>
                            
                            <td class="text-start">
                                <div class="fw-semibold">
                                    <?= htmlspecialchars($os['cliente_nome_exibir'] ?: '—') ?>
                                </div>
                                <?php if (!empty($os['telefone'])): ?>
                                    <small class="text-muted">
                                        <i class="bi bi-telephone"></i> <?= htmlspecialchars($os['telefone']) ?>
                                    </small>
                                <?php endif; ?>
                                <?php if ($os['cliente_id'] > 0): ?>
                                    <small class="text-success d-block">
                                        <i class="bi bi-link-45deg"></i> Vinculado
                                    </small>
                                <?php else: ?>
                                    <small class="text-warning d-block">
                                        <i class="bi bi-exclamation-triangle"></i> Sem vínculo
                                    </small>
                                <?php endif; ?>
                            </td>
                            
                            <td class="text-start">
                                <small><?= htmlspecialchars(substr($os['servico'] ?: '—', 0, 50)) ?></small>
                                <?php if (strlen($os['servico']) > 50): ?>...<?php endif; ?>
                            </td>
                            
                            <td>
                                <?php if (!empty($os['data_agendada'])): ?>
                                    <?= date('d/m/Y', strtotime($os['data_agendada'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <?php
                                $status_colors = [
                                    'Agendada' => 'info',
                                    'Em Execução' => 'warning text-dark',
                                    'Concluída' => 'success',
                                    'Cancelada' => 'danger'
                                ];
                                $color = $status_colors[$os['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $color ?>">
                                    <?= htmlspecialchars($os['status']) ?>
                                </span>
                            </td>
                            
                            <td class="fw-bold">
                                R$ <?= number_format($os['valor_total'] ?? 0, 2, ',', '.') ?>
                            </td>
                            
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="os_view.php?id=<?= $os['id'] ?>" class="btn btn-outline-info" title="Visualizar">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="os_edit.php?id=<?= $os['id'] ?>" class="btn btn-outline-warning" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="os_pdf.php?id=<?= $os['id'] ?>" class="btn btn-outline-success" title="PDF" target="_blank">
                                        <i class="bi bi-file-pdf"></i>
                                    </a>
                                    <button 
                                        onclick="confirmarExclusao(<?= $os['id'] ?>)" 
                                        class="btn btn-outline-danger" 
                                        title="Excluir"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Paginação -->
<?php if ($total_paginas > 1): ?>
<nav aria-label="Navegação" class="mt-4">
    <ul class="pagination justify-content-center">
        <li class="page-item <?= ($pagina_atual <= 1) ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= $query_string ?>&pagina=<?= $pagina_atual - 1 ?>">
                <i class="bi bi-chevron-left"></i> Anterior
            </a>
        </li>

        <?php
        $start_page = max(1, $pagina_atual - 2);
        $end_page = min($total_paginas, $pagina_atual + 2);
        if ($end_page - $start_page < 4) $start_page = max(1, $end_page - 4);
        if ($end_page - $start_page < 4) $end_page = min($total_paginas, $start_page + 4);
        ?>

        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
            <li class="page-item <?= ($i == $pagina_atual) ? 'active' : '' ?>">
                <a class="page-link" href="?<?= $query_string ?>&pagina=<?= $i ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>

        <li class="page-item <?= ($pagina_atual >= $total_paginas) ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= $query_string ?>&pagina=<?= $pagina_atual + 1 ?>">
                Próxima <i class="bi bi-chevron-right"></i>
            </a>
        </li>
    </ul>
    
    <div class="text-center text-muted">
        <small>
            <i class="bi bi-info-circle"></i>
            Página <strong><?= $pagina_atual ?></strong> de <strong><?= $total_paginas ?></strong> 
            • Total de <strong><?= number_format($total_registros) ?></strong> OS
        </small>
    </div>
</nav>
<?php endif; ?>

</div>

<script>
function confirmarExclusao(id) {
    if (confirm("⚠️ Tem certeza que deseja excluir esta Ordem de Serviço?\n\nEsta ação não pode ser desfeita!")) {
        window.location = "os_delete.php?id=" + id;
    }
}

// Auto-dismiss alertas
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>

<style>
.table td, .table th { 
    vertical-align: middle; 
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
}

@media (max-width: 768px) {
    .btn-group {
        flex-direction: column;
    }
    
    .btn-group .btn {
        border-radius: 0.25rem !important;
        margin-bottom: 2px;
    }
}
</style>

<?php include "layout_footer.php"; ?>