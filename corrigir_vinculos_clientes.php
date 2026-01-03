function corrigirVinculos($conn, $cliente) {
    $nomeCliente = trim($cliente['nome']);
    $idCliente   = intval($cliente['id']);
    $corrigidos  = 0;

    $tabelas = [
        'os' => ['campo_nome' => 'cliente_nome', 'campo_id' => 'cliente_id'],
        'orcamentos' => ['campo_nome' => 'cliente_nome', 'campo_id' => 'cliente_id'],
        'financeiro' => ['campo_nome' => 'cliente_nome', 'campo_id' => 'cliente_id'],
        'garantias' => ['campo_nome' => 'cliente_nome', 'campo_id' => 'cliente_id']
    ];

    foreach ($tabelas as $tabela => $campos) {
        $campoNome = $campos['campo_nome'];
        $campoId   = $campos['campo_id'];

        // Verifica se tabela existe
        $check = $conn->query("SHOW TABLES LIKE '$tabela'");
        if (!$check || $check->num_rows == 0) continue;

        // Verifica colunas existentes
        $colunas = [];
        $cols = $conn->query("SHOW COLUMNS FROM $tabela");
        while ($c = $cols->fetch_assoc()) {
            $colunas[] = $c['Field'];
        }

        if (in_array($campoNome, $colunas)) {
            // Usa comparação case-insensitive e ignora acentos
            $sql = "
                UPDATE $tabela
                SET $campoId = $idCliente
                WHERE ($campoId IS NULL OR $campoId = 0)
                AND LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE($campoNome,
                    'á','a'),'à','a'),'ã','a'),'â','a'),'é','e'),'ê','e'),'í','i'),'ó','o'),'ô','o'),'ú','u'))
                LIKE LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE('$nomeCliente',
                    'á','a'),'à','a'),'ã','a'),'â','a'),'é','e'),'ê','e'),'í','i'),'ó','o'),'ô','o'),'ú','u'))
            ";
            $conn->query($sql);
            $corrigidos += $conn->affected_rows;
        }
    }

    return $corrigidos;
}