<?php
use App\Helpers\Format;
$month = (int)$calendar['month']; $year = (int)$calendar['year'];
$prevMonth = $month === 1 ? 12 : $month - 1; $prevYear = $month === 1 ? $year - 1 : $year;
$nextMonth = $month === 12 ? 1 : $month + 1; $nextYear = $month === 12 ? $year + 1 : $year;
?>
<section class="calendar-toolbar">
    <a class="btn btn-success btn-sm" href="<?= url('/calendario?mes=' . $month . '&ano=' . ($year - 1)) ?>">Ano-</a>
    <a class="btn btn-success btn-sm" href="<?= url('/calendario?mes=' . $prevMonth . '&ano=' . $prevYear) ?>">Mês-</a>
    <a class="btn btn-success btn-sm" href="<?= url('/calendario?mes=' . $nextMonth . '&ano=' . $nextYear) ?>">Mês+</a>
    <a class="btn btn-success btn-sm" href="<?= url('/calendario?mes=' . $month . '&ano=' . ($year + 1)) ?>">Ano+</a>
</section>
<p class="month-label"><?= e(Format::monthName($month) . ' ' . $year) ?></p>
<section class="calendar-panel">
    <table class="calendar-table">
        <thead><tr><th>Domingo</th><th>Segunda</th><th>Terça</th><th>Quarta</th><th>Quinta</th><th>Sexta</th><th>Sábado</th></tr></thead>
        <tbody>
        <?php foreach ($calendar['weeks'] as $week): ?>
            <tr>
            <?php foreach ($week as $day): ?>
                <td>
                    <?php if ($day): ?>
                        <a class="day-pill" href="<?= url('/linha-a-linha?data=' . urlencode($day['date'])) ?>">DIA <?= str_pad((string)$day['day'], 2, '0', STR_PAD_LEFT) ?></a>
                        <?php if ($day['batidas'] > 0): ?><div>Batidas: <?= e($day['batidas']) ?></div><?php endif; ?>
                        <?php if ($day['cadastros'] > 0): ?><div>Cadastro: <?= e($day['cadastros']) ?></div><?php endif; ?>
                    <?php endif; ?>
                </td>
            <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
