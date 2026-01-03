<?php
// os_delete.php
require "config.php";
if (session_status()===PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$id = intval($_GET['id'] ?? 0);
if (!$id) { $_SESSION['msg'] = "ID inválido"; header("Location: os.php"); exit; }

// carregar fotos e assinatura para excluir arquivos
$res = $conn->query("SELECT fotos, assinatura FROM os WHERE id=" . intval($id));
if ($res && $res->num_rows) {
    $row = $res->fetch_assoc();
    if (!empty($row['fotos'])) {
        $fotos = json_decode($row['fotos'], true);
        if (is_array($fotos)) {
            foreach($fotos as $f) {
                $path = __DIR__.'/uploads/os_fotos/'.$f;
                if (file_exists($path)) @unlink($path);
            }
        }
    }
    if (!empty($row['assinatura'])) {
        $path = __DIR__.'/uploads/os_assinaturas/'.$row['assinatura'];
        if (file_exists($path)) @unlink($path);
    }
}

// apagar equipamentos
$conn->query("DELETE FROM os_equipamentos WHERE os_id=" . intval($id));
// apagar registro
$conn->query("DELETE FROM os WHERE id=" . intval($id));

$_SESSION['msg'] = "OS excluída";
header("Location: os.php");
exit;
?>