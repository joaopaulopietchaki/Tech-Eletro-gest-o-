<?php
// ==========================================================
// os_edit.php — Arquivo completo consolidado e corrigido
// Mantive TODAS as suas funções e lógica, apenas corrigi
// prepared statements, bind_param dinâmico e pequenos bugs.
// ==========================================================

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ==========================================================
// FUNÇÕES DE UTILIDADE (BR para FLOAT, FLOAT para BR, e SALVAR IMAGENS)
// ==========================================================
if (!function_exists('br_to_float')) {
    function br_to_float($val) {
        if ($val === null || $val === '') return 0.00;
        // Substitui ponto por nada e vírgula por ponto (para float)
        $val = str_replace(['.', ','], ['', '.'], (string)$val);
        return floatval($val);
    }
}

if (!function_exists('float_to_br')) {
    function float_to_br($v) {
        // Garante que o número seja formatado corretamente no padrão brasileiro
        return number_format((float)$v, 2, ',', '.');
    }
}

/**
 * Salva uma string Base64 (imagem) como arquivo PNG (Assinatura).
 * Retorna o caminho com a barra inicial (/) para acesso HTTP.
 */
if (!function_exists('save_base64_image')) {
    function save_base64_image($base64_string, $output_folder = 'signatures', $os_id = 0) {
        if (empty($base64_string)) return null;

        // Remove o prefixo 'data:image/...;base64,'
        $base64_string = preg_replace('/^data:image\/\w+;base64,/', '', $base64_string);
        $data = base64_decode($base64_string);
        if ($data === false) return null;

        // Nome do arquivo para o sistema de arquivos
        $timestamp = time(); // Usa timestamp para nome único
        $filename_fs = rtrim($output_folder, '/') . '/' . $os_id . '_' . $timestamp . '.png';

        // Garanta que a pasta de saída exista
        if (!is_dir($output_folder)) {
            if (!mkdir($output_folder, 0777, true) && !is_dir($output_folder)) {
                return null;
            }
        }

        // Salve o arquivo no sistema de arquivos
        if (file_put_contents($filename_fs, $data) !== false) {
            // Retorna o caminho COM a barra inicial (/) para acesso HTTP no navegador
            return '/' . trim($output_folder, '/') . '/' . $os_id . '_' . $timestamp . '.png';
        }

        return null;
    }
}

// ==========================================================
// LÓGICA PRINCIPAL (POST/GET)
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // valida id no POST request (vem via query string)
    $os_id = intval($_GET['id'] ?? 0);
    if ($os_id <= 0) {
        $_SESSION['error'] = 'ID da OS não informado.';
        header('Location: os.php');
        exit;
    }

    // Campos principais
    $cliente_id      = intval($_POST['cliente_id'] ?? 0);
    $cliente_nome    = trim($_POST['cliente_nome'] ?? '');
    $endereco        = trim($_POST['endereco'] ?? '');
    $cidade          = trim($_POST['cidade'] ?? '');
    $telefone        = trim($_POST['telefone'] ?? '');
    $servico         = trim($_POST['servico'] ?? '');
    $observacoes     = trim($_POST['observacoes'] ?? '');
    $data_agendada   = trim($_POST['data_agendada'] ?? date('Y-m-d'));
    $status          = trim($_POST['status'] ?? 'Pendente');

    // Pagamento / orcamento
    $status_pagamento = trim($_POST['status_pagamento'] ?? 'PENDENTE');
    $data_pagamento   = trim($_POST['data_pagamento'] ?? null);
    $orcamento_id     = intval($_POST['orcamento_id'] ?? 0);

    // Valores (formatados em BR pelo JS)
    $custo_desloc    = br_to_float($_POST['custo_desloc'] ?? 0);
    $valor_total     = br_to_float($_POST['valor_total_final'] ?? 0);
    $valor_pago      = br_to_float($_POST['valor_pago'] ?? 0);

    // Assinatura
    $assinatura_base64 = $_POST['assinatura_data'] ?? null;
    $assinatura_file_path = $_POST['assinatura_existente'] ?? null;

    if (!empty($assinatura_base64)) {
        $saved = save_base64_image($assinatura_base64, 'signatures', $os_id);
        if ($saved !== null) $assinatura_file_path = $saved;
    }

    // Início transação
    $conn->query("SET FOREIGN_KEY_CHECKS=0");
    $conn->begin_transaction();

    try {
        // 1) UPDATE tabela os
        $sql_update_os = "UPDATE os SET 
            cliente_id = ?, cliente_nome = ?, endereco = ?, cidade = ?, telefone = ?, 
            servico = ?, observacoes = ?, data_agendada = ?, status = ?, 
            status_pagamento = ?, data_pagamento = ?, orcamento_id = ?, 
            custo_desloc = ?, valor_total = ?, valor_pago = ?, 
            assinatura = ?
            WHERE id = ?";

        // Monta parâmetros na mesma ordem dos placeholders
        $params = [
            $cliente_id,
            $cliente_nome,
            $endereco,
            $cidade,
            $telefone,
            $servico,
            $observacoes,
            $data_agendada,
            $status,
            $status_pagamento,
            $data_pagamento,
            $orcamento_id,
            $custo_desloc,
            $valor_total,
            $valor_pago,
            $assinatura_file_path,
            $os_id
        ];

        // Monta dinamicamente a string de tipos:
        // cliente_id -> i
        // cliente_nome..data_pagamento -> 10 strings (s)
        // orcamento_id -> i
        // custo_desloc, valor_total, valor_pago -> ddd
        // assinatura -> s
        // os_id -> i
        $types = 'i' . str_repeat('s', 10) . 'i' . str_repeat('d', 3) . 's' . 'i';

        $stmt_os = $conn->prepare($sql_update_os);
        if (!$stmt_os) throw new mysqli_sql_exception('Erro ao preparar UPDATE os: ' . $conn->error);

        // bind_param exige referências
        $bind_args = array_merge([$types], $params);
        $refs = [];
        foreach ($bind_args as $k => $v) $refs[$k] = &$bind_args[$k];

        if (!call_user_func_array([$stmt_os, 'bind_param'], $refs)) {
            throw new mysqli_sql_exception('Erro no bind_param UPDATE os: ' . $stmt_os->error);
        }

        if (!$stmt_os->execute()) {
            throw new mysqli_sql_exception('Erro ao executar UPDATE os: ' . $stmt_os->error);
        }
        $stmt_os->close();

        // 2) Deleta itens e equipamentos antigos
        $del_items = $conn->prepare("DELETE FROM os_itens WHERE os_id = ?");
        $del_items->bind_param('i', $os_id);
        $del_items->execute();
        $del_items->close();

        $del_equip = $conn->prepare("DELETE FROM os_equipamentos WHERE os_id = ?");
        $del_equip->bind_param('i', $os_id);
        $del_equip->execute();
        $del_equip->close();

        // 3) Inserir itens (se houver)
        if (isset($_POST['item_produto']) && is_array($_POST['item_produto'])) {
            $sql_item = "INSERT INTO os_itens (os_id, produto, quantidade, valor_unit, subtotal) VALUES (?, ?, ?, ?, ?)";
            $stmt_item = $conn->prepare($sql_item);
            if (!$stmt_item) throw new mysqli_sql_exception('Erro preparar INSERT os_itens: ' . $conn->error);

            foreach ($_POST['item_produto'] as $k => $produto) {
                if (!empty(trim($produto))) {
                    $qtd = intval($_POST['item_quantidade'][$k] ?? 1);
                    $vu  = br_to_float($_POST['item_valor_unit'][$k] ?? 0);
                    $sub = br_to_float($_POST['item_subtotal'][$k] ?? 0);

                    $stmt_item->bind_param('isidd', $os_id, $produto, $qtd, $vu, $sub);
                    $stmt_item->execute();
                }
            }
            $stmt_item->close();
        }

        // 4) Inserir equipamentos (se houver)
        if (isset($_POST['equip_nome']) && is_array($_POST['equip_nome'])) {
            $sql_equip = "INSERT INTO os_equipamentos (os_id, equipamento, modelo, serie, usuario, senha, ip, extra) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_equip = $conn->prepare($sql_equip);
            if (!$stmt_equip) throw new mysqli_sql_exception('Erro preparar INSERT os_equipamentos: ' . $conn->error);

            foreach ($_POST['equip_nome'] as $k => $equipamento) {
                if (!empty(trim($equipamento))) {
                    $modelo  = $_POST['equip_modelo'][$k] ?? '';
                    $serie   = $_POST['equip_serie'][$k] ?? '';
                    $usuario = $_POST['equip_usuario'][$k] ?? '';
                    $senha   = $_POST['equip_senha'][$k] ?? '';
                    $ip      = $_POST['equip_ip'][$k] ?? '';
                    $extra   = $_POST['equip_extra'][$k] ?? '';

                    $stmt_equip->bind_param('isssssss', $os_id, $equipamento, $modelo, $serie, $usuario, $senha, $ip, $extra);
                    $stmt_equip->execute();
                }
            }
            $stmt_equip->close();
        }

        // 5) Upload de fotos
        if (isset($_FILES['fotos']) && !empty($_FILES['fotos']['name'][0])) {
            $upload_dir_fs = 'uploads/os';
            if (!is_dir($upload_dir_fs)) mkdir($upload_dir_fs, 0777, true);

            $sql_foto = "INSERT INTO os_fotos (os_id, file_path, tipo) VALUES (?, ?, ?)";
            $stmt_foto = $conn->prepare($sql_foto);
            if (!$stmt_foto) throw new mysqli_sql_exception('Erro preparar INSERT os_fotos: ' . $conn->error);

            $tipo_foto = 'depois';
            $ts = time();

            foreach ($_FILES['fotos']['name'] as $k => $name) {
                if ($_FILES['fotos']['error'][$k] === UPLOAD_ERR_OK) {
                    $tmp = $_FILES['fotos']['tmp_name'][$k];
                    $ext = pathinfo($name, PATHINFO_EXTENSION);
                    $basename = 'os_' . $os_id . '_' . ($ts + $k) . '.' . $ext;
                    $fs = $upload_dir_fs . '/' . $basename;
                    $dbpath = '/' . $upload_dir_fs . '/' . $basename;
                    if (move_uploaded_file($tmp, $fs)) {
                        $stmt_foto->bind_param('iss', $os_id, $dbpath, $tipo_foto);
                        $stmt_foto->execute();
                    }
                }
            }
            $stmt_foto->close();
        }

        // commit
        $conn->commit();
        $_SESSION['message'] = "OS #{$os_id} salva com sucesso!";
    } catch (Exception $e) {
        $conn->rollback();
        // mensagem amigável
        $msg = $e instanceof mysqli_sql_exception && strpos($e->getMessage(), 'bind_param') !== false
            ? "Erro de processamento interno ao salvar (verifique os tipos de dados ou colunas no código)."
            : "Erro ao salvar OS: " . $e->getMessage();
        $_SESSION['error'] = $msg;
    }

    $conn->query("SET FOREIGN_KEY_CHECKS=1");
    header("Location: os_edit.php?id=" . $os_id);
    exit;
} else {
    // GET: carregar dados para edição
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        $_SESSION['error'] = "ID de OS inválido.";
        header("Location: os.php");
        exit;
    }

    // busca principal com prepared
    $stmt_busca = $conn->prepare("SELECT * FROM os WHERE id = ?");
    $stmt_busca->bind_param("i", $id);
    $stmt_busca->execute();
    $res = $stmt_busca->get_result();

    if ($res->num_rows === 0) {
        $_SESSION['error'] = "Ordem de Serviço #{$id} não encontrada.";
        header("Location: os.php");
        exit;
    }
    $os = $res->fetch_assoc();
    $stmt_busca->close();

    // dependências
    $itens  = $conn->query("SELECT * FROM os_itens WHERE os_id = " . intval($id))->fetch_all(MYSQLI_ASSOC);
    $equips = $conn->query("SELECT * FROM os_equipamentos WHERE os_id = " . intval($id))->fetch_all(MYSQLI_ASSOC);
    $fotos  = $conn->query("SELECT * FROM os_fotos WHERE os_id = " . intval($id))->fetch_all(MYSQLI_ASSOC);
}

// ==========================================================
// layout header (mantive como include seu layout_header.php)
// ==========================================================
include "layout_header.php";
?>

<!-- =========================================================
     Inclusões JS/CSS (local paths as in your original)
     ========================================================= -->
<script src="/js/jquery.min.js"></script>
<script src="/js/jquery-ui.min.js"></script>
<script src="/js/signature_pad.min.js"></script>
<link rel="stylesheet" href="/css/jquery-ui.css">

<style>
    /* Estilos para melhor visualização em dispositivos móveis */
    .item-row > div { padding-left: 5px !important; padding-right: 5px !important; }
    .item-row input[type="text"], .item-row input[type="number"] {
        width: 100%; box-sizing: border-box; padding: .2rem .5rem; font-size: 0.875rem;
    }
    .item-row .col-5 { width: 40%; } 
    .item-row .col-1 { width: 15%; } 
    .item-row .col-3 { width: 25%; } 
    .item-row .col-2 { width: 15%; } 
    .item-row .col-1.text-center { width: 5%; }

    .lista-produtos {
        position: absolute; width: 100%; top: 100%; left: 0; z-index: 1050; border: 1px solid #ddd;
        background-color: #fff; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    #total-pecas-servicos, #valor-total-final { white-space: nowrap; }

    .equip-row .col-md-1, .equip-row .col-md-2, .equip-row .col-md-3 {
        padding-left: 5px !important; padding-right: 5px !important;
    }
</style>

<?php
// Variáveis para preenchimento do formulário
$cliente_nome = htmlspecialchars($os['cliente_nome'] ?? '');
$cliente_id = htmlspecialchars($os['cliente_id'] ?? 0);
$endereco = htmlspecialchars($os['endereco'] ?? '');
$cidade = htmlspecialchars($os['cidade'] ?? '');
$telefone = htmlspecialchars($os['telefone'] ?? '');
$servico = htmlspecialchars($os['servico'] ?? '');
$observacoes = htmlspecialchars($os['observacoes'] ?? '');
$data_agendada = htmlspecialchars($os['data_agendada'] ?? date('Y-m-d'));
$status = htmlspecialchars($os['status'] ?? 'Pendente');
$orcamento_id = htmlspecialchars($os['orcamento_id'] ?? 0);

$status_pagamento = htmlspecialchars($os['status_pagamento'] ?? 'PENDENTE');

$data_pagamento = ($os['data_pagamento'] ?? '') === '0000-00-00' || empty($os['data_pagamento'])
    ? ''
    : htmlspecialchars($os['data_pagamento']);

$custo_desloc = float_to_br($os['custo_desloc'] ?? 0);
$valor_total_os = float_to_br($os['valor_total'] ?? 0);
$valor_pago = float_to_br($os['valor_pago'] ?? 0);

$assinatura_existente = htmlspecialchars($os['assinatura'] ?? '');
$assinatura_existente = str_replace('//', '/', $assinatura_existente);

$mostrar_item_vazio = empty($itens);
$equip_template_class = empty($equips) ? '' : 'd-none';
?>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>✏️ Editar Ordem de Serviço #<?= $os['id'] ?></h4>
        <a href="os.php" class="btn btn-secondary">Voltar (Todas OS)</a>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
        <?php unset($_SESSION['message']); ?>
    <?php elseif (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <form action="os_edit.php?id=<?= $os['id'] ?>" method="POST" enctype="multipart/form-data" id="formOS">
        <input type="hidden" name="cliente_id" id="cliente_id" value="<?= $cliente_id ?>">
        <input type="hidden" name="assinatura_existente" value="<?= $assinatura_existente ?>">
        <input type="hidden" name="assinatura_data" id="assinatura_data">
        <input type="hidden" name="orcamento_id" id="orcamento_id" value="<?= $orcamento_id ?>">

        <div class="card mt-3 shadow-sm">
            <div class="card-header bg-light fw-bold">📋 Dados da OS</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Cliente (Busca)</label>
                        <input type="text" name="cliente_nome" id="cliente_nome" class="form-control" autocomplete="off" value="<?= $cliente_nome ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Endereço</label>
                        <input type="text" name="endereco" id="endereco" class="form-control" value="<?= $endereco ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cidade</label>
                        <input type="text" name="cidade" id="cidade" class="form-control" value="<?= $cidade ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="telefone" id="telefone" class="form-control" value="<?= $telefone ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Data Agendada</label>
                        <input type="date" name="data_agendada" class="form-control" value="<?= $data_agendada ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nº Orçamento (Busca)</label>
                        <input type="text" name="orcamento_numero" id="orcamento_numero" class="form-control" autocomplete="off" value="<?= htmlspecialchars($os['orcamento_id'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Visualizar Orçamento</label>
                        <?php 
                        $orcamento_link = !empty($os['orcamento_id']) ? "orcamento_edit.php?id=" . urlencode($os['orcamento_id']) : "#";
                        $orcamento_texto = !empty($os['orcamento_id']) ? "Ver Orçamento #{$os['orcamento_id']}" : "Nenhum vinculado";
                        ?>
                        <a href="<?= $orcamento_link ?>" id="link_orcamento" class="btn btn-sm btn-outline-info w-100 mt-1" target="_blank" <?= empty($os['orcamento_id']) ? 'disabled' : '' ?>>
                            <?= $orcamento_texto ?>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status (Execução)</label>
                        <select name="status" class="form-select">
                            <option value="Pendente" <?= ($status === 'Pendente') ? 'selected' : '' ?>>Pendente</option>
                            <option value="Em andamento" <?= ($status === 'Em andamento') ? 'selected' : '' ?>>Em andamento</option>
                            <option value="Concluída" <?= ($status === 'Concluída') ? 'selected' : '' ?>>Concluída</option>
                            <option value="Cancelada" <?= ($status === 'Cancelada') ? 'selected' : '' ?>>Cancelada</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Serviço Solicitado/Executado</label>
                        <input type="text" name="servico" class="form-control" value="<?= $servico ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Observações Técnicas</label>
                        <textarea name="observacoes" rows="3" class="form-control"><?= $observacoes ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Itens / Peças -->
        <div class="card mt-4 shadow-sm">
            <div class="card-header bg-light fw-bold d-flex justify-content-between align-items-center">
                📦 Itens e Peças
                <button type="button" class="btn btn-sm btn-primary" id="btn-adicionar-item">Adicionar Item</button>
            </div>
            <div class="card-body">
                <div class="row fw-bold text-center d-none d-sm-flex" style="font-size: 0.8rem;">
                    <div class="col-5">Produto/Serviço</div>
                    <div class="col-1">Qtd</div>
                    <div class="col-3">Valor Unit.</div>
                    <div class="col-2">Subtotal</div>
                    <div class="col-1"></div>
                </div>

                <div id="item-container">
                    <?php if (!$mostrar_item_vazio): ?>
                        <?php foreach ($itens as $item): ?>
                            <div class="row g-2 item-row mb-2 border-bottom pb-2">
                                <div class="col-5">
                                    <input type="text" name="item_produto[]" class="form-control item-produto" placeholder="Produto/Serviço" value="<?= htmlspecialchars($item['produto']) ?>">
                                    <div class="lista-produtos list-group"></div>
                                </div>
                                <div class="col-1"><input type="number" name="item_quantidade[]" class="form-control item-qtd" value="<?= $item['quantidade'] ?>" min="1"></div>
                                <div class="col-3"><input type="text" name="item_valor_unit[]" class="form-control item-vu" placeholder="Unit." value="<?= float_to_br($item['valor_unit']) ?>"></div>
                                <div class="col-2"><input type="text" name="item_subtotal[]" class="form-control item-subtotal" placeholder="Subtotal" value="<?= float_to_br($item['subtotal']) ?>" readonly></div>
                                <div class="col-1 text-center"><button type="button" class="btn btn-danger btn-sm remove-item">X</button></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <div class="row g-2 item-row mb-2 border-bottom pb-2 d-none" id="item-template">
                        <div class="col-5">
                             <input type="text" name="item_produto[]" class="form-control item-produto" placeholder="Produto/Serviço">
                             <div class="lista-produtos list-group"></div>
                        </div>
                        <div class="col-1"><input type="number" name="item_quantidade[]" class="form-control item-qtd" value="1" min="1"></div>
                        <div class="col-3"><input type="text" name="item_valor_unit[]" class="form-control item-vu" placeholder="Unit." value="0,00"></div>
                        <div class="col-2"><input type="text" name="item-subtotal[]" class="form-control item-subtotal" placeholder="Subtotal" value="0,00" readonly></div>
                        <div class="col-1 text-center"><button type="button" class="btn btn-danger btn-sm remove-item">X</button></div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-9 offset-md-1 text-end fw-bold">Total Peças/Serviços:</div>
                    <div class="col-md-2 text-md-start">
                        <span id="total-pecas-servicos" class="form-control bg-light fw-bold text-end"><?= float_to_br(0) ?></span>
                        <input type="hidden" name="total_pecas_servicos_input" id="total_pecas_servicos_input" value="<?= float_to_br(0) ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Totais e Pagamento -->
        <div class="card mt-4 shadow-sm">
            <div class="card-header bg-secondary text-white fw-bold d-flex justify-content-between align-items-center">
                💲 Totais e Pagamento
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Status de Pagamento</label>
                        <select name="status_pagamento" class="form-select">
                            <option value="PENDENTE" <?= ($status_pagamento === 'PENDENTE') ? 'selected' : '' ?>>PENDENTE</option>
                            <option value="PAGO" <?= ($status_pagamento === 'PAGO') ? 'selected' : '' ?>>PAGO</option>
                            <option value="PARCIAL" <?= ($status_pagamento === 'PARCIAL') ? 'selected' : '' ?>>PARCIAL</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Data de Pagamento (Opcional)</label>
                        <input type="date" name="data_pagamento" class="form-control" value="<?= $data_pagamento ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Custo Deslocamento/Taxas</label>
                        <input type="text" name="custo_desloc" id="custo-desloc" class="form-control text-end" value="<?= $custo_desloc ?>">
                    </div>

                    <hr class="mt-4 mb-3">

                    <div class="col-md-4">
                        <label class="form-label">Valor Pago pelo Cliente</label>
                        <input type="text" name="valor_pago" id="valor-pago" class="form-control text-end" value="<?= $valor_pago ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label text-success fw-bold">TOTAL FINAL DA OS (R$)</label>
                        <input type="hidden" name="valor_total_final" id="valor-total-final-input" value="<?= $valor_total_os ?>">
                        <span id="valor-total-final" class="form-control bg-success text-white fw-bold text-end fs-5"><?= $valor_total_os ?></span>
                    </div>

                </div>
            </div>
        </div>

        <!-- Equipamentos -->
        <div class="card mt-4 shadow-sm">
            <div class="card-header bg-light fw-bold d-flex justify-content-between align-items-center">
                ⚙️ Equipamentos do Cliente
                <button type="button" class="btn btn-sm btn-info text-white" id="btn-adicionar-equip">Adicionar Equipamento</button>
            </div>
            <div class="card-body">
                <div id="equip-container">

                    <div class="row g-2 equip-row mb-2 border-bottom pb-2 <?= $equip_template_class ?>" id="equip-template">
                        <div class="col-md-3 col-6"><input type="text" name="equip_nome[]" class="form-control" placeholder="Nome (Ex: DVR, NVR, Alarme)"></div>
                        <div class="col-md-2 col-6"><input type="text" name="equip_modelo[]" class="form-control" placeholder="Modelo"></div>
                        <div class="col-md-2 col-6"><input type="text" name="equip_serie[]" class="form-control" placeholder="Série"></div>
                        <div class="col-md-2 col-6"><input type="text" name="equip_usuario[]" class="form-control" placeholder="Usuário"></div>
                        <div class="col-md-1 col-6"><input type="text" name="equip_senha[]" class="form-control" placeholder="Senha"></div>
                        <div class="col-md-1 col-6"><input type="text" name="equip_ip[]" class="form-control" placeholder="IP/Porta"></div>
                        <div class="col-md-1 col-12 text-center">
                            <button type="button" class="btn btn-danger btn-sm remove-equip">X</button>
                        </div>
                    </div>

                    <?php if (!empty($equips)): ?>
                        <?php foreach ($equips as $equip): ?>
                            <div class="row g-2 equip-row mb-2 border-bottom pb-2">
                                <div class="col-md-3 col-6"><input type="text" name="equip_nome[]" class="form-control" placeholder="Nome" value="<?= htmlspecialchars($equip['equipamento'] ?? '') ?>"></div>
                                <div class="col-md-2 col-6"><input type="text" name="equip_modelo[]" class="form-control" placeholder="Modelo" value="<?= htmlspecialchars($equip['modelo'] ?? '') ?>"></div>
                                <div class="col-md-2 col-6"><input type="text" name="equip_serie[]" class="form-control" placeholder="Série" value="<?= htmlspecialchars($equip['serie'] ?? '') ?>"></div>
                                <div class="col-md-2 col-6"><input type="text" name="equip_usuario[]" class="form-control" placeholder="Usuário" value="<?= htmlspecialchars($equip['usuario'] ?? '') ?>"></div>
                                <div class="col-md-1 col-6"><input type="text" name="equip_senha[]" class="form-control" placeholder="Senha" value="<?= htmlspecialchars($equip['senha'] ?? '') ?>"></div>
                                <div class="col-md-1 col-6"><input type="text" name="equip_ip[]" class="form-control" placeholder="IP/Porta" value="<?= htmlspecialchars($equip['ip'] ?? '') ?>"></div>
                                <div class="col-md-1 col-12 text-center">
                                    <button type="button" class="btn btn-danger btn-sm remove-equip">X</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <!-- Fotos -->
        <div class="card mt-4 shadow-sm">
            <div class="card-header bg-light fw-bold">📸 Fotos do Serviço</div>
            <div class="card-body">
                <p class="mb-2">Fotos Atuais (Clique para remover):</p>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <?php foreach ($fotos as $foto): 
                        $caminho_foto = htmlspecialchars($foto['file_path'] ?? '');
                        if (!empty($caminho_foto)): ?>
                        <div class="foto-item position-relative" style="width: 100px; height: 100px; overflow: hidden; border: 1px solid #ccc;">
                            <img src="<?= $caminho_foto ?>" alt="Foto OS" style="width: 100%; height: 100%; object-fit: cover;">
                            <button type="button" class="btn btn-danger btn-sm delete-foto position-absolute top-0 end-0" data-foto-id="<?= $foto['id'] ?>" title="Remover Foto">X</button>
                        </div>
                    <?php endif; endforeach; ?>
                </div>

                <label for="fotos" class="form-label">Adicionar Novas Fotos (Tipo: Depois)</label>
                <input type="file" name="fotos[]" id="fotos" class="form-control" multiple accept="image/*">
            </div>
        </div>

        <!-- Assinatura -->
        <div class="card mt-4 shadow-sm">
            <div class="card-header bg-light fw-bold">✍️ Assinatura do Cliente</div>
            <div class="card-body">
                <?php if (!empty($assinatura_existente)): ?>
                    <p class="mb-2">Assinatura Atual (Salva):</p>
                    <div class="mb-3 border p-2" style="max-width: 400px;">
                        <img src="<?= $assinatura_existente ?>" alt="Assinatura Cliente" style="max-width: 100%; height: auto;">
                    </div>
                <?php endif; ?>

                <p>Desenhe a nova assinatura abaixo ou use a existente.</p>
                <div class="border" style="max-width: 400px; background-color: #fff;">
                    <canvas id="signature-canvas" style="width: 100%; height: 200px;"></canvas>
                </div>
                <button type="button" id="limpar-assinatura" class="btn btn-warning btn-sm mt-2">Limpar Assinatura</button>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Salvar Ordem de Serviço</button>
            <a href="os.php" class="btn btn-secondary">← Voltar</a>
        </div>
    </form>
</div>

<script>
// ==========================================================
// FUNÇÕES AUXILIARES DE FORMATAÇÃO E CÁLCULO (JS)
// ==========================================================
function brToFloat(val) {
    if (!val) return 0.00;
    const rawValue = String(val).replace(/R\$\s?|\./g, '').replace(',', '.').replace(/[^\d.]/g, '');
    return parseFloat(rawValue) || 0.00;
}

function floatToBr(val) {
    const number = isNaN(val) ? 0 : val;
    return number.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function calcularSubtotalLinha(row) {
    const qtdInput = row.querySelector('.item-qtd');
    const vuInput = row.querySelector('.item-vu');
    const subtotalInput = row.querySelector('.item-subtotal');

    const quantidade = parseInt(qtdInput.value, 10) || 0;
    const valorUnit = brToFloat(vuInput.value);

    const subtotal = quantidade * valorUnit;
    subtotalInput.value = floatToBr(subtotal);
}

function atualizarTotaisOS() {
    let totalPecasServicos = 0;

    document.querySelectorAll('#item-container .item-row:not(#item-template)').forEach(row => {
        calcularSubtotalLinha(row);
        const subtotalValor = row.querySelector('.item-subtotal').value;
        totalPecasServicos += brToFloat(subtotalValor);
    });

    const custoDesloc = brToFloat(document.getElementById('custo-desloc').value || '0');
    const valorTotalFinal = totalPecasServicos + custoDesloc;

    const totalPecasSpan = document.getElementById('total-pecas-servicos');
    const totalPecasInput = document.getElementById('total_pecas_servicos_input');

    totalPecasSpan.innerText = floatToBr(totalPecasServicos);
    totalPecasInput.value = floatToBr(totalPecasServicos);

    const finalTotalSpan = document.getElementById('valor-total-final');
    const finalTotalInput = document.getElementById('valor-total-final-input');

    finalTotalSpan.innerText = floatToBr(valorTotalFinal);
    finalTotalInput.value = floatToBr(valorTotalFinal);
}

// ==========================================================
// MANIPULAÇÃO DE EVENTOS E LÓGICA DE INTERFACE (JS)
// ==========================================================
document.addEventListener('DOMContentLoaded', function() {

    // aplicar máscara (usa jquery.mask se existir)
    function aplicarMascara(element) {
        if (typeof $ !== 'undefined' && $.fn.mask) {
            $(element).mask('0.000.000.000,00', { reverse: true, selectOnFocus: true });
        } else {
            element.addEventListener('keypress', function(e) {
                const char = String.fromCharCode(e.which || e.keyCode);
                if (!/[0-9,]/.test(char)) { e.preventDefault(); }
                if (char === ',' && this.value.includes(',')) { e.preventDefault(); }
            });
        }
    }

    document.querySelectorAll('.item-vu, .item-subtotal, #custo-desloc, #valor-pago').forEach(aplicarMascara);

    // Itens
    const itemContainer = document.getElementById('item-container');
    const itemTemplate = document.getElementById('item-template');

    document.getElementById('btn-adicionar-item').addEventListener('click', function() {
        const newItemRow = itemTemplate.cloneNode(true);
        newItemRow.id = '';
        newItemRow.classList.remove('d-none');

        newItemRow.querySelectorAll('input').forEach(input => {
            if (input.classList.contains('item-qtd')) {
                input.value = 1;
            } else if (input.classList.contains('item-produto')) {
                input.value = '';
            } else {
                input.value = '0,00';
            }
        });

        const vuInput = newItemRow.querySelector('.item-vu');
        const subtotalInput = newItemRow.querySelector('.item-subtotal');

        aplicarMascara(vuInput);
        aplicarMascara(subtotalInput);

        itemContainer.appendChild(newItemRow);
        atualizarTotaisOS();
    });

    itemContainer.addEventListener('click', function(e) {
        if (e.target.closest('.remove-item')) {
            const rowToRemove = e.target.closest('.item-row');
            const visibleRows = document.querySelectorAll('#item-container .item-row:not(#item-template):not(.d-none)').length;

            if (rowToRemove) {
                if (visibleRows > 1) {
                    rowToRemove.remove();
                } else {
                    rowToRemove.querySelector('.item-produto').value = '';
                    rowToRemove.querySelector('.item-qtd').value = '1';
                    rowToRemove.querySelector('.item-vu').value = '0,00';
                    rowToRemove.querySelector('.item-subtotal').value = '0,00';
                }
                atualizarTotaisOS();
            }
        }
    });

    itemContainer.addEventListener('input', e => {
        if (e.target.classList.contains('item-qtd') || e.target.classList.contains('item-vu')) {
            atualizarTotaisOS();
        }
    });

    // custo desloc
    const custoDesloc = document.getElementById('custo-desloc');
    if (custoDesloc) custoDesloc.addEventListener('input', atualizarTotaisOS);

    // inicializar cálculos (se jquery existe, trigger)
    if (typeof $ !== 'undefined') {
        $('.item-vu').trigger('input');
        $('#custo-desloc').trigger('input');
    }
    atualizarTotaisOS();

    // Equipamentos
    const equipContainer = document.getElementById('equip-container');
    const equipTemplate = document.getElementById('equip-template');

    document.getElementById('btn-adicionar-equip').addEventListener('click', function() {
        const newEquipRow = equipTemplate.cloneNode(true);
        newEquipRow.id = '';
        newEquipRow.classList.remove('d-none');
        newEquipRow.querySelectorAll('input').forEach(input => input.value = '');
        equipContainer.appendChild(newEquipRow);
    });

    document.getElementById('equip-container').addEventListener('click', function(e) {
        if (e.target.closest('.remove-equip')) {
            const rowToRemove = e.target.closest('.equip-row');
            if (rowToRemove) rowToRemove.remove();
        }
    });

    // Assinatura (SignaturePad)
    const canvas = document.getElementById('signature-canvas');
    if (canvas && typeof SignaturePad !== 'undefined') {
        // ajusta tamanho do canvas para alta DPI
        function resizeCanvasToDisplaySize(canvas) {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            const w = canvas.offsetWidth;
            const h = canvas.offsetHeight;
            if (canvas.width !== Math.floor(w * ratio) || canvas.height !== Math.floor(h * ratio)) {
                canvas.width = Math.floor(w * ratio);
                canvas.height = Math.floor(h * ratio);
                canvas.getContext("2d").scale(ratio, ratio);
            }
        }
        resizeCanvasToDisplaySize(canvas);
        const signaturePad = new SignaturePad(canvas, { backgroundColor: 'rgb(255,255,255)' });

        document.getElementById('limpar-assinatura').addEventListener('click', function() { signaturePad.clear(); });

        document.getElementById('formOS').addEventListener('submit', function(e) {
            if (!signaturePad.isEmpty()) {
                document.getElementById('assinatura_data').value = signaturePad.toDataURL();
                document.querySelector('input[name="assinatura_existente"]').value = '';
            } else {
                document.getElementById('assinatura_data').value = '';
            }
        });

        // redimensiona ao mudar o tamanho da janela
        window.addEventListener('resize', function() { resizeCanvasToDisplaySize(canvas); });
    }

    // Autocomplete Cliente
    if (typeof $ !== 'undefined' && $.ui && $.ui.autocomplete) {
        $("#cliente_nome").autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: "clientes_search.php",
                    dataType: "json",
                    data: { term: request.term },
                    success: function(data) { response(data); },
                    error: function() { response([]); }
                });
            },
            minLength: 2,
            select: function(event, ui) {
                $('#cliente_id').val(ui.item.id);
                $('#cliente_nome').val(ui.item.nome);
                $('#endereco').val(ui.item.endereco);
                $('#cidade').val(ui.item.cidade);
                $('#telefone').val(ui.item.telefone);
                return false;
            },
            focus: function(event, ui) {
                $("#cliente_nome").val(ui.item.nome);
                return false;
            }
        }).autocomplete("instance")._renderItem = function(ul, item) {
            return $("<li>")
                .append("<div>" + item.nome + " - <small>" + item.telefone + "</small></div>")
                .appendTo(ul);
        };
    }

    // Autocomplete Produto (delegado)
    $(document).on('focusin', '.item-produto', function() {
        if (!$(this).data("autocomplete")) {
            $(this).autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: "buscar_produtos.php",
                        dataType: "json",
                        data: { term: request.term },
                        success: function(data) { response(data); },
                        error: function() { response([]); }
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    var row = $(this).closest('.item-row');
                    row.find('.item-vu').val(floatToBr(ui.item.valor));
                    atualizarTotaisOS();
                    return false;
                },
                focus: function(event, ui) {
                    $(this).val(ui.item.nome);
                    return false;
                }
            }).autocomplete("instance")._renderItem = function(ul, item) {
                return $("<li>")
                    .append("<div>" + item.nome + " - R$ " + floatToBr(item.valor) + "</div>")
                    .appendTo(ul);
            };
        }
    });

    // Autocomplete Orçamento
    function atualizarLinkOrcamento(orcamento_id) {
        const linkElement = document.getElementById('link_orcamento');
        if (!linkElement) return;
        if (orcamento_id > 0) {
            const url = "orcamento_edit.php?id=" + orcamento_id;
            linkElement.href = url;
            linkElement.innerText = "Ver Orçamento #" + orcamento_id;
            linkElement.classList.remove('disabled');
            linkElement.removeAttribute('disabled');
        } else {
            linkElement.href = "#";
            linkElement.innerText = "Nenhum vinculado";
            linkElement.classList.add('disabled');
            linkElement.setAttribute('disabled', 'true');
        }
    }

    if (typeof $ !== 'undefined' && $.ui && $.ui.autocomplete) {
        $("#orcamento_numero").autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: "buscar_orcamentos.php",
                    dataType: "json",
                    data: { term: request.term },
                    success: function(data) { response(data); },
                    error: function() { response([]); }
                });
            },
            minLength: 1,
            select: function(event, ui) {
                $('#orcamento_id').val(ui.item.id);
                $('#orcamento_numero').val(ui.item.numero);
                atualizarLinkOrcamento(ui.item.id);
                return false;
            },
            focus: function(event, ui) {
                $('#orcamento_numero').val(ui.item.numero);
                return false;
            },
            change: function(event, ui) {
                if (!ui.item) {
                    $('#orcamento_id').val(0);
                    atualizarLinkOrcamento(0);
                }
            }
        }).autocomplete("instance")._renderItem = function(ul, item) {
            return $("<li>")
                .append("<div>Nº " + item.numero + " - Cliente: " + item.cliente + "</div>")
                .appendTo(ul);
        };
    }

    // inicializa link do orçamento se já existe
    try {
        const orcIdInicial = parseInt($('#orcamento_id').val());
        if (orcIdInicial > 0) atualizarLinkOrcamento(orcIdInicial);
    } catch (e) { /* ignore */ }

    // Remover foto (AJAX) - implementa remoção visual e chamada backend (opcional)
    $(document).on('click', '.delete-foto', function() {
        const btn = $(this);
        const fotoId = btn.data('foto-id');
        if (!fotoId) {
            btn.closest('.foto-item').remove();
            return;
        }
        if (!confirm('Remover esta foto?')) return;
        $.ajax({
            url: 'os_delete_foto.php',
            method: 'POST',
            data: { id: fotoId },
            success: function(resp) {
                // espera retorno json {success:true}
                try {
                    const j = (typeof resp === 'string') ? JSON.parse(resp) : resp;
                    if (j.success) btn.closest('.foto-item').remove();
                    else alert(j.error || 'Erro ao remover foto.');
                } catch (err) { alert('Resposta inválida do servidor.'); }
            },
            error: function() { alert('Erro ao conectar com o servidor.'); }
        });
    });

});
</script>

<?php include "layout_footer.php"; ?>
