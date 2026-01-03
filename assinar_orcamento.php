<?php
require "config.php";
if (session_status()===PHP_SESSION_NONE) session_start();
$id = (int)($_GET['id'] ?? 0);

$o = $conn->prepare("SELECT o.*, c.nome cliente FROM orcamentos o LEFT JOIN clientes c ON c.id=o.cliente_id WHERE o.id=?");
$o->bind_param("i",$id);
$o->execute();
$orc = $o->get_result()->fetch_assoc();
if(!$orc){ die("Orçamento não encontrado"); }

if($_SERVER['REQUEST_METHOD']==='POST'){
  $nome = trim($_POST['nome'] ?? '');
  $doc  = trim($_POST['doc'] ?? '');
  $img  = $_POST['img'] ?? '';

  if($nome && $img){
    @mkdir('uploads/assinaturas',0777,true);
    $data = explode(',', $img, 2)[1] ?? '';
    $bin = base64_decode($data);
    $file = 'uploads/assinaturas/sig_orc_'.$id.'_'.time().'.png';
    file_put_contents($file, $bin);

    $st = $conn->prepare("UPDATE orcamentos
      SET assinatura_file=?, assinatura_quando=NOW(), assinatura_nome=?, assinatura_doc=?, status='Assinado'
      WHERE id=?");
    $st->bind_param("sssi",$file,$nome,$doc,$id);
    $st->execute();

    $_SESSION['msg']="✅ Assinado com sucesso!";
    header("Location: orcamento_print.php?id=".$id);
    exit;
  }
}

include "layout_header.php";
?>
<h3>🖊️ Assinar Orçamento #<?= $orc['id'] ?></h3>
<p><b>Cliente:</b> <?= htmlspecialchars($orc['cliente']) ?></p>

<canvas id="pad" width="360" height="160" style="border:1px solid #ccc;border-radius:6px"></canvas><br>
<button class="btn btn-sm btn-secondary" onclick="limpar()">Limpar</button>
<hr>
<form method="post" onsubmit="return enviar()">
  <input type="hidden" name="img" id="img">
  <div class="row">
    <div class="col-md-5">
      <label>Nome do assinante</label>
      <input name="nome" class="form-control" required>
    </div>
    <div class="col-md-4">
      <label>Documento (CPF/CNPJ)</label>
      <input name="doc" class="form-control">
    </div>
    <div class="col-md-3">
      <label>&nbsp;</label><br>
      <button class="btn btn-success">Salvar Assinatura</button>
    </div>
  </div>
</form>

<script>
const c = document.getElementById('pad');
const ctx = c.getContext('2d');
ctx.lineWidth = 2;
let d=false, lx=0, ly=0;

c.addEventListener('mousedown', e=>{ d=true; [lx,ly] = pos(e); });
c.addEventListener('mousemove', e=>{
  if(!d) return; const [x,y]=pos(e);
  ctx.beginPath(); ctx.moveTo(lx,ly); ctx.lineTo(x,y); ctx.stroke(); [lx,ly]=[x,y];
});
c.addEventListener('mouseup', ()=> d=false);
c.addEventListener('mouseout', ()=> d=false);

function pos(e){ const r=c.getBoundingClientRect(); return [e.clientX-r.left, e.clientY-r.top]; }
function limpar(){ ctx.clearRect(0,0,c.width,c.height); }
function enviar(){
  const url = c.toDataURL('image/png');
  document.getElementById('img').value = url;
  return true;
}
</script>

<?php include "layout_footer.php"; ?>