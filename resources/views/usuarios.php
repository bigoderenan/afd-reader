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

$renderUsuarioRows = static function (array $usuarios, bool $grupoAtivo): void {
    $i = 1;
    foreach ($usuarios as $u):
        $statusCodigo = (string)($u['statusCodigo'] ?? 'sem_registro');
        $statusLabel = (string)($u['statusLabel'] ?? 'Sem registro no período');
        $statusClass = (string)($u['statusClass'] ?? 'status-warning');
        $pis = (string)($u['pis'] ?? '');
        $checked = $grupoAtivo && $statusCodigo === 'com_registro';
        $disabled = in_array($statusCodigo, ['sem_registro', 'incluido_apos', 'excluido_antes'], true);
        ?>
        <tr class="export-row <?php echo htmlspecialchars($statusClass); ?>"
            data-active="<?php echo $grupoAtivo ? '1' : '0'; ?>"
            data-start="<?php echo htmlspecialchars((string)($u['usuarioInicio'] ?? '')); ?>"
            data-end="<?php echo htmlspecialchars((string)($u['usuarioFim'] ?? '')); ?>"
            data-marks="<?php echo htmlspecialchars((string)($u['markDates'] ?? '')); ?>">
            <td>
                <input class="export-pis <?php echo $grupoAtivo ? 'export-pis-ativo' : 'export-pis-excluido'; ?>"
                       type="checkbox"
                       name="pis_visual[]"
                       data-pis="<?php echo htmlspecialchars($pis); ?>"
                       value="<?php echo htmlspecialchars($pis); ?>"
                       <?php echo $checked ? 'checked' : ''; ?>
                       <?php echo $disabled ? 'disabled' : ''; ?>>
            </td>
            <td><?php echo $i++; ?>.</td>
            <td><a class="text-blue text-decoration-none" href="index.php?page=espelho&pis=<?php echo urlencode($pis); ?>"><?php echo htmlspecialchars((string)($u['nome'] ?? '')); ?></a></td>
            <td><?php echo htmlspecialchars($pis); ?></td>
            <td><?php echo htmlspecialchars((string)($u['marcacoes'] ?? 0)); ?></td>
            <td><?php echo htmlspecialchars((string)($u['primeira'] ?? '-')); ?></td>
            <td><?php echo htmlspecialchars((string)($u['ultima'] ?? '-')); ?></td>
            <td><?php echo htmlspecialchars((string)($u['cargaHoraria'] ?? '')); ?></td>
            <td><span class="period-status <?php echo htmlspecialchars($statusClass); ?>"><?php echo htmlspecialchars($statusLabel); ?></span></td>
            <td><a class="text-blue text-decoration-none" href="index.php?page=espelho&pis=<?php echo urlencode($pis); ?>">Espelho</a></td>
            <td><a class="text-blue text-decoration-none" href="index.php?page=cadastro&pis=<?php echo urlencode($pis); ?>">Cadastro</a></td>
        </tr>
        <?php
    endforeach;
};
?>

<div class="usuarios-page-header mb-3">
    <h2 class="header-section mb-1">Nomes Ativos no Relógio</h2>
    <p class="text-secondary mb-0">Marque os colaboradores na tabela e exporte somente o período, tratamento e colunas selecionadas.</p>
</div>

<form method="post" action="index.php?page=exportar_folha" id="exportForm">
    <div id="exportSelectedPisContainer"></div>
    <div class="export-panel mb-4">
        <div class="export-panel__header">
            <div>
                <div class="export-panel__title">Filtro de exportação</div>
                <div class="export-panel__subtitle">Configure o período, o tratamento dos sem registro e a planilha antes de exportar.</div>
            </div>
            <div class="export-counter">
                <span id="selectedUsersCount">0</span> colaborador(es) selecionado(s)
            </div>
        </div>

        <div class="export-panel__body">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label mb-1">Mês</label>
                    <select id="exportMes" name="mes" class="form-select form-select-sm bg-dark text-light border-secondary">
                        <?php foreach ($mesesExport as $num => $label): ?>
                            <option value="<?php echo $num; ?>" <?php echo $num === $mesAtual ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label mb-1">Ano</label>
                    <input id="exportAno" type="number" name="ano" value="<?php echo htmlspecialchars((string)$anoAtual); ?>" min="2000" max="2100" class="form-control form-control-sm bg-dark text-light border-secondary">
                </div>

                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label mb-1">Data inicial <small class="text-secondary">opcional</small></label>
                    <input id="exportDataInicio" type="date" name="data_inicio" class="form-control form-control-sm bg-dark text-light border-secondary">
                </div>

                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label mb-1">Data final <small class="text-secondary">opcional</small></label>
                    <input id="exportDataFim" type="date" name="data_fim" class="form-control form-control-sm bg-dark text-light border-secondary">
                </div>

                <div class="col-12 col-lg-2">
                    <label class="form-label mb-1">Colaboradores</label>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-light btn-sm" onclick="setExportUsers('ativos')">Ativos</button>
                        <button type="button" class="btn btn-outline-light btn-sm" onclick="setExportUsers('todos')">Todos</button>
                        <button type="button" class="btn btn-outline-light btn-sm" onclick="setExportUsers('limpar')">Limpar</button>
                    </div>
                </div>

                <div class="col-12 col-lg-2">
                    <button type="submit" class="btn btn-green btn-sm w-100 export-main-button">Exportar Selecionados</button>
                </div>
            </div>

            <div class="export-panel__rules mt-3">
                <div>
                    <strong>Funcionários sem registro no período</strong>
                    <small class="text-secondary d-block">A opção padrão evita faltas falsas. Para exportar esses funcionários, escolha uma das opções abaixo e selecione os nomes na tabela.</small>
                </div>
                <div class="export-rules-grid mt-2">
                    <label class="export-rule-option">
                        <input type="radio" name="sem_registro" value="skip" checked>
                        <span>Não exportar <small>recomendado</small></span>
                    </label>
                    <label class="export-rule-option">
                        <input type="radio" name="sem_registro" value="zero">
                        <span>Exportar zerado <small>com observação</small></span>
                    </label>
                    <label class="export-rule-option">
                        <input type="radio" name="sem_registro" value="falta">
                        <span>Considerar falta integral <small>use com cuidado</small></span>
                    </label>
                </div>
            </div>

            <div class="export-panel__columns mt-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <strong>Colunas da planilha</strong>
                        <small class="text-secondary d-block">COLABORADOR é obrigatório. As demais colunas podem ser removidas.</small>
                    </div>
                    <button type="button" class="btn btn-outline-light btn-sm" data-bs-toggle="collapse" data-bs-target="#exportColumnsBox" aria-expanded="false" aria-controls="exportColumnsBox">
                        Selecionar colunas
                    </button>
                </div>

                <div class="collapse mt-3" id="exportColumnsBox">
                    <div class="export-columns-grid">
                        <?php foreach ($exportColumns as $key => $label): ?>
                            <?php if ($key === 'nome'): ?>
                                <input type="hidden" name="columns[]" value="nome">
                                <label class="export-column-option export-column-option--locked">
                                    <input type="checkbox" checked disabled>
                                    <span><?php echo htmlspecialchars($label); ?> <small>(obrigatório)</small></span>
                                </label>
                            <?php else: ?>
                                <label class="export-column-option">
                                    <input class="export-column" type="checkbox" name="columns[]" value="<?php echo htmlspecialchars($key); ?>" checked>
                                    <span><?php echo htmlspecialchars($label); ?></span>
                                </label>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button type="button" class="btn btn-outline-light btn-sm" onclick="setExportColumns(true)">Marcar colunas</button>
                        <button type="button" class="btn btn-outline-light btn-sm" onclick="setExportColumns(false)">Limpar colunas</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($ativos)): ?>
        <div class="alert alert-warning">Nenhum usuário ativo encontrado.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-dark table-striped table-sm align-middle usuarios-table">
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
                        <th>Status no período</th>
                        <th>Espelho</th>
                        <th>Cadastro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $renderUsuarioRows($ativos, true); ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <h2 class="header-section mt-4">Nomes Excluídos do Relógio</h2>
    <?php if (empty($excluidos)): ?>
        <div class="alert alert-warning">Nenhum usuário excluído encontrado.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-dark table-striped table-sm align-middle usuarios-table">
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
                        <th>Status no período</th>
                        <th>Espelho</th>
                        <th>Cadastro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $renderUsuarioRows($excluidos, false); ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</form>

<p class="fw-bold mt-2">*Horário Padrão. Clique em Espelho para alterar</p>

<script>
function pad2(value) {
    return String(value).padStart(2, '0');
}

function monthBounds() {
    const month = Number(document.getElementById('exportMes')?.value || new Date().getMonth() + 1);
    const year = Number(document.getElementById('exportAno')?.value || new Date().getFullYear());
    const startDefault = `${year}-${pad2(month)}-01`;
    const lastDay = new Date(year, month, 0).getDate();
    const endDefault = `${year}-${pad2(month)}-${pad2(lastDay)}`;
    const customStart = document.getElementById('exportDataInicio')?.value || '';
    const customEnd = document.getElementById('exportDataFim')?.value || '';

    return {
        start: customStart && customStart > startDefault ? customStart : startDefault,
        end: customEnd && customEnd < endDefault ? customEnd : endDefault,
    };
}

function semRegistroMode() {
    return document.querySelector('input[name="sem_registro"]:checked')?.value || 'skip';
}

function hasMarkInPeriod(row, start, end) {
    const marks = (row.dataset.marks || '').split(',').filter(Boolean);
    return marks.some((date) => date >= start && date <= end);
}

function setRowStatus(row, code, label, cssClass, disabled) {
    const badge = row.querySelector('.period-status');
    const checkbox = row.querySelector('.export-pis');

    row.classList.remove('status-ok', 'status-warning', 'status-muted');
    row.classList.add(cssClass);

    if (badge) {
        badge.textContent = label;
        badge.classList.remove('status-ok', 'status-warning', 'status-muted');
        badge.classList.add(cssClass);
    }

    if (checkbox) {
        checkbox.dataset.status = code;
        checkbox.disabled = disabled;
        if (disabled) {
            checkbox.checked = false;
        }
    }
}

function updatePeriodStatus() {
    const bounds = monthBounds();
    const mode = semRegistroMode();

    document.querySelectorAll('.export-row').forEach((row) => {
        const start = row.dataset.start || '';
        const end = row.dataset.end || '';

        if (start && start > bounds.end) {
            setRowStatus(row, 'incluido_apos', 'Incluído após o período', 'status-muted', true);
            return;
        }

        if (end && end < bounds.start) {
            setRowStatus(row, 'excluido_antes', 'Excluído antes do período', 'status-muted', true);
            return;
        }

        if (hasMarkInPeriod(row, bounds.start, bounds.end)) {
            setRowStatus(row, 'com_registro', 'Com registro no período', 'status-ok', false);
            return;
        }

        const disabled = mode === 'skip';
        setRowStatus(row, 'sem_registro', 'Sem registro no período', 'status-warning', disabled);
    });

    updateSelectedUsersCount();
}

function updateSelectedUsersCount() {
    const counter = document.getElementById('selectedUsersCount');
    if (!counter) {
        return;
    }

    counter.textContent = document.querySelectorAll('.export-pis:checked:not(:disabled)').length;
}

function setExportUsers(mode) {
    const all = document.querySelectorAll('.export-pis');
    all.forEach((item) => {
        if (item.disabled) {
            item.checked = false;
            return;
        }

        if (mode === 'limpar') {
            item.checked = false;
        } else if (mode === 'todos') {
            item.checked = true;
        } else if (mode === 'ativos') {
            item.checked = item.classList.contains('export-pis-ativo');
        }
    });
    updateSelectedUsersCount();
}

function setExportColumns(checked) {
    document.querySelectorAll('.export-column').forEach((item) => {
        item.checked = checked;
    });
}

document.querySelectorAll('.export-pis').forEach((item) => {
    item.addEventListener('change', updateSelectedUsersCount);
});

document.querySelectorAll('#exportMes, #exportAno, #exportDataInicio, #exportDataFim, input[name="sem_registro"]').forEach((item) => {
    item.addEventListener('change', updatePeriodStatus);
});

updatePeriodStatus();

document.getElementById('exportForm')?.addEventListener('submit', function (event) {
    updatePeriodStatus();

    const selected = Array.from(document.querySelectorAll('.export-pis:checked:not(:disabled)'));

    if (selected.length === 0) {
        event.preventDefault();
        alert('Selecione pelo menos um colaborador exportável. Se precisar exportar funcionários sem registro, altere o tratamento para "Exportar zerado" ou "Considerar falta integral".');
        return;
    }

    const container = document.getElementById('exportSelectedPisContainer');
    if (container) {
        container.innerHTML = '';

        selected.forEach((checkbox) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'pis[]';
            input.value = checkbox.dataset.pis || checkbox.value;
            container.appendChild(input);
        });
    }
});
</script>
