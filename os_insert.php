<?php
require "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: os_add.php");
    exit;
}

// ==========================================================
// RECEBER DADOS
// ==========================================================
$cliente_id   = intval($_POST['cliente_id'] ?? 0);
$cliente_nome = trim($_POST['cliente_nome'] ?? ''); 
$endereco     = trim($_POST['endereco'] ?? '');
$cidade       = trim($_POST['cidade'] ?? '');
$telefone     = trim($_POST['telefone'] ?? '');
$servico      = trim($_POST['servico'] ?? '');
$data_agendada= trim($_POST['data_agendada'] ?? date('Y-m-d'));
$status       = trim($_POST['status'] ?? 'Agendada');
$observacoes  = trim($_POST['observacoes'] ?? '');

// Valores iniciais
$custo_desloc = 0.00;
$valor_total  = 0.00;
$valor_pago   = 0.00;

// ==========================================================
// VALIDAÇÕES
// ==========================================================
if (empty($cliente_nome)) {
    $_SESSION['msg_erro'] = "⚠️ Nome do cliente é obrigatório!";
    header("Location: os_add.php");
    exit;
}

if (empty($servico)) {
    $_SESSION['msg_erro'] = "⚠️ Serviço é obrigatório!";
    header("Location: os_add.php");
    exit;
}

// Se cliente_id foi informado, verifica se existe
if ($cliente_id > 0) {
    $check = $conn->prepare("SELECT id FROM clientes WHERE id = ?");
    $check->bind_param("i", $cliente_id);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows === 0) {
        // Cliente não existe mais, remove vínculo
        $cliente_id = 0;
    }
}

// ==========================================================
// INICIA TRANSAÇÃO
// ==========================================================
$conn->begin_transaction();

try {
    
    // ==========================================================
    // INSERIR OS
    // ==========================================================
    $sql = "
        INSERT INTO os (
            cliente_id, cliente_nome, endereco, cidade, telefone, 
            servico, data_agendada, status, observacoes, 
            custo_desloc, valor_total, valor_pago, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Erro na preparação do SQL: " . $conn->error);
    }

    $stmt->bind_param(
        "issssssssddd", 
        $cliente_id, 
        $cliente_nome, 
        $endereco, 
        $cidade, 
        $telefone, 
        $servico, 
        $data_agendada, 
        $status, 
        $observacoes,
        $custo_desloc,
        $valor_total,
        $valor_pago
    );

    if (!$stmt->execute()) {
        throw new Exception("Erro ao salvar OS: " . $stmt->error);
    }

    $new_id = $stmt->insert_id;
    $stmt->close();
    
    // Confirma transação
    $conn->commit();
    
    // Mensagem de sucesso
    $_SESSION['msg_sucesso'] = "✅ OS #$new_id criada com sucesso!";
    
    // Redireciona para edição
    header("Location: os_edit.php?id=" . $new_id);
    exit;
    
} catch (Exception $e) {
    
    // Reverte em caso de erro
    $conn->rollback();
    
    $_SESSION['msg_erro'] = "❌ Erro ao criar OS: " . $e->getMessage();
    header("Location: os_add.php");
    exit;
}