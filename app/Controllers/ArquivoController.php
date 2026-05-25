<?php
namespace App\Controllers;

use App\Core\Controller;

/**
 * Class ArquivoController
 *
 * Displays a summary of the imported AFD file, including basic
 * statistics such as number of lines, first and last NSR, counts of
 * each type of record and a simple integrity check on the NSR
 * sequence. The controller reads cached data from
 * storage/cache/import_data.json generated during upload.
 */
class ArquivoController extends Controller
{
    public function index(): void
    {
        $cachePath = __DIR__ . '/../../storage/cache/import_data.json';
        if (!file_exists($cachePath)) {
            $_SESSION['upload_message'] = 'Nenhum arquivo foi importado ainda.';
            header('Location: index.php?page=upload');
            exit;
        }
        $parsed = json_decode(file_get_contents($cachePath), true);
        if (!$parsed) {
            $_SESSION['upload_message'] = 'Erro ao ler os dados importados.';
            header('Location: index.php?page=upload');
            exit;
        }
        // Compute statistics
        $nsrs = array_column($parsed['linhas'], 'nsr');
        sort($nsrs);
        $integridade = 'Arquivo íntegro';
        for ($i = 1; $i < count($nsrs); $i++) {
            if ($nsrs[$i] !== $nsrs[$i - 1] + 1) {
                $integridade = 'Possíveis quebras ou duplicidades de NSR';
                break;
            }
        }
        // Count employee events by operation
        $inclusoes = 0;
        $alteracoesCad = 0;
        $exclusoes = 0;
        foreach ($parsed['eventosCadastro'] as $cad) {
            if ($cad['operacao'] === 'I') {
                $inclusoes++;
            } elseif ($cad['operacao'] === 'A') {
                $alteracoesCad++;
            } elseif ($cad['operacao'] === 'E') {
                $exclusoes++;
            }
        }
        $summary = [
            'nomeArquivo'      => $parsed['arquivo']['nome'] ?? '',
            'tamanhoArquivo'   => $parsed['arquivo']['tamanho'] ?? 0,
            'primeiroNsr'      => $parsed['arquivo']['primeiroNsr'] ?? null,
            'ultimoNsr'        => $parsed['arquivo']['ultimoNsr'] ?? null,
            'dataPrimeiroNsr'  => $parsed['arquivo']['dataPrimeiroNsr'] ?? null,
            'dataUltimoNsr'    => $parsed['arquivo']['dataUltimoNsr'] ?? null,
            'numeroLinhas'     => $parsed['arquivo']['numeroLinhas'] ?? 0,
            'integridade'      => $integridade,
            'edicoesEmpresa'   => count($parsed['eventosEmpresa']),
            'marcacoes'        => count($parsed['marcacoes']),
            'alteracoesHorario'=> count($parsed['alteracoesHorario']),
            'inclusoes'        => $inclusoes,
            'alteracoesCad'    => $alteracoesCad,
            'exclusoes'        => $exclusoes,
            'registrosOperacionais' => count($parsed['eventosOperacionais'] ?? []),
            'dataInicio'       => $parsed['empresa']['dataInicio'] ?? '',
            'dataFim'          => $parsed['empresa']['dataFim'] ?? '',
        ];
        $this->render('arquivo', [
            'summary' => $summary,
            'message' => $_SESSION['upload_message'] ?? null,
        ]);
    }
}