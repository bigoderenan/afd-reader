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
            <select id="employeeSelectionMode" class="form-select form-select-sm bg-dark text-light border-secondary" title="Selecionar colaboradores" onchange="afdUsuariosSelectionChange(this.value)">
                <option value="manter">Colaboradores marcados</option>
                <option value="ativos">Selecionar ativos do filtro</option>
                <option value="todos">Selecionar todos do filtro</option>
                <option value="limpar">Limpar seleção</option>
            </select>
        </div>

        <button type="button" class="btn btn-outline-info btn-sm filter-action" id="applyEmployeeFilter" onclick="afdUsuariosApplyFilter()" title="Pesquisar com filtro">🔍 Pesquisar</button>
        <button type="button" class="btn btn-outline-secondary btn-sm filter-action" id="clearEmployeeFilter" onclick="afdUsuariosClearFilter()" title="Limpar filtro">× Limpar</button>
        <button type="button" class="btn btn-outline-light btn-sm filter-action" id="toggleAdvancedExportOptions" onclick="afdUsuariosToggleOptions()" aria-expanded="false" aria-controls="advancedExportOptions" title="Opções avançadas">⚙ Opções</button>
        <button type="submit" class="btn btn-green btn-sm filter-action filter-action--export" title="Exportar dados">📤 Exportar</button>
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
                        <button type="button" class="btn btn-outline-light btn-sm" data-export-users="ativos" onclick="afdUsuariosSetExportUsers('ativos')">Ativos do filtro</button>
                        <button type="button" class="btn btn-outline-light btn-sm" data-export-users="todos" onclick="afdUsuariosSetExportUsers('todos')">Todos do filtro</button>
                        <button type="button" class="btn btn-outline-light btn-sm" data-export-users="limpar" onclick="afdUsuariosSetExportUsers('limpar')">Limpar seleção</button>
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
(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    function byId(id) {
        return document.getElementById(id);
    }

    function all(selector, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(selector));
    }

    function pad2(value) {
        return String(value).padStart(2, '0');
    }

    function normalizeText(value) {
        value = String(value || '');
        if (typeof value.normalize === 'function') {
            value = value.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }
        return value.toLowerCase().trim();
    }

    function getCheckbox(row) {
        return row ? row.querySelector('.export-pis') : null;
    }

    function getRows() {
        return all('.export-row');
    }

    function setDisplay(row, visible) {
        if (!row) return;
        row.classList.toggle('d-none', !visible);
        row.style.display = visible ? '' : 'none';
    }

    function monthBounds() {
        var month = Number((byId('exportMes') && byId('exportMes').value) || (new Date().getMonth() + 1));
        var year = Number((byId('exportAno') && byId('exportAno').value) || new Date().getFullYear());
        var startDefault = year + '-' + pad2(month) + '-01';
        var lastDay = new Date(year, month, 0).getDate();
        var endDefault = year + '-' + pad2(month) + '-' + pad2(lastDay);
        var customStart = (byId('exportDataInicio') && byId('exportDataInicio').value) || '';
        var customEnd = (byId('exportDataFim') && byId('exportDataFim').value) || '';

        return {
            start: customStart && customStart > startDefault ? customStart : startDefault,
            end: customEnd && customEnd < endDefault ? customEnd : endDefault
        };
    }

    function semRegistroMode() {
        var checked = document.querySelector('input[name="sem_registro"]:checked');
        return checked ? checked.value : 'skip';
    }

    function setSemRegistroMode(mode) {
        var option = document.querySelector('input[name="sem_registro"][value="' + mode + '"]');
        if (option) {
            option.checked = true;
        }
        updateSemRegistroHint();
    }

    function hasMarkInPeriod(row, start, end) {
        var marks = String(row.getAttribute('data-marks') || '').split(',').filter(Boolean);
        for (var i = 0; i < marks.length; i++) {
            if (marks[i] >= start && marks[i] <= end) {
                return true;
            }
        }
        return false;
    }

    function setRowStatus(row, code, label, cssClass, disabled) {
        var badge = row.querySelector('.period-status');
        var checkbox = getCheckbox(row);

        row.classList.remove('status-ok', 'status-warning', 'status-muted');
        row.classList.add(cssClass);
        row.setAttribute('data-period-status', code);

        if (badge) {
            badge.textContent = label;
            badge.classList.remove('status-ok', 'status-warning', 'status-muted');
            badge.classList.add(cssClass);
        }

        if (checkbox) {
            checkbox.setAttribute('data-status', code);
            checkbox.disabled = !!disabled;
            if (disabled) {
                checkbox.checked = false;
            }
        }
    }

    function updatePeriodStatus() {
        var bounds = monthBounds();
        getRows().forEach(function (row) {
            var start = row.getAttribute('data-start') || '';
            var end = row.getAttribute('data-end') || '';

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
        var termEl = byId('employeeSearch');
        var typeEl = byId('employeeStatusFilter');
        var term = normalizeText(termEl ? termEl.value : '');
        var statusFilter = typeEl ? typeEl.value : 'todos';
        var searchable = normalizeText((row.getAttribute('data-name') || '') + ' ' + (row.getAttribute('data-pis') || ''));
        var status = row.getAttribute('data-period-status') || (getCheckbox(row) ? getCheckbox(row).getAttribute('data-status') : '');
        var isActive = row.getAttribute('data-active') === '1';

        if (term !== '' && searchable.indexOf(term) === -1) return false;
        if (statusFilter === 'com_registro' && status !== 'com_registro') return false;
        if (statusFilter === 'sem_registro' && status !== 'sem_registro') return false;
        if (statusFilter === 'fora_periodo' && ['incluido_apos', 'excluido_antes'].indexOf(status) === -1) return false;
        if (statusFilter === 'ativos' && !isActive) return false;
        if (statusFilter === 'excluidos' && isActive) return false;

        return true;
    }

    function visibleRows() {
        return getRows().filter(function (row) {
            return !row.classList.contains('d-none') && row.style.display !== 'none';
        });
    }

    function rowsMatchingCurrentFilter() {
        return getRows().filter(rowMatchesFilter);
    }

    function updateVisibleUsersCount() {
        var counter = byId('visibleUsersCount');
        if (counter) counter.textContent = String(visibleRows().length);
    }

    function updateSelectedUsersCount() {
        var counter = byId('selectedUsersCount');
        if (counter) counter.textContent = String(all('.export-pis:checked:not(:disabled)').length);
    }

    function updateSemRegistroHint() {
        var hint = byId('semRegistroAutoHint');
        if (!hint) return;
        var hasSem = all('.export-pis:checked:not(:disabled)').some(function (item) {
            return item.getAttribute('data-status') === 'sem_registro';
        });
        hint.classList.toggle('d-none', !hasSem);
    }

    function applyEmployeeFilter(uncheckHidden) {
        updatePeriodStatus();
        getRows().forEach(function (row) {
            var visible = rowMatchesFilter(row);
            var checkbox = getCheckbox(row);
            setDisplay(row, visible);
            if (!visible && uncheckHidden !== false && checkbox) {
                checkbox.checked = false;
            }
        });
        updateVisibleUsersCount();
        updateSelectedUsersCount();
        updateSemRegistroHint();
    }

    function clearEmployeeFilter() {
        if (byId('employeeSearch')) byId('employeeSearch').value = '';
        if (byId('employeeStatusFilter')) byId('employeeStatusFilter').value = 'todos';
        if (byId('employeeSelectionMode')) byId('employeeSelectionMode').value = 'manter';
        getRows().forEach(function (row) { setDisplay(row, true); });
        updatePeriodStatus();
    }

    function selectableRowsForMode(mode) {
        var base = (mode === 'todos' || mode === 'ativos') ? rowsMatchingCurrentFilter() : visibleRows();
        return base.filter(function (row) {
            var checkbox = getCheckbox(row);
            return checkbox && !checkbox.disabled;
        });
    }

    function setExportUsers(mode) {
        mode = mode || 'ativos';
        if (mode === 'limpar') {
            all('.export-pis').forEach(function (item) { item.checked = false; });
            if (byId('employeeSelectionMode')) byId('employeeSelectionMode').value = 'manter';
            updateSelectedUsersCount();
            updateSemRegistroHint();
            return;
        }

        // Regra automática: se o usuário pediu todos/ativos do filtro,
        // funcionários sem registro podem entrar zerados com observação.
        if (mode === 'todos' || mode === 'ativos') {
            setSemRegistroMode('zero');
        }

        updatePeriodStatus();
        var targetRows = selectableRowsForMode(mode);
        var targetSet = new Set(targetRows);

        getRows().forEach(function (row) {
            var checkbox = getCheckbox(row);
            if (!checkbox || checkbox.disabled) {
                if (checkbox) checkbox.checked = false;
                return;
            }
            var shouldCheck = targetSet.has(row);
            if (mode === 'ativos') {
                shouldCheck = shouldCheck && row.getAttribute('data-active') === '1';
            }
            checkbox.checked = shouldCheck;
        });

        updateSelectedUsersCount();
        updateSemRegistroHint();
    }

    function toggleOptions() {
        var panel = byId('advancedExportOptions');
        var button = byId('toggleAdvancedExportOptions');
        if (!panel) return;
        var isOpen = panel.classList.contains('show');
        panel.classList.toggle('show', !isOpen);
        panel.style.display = isOpen ? 'none' : 'block';
        if (button) button.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
    }

    function setExportColumns(checked) {
        all('.export-column').forEach(function (item) { item.checked = !!checked; });
    }

    function selectionChange(value) {
        if (value && value !== 'manter') {
            setExportUsers(value);
        }
    }

    window.afdUsuariosApplyFilter = function () { applyEmployeeFilter(true); };
    window.afdUsuariosClearFilter = clearEmployeeFilter;
    window.afdUsuariosToggleOptions = toggleOptions;
    window.afdUsuariosSetExportUsers = setExportUsers;
    window.afdUsuariosSelectionChange = selectionChange;
    window.setExportColumns = setExportColumns;

    ready(function () {
        var applyBtn = byId('applyEmployeeFilter');
        var clearBtn = byId('clearEmployeeFilter');
        var toggleBtn = byId('toggleAdvancedExportOptions');
        var selection = byId('employeeSelectionMode');
        var search = byId('employeeSearch');
        var form = byId('exportForm');

        if (applyBtn) applyBtn.addEventListener('click', function () { applyEmployeeFilter(true); });
        if (clearBtn) clearBtn.addEventListener('click', clearEmployeeFilter);
        if (toggleBtn) toggleBtn.addEventListener('click', toggleOptions);
        if (selection) selection.addEventListener('change', function () { selectionChange(selection.value); });

        all('[data-export-users]').forEach(function (button) {
            button.addEventListener('click', function () {
                setExportUsers(button.getAttribute('data-export-users') || 'ativos');
            });
        });

        all('.export-pis').forEach(function (item) {
            item.addEventListener('change', function () {
                if (item.checked && item.getAttribute('data-status') === 'sem_registro' && semRegistroMode() === 'skip') {
                    setSemRegistroMode('zero');
                }
                updateSelectedUsersCount();
                updateSemRegistroHint();
            });
        });

        all('#exportMes, #exportAno, #exportDataInicio, #exportDataFim, input[name="sem_registro"]').forEach(function (item) {
            item.addEventListener('change', function () {
                updatePeriodStatus();
                applyEmployeeFilter(false);
            });
        });

        if (search) {
            search.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    applyEmployeeFilter(true);
                }
            });
        }

        if (form) {
            form.addEventListener('submit', function (event) {
                updatePeriodStatus();
                var mode = selection ? selection.value : 'manter';
                if (mode === 'todos' || mode === 'ativos') {
                    setExportUsers(mode);
                }
                var selected = all('.export-pis:checked:not(:disabled)');
                if (selected.length === 0) {
                    event.preventDefault();
                    alert('Selecione pelo menos um colaborador exportável.');
                }
            });
        }

        var panel = byId('advancedExportOptions');
        if (panel && !panel.classList.contains('show')) {
            panel.style.display = 'none';
        }

        updatePeriodStatus();
        applyEmployeeFilter(false);
    });
})();
</script>
