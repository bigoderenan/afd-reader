<?php $flashes = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); ?>
<?php if (!empty($_SESSION['db_warning'])): ?>
    <div class="alert alert-warning alert-afd"><?= e($_SESSION['db_warning']) ?></div>
<?php endif; ?>
<?php foreach ($flashes as $flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-afd"><?= e($flash['message']) ?></div>
<?php endforeach; ?>
