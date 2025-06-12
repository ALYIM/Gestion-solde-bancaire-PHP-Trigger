-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mer. 19 fév. 2025 à 14:25
-- Version du serveur : 10.4.28-MariaDB
-- Version de PHP : 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `banque`
--

-- --------------------------------------------------------

--
-- Structure de la table `audit_compte`
--

CREATE TABLE `audit_compte` (
  `id` int(11) NOT NULL,
  `type_action` enum('ajout','modification','suppression') DEFAULT NULL,
  `date_mise_a_jour` timestamp NOT NULL DEFAULT current_timestamp(),
  `num_compte` int(11) DEFAULT NULL,
  `nom_client` varchar(100) DEFAULT NULL,
  `solde_ancien` decimal(10,2) DEFAULT NULL,
  `solde_nouv` decimal(10,2) DEFAULT NULL,
  `utilisateur` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `audit_compte`
--

INSERT INTO `audit_compte` (`id`, `type_action`, `date_mise_a_jour`, `num_compte`, `nom_client`, `solde_ancien`, `solde_nouv`, `utilisateur`) VALUES
(1, 'ajout', '2025-02-17 15:15:51', 1, 'ddd', NULL, -0.01, 'admin'),
(2, 'ajout', '2025-02-17 15:28:36', 2, 'ddd', NULL, -0.01, 'admin'),
(3, 'ajout', '2025-02-17 16:06:40', 3, 'ddd', NULL, -0.01, 'admin'),
(4, 'ajout', '2025-02-17 16:13:08', 4, 'ddd', NULL, -0.01, 'admin'),
(5, 'ajout', '2025-02-17 16:19:22', 5, 'zaza', NULL, 4444.08, 'admin'),
(6, 'modification', '2025-02-17 16:19:48', 1, 'ddd', -0.01, 4555.00, 'admin'),
(7, 'modification', '2025-02-17 16:22:48', 1, 'ddd', 4555.00, 4555.00, 'admin'),
(8, 'modification', '2025-02-17 16:22:52', 1, 'ddd', 4555.00, 4555.00, 'admin'),
(9, 'modification', '2025-02-17 16:32:17', 1, 'ddd', 4555.00, 4555.00, 'admin'),
(10, 'ajout', '2025-02-17 16:39:03', 6, 'rakoto', NULL, 5000.00, 'admin'),
(11, 'modification', '2025-02-17 16:41:03', 6, 'rakoto', 5000.00, 100000.00, 'admin'),
(12, 'suppression', '2025-02-17 16:41:45', 1, 'ddd', 4555.00, NULL, 'admin'),
(13, 'suppression', '2025-02-17 16:41:49', 2, 'ddd', -0.01, NULL, 'admin'),
(14, 'suppression', '2025-02-17 16:41:52', 3, 'ddd', -0.01, NULL, 'admin'),
(15, 'suppression', '2025-02-17 16:41:54', 4, 'ddd', -0.01, NULL, 'admin'),
(16, 'suppression', '2025-02-17 16:41:57', 5, 'zaza', 4444.08, NULL, 'admin'),
(17, 'suppression', '2025-02-17 16:41:59', 6, 'rakoto', 100000.00, NULL, 'admin'),
(18, 'ajout', '2025-02-17 16:43:06', 7, '1', NULL, 3000.00, 'admin'),
(19, 'suppression', '2025-02-17 16:51:19', 7, '1', 3000.00, NULL, 'admin'),
(20, 'ajout', '2025-02-17 16:51:29', 8, 'bao', NULL, 20000.00, 'admin'),
(21, 'ajout', '2025-02-17 17:09:09', 9, 'bema', NULL, 14000.00, 'admin'),
(22, 'modification', '2025-02-17 17:09:34', 9, 'bema', 14000.00, 900000.00, 'admin'),
(23, 'ajout', '2025-02-17 17:17:16', 10, 'Chynna', NULL, 1000000.00, 'admin'),
(26, 'ajout', '2025-02-18 20:50:57', 12, 'rabe', NULL, 1200000.00, 'admin'),
(27, 'ajout', '2025-02-18 20:52:10', 13, 'rabe', NULL, 1200000.00, 'admin'),
(28, 'ajout', '2025-02-18 20:52:17', 14, 'rabe', NULL, 1200000.00, 'admin'),
(29, 'ajout', '2025-02-18 20:52:40', 15, 'rabe', NULL, 1200000.00, 'admin'),
(30, 'ajout', '2025-02-18 20:53:16', 16, 'rabe', NULL, 1200000.00, 'admin'),
(31, 'ajout', '2025-02-18 20:53:43', 17, 'rabe', NULL, 1200000.00, 'admin'),
(32, 'ajout', '2025-02-18 20:54:12', 18, 'rabe', NULL, 1200000.00, 'admin'),
(33, 'modification', '2025-02-18 20:55:01', 10, 'Chynna', 1000000.00, 45000000.00, 'admin');

-- --------------------------------------------------------

--
-- Structure de la table `compte`
--

CREATE TABLE `compte` (
  `num_compte` int(11) NOT NULL,
  `nom_client` varchar(100) DEFAULT NULL,
  `solde` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `compte`
--

INSERT INTO `compte` (`num_compte`, `nom_client`, `solde`) VALUES
(8, 'bao', 20000.00),
(9, 'bema', 900000.00),
(10, 'Chynna', 45000000.00),
(11, 'Mialy', 15000000.00),
(12, 'rabe', 1200000.00),
(13, 'rabe', 1200000.00),
(14, 'rabe', 1200000.00),
(15, 'rabe', 1200000.00),
(16, 'rabe', 1200000.00),
(17, 'rabe', 1200000.00),
(18, 'rabe', 1200000.00);

--
-- Déclencheurs `compte`
--
DELIMITER $$
CREATE TRIGGER `after_delete_compte` AFTER DELETE ON `compte` FOR EACH ROW BEGIN
    INSERT INTO audit_compte (type_action, num_compte, nom_client, solde_ancien, utilisateur)
    VALUES ('suppression', OLD.num_compte, OLD.nom_client, OLD.solde, 'admin');
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_insert_compte` AFTER INSERT ON `compte` FOR EACH ROW BEGIN
    INSERT INTO audit_compte (type_action, num_compte, nom_client, solde_nouv, utilisateur)
    VALUES ('ajout', NEW.num_compte, NEW.nom_client, NEW.solde, 'admin');
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_update_compte` AFTER UPDATE ON `compte` FOR EACH ROW BEGIN
    INSERT INTO audit_compte (type_action, num_compte, nom_client, solde_ancien, solde_nouv, utilisateur)
    VALUES ('modification', OLD.num_compte, OLD.nom_client, OLD.solde, NEW.solde, 'admin');
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `roles`
--

INSERT INTO `roles` (`id`, `name`) VALUES
(1, 'Admin'),
(2, 'Utilisateur');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role_id`) VALUES
(1, 'admin', '$2y$10$uns3z/IvmE/uRwPOmc6QKODOXd/uWb/DDkFMEkRv0JfMIP9nrnfmC', 1),
(2, 'mialy', '$2y$10$qm.3VtMVNv04g9NfX355oOEs68WBb.R1MJk//TWWpOa4AnsgpE3Ry', 2),
(3, 'lucas', '$2y$10$sufJ3o1kzDgm8M/eb5CuYep57F0MGaqAHkYFTYLdCGnV/WQkDq/f2', 1),
(5, 'bidy', '$2y$10$RYwTs0icSFBCHz4zFlTCC.SlcYyy4MXczvEW0.Ssb2k4HrSTVDTaG', 2);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `audit_compte`
--
ALTER TABLE `audit_compte`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `compte`
--
ALTER TABLE `compte`
  ADD PRIMARY KEY (`num_compte`);

--
-- Index pour la table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `audit_compte`
--
ALTER TABLE `audit_compte`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT pour la table `compte`
--
ALTER TABLE `compte`
  MODIFY `num_compte` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT pour la table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
