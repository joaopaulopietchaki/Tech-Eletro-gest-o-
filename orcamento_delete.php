<?php
require "config.php";

if (session_status() === PHP_SESSION_NONE) session_start();

// Verifica autenticação
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Verifica se recebeu ID
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['error'] = "⚠️ ID do orçamento inválido.";
    header("Location: orcamentos.php");
    exit;
}

// Verifica se o orçamento existe
$check = $conn->prepare("SELECT id, cliente_nome FROM orcamentos WHERE id = ?");
$check->bind_param("i", $id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "⚠️ Orçamento #$id não encontrado.";
    header("Location: orcamentos.php");
    exit;
}

$orcamento = $result->fetch_assoc();

// Inicia transação para garantir integridade
$conn->begin_transaction();

try {
    
    // 1. Deleta os itens do orçamento
    $stmt_itens = $conn->prepare("DELETE FROM orcamentos_itens WHERE orcamento_id = ?");
    $stmt_itens->bind_param("i", $id);
    
    if (!$stmt_itens->execute()) {
        throw new Exception("Erro ao deletar itens: " . $stmt_itens->error);
    }
    
    $itens_deletados = $stmt_itens->affected_rows;
    $stmt_itens->close();
    
    // 2. Deleta o orçamento
    $stmt_orc = $conn->prepare("DELETE FROM orcamentos WHERE id = ?");
    $stmt_orc->bind_param("i", $id);
    
    if (!$stmt_orc->execute()) {
        throw new Exception("Erro ao deletar orçamento: " . $stmt_orc->error);
    }
    
    $stmt_orc->close();
    
    // Confirma transação
    $conn->commit();
    
    // Mensagem de sucesso
    $_SESSION['message'] = "✅ Orçamento #$id (" . htmlspecialchars($orcamento['cliente_nome']) . ") excluído com sucesso!";
    if ($itens_deletados > 0) {
        $_SESSION['message'] .= " [$itens_deletados item(ns) removido(s)]";
    }
    
    header("Location: orcamentos.php");
    exit;
    
} catch (Exception $e) {
    
    // Reverte em caso de erro
    $conn->rollback();
    
    $_SESSION['error'] = "❌ Erro ao excluir orçamento: " . $e->getMessage();
    header("Location: orcamentos.php");
    exit;
}