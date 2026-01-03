<?php
require "config.php";
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

if ($_POST && isset($_FILES['backup_file'])) {

    $file = $_FILES['backup_file']['tmp_name'];

    if (!file_exists($file)) {
        $_SESSION['msg'] = "❌ Nenhum arquivo enviado.";
        header("Location: configuracoes.php");
        exit;
    }

    $cmd = "mysql --user={$user} --password={$pass} --host={$host} {$db} < $file";
    system($cmd);

    $_SESSION['msg'] = "✅ Backup restaurado com sucesso!";
    header("Location: configuracoes.php");
    exit;
}

echo "Acesso inválido.";