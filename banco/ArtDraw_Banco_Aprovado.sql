-- ============================================================
-- ARTDRAW - MODELAGEM DO BANCO DE DADOS APROVADA
-- MySQL 8.3 / InnoDB / utf8mb4
--
-- IMPORTANTE:
-- 1. Este arquivo e destinado a uma INSTALACAO LIMPA do banco.
-- 2. Ele NAO e um script de migracao para um banco ja existente.
-- 3. Faca backup antes de qualquer substituicao do banco atual.
-- 4. A conta admin sera criada separadamente, por procedimento privado.
-- 5. Cadastro publico deve aceitar somente os perfis aluno e professor.
--
-- REGRAS APROVADAS:
-- - Conta excluida e desativada logicamente (status='excluido').
-- - Publicacoes do usuario excluido permanecem no banco e mantem autoria.
-- - Professor bloqueado nao tem suas publicacoes exibidas aos usuarios.
-- - Categorias e niveis utilizados nao podem ser apagados fisicamente;
--   devem ser desativados (ativo=0).
-- - Uma publicacao possui no maximo uma midia principal:
--   imagem armazenada no servidor OU URL do YouTube.
-- ============================================================

CREATE DATABASE IF NOT EXISTS sistema_artdraw
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_0900_ai_ci;

USE sistema_artdraw;

-- ============================================================
-- 1. USUARIOS
-- Perfis: aluno, professor e admin.
-- O administrador e provisionado manualmente; nunca pelo cadastro publico.
-- status='excluido' representa exclusao logica, nao DELETE fisico.
-- data_exclusao registra quando a conta foi desativada/excluida.
-- ============================================================

CREATE TABLE usuarios (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    apelido VARCHAR(50) NOT NULL,
    email VARCHAR(150) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo_usuario ENUM('aluno', 'professor', 'admin') NOT NULL DEFAULT 'aluno',
    foto_perfil VARCHAR(255) DEFAULT NULL,
    biografia TEXT DEFAULT NULL,
    status ENUM('ativo', 'bloqueado', 'excluido') NOT NULL DEFAULT 'ativo',
    data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    data_exclusao DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_email (email),
    KEY idx_usuarios_apelido (apelido),
    KEY idx_usuarios_tipo_status (tipo_usuario, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================================
-- 2. CATEGORIAS
-- Gerenciadas exclusivamente pelo administrador.
-- Categorias em uso devem ser desativadas (ativo=0), nao apagadas.
-- ============================================================

CREATE TABLE categorias (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categorias_nome (nome),
    KEY idx_categorias_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================================
-- 3. NIVEIS
-- Gerenciados exclusivamente pelo administrador.
-- ordem controla a ordem de exibicao.
-- Niveis em uso devem ser desativados, nao apagados.
-- ============================================================

CREATE TABLE niveis (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(50) NOT NULL,
    ordem TINYINT UNSIGNED NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_niveis_nome (nome),
    UNIQUE KEY uq_niveis_ordem (ordem),
    KEY idx_niveis_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================================
-- 4. CONTEUDOS
-- Cada conteudo possui autor, categoria, nivel e tipo.
-- A midia principal e opcional e unica: imagem local OU YouTube.
-- O autor nao pode ser apagado fisicamente enquanto houver conteudos.
-- Para excluir uma conta, use exclusao logica em usuarios.
-- ============================================================

CREATE TABLE conteudos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    titulo VARCHAR(150) NOT NULL,
    descricao TEXT NOT NULL,
    conteudo LONGTEXT NOT NULL,
    tipo_conteudo ENUM('aula', 'dica', 'trabalho') NOT NULL,
    tipo_midia ENUM('imagem', 'youtube') DEFAULT NULL,
    midia_url VARCHAR(500) DEFAULT NULL,
    autor_id INT UNSIGNED NOT NULL,
    categoria_id INT UNSIGNED NOT NULL,
    nivel_id INT UNSIGNED NOT NULL,
    data_publicacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_conteudos_autor (autor_id),
    KEY idx_conteudos_categoria (categoria_id),
    KEY idx_conteudos_nivel (nivel_id),
    KEY idx_conteudos_tipo (tipo_conteudo),
    KEY idx_conteudos_data (data_publicacao),
    KEY idx_conteudos_filtros (nivel_id, categoria_id, tipo_conteudo),
    CONSTRAINT fk_conteudos_autor
        FOREIGN KEY (autor_id) REFERENCES usuarios (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_conteudos_categoria
        FOREIGN KEY (categoria_id) REFERENCES categorias (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_conteudos_nivel
        FOREIGN KEY (nivel_id) REFERENCES niveis (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_conteudos_midia
        CHECK (
            (tipo_midia IS NULL AND midia_url IS NULL)
            OR
            (tipo_midia IS NOT NULL AND midia_url IS NOT NULL AND midia_url <> '')
        )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================================
-- 5. LINKS COMPLEMENTARES
-- Um conteudo pode ter zero, um ou varios links complementares.
-- ============================================================

CREATE TABLE links_complementares (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    conteudo_id INT UNSIGNED NOT NULL,
    titulo VARCHAR(150) DEFAULT NULL,
    url VARCHAR(1000) NOT NULL,
    ordem SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY idx_links_conteudo (conteudo_id),
    CONSTRAINT fk_links_conteudo
        FOREIGN KEY (conteudo_id) REFERENCES conteudos (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================================
-- 6. CURTIDAS
-- Um aluno ou professor pode curtir cada conteudo apenas uma vez.
-- Visitantes nao possuem conta/sessao e nao podem curtir.
-- ============================================================

CREATE TABLE curtidas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id INT UNSIGNED NOT NULL,
    conteudo_id INT UNSIGNED NOT NULL,
    data_curtida DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_curtidas_usuario_conteudo (usuario_id, conteudo_id),
    KEY idx_curtidas_conteudo (conteudo_id),
    CONSTRAINT fk_curtidas_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_curtidas_conteudo
        FOREIGN KEY (conteudo_id) REFERENCES conteudos (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================================
-- 7. SALVAMENTOS
-- Um aluno ou professor pode salvar cada conteudo apenas uma vez.
-- Excluir logicamente o usuario nao apaga seus salvamentos nem os de outros.
-- ============================================================

CREATE TABLE salvamentos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id INT UNSIGNED NOT NULL,
    conteudo_id INT UNSIGNED NOT NULL,
    data_salvamento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_salvamentos_usuario_conteudo (usuario_id, conteudo_id),
    KEY idx_salvamentos_conteudo (conteudo_id),
    CONSTRAINT fk_salvamentos_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_salvamentos_conteudo
        FOREIGN KEY (conteudo_id) REFERENCES conteudos (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================================
-- 8. RECUPERACOES DE SENHA
-- Armazena somente o hash SHA-256 do token enviado por e-mail.
-- O token original nao fica armazenado no banco.
-- ============================================================

CREATE TABLE recuperacoes_senha (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expira_em DATETIME NOT NULL,
    usado_em DATETIME DEFAULT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_recuperacoes_token_hash (token_hash),
    KEY idx_recuperacoes_usuario (usuario_id),
    KEY idx_recuperacoes_expiracao (expira_em),
    CONSTRAINT fk_recuperacoes_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================================
-- 9. DADOS INICIAIS: CATEGORIAS
-- ============================================================

INSERT INTO categorias (nome) VALUES
('Desenho técnico'),
('Desenho à mão livre'),
('Anatomia e figura humana'),
('Perspectiva'),
('Luz e sombra'),
('Teoria das cores'),
('Retrato'),
('Paisagem e natureza'),
('Ilustração'),
('Desenho digital');

-- ============================================================
-- 10. DADOS INICIAIS: NIVEIS
-- ============================================================

INSERT INTO niveis (nome, ordem) VALUES
('Iniciante', 1),
('Intermediário', 2),
('Avançado', 3);

-- ============================================================
-- REGRAS DE APLICACAO IMPORTANTES
--
-- 1. Exclusao de usuario:
--    Nao executar DELETE em usuarios para remover uma conta normal.
--    Atualizar status para 'excluido', preencher data_exclusao e
--    anonimizar os dados pessoais. A publicacao continua ligada ao id
--    original do autor e permanece disponivel, conforme a regra definida.
--
-- 2. Bloqueio de professor:
--    Atualizar status para 'bloqueado'. Consultas publicas devem ocultar
--    conteudos cujo autor esteja bloqueado. Ao reativar a conta, os
--    conteudos voltam a aparecer.
--
-- 3. Categorias e niveis:
--    Se houver conteudos vinculados, alterar ativo para 0 em vez de
--    executar DELETE. A aplicacao deve impedir novas selecoes de itens
--    inativos em publicacoes novas ou editadas.
--
-- 4. Cadastro publico:
--    Permitir somente tipo_usuario='aluno' ou 'professor'. O perfil admin
--    deve ser criado por procedimento administrativo privado.
-- ============================================================
