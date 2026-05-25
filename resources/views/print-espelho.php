<?php use App\Helpers\Format; $u = $espelho['user']; ?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Espelho de Ponto</title>
<style>
body{font-family:Arial,sans-serif;color:#111;background:#fff;margin:24px;font-size:12px}h1{font-size:18px;margin:0 0 8px}.meta{margin-bottom:16px;border-bottom:1px solid #222;padding-bottom:8px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #444;padding:4px;text-align:center}th{background:#eee}.totals{font-weight:bold;background:#f3f3f3}.text-left{text-align:left}@media print{button{display:none}body{margin:0}}
</style>
</head>
<body onload="window.print()">
<button onclick="window.print()">Imprimir</button>
<h1>Espelho de Ponto</h1>
<div class="meta">
    <div><strong>Empresa:</strong> <?= e($data['empresa']['razaoSocial'] ?? '--') ?> - <strong>CNPJ:</strong> <?= e($data['empresa']['cnpjCpf'] ?? '--') ?></div>
    <div><strong>Empregado:</strong> <?= e($u['nome'] ?? '--') ?> - <strong>PIS/CPF:</strong> <?= e($u['pisCpf'] ?? '--') ?></div>
    <div><strong>Período:</strong> <?= e(Format::monthName((int)$espelho['month']) . ' de ' . $espelho['year']) ?> - <strong>Tolerância:</strong> <?= e($espelho['tolerance']) ?> min</div>
</div>
<table>
<thead><tr><th>Data</th><th>Dia</th><th>Entrada</th><th>Saída</th><th>Entrada</th><th>Saída</th><th>Entrada</th><th>Saída</th><th>Tempo</th><th>Esperado</th><th>Comentário</th><th>Falta</th><th>Hora Extra</th></tr></thead>
<tbody>
<?php foreach ($espelho['rows'] as $row): ?>
<tr><td><?= e($row['dateBr']) ?></td><td><?= e($row['weekday']) ?></td><?php foreach ($row['times'] as $time): ?><td><?= e($time ?? '') ?></td><?php endforeach; ?><td><?= $row['worked'] ? e(Format::minutesToHm($row['worked'])) : '' ?></td><td><?= $row['expected'] ? e(Format::minutesToHm($row['expected'])) : '--' ?></td><td><?= e($row['comment']) ?></td><td><?= $row['lack'] ? e(Format::minutesToHm($row['lack'])) : '--' ?></td><td><?= $row['extra'] ? e(Format::minutesToHm($row['extra'])) : '--' ?></td></tr>
<?php endforeach; ?>
</tbody>
<tfoot><tr class="totals"><td colspan="8" class="text-left">Totais</td><td><?= e(Format::minutesToHm($espelho['totals']['trabalhado'])) ?></td><td></td><td></td><td><?= e(Format::minutesToHm($espelho['totals']['faltas'])) ?></td><td><?= e(Format::minutesToHm($espelho['totals']['extras'])) ?></td></tr></tfoot>
</table>
</body>
</html>
