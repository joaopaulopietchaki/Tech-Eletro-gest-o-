<?php
require "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// --- Dados principais ---
$cliente = trim($_POST['cliente'] ?? '');
$endereco = trim($_POST['endereco'] ?? '');
$servico = trim($_POST['servico'] ?? '');
$data_agendada = trim($_POST['data_agendada'] ?? '');
$hora_agendada = trim($_POST['hora_agendada'] ?? '');
$status = trim($_POST['status'] ?? 'Pendente');
$observacoes = trim($_POST['observacoes'] ?? '');
$assinatura = trim($_POST['assinatura'] ?? '');

// --- Validação ---
if ($cliente == '' || $servico == '') {
    $_SESSION['msg_erro'] = "⚠️ Campos obrigatórios não preenchidos!";
    header("Location: os_add.php");
    exit;
}

// --- Inserir nova OS ---
$stmt = $conn->prepare("
    INSERT INTO os (cliente, endereco, servico, data_agendada, hora_agendada, status, observacoes, assinatura)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("ssssssss", $cliente, $endereco, $servico, $data_agendada, $hora_agendada, $status, $observacoes, $assinatura);
$stmt->execute();
$os_id = $stmt->insert_id;
$stmt->close();

// --- Inserir equipamentos ---
if (!empty($_POST['equip_nome'])) {
    $equip_nomes = $_POST['equip_nome'];
    $equip_series = $_POST['equip_serie'];
    $equip_modelos = $_POST['equip_modelo'];
    $equip_ips = $_POST['equip_ip'];
    $equip_usuarios = $_POST['equip_usuario'];
    $equip_senhas = $_POST['equip_senha'];
    $equip_obs = $_POST['equip_obs'];

    $stmt = $conn->prepare("
        INSERT INTO os_equipamentos (os_id, equipamento, serie, modelo, ip, usuario, senha, extra)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    for ($i = 0; $i < count($equip_nomes); $i++) {
        if (trim($equip_nomes[$i]) == "") continue;
        $stmt->bind_param("isssssss", $os_id, $equip_nomes[$i], $equip_series[$i], $equip_modelos[$i], $equip_ips[$i], $equip_usuarios[$i], $equip_senhas[$i], $equip_obs[$i]);
        $stmt->execute();
    }

    $stmt->close();
}

// --- Upload de fotos ---
if (!empty($_FILES['fotos']['name'][0])) {
    $upload_dir = "uploads/os/";
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

    foreach ($_FILES['fotos']['tmp_name'] as $i => $tmp_name) {
        if (is_uploaded_file($tmp_name)) {
            $nome_arquivo = time() . "_" . basename($_FILES['fotos']['name'][$i]);
            $caminho_completo = $upload_dir . $nome_arquivo;
            move_uploaded_file($tmp_name, $caminho_completo);

            // ✅ Corrigido: grava na coluna file_path
            $stmtFoto = $conn->prepare("INSERT INTO os_fotos (os_id, file_path) VALUES (?, ?)");
            $stmtFoto->bind_param("is", $os_id, $caminho_completo);
            $stmtFoto->execute();
            $stmtFoto->close();
        }
    }
}

// --- Mensagem de sucesso ---
$_SESSION['msg_sucesso'] = "✅ Nova OS #$os_id criada com sucesso!";

// --- Redirecionar ---
header("Location: os_view.php?id=$os_id");
exit;
?>