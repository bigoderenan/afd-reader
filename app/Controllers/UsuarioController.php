<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\ImportSession;

final class UsuarioController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $data = ImportSession::requireData();
        $stats = $this->statsByUser($data);
        $this->view('usuarios', ['title' => 'Usuários', 'currentTab' => 'usuarios', 'data' => $data, 'stats' => $stats]);
    }

    public function cadastro(): void
    {
        $this->requireAuth();
        $data = ImportSession::requireData();
        $pis = (string)($_GET['pis'] ?? '');
        $user = $this->findUser($data, $pis);
        $events = array_values(array_filter($data['eventosCadastro'] ?? [], static fn($event) => ($event['pisCpf'] ?? '') === $pis));
        $this->view('cadastro', ['title' => 'Cadastro', 'currentTab' => 'usuarios', 'data' => $data, 'user' => $user, 'events' => $events]);
    }

    private function statsByUser(array $data): array
    {
        $stats = [];
        foreach ($data['marcacoes'] ?? [] as $mark) {
            $pis = $mark['pisCpf'] ?? '';
            if ($pis === '') {
                continue;
            }
            $stats[$pis]['count'] = ($stats[$pis]['count'] ?? 0) + 1;
            $date = $mark['data'] ?? null;
            if ($date) {
                $stats[$pis]['first'] = min($stats[$pis]['first'] ?? $date, $date);
                $stats[$pis]['last'] = max($stats[$pis]['last'] ?? $date, $date);
            }
        }
        return $stats;
    }

    private function findUser(array $data, string $pis): array
    {
        foreach ($data['usuarios'] ?? [] as $user) {
            if (($user['pisCpf'] ?? '') === $pis) {
                return $user;
            }
        }
        return ['pisCpf' => $pis, 'nome' => 'Usuário não identificado'];
    }
}
