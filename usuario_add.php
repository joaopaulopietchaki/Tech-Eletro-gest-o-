<?php
require 'config.php';
if (session_status()===PHP_SESSION_NONE) session_start();

// Apenas admin
$adm = $conn->query("SELECT role FROM usuarios WHERE id=".$_SESSION['user_id'])->fetch_assoc();
if($adm['role'] != 'admin'){ die("❌ Acesso negado"); }

if($_POST){
    $nome  = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $role  = $_POST['role'];

    $sql = $conn->prepare("INSERT INTO usuarios (nome,email,senha,role) VALUES (?,?,?,?)");
    $sql->bind_param("ssss", $nome,$email,$senha,$role);
    $sql->execute();

    header("Location: usuarios.php");
    exit;
}

include 'layout_header.php';
?>

<h3>➕ Novo Usuário</h3>

<form method="post">
<input class="form-control mb-2" name="nome" placeholder="Nome" required>
<input class="form-control mb-2" name="email" type="email" placeholder="Email" required>
<input class="form-control mb-2" name="senha" type="password" placeholder="Senha" required>

<select class="form-control mb-3" name="role">
<option value="user">Usuário</option>
<option value="admin">Administrador</option>
</select>

<button class="btn btn-success">Salvar</button>
</form>

<?php include 'layout_footer.php'; ?>