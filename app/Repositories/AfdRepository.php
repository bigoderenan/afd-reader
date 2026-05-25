<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class AfdRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::pdo();
    }

    public function hashExists(string $hash): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM afd_arquivos WHERE hash_sha256 = :hash LIMIT 1');
        $stmt->execute(['hash' => $hash]);
        return (bool)$stmt->fetchColumn();
    }

    /** @return array{status:string,id:int|null,message:string} */
    public function save(array $data): array
    {
        $hash = (string)($data['arquivo']['hashSha256'] ?? '');
        if ($hash !== '' && $this->hashExists($hash)) {
            return ['status' => 'duplicado', 'id' => null, 'message' => 'Este arquivo já foi salvo anteriormente pelo hash SHA-256.'];
        }

        $this->pdo->beginTransaction();
        try {
            $empresaId = $this->insertEmpresa($data['empresa'] ?? []);
            $relogioId = $this->insertRelogio($empresaId, $data['relogio'] ?? []);
            $arquivoId = $this->insertArquivo($empresaId, $relogioId, $data['arquivo'] ?? []);
            $userIds = $this->insertUsuarios($arquivoId, $empresaId, $data['usuarios'] ?? []);
            $this->insertMarcacoes($arquivoId, $userIds, $data['marcacoes'] ?? []);
            $this->insertEventosCadastro($arquivoId, $userIds, $data['eventosCadastro'] ?? []);
            $this->insertSimpleEvents('eventos_empresa', $arquivoId, $data['eventosEmpresa'] ?? []);
            $this->insertSimpleEvents('eventos_operacionais', $arquivoId, $data['eventosOperacionais'] ?? []);
            $this->insertSimpleEvents('alteracoes_horario', $arquivoId, $data['alteracoesHorario'] ?? []);
            $this->insertLinhas($arquivoId, $data['linhas'] ?? []);
            $this->pdo->commit();

            return ['status' => 'ok', 'id' => $arquivoId, 'message' => 'Dados salvos com sucesso no banco.'];
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function insertEmpresa(array $empresa): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO empresas (tipo_empregador, cnpj_cpf, cno_caepf, razao_social, created_at, updated_at)
            VALUES (:tipo, :doc, :cno, :razao, NOW(), NOW())
            ON DUPLICATE KEY UPDATE razao_social = VALUES(razao_social), updated_at = NOW()');
        $stmt->execute([
            'tipo' => $empresa['tipoEmpregador'] ?? null,
            'doc' => $empresa['cnpjCpf'] ?? null,
            'cno' => $empresa['cnoCaepf'] ?? null,
            'razao' => $empresa['razaoSocial'] ?? null,
        ]);
        if ((int)$this->pdo->lastInsertId() > 0) {
            return (int)$this->pdo->lastInsertId();
        }
        $select = $this->pdo->prepare('SELECT id FROM empresas WHERE cnpj_cpf <=> :doc LIMIT 1');
        $select->execute(['doc' => $empresa['cnpjCpf'] ?? null]);
        return (int)$select->fetchColumn();
    }

    private function insertRelogio(int $empresaId, array $relogio): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO relogios (empresa_id, serial, tipo_fabricante, cnpj_cpf_fabricante, modelo, layout, created_at, updated_at)
            VALUES (:empresa_id, :serial, :tipo_fabricante, :cnpj_fabricante, :modelo, :layout, NOW(), NOW())
            ON DUPLICATE KEY UPDATE modelo = VALUES(modelo), layout = VALUES(layout), updated_at = NOW()');
        $stmt->execute([
            'empresa_id' => $empresaId,
            'serial' => $relogio['serial'] ?? null,
            'tipo_fabricante' => $relogio['tipoFabricante'] ?? null,
            'cnpj_fabricante' => $relogio['cnpjCpfFabricante'] ?? null,
            'modelo' => $relogio['modelo'] ?? null,
            'layout' => $relogio['layout'] ?? null,
        ]);
        if ((int)$this->pdo->lastInsertId() > 0) {
            return (int)$this->pdo->lastInsertId();
        }
        $select = $this->pdo->prepare('SELECT id FROM relogios WHERE serial <=> :serial AND empresa_id = :empresa_id LIMIT 1');
        $select->execute(['serial' => $relogio['serial'] ?? null, 'empresa_id' => $empresaId]);
        return (int)$select->fetchColumn();
    }

    private function insertArquivo(int $empresaId, int $relogioId, array $arquivo): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO afd_arquivos
            (empresa_id, relogio_id, nome_original, nome_armazenado, caminho_armazenado, tamanho_bytes, hash_sha256, primeiro_nsr, data_primeiro_nsr, ultimo_nsr, data_ultimo_nsr, numero_linhas, integridade, created_at, updated_at)
            VALUES (:empresa_id, :relogio_id, :nome_original, :nome_armazenado, :caminho, :tamanho, :hash, :primeiro_nsr, :data_primeiro, :ultimo_nsr, :data_ultimo, :numero_linhas, :integridade, NOW(), NOW())');
        $stmt->execute([
            'empresa_id' => $empresaId,
            'relogio_id' => $relogioId,
            'nome_original' => $arquivo['nomeOriginal'] ?? null,
            'nome_armazenado' => $arquivo['nomeArmazenado'] ?? null,
            'caminho' => $arquivo['caminhoArmazenado'] ?? null,
            'tamanho' => $arquivo['tamanhoBytes'] ?? 0,
            'hash' => $arquivo['hashSha256'] ?? null,
            'primeiro_nsr' => $arquivo['primeiroNsr'] ?? null,
            'data_primeiro' => $arquivo['dataPrimeiroNsr'] ?? null,
            'ultimo_nsr' => $arquivo['ultimoNsr'] ?? null,
            'data_ultimo' => $arquivo['dataUltimoNsr'] ?? null,
            'numero_linhas' => $arquivo['numeroLinhas'] ?? 0,
            'integridade' => $arquivo['integridade'] ?? null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /** @return array<string,int> */
    private function insertUsuarios(int $arquivoId, int $empresaId, array $usuarios): array
    {
        $ids = [];
        $stmt = $this->pdo->prepare('INSERT INTO usuarios (empresa_id, arquivo_id, pis_cpf, nome, status, carga_horaria, created_at, updated_at)
            VALUES (:empresa_id, :arquivo_id, :pis_cpf, :nome, :status, :carga_horaria, NOW(), NOW())');
        foreach ($usuarios as $user) {
            $stmt->execute([
                'empresa_id' => $empresaId,
                'arquivo_id' => $arquivoId,
                'pis_cpf' => $user['pisCpf'] ?? null,
                'nome' => $user['nome'] ?? null,
                'status' => $user['status'] ?? 'ativo',
                'carga_horaria' => $user['cargaHoraria'] ?? '44:00',
            ]);
            if (!empty($user['pisCpf'])) {
                $ids[$user['pisCpf']] = (int)$this->pdo->lastInsertId();
            }
        }
        return $ids;
    }

    private function insertMarcacoes(int $arquivoId, array $userIds, array $marcacoes): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO marcacoes (arquivo_id, usuario_id, nsr, data_marcacao, hora_marcacao, pis_cpf, origem, linha_numero, created_at, updated_at)
            VALUES (:arquivo_id, :usuario_id, :nsr, :data_marcacao, :hora_marcacao, :pis_cpf, :origem, :linha_numero, NOW(), NOW())');
        foreach ($marcacoes as $mark) {
            $stmt->execute([
                'arquivo_id' => $arquivoId,
                'usuario_id' => $userIds[$mark['pisCpf'] ?? ''] ?? null,
                'nsr' => $mark['nsr'] ?? null,
                'data_marcacao' => $mark['data'] ?? null,
                'hora_marcacao' => $mark['hora'] ?? null,
                'pis_cpf' => $mark['pisCpf'] ?? null,
                'origem' => $mark['origem'] ?? 'arquivo',
                'linha_numero' => $mark['linhaNumero'] ?? null,
            ]);
        }
    }

    private function insertEventosCadastro(int $arquivoId, array $userIds, array $events): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO eventos_cadastro (arquivo_id, usuario_id, nsr, data_evento, hora_evento, pis_cpf, nome, tipo, descricao, linha_numero, created_at, updated_at)
            VALUES (:arquivo_id, :usuario_id, :nsr, :data_evento, :hora_evento, :pis_cpf, :nome, :tipo, :descricao, :linha_numero, NOW(), NOW())');
        foreach ($events as $event) {
            $stmt->execute([
                'arquivo_id' => $arquivoId,
                'usuario_id' => $userIds[$event['pisCpf'] ?? ''] ?? null,
                'nsr' => $event['nsr'] ?? null,
                'data_evento' => $event['data'] ?? null,
                'hora_evento' => $event['hora'] ?? null,
                'pis_cpf' => $event['pisCpf'] ?? null,
                'nome' => $event['nome'] ?? null,
                'tipo' => $event['tipo'] ?? null,
                'descricao' => $event['descricao'] ?? null,
                'linha_numero' => $event['linhaNumero'] ?? null,
            ]);
        }
    }

    private function insertSimpleEvents(string $table, int $arquivoId, array $events): void
    {
        $allowed = ['eventos_empresa', 'eventos_operacionais', 'alteracoes_horario'];
        if (!in_array($table, $allowed, true)) {
            throw new \InvalidArgumentException('Tabela de eventos inválida.');
        }
        $stmt = $this->pdo->prepare("INSERT INTO {$table} (arquivo_id, nsr, data_evento, hora_evento, descricao, linha_numero, created_at, updated_at)
            VALUES (:arquivo_id, :nsr, :data_evento, :hora_evento, :descricao, :linha_numero, NOW(), NOW())");
        foreach ($events as $event) {
            $stmt->execute([
                'arquivo_id' => $arquivoId,
                'nsr' => $event['nsr'] ?? null,
                'data_evento' => $event['data'] ?? null,
                'hora_evento' => $event['hora'] ?? null,
                'descricao' => $event['descricao'] ?? null,
                'linha_numero' => $event['linhaNumero'] ?? null,
            ]);
        }
    }

    private function insertLinhas(int $arquivoId, array $linhas): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO afd_linhas (arquivo_id, linha_numero, nsr, tipo_registro, codigo_tipo, data_registro, hora_registro, pis_cpf, nome, descricao, conteudo_original, status, erros_json, created_at, updated_at)
            VALUES (:arquivo_id, :linha_numero, :nsr, :tipo_registro, :codigo_tipo, :data_registro, :hora_registro, :pis_cpf, :nome, :descricao, :conteudo_original, :status, :erros_json, NOW(), NOW())');
        foreach ($linhas as $line) {
            $stmt->execute([
                'arquivo_id' => $arquivoId,
                'linha_numero' => $line['linha'] ?? null,
                'nsr' => $line['nsr'] ?? null,
                'tipo_registro' => $line['tipoRegistro'] ?? null,
                'codigo_tipo' => $line['codigoTipo'] ?? null,
                'data_registro' => $line['data'] ?? null,
                'hora_registro' => $line['hora'] ?? null,
                'pis_cpf' => $line['pisCpf'] ?? null,
                'nome' => $line['nome'] ?? null,
                'descricao' => $line['descricao'] ?? null,
                'conteudo_original' => $line['conteudoOriginal'] ?? null,
                'status' => $line['status'] ?? 'ok',
                'erros_json' => json_encode($line['erros'] ?? [], JSON_UNESCAPED_UNICODE),
            ]);
        }
    }
}
