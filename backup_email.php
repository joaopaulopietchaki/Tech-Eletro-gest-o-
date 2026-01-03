<?php
require 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    exit("Acesso negado");
}

// CONFIG DB
$host = "localhost";
$user = "joao0106_financeiro";
$pass = "padrao203040";
$db   = "joao0106_financeiro";

// CONFIG EMAIL
$para = "techeletroloja@gmail.com"; // email configurado
$assunto = "Backup do Sistema Tech Eletro - " . date("d/m/Y H:i");
$mensagem = "Segue em anexo o backup automático do banco de dados do sistema Tech Eletro.";

$backup_file = "backup_" . date("Y-m-d_H-i-s") . ".sql";

// EXECUTA BACKUP
$command = "mysqldump --user={$user} --password={$pass} --host={$host} {$db} > {$backup_file}";
system($command);

// LÊ ARQUIVO E CODIFICA
$conteudo = chunk_split(base64_encode(file_get_contents($backup_file)));

$boundary = md5(time());

// CABEÇALHOS
$headers  = "MIME-Version: 1.0\r\n";
$headers .= "From: Tech Eletro Sistema <no-reply@playtvtech.xyz>\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"";

// CORPO
$body  = "--{$boundary}\r\n";
$body .= "Content-Type: text/plain; charset=utf-8\r\n\r\n";
$body .= "$mensagem\r\n\r\n";
$body .= "--{$boundary}\r\n";
$body .= "Content-Type: application/octet-stream; name=\"{$backup_file}\"\r\n";
$body .= "Content-Transfer-Encoding: base64\r\n";
$body .= "Content-Disposition: attachment; filename=\"{$backup_file}\"\r\n\r\n";
$body .= "$conteudo\r\n";
$body .= "--{$boundary}--";

// ENVIA EMAIL
if (mail($para, $assunto, $body, $headers)) {
    echo "✅ Backup enviado com sucesso para: <b>{$para}</b>";
} else {
    echo "❌ Erro ao enviar backup. Verifique configuração do servidor.";
}

// REMOVE BACKUP TEMPORÁRIO
unlink($backup_file);
?>
