<?php
require 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$ids = $_POST['ids'] ?? [];

if (!is_array($ids) || count($ids) === 0) {
    $_SESSION['msg'] = "⚠️ Nenhum serviço selecionado.";
    header("Location: servicos.php");
    exit;
}

foreach ($ids as $id) {
    $id = intval($id);

    // Apaga itens
    $stmt = $conn->prepare("DELETE FROM servico_itens WHERE servico_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Apaga serviço
    $stmt = $conn->prepare("DELETE FROM servicos WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

$_SESSION['msg'] = "🗑️ Serviços excluídos com sucesso!";
header("Location: servicos.php");
exit;