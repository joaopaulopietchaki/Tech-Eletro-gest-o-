<?php  
require "config.php";  

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$erro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $unidade = trim($_POST['unidade'] ?? '');

    // Converte moeda BR → número
    function brToFloat($v) {
        if (!$v) return 0;
        return floatval(str_replace(',', '.', str_replace('.', '', $v)));
    }

    $preco_venda = brToFloat($_POST['preco'] ?? '0');
    $preco_custo = brToFloat($_POST['custo'] ?? '0');

    $descricao = trim($_POST['descricao'] ?? '');
    $imagem = null;

    if ($nome === '') {
        $erro = "O nome do produto é obrigatório.";
    } else {

        // Upload
        if (!empty($_FILES['imagem']['name'])) {

            $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));

            // Apenas imagens permitidas
            $permitidos = ['jpg','jpeg','png','gif','webp'];
            if (!in_array($ext, $permitidos)) {
                $erro = "Formato de imagem inválido. Use JPG, PNG, GIF ou WEBP.";
            } else {

                $nomeArq = 'produto_' . time() . '.' . $ext;
                $destino = __DIR__ . "/uploads/produtos/" . $nomeArq;

                if (move_uploaded_file($_FILES['imagem']['tmp_name'], $destino)) {
                    $imagem = $nomeArq;
                }
            }
        }

        if ($erro === "") {

            // Inserção correta no banco
            $sql = "INSERT INTO produtos (nome, unidade, preco_venda, preco_custo, descricao, imagem) 
                    VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssddss",
                $nome,
                $unidade,
                $preco_venda,
                $preco_custo,
                $descricao,
                $imagem
            );

            if ($stmt->execute()) {
                $_SESSION['msg'] = "Produto cadastrado com sucesso!";
                header("Location: produtos.php");
                exit;
            } else {
                $erro = "Erro ao cadastrar: " . $stmt->error;
            }
        }
    }
}

include "layout_header.php";
?>  

<div class="container-fluid">  
    <h3>🆕 Novo Produto</h3>  

    <?php if ($erro): ?>  
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>  
    <?php endif; ?>  

    <form method="post" enctype="multipart/form-data">  

        <div class="row mb-3">  
            <div class="col-md-6">  
                <label>Nome do Produto</label>  
                <input type="text" name="nome" class="form-control" required placeholder="Ex: Câmera WiFi 1080p">  
            </div>  

            <div class="col-md-3">  
                <label>Unidade</label>  
                <input type="text" name="unidade" class="form-control" placeholder="Ex: un, pç, cx">  
            </div>  

            <div class="col-md-3">  
                <label>Preço de Venda (R$)</label>  
                <input type="text" name="preco" id="preco" class="form-control moeda" value="0,00" required>  
            </div>  
        </div>  

        <div class="row mb-3">  
            <div class="col-md-6">  
                <label>Custo do Produto (R$)</label>  
                <input type="text" name="custo" id="custo" class="form-control moeda" value="0,00">  
            </div>  

            <div class="col-md-6">  
                <label>Imagem do Produto</label>  
                <input type="file" name="imagem" class="form-control">  
            </div>  
        </div>  

        <div class="mb-3">  
            <label>Descrição</label>  
            <textarea name="descricao" class="form-control" rows="3" placeholder="Detalhes do produto..."></textarea>  
        </div>  

        <button type="submit" class="btn btn-success">💾 Salvar Produto</button>  
        <a href="produtos.php" class="btn btn-secondary">⬅ Voltar</a>  
    </form>  
</div>  

<style>  
form label { font-weight: 600; }  
input.moeda { text-align: right; }  
</style>  

<script>
// Máscara de moeda BR
document.querySelectorAll('.moeda').forEach(campo => {
    campo.addEventListener('input', () => {
        let v = campo.value.replace(/\D/g, "");
        v = (parseInt(v, 10) / 100).toFixed(2) + "";
        v = v.replace(".", ",");
        v = v.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        campo.value = v;
    });
});
</script>

<?php include "layout_footer.php"; ?>
