<!-- Modal Nova OS -->
<div class="modal fade" id="modalNova" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="formNova">
        <div class="modal-header bg-success text-white"><h5 class="modal-title">Nova Ordem de Serviço</h5>
          <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 mb-2">
              <label>Cliente</label>
              <input type="text" id="clienteBusca" name="cliente" class="form-control" placeholder="Buscar cliente..." required autocomplete="off">
              <div id="resultadoBusca" class="list-group position-absolute w-100"></div>
            </div>
            <div class="col-md-3 mb-2"><label>Contato</label><input id="contatoCliente" name="contato" class="form-control"></div>
            <div class="col-md-3 mb-2"><label>Endereço</label><input id="enderecoCliente" name="endereco" class="form-control"></div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-2"><label>Tipo Serviço</label><input name="tipo_servico" class="form-control"></div>
            <div class="col-md-4 mb-2"><label>Serviço</label><input name="servico" class="form-control"></div>
            <div class="col-md-4 mb-2"><label>Equipamento</label><input name="equipamento" class="form-control"></div>
          </div>
          <div class="mb-2"><label>Problema relatado</label><textarea name="problema" class="form-control" rows="2"></textarea></div>
          <div class="row">
            <div class="col-md-4 mb-2"><label>Data</label><input type="date" id="novaData" name="data" class="form-control" required></div>
            <div class="col-md-4 mb-2"><label>Hora</label><input type="time" name="hora" class="form-control"></div>
            <div class="col-md-4 mb-2"><label>Técnico</label><input name="tecnico" class="form-control"></div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-2"><label>Status</label>
              <select name="status" class="form-select"><option>Em aberto</option><option>Em andamento</option><option>Concluída</option><option>Cancelada</option></select></div>
            <div class="col-md-4 mb-2"><label>Valor (R$)</label><input type="number" step="0.01" name="valor" class="form-control"></div>
            <div class="col-md-4 mb-2"><label>Pagamento</label>
              <select name="pagamento_status" class="form-select"><option>Pendente</option><option>Pago</option><option>Em atraso</option></select></div>
          </div>
          <div class="mb-2"><label>Observações</label><textarea name="observacoes" class="form-control"></textarea></div>
        </div>
        <div class="modal-footer"><button class="btn btn-success" type="submit">Salvar</button><button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button></div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Detalhes -->
<div class="modal fade" id="modalDetalhes" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white"><h5 class="modal-title">Detalhes da OS</h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div><b>Cliente:</b> <span id="mCliente"></span></div>
        <div><b>Contato:</b> <span id="mContato"></span></div>
        <div><b>Endereço:</b> <span id="mEndereco"></span></div><hr>
        <div><b>Tipo:</b> <span id="mTipo"></span> | <b>Equipamento:</b> <span id="mEquipamento"></span> | <b>Técnico:</b> <span id="mTecnico"></span></div>
        <div><b>Problema:</b> <span id="mProblema"></span></div><hr>
        <div><b>Status:</b> <span id="mStatus" class="badge bg-info"></span></div>
        <div><b>Valor:</b> R$ <span id="mValor"></span> | <b>Pagamento:</b> <span id="mPagamento"></span></div>
        <div><b>Data/Hora:</b> <span id="mDataHora"></span></div>
        <div><b>Observações:</b> <span id="mObs"></span></div>
      </div>
      <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button></div>
    </div>
  </div>
</div>