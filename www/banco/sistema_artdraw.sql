-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: mysql
-- Tempo de geração: 30/10/2026 às 11:05
-- Versão do servidor: 8.3.0
-- Versão do PHP: 8.3.36

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `sistema_artdraw`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id` int NOT NULL,
  `nome` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `categorias`
--

INSERT INTO `categorias` (`id`, `nome`) VALUES
(1, 'Desenho Técnico'),
(2, 'Mão livre');

-- --------------------------------------------------------

--
-- Estrutura para tabela `conteudos`
--

CREATE TABLE `conteudos` (
  `id` int NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descricao` text NOT NULL,
  `conteudo` longtext NOT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `nivel` varchar(20) NOT NULL,
  `autor_id` int NOT NULL,
  `categoria_id` int NOT NULL,
  `data_publicacao` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `curtidas`
--

CREATE TABLE `curtidas` (
  `id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `conteudo_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `salvamentos`
--

CREATE TABLE `salvamentos` (
  `id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `conteudo_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo_usuario` varchar(20) NOT NULL,
  `data_cadastro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome_UNIQUE` (`nome`);

--
-- Índices de tabela `conteudos`
--
ALTER TABLE `conteudos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_conteudos_usuarios_idx` (`autor_id`),
  ADD KEY `fk_conteudos_categorias_idx` (`categoria_id`);

--
-- Índices de tabela `curtidas`
--
ALTER TABLE `curtidas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_curtidas_usuario_conteudo` (`usuario_id`,`conteudo_id`),
  ADD KEY `fk_curtidas_conteudos_idx` (`conteudo_id`);

--
-- Índices de tabela `salvamentos`
--
ALTER TABLE `salvamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_salvamentos_usuario_conteudo` (`usuario_id`,`conteudo_id`),
  ADD KEY `fk_salvamentos_conteudos_idx` (`conteudo_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_UNIQUE` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `conteudos`
--
ALTER TABLE `conteudos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `curtidas`
--
ALTER TABLE `curtidas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `salvamentos`
--
ALTER TABLE `salvamentos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `conteudos`
--
ALTER TABLE `conteudos`
  ADD CONSTRAINT `fk_conteudos_categorias` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`),
  ADD CONSTRAINT `fk_conteudos_usuarios` FOREIGN KEY (`autor_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `curtidas`
--
ALTER TABLE `curtidas`
  ADD CONSTRAINT `fk_curtidas_conteudos` FOREIGN KEY (`conteudo_id`) REFERENCES `conteudos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_curtidas_usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `salvamentos`
--
ALTER TABLE `salvamentos`
  ADD CONSTRAINT `fk_salvamentos_conteudos` FOREIGN KEY (`conteudo_id`) REFERENCES `conteudos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_salvamentos_usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
