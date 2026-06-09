<?php
use App\Services\JornadaService;

$usuario = $espelho['usuario'];
$nome = preg_replace('/\s+/', ' ', trim((string)($usuario['nome'] ?? $pis))) ?: $pis;
$meses = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
];

$jornada = (new JornadaService())->normalize($jornada ?? []);

function navMonthUrl(string $pis, int $mes, int $ano, int $deltaMes = 0, int $deltaAno = 0): string {
    $date = new DateTime(sprintf('%04d-%02d-01', $ano + $deltaAno, $mes));
    if ($deltaMes !== 0) {
        $date->modify(($deltaMes > 0 ? '+' : '') . $deltaMes . ' month');
    }
    return 'index.php?page=espelho&pis=' . urlencode($pis) . '&mes=' . (int)$date->format('m') . '&ano=' . (int)$date->format('Y');
}

$editarDia = $editarDia ?? null;
$ajusteManual = is_array($ajusteManual ?? null) ? $ajusteManual : null;
$editarDiaRow = null;
if ($editarDia) {
    foreach (($espelho['rows'] ?? []) as $linhaEspelho) {
        if (($linhaEspelho['data_iso'] ?? '') === $editarDia) {
            $editarDiaRow = $linhaEspelho;
            break;
        }
    }
}
$editarBatidas = $ajusteManual['batidas'] ?? ($editarDiaRow['batidas_raw'] ?? []);
$editarBatidas = is_array($editarBatidas) ? array_values(array_slice($editarBatidas, 0, 4)) : [];
while (count($editarBatidas) < 4) {
    $editarBatidas[] = '00:00';
}
$editarBatidas = array_map(static function ($hora) {
    $hora = trim((string)$hora);
    return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $hora) ? $hora : '00:00';
}, $editarBatidas);
$editarComentario = $ajusteManual['comentario'] ?? '';
?>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="mb-3 d-print-none">
    <a class="btn btn-green btn-sm" href="<?php echo navMonthUrl($pis, $mes, $ano, 0, -1); ?>">Ano-</a>
    <a class="btn btn-green btn-sm" href="<?php echo navMonthUrl($pis, $mes, $ano, -1, 0); ?>">Mês-</a>
    <a class="btn btn-green btn-sm" href="<?php echo navMonthUrl($pis, $mes, $ano, 1, 0); ?>">Mês+</a>
    <a class="btn btn-green btn-sm" href="<?php echo navMonthUrl($pis, $mes, $ano, 0, 1); ?>">Ano+</a>

    <form method="get" action="index.php" class="d-inline-flex align-items-center gap-2 ms-2">
        <input type="hidden" name="page" value="espelho">
        <input type="hidden" name="pis" value="<?php echo htmlspecialchars($pis); ?>">
        <select name="mes" class="form-select form-select-sm bg-dark text-light border-secondary" style="width: 130px;">
            <?php foreach ($meses as $num => $label): ?>
                <option value="<?php echo $num; ?>" <?php echo $num === $mes ? 'selected' : ''; ?>><?php echo $label; ?></option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="ano" class="form-control form-control-sm bg-dark text-light border-secondary" value="<?php echo $ano; ?>" style="width: 90px;">
        <button class="btn btn-green btn-sm" type="submit">Selecionar Período</button>
    </form>

    <button class="btn btn-blue btn-sm ms-2" type="button" onclick="window.print()">Preparar para Impressão</button>
    <a class="btn btn-secondary btn-sm" href="index.php?page=espelho&pis=<?php echo urlencode($pis); ?>&mes=<?php echo $mes; ?>&ano=<?php echo $ano; ?>&editar=1">Editar jornada</a>
    <a class="btn btn-secondary btn-sm" href="index.php?page=usuarios">Voltar</a>
</div>

<?php if ($editar): ?>
<div class="card bg-dark text-light border-secondary mb-3 d-print-none">
    <div class="card-header bg-success text-white fw-bold">Alterar carga horária de <?php echo htmlspecialchars($nome); ?></div>
    <div class="card-body">
        <form method="post" action="index.php?page=salvar_jornada" class="row g-3">
            <input type="hidden" name="pis" value="<?php echo htmlspecialchars($pis); ?>">
            <input type="hidden" name="mes" value="<?php echo $mes; ?>">
            <input type="hidden" name="ano" value="<?php echo $ano; ?>">
            <div class="col-md-2">
                <label class="form-label">Carga semanal</label>
                <input class="form-control bg-dark text-light border-secondary" name="semanal" value="<?php echo htmlspecialchars(JornadaService::minutesToHour((int)($jornada['semanal_minutos'] ?? 2640)), ENT_QUOTES, 'UTF-8'); ?>" placeholder="44:00">
            </div>
            <div class="col-md-2">
                <label class="form-label">Carga diária</label>
                <input class="form-control bg-dark text-light border-secondary" name="diaria" value="<?php echo htmlspecialchars(JornadaService::minutesToHour((int)($jornada['diaria_minutos'] ?? 540)), ENT_QUOTES, 'UTF-8'); ?>" placeholder="09:00">
            </div>
            <div class="col-md-2">
                <label class="form-label">Sexta-feira</label>
                <input class="form-control bg-dark text-light border-secondary" name="sexta" value="<?php echo htmlspecialchars(JornadaService::minutesToHour((int)($jornada['sexta_minutos'] ?? 480)), ENT_QUOTES, 'UTF-8'); ?>" placeholder="08:00">
            </div>
            <div class="col-md-2">
                <label class="form-label">Tolerância/min</label>
                <input type="number" min="0" class="form-control bg-dark text-light border-secondary" name="tolerancia" value="<?php echo (int)($jornada['tolerancia_minutos'] ?? 10); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label d-block">Dias úteis</label>
                <?php $labels = [1=>'Seg',2=>'Ter',3=>'Qua',4=>'Qui',5=>'Sex',6=>'Sáb',7=>'Dom']; ?>
                <?php foreach ($labels as $n => $label): ?>
                    <label class="me-2">
                        <input type="checkbox" name="dias_uteis[]" value="<?php echo $n; ?>" <?php echo in_array($n, $jornada['dias_uteis'] ?? [1, 2, 3, 4, 5], true) ? 'checked' : ''; ?>> <?php echo $label; ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-green w-100" type="submit">Salvar</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($editarDia && $editarDiaRow): ?>
<div class="card bg-dark text-light border-secondary mb-3 d-print-none" id="editar-dia">
    <div class="card-header bg-primary text-white fw-bold">
        Editar marcações de <?php echo htmlspecialchars($editarDiaRow['data']); ?> - <?php echo htmlspecialchars($nome); ?>
    </div>
    <div class="card-body">
        <p class="text-secondary mb-3">
            Este ajuste substitui as batidas do AFD somente neste dia. São quatro posições fixas: Entrada 1, Saída 1, Entrada 2 e Saída 2. Use 00:00 para indicar posição sem marcação; esse valor não entra no cálculo.
        </p>
        <form method="post" action="index.php?page=salvar_marcacao_manual" class="row g-3 align-items-end">
            <input type="hidden" name="pis" value="<?php echo htmlspecialchars($pis); ?>">
            <input type="hidden" name="mes" value="<?php echo $mes; ?>">
            <input type="hidden" name="ano" value="<?php echo $ano; ?>">
            <input type="hidden" name="data" value="<?php echo htmlspecialchars($editarDia); ?>">

            <?php $labelsBatidas = ['Entrada 1', 'Saída 1', 'Entrada 2', 'Saída 2']; ?>
            <?php foreach ($labelsBatidas as $idx => $labelBatida): ?>
                <div class="col-6 col-md-3">
                    <label class="form-label"><?php echo $labelBatida; ?></label>
                    <input type="time" name="batidas[]" class="form-control bg-dark text-light border-secondary" value="<?php echo htmlspecialchars((string)($editarBatidas[$idx] ?? '')); ?>">
                </div>
            <?php endforeach; ?>

            <div class="col-12 col-md-8">
                <label class="form-label">Observação do ajuste</label>
                <input type="text" name="comentario" maxlength="180" class="form-control bg-dark text-light border-secondary" value="<?php echo htmlspecialchars((string)$editarComentario); ?>" placeholder="Ex.: Relógio sem comunicação; ajuste autorizado">
            </div>

            <div class="col-12 col-md-4 d-flex flex-wrap gap-2">
                <button class="btn btn-green" type="submit">Salvar dia</button>
                <button class="btn btn-outline-danger" type="submit" name="limpar_ajuste" value="1">Remover ajuste</button>
                <a class="btn btn-outline-secondary" href="index.php?page=espelho&pis=<?php echo urlencode($pis); ?>&mes=<?php echo $mes; ?>&ano=<?php echo $ano; ?>#dia-<?php echo htmlspecialchars($editarDia); ?>">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="afd-section-title d-flex justify-content-between align-items-center">
    <div>
        <span class="badge bg-success me-2"><?php echo htmlspecialchars($nome); ?></span>
        - Ref.: <?php echo $meses[$mes] . ' de ' . $ano; ?>
    </div>
    <div>TOLERÂNCIA : <?php echo (int)($jornada['tolerancia_minutos'] ?? 10); ?> min</div>
</div>

<div class="table-responsive">
    <table class="table table-dark table-striped table-sm align-middle espelho-table">
        <thead>
            <tr>
                <th>Data</th>
                <th>Dia</th>
                <th>Entrada</th>
                <th>Saída</th>
                <th>Entrada</th>
                <th>Saída</th>
                <th class="tempo-col">Tempo</th>
                <th>Esperado</th>
                <th>Comentário</th>
                <th>Falta</th>
                <th>Hora Extra</th>
                <th class="d-print-none">Ação</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($espelho['rows'] as $row): ?>
                <?php
                    $rowClasses = [];
                    if (!empty($row['manual'])) {
                        $rowClasses[] = 'espelho-row-manual';
                    }
                    if (!empty($row['marcacao_pendente'])) {
                        $rowClasses[] = 'espelho-row-pendente';
                    }
                ?>
                <tr id="dia-<?php echo htmlspecialchars((string)$row['data_iso']); ?>" class="<?php echo implode(' ', $rowClasses); ?>">
                    <td class="fw-bold"><?php echo htmlspecialchars($row['data']); ?></td>
                    <td><em><?php echo htmlspecialchars($row['dia']); ?></em></td>
                    <td><?php echo htmlspecialchars($row['entrada1']); ?></td>
                    <td><?php echo htmlspecialchars($row['saida1']); ?></td>
                    <td><?php echo htmlspecialchars($row['entrada2']); ?></td>
                    <td><?php echo htmlspecialchars($row['saida2']); ?></td>
                    <td class="tempo-col"><?php echo htmlspecialchars($row['tempo']); ?></td>
                    <td><?php echo htmlspecialchars($row['esperado']); ?></td>
                    <td><?php echo htmlspecialchars($row['comentario']); ?></td>
                    <td class="text-red fw-bold"><?php echo htmlspecialchars($row['falta']); ?></td>
                    <td class="text-purple fw-bold"><?php echo htmlspecialchars($row['extra']); ?></td>
                    <td class="d-print-none"><a class="btn btn-outline-light btn-sm" href="index.php?page=espelho&pis=<?php echo urlencode($pis); ?>&mes=<?php echo $mes; ?>&ano=<?php echo $ano; ?>&editar_dia=<?php echo urlencode((string)$row['data_iso']); ?>#editar-dia"><?php echo !empty($row['marcacao_pendente']) ? 'Corrigir dia' : 'Editar dia'; ?></a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="table-info text-dark fw-bold">
                <td colspan="6" class="text-end">Totalizador:</td>
                <td><?php echo htmlspecialchars($espelho['totais']['trabalhado']); ?></td>
                <td></td>
                <td></td>
                <td class="text-red"><?php echo htmlspecialchars($espelho['totais']['faltas']); ?></td>
                <td class="text-purple"><?php echo htmlspecialchars($espelho['totais']['extras']); ?></td>
                <td class="d-print-none"></td>
            </tr>
        </tfoot>
    </table>
</div>

<p class="fw-bold">(*) Asterisco indica marcações ajustadas manualmente. O valor 00:00 é apenas posição reservada e não entra no cálculo.</p>
<p><?php echo (int)($espelho['invalidadas'] ?? 0); ?> marcação(ões) incompleta(s) ou inválida(s).</p>
<p>Nenhuma justificativa registrada para o PIS/CPF <?php echo htmlspecialchars($pis); ?>.</p>
