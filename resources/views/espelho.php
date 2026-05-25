<?php
use App\Services\JornadaService;

$usuario = $espelho['usuario'];
$nome = preg_replace('/\s+/', ' ', trim((string)($usuario['nome'] ?? $pis))) ?: $pis;
$meses = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
];

function navMonthUrl(string $pis, int $mes, int $ano, int $deltaMes = 0, int $deltaAno = 0): string {
    $date = new DateTime(sprintf('%04d-%02d-01', $ano + $deltaAno, $mes));
    if ($deltaMes !== 0) {
        $date->modify(($deltaMes > 0 ? '+' : '') . $deltaMes . ' month');
    }
    return 'index.php?page=espelho&pis=' . urlencode($pis) . '&mes=' . (int)$date->format('m') . '&ano=' . (int)$date->format('Y');
}
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
    <a class="btn btn-secondary btn-sm" href="index.php?page=espelho&pis=<?php echo urlencode($pis); ?>&mes=<?php echo $mes; ?>&ano=<?php echo $ano; ?>&editar=1">Editar</a>
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
                <input class="form-control bg-dark text-light border-secondary" name="semanal" value="<?php echo JornadaService::minutesToHour((int)$jornada['semanal_minutos']); ?>" placeholder="44:00">
            </div>
            <div class="col-md-2">
                <label class="form-label">Carga diária</label>
                <input class="form-control bg-dark text-light border-secondary" name="diaria" value="<?php echo JornadaService::minutesToHour((int)$jornada['diaria_minutos']); ?>" placeholder="08:00">
            </div>
            <div class="col-md-2">
                <label class="form-label">Tolerância/min</label>
                <input type="number" min="0" class="form-control bg-dark text-light border-secondary" name="tolerancia" value="<?php echo (int)$jornada['tolerancia_minutos']; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label d-block">Dias úteis</label>
                <?php $labels = [1=>'Seg',2=>'Ter',3=>'Qua',4=>'Qui',5=>'Sex',6=>'Sáb',7=>'Dom']; ?>
                <?php foreach ($labels as $n => $label): ?>
                    <label class="me-2">
                        <input type="checkbox" name="dias_uteis[]" value="<?php echo $n; ?>" <?php echo in_array($n, $jornada['dias_uteis'], true) ? 'checked' : ''; ?>> <?php echo $label; ?>
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

<div class="afd-section-title d-flex justify-content-between align-items-center">
    <div>
        <span class="badge bg-success me-2"><?php echo htmlspecialchars($nome); ?></span>
        - Ref.: <?php echo $meses[$mes] . ' de ' . $ano; ?>
    </div>
    <div>TOLERÂNCIA : <?php echo (int)$jornada['tolerancia_minutos']; ?> min</div>
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
                <tr>
                    <td class="fw-bold"><?php echo htmlspecialchars($row['data']); ?></td>
                    <td><em><?php echo htmlspecialchars($row['dia']); ?></em></td>
                    <td><?php echo htmlspecialchars($row['entrada1']); ?></td>
                    <td><?php echo htmlspecialchars($row['saida1']); ?></td>
                    <td><?php echo htmlspecialchars($row['entrada2']); ?></td>
                    <td><?php echo htmlspecialchars($row['saida2']); ?></td>
                    <td><?php echo htmlspecialchars($row['entrada3']); ?></td>
                    <td><?php echo htmlspecialchars($row['saida3']); ?></td>
                    <td class="tempo-col"><?php echo htmlspecialchars($row['tempo']); ?></td>
                    <td><?php echo htmlspecialchars($row['esperado']); ?></td>
                    <td><?php echo htmlspecialchars($row['comentario']); ?></td>
                    <td class="text-red fw-bold"><?php echo htmlspecialchars($row['falta']); ?></td>
                    <td class="text-purple fw-bold"><?php echo htmlspecialchars($row['extra']); ?></td>
                    <td class="d-print-none"><a class="text-purple text-decoration-none" href="index.php?page=espelho&pis=<?php echo urlencode($pis); ?>&mes=<?php echo $mes; ?>&ano=<?php echo $ano; ?>&editar=1">✎</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="table-info text-dark fw-bold">
                <td colspan="8" class="text-end">Totalizador:</td>
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

<p class="fw-bold">(*) Asterisco indica marcações inseridas manualmente</p>
<p>Nenhuma marcação invalidada.</p>
<p>Nenhuma justificativa registrada para o PIS/CPF <?php echo htmlspecialchars($pis); ?>.</p>
