<?php
require "config.php";

// Define como JSON
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();

// Verifica autenticação
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Acesso negado. Faça login primeiro.'
    ]);
    exit;
}

// Verifica método (aceita GET ou POST)
$id = intval($_POST['id'] ?? $_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'ID inválido.'
    ]);
    exit;
}

// Verifica se o orçamento existe
$check = $conn->prepare("SELECT id, cliente_nome, valor_total FROM orcamentos WHERE id = ?");
$check->bind_param("i", $id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false, 
        'message' => "Orçamento #$id não encontrado."
    ]);
    exit;
}

$orcamento = $result->fetch_assoc();

// Inicia transação
$conn->begin_transaction();

try {
    
    // Deleta itens
    $stmt_itens = $conn->prepare("DELETE FROM orcamentos_itens WHERE orcamento_id = ?");
    $stmt_itens->bind_param("i", $id);
    $stmt_itens->execute();
    $itens_deletados = $stmt_itens->affected_rows;
    $stmt_itens->close();
    
    // Deleta orçamento
    $stmt_orc = $conn->prepare("DELETE FROM orcamentos WHERE id = ?");
    $stmt_orc->bind_param("i", $id);
    $stmt_orc->execute();
    $stmt_orc->close();
    
    // Confirma
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Orçamento #$id excluído com sucesso!",
        'data' => [
            'id' => $id,
            'cliente' => $orcamento['cliente_nome'],
            'valor' => $orcamento['valor_total'],
            'itens_deletados' => $itens_deletados
        ]
    ]);
    exit;
    
} catch (Exception $e) {
    
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao excluir: ' . $e->getMessage()
    ]);
    exit;
}