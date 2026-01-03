<?php
require 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header("Location: servicos.php");
    exit;
}

/* Apagar itens do serviço */
$stmt = $conn->prepare("DELETE FROM servico_itens WHERE servico_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

/* Apagar serviço */
$stmt = $conn->prepare("DELETE FROM servicos WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

$_SESSION['msg'] = "🗑️ Serviço excluído com sucesso!";
header("Location: servicos.php");
exit;