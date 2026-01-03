<?php
require "config.php";
require_once __DIR__ . "/vendor/autoload.php"; // Biblioteca mPDF

use Mpdf\Mpdf;

if (session_status() === PHP_SESSION_NONE) session_start();

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("⚠️ OS inválida ou não informada.");
}

// === BUSCAR DADOS DA OS ===
$stmt = $conn->prepare("SELECT * FROM os WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$os = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$os) die("⚠️ OS não encontrada.");

// === EQUIPAMENTOS ===
$equipamentos = [];
$resEquip = $conn->query("SELECT * FROM os_equipamentos WHERE os_id = $id");
if ($resEquip && $resEquip->num_rows > 0) {
    $equipamentos = $resEquip->fetch_all(MYSQLI_ASSOC);
}

// === LOGO ===
$logo = !empty($EMPRESA_LOGO) ? "uploads/" . $EMPRESA_LOGO : "";

// === HTML DO PDF ===
$html = "
<html>
<head>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #0d6efd; margin-bottom: 20px; padding-bottom: 10px; }
        .logo { max-height: 60px; }
        .titulo { font-size: 18px; font-weight: bold; color: #0d6efd; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background-color: #f8f9fa; }
        .assinatura { text-align: center; margin-top: 40px; }
        .assinatura img { max-width: 300px; border: 1px solid #ccc; border-radius: 6px; }
        .footer { position: fixed; bottom: 0; text-align: center; font-size: 10px; color: #777; }
    </style>
</head>
<body>

<div class='header'>
";

// === CABEÇALHO COM LOGO OU NOME ===
if ($logo && file_exists($logo)) {
    $html .= "<img src='$logo' class='logo'><br>";
} else {
    $html .= "<div style='font-size:22px;font-weight:bold;color:#0d6efd;'>$EMPRESA_NOME</div>";
}

$html .= "
    <div class='titulo'>Ordem de Serviço #{$os['id']}</div>
</div>

<table>
    <tr><th>Cliente</th><td>" . htmlspecialchars($os['cliente']) . "</td></tr>
    <tr><th>Endereço</th><td>" . htmlspecialchars($os['endereco']) . "</td></tr>
    <tr><th>Serviço</th><td>" . htmlspecialchars($os['servico']) . "</td></tr>
    <tr><th>Data Agendada</th><td>" . date('d/m/Y', strtotime($os['data_agendada'])) . "</td></tr>
    <tr><th>Status</th><td>" . htmlspecialchars($os['status']) . "</td></tr>
    <tr><th>Observações</th><td>" . nl2br(htmlspecialchars($os['observacoes'])) . "</td></tr>
</table>
";

// === EQUIPAMENTOS INSTALADOS ===
if (!empty($equipamentos)) {
    $html .= "<h3 style='margin-top:20px;color:#0d6efd;'>🧰 Equipamentos Instalados</h3>
    <table>
        <thead>
            <tr>
                <th>Equipamento</th>
                <th>Modelo</th>
                <th>NS</th>
                <th>Usuário</th>
                <th>Senha</th>
                <th>IP/DDNS</th>
                <th>Obs</th>
            </tr>
        </thead>
        <tbody>";

    foreach ($equipamentos as $eq) {
        $html .= "<tr>
            <td>" . htmlspecialchars($eq['equipamento']) . "</td>
            <td>" . htmlspecialchars($eq['modelo']) . "</td>
            <td>" . htmlspecialchars($eq['serie']) . "</td>
            <td>" . htmlspecialchars($eq['usuario']) . "</td>
            <td>" . htmlspecialchars($eq['senha']) . "</td>
            <td>" . htmlspecialchars($eq['ip']) . "</td>
            <td>" . htmlspecialchars($eq['extra']) . "</td>
        </tr>";
    }

    $html .= "</tbody></table>";
}

// === ASSINATURA DO CLIENTE ===
if (!empty($os['assinatura'])) {
    $html .= "
        <div class='assinatura'>
            <h3 style='color:#0d6efd;'>✍️ Assinatura do Cliente</h3>
            <img src='" . $os['assinatura'] . "' alt='Assinatura do Cliente'>
        </div>
    ";
}

$html .= "
<div class='footer'>
    © " . date('Y') . " $EMPRESA_NOME — Todos os direitos reservados.
</div>

</body></html>
";

// === GERAR PDF ===
$mpdf = new Mpdf(['format' => 'A4']);
$mpdf->WriteHTML($html);
$mpdf->Output("Ordem_de_Servico_{$os['id']}.pdf", "I");