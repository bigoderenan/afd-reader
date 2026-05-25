<?php

declare(strict_types=1);

namespace App\Services;

final class DemoDataService
{
    public function make(): array
    {
        $users = [
            ['pisCpf' => '002096264103', 'nome' => 'ADRIANO MORENO', 'status' => 'ativo', 'cargaHoraria' => '44:00'],
            ['pisCpf' => '009900936116', 'nome' => 'ALESSANDRO CURCINO', 'status' => 'ativo', 'cargaHoraria' => '44:00'],
            ['pisCpf' => '004569363199', 'nome' => 'ALESSANDRO DA SILVA', 'status' => 'ativo', 'cargaHoraria' => '44:00'],
            ['pisCpf' => '070731990161', 'nome' => 'SAMUEL RODRIGUES DA SILVA', 'status' => 'ativo', 'cargaHoraria' => '44:00'],
            ['pisCpf' => '000000000011', 'nome' => 'RENAN TI', 'status' => 'excluido', 'cargaHoraria' => '44:00'],
        ];

        $marcacoes = [];
        $nsr = 100;
        foreach ($users as $idx => $user) {
            if ($user['status'] === 'excluido') {
                continue;
            }
            for ($day = 4; $day <= 21; $day++) {
                $weekday = (int)date('w', strtotime("2026-05-$day"));
                if ($weekday === 0 || $weekday === 6) {
                    continue;
                }
                if ($day === 18 && $idx === 0) {
                    continue;
                }
                $times = ['06:57', '11:57', '12:56', '17:00'];
                if ($idx % 2 === 1) {
                    $times = ['07:02', '12:00', '13:00', '17:06'];
                }
                foreach ($times as $time) {
                    $marcacoes[] = [
                        'nsr' => str_pad((string)$nsr++, 9, '0', STR_PAD_LEFT),
                        'data' => sprintf('2026-05-%02d', $day),
                        'hora' => $time,
                        'pisCpf' => $user['pisCpf'],
                        'nome' => $user['nome'],
                        'origem' => 'demo',
                        'linhaNumero' => $nsr,
                    ];
                }
            }
        }

        $events = [
            ['nsr' => '000000168', 'data' => '2026-04-07', 'hora' => '14:42', 'pisCpf' => '002096264103', 'nome' => 'ADRIANO MORENO', 'tipo' => 'inclusao', 'descricao' => 'Inclusão de cadastro - ADRIANO MORENO'],
            ['nsr' => '000000190', 'data' => '2026-04-11', 'hora' => '10:05', 'pisCpf' => '009900936116', 'nome' => 'ALESSANDRO CURCINO', 'tipo' => 'alteracao', 'descricao' => 'Alteração de cadastro - ALESSANDRO CURCINO'],
            ['nsr' => '000000221', 'data' => '2026-05-01', 'hora' => '08:10', 'pisCpf' => '000000000011', 'nome' => 'RENAN TI', 'tipo' => 'exclusao', 'descricao' => 'Exclusão de cadastro - RENAN TI'],
        ];

        $linhas = [];
        foreach ($marcacoes as $i => $m) {
            $linhas[] = [
                'linha' => $i + 1,
                'nsr' => $m['nsr'],
                'tipo' => 'marcacao',
                'tipoRegistro' => 'Marcação de ponto',
                'data' => $m['data'],
                'hora' => $m['hora'],
                'pisCpf' => $m['pisCpf'],
                'nome' => $m['nome'],
                'descricao' => 'Marcação de ponto',
                'conteudoOriginal' => $m['nsr'] . '5' . date('dmY', strtotime($m['data'])) . str_replace(':', '', $m['hora']) . $m['pisCpf'],
                'status' => 'ok',
                'erros' => [],
            ];
        }

        return [
            'modoDemo' => true,
            'arquivo' => [
                'nomeOriginal' => 'DEMO_AFD_2026.txt',
                'nomeArmazenado' => null,
                'tamanhoBytes' => 109 * 1024,
                'tamanhoLegivel' => '109 kb',
                'hashSha256' => hash('sha256', 'demo-afd-reader'),
                'primeiroNsr' => '000000100',
                'dataPrimeiroNsr' => '2026-04-07',
                'ultimoNsr' => str_pad((string)(100 + count($marcacoes) + count($events)), 9, '0', STR_PAD_LEFT),
                'dataUltimoNsr' => '2026-05-21',
                'numeroLinhas' => count($linhas),
                'integridade' => 'Arquivo íntegro',
                'quebrasNsr' => [],
                'duplicidadesNsr' => [],
                'contadores' => [
                    'eventosEmpresa' => 3,
                    'marcacoes' => count($marcacoes),
                    'alteracoesHorario' => 2,
                    'inclusoesEmpregado' => 1,
                    'alteracoesEmpregado' => 1,
                    'exclusoesEmpregado' => 1,
                    'eventosOperacionais' => 20,
                ],
            ],
            'empresa' => [
                'tipoEmpregador' => 'CNPJ',
                'cnpjCpf' => '35973853000194',
                'cnoCaepf' => '00000000000000',
                'razaoSocial' => 'THN CONSTRUTORA - INFINITY TOWER',
                'dataInicial' => '2026-03-10',
                'dataFinal' => '2026-05-21',
                'dataHoraGeracao' => '2026-05-21T17:01:00-03:00',
            ],
            'relogio' => [
                'serial' => '00014008880012118',
                'tipoFabricante' => 'CNPJ',
                'cnpjCpfFabricante' => '08238299000129',
                'modelo' => 'iDClass Facial Prox',
                'layout' => '003',
            ],
            'usuarios' => $users,
            'marcacoes' => $marcacoes,
            'eventosCadastro' => $events,
            'eventosEmpresa' => [
                ['nsr' => '000000010', 'data' => '2026-03-10', 'hora' => '09:00', 'descricao' => 'Dados do empregador informados no cabeçalho'],
            ],
            'alteracoesHorario' => [
                ['nsr' => '000000090', 'data' => '2026-04-05', 'hora' => '07:40', 'descricao' => 'Ajuste manual de data/hora do relógio'],
            ],
            'eventosOperacionais' => [],
            'linhas' => $linhas,
            'erros' => [],
            'avisos' => ['Dados fictícios carregados para demonstração visual e funcional.'],
        ];
    }
}
