<?php
namespace App\Services;

/**
 * Exporta uma planilha de fechamento mensal seguindo a estrutura da planilha
 * enviada como referência pelo usuário.
 *
 * Agora aceita filtro de colaboradores, período e colunas. Na tela Usuários,
 * somente o que for marcado pelo usuário é enviado para a exportação.
 */
class FolhaPontoExportService
{
    private const COLUMN_MAP = [
        'nome' => 'COLABORADOR',
        'adiantamento' => 'ADIANTAMENTO',
        'periculosidade' => 'PERICULOSIDADE',
        'gratificacao' => 'GRATIFICAÇÃO',
        'extra50' => 'H. EXTRA 50%',
        'extra100' => 'H. EXTRA 100%',
        'faltaHoras' => 'FALTA HORAS',
        'meioPeriodo' => 'MEIO PERIODO',
        'datasMeioPeriodo' => 'DATA MEIO PERIODO',
        'faltas' => 'FALTAS',
        'datasFalta' => 'DATA DA FALTA',
        'descTransporte' => 'DESC. V. TRANSP.',
        'planoSaude' => 'PLANO DE SAUDE',
        'valeAlimentacao' => 'V. ALIMENTAÇÃO',
        'observacoes' => 'OBSERVAÇÕES',
    ];

    private const DEFAULT_COLUMNS = [
        'nome',
        'adiantamento',
        'periculosidade',
        'gratificacao',
        'extra50',
        'extra100',
        'faltaHoras',
        'meioPeriodo',
        'datasMeioPeriodo',
        'faltas',
        'datasFalta',
        'descTransporte',
        'planoSaude',
        'valeAlimentacao',
        'observacoes',
    ];

    public static function availableColumns(): array
    {
        return self::COLUMN_MAP;
    }

    public function export(array $parsed, int $mes, int $ano, string $outputPath, array $options = []): void
    {
        $options = $this->withEmpresaNome($parsed, $options);

        if (!class_exists(\ZipArchive::class)) {
            $this->exportXlsXml($parsed, $mes, $ano, preg_replace('/\.xlsx$/i', '.xls', $outputPath), $options);
            return;
        }

        $columns = $this->resolveColumns($options['columns'] ?? []);
        $rows = $this->buildRows($parsed, $mes, $ano, $options);
        $sheetXml = $this->buildSheetXml($rows, $mes, $ano, $columns, $options);
        $this->writeXlsx($outputPath, $sheetXml);
    }

    public function exportXlsXml(array $parsed, int $mes, int $ano, string $outputPath, array $options = []): void
    {
        $options = $this->withEmpresaNome($parsed, $options);
        $columns = $this->resolveColumns($options['columns'] ?? []);
        $rows = $this->buildRows($parsed, $mes, $ano, $options);
        $titulo = $this->sheetTitle($mes, $ano, $options);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
        $xml .= '<Styles>';
        $xml .= '<Style ss:ID="Default"><Alignment ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/></Borders><Font ss:FontName="Calibri" ss:Size="11"/></Style>';
        $xml .= '<Style ss:ID="Title"><Font ss:Bold="1" ss:Size="14"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        $xml .= '<Style ss:ID="Header"><Interior ss:Color="#111827" ss:Pattern="Solid"/><Font ss:Bold="1" ss:Color="#FFFFFF"/><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/></Style>';
        $xml .= '<Style ss:ID="Name"><Font ss:Color="#111827"/></Style>';
        $xml .= '<Style ss:ID="Obs"><Interior ss:Color="#F3F4F6" ss:Pattern="Solid"/><Font ss:Bold="1"/></Style>';
        $xml .= '</Styles>';
        $xml .= '<Worksheet ss:Name="CONTROLE DE PONTO"><Table>';
        foreach ($columns as $key) {
            $xml .= '<Column ss:Width="' . ($key === 'nome' || $key === 'observacoes' ? '240' : '95') . '"/>';
        }
        $xml .= '<Row/>';
        $mergeAcross = max(0, count($columns) - 1);
        $xml .= '<Row><Cell ss:MergeAcross="' . $mergeAcross . '" ss:StyleID="Title"><Data ss:Type="String">' . $this->xml($titulo) . '</Data></Cell></Row>';
        $xml .= '<Row>';
        foreach ($columns as $key) {
            $xml .= '<Cell ss:StyleID="Header"><Data ss:Type="String">' . $this->xml(self::COLUMN_MAP[$key]) . '</Data></Cell>';
        }
        $xml .= '</Row>';

        foreach ($rows as $row) {
            $xml .= '<Row>';
            foreach ($columns as $key) {
                $value = $row[$key] ?? '';
                $style = $key === 'nome' ? ' ss:StyleID="Name"' : '';
                $type = is_numeric($value) && $value !== '' ? 'Number' : 'String';
                $xml .= '<Cell' . $style . '><Data ss:Type="' . $type . '">' . $this->xml((string)$value) . '</Data></Cell>';
            }
            $xml .= '</Row>';
        }

        $xml .= '<Row/><Row><Cell ss:MergeAcross="' . $mergeAcross . '" ss:StyleID="Obs"><Data ss:Type="String">OBSERVAÇÃO : PLANILHA GERADA PELO LEITOR DE AFD. CONFERIR COLUNAS MANUAIS ANTES DO FECHAMENTO.</Data></Cell></Row>';
        $xml .= '</Table><WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><FreezePanes/><FrozenNoSplit/><SplitHorizontal>3</SplitHorizontal><TopRowBottomPane>3</TopRowBottomPane></WorksheetOptions></Worksheet></Workbook>';

        $this->ensureWritableOutput($outputPath);
        $bytes = @file_put_contents($outputPath, $xml);
        if ($bytes === false) {
            throw new \RuntimeException('Não foi possível gravar o arquivo de exportação em: ' . $outputPath);
        }
    }

    public function buildRows(array $parsed, int $mes, int $ano, array $options = []): array
    {
        $usuarios = $parsed['usuarios'] ?? [];
        $jornadaService = new JornadaService();
        $espelhoService = new EspelhoPontoService();
        $rows = [];
        $coverage = $this->coverageForMonth($parsed, $mes, $ano, $options);

        if ($coverage === null) {
            return [];
        }

        [$periodStart, $periodEnd] = $coverage;
        $selectedPis = $this->selectedPisMap($options['pis'] ?? []);
        $hasSelectedPis = !empty($selectedPis);
        $requireSelected = !empty($options['require_selected']);
        $semRegistroMode = $this->normalizeSemRegistroMode((string)($options['sem_registro'] ?? 'skip'));

        // Segurança: quando a exportação vem da tela com filtro, nunca exporta
        // todos por fallback caso nenhum checkbox tenha sido enviado.
        if ($requireSelected && !$hasSelectedPis) {
            return [];
        }

        foreach ($usuarios as $pis => $usuario) {
            $pis = (string)$pis;
            if (trim($pis) === '') {
                continue;
            }

            if ($hasSelectedPis || $requireSelected) {
                if (!isset($selectedPis[$pis])) {
                    continue;
                }
            } elseif (empty($usuario['ativo'])) {
                continue;
            }

            $usuarioStart = $this->usuarioStartDate($usuario);
            $usuarioEnd = $this->usuarioEndDate($usuario);

            // Não exporta funcionário que ainda não existia no período ou que já havia sido excluído antes dele.
            if ($usuarioStart !== null && $usuarioStart > $periodEnd) {
                continue;
            }
            if ($usuarioEnd !== null && $usuarioEnd < $periodStart) {
                continue;
            }

            $effectiveStart = $this->maxDate($periodStart, $usuarioStart ?? $periodStart);
            $effectiveEnd = $periodEnd;
            if ($usuarioEnd !== null) {
                $effectiveEnd = $this->minDate($periodEnd, $usuarioEnd);
            }

            if ($effectiveStart > $effectiveEnd) {
                continue;
            }

            $nome = preg_replace('/\s+/', ' ', trim((string)($usuario['nome'] ?? $pis))) ?: $pis;
            $hasMarksInPeriod = $this->usuarioHasMarksInRange($pis, $usuario, $effectiveStart, $effectiveEnd);

            if (!$hasMarksInPeriod) {
                if ($semRegistroMode === 'skip') {
                    continue;
                }

                if ($semRegistroMode === 'zero') {
                    $rows[] = $this->blankRow($pis, $nome, 'SEM REGISTRO NO PERÍODO');
                    continue;
                }

                // Modo falta: segue para o cálculo do espelho e considera falta integral no período efetivo.
            }

            $jornada = $jornadaService->get($pis);
            $espelho = $espelhoService->gerar($parsed, $pis, $mes, $ano, $jornada);
            $espelhoRows = $this->filterRowsByDateRange($espelho['rows'] ?? [], $effectiveStart, $effectiveEnd);
            $resumo = $this->summarizeEspelho($espelhoRows);
            $observacao = $this->buildObservacao($resumo);

            if (!$hasMarksInPeriod && $semRegistroMode === 'falta') {
                $observacao = trim($observacao . ($observacao !== '' ? ' | ' : '') . 'SEM REGISTRO NO PERÍODO');
            }

            $rows[] = [
                'pis' => $pis,
                'nome' => $nome,
                'adiantamento' => '',
                'periculosidade' => '',
                'gratificacao' => '',
                'extra50' => $this->minutesToSheetText($resumo['extra50']),
                'extra100' => $this->minutesToSheetText($resumo['extra100']),
                'faltaHoras' => $this->minutesToSheetText($resumo['faltaHoras']),
                'meioPeriodo' => $resumo['meioPeriodo'] > 0 ? $resumo['meioPeriodo'] : '',
                'datasMeioPeriodo' => implode(' E ', $resumo['datasMeioPeriodo']),
                'faltas' => $resumo['faltas'] > 0 ? $resumo['faltas'] : '',
                'datasFalta' => implode(' E ', $resumo['datasFalta']),
                'descTransporte' => '',
                'planoSaude' => '',
                'valeAlimentacao' => '',
                'observacoes' => $observacao,
            ];
        }

        usort($rows, static function ($a, $b) {
            return strcasecmp((string)$a['nome'], (string)$b['nome']);
        });

        return $rows;
    }

    private function normalizeSemRegistroMode(string $mode): string
    {
        return in_array($mode, ['skip', 'zero', 'falta'], true) ? $mode : 'skip';
    }

    private function usuarioHasMarksInRange(string $pis, array $usuario, string $start, string $end): bool
    {
        foreach (($usuario['marcacoes'] ?? []) as $m) {
            $data = (string)($m['data'] ?? '');
            if ($this->isIsoDate($data) && $data >= $start && $data <= $end) {
                return true;
            }
        }

        foreach ((new MarcacaoManualService())->forPis($pis) as $data => $ajuste) {
            $batidas = is_array($ajuste) ? ($ajuste['batidas'] ?? []) : [];
            $data = (string)$data;
            if ($this->isIsoDate($data) && $data >= $start && $data <= $end && is_array($batidas) && count($batidas) > 0) {
                return true;
            }
        }

        return false;
    }

    private function blankRow(string $pis, string $nome, string $observacao = ''): array
    {
        return [
            'pis' => $pis,
            'nome' => $nome,
            'adiantamento' => '',
            'periculosidade' => '',
            'gratificacao' => '',
            'extra50' => '',
            'extra100' => '',
            'faltaHoras' => '',
            'meioPeriodo' => '',
            'datasMeioPeriodo' => '',
            'faltas' => '',
            'datasFalta' => '',
            'descTransporte' => '',
            'planoSaude' => '',
            'valeAlimentacao' => '',
            'observacoes' => $observacao,
        ];
    }

    private function resolveColumns($columns): array
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }

        $allowed = array_keys(self::COLUMN_MAP);
        $selected = [];
        foreach ($columns as $column) {
            $column = (string)$column;
            if (in_array($column, $allowed, true) && !in_array($column, $selected, true)) {
                $selected[] = $column;
            }
        }

        if (!$selected) {
            $selected = self::DEFAULT_COLUMNS;
        }

        // Colaborador é obrigatório para a planilha ter identificação.
        if (!in_array('nome', $selected, true)) {
            array_unshift($selected, 'nome');
        }

        return $selected;
    }

    private function selectedPisMap($selectedPis): array
    {
        if (!is_array($selectedPis)) {
            $selectedPis = [$selectedPis];
        }

        $map = [];
        foreach ($selectedPis as $pis) {
            $pis = trim((string)$pis);
            if ($pis !== '') {
                $map[$pis] = true;
            }
        }

        return $map;
    }

    private function coverageForMonth(array $parsed, int $mes, int $ano, array $options = []): ?array
    {
        $monthStart = sprintf('%04d-%02d-01', $ano, $mes);
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        $start = $monthStart;
        $end = $monthEnd;

        $dateStart = $this->validDateOrNull((string)($options['date_start'] ?? ''));
        $dateEnd = $this->validDateOrNull((string)($options['date_end'] ?? ''));

        if ($dateStart !== null) {
            $start = $this->maxDate($start, $dateStart);
        }
        if ($dateEnd !== null) {
            $end = $this->minDate($end, $dateEnd);
        }

        return $start <= $end ? [$start, $end] : null;
    }

    private function afdCoverage(array $parsed): array
    {
        $datas = [];

        foreach (($parsed['marcacoes'] ?? []) as $m) {
            $data = (string)($m['data'] ?? '');
            if ($this->isIsoDate($data)) {
                $datas[] = $data;
            }
        }

        foreach (['dataPrimeiroNsr', 'dataUltimoNsr'] as $key) {
            $data = (string)($parsed['arquivo'][$key] ?? '');
            if ($this->isIsoDate($data)) {
                $datas[] = $data;
            }
        }

        foreach (['dataInicio', 'dataFim'] as $key) {
            $data = (string)($parsed['empresa'][$key] ?? '');
            if ($this->isIsoDate($data)) {
                $datas[] = $data;
            }
        }

        if (!$datas) {
            return [null, null];
        }

        sort($datas);
        return [$datas[0], $datas[count($datas) - 1]];
    }

    private function usuarioStartDate(array $usuario): ?string
    {
        $datas = [];

        foreach (($usuario['eventos'] ?? []) as $evento) {
            $operacao = (string)($evento['operacao'] ?? '');
            $data = (string)($evento['data'] ?? '');
            if ($operacao === 'I' && $this->isIsoDate($data)) {
                $datas[] = $data;
            }
        }

        foreach (($usuario['marcacoes'] ?? []) as $m) {
            $data = (string)($m['data'] ?? '');
            if ($this->isIsoDate($data)) {
                $datas[] = $data;
            }
        }

        if (!$datas) {
            return null;
        }

        sort($datas);
        return $datas[0];
    }

    private function usuarioEndDate(array $usuario): ?string
    {
        $eventos = $usuario['eventos'] ?? [];
        if (!$eventos) {
            return null;
        }

        usort($eventos, static function ($a, $b) {
            $da = (string)($a['datetime'] ?? (($a['data'] ?? '') . ' ' . ($a['hora'] ?? '')));
            $db = (string)($b['datetime'] ?? (($b['data'] ?? '') . ' ' . ($b['hora'] ?? '')));
            return strcmp($da, $db);
        });

        $ultimo = $eventos[count($eventos) - 1];
        $data = (string)($ultimo['data'] ?? '');
        return (($ultimo['operacao'] ?? '') === 'E' && $this->isIsoDate($data)) ? $data : null;
    }

    private function filterRowsByDateRange(array $rows, string $start, string $end): array
    {
        return array_values(array_filter($rows, function ($row) use ($start, $end) {
            $data = (string)($row['data_iso'] ?? '');
            return $this->isIsoDate($data) && $data >= $start && $data <= $end;
        }));
    }

    private function validDateOrNull(string $data): ?string
    {
        $data = trim($data);
        return $this->isIsoDate($data) ? $data : null;
    }

    private function isIsoDate(string $data): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) === 1;
    }

    private function maxDate(string $a, string $b): string
    {
        return $a >= $b ? $a : $b;
    }

    private function minDate(string $a, string $b): string
    {
        return $a <= $b ? $a : $b;
    }

    private function summarizeEspelho(array $rows): array
    {
        $resumo = [
            'extra50' => 0,
            'extra100' => 0,
            'faltaHoras' => 0,
            'meioPeriodo' => 0,
            'datasMeioPeriodo' => [],
            'faltas' => 0,
            'datasFalta' => [],
            'compensado' => 0,
        ];

        foreach ($rows as $row) {
            $dow = isset($row['data_iso']) ? (int)date('N', strtotime((string)$row['data_iso'])) : 0;
            $dataBr = (string)($row['data'] ?? '');
            $faltaMinutos = (int)($row['falta_minutos'] ?? 0);
            $extraMinutos = (int)($row['extra_minutos'] ?? 0);
            $trabalhou = trim((string)($row['tempo'] ?? '')) !== '';

            if ($extraMinutos > 0) {
                if ($dow === 7) {
                    $resumo['extra100'] += $extraMinutos;
                } else {
                    $resumo['extra50'] += $extraMinutos;
                }
            }

            if ($faltaMinutos > 0) {
                $resumo['faltaHoras'] += $faltaMinutos;

                if ($trabalhou) {
                    $resumo['meioPeriodo']++;
                    if ($dataBr !== '') {
                        $resumo['datasMeioPeriodo'][] = $dataBr;
                    }
                } else {
                    $resumo['faltas']++;
                    if ($dataBr !== '') {
                        $resumo['datasFalta'][] = $dataBr;
                    }
                }
            }
        }

        return $this->compensarExtrasEFaltas($resumo);
    }

    private function compensarExtrasEFaltas(array $resumo): array
    {
        $totalExtra = (int)($resumo['extra50'] ?? 0) + (int)($resumo['extra100'] ?? 0);
        $totalFalta = (int)($resumo['faltaHoras'] ?? 0);

        if ($totalExtra <= 0 || $totalFalta <= 0) {
            return $resumo;
        }

        $compensado = min($totalExtra, $totalFalta);
        $restanteParaAbater = $compensado;

        $abateExtra50 = min((int)$resumo['extra50'], $restanteParaAbater);
        $resumo['extra50'] -= $abateExtra50;
        $restanteParaAbater -= $abateExtra50;

        if ($restanteParaAbater > 0) {
            $abateExtra100 = min((int)$resumo['extra100'], $restanteParaAbater);
            $resumo['extra100'] -= $abateExtra100;
            $restanteParaAbater -= $abateExtra100;
        }

        $resumo['faltaHoras'] = max(0, $totalFalta - $compensado);
        $resumo['compensado'] = ((int)($resumo['compensado'] ?? 0)) + $compensado;

        if ($resumo['faltaHoras'] === 0) {
            $resumo['meioPeriodo'] = 0;
            $resumo['datasMeioPeriodo'] = [];
            $resumo['faltas'] = 0;
            $resumo['datasFalta'] = [];
        }

        return $resumo;
    }

    private function buildObservacao(array $resumo): string
    {
        $obs = [];
        if ($resumo['faltas'] > 0) {
            $obs[] = $resumo['faltas'] . ($resumo['faltas'] === 1 ? ' FALTA' : ' FALTAS');
        }
        if ($resumo['meioPeriodo'] > 0) {
            $obs[] = $resumo['meioPeriodo'] . ($resumo['meioPeriodo'] === 1 ? ' MEIO PERÍODO' : ' MEIOS PERÍODOS');
        }
        if ($resumo['extra50'] > 0) {
            $obs[] = 'H. EXTRA 50% ' . $this->minutesToSheetText($resumo['extra50']);
        }
        if ($resumo['extra100'] > 0) {
            $obs[] = 'H. EXTRA 100% ' . $this->minutesToSheetText($resumo['extra100']);
        }
        if (($resumo['compensado'] ?? 0) > 0) {
            $obs[] = 'SALDO COMPENSADO ' . $this->minutesToSheetText((int)$resumo['compensado']);
        }

        return implode(' | ', $obs);
    }

    private function minutesToSheetText(int $minutes): string
    {
        return $minutes > 0 ? JornadaService::minutesToHour($minutes) : '';
    }

    private function sheetTitle(int $mes, int $ano, array $options = []): string
    {
        $meses = [
            1 => 'JANEIRO', 2 => 'FEVEREIRO', 3 => 'MARÇO', 4 => 'ABRIL',
            5 => 'MAIO', 6 => 'JUNHO', 7 => 'JULHO', 8 => 'AGOSTO',
            9 => 'SETEMBRO', 10 => 'OUTUBRO', 11 => 'NOVEMBRO', 12 => 'DEZEMBRO',
        ];

        $titulo = 'FOLHA DE PONTO ' . ($meses[$mes] ?? $mes) . ' ' . $ano;
        $empresaNome = $this->normalizeTitlePart((string)($options['empresa_nome'] ?? ''));
        if ($empresaNome !== '') {
            $titulo .= ' - ' . $empresaNome;
        }

        $dateStart = $this->validDateOrNull((string)($options['date_start'] ?? ''));
        $dateEnd = $this->validDateOrNull((string)($options['date_end'] ?? ''));

        if ($dateStart !== null || $dateEnd !== null) {
            $inicio = $dateStart ? date('d/m/Y', strtotime($dateStart)) : 'INÍCIO DO MÊS';
            $fim = $dateEnd ? date('d/m/Y', strtotime($dateEnd)) : 'FIM DO MÊS';
            $titulo .= ' - PERÍODO: ' . $inicio . ' A ' . $fim;
        }

        return $titulo;
    }

    private function withEmpresaNome(array $parsed, array $options): array
    {
        if (!empty($options['empresa_nome'])) {
            $options['empresa_nome'] = $this->normalizeTitlePart((string)$options['empresa_nome']);
            return $options;
        }

        $empresa = $parsed['empresa'] ?? [];
        if (!is_array($empresa)) {
            return $options;
        }

        foreach (['nome', 'razaoSocial', 'razao_social', 'razao', 'empregador'] as $key) {
            $value = $this->normalizeTitlePart((string)($empresa[$key] ?? ''));
            if ($value !== '') {
                $options['empresa_nome'] = $value;
                break;
            }
        }

        return $options;
    }

    private function normalizeTitlePart(string $value): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);
        if ($value === '') {
            return '';
        }

        return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
    }

    private function buildSheetXml(array $rows, int $mes, int $ano, array $columns, array $options = []): string
    {
        $titulo = $this->sheetTitle($mes, $ano, $options);
        $columnCount = count($columns);
        $lastColumn = $this->columnName($columnCount);
        $mergeTitleRef = 'A2:' . $lastColumn . '2';
        $mergeObsRef = 'A' . (count($rows) + 5) . ':' . $lastColumn . (count($rows) + 5);

        $sheetData = [];
        $sheetData[] = $this->rowXml(1, []);
        $sheetData[] = $this->rowXml(2, [$titulo], [1]);
        $headerValues = array_map(static fn ($key) => self::COLUMN_MAP[$key], $columns);
        $sheetData[] = $this->rowXml(3, $headerValues, array_fill(0, $columnCount, 2));

        $r = 4;
        foreach ($rows as $row) {
            $values = [];
            $styles = [];
            foreach ($columns as $key) {
                $values[] = $row[$key] ?? '';
                $styles[] = $key === 'nome' ? 3 : 0;
            }
            $sheetData[] = $this->rowXml($r++, $values, $styles);
        }

        $obsRow = $r + 1;
        $sheetData[] = $this->rowXml($obsRow, ['OBSERVAÇÃO : PLANILHA GERADA PELO LEITOR DE AFD. CONFERIR COLUNAS MANUAIS ANTES DO FECHAMENTO.'], [4]);

        $colsXml = '';
        foreach ($columns as $i => $key) {
            $indexCol = $i + 1;
            $width = ($key === 'nome' || $key === 'observacoes') ? 38 : 16;
            $colsXml .= '<col min="' . $indexCol . '" max="' . $indexCol . '" width="' . $width . '" customWidth="1"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
            '<dimension ref="A1:' . $lastColumn . $obsRow . '"/>' .
            '<sheetViews><sheetView workbookViewId="0"><pane ySplit="3" topLeftCell="A4" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>' .
            '<cols>' . $colsXml . '</cols>' .
            '<sheetData>' . implode('', $sheetData) . '</sheetData>' .
            '<mergeCells count="2"><mergeCell ref="' . $mergeTitleRef . '"/><mergeCell ref="' . $mergeObsRef . '"/></mergeCells>' .
            '</worksheet>';
    }

    private function rowXml(int $rowNumber, array $values, ?array $styles = null): string
    {
        $cells = '';
        foreach ($values as $i => $value) {
            if ($value === null) {
                continue;
            }
            $col = $this->columnName($i + 1);
            $style = $styles[$i] ?? 0;
            $ref = $col . $rowNumber;
            if (is_int($value) || is_float($value)) {
                $cells .= '<c r="' . $ref . '" s="' . $style . '"><v>' . $value . '</v></c>';
            } else {
                $cells .= '<c r="' . $ref . '" s="' . $style . '" t="inlineStr"><is><t>' . $this->xml((string)$value) . '</t></is></c>';
            }
        }

        return '<row r="' . $rowNumber . '">' . $cells . '</row>';
    }

    private function writeXlsx(string $outputPath, string $sheetXml): void
    {
        $this->ensureWritableOutput($outputPath);

        $zip = new \ZipArchive();
        if ($zip->open($outputPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Não foi possível criar o arquivo XLSX.');
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>');

        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>');

        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<sheets><sheet name="CONTROLE DE PONTO" sheetId="1" r:id="rId1"/></sheets>
</workbook>');

        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>');

        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->addFromString('docProps/core.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:creator>Leitor de AFD</dc:creator><cp:lastModifiedBy>Leitor de AFD</cp:lastModifiedBy><dcterms:created xsi:type="dcterms:W3CDTF">' . gmdate('Y-m-d\TH:i:s\Z') . '</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">' . gmdate('Y-m-d\TH:i:s\Z') . '</dcterms:modified></cp:coreProperties>');
        $zip->addFromString('docProps/app.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>Leitor de AFD</Application></Properties>');
        $zip->close();
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<fonts count="5"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="14"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font><font><color rgb="FF111827"/><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>
<fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF111827"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFF3F4F6"/><bgColor indexed="64"/></patternFill></fill></fills>
<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"/><right style="thin"/><top style="thin"/><bottom style="thin"/><diagonal/></border></borders>
<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
<cellXfs count="5"><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="3" fillId="0" borderId="1" xfId="0" applyFont="1"/><xf numFmtId="0" fontId="4" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1"/></cellXfs>
<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>';
    }

    private function ensureWritableOutput(string $outputPath): void
    {
        $dir = dirname($outputPath);

        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Não foi possível criar a pasta de exportação: ' . $dir);
        }

        if (!is_writable($dir)) {
            throw new \RuntimeException('Sem permissão de escrita na pasta de exportação: ' . $dir);
        }

        if (is_file($outputPath) && !is_writable($outputPath)) {
            throw new \RuntimeException('Sem permissão para sobrescrever o arquivo de exportação: ' . $outputPath);
        }
    }

    private function columnName(int $index): string
    {
        $name = '';
        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)) . $name;
            $index = intdiv($index, 26);
        }
        return $name;
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
