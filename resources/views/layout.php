<?php
// Layout file for the AFD reader application
// Expects $viewFile variable to be set by the controller
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leitor de AFD</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css?v=20260609-mini-v2" rel="stylesheet">
</head>
<body>
    <?php $currentPage = $_GET['page'] ?? 'upload'; ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-purple">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php?page=upload">Leitor de AFD</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php if (isset($_SESSION['user'])): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'upload' ? 'active' : ''; ?>" href="index.php?page=upload">Início</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'arquivo' ? 'active' : ''; ?>" href="index.php?page=arquivo">Arquivo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'empresa' ? 'active' : ''; ?>" href="index.php?page=empresa">Empresa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'usuarios' ? 'active' : ''; ?>" href="index.php?page=usuarios">Usuários</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'linhas' ? 'active' : ''; ?>" href="index.php?page=linhas">Linha a Linha</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <?php if (isset($_SESSION['user'])): ?>
                    <li class="nav-item">
                        <span class="navbar-text me-3">Usuário: <?php echo htmlspecialchars($_SESSION['user']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=logout">Sair</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <?php
        // Include the requested view within the layout. If the view file is
        // missing, display a simple error message.
        if (isset($viewFile) && file_exists($viewFile)) {
            include $viewFile;
        } else {
            echo '<div class="alert alert-warning">View não encontrada.</div>';
        }
        ?>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>