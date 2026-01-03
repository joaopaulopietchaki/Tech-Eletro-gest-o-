/* ==============================
   SISTEMA DE PRODUTOS (MODAL)
   - mantém selecionados ao buscar
   - thumbnails corrigidos
   - responsivo
============================== */

// Armazena IDs selecionados mesmo ao fazer novas buscas
let produtosSelecionados = new Set();
let prodTimer = null;

/* --------------------------
   ABRIR MODAL
--------------------------- */
$("#abrirProdutos").on("click", function () {
    $("#modalProdutos").modal("show");
    $("#prodBusca").val("");

    // Carrega todos
    buscarProdutos("");
});

/* --------------------------
   BUSCA EM TEMPO REAL
--------------------------- */
$("#prodBusca").on("input", function () {
    const termo = $(this).val().trim();
    clearTimeout(prodTimer);

    prodTimer = setTimeout(() => {
        buscarProdutos(termo);
    }, 220);
});

/* --------------------------
   REQUISIÇÃO AJAX
--------------------------- */
function buscarProdutos(q) {
    $.ajax({
        url: "produtos_search.php",
        data: { q: q },
        dataType: "json",
        success: function (data) {
            if (!Array.isArray(data)) data = [];
            renderProdutosLista(data);
        },
        error: function () {
            $("#produtosLista").html(`
                <tr>
                    <td colspan="5" class="text-danger text-center py-3">
                        Erro ao buscar produtos.
                    </td>
                </tr>
            `);
        }
    });
}

/* --------------------------
   MONTAR LISTA DE PRODUTOS
--------------------------- */
function renderProdutosLista(lista) {
    let tbody = $("#produtosLista");
    tbody.empty();

    if (lista.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="5" class="text-center text-muted py-3">
                    Nenhum produto encontrado.
                </td>
            </tr>
        `);
        return;
    }

    lista.forEach(p => {

        let id = String(p.id);
        let precoVenda = p.preco_venda ?? p.preco ?? 0;
        let precoCusto = p.preco_custo ?? p.custo ?? 0;

        // Checkbox
        let checkbox = $("<input>", {
            type: "checkbox",
            class: "form-check checkProduto",
            "data-id": id,
            "data-nome": p.nome,
            "data-preco": precoVenda,
            "data-custo": precoCusto,
            "data-imagem": p.imagem,
            "data-unidade": p.unidade ?? "un"
        });

        // Aplicar marcação automática
        if (produtosSelecionados.has(id)) {
            checkbox.prop("checked", true);
        }

        // Atualiza Set ao marcar ou desmarcar
        checkbox.on("change", function () {
            if (this.checked) {
                produtosSelecionados.add(id);
            } else {
                produtosSelecionados.delete(id);
            }
        });

        // Monta linha
        let row = $("<tr>");
        row.append($("<td>").append(checkbox));

        row.append(`
            <td>
                <img src="${p.imagem ? "uploads/produtos/" + p.imagem : "https://via.placeholder.com/52?text=No"}" 
                     class="produto-thumb"
                     onerror="this.src='https://via.placeholder.com/52?text=No'">
            </td>
        `);

        row.append(`<td>${p.nome}</td>`);
        row.append(`<td>R$ ${precoVenda.toFixed(2).replace(".", ",")}</td>`);
        row.append(`<td>R$ ${precoCusto.toFixed(2).replace(".", ",")}</td>`);

        tbody.append(row);
    });

    $("#checkAllProds").prop("checked", false);
}

/* --------------------------
   MARCAR / DESMARCAR TODOS
--------------------------- */
$(document).on("change", "#checkAllProds", function () {
    const marcar = $(this).prop("checked");

    $(".checkProduto").each(function () {
        $(this).prop("checked", marcar);

        let id = String($(this).data("id"));
        if (marcar) produtosSelecionados.add(id);
        else produtosSelecionados.delete(id);
    });
});

/* --------------------------
   ADICIONAR SELECIONADOS
--------------------------- */
$("#adicionarSelecionados").on("click", function () {

    $(".checkProduto:checked").each(function () {
        adicionarLinhaItem({
            id: $(this).data("id"),
            nome: $(this).data("nome"),
            unidade: $(this).data("unidade"),
            preco_venda: $(this).data("preco"),
            preco_custo: $(this).data("custo"),
            imagem: $(this).data("imagem")
        });
    });

    $("#modalProdutos").modal("hide");
});
