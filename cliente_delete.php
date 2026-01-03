<?php
require 'config.php';
if (session_status()===PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
  $del = $conn->prepare("DELETE FROM clientes WHERE id=?");
  $del->bind_param("i", $id);
  $del->execute();
}
header("Location: clientes.php");
exit;