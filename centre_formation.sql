-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mar. 29 juil. 2025 à 15:39
-- Version du serveur : 8.2.0
-- Version de PHP : 8.2.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `centre_formation`
--

-- --------------------------------------------------------

--
-- Structure de la table `inscriptions`
--

DROP TABLE IF EXISTS `inscriptions`;
CREATE TABLE IF NOT EXISTS `inscriptions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sexe` enum('Masculin','Féminin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_naissance` date NOT NULL,
  `lieu_naissance` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nationalite` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `localite` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `indicatif` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `niveau_etude` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `diplome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `classe` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `formation` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_debut` date NOT NULL,
  `date_inscription` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `presences`
--

DROP TABLE IF EXISTS `presences`;
CREATE TABLE IF NOT EXISTS `presences` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int UNSIGNED NOT NULL,
  `date_presence` date NOT NULL,
  `arrivee_entreprise` time DEFAULT NULL,
  `depart_entreprise` time DEFAULT NULL,
  `arrivee_pause` time DEFAULT NULL,
  `depart_pause` time DEFAULT NULL,
  `timestamp_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_presence` (`utilisateur_id`,`date_presence`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `presences`
--

INSERT INTO `presences` (`id`, `utilisateur_id`, `date_presence`, `arrivee_entreprise`, `depart_entreprise`, `arrivee_pause`, `depart_pause`, `timestamp_creation`) VALUES
(1, 10, '2025-07-25', '17:17:44', '14:40:59', '14:39:47', '14:40:39', '2025-07-25 14:39:32'),
(2, 6, '2025-07-25', '14:45:34', NULL, NULL, NULL, '2025-07-25 14:45:22'),
(3, 11, '2025-07-25', '17:16:59', '17:12:23', NULL, NULL, '2025-07-25 17:09:27'),
(4, 9, '2025-07-28', '16:00:45', '18:15:11', NULL, NULL, '2025-07-28 16:00:45'),
(5, 11, '2025-07-28', '18:16:39', NULL, NULL, NULL, '2025-07-28 16:16:10'),
(6, 8, '2025-07-29', '12:11:24', '12:14:59', '12:15:45', '12:16:14', '2025-07-29 10:11:24'),
(7, 7, '2025-07-29', '17:11:33', '17:13:02', '17:13:50', NULL, '2025-07-29 15:11:33'),
(8, 10, '2025-07-29', '17:15:18', NULL, NULL, NULL, '2025-07-29 15:15:18'),
(9, 11, '2025-07-29', '17:16:52', '17:17:50', NULL, NULL, '2025-07-29 15:16:52'),
(10, 6, '2025-07-29', '17:31:44', '17:34:12', NULL, NULL, '2025-07-29 15:31:44');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `prenom`) VALUES
(6, 'DOGNON', 'Victorin'),
(7, 'EHOUMI', 'Faïdath'),
(8, 'HOUNNOU', 'Josias'),
(9, 'NOUGBO', 'Premix'),
(10, 'TANI', 'Aminatou'),
(11, 'FATOMBI', 'Waris');

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `presences`
--
ALTER TABLE `presences`
  ADD CONSTRAINT `fk_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
