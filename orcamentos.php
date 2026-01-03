<?php
require "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit; 
}

include "layout_header.php";

// ==========================================================
// 1. CONFIGURAÇÃO DE PAGINAÇÃO E FILTROS
// ==========================================================

// Configuração
$limite_por_pagina = 10;
$pagina_atual = intval($_GET['pagina'] ?? 1);
$offset = ($pagina_atual - 1) * $limite_por_pagina;

// === Parâmetros de filtro ===
$cliente_filtro = $_GET['cliente'] ?? '';
$status_filtro = $_GET['status'] ?? '';
$data_ini = $_GET['data_ini'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';

$where = [];
$params = [];
$types = '';

// Filtro por cliente (busca no nome do cliente OU na tabela clientes)
if ($cliente_filtro !== '') {
    $where[] = "(c.nome LIKE ? OR o.cliente_nome LIKE ?)";
    $params[] = "%$cliente_filtro%";
    $params[] = "%$cliente_filtro%";
    $types .= 'ss';
}

// Filtro por status
if ($status_filtro !== '') {
    $where[] = "o.status = ?";
    $params[] = $status_filtro;
    $types .= 's';
}

// Conversão e filtro de datas
$data_ini_db = !empty($data_ini) ? date('Y-m-d', strtotime($data_ini)) : '';
$data_fim_db = !empty($data_fim) ? date('Y-m-d', strtotime($data_fim)) : '';

if ($data_ini_db !== '' && $data_fim_db !== '') {
    $where[] = "DATE(o.data_criacao) BETWEEN ? AND ?";
    $params[] = $data_ini_db;
    $params[] = $data_fim_db;
    $types .= 'ss';
} elseif ($data_ini_db !== '') {
    $where[] = "DATE(o.data_criacao) >= ?";
    $params[] = $data_ini_db;
    $types .= 's';
} elseif ($data_fim_db !== '') {
    $where[] = "DATE(o.data_criacao) <= ?";
    $params[] = $data_fim_db;
    $types .= 's';
}

$whereSQL = count($where) ? "WHERE " . implode(" AND ", $where) : "";

// ==========================================================
// 2. CÁLCULO DE PAGINAÇÃO (TOTAL DE REGISTROS)
// ==========================================================
$sql_total = "
    SELECT COUNT(o.id) AS total 
    FROM orcamentos o 
    LEFT JOIN clientes c ON c.id = o.cliente_id 
    $whereSQL
";

if ($types) {
    $stmt_total = $conn->prepare($sql_total);
    $stmt_total->bind_param($types, ...$params);
    $stmt_total->execute();
    $res_total = $stmt_total->get_result();
    $total_registros = $res_total->fetch_assoc()['total'];
} else {
    $res_total = $conn->query($sql_total);
    $total_registros = $res_total->fetch_assoc()['total'];
}

$total_paginas = ceil($total_registros / $limite_por_pagina);

// ==========================================================
// 3. CONSULTA SQL COM PAGINAÇÃO
// ==========================================================
$sql = "
    SELECT 
        o.*,
        COALESCE(c.nome, o.cliente_nome) AS cliente_nome_exibir,
        c.telefone AS cliente_telefone_cad,
        c.cidade AS cliente_cidade_cad
    FROM orcamentos o 
    LEFT JOIN clientes c ON c.id = o.cliente_id 
    $whereSQL
    ORDER BY o.id DESC
    LIMIT ? OFFSET ?
";

// Adiciona limit e offset aos parâmetros
$params_lista = $params;
$params_lista[] = $limite_por_pagina;
$params_lista[] = $offset;
$types_lista = $types . 'ii';

$stmt = $conn->prepare($sql);
if ($types_lista) {
    $stmt->bind_param($types_lista, ...$params_lista);
}
$stmt->execute();
$orcamentos = $stmt->get_result();

// Constrói a string de query para links de paginação
$query_string = http_build_query([
    'cliente' => $cliente_filtro,
    'status' => $status_filtro,
    'data_ini' => $data_ini,
    'data_fim' => $data_fim
]);

?>

<div class="container-fluid">

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mt-4 mb-3 flex-wrap gap-2">
    <div>
        <h3 class="mb-1">🧾 Orçamentos</h3>
        <small class="text-muted">Gerencie todos os orçamentos do sistema</small>
    </div>
    <div class="d-flex gap-2">
        <a href="diagnostico_orcamentos.php" class="btn btn-outline-info btn-sm">
            <i class="bi bi-clipboard-data"></i> Diagnóstico
        </a>
        <a href="orcamento_add.php" class="btn btn-success btn-sm">
            <i class="bi bi-plus-circle"></i> Novo Orçamento
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

<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filtros -->
<form method="get" class="card shadow-sm mb-4" style="border-radius: 12px;">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold">
                    <i class="bi bi-person"></i> Cliente
                </label>
                <input 
                    type="text" 
                    name="cliente" 
                    class="form-control" 
                    placeholder="Nome do cliente..." 
                    value="<?= htmlspecialchars($cliente_filtro) ?>"
                >
            </div>
            
            <div class="col-md-3">
                <label class="form-label fw-semibold">
                    <i class="bi bi-tag"></i> Status
                </label>
                <select name="status" class="form-select">
                    <option value="">📋 Todos</option>
                    <?php
                    $statusList = [
                        'Pendente' => '⏳',
                        'Aprovado' => '✅',
                        'Em execução' => '🔄',
                        'Concluído' => '✔️',
                        'Cancelado' => '❌'
                    ];
                    foreach ($statusList as $s => $icon):
                        $sel = ($s == $status_filtro) ? 'selected' : '';
                        echo "<option value='$s' $sel>$icon $s</option>";
                    endforeach;
                    ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label fw-semibold">
                    <i class="bi bi-calendar"></i> Data Inicial
                </label>
                <input 
                    type="date" 
                    name="data_ini" 
                    class="form-control" 
                    value="<?= htmlspecialchars($data_ini) ?>"
                >
            </div>
            
            <div class="col-md-2">
                <label class="form-label fw-semibold">
                    <i class="bi bi-calendar-check"></i> Data Final
                </label>
                <input 
                    type="date" 
                    name="data_fim" 
                    class="form-control" 
                    value="<?= htmlspecialchars($data_fim) ?>"
                >
            </div>
            
            <div class="col-md-2">
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                    <a href="orcamentos.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Limpar
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Busca Rápida -->
<div class="mb-3">
    <div class="input-group shadow-sm" style="max-width: 450px;">
        <span class="input-group-text bg-white">
            <i class="bi bi-search"></i>
        </span>
        <input 
            type="text" 
            id="buscaOrcamento" 
            class="form-control border-start-0" 
            placeholder="Buscar na página atual (<?= $orcamentos->num_rows ?> registros)..."
            style="border-radius: 0 10px 10px 0;"
        >
    </div>
</div>

<!-- Tabela de Orçamentos -->
<div class="card shadow-sm" style="border-radius: 12px;">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tabelaOrcamentos">
            <thead class="table-dark">
                <tr class="text-center">
                    <th width="80">#</th>
                    <th>Cliente</th>
                    <th>Descrição</th>
                    <th width="120">Garantia</th>
                    <th width="120">Status</th>
                    <th width="120">Valor</th>
                    <th width="120">Data</th>
                    <th width="200">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($orcamentos && $orcamentos->num_rows > 0): ?>
                    <?php while ($o = $orcamentos->fetch_assoc()): ?>
                        <tr class="linha-orcamento text-center">
                            <td class="fw-bold">#<?= $o['id'] ?></td>
                            
                            <td class="text-start">
                                <div class="fw-semibold">
                                    <?= htmlspecialchars($o['cliente_nome_exibir'] ?: 'Sem cliente') ?>
                                </div>
                                <?php if (!empty($o['telefone'])): ?>
                                    <small class="text-muted">
                                        <i class="bi bi-telephone"></i> <?= htmlspecialchars($o['telefone']) ?>
                                    </small>
                                <?php endif; ?>
                                <?php if ($o['cliente_id'] > 0): ?>
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
                                <small><?= htmlspecialchars(substr($o['descricao'] ?: '-', 0, 60)) ?></small>
                                <?php if (strlen($o['descricao']) > 60): ?>...<?php endif; ?>
                            </td>
                            
                            <td>
                                <small><?= htmlspecialchars($o['garantia_tipo'] ?: '-') ?></small>
                            </td>
                            
                            <td>
                                <?php
                                $status_colors = [
                                    'Pendente' => 'warning text-dark',
                                    'Aprovado' => 'success',
                                    'Em execução' => 'info',
                                    'Concluído' => 'primary',
                                    'Cancelado' => 'secondary'
                                ];
                                $color = $status_colors[$o['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $color ?>">
                                    <?= htmlspecialchars($o['status']) ?>
                                </span>
                            </td>
                            
                            <td class="fw-bold">
                                R$ <?= number_format($o['valor_total'], 2, ',', '.') ?>
                            </td>
                            
                            <td>
                                <small><?= date('d/m/Y', strtotime($o['data_criacao'])) ?></small>
                                <br>
                                <small class="text-muted"><?= date('H:i', strtotime($o['data_criacao'])) ?></small>
                            </td>
                            
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a 
                                        href="orcamento_view.php?id=<?= $o['id'] ?>" 
                                        class="btn btn-outline-info" 
                                        title="Visualizar"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a 
                                        href="orcamento_edit.php?id=<?= $o['id'] ?>" 
                                        class="btn btn-outline-warning" 
                                        title="Editar"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a 
                                        href="orcamento_pdf.php?id=<?= $o['id'] ?>" 
                                        class="btn btn-outline-success" 
                                        title="Gerar PDF"
                                        target="_blank"
                                    >
                                        <i class="bi bi-file-pdf"></i>
                                    </a>
                                    <a 
                                        href="orcamento_delete.php?id=<?= $o['id'] ?>" 
                                        class="btn btn-outline-danger" 
                                        title="Excluir"
                                        onclick="return confirm('⚠️ Tem certeza que deseja excluir o orçamento #<?= $o['id'] ?>?\n\nEsta ação não pode ser desfeita!')"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr id="semResultados">
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                            <p class="mt-3 mb-0">Nenhum orçamento encontrado 😕</p>
                            <small>Tente ajustar os filtros ou criar um novo orçamento</small>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Paginação -->
<?php if ($total_paginas > 1): ?>
<nav aria-label="Navegação de página" class="mt-4">
    <ul class="pagination justify-content-center">
        <li class="page-item <?= ($pagina_atual <= 1) ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= $query_string ?>&pagina=<?= $pagina_atual - 1 ?>">
                <i class="bi bi-chevron-left"></i> Anterior
            </a>
        </li>

        <?php
        $start_page = max(1, $pagina_atual - 2);
        $end_page = min($total_paginas, $pagina_atual + 2);

        if ($end_page - $start_page < 4) {
            $start_page = max(1, $end_page - 4);
        }
        if ($end_page - $start_page < 4) {
            $end_page = min($total_paginas, $start_page + 4);
        }
        ?>

        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
            <li class="page-item <?= ($i == $pagina_atual) ? 'active' : '' ?>">
                <a class="page-link" href="?<?= $query_string ?>&pagina=<?= $i ?>">
                    <?= $i ?>
                </a>
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
            • Total de <strong><?= number_format($total_registros) ?></strong> registro(s)
        </small>
    </div>
</nav>
<?php endif; ?>

</div>

<style>
.table td, .table th { 
    vertical-align: middle; 
}

.card { 
    border: none;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
}

#buscaOrcamento {
    transition: all 0.3s;
}

#buscaOrcamento:focus {
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    border-color: #86b7fe;
}

.linha-orcamento {
    transition: background-color 0.2s;
}

.linha-orcamento:hover {
    background-color: #f8f9fa !important;
}

.badge {
    padding: 0.4em 0.65em;
    font-weight: 500;
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

<script>
// Busca instantânea na página atual
document.getElementById('buscaOrcamento').addEventListener('input', function() {
    const termo = this.value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    const linhas = document.querySelectorAll('#tabelaOrcamentos .linha-orcamento');
    let encontrou = false;

    linhas.forEach(linha => {
        const texto = linha.textContent.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        if (termo === '' || texto.includes(termo)) {
            linha.style.display = '';
            encontrou = true;
        } else {
            linha.style.display = 'none';
        }
    });

    // Gerencia mensagem de "nenhum resultado"
    const tabelaBody = document.querySelector('#tabelaOrcamentos tbody');
    let msgExistente = document.getElementById('semOrcamentos');
    
    if (!encontrou && termo !== '') {
        // Remove mensagem antiga se existir
        if (msgExistente) msgExistente.remove();
        
        // Cria nova mensagem
        const tr = document.createElement('tr');
        tr.id = 'semOrcamentos';
        tr.innerHTML = `
            <td colspan="8" class="text-center text-muted py-4">
                <i class="bi bi-search" style="font-size: 2rem;"></i>
                <p class="mt-2 mb-0">Nenhum resultado encontrado para "<strong>${this.value}</strong>"</p>
                <small>Tente buscar por outro termo</small>
            </td>
        `;
        tabelaBody.appendChild(tr);
    } else if (encontrou || termo === '') {
        // Remove mensagem se encontrou resultados ou limpou busca
        if (msgExistente) msgExistente.remove();
    }
});

// Auto-dismiss de alertas após 5 segundos
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>

<?php include "layout_footer.php"; ?>

// ADICIONE ESTE SCRIPT NO FINAL DO orcamentos.php
// Logo antes do <?php include "layout_footer.php"; ?>

<script>
// OPÇÃO 1: Delete com confirmação simples (padrão - já implementado)
// Não precisa fazer nada, já funciona

// OPÇÃO 2: Delete com AJAX e SweetAlert2 (mais bonito)
// Descomente o código abaixo para usar esta opção

/*
// Primeiro, adicione SweetAlert2 no head do layout_header.php:
// <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

// Depois adicione esta função:
function deletarOrcamento(id, clienteNome) {
    Swal.fire({
        title: '⚠️ Confirmar Exclusão',
        html: `Tem certeza que deseja excluir o orçamento <strong>#${id}</strong>?<br><small>Cliente: ${clienteNome}</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '🗑️ Sim, excluir',
        cancelButtonText: 'Cancelar',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return fetch('orcamento_delete_ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + id
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na requisição');
                }
                return response.json();
            })
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message);
                }
                return data;
            })
            .catch(error => {
                Swal.showValidationMessage(`Erro: ${error}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: '✅ Excluído!',
                text: result.value.message,
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                // Remove a linha da tabela
                const linha = document.querySelector(`tr[data-orc-id="${id}"]`);
                if (linha) {
                    linha.style.transition = 'opacity 0.3s';
                    linha.style.opacity = '0';
                    setTimeout(() => linha.remove(), 300);
                } else {
                    // Se não encontrou, recarrega a página
                    location.reload();
                }
            });
        }
    });
}

// E no HTML da tabela, substitua o link de delete por:
// <button 
//     onclick="deletarOrcamento(<?= $o['id'] ?>, '<?= htmlspecialchars($o['cliente_nome_exibir'], ENT_QUOTES) ?>')" 
//     class="btn btn-outline-danger" 
//     title="Excluir"
// >
//     <i class="bi bi-trash"></i>
// </button>

// E adicione data-orc-id na <tr>:
// <tr class="linha-orcamento text-center" data-orc-id="<?= $o['id'] ?>">
*/
</script>