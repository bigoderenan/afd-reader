<h2 class="header-section">Linha a Linha</h2>

<form method="get" action="index.php" class="row g-3 mb-3">
    <input type="hidden" name="page" value="linhas">
    <div class="col-md-3">
        <label for="nsr" class="form-label">Buscar NSR</label>
        <input type="text" class="form-control" name="nsr" id="nsr" value="<?php echo htmlspecialchars($queryNsr ?? ''); ?>">
    </div>
    <div class="col-md-3">
        <label for="tipo" class="form-label">Tipo de registro</label>
        <input type="text" class="form-control" name="tipo" id="tipo" value="<?php echo htmlspecialchars($queryTipo ?? ''); ?>">
    </div>
    <div class="col-md-3">
        <label for="pis" class="form-label">Buscar PIS</label>
        <input type="text" class="form-control" name="pis" id="pis" value="<?php echo htmlspecialchars($queryPis ?? ''); ?>">
    </div>
    <div class="col-md-3 d-flex align-items-end">
        <button type="submit" class="btn btn-blue me-2">Filtrar</button>
        <a href="index.php?page=linhas" class="btn btn-secondary">Limpar</a>
    </div>
</form>

<div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
    <table class="table table-dark table-striped table-sm">
        <thead>
            <tr>
                <th>Linha</th>
                <th>NSR</th>
                <th>Tipo</th>
                <th>Conteúdo Original</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($linhas)): ?>
            <tr><td colspan="4">Nenhuma linha encontrada.</td></tr>
        <?php else: ?>
            <?php foreach ($linhas as $l): ?>
                <tr>
                    <td><?php echo htmlspecialchars($l['linha']); ?></td>
                    <td><?php echo htmlspecialchars($l['nsr']); ?></td>
                    <td><?php echo htmlspecialchars($l['tipo']); ?></td>
                    <td><code style="white-space: nowrap;"><?php echo htmlspecialchars($l['conteudo']); ?></code></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>