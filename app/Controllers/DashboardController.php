<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\ImportSession;

final class DashboardController extends Controller
{
    public function arquivo(): void
    {
        $this->requireAuth();
        $data = ImportSession::requireData();
        $this->view('arquivo', ['title' => 'Arquivo', 'currentTab' => 'arquivo', 'data' => $data]);
    }
}
