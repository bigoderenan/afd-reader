<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\CalendarioService;
use App\Services\ImportSession;

final class CalendarioController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $data = ImportSession::requireData();
        $month = max(1, min(12, (int)($_GET['mes'] ?? date('n'))));
        $year = (int)($_GET['ano'] ?? date('Y'));
        $calendar = (new CalendarioService())->build($data, $month, $year);
        $this->view('calendario', ['title' => 'Calendário', 'currentTab' => 'calendario', 'data' => $data, 'calendar' => $calendar]);
    }
}
