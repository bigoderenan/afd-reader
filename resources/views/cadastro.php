<?php
$nome = preg_replace('/\s+/', ' ', trim((string)($usuario['nome'] ?? $pis))) ?: $pis;
function cadastroEventoLabel(array $evento): string {
    $op = $evento['operacao'] ?? '';
    $nomeEv = preg_replace('/\s+/', ' ', trim((string)($evento['nome'] ?? '')));
    return match ($op) {
        'I' => 'Inclusão de cadastro' . ($nomeEv ? ' - ' . $nomeEv : ''),
        'A' => 'Alteração de cadastro' . ($nomeEv ? ' - ' . $nomeEv : ''),
        'E' => 'Exclusão de cadastro' . ($nomeEv ? ' - ' . $nomeEv : ''),
        default => 'Evento de cadastro' . ($nomeEv ? ' - ' . $nomeEv : ''),
    };
}
function cadastroFormatDate(string $date): string {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return date('d/m/Y', strtotime($date));
    }
    return $date;
}
?>

<div class="afd-section-title">
    Edições no cadastro de:
    <span class="badge bg-success ms-2 me-2"><?php echo htmlspecialchars($nome); ?></span>
    - PIS: <?php echo htmlspecialchars($pis); ?>
</div>

<div class="table-responsive">
    <table class="table table-dark table-striped table-sm align-middle">
        <thead>
            <tr>
                <th>Nro</th>
                <th>NSR.</th>
                <th>Data</th>
                <th>Hora</th>
                <th>Evento</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$eventos): ?>
                <tr><td colspan="5">Nenhuma edição de cadastro encontrada.</td></tr>
            <?php else: ?>
                <?php $i = 1; foreach ($eventos as $ev): ?>
                    <tr>
                        <td><?php echo $i++; ?>.</td>
                        <td><?php echo str_pad((string)($ev['nsr'] ?? ''), 9, '0', STR_PAD_LEFT); ?></td>
                        <td><?php echo htmlspecialchars(cadastroFormatDate((string)($ev['data'] ?? ''))); ?></td>
                        <td><?php echo htmlspecialchars((string)($ev['hora'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars(cadastroEventoLabel($ev)); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<p class="mt-3"><a class="btn btn-secondary btn-sm" href="index.php?page=usuarios">Voltar para usuários</a></p>
