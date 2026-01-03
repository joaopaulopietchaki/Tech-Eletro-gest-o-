<?php
require "config.php";
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$backupFile = "backup_" . date("Y-m-d_H-i-s") . ".sql";
$command = "mysqldump --user={$user} --password={$pass} --host={$host} {$db} > $backupFile";

// Executa o dump
system($command);

// Força download
header("Content-Disposition: attachment; filename=$backupFile");
header("Content-type: application/octet-stream");
readfile($backupFile);

// Remove o arquivo do servidor após baixar
unlink($backupFile);
exit;
?>