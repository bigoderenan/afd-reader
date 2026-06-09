<?php
$mesesExport = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
];

$mesAtual = (int)($exportMes ?? date('m'));
$anoAtual = (int)($exportAno ?? date('Y'));
$exportColumns = $exportColumns ?? [
    'nome' => 'COLABORADOR',
    'adiantamento' => 'ADIANTAMENTO',
    'periculosidade' => 'PERICULOSIDADE',
    'gratificacao' => 'GRATIFICAÇÃO',
    'extra50' => 'H. EXTRA 50%',
    'extra100' => 'H. EXTRA 100%',
    'faltaHoras' => 'FALTA HORAS',
    'meioPeriodo' => 'MEIO PERIODO',
    'datasMeioPeriodo' => 'DATA MEIO PERIODO',
    'faltas' => 'FALTAS',
    'datasFalta' => 'DATA DA FALTA',
    'descTransporte' => 'DESC. V. TRANSP.',
    'planoSaude' => 'PLANO DE SAUDE',
    'valeAlimentacao' => 'V. ALIMENTAÇÃO',
    'observacoes' => 'OBSERVAÇÕES',
];
?>

<form method="post" action="index.php?page=exportar_folha" id="exportForm">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h2 class="header-section mb-1">Nomes Ativos no Relógio</h2>
            <small class="text-secondary">Marque os colaboradores, o período e as colunas que deverão sair na planilha.</small>
        </div>

        <div class="card bg-dark text-light border-secondary" style="min-width: 340px; max-width: 100%;">
            <div class="card-header bg-success text-white fw-bold py-2">Filtro de exportação</div>
            <div class="card-body py-3">
                <div class="row g-2 align-items-end">
                    <div class="col-7">
                        <label class="form-label mb-1">Mês</label>
                        <select name="mes" class="form-select form-select-sm bg-dark text-light border-secondary">
                            <?php foreach ($mesesExport as $num => $label): ?>
                                <option value="<?php echo $num; ?>" <?php echo $num === $mesAtual ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-5">
                        <label class="form-label mb-1">Ano</label>
                        <input type="number" name="ano" value="<?php echo htmlspecialchars((string)$anoAtual); ?>" min="2000" max="2100" class="form-control form-control-sm bg-dark text-light border-secondary">
                    </div>
                    <div class="col-6">
                        <label class="form-label mb-1">Data inicial <small class="text-secondary">opcional</small></label>
                        <input type="date" name="data_inicio" class="form-control form-control-sm bg-dark text-light border-secondary">
                    </div>
                    <div class="col-6">
                        <label class="form-label mb-1">Data final <small class="text-secondary">opcional</small></label>
                        <input type="date" name="data_fim" class="form-control form-control-sm bg-dark text-light border-secondary">
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label mb-1">Colaboradores</label>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-light btn-sm" onclick="setExportUsers('ativos')">Ativos</button>
                        <button type="button" class="btn btn-outline-light btn-sm" onclick="setExportUsers('todos')">Todos</button>
                        <button type="button" class="btn btn-outline-light btn-sm" onclick="setExportUsers('limpar')">Limpar</button>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="button" class="btn btn-outline-light btn-sm" data-bs-toggle="collapse" data-bs-target="#exportColumnsBox">Selecionar colunas</button>
                    <div class="collapse mt-2" id="exportColumnsBox">
                        <div class="border border-secondary rounded p-2" style="max-height: 190px; overflow:auto;">
                            <?php foreach ($exportColumns as $key => $label): ?>
                                <?php if ($key === 'nome'): ?>
                                    <input type="hidden" name="columns[]" value="nome">
                                    <label class="d-block small mb-1 text-secondary">
                                        <input type="checkbox" checked disabled> <?php echo htmlspecialchars($label); ?> <span class="text-secondary">(obrigatório)</span>
                                    </label>
                                <?php else: ?>
                                    <label class="d-block small mb-1">
                                        <input class="export-column" type="checkbox" name="columns[]" value="<?php echo htmlspecialchars($key); ?>" checked>
                                        <?php echo htmlspecialchars($label); ?>
                                    </label>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-outline-light btn-sm" onclick="setExportColumns(true)">Marcar colunas</button>
                            <button type="button" class="btn btn-outline-light btn-sm" onclick="setExportColumns(false)">Limpar colunas</button>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-green btn-sm w-100 mt-3">Exportar Selecionados</button>
            </div>
        </div>
    </div>

    <?php if (empty($ativos)): ?>
        <div class="alert alert-warning">Nenhum usuário ativo encontrado.</div>
    <?php else: ?>
        <table class="table table-dark table-striped table-sm align-middle">
            <thead>
                <tr>
                    <th style="width:45px;">Sel.</th>
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
                    <td>
                        <input class="export-pis export-pis-ativo" type="checkbox" name="pis[]" value="<?php echo htmlspecialchars($u['pis']); ?>" checked>
                    </td>
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
                    <th style="width:45px;">Sel.</th>
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
                    <td>
                        <input class="export-pis export-pis-excluido" type="checkbox" name="pis[]" value="<?php echo htmlspecialchars($u['pis']); ?>">
                    </td>
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
</form>

<p class="fw-bold mt-2">*Horário Padrão. Clique em Espelho para alterar</p>

<script>
function setExportUsers(mode) {
    const all = document.querySelectorAll('.export-pis');
    all.forEach((item) => {
        if (mode === 'limpar') {
            item.checked = false;
        } else if (mode === 'todos') {
            item.checked = true;
        } else if (mode === 'ativos') {
            item.checked = item.classList.contains('export-pis-ativo');
        }
    });
}

function setExportColumns(checked) {
    document.querySelectorAll('.export-column').forEach((item) => {
        item.checked = checked;
    });
}

document.getElementById('exportForm')?.addEventListener('submit', function (event) {
    if (!document.querySelector('.export-pis:checked')) {
        event.preventDefault();
        alert('Selecione pelo menos um colaborador para exportar.');
    }
});
</script>
