<?php
require "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['msg_erro'] = "⚠️ ID da OS inválido.";
    header("Location: os.php");
    exit;
}

// Busca OS com JOIN no cliente
$stmt = $conn->prepare("
    SELECT 
        o.*,
        COALESCE(c.nome, o.cliente_nome) AS cliente_nome_exibir,
        c.telefone AS telefone_cad,
        c.email AS email_cad,
        c.cpf_cnpj AS cpf_cnpj_cad
    FROM os o
    LEFT JOIN clientes c ON c.id = o.cliente_id
    WHERE o.id = ?
    LIMIT 1
");

$stmt->bind_param("i", $id);
$stmt->execute();
$os = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$os) {
    $_SESSION['msg_erro'] = "⚠️ OS #$id não encontrada.";
    header("Location: os.php");
    exit;
}

// Busca dados relacionados
$equip = $conn->query("SELECT * FROM os_equipamentos WHERE os_id=$id")->fetch_all(MYSQLI_ASSOC);
$fotos = $conn->query("SELECT * FROM os_fotos WHERE os_id=$id")->fetch_all(MYSQLI_ASSOC);
$itens = $conn->query("SELECT * FROM os_itens WHERE os_id=$id")->fetch_all(MYSQLI_ASSOC);

function h($s) { 
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); 
}

include "layout_header.php";
?>

<div class="container mt-4 mb-5">

<!-- Mensagens -->
<?php if (isset($_SESSION['msg_sucesso'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= h($_SESSION['msg_sucesso']); unset($_SESSION['msg_sucesso']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Header -->
<div class="card shadow-sm mb-3" style="border-left: 4px solid #0d6efd;">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h4 class="mb-1">📋 OS #<?= h($os['id']) ?></h4>
                <small class="text-muted">
                    Criada em: <?= date('d/m/Y H:i', strtotime($os['created_at'] ?? $os['data_agendada'])) ?>
                    <?php if ($os['cliente_id'] > 0): ?>
                        • <a href="cliente_view.php?id=<?= $os['cliente_id'] ?>" class="text-decoration-none">
                            🔗 Ver cadastro do cliente
                        </a>
                    <?php endif; ?>
                </small>
            </div>
            <div class="text-end">
                <?php
                $status_colors = [
                    'Agendada' => 'info',
                    'Em Execução' => 'warning text-dark',
                    'Concluída' => 'success',
                    'Cancelada' => 'danger'
                ];
                $color = $status_colors[$os['status']] ?? 'secondary';
                ?>
                <span class="badge bg-<?= $color ?> fs-6"><?= h($os['status']) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Dados do Cliente -->
<div class="card shadow-sm mb-3">
    <div class="card-header bg-light">
        <h6 class="mb-0">👤 Dados do Cliente</h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="mb-2">
                    <small class="text-muted">Nome</small>
                    <div class="fw-semibold"><?= h($os['cliente_nome_exibir'] ?: $os['cliente_nome']) ?></div>
                </div>
                
                <div class="mb-2">
                    <small class="text-muted">Telefone</small>
                    <div><?= h($os['telefone_cad'] ?: $os['telefone'] ?: '—') ?></div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="mb-2">
                    <small class="text-muted">Cidade</small>
                    <div><?= h($os['cidade'] ?: '—') ?></div>
                </div>
                
                <div class="mb-2">
                    <small class="text-muted">Endereço</small>
                    <div><?= h($os['endereco'] ?: '—') ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dados da OS -->
<div class="card shadow-sm mb-3">
    <div class="card-header bg-light">
        <h6 class="mb-0">🔧 Dados do Serviço</h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-8">
                <small class="text-muted">Serviço</small>
                <div class="fw-semibold"><?= h($os['servico']) ?></div>
            </div>
            
            <div class="col-md-4">
                <small class="text-muted">Data Agendada</small>
                <div><?= date("d/m/Y", strtotime($os['data_agendada'])) ?></div>
            </div>
        </div>
        
        <?php if (!empty($os['observacoes'])): ?>
        <hr>
        <div>
            <small class="text-muted">Observações</small>
            <div class="mt-1 p-2 bg-light rounded"><?= nl2br(h($os['observacoes'])) ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Equipamentos -->
<?php if (!empty($equip)): ?>
<div class="card shadow-sm mb-3">
    <div class="card-header bg-light d-flex justify-content-between">
        <h6 class="mb-0">🧰 Equipamentos</h6>
        <small class="text-muted"><?= count($equip) ?> item(ns)</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Equipamento</th>
                        <th>Modelo</th>
                        <th>NS</th>
                        <th>Usuário</th>
                        <th>Senha</th>
                        <th>IP</th>
                        <th>Obs</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equip as $e): ?>
                    <tr>
                        <td><?= h($e['equipamento']) ?></td>
                        <td><?= h($e['modelo']) ?></td>
                        <td><?= h($e['serie']) ?></td>
                        <td><?= h($e['usuario']) ?></td>
                        <td><code><?= h($e['senha']) ?></code></td>
                        <td><code><?= h($e['ip']) ?></code></td>
                        <td><?= h($e['extra']) ?></td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Itens/Peças -->
<?php if (!empty($itens)): ?>
<div class="card shadow-sm mb-3">
    <div class="card-header bg-light d-flex justify-content-between">
        <h6 class="mb-0">📦 Itens e Peças</h6>
        <small class="text-muted"><?= count($itens) ?> item(ns)</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Produto</th>
                        <th width="80" class="text-center">Qtd</th>
                        <th width="130" class="text-end">Valor Un.</th>
                        <th width="130" class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_itens = 0;
                    foreach ($itens as $i): 
                        $subtotal = $i['subtotal'] ?? ($i['quantidade'] * $i['valor_unit']);
                        $total_itens += $subtotal;
                    ?>
                    <tr>
                        <td><?= h($i['produto']) ?></td>
                        <td class="text-center"><?= h($i['quantidade']) ?></td>
                        <td class="text-end">R$ <?= number_format($i['valor_unit'], 2, ',', '.') ?></td>
                        <td class="text-end fw-bold">R$ <?= number_format($subtotal, 2, ',', '.') ?></td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
                <tfoot class="table-secondary">
                    <tr>
                        <th colspan="3" class="text-end">Total</th>
                        <th class="text-end">R$ <?= number_format($total_itens, 2, ',', '.') ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Totais Financeiros -->
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 ms-auto">
                <table class="table table-sm mb-0">
                    <tr>
                        <td class="text-end">Deslocamento:</td>
                        <td class="text-end">R$ <?= number_format($os['custo_desloc'] ?? 0, 2, ',', '.') ?></td>
                    </tr>
                    <tr class="table-success">
                        <td class="text-end"><b>VALOR TOTAL:</b></td>
                        <td class="text-end"><h5 class="mb-0 text-success">R$ <?= number_format($os['valor_total'] ?? 0, 2, ',', '.') ?></h5></td>
                    </tr>
                    <tr>
                        <td class="text-end">Valor Pago:</td>
                        <td class="text-end">R$ <?= number_format($os['valor_pago'] ?? 0, 2, ',', '.') ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Fotos -->
<?php if (!empty($fotos)): ?>
<div class="card shadow-sm mb-3">
    <div class="card-header bg-light">
        <h6 class="mb-0">📸 Fotos</h6>
    </div>
    <div class="card-body">
        <div class="row g-2">
            <?php foreach ($fotos as $f): ?>
                <div class="col-6 col-md-3">
                    <img 
                        src="<?= h($f['file_path']) ?>" 
                        class="img-fluid rounded shadow-sm" 
                        style="cursor:pointer; object-fit:cover; height:150px; width:100%;"
                        onclick="window.open('<?= h($f['file_path']) ?>','_blank')"
                    >
                </div>
            <?php endforeach ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Assinatura -->
<?php if (!empty($os['assinatura'])): ?>
<div class="card shadow-sm mb-3">
    <div class="card-header bg-light">
        <h6 class="mb-0">✍️ Assinatura do Cliente</h6>
    </div>
    <div class="card-body text-center">
        <img 
            src="<?= h($os['assinatura']) ?>" 
            style="max-width:400px; border:1px solid #ddd; border-radius:8px;"
            class="img-fluid"
        >
    </div>
</div>
<?php endif; ?>

<!-- Ações -->
<div class="d-flex gap-2 justify-content-between flex-wrap">
    <div>
        <a href="os.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>
    <div class="d-flex gap-2">
        <a href="os_edit.php?id=<?= $id ?>" class="btn btn-warning">
            <i class="bi bi-pencil"></i> Editar
        </a>
        <a href="os_pdf.php?id=<?= $id ?>" class="btn btn-success" target="_blank">
            <i class="bi bi-file-pdf"></i> Gerar PDF
        </a>
    </div>
</div>

</div>

<?php include "layout_footer.php"; ?>