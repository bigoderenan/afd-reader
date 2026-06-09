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
        $nome = (string)($u['nome'] ?? '');
        $checked = $grupoAtivo && $statusCodigo === 'com_registro';
        $disabled = in_array($statusCodigo, ['incluido_apos', 'excluido_antes'], true);
        ?>
        <tr class="export-row <?php echo htmlspecialchars($statusClass); ?>"
            data-active="<?php echo $grupoAtivo ? '1' : '0'; ?>"
            data-name="<?php echo htmlspecialchars(strtolower($nome)); ?>"
            data-pis="<?php echo htmlspecialchars($pis); ?>"
            data-start="<?php echo htmlspecialchars((string)($u['usuarioInicio'] ?? '')); ?>"
            data-end="<?php echo htmlspecialchars((string)($u['usuarioFim'] ?? '')); ?>"
            data-marks="<?php echo htmlspecialchars((string)($u['markDates'] ?? '')); ?>">
            <td>
                <input class="export-pis <?php echo $grupoAtivo ? 'export-pis-ativo' : 'export-pis-excluido'; ?>"
                       type="checkbox"
                       name="pis[]"
                       data-pis="<?php echo htmlspecialchars($pis); ?>"
                       data-status="<?php echo htmlspecialchars($statusCodigo); ?>"
                       value="<?php echo htmlspecialchars($pis); ?>"
                       <?php echo $checked ? 'checked' : ''; ?>
                       <?php echo $disabled ? 'disabled' : ''; ?>>
            </td>
            <td><?php echo $i++; ?>.</td>
            <td><a class="text-blue text-decoration-none" href="index.php?page=espelho&pis=<?php echo urlencode($pis); ?>"><?php echo htmlspecialchars($nome); ?></a></td>
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
    <p class="text-secondary mb-0">Pesquise, filtre, marque os colaboradores e exporte somente o período selecionado.</p>
</div>

<form method="post" action="index.php?page=exportar_folha" id="exportForm">
    <div class="afd-filter-strip mb-3" aria-label="Filtro de exportação compacto">
        <div class="filter-control filter-control--employee">
            <input id="employeeSearch" type="search" class="form-control form-control-sm bg-dark text-light border-secondary" placeholder="Funcionário ou PIS">
        </div>

        <div class="filter-control filter-control--status">
            <select id="employeeStatusFilter" class="form-select form-select-sm bg-dark text-light border-secondary" title="Tipo de funcionário">
                <option value="todos">Tipo (Todos)</option>
                <option value="com_registro">Com registro</option>
                <option value="sem_registro">Sem registro</option>
                <option value="fora_periodo">Fora do período</option>
                <option value="ativos">Somente ativos</option>
                <option value="excluidos">Somente excluídos</option>
            </select>
        </div>

        <div class="input-group input-group-sm filter-control filter-control--year">
            <span class="input-group-text bg-primary text-white border-primary fw-bold">ANO</span>
            <input id="exportAno" type="number" name="ano" value="<?php echo htmlspecialchars((string)$anoAtual); ?>" min="2000" max="2100" class="form-control bg-dark text-light border-primary">
        </div>

        <div class="input-group input-group-sm filter-control filter-control--month">
            <span class="input-group-text bg-dark text-light border-secondary fw-bold">MÊS</span>
            <select id="exportMes" name="mes" class="form-select bg-dark text-light border-secondary">
                <?php foreach ($mesesExport as $num => $label): ?>
                    <option value="<?php echo $num; ?>" <?php echo $num === $mesAtual ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-control filter-control--selection">
            <select id="employeeSelectionMode" class="form-select form-select-sm bg-dark text-light border-secondary" title="Selecionar colaboradores">
                <option value="manter">Colaboradores marcados</option>
                <option value="ativos">Selecionar ativos do filtro</option>
                <option value="todos">Selecionar todos do filtro</option>
                <option value="limpar">Limpar seleção</option>
            </select>
        </div>

        <button type="button" class="btn btn-outline-info btn-sm filter-action" id="applyEmployeeFilter">🔍 Pesquisar com filtro</button>
        <button type="button" class="btn btn-outline-secondary btn-sm filter-action" id="clearEmployeeFilter">× Limpar filtro</button>
        <button type="button" class="btn btn-outline-light btn-sm filter-action" data-bs-toggle="collapse" data-bs-target="#advancedExportOptions" aria-expanded="false" aria-controls="advancedExportOptions">⚙ Opções</button>
        <button type="submit" class="btn btn-green btn-sm filter-action filter-action--export">📤 Exportar dados</button>
    </div>

    <div class="export-mini-summary mb-3">
        <span><strong id="selectedUsersCount">0</strong> selecionado(s)</span>
        <span><strong id="visibleUsersCount">0</strong> visível(eis)</span>
        <span id="semRegistroAutoHint" class="text-warning d-none">Funcionário sem registro selecionado: será exportado zerado com observação.</span>
    </div>

    <div class="collapse" id="advancedExportOptions">
        <div class="export-advanced-panel mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <strong>Opções avançadas da exportação</strong>
                    <small class="text-secondary d-block">Datas específicas, tratamento dos sem registro e colunas da planilha.</small>
                </div>
            </div>

            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label mb-1">Data inicial <small class="text-secondary">opcional</small></label>
                    <input id="exportDataInicio" type="date" name="data_inicio" class="form-control form-control-sm bg-dark text-light border-secondary">
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label mb-1">Data final <small class="text-secondary">opcional</small></label>
                    <input id="exportDataFim" type="date" name="data_fim" class="form-control form-control-sm bg-dark text-light border-secondary">
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label mb-1">Ações de seleção</label>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-light btn-sm" data-export-users="ativos">Ativos do filtro</button>
                        <button type="button" class="btn btn-outline-light btn-sm" data-export-users="todos">Todos do filtro</button>
                        <button type="button" class="btn btn-outline-light btn-sm" data-export-users="limpar">Limpar seleção</button>
                    </div>
                </div>
            </div>

            <div class="export-panel__rules mt-3">
                <div>
                    <strong>Funcionários sem registro no período</strong>
                    <small class="text-secondary d-block">Automático: ao selecionar Todos do filtro ou marcar um funcionário sem registro, o sistema altera sozinho para exportar zerado com observação.</small>
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
            <table class="table table-dark table-striped table-sm align-middle usuarios-table" data-user-table="ativos">
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
            <table class="table table-dark table-striped table-sm align-middle usuarios-table" data-user-table="excluidos">
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

function normalizeText(value) {
    return String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();
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

function setSemRegistroMode(mode, dispatchChange = false) {
    const option = document.querySelector(`input[name="sem_registro"][value="${mode}"]`);
    if (option) {
        const changed = !option.checked;
        option.checked = true;

        if (dispatchChange && changed) {
            option.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }
    updateSemRegistroHint();
}

function autoAllowSemRegistroForRows(rows) {
    const hasSemRegistro = rows.some((row) => {
        const item = row.querySelector('.export-pis');
        return item && item.dataset.status === 'sem_registro';
    });

    if (hasSemRegistro && semRegistroMode() === 'skip') {
        setSemRegistroMode('zero');
    }

    return hasSemRegistro;
}

function updateSemRegistroHint() {
    const hint = document.getElementById('semRegistroAutoHint');
    if (!hint) {
        return;
    }

    const hasSemRegistroSelecionado = Array.from(document.querySelectorAll('.export-pis:checked:not(:disabled)'))
        .some((item) => item.dataset.status === 'sem_registro');

    hint.classList.toggle('d-none', !hasSemRegistroSelecionado);
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
    row.dataset.periodStatus = code;

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

        setRowStatus(row, 'sem_registro', 'Sem registro no período', 'status-warning', false);
    });

    updateVisibleUsersCount();
    updateSelectedUsersCount();
    updateSemRegistroHint();
}

function rowMatchesFilter(row) {
    const term = normalizeText(document.getElementById('employeeSearch')?.value || '');
    const statusFilter = document.getElementById('employeeStatusFilter')?.value || 'todos';
    const searchable = normalizeText(`${row.dataset.name || ''} ${row.dataset.pis || ''}`);
    const status = row.dataset.periodStatus || row.querySelector('.export-pis')?.dataset.status || '';
    const isActive = row.dataset.active === '1';

    if (term !== '' && !searchable.includes(term)) {
        return false;
    }

    if (statusFilter === 'com_registro' && status !== 'com_registro') {
        return false;
    }

    if (statusFilter === 'sem_registro' && status !== 'sem_registro') {
        return false;
    }

    if (statusFilter === 'fora_periodo' && !['incluido_apos', 'excluido_antes'].includes(status)) {
        return false;
    }

    if (statusFilter === 'ativos' && !isActive) {
        return false;
    }

    if (statusFilter === 'excluidos' && isActive) {
        return false;
    }

    return true;
}

function applyEmployeeFilter(uncheckHidden = true) {
    updatePeriodStatus();

    document.querySelectorAll('.export-row').forEach((row) => {
        const visible = rowMatchesFilter(row);
        row.classList.toggle('d-none', !visible);

        if (!visible && uncheckHidden) {
            const checkbox = row.querySelector('.export-pis');
            if (checkbox) {
                checkbox.checked = false;
            }
        }
    });

    const selection = document.getElementById('employeeSelectionMode')?.value || 'manter';
    if (selection !== 'manter') {
        setExportUsers(selection);
    }

    updateVisibleUsersCount();
    updateSelectedUsersCount();
    updateSemRegistroHint();
}

function clearEmployeeFilter() {
    const search = document.getElementById('employeeSearch');
    const status = document.getElementById('employeeStatusFilter');
    const selection = document.getElementById('employeeSelectionMode');

    if (search) search.value = '';
    if (status) status.value = 'todos';
    if (selection) selection.value = 'manter';

    document.querySelectorAll('.export-row').forEach((row) => {
        row.classList.remove('d-none');
    });

    updatePeriodStatus();
}

function visibleRows() {
    return Array.from(document.querySelectorAll('.export-row')).filter((row) => !row.classList.contains('d-none'));
}

function updateVisibleUsersCount() {
    const counter = document.getElementById('visibleUsersCount');
    if (counter) {
        counter.textContent = visibleRows().length;
    }
}

function updateSelectedUsersCount() {
    const counter = document.getElementById('selectedUsersCount');
    if (counter) {
        counter.textContent = document.querySelectorAll('.export-pis:checked:not(:disabled)').length;
    }
}

function ensureSemRegistroModeForSelection() {
    const selectedSemRegistro = Array.from(document.querySelectorAll('.export-pis:checked:not(:disabled)'))
        .filter((item) => item.dataset.status === 'sem_registro');

    if (selectedSemRegistro.length > 0 && semRegistroMode() === 'skip') {
        setSemRegistroMode('zero');
    }
}

function rowsMatchingCurrentFilter() {
    return Array.from(document.querySelectorAll('.export-row')).filter(rowMatchesFilter);
}

function selectableRowsForMode(mode) {
    const rows = mode === 'todos' || mode === 'ativos'
        ? rowsMatchingCurrentFilter()
        : visibleRows();

    return rows.filter((row) => {
        const item = row.querySelector('.export-pis');
        return item && !item.disabled;
    });
}

function setExportUsers(mode) {
    // Ao escolher Todos/Ativos, a própria ação já autoriza sem registro como zerado.
    // Isso precisa acontecer ANTES do recálculo do período, porque algumas regras
    // da tela dependem do modo atual de tratamento.
    if ((mode === 'todos' || mode === 'ativos') && semRegistroMode() === 'skip') {
        setSemRegistroMode('zero');
    }

    updatePeriodStatus();

    if (mode === 'limpar') {
        document.querySelectorAll('.export-pis').forEach((item) => {
            item.checked = false;
        });
        updateSelectedUsersCount();
        updateSemRegistroHint();
        return;
    }

    let targetRows = selectableRowsForMode(mode);

    // Automação principal:
    // se a seleção atual incluir funcionário sem registro, a regra muda para
    // "Exportar zerado com observação" antes de marcar os checkboxes.
    autoAllowSemRegistroForRows(targetRows);

    // Recalcula depois da alteração automática para garantir que a tela e
    // o backend usem a mesma regra no envio do formulário.
    updatePeriodStatus();
    targetRows = selectableRowsForMode(mode);
    const targetSet = new Set(targetRows);

    document.querySelectorAll('.export-row').forEach((row) => {
        const item = row.querySelector('.export-pis');
        if (!item || item.disabled) {
            if (item) item.checked = false;
            return;
        }

        if (mode === 'todos') {
            item.checked = targetSet.has(row);
            return;
        }

        if (mode === 'ativos') {
            item.checked = targetSet.has(row) && row.dataset.active === '1';
        }
    });

    ensureSemRegistroModeForSelection();
    updateSelectedUsersCount();
    updateSemRegistroHint();
}

document.querySelectorAll('[data-export-users]').forEach((button) => {
    button.addEventListener('click', () => setExportUsers(button.dataset.exportUsers || 'ativos'));
});

document.getElementById('employeeSelectionMode')?.addEventListener('change', (event) => {
    const mode = event.target.value || 'manter';
    if (mode !== 'manter') {
        setExportUsers(mode);
    }
});

function setExportColumns(checked) {
    document.querySelectorAll('.export-column').forEach((item) => {
        item.checked = checked;
    });
}

document.querySelectorAll('.export-pis').forEach((item) => {
    item.addEventListener('change', () => {
        ensureSemRegistroModeForSelection();
        updateSelectedUsersCount();
        updateSemRegistroHint();
    });
});

document.querySelectorAll('#exportMes, #exportAno, #exportDataInicio, #exportDataFim, input[name="sem_registro"]').forEach((item) => {
    item.addEventListener('change', () => {
        updatePeriodStatus();
        applyEmployeeFilter(false);
    });
});

document.getElementById('applyEmployeeFilter')?.addEventListener('click', () => applyEmployeeFilter(true));
document.getElementById('clearEmployeeFilter')?.addEventListener('click', clearEmployeeFilter);
document.getElementById('employeeSearch')?.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {
        event.preventDefault();
        applyEmployeeFilter(true);
    }
});

updatePeriodStatus();
applyEmployeeFilter(false);

document.getElementById('exportForm')?.addEventListener('submit', function (event) {
    updatePeriodStatus();

    const selectionMode = document.getElementById('employeeSelectionMode')?.value || 'manter';
    if (selectionMode === 'todos' || selectionMode === 'ativos') {
        setExportUsers(selectionMode);
    }

    ensureSemRegistroModeForSelection();

    const selected = Array.from(document.querySelectorAll('.export-pis:checked:not(:disabled)'));

    if (selected.length === 0) {
        event.preventDefault();
        alert('Selecione pelo menos um colaborador exportável.');
    }
});
</script>
