CREATE DATABASE IF NOT EXISTS afd_reader CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE afd_reader;

CREATE TABLE IF NOT EXISTS system_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    username VARCHAR(80) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO system_users (name, username, password_hash, active, created_at, updated_at)
VALUES ('Usuário Padrão', 'admin', '$2y$12$xGfNsnV0IpkOM9zopDmHo.OqUJrlTPNvMCBcqjlHH1ixY/cTsIj8q', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), active = VALUES(active), updated_at = NOW();

CREATE TABLE IF NOT EXISTS empresas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo_empregador VARCHAR(20) NULL,
    cnpj_cpf VARCHAR(20) NULL,
    cno_caepf VARCHAR(20) NULL,
    razao_social VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uk_empresas_cnpj_cpf (cnpj_cpf),
    KEY idx_empresas_razao_social (razao_social)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS relogios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id BIGINT UNSIGNED NOT NULL,
    serial VARCHAR(60) NULL,
    tipo_fabricante VARCHAR(20) NULL,
    cnpj_cpf_fabricante VARCHAR(20) NULL,
    modelo VARCHAR(120) NULL,
    layout VARCHAR(20) NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uk_relogios_empresa_serial (empresa_id, serial),
    KEY idx_relogios_serial (serial),
    KEY idx_relogios_fabricante (cnpj_cpf_fabricante),
    CONSTRAINT fk_relogios_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS afd_arquivos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id BIGINT UNSIGNED NULL,
    relogio_id BIGINT UNSIGNED NULL,
    nome_original VARCHAR(255) NOT NULL,
    nome_armazenado VARCHAR(255) NULL,
    caminho_armazenado VARCHAR(500) NULL,
    tamanho_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
    hash_sha256 CHAR(64) NOT NULL,
    primeiro_nsr VARCHAR(20) NULL,
    data_primeiro_nsr DATE NULL,
    ultimo_nsr VARCHAR(20) NULL,
    data_ultimo_nsr DATE NULL,
    numero_linhas INT UNSIGNED NOT NULL DEFAULT 0,
    integridade VARCHAR(80) NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uk_afd_arquivos_hash (hash_sha256),
    KEY idx_afd_arquivos_empresa (empresa_id),
    KEY idx_afd_arquivos_relogio (relogio_id),
    KEY idx_afd_arquivos_nsr (primeiro_nsr, ultimo_nsr),
    KEY idx_afd_arquivos_datas (data_primeiro_nsr, data_ultimo_nsr),
    CONSTRAINT fk_afd_arquivos_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE SET NULL,
    CONSTRAINT fk_afd_arquivos_relogio FOREIGN KEY (relogio_id) REFERENCES relogios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuarios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id BIGINT UNSIGNED NOT NULL,
    arquivo_id BIGINT UNSIGNED NULL,
    pis_cpf VARCHAR(20) NULL,
    nome VARCHAR(255) NULL,
    status ENUM('ativo','excluido','indefinido') NOT NULL DEFAULT 'ativo',
    carga_horaria VARCHAR(10) NULL DEFAULT '44:00',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    KEY idx_usuarios_empresa (empresa_id),
    KEY idx_usuarios_arquivo (arquivo_id),
    KEY idx_usuarios_pis_cpf (pis_cpf),
    KEY idx_usuarios_nome (nome),
    CONSTRAINT fk_usuarios_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_usuarios_arquivo FOREIGN KEY (arquivo_id) REFERENCES afd_arquivos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS marcacoes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    arquivo_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NULL,
    nsr VARCHAR(20) NULL,
    data_marcacao DATE NULL,
    hora_marcacao TIME NULL,
    pis_cpf VARCHAR(20) NULL,
    origem VARCHAR(30) NOT NULL DEFAULT 'arquivo',
    linha_numero INT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    KEY idx_marcacoes_arquivo (arquivo_id),
    KEY idx_marcacoes_usuario (usuario_id),
    KEY idx_marcacoes_pis_data (pis_cpf, data_marcacao),
    KEY idx_marcacoes_nsr (nsr),
    KEY idx_marcacoes_data (data_marcacao),
    CONSTRAINT fk_marcacoes_arquivo FOREIGN KEY (arquivo_id) REFERENCES afd_arquivos(id) ON DELETE CASCADE,
    CONSTRAINT fk_marcacoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS eventos_cadastro (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    arquivo_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NULL,
    nsr VARCHAR(20) NULL,
    data_evento DATE NULL,
    hora_evento TIME NULL,
    pis_cpf VARCHAR(20) NULL,
    nome VARCHAR(255) NULL,
    tipo ENUM('inclusao','alteracao','exclusao','indefinido') NULL DEFAULT 'indefinido',
    descricao VARCHAR(255) NULL,
    linha_numero INT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    KEY idx_eventos_cadastro_arquivo (arquivo_id),
    KEY idx_eventos_cadastro_usuario (usuario_id),
    KEY idx_eventos_cadastro_pis (pis_cpf),
    KEY idx_eventos_cadastro_nsr (nsr),
    KEY idx_eventos_cadastro_data (data_evento),
    CONSTRAINT fk_eventos_cadastro_arquivo FOREIGN KEY (arquivo_id) REFERENCES afd_arquivos(id) ON DELETE CASCADE,
    CONSTRAINT fk_eventos_cadastro_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS eventos_empresa (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    arquivo_id BIGINT UNSIGNED NOT NULL,
    nsr VARCHAR(20) NULL,
    data_evento DATE NULL,
    hora_evento TIME NULL,
    descricao VARCHAR(255) NULL,
    linha_numero INT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    KEY idx_eventos_empresa_arquivo (arquivo_id),
    KEY idx_eventos_empresa_nsr (nsr),
    KEY idx_eventos_empresa_data (data_evento),
    CONSTRAINT fk_eventos_empresa_arquivo FOREIGN KEY (arquivo_id) REFERENCES afd_arquivos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS eventos_operacionais (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    arquivo_id BIGINT UNSIGNED NOT NULL,
    nsr VARCHAR(20) NULL,
    data_evento DATE NULL,
    hora_evento TIME NULL,
    descricao VARCHAR(255) NULL,
    linha_numero INT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    KEY idx_eventos_operacionais_arquivo (arquivo_id),
    KEY idx_eventos_operacionais_nsr (nsr),
    KEY idx_eventos_operacionais_data (data_evento),
    CONSTRAINT fk_eventos_operacionais_arquivo FOREIGN KEY (arquivo_id) REFERENCES afd_arquivos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS alteracoes_horario (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    arquivo_id BIGINT UNSIGNED NOT NULL,
    nsr VARCHAR(20) NULL,
    data_evento DATE NULL,
    hora_evento TIME NULL,
    descricao VARCHAR(255) NULL,
    linha_numero INT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    KEY idx_alteracoes_horario_arquivo (arquivo_id),
    KEY idx_alteracoes_horario_nsr (nsr),
    KEY idx_alteracoes_horario_data (data_evento),
    CONSTRAINT fk_alteracoes_horario_arquivo FOREIGN KEY (arquivo_id) REFERENCES afd_arquivos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS afd_linhas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    arquivo_id BIGINT UNSIGNED NOT NULL,
    linha_numero INT UNSIGNED NOT NULL,
    nsr VARCHAR(20) NULL,
    tipo_registro VARCHAR(80) NULL,
    codigo_tipo VARCHAR(10) NULL,
    data_registro DATE NULL,
    hora_registro TIME NULL,
    pis_cpf VARCHAR(20) NULL,
    nome VARCHAR(255) NULL,
    descricao VARCHAR(255) NULL,
    conteudo_original TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'ok',
    erros_json JSON NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    KEY idx_afd_linhas_arquivo (arquivo_id),
    KEY idx_afd_linhas_nsr (nsr),
    KEY idx_afd_linhas_pis (pis_cpf),
    KEY idx_afd_linhas_data (data_registro),
    KEY idx_afd_linhas_tipo (codigo_tipo),
    CONSTRAINT fk_afd_linhas_arquivo FOREIGN KEY (arquivo_id) REFERENCES afd_arquivos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS feriados (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    data_feriado DATE NOT NULL,
    descricao VARCHAR(160) NOT NULL,
    tipo ENUM('nacional','estadual','municipal','empresa') NOT NULL DEFAULT 'empresa',
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uk_feriados_data_descricao (data_feriado, descricao),
    KEY idx_feriados_data (data_feriado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS justificativas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id BIGINT UNSIGNED NOT NULL,
    data_referencia DATE NOT NULL,
    tipo VARCHAR(80) NOT NULL,
    descricao TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    KEY idx_justificativas_usuario_data (usuario_id, data_referencia),
    CONSTRAINT fk_justificativas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS configuracoes_jornada (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id BIGINT UNSIGNED NULL,
    usuario_id BIGINT UNSIGNED NULL,
    carga_semanal_minutos INT UNSIGNED NOT NULL DEFAULT 2640,
    carga_diaria_minutos INT UNSIGNED NOT NULL DEFAULT 480,
    tolerancia_minutos INT UNSIGNED NOT NULL DEFAULT 10,
    dias_uteis VARCHAR(30) NOT NULL DEFAULT '1,2,3,4,5',
    sabado_util TINYINT(1) NOT NULL DEFAULT 0,
    domingo_util TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    KEY idx_configuracoes_empresa (empresa_id),
    KEY idx_configuracoes_usuario (usuario_id),
    CONSTRAINT fk_configuracoes_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_configuracoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
