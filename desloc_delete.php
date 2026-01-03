<?php
require 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    $del = $conn->prepare("DELETE FROM desloc_pagamentos WHERE id=?");
    $del->bind_param("i", $id);
    $del->execute();
    $_SESSION['msg'] = "🗑️ Pagamento de deslocamento excluído.";
}
header("Location: socio.php");
exit;