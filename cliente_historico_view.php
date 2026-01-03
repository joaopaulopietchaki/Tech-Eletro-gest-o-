<?php
// Arquivo: cliente_historico_view.php (Versão Final Corrigida)

require "config.php"; 
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$cliente_id = intval($_GET['id'] ?? 0);

if ($cliente_id === 0) {
    die("ID de Cliente inválido.");
}

$cliente = null;
$ordens_servico = [];
$anotacoes = [];
$error = null;

// ==========================================================
// FUNÇÃO DE MAPEAR STATUS E PAGAMENTOS (Regras de Negócio)
// ==========================================================
function map_status($db_status, $db_pagamento, $valor_total) { 
    
    $valor_total = floatval($valor_total);
    $status_lower = strtolower($db_status);
    $pagamento_lower = strtolower($db_pagamento);
    $display_status = htmlspecialchars($db_status);
    $tag_class = 'bg-secondary';
    
    // 1. Mapeamento da Situação da OS (Execução)
    if ($status_lower == 'pendente') {
        $display_status = "Agendada"; 
        $tag_class = 'bg-info';
    } elseif ($status_lower == 'em andamento') {
        $display_status = "Em Execução";
        $tag_class = 'bg-primary';
    } elseif ($status_lower == 'concluída') {
        $display_status = "Concluída (Aguardando Faturamento)";
        $tag_class = 'bg-primary';
    } elseif ($status_lower == 'cancelada') {
        $display_status = "Cancelada";
        $tag_class = 'bg-dark';
    }
    
    // 2. Mapeamento Financeiro (Sobrescreve o status de Execução, se aplicável)
    
    // Regra: Orçamento
    if ($pagamento_lower == 'orcamento') {
        $display_status = "Orçamento Aguardando Aprovação";
        $tag_class = 'bg-warning';
    }
    
    // Regra: Pago -> Recebido
    if ($pagamento_lower == 'pago') {
        $display_status = "RECEBIDO / CONCLUÍDO"; 
        $tag_class = 'bg-success';
    } 
    // Regra: Pendência de Pagamento (Se não está PAGO e o valor é > 0)
    elseif ($pagamento_lower == 'pendente' && $valor_total > 0) {
        // Esta tag de perigo é a mais importante para indicar pendência financeira
        $display_status = "PENDÊNCIA DE PAGAMENTO"; 
        $tag_class = 'bg-danger'; 
    }

    return ['status' => $display_status, 'class' => $tag_class];
}
// ==========================================================
// FIM FUNÇÃO DE MAPEAR STATUS
// ==========================================================

try {
    // 1. Busca dos dados do Cliente
    $stmt_cliente = $conn->prepare("SELECT id, nome, telefone, email FROM clientes WHERE id = ?");
    $stmt_cliente->bind_param("i", $cliente_id);
    $stmt_cliente->execute();
    $result_cliente = $stmt_cliente->get_result();

    if ($result_cliente->num_rows === 0) {
        die("Cliente não encontrado.");
    }
    $cliente = $result_cliente->fetch_assoc();
    $stmt_cliente->close();

    // 2. Busca das Ordens de Serviço (OS) com as colunas CORRETAS
    // Colunas usadas: servico, status, valor_total, status_pagamento
    $sql_os = "SELECT id, data_criacao, servico, status, valor_total, status_pagamento FROM os WHERE cliente_id = ? ORDER BY id DESC";
    $stmt_os = $conn->prepare($sql_os);
    $stmt_os->bind_param("i", $cliente_id);
    $stmt_os->execute();
    $result_os = $stmt_os->get_result();
    $ordens_servico = $result_os->fetch_all(MYSQLI_ASSOC);
    $stmt_os->close();

    // 3. Busca das Anotações
    $sql_anotacoes = "SELECT id, descricao, data FROM cliente_historico WHERE cliente_id = ? AND tipo = 'Anotação' ORDER BY data DESC";
    $stmt_anotacoes = $conn->prepare($sql_anotacoes);
    $stmt_anotacoes->bind_param("i", $cliente_id);
    $stmt_anotacoes->execute();
    $result_anotacoes = $stmt_anotacoes->get_result();
    $anotacoes = $result_anotacoes->fetch_all(MYSQLI_ASSOC);
    $stmt_anotacoes->close();

} catch (mysqli_sql_exception $e) {
    // Exibe o erro de forma amigável
    $error = "Erro ao buscar dados: " . htmlspecialchars($e->getMessage()) . " Verifique se as colunas estão corretas. (Possívelmente faltando 'servico' ou 'status_pagamento' na tabela 'os').";
}

include "layout_header.php";
?>

<div class="container mt-4 mb-5">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>📋 Histórico do Cliente: <?= htmlspecialchars($cliente['nome']) ?></h4>
        <div class="btn-group" role="group">
            <a href="cliente_edit.php?id=<?= $cliente['id'] ?>" class="btn btn-info text-white">
                Editar Cliente
            </a>
            <a href="clientes.php" class="btn btn-secondary">
                Lista de Clientes
            </a>
        </div>
    </div>
    
    <hr>
    
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white">
            Detalhes do Cliente
        </div>
        <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($cliente['nome']) ?> (ID: <?= $cliente['id'] ?>)</h5>
            <p class="card-text mb-0">
                <i class="fas fa-phone-alt me-2"></i> Telefone: **<?= htmlspecialchars($cliente['telefone'] ?? 'N/A') ?>**
            </p>
            <p class="card-text">
                <i class="fas fa-envelope me-2"></i> Email: **<?= htmlspecialchars($cliente['email'] ?? 'N/A') ?>**
            </p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header bg-light fw-bold">
            ✍️ Anotações do Histórico
        </div>
        <div class="card-body">
            <div id="anotacoes-list" class="mb-3">
                <?php if (empty($anotacoes)): ?>
                    <p id="no-notes" class="text-muted text-center">Nenhuma anotação registrada.</p>
                <?php else: ?>
                    <?php foreach ($anotacoes as $a): ?>
                        <div class="alert alert-secondary d-flex justify-content-between align-items-start note-item" data-id="<?= $a['id'] ?>">
                            <div>
                                <small class="text-muted d-block note-date"><?= date('d/m/Y H:i', strtotime($a['data'])) ?></small>
                                <span class="note-desc"><?= htmlspecialchars($a['descricao']) ?></span>
                            </div>
                            <button type="button" class="btn-close ms-3 delete-note" data-id="<?= $a['id'] ?>" aria-label="Excluir"></button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <form id="add-note-form" class="d-flex mt-3">
                <input type="hidden" name="cliente_id" value="<?= $cliente['id'] ?>">
                <input type="hidden" name="acao" value="add">
                <textarea id="descricao" name="descricao" class="form-control me-2" rows="2" placeholder="Adicionar nova anotação..." required></textarea>
                <button type="submit" class="btn btn-success btn-sm align-self-end">Salvar</button>
            </form>
            <div id="add-note-message" class="mt-2" style="display:none;"></div>
        </div>
    </div>


    <h5 class="mt-4">🛠️ Ordens de Serviço, Orçamentos e Pendências (<?= count($ordens_servico) ?>)</h5>

    <div class="table-responsive">
        <table class="table table-hover table-striped">
            <thead class="table-dark">
                <tr>
                    <th>OS ID</th>
                    <th>Data</th>
                    <th>Serviço Solicitado</th>
                    <th>Status Financeiro/OS</th>
                    <th>Valor Total</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ordens_servico)): ?>
                    <tr>
                        <td colspan="6" class="text-center">Nenhuma Ordem de Serviço encontrada para este cliente.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ordens_servico as $os): ?>
                        <?php 
                        $valor_total = $os['valor_total'] ?? 0;
                        $status_map = map_status($os['status'], $os['status_pagamento'] ?? 'PENDENTE', $valor_total);
                        ?>
                        <tr>
                            <td><?= $os['id'] ?></td>
                            <td><?= date('d/m/Y', strtotime($os['data_criacao'])) ?></td>
                            <td><?= htmlspecialchars(substr($os['servico'], 0, 50)) . (strlen($os['servico']) > 50 ? '...' : '') ?></td>
                            <td><span class="badge <?= $status_map['class'] ?>"><?= $status_map['status'] ?></span></td>
                            <td>R$ <?= number_format($valor_total, 2, ',', '.') ?></td>
                            <td>
                                <a href="os_edit.php?id=<?= $os['id'] ?>" class="btn btn-sm btn-primary">
                                    Ver OS
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('add-note-form');
    const noteList = document.getElementById('anotacoes-list');
    const noNotes = document.getElementById('no-notes');
    const noteDesc = document.getElementById('descricao');
    const messageDiv = document.getElementById('add-note-message');

    // Função para adicionar anotação via AJAX
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const apiHandlerUrl = 'cliente_historico.php'; 

        fetch(apiHandlerUrl, {
            method: 'POST',
            body: new URLSearchParams(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'ok') {
                const newNoteHtml = `
                    <div class="alert alert-secondary d-flex justify-content-between align-items-start note-item" data-id="${data.id}">
                        <div>
                            <small class="text-muted d-block note-date">${data.data}</small>
                            <span class="note-desc">${noteDesc.value}</span>
                        </div>
                        <button type="button" class="btn-close ms-3 delete-note" data-id="${data.id}" aria-label="Excluir"></button>
                    </div>
                `;

                noteList.insertAdjacentHTML('afterbegin', newNoteHtml);
                
                if (noNotes) {
                    noNotes.style.display = 'none';
                }

                noteDesc.value = '';
                
                messageDiv.className = 'alert alert-success mt-2';
                messageDiv.textContent = 'Anotação salva com sucesso!';
            } else {
                messageDiv.className = 'alert alert-danger mt-2';
                messageDiv.textContent = 'Erro ao salvar anotação.';
            }
            messageDiv.style.display = 'block';
            setTimeout(() => { messageDiv.style.display = 'none'; }, 3000);
        })
        .catch(error => {
            console.error('Erro de rede:', error);
            messageDiv.className = 'alert alert-danger mt-2';
            messageDiv.textContent = 'Erro de comunicação com o servidor.';
            messageDiv.style.display = 'block';
            setTimeout(() => { messageDiv.style.display = 'none'; }, 3000);
        });
    });

    // Função para excluir anotação via AJAX
    noteList.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-note')) {
            const noteId = e.target.getAttribute('data-id');
            if (confirm("Tem certeza que deseja excluir esta anotação?")) {
                
                const apiHandlerUrl = 'cliente_historico.php'; 

                fetch(apiHandlerUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `acao=delete&id=${noteId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'ok') {
                        const noteItem = document.querySelector(`.note-item[data-id="${noteId}"]`);
                        if (noteItem) {
                            noteItem.remove();
                        }
                        
                        // Verificar se a lista ficou vazia
                        if (noteList.children.length === 0) {
                             if (noNotes) {
                                noNotes.style.display = 'block';
                            } else {
                                noteList.innerHTML = '<p id="no-notes" class="text-muted text-center">Nenhuma anotação registrada.</p>';
                            }
                        }

                        messageDiv.className = 'alert alert-success mt-2';
                        messageDiv.textContent = 'Anotação excluída.';
                    } else {
                        messageDiv.className = 'alert alert-danger mt-2';
                        messageDiv.textContent = 'Erro ao excluir anotação.';
                    }
                    messageDiv.style.display = 'block';
                    setTimeout(() => { messageDiv.style.display = 'none'; }, 3000);
                })
                .catch(error => {
                    console.error('Erro de rede:', error);
                    alert('Erro de comunicação ao tentar excluir a anotação.');
                });
            }
        }
    });
});
</script>

<?php include "layout_footer.php"; ?>