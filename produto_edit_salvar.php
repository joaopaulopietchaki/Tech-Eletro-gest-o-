<?php
require "config.php";

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ID do produto
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['msg'] = "Produto inválido.";
    header("Location: produtos.php");
    exit;
}

// Carrega dados atuais para verificar a imagem existente
$stmt = $conn->prepare("SELECT imagem FROM produtos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$produto_atual = $stmt->get_result()->fetch_assoc();

if (!$produto_atual) {
    $_SESSION['msg'] = "Produto não encontrado.";
    header("Location: produtos.php");
    exit;
}

// Campos enviados
$nome = trim($_POST['nome'] ?? '');
$unidade = trim($_POST['unidade'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');

// Converte moeda BR para número decimal
$preco_venda = floatval(str_replace(',', '.', str_replace('.', '', $_POST['preco_venda'] ?? '0')));
$preco_custo = floatval(str_replace(',', '.', str_replace('.', '', $_POST['preco_custo'] ?? '0')));

$imagem = $produto_atual['imagem']; // mantém imagem atual por padrão

// Verifica se campos obrigatórios estão corretos
if ($nome === '') {
    $_SESSION['msg'] = "O nome do produto é obrigatório.";
    header("Location: produto_edit.php?id=" . $id);
    exit;
}

// Upload opcional
if (!empty($_FILES['imagem']['name'])) {
    $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));

    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {

        $novoNome = "produto_" . time() . "." . $ext;
        $destino = __DIR__ . "/uploads/produtos/" . $novoNome;

        if (move_uploaded_file($_FILES['imagem']['tmp_name'], $destino)) {

            // Remove imagem antiga se existir
            if (!empty($produto_atual['imagem'])) {
                $antiga = __DIR__ . "/uploads/produtos/" . $produto_atual['imagem'];
                if (file_exists($antiga)) unlink($antiga);
            }

            $imagem = $novoNome;
        }
    }
}

// Atualização no banco
$sql = "UPDATE produtos SET 
            nome = ?, 
            unidade = ?, 
            preco_venda = ?, 
            preco_custo = ?, 
            descricao = ?, 
            imagem = ?
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssddssi", $nome, $unidade, $preco_venda, $preco_custo, $descricao, $imagem, $id);

if ($stmt->execute()) {
    $_SESSION['msg'] = "Produto atualizado com sucesso!";
} else {
    $_SESSION['msg'] = "Erro ao atualizar produto: " . $stmt->error;
}

header("Location: produto_edit.php?id=" . $id);
exit;
