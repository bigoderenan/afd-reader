<?php $pages = max(1, (int)ceil($total / $perPage)); ?>
<section class="data-panel">
    <div class="section-title">Linha a Linha do Arquivo AFD</div>
    <div class="filter-box">
        <form method="get" action="<?= url('/linha-a-linha') ?>" class="row g-2 align-items-end">
            <div class="col-md-4"><label class="form-label">Busca por NSR, PIS/CPF, nome ou conteúdo</label><input class="form-control afd-input" name="q" value="<?= e($filters['q']) ?>"></div>
            <div class="col-md-3"><label class="form-label">Tipo de registro</label><select class="form-select afd-input" name="tipo">
                <option value="">Todos</option>
                <?php foreach (['cabecalho','evento_empresa','evento_cadastro','alteracao_horario','marcacao','operacional','generico'] as $tipo): ?>
                    <option value="<?= e($tipo) ?>" <?= $filters['tipo']===$tipo?'selected':'' ?>><?= e($tipo) ?></option>
                <?php endforeach; ?>
            </select></div>
            <div class="col-md-3"><label class="form-label">Data</label><input class="form-control afd-input" type="date" name="data" value="<?= e($filters['data']) ?>"></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Filtrar</button></div>
        </form>
    </div>
    <table class="afd-table line-table">
        <thead><tr><th>Linha</th><th>NSR</th><th>Tipo de Registro</th><th>Data</th><th>Hora</th><th>PIS/CPF</th><th>Nome</th><th>Descrição do Evento</th><th>Conteúdo Original</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($lines as $line): ?>
            <tr class="<?= ($line['status'] ?? '') === 'erro' ? 'row-error' : '' ?>">
                <td><?= e($line['linha'] ?? '--') ?></td>
                <td><?= e($line['nsr'] ?? '--') ?></td>
                <td><?= e($line['tipoRegistro'] ?? '--') ?></td>
                <td><?= e($line['data'] ?? '--') ?></td>
                <td><?= e($line['hora'] ?? '--') ?></td>
                <td><?= e($line['pisCpf'] ?? '--') ?></td>
                <td><?= e($line['nome'] ?? '--') ?></td>
                <td><?= e($line['descricao'] ?? '--') ?></td>
                <td><button class="btn btn-sm btn-outline-light" data-copy="<?= e($line['conteudoOriginal'] ?? '') ?>">Ver</button></td>
                <td><?= e($line['status'] ?? 'ok') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="pagination-box">
        <span>Total: <?= e($total) ?> linhas</span>
        <?php if ($page > 1): ?><a href="<?= url('/linha-a-linha?' . http_build_query($filters + ['page' => $page - 1])) ?>">Anterior</a><?php endif; ?>
        <span>Página <?= e($page) ?> de <?= e($pages) ?></span>
        <?php if ($page < $pages): ?><a href="<?= url('/linha-a-linha?' . http_build_query($filters + ['page' => $page + 1])) ?>">Próxima</a><?php endif; ?>
    </div>
</section>
