<?php if (isset($message) && $message): ?>
    <div class="alert alert-success" role="alert">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<h2 class="header-section">Detalhamento do Arquivo Importado</h2>
<table class="table table-dark table-striped table-sm">
    <tbody>
        <tr>
            <th>Nome do Arquivo</th>
            <td><?php echo htmlspecialchars($summary['nomeArquivo'] ?? ''); ?></td>
        </tr>
        <tr>
            <th>Tamanho do Arquivo</th>
            <td><?php echo number_format(($summary['tamanhoArquivo'] ?? 0) / 1024, 2); ?> KB</td>
        </tr>
        <tr>
            <th>Primeiro NSR</th>
            <td><?php echo htmlspecialchars($summary['primeiroNsr'] ?? ''); ?></td>
        </tr>
        <tr>
            <th>Data Primeiro NSR</th>
            <td><?php echo !empty($summary['dataPrimeiroNsr']) ? date('d/m/Y', strtotime($summary['dataPrimeiroNsr'])) : ''; ?></td>
        </tr>
        <tr>
            <th>Último NSR</th>
            <td><?php echo htmlspecialchars($summary['ultimoNsr'] ?? ''); ?></td>
        </tr>
        <tr>
            <th>Data Último NSR</th>
            <td><?php echo !empty($summary['dataUltimoNsr']) ? date('d/m/Y', strtotime($summary['dataUltimoNsr'])) : ''; ?></td>
        </tr>
        <tr>
            <th>Número de Linhas</th>
            <td><?php echo htmlspecialchars($summary['numeroLinhas'] ?? 0); ?></td>
        </tr>
        <tr>
            <th>Integridade</th>
            <td><?php echo htmlspecialchars($summary['integridade'] ?? ''); ?></td>
        </tr>
        <tr>
            <th>Edições da Empresa</th>
            <td><?php echo htmlspecialchars($summary['edicoesEmpresa'] ?? 0); ?></td>
        </tr>
        <tr>
            <th>Marcações de Ponto</th>
            <td><?php echo htmlspecialchars($summary['marcacoes'] ?? 0); ?></td>
        </tr>
        <tr>
            <th>Alterações de Horário</th>
            <td><?php echo htmlspecialchars($summary['alteracoesHorario'] ?? 0); ?></td>
        </tr>
        <tr>
            <th>Inclusões de Empregado</th>
            <td><?php echo htmlspecialchars($summary['inclusoes'] ?? 0); ?></td>
        </tr>
        <tr>
            <th>Alterações de Empregado</th>
            <td><?php echo htmlspecialchars($summary['alteracoesCad'] ?? 0); ?></td>
        </tr>
        <tr>
            <th>Exclusões de Empregado</th>
            <td><?php echo htmlspecialchars($summary['exclusoes'] ?? 0); ?></td>
        </tr>
        <tr>
            <th>Registros Operacionais</th>
            <td><?php echo htmlspecialchars($summary['registrosOperacionais'] ?? 0); ?></td>
        </tr>
        <tr>
            <th>Data Inicial dos registros</th>
            <td><?php echo htmlspecialchars($summary['dataInicio'] ?? ''); ?></td>
        </tr>
        <tr>
            <th>Data Final dos registros</th>
            <td><?php echo htmlspecialchars($summary['dataFim'] ?? ''); ?></td>
        </tr>
    </tbody>
</table>

<p>
    <a href="index.php?page=empresa" class="btn btn-blue btn-sm">Ver Dados da Empresa</a>
    <a href="index.php?page=usuarios" class="btn btn-green btn-sm">Ver Usuários</a>
    <a href="index.php?page=linhas" class="btn btn-orange btn-sm">Linha a Linha</a>
</p>