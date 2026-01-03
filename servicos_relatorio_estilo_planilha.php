<?php
require 'config.php';
require 'vendor/autoload.php';

use Dompdf\Dompdf;

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) exit;

$ids = $_POST['ids'] ?? [];
if (!$ids) exit;

$html = "
<style>
body{font-family:DejaVu Sans;font-size:12px;}
.bloco{margin-bottom:25px;border-bottom:4px solid #1e88e5;padding-bottom:10px;}
.titulo{font-size:14px;font-weight:bold;margin-bottom:5px;}
.linha{display:flex;justify-content:space-between;}
.col{width:48%;}
.verde{color:green;font-weight:bold;}
.vermelho{color:red;font-weight:bold;}
.amarelo{background:#fff176;padding:3px 6px;font-weight:bold;}
.resumo{margin-top:30px;font-size:14px;}
</style>

<h2 style='text-align:center'>Relatório de Serviços</h2>
";

$total_servicos = 0;
$total_reserva = 0;
$socio1 = 0;
$socio2 = 0;

foreach ($ids as $id) {
    $id = (int)$id;
    $s = $conn->query("SELECT * FROM servicos WHERE id=$id")->fetch_assoc();
    if (!$s) continue;

    $ganho = $s['valor_recebido'];
    $custo = $s['custo_total'];
    $lucro = $s['lucro'];
    $reserva = $s['reserva_emergencia'];

    $itens = $conn->query("SELECT * FROM servico_itens WHERE servico_id=$id");

    $html .= "
    <div class='bloco'>
        <div class='titulo'>{$s['nome_cliente']}</div>

        <div class='linha'>
            <div class='col'>
                <b>Gastos</b><br>";

    if ($s['desloc'] > 0) {
        $html .= "<span class='vermelho'>R$ ".number_format($s['desloc'],2,',','.')."</span> deslocamento<br>";
    }

    while ($i = $itens->fetch_assoc()) {
        $html .= "<span class='vermelho'>R$ ".number_format($i['valor_unit'],2,',','.')."</span> {$i['produto_nome']}<br>";
    }

    $html .= "
            </div>
            <div class='col'>
                <b>Ganhos</b><br>
                <span class='verde'>R$ ".number_format($ganho,2,',','.')."</span><br><br>

                Lucro limpo<br>
                <span class='amarelo'>R$ ".number_format($lucro,2,',','.')."</span><br>
                <small>R$ ".number_format($lucro/2,2,',','.')." cada sócio</small>
            </div>
        </div>
    </div>
    ";

    $total_servicos += $ganho;
    $total_reserva  += $reserva;
    $socio1 += $s['socio1_valor'];
    $socio2 += $s['socio2_valor'];
}

// RESUMO FINAL
$html .= "
<div class='resumo'>
<b>Total serviços:</b> R$ ".number_format($total_servicos,2,',','.')."<br>
<b>Reserva emergência:</b> R$ ".number_format($total_reserva,2,',','.')."<br>
<b>Lucro limpo cada sócio:</b> R$ ".number_format(($socio1+$socio2)/2,2,',','.')."
</div>
";

$pdf = new Dompdf();
$pdf->loadHtml($html);
$pdf->setPaper('A4','portrait');
$pdf->render();
$pdf->stream("relatorio_servicos_planilha.pdf", ["Attachment"=>false]);
exit;