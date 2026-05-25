<div class="auth-card">
    <div class="mini-logo mx-auto mb-3">MEU<br><span>REP</span></div>
    <h1>Leitor de AFD</h1>
    <p>Entre para importar, conferir e analisar arquivos AFD.</p>
    <?php require dirname(__DIR__) . '/partials/flash.php'; ?>
    <form method="post" action="<?= url('/login') ?>" class="mt-3">
        <?= csrf_field() ?>
        <label class="form-label">Usuário</label>
        <input class="form-control afd-input" name="username" value="admin" autocomplete="username" required>
        <label class="form-label mt-3">Senha</label>
        <input class="form-control afd-input" type="password" name="password" value="admin123" autocomplete="current-password" required>
        <button class="btn btn-success w-100 mt-4" type="submit">Entrar</button>
    </form>
    <small class="auth-hint">Padrão inicial: admin / admin123</small>
</div>
