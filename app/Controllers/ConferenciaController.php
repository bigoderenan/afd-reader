<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\ConferenciaService;
use App\Services\ImportSession;

final class ConferenciaController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $data = ImportSession::requireData();
        $reports = (new ConferenciaService())->runAll($data);
        $this->view('conferencias', ['title' => 'Conferências', 'currentTab' => 'conferencias', 'data' => $data, 'reports' => $reports]);
    }
}
