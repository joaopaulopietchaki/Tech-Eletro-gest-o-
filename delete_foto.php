<?php
// Arquivo: delete_foto.php

require "config.php"; // Inclui sua conexão
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

// Verifica a autenticação (altamente recomendado!)
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado.']);
    exit;
}

// 1. Recebe o ID da foto
$foto_id = filter_input(INPUT_POST, 'foto_id', FILTER_VALIDATE_INT);
$os_id = filter_input(INPUT_POST, 'os_id', FILTER_VALIDATE_INT); // Opcional, mas útil para logs

if (!$foto_id) {
    echo json_encode(['success' => false, 'message' => 'ID de foto inválido.']);
    exit;
}

try {
    // Início da transação (opcional, mas bom para garantir integridade)
    $conn->begin_transaction();

    // 2. Busca o caminho do arquivo no banco de dados antes de excluir
    $stmt = $conn->prepare("SELECT file_path FROM os_fotos WHERE id = ?");
    $stmt->bind_param("i", $foto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $foto_data = $result->fetch_assoc();
    $stmt->close();

    if ($foto_data) {
        // O caminho salvo no banco de dados é /uploads/os/nome_do_arquivo.jpg
        $file_path_db = $foto_data['file_path'];
        
        // Remove a barra inicial para obter o caminho relativo ao sistema de arquivos
        // Ex: /uploads/os/file.jpg -> uploads/os/file.jpg
        $file_path_fs = ltrim($file_path_db, '/');

        // 3. Exclui o registro do banco de dados
        $stmt_delete_db = $conn->prepare("DELETE FROM os_fotos WHERE id = ?");
        $stmt_delete_db->bind_param("i", $foto_id);
        $stmt_delete_db->execute();
        $stmt_delete_db->close();

        // 4. Exclui o arquivo físico (Importante: Verifique se o caminho é seguro antes!)
        $success_unlink = false;
        if (file_exists($file_path_fs) && is_file($file_path_fs)) {
            // Garante que o arquivo está dentro de 'uploads' por segurança
            if (strpos($file_path_fs, 'uploads/os/') === 0) {
                 $success_unlink = unlink($file_path_fs);
            }
        } else {
             // Considera sucesso se o arquivo já não existe (registro removido do BD)
             $success_unlink = true; 
        }

        $conn->commit();

        echo json_encode([
            'success' => true, 
            'message' => 'Foto removida.',
            'unlink_status' => $success_unlink ? 'File removed from disk' : 'File not found on disk or safe path not matched'
        ]);

    } else {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Foto não encontrada no banco de dados.']);
    }

} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Erro SQL: ' . $e->getMessage()]);
}
?>