<h2 class="header-section">Dados do Cabeçalho do Arquivo</h2>
<?php if (empty($empresa)): ?>
    <div class="alert alert-warning">Nenhum dado de empresa encontrado.</div>
<?php else: ?>
    <table class="table table-dark table-striped table-sm">
        <tbody>
            <tr>
                <th>Tipo do Empregador</th>
                <td><?php
                    $tipo = $empresa['tipoEmpregador'] ?? '';
                    echo $tipo === '1' ? 'CNPJ' : ($tipo === '2' ? 'CPF' : $tipo);
                ?></td>
            </tr>
            <tr>
                <th>CNPJ/CPF do empregador</th>
                <td><?php echo htmlspecialchars($empresa['cnpj_cpf'] ?? ''); ?></td>
            </tr>
            <tr>
                <th>CNO/CAEPF</th>
                <td><?php echo htmlspecialchars($empresa['cno_caepf'] ?? ''); ?></td>
            </tr>
            <tr>
                <th>Razão social / nome do empregador</th>
                <td><?php echo htmlspecialchars($empresa['nome'] ?? ''); ?></td>
            </tr>
            <tr>
                <th>Serial / Processo / REP</th>
                <td><?php echo htmlspecialchars($empresa['serial'] ?? ''); ?></td>
            </tr>
            <tr>
                <th>Data Inicial dos registros</th>
                <td><?php echo htmlspecialchars($empresa['dataInicio'] ?? ''); ?></td>
            </tr>
            <tr>
                <th>Data Final dos registros</th>
                <td><?php echo htmlspecialchars($empresa['dataFim'] ?? ''); ?></td>
            </tr>
            <tr>
                <th>Data/Hora de geração</th>
                <td><?php echo htmlspecialchars($empresa['dataHoraGeracao'] ?? ''); ?></td>
            </tr>
            <tr>
                <th>Layout da geração do arquivo</th>
                <td><?php echo htmlspecialchars($empresa['layout'] ?? ''); ?></td>
            </tr>
            <tr>
                <th>Tipo de identificador do fabricante</th>
                <td><?php
                    $tf = $empresa['tipoFabricante'] ?? '';
                    echo $tf === '1' ? 'CNPJ' : ($tf === '2' ? 'CPF' : $tf);
                ?></td>
            </tr>
            <tr>
                <th>CNPJ/CPF do fabricante</th>
                <td><?php echo htmlspecialchars($empresa['cnpjFabricante'] ?? ''); ?></td>
            </tr>
            <tr>
                <th>Modelo</th>
                <td><?php echo htmlspecialchars($empresa['modelo'] ?? ''); ?></td>
            </tr>
        </tbody>
    </table>
<?php endif; ?>