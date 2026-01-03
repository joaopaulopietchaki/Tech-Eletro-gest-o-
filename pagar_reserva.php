<?php
require 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valor = isset($_POST['valor']) ? (float)$_POST['valor'] : 0;
    $data  = !empty($_POST['data']) ? $_POST['data'] : date('Y-m-d');
    $obs   = trim($_POST['obs'] ?? '');

    if ($valor > 0) {
        $ins = $conn->prepare("INSERT INTO reserva_pagamentos (valor, data, obs) VALUES (?,?,?)");
        $ins->bind_param("dss", $valor, $data, $obs);
        $ins->execute();
        $_SESSION['msg'] = "✅ Pagamento da reserva registrado.";
    } else {
        $_SESSION['msg'] = "⚠️ Informe um valor válido para a reserva.";
    }
}
header("Location: socio.php");
exit;