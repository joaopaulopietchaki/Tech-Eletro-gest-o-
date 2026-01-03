<?php
include 'config.php';

// Recebe filtros
$inicio = $_GET['inicio'] ?? '1900-01-01';
$fim = $_GET['fim'] ?? '2999-12-31';
$cliente = $_GET['cliente'] ?? '';
$servico = $_GET['servico'] ?? '';

$sql = "SELECT * FROM servicos WHERE data BETWEEN '$inicio' AND '$fim'";
if ($cliente != "") $sql .= " AND nome_cliente LIKE '%$cliente%'";
if ($servico != "") $sql .= " AND servico_executado LIKE '%$servico%'";
$sql .= " ORDER BY data DESC";

$res = $conn->query($sql);

// Dompdf
require_once "dompdf/autoload.inc.php";
use Dompdf\Dompdf;

$dompdf = new Dompdf();

$html = "
<h2 style='text-align:center'>Relatório Financeiro</h2>
<p><b>Período:</b> $inicio até $fim</p>

<table border='1' cellpadding='6' cellspacing='0' width='100%'>
<tr style='background:#eee'>
<th>Data</th><th>Cliente</th><th>Serviço</th>
<th>Recebido</th><th>Custo</th>
<th>Reserva</th><th>Sócio 1</th><th>Sócio 2</th><th>Lucro</th>
</tr>
";

while($d = $res->fetch_assoc()) {
$html .= "
<tr>
<td>".date('d/m/Y', strtotime($d['data']))."</td>
<td>{$d['nome_cliente']}</td>
<td>{$d['servico_executado']}</td>
<td>R$ ".number_format($d['valor_recebido'],2,",",".")."</td>
<td>R$ ".number_format($d['custo_total'],2,",",".")."</td>
<td>R$ ".number_format($d['reserva_emergencia'],2,",",".")."</td>
<td>R$ ".number_format($d['socio1_valor'],2,",",".")."</td>
<td>R$ ".number_format($d['socio2_valor'],2,",",".")."</td>
<td>R$ ".number_format($d['lucro'],2,",",".")."</td>
</tr>";
}

$html .= "</table>";

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("relatorio_financeiro.pdf", ["Attachment" => true]);