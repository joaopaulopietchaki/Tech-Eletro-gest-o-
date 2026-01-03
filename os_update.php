<?php
require "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    die("Acesso negado.");
}

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) die("ID da OS inválido.");

/* ============================================================
   1 - RECEBE DADOS PRINCIPAIS
   ============================================================ */
$cliente_id   = intval($_POST['cliente_id'] ?? 0);
$cliente_nome = trim($_POST['cliente'] ?? "");
$endereco     = trim($_POST['endereco'] ?? "");
$cidade       = trim($_POST['cidade'] ?? "");
$telefone     = trim($_POST['telefone'] ?? "");
$servico      = trim($_POST['servico'] ?? "");
$data_agendada = $_POST['data_agendada'] ?? "";
$status       = trim($_POST['status'] ?? "");
$observacoes  = trim($_POST['observacoes'] ?? "");
$assinatura   = trim($_POST['assinatura'] ?? "");

/* ============================================================
   2 - ATUALIZA OS PRINCIPAL
   ============================================================ */
$stmt = $conn->prepare("
    UPDATE os SET 
        cliente_id=?, 
        cliente_nome=?, 
        endereco=?, 
        cidade=?, 
        telefone=?, 
        servico=?, 
        data_agendada=?, 
        status=?, 
        observacoes=?, 
        assinatura=?
    WHERE id=?
");

$stmt->bind_param(
    "isssssssssi",
    $cliente_id,
    $cliente_nome,
    $endereco,
    $cidade,
    $telefone,
    $servico,
    $data_agendada,
    $status,
    $observacoes,
    $assinatura,
    $id
);

$stmt->execute();
$stmt->close();

/* ============================================================
   3 - ATUALIZA EQUIPAMENTOS → apaga tudo e insere novamente
   ============================================================ */
$conn->query("DELETE FROM os_equipamentos WHERE os_id = $id");

if (!empty($_POST['equip_nome'])) {

    $stmtEquip = $conn->prepare("
        INSERT INTO os_equipamentos 
        (os_id, equipamento, modelo, serie, usuario, senha, ip, extra)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $equip_nome   = $_POST['equip_nome'];
    $equip_modelo = $_POST['equip_modelo'];
    $equip_serie  = $_POST['equip_serie'];
    $equip_user   = $_POST['equip_usuario'];
    $equip_senha  = $_POST['equip_senha'];
    $equip_ip     = $_POST['equip_ip'];
    $equip_obs    = $_POST['equip_obs'];

    for ($i = 0; $i < count($equip_nome); $i++) {

        if (trim($equip_nome[$i]) == "") continue;

        $stmtEquip->bind_param(
            "isssssss",
            $id,
            $equip_nome[$i],
            $equip_modelo[$i],
            $equip_serie[$i],
            $equip_user[$i],
            $equip_senha[$i],
            $equip_ip[$i],
            $equip_obs[$i]
        );

        $stmtEquip->execute();
    }

    $stmtEquip->close();
}

/* ============================================================
   4 - FOTOS NOVAS
   ============================================================ */

if (!empty($_FILES['fotos']['name'][0])) {

    $dir = "uploads/os/";
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    foreach ($_FILES['fotos']['tmp_name'] as $i => $tmp) {

        if (!is_uploaded_file($tmp)) continue;

        $nomeArq = time() . "_" . rand(1000,9999) . "_" . basename($_FILES['fotos']['name'][$i]);
        $destino = $dir . $nomeArq;

        move_uploaded_file($tmp, $destino);

        $stmtF = $conn->prepare("
            INSERT INTO os_fotos (os_id, file_path) 
            VALUES (?, ?)
        ");
        $stmtF->bind_param("is", $id, $destino);
        $stmtF->execute();
        $stmtF->close();
    }
}

/* ============================================================
   5 - FINALIZAÇÃO
   ============================================================ */

$_SESSION['msg_sucesso'] = "OS #$id atualizada com sucesso!";

header("Location: os_view.php?id=$id");
exit;

?>