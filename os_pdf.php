<?php
// ===========================================
// OS_PDF.PHP — CORRIGIDO (Tabela 'os')
// ===========================================

// Desativa erros na tela para não quebrar o binário do PDF
ini_set('display_errors', 0);
error_reporting(0);

require "config.php";

// Tenta carregar o mPDF (ajuste o caminho se necessário)
if (file_exists(__DIR__ . "/vendor/autoload.php")) {
    require __DIR__ . "/vendor/autoload.php";
} else {
    die("Erro: Pasta 'vendor' do mPDF não encontrada. Rode 'composer install' ou verifique o caminho.");
}

use Mpdf\Mpdf;

if (session_status() === PHP_SESSION_NONE) session_start();
global $conn;

// 1. Validar ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { die("ID Inválido"); }

// 2. Buscar Dados da OS (TABELA CORRETA: 'os')
$sql = "SELECT * FROM os WHERE id = $id";
$res = $conn->query($sql);
$os = $res->fetch_assoc();

if (!$os) { 
    // Debug: Se não achar, mostra erro simples
    die("OS #$id não encontrada na tabela 'os'."); 
}

// Buscar Itens, Equipamentos e Fotos
$itens = $conn->query("SELECT * FROM os_itens WHERE os_id = $id")->fetch_all(MYSQLI_ASSOC);
$equips = $conn->query("SELECT * FROM os_equipamentos WHERE os_id = $id")->fetch_all(MYSQLI_ASSOC);
$fotos = $conn->query("SELECT * FROM os_fotos WHERE os_id = $id")->fetch_all(MYSQLI_ASSOC);

// 3. Caminho Físico para Imagens (Obrigatório para mPDF)
$upload_path = __DIR__ . "/uploads/os/"; 

// Formatação de Valores
function fmt_money($v) { return number_format((float)$v, 2, ',', '.'); }

// 4. HTML DO PDF
$html = '
<html>
<head>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { width: 100%; border-bottom: 2px solid #444; padding-bottom: 10px; margin-bottom: 20px; }
        .title { font-size: 18px; font-weight: bold; text-align: right; }
        .label { font-weight: bold; color: #555; }
        .box { background-color: #f9f9f9; padding: 10px; border: 1px solid #ddd; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th { background-color: #eee; text-align: left; padding: 8px; border: 1px solid #ccc; font-size:11px; }
        td { padding: 8px; border: 1px solid #ccc; font-size:11px; }
        .text-right { text-align: right; }
        h3 { margin-top: 20px; margin-bottom: 10px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
    </style>
</head>
<body>

    <div class="header">
        <table style="border:none;">
            <tr>
                <td style="border:none; width:50%;">
                    <h2 style="margin:0;">'. htmlspecialchars($EMPRESA_NOME ?? "Tech Eletro") .'</h2>
                    <small>Relatório Técnico</small>
                </td>
                <td style="border:none; width:50%; text-align:right;">
                    <div class="title">OS #'. $os['id'] .'</div>
                    <div>Data: '. date("d/m/Y", strtotime($os['data_agendada'] ?? date('Y-m-d'))) .'</div>
                    <div>Status: <b>'. htmlspecialchars($os['status']) .'</b></div>
                </td>
            </tr>
        </table>
    </div>

    <div class="box">
        <table style="border:none; margin:0;">
            <tr>
                <td style="border:none; width:50%;">
                    <span class="label">Cliente:</span> '. htmlspecialchars($os['cliente_nome']) .'<br>
                    <span class="label">Telefone:</span> '. htmlspecialchars($os['telefone']) .'
                </td>
                <td style="border:none; width:50%;">
                    <span class="label">Endereço:</span> '. htmlspecialchars($os['endereco']) .'<br>
                    <span class="label">Cidade:</span> '. htmlspecialchars($os['cidade']) .'
                </td>
            </tr>
        </table>
    </div>

    <div class="box">
        <span class="label">Serviço:</span> '. htmlspecialchars($os['servico']) .'<br><br>
        <span class="label">Observações:</span><br>
        '. nl2br(htmlspecialchars($os['observacoes'])) .'
    </div>

    <h3>📦 Itens / Peças</h3>';
    
    if (count($itens) > 0) {
        $html .= '<table>
            <thead>
                <tr>
                    <th>Produto</th>
                    <th width="50" align="center">Qtd</th>
                    <th width="80" align="right">Valor Un.</th>
                    <th width="80" align="right">Subtotal</th>
                </tr>
            </thead>
            <tbody>';
        foreach ($itens as $it) {
            $html .= '<tr>
                <td>'. htmlspecialchars($it['produto']) .'</td>
                <td align="center">'. $it['quantidade'] .'</td>
                <td align="right">R$ '. fmt_money($it['valor_unit']) .'</td>
                <td align="right">R$ '. fmt_money($it['subtotal']) .'</td>
            </tr>';
        }
        $html .= '</tbody></table>';
    } else {
        $html .= '<p>Nenhum item registrado.</p>';
    }

    // EQUIPAMENTOS
    $html .= '<h3>🔧 Equipamentos</h3>';
    if (count($equips) > 0) {
        $html .= '<table>
            <thead>
                <tr>
                    <th>Equipamento</th>
                    <th>Detalhes (NS / Modelo)</th>
                    <th>Acesso (User / Senha / IP)</th>
                </tr>
            </thead>
            <tbody>';
        foreach ($equips as $eq) {
            $html .= '<tr>
                <td><b>'. htmlspecialchars($eq['equipamento']) .'</b></td>
                <td>
                    NS: '. htmlspecialchars($eq['serie']) .'<br>
                    Mod: '. htmlspecialchars($eq['modelo']) .'
                </td>
                <td>
                    U: '. htmlspecialchars($eq['usuario']) .' / S: '. htmlspecialchars($eq['senha']) .'<br>
                    IP: '. htmlspecialchars($eq['ip']) .'
                </td>
            </tr>';
        }
        $html .= '</tbody></table>';
    } else {
        $html .= '<p>Nenhum equipamento registrado.</p>';
    }

    // TOTAIS
    $html .= '
    <div style="text-align:right; margin-top:10px;">
        <table style="width:45%; margin-left:auto;">
            <tr>
                <td style="background:#f0f0f0;"><b>Deslocamento:</b></td>
                <td align="right">R$ '. fmt_money($os['custo_desloc']) .'</td>
            </tr>
            <tr>
                <td style="background:#ddd;"><b>VALOR TOTAL:</b></td>
                <td align="right" style="background:#ddd;"><b>R$ '. fmt_money($os['valor_total']) .'</b></td>
            </tr>
            <tr>
                <td>Valor Pago:</td>
                <td align="right">R$ '. fmt_money($os['valor_pago']) .'</td>
            </tr>
        </table>
    </div>';

    // QUEBRA DE PÁGINA PARA FOTOS
    $html .= '<pagebreak />';
    $html .= '<h3>📸 Fotos e Assinatura</h3>';

    // Assinatura
    $caminho_ass = $upload_path . $os['assinatura'];
    if (!empty($os['assinatura']) && file_exists($caminho_ass)) {
        $html .= '<div style="margin-bottom:20px;">
            <span class="label">Assinatura do Cliente:</span><br>
            <img src="'. $caminho_ass .'" style="height:60px; border:1px solid #ccc;">
        </div>';
    }

    // Fotos
    if (count($fotos) > 0) {
        $html .= '<div>';
        foreach ($fotos as $f) {
            $caminho_foto = $upload_path . $f['file_path'];
            
            // Verifica se arquivo existe antes de tentar colocar no PDF
            if (!empty($f['file_path']) && file_exists($caminho_foto)) {
                // mPDF suporta HTML img tag com caminho absoluto de servidor
                $html .= '<img src="'. $caminho_foto .'" style="width:160px; height:120px; object-fit:cover; margin:5px; border:1px solid #999;"> ';
            }
        }
        $html .= '</div>';
    } else {
        $html .= '<p>Nenhuma foto anexada.</p>';
    }

$html .= '</body></html>';

// 5. Gerar PDF
try {
    // Configurações do mPDF (Margens, etc)
    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 15,
        'margin_bottom' => 15
    ]);
    
    $mpdf->SetTitle("OS #" . $id);
    $mpdf->WriteHTML($html);
    $mpdf->Output("OS_{$id}.pdf", "I"); // I = Abre no navegador

} catch (\Mpdf\MpdfException $e) {
    die("Erro ao gerar PDF: " . $e->getMessage());
}
