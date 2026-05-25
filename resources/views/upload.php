<?php if (isset($message) && $message): ?>
    <div class="alert alert-info" role="alert">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<h2 class="header-section">Importar Arquivo AFD</h2>
<div class="card bg-dark text-light shadow-sm">
    <div class="card-body">
        <form method="post" action="index.php?page=upload_process" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="afd_file" class="form-label">Selecionar arquivo .txt</label>
                <input class="form-control" type="file" id="afd_file" name="afd_file" accept=".txt" required>
            </div>
            <button type="submit" class="btn btn-green">Importar Arquivo</button>
        </form>
    </div>
</div>