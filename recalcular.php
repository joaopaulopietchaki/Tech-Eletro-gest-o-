<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) exit("Acesso negado");

$q = $conn->query("SELECT * FROM servicos");

while($s = $q->fetch_assoc()){

    $id = $s['id'];
    $valor = $s['valor_recebido'];
    $custo = $s['custo_total'];

    $lucro_bruto = $valor - $custo;
    $reserva = ($lucro_bruto > 0) ? ($lucro_bruto * ($RESERVA_PCT/100)) : 0;
    $lucro_liq = $lucro_bruto - $reserva;

    $s1 = $lucro_liq * ($SOCIO_PCT/100);
    $s2 = $lucro_liq * ($SOCIO_PCT/100);

    $upd = $conn->prepare("UPDATE servicos 
        SET lucro=?, socio1_valor=?, socio2_valor=?, reserva_emergencia=? 
        WHERE id=?");
    $upd->bind_param("ddddi", $lucro_liq, $s1, $s2, $reserva, $id);
    $upd->execute();
}

echo "✅ Recalculado com sucesso!";
?>
