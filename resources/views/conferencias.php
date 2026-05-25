<section class="conference-actions">
    <a href="#marcacoes" class="btn btn-success">PESQUISA DE MARCAÇÕES SUSPEITAS</a>
    <a href="#empresa" class="btn btn-success">VERIFICAR EDIÇÕES SUSPEITAS DA EMPRESA</a>
    <a href="#horario" class="btn btn-success">CONFERIR ALTERAÇÕES DE HORÁRIO</a>
</section>
<?php
$renderReport = function(string $id, string $title, array $items) {
?>
<section class="data-panel mt-4" id="<?= e($id) ?>">
    <div class="section-title"><?= e($title) ?></div>
    <table class="afd-table users-table">
        <thead><tr><th>Tipo</th><th>Data</th><th>Hora</th><th>NSR</th><th>Descrição</th><th>Gravidade</th><th>Ação</th></tr></thead>
        <tbody>
        <?php if (empty($items)): ?><tr><td colspan="7">Nenhuma ocorrência encontrada.</td></tr><?php endif; ?>
        <?php foreach ($items as $item): ?>
            <tr><td><?= e($item['tipo']) ?></td><td><?= e($item['data'] ?? '--') ?></td><td><?= e($item['hora'] ?? '--') ?></td><td><?= e($item['nsr'] ?? '--') ?></td><td><?= e($item['descricao'] ?? '--') ?></td><td><span class="severity <?= strtolower(e($item['gravidade'])) ?>"><?= e($item['gravidade']) ?></span></td><td><?= e($item['acao']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php };
$renderReport('marcacoes', 'Pesquisa de Marcações Suspeitas', $reports['marcacoes'] ?? []);
$renderReport('empresa', 'Edições Suspeitas da Empresa', $reports['empresa'] ?? []);
$renderReport('horario', 'Alterações de Horário', $reports['horario'] ?? []);
?>
