<h2 class="header-section">Nomes Ativos no Relógio</h2>
<?php if (empty($ativos)): ?>
    <div class="alert alert-warning">Nenhum usuário ativo encontrado.</div>
<?php else: ?>
    <table class="table table-dark table-striped table-sm align-middle">
        <thead>
            <tr>
                <th style="width:45px;">Nro</th>
                <th>NOME</th>
                <th>PIS</th>
                <th>Marcações</th>
                <th>Primeira</th>
                <th>Última</th>
                <th>Carga Horária</th>
                <th>Espelho</th>
                <th>Cadastro</th>
            </tr>
        </thead>
        <tbody>
        <?php $i = 1; foreach ($ativos as $u): ?>
            <tr>
                <td><?php echo $i++; ?>.</td>
                <td><a class="text-blue text-decoration-none" href="index.php?page=espelho&pis=<?php echo urlencode($u['pis']); ?>"><?php echo htmlspecialchars($u['nome']); ?></a></td>
                <td><?php echo htmlspecialchars($u['pis']); ?></td>
                <td><?php echo htmlspecialchars($u['marcacoes']); ?></td>
                <td><?php echo htmlspecialchars($u['primeira']); ?></td>
                <td><?php echo htmlspecialchars($u['ultima']); ?></td>
                <td><?php echo htmlspecialchars($u['cargaHoraria']); ?></td>
                <td><a class="text-blue text-decoration-none" href="index.php?page=espelho&pis=<?php echo urlencode($u['pis']); ?>">Espelho</a></td>
                <td><a class="text-blue text-decoration-none" href="index.php?page=cadastro&pis=<?php echo urlencode($u['pis']); ?>">Cadastro</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h2 class="header-section mt-4">Nomes Excluídos do Relógio</h2>
<?php if (empty($excluidos)): ?>
    <div class="alert alert-warning">Nenhum usuário excluído encontrado.</div>
<?php else: ?>
    <table class="table table-dark table-striped table-sm align-middle">
        <thead>
            <tr>
                <th style="width:45px;">Nro</th>
                <th>NOME</th>
                <th>PIS</th>
                <th>Marcações</th>
                <th>Primeira</th>
                <th>Última</th>
                <th>Carga Horária</th>
                <th>Espelho</th>
                <th>Cadastro</th>
            </tr>
        </thead>
        <tbody>
        <?php $i = 1; foreach ($excluidos as $u): ?>
            <tr>
                <td><?php echo $i++; ?>.</td>
                <td><a class="text-blue text-decoration-none" href="index.php?page=espelho&pis=<?php echo urlencode($u['pis']); ?>"><?php echo htmlspecialchars($u['nome']); ?></a></td>
                <td><?php echo htmlspecialchars($u['pis']); ?></td>
                <td><?php echo htmlspecialchars($u['marcacoes']); ?></td>
                <td><?php echo htmlspecialchars($u['primeira']); ?></td>
                <td><?php echo htmlspecialchars($u['ultima']); ?></td>
                <td><?php echo htmlspecialchars($u['cargaHoraria']); ?></td>
                <td><a class="text-blue text-decoration-none" href="index.php?page=espelho&pis=<?php echo urlencode($u['pis']); ?>">Espelho</a></td>
                <td><a class="text-blue text-decoration-none" href="index.php?page=cadastro&pis=<?php echo urlencode($u['pis']); ?>">Cadastro</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<p class="fw-bold mt-2">*Horário Padrão. Clique em Espelho para alterar</p>
