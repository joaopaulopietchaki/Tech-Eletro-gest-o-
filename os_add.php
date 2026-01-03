<?php
// ===========================================
// OS_ADD.PHP — NOVA OS (CORRIGIDO)
// ===========================================

require "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();

// Proteção de Login
if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include "layout_header.php";
?>

<style>
    .ui-autocomplete { 
        z-index: 99999 !important; 
        max-height: 250px; 
        overflow-y: auto; 
        background: white; 
        border: 1px solid #ddd;
        border-radius: 0 0 8px 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .ui-menu-item div { 
        padding: 10px; 
        border-bottom: 1px solid #f0f0f0; 
        cursor: pointer; 
    }
    .ui-menu-item div:hover { 
        background-color: #f8f9fa; 
        color: #0d6efd; 
    }
</style>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>➕ Nova Ordem de Serviço</h4>
        <a href="os.php" class="btn btn-secondary btn-sm">Voltar</a>
    </div>

    <form action="os_insert.php" method="POST" enctype="multipart/form-data" id="formOS">
        
        <div class="card shadow-sm">
            <div class="card-header bg-light fw-bold">📋 Dados da OS</div>
            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">Cliente (Busca)</label>
                        <input type="text" name="cliente_nome" id="cliente_nome" class="form-control fw-bold" placeholder="Digite para buscar..." autocomplete="off" required>
                        <input type="hidden" name="cliente_id" id="cliente_id">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Endereço</label>
                        <input type="text" name="endereco" id="endereco" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Cidade</label>
                        <input type="text" name="cidade" id="cidade" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="telefone" id="telefone" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Data Agendada</label>
                        <input type="date" name="data_agendada" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Serviço Solicitado</label>
                        <input type="text" name="servico" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Status Inicial</label>
                        <select name="status" class="form-select">
                            <option selected>Agendada</option>
                            <option>Em Execução</option>
                            <option>Concluída</option>
                            <option>Cancelada</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" rows="3" class="form-control"></textarea>
                    </div>

                </div>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-success btn-lg w-100 py-3">💾 CRIAR ORDEM DE SERVIÇO</button>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">


<script>
// ================================================
// AUTOCOMPLETE — CORRIGIDO 100%
// ================================================
$(document).ready(function() {

    $("#cliente_nome").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "clientes_search.php",
                dataType: "json",
                data: { term: request.term },
                success: function(data) {
                    response($.map(data, function(item) {
                        return {
                            label: item.label,      // aparece na lista
                            value: item.nome,       // vai pro input ao selecionar
                            id: item.id,
                            nome: item.nome,
                            endereco: item.endereco,
                            cidade: item.cidade,
                            telefone: item.telefone
                        };
                    }));
                }
            });
        },
        minLength: 2,

        select: function(event, ui) {
            $("#cliente_id").val(ui.item.id);
            $("#cliente_nome").val(ui.item.nome);
            $("#endereco").val(ui.item.endereco);
            $("#cidade").val(ui.item.cidade);
            $("#telefone").val(ui.item.telefone);
            return false;
        },

        focus: function(event, ui) {
            $("#cliente_nome").val(ui.item.nome);
            return false;
        }
    })
    .autocomplete("instance")._renderItem = function(ul, item) {
        return $("<li>")
        .append(`
            <div>
                <strong>${item.nome}</strong><br>
                <small>${item.cidade || "Sem cidade"} — ${item.telefone || ""}</small>
            </div>
        `)
        .appendTo(ul);
    };

});
</script>

<?php include "layout_footer.php"; ?>