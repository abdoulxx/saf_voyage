-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : lun. 28 avr. 2025 à 22:06
-- Version du serveur : 8.3.0
-- Version de PHP : 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `saf_voyage`
--

-- --------------------------------------------------------

--
-- Structure de la table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`(191)),
  KEY `role_index` (`role`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `admins`
--

INSERT INTO `admins` (`id`, `nom`, `prenom`, `email`, `password`, `role`, `created_at`) VALUES
(7, 'samb', 'sidy', 'saf@gmail.com', '$2y$10$6RWFpwYZOMugamcPodCG7OJkqSIJc49xFHUDpNiBi7lIROAToOsp6', 'admin', '2025-04-20 00:54:24');

-- --------------------------------------------------------

--
-- Structure de la table `destinations`
--

DROP TABLE IF EXISTS `destinations`;
CREATE TABLE IF NOT EXISTS `destinations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `description` text,
  `prix_par_personne` decimal(10,2) DEFAULT NULL,
  `date_ajout` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `hotels`
--

DROP TABLE IF EXISTS `hotels`;
CREATE TABLE IF NOT EXISTS `hotels` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `localisation` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `prix_nuit` decimal(10,2) NOT NULL,
  `image` varchar(255) NOT NULL,
  `vol_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `vol_id` (`vol_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `hotels`
--

INSERT INTO `hotels` (`id`, `nom`, `localisation`, `description`, `prix_nuit`, `image`, `vol_id`, `created_at`) VALUES
(1, 'SIDY ', 'marcory', 'ddddd', 10000.00, 'sidy_.png', 2, '2025-04-18 00:37:05'),
(2, 'SIDY SAMB', 'injs', 'ddd', 50000.00, 'sidy_samb.png', 2, '2025-04-18 00:39:11'),
(3, 'brise de mer', 'paris nord ', 'bdbd', 200000.00, 'brise_de_mer.png', 3, '2025-04-18 00:41:49'),
(9, 'SIDY ', 'injs', 'cc', 20000.00, 'sidy__1745112535.png', 8, '2025-04-20 01:28:55');

-- --------------------------------------------------------

--
-- Structure de la table `messages_contact`
--

DROP TABLE IF EXISTS `messages_contact`;
CREATE TABLE IF NOT EXISTS `messages_contact` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `date_envoi` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `messages_contact`
--

INSERT INTO `messages_contact` (`id`, `nom`, `email`, `message`, `date_envoi`) VALUES
(1, 'SIDY SAMB', 'sambsidy287@gmail.com', 'hello a tous beau boulot !', '2025-04-18 22:35:12'),
(2, 'sozani', 'ff@gmail.com', 'bonjour!\r\n', '2025-04-20 00:56:28'),
(3, 'saf', 'sambsidy2dd87@gmail.com', 'bonjour beau boulot', '2025-04-28 21:10:40');

-- --------------------------------------------------------

--
-- Structure de la table `options`
--

DROP TABLE IF EXISTS `options`;
CREATE TABLE IF NOT EXISTS `options` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prix` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `options`
--

INSERT INTO `options` (`id`, `nom`, `prix`, `created_at`) VALUES
(1, 'Petit déjeuner', 2000.00, '2025-04-18 00:18:11'),
(2, 'Excursion guidée', 10000.00, '2025-04-18 00:18:11'),
(3, 'Transport privé', 5000.00, '2025-04-18 00:18:11'),
(4, 'Petit déjeuner', 2000.00, '2025-04-18 00:18:11'),
(5, 'Excursion guidée', 10000.00, '2025-04-18 00:18:11'),
(6, 'Transport privé', 5000.00, '2025-04-18 00:18:11');

-- --------------------------------------------------------

--
-- Structure de la table `reservations`
--

DROP TABLE IF EXISTS `reservations`;
CREATE TABLE IF NOT EXISTS `reservations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `vol_id` int NOT NULL,
  `hotel_id` int DEFAULT NULL,
  `prix_total` decimal(10,2) NOT NULL,
  `statut_paiement` enum('paye','en_attente','non_paye','annule') NOT NULL DEFAULT 'non_paye',
  `methode_paiement` enum('online','reception') NOT NULL,
  `reference_paiement` varchar(100) DEFAULT NULL,
  `date_reservation` datetime NOT NULL,
  `date_paiement` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `vol_id` (`vol_id`),
  KEY `hotel_id` (`hotel_id`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `reservations`
--

INSERT INTO `reservations` (`id`, `user_id`, `vol_id`, `hotel_id`, `prix_total`, `statut_paiement`, `methode_paiement`, `reference_paiement`, `date_reservation`, `date_paiement`) VALUES
(13, 1, 2, 2, 857000.00, 'non_paye', 'online', 'RES13_1745872285', '2025-04-28 20:30:48', NULL),
(2, 1, 2, 2, 865000.00, 'paye', 'online', 'RES2_1745026511', '2025-04-19 01:24:34', '2025-04-19 01:35:24'),
(3, 1, 3, 0, 712000.00, 'paye', 'online', 'RES3_1745026561', '2025-04-19 01:35:55', '2025-04-19 01:36:12'),
(4, 1, 2, 0, 512000.00, 'non_paye', 'online', 'RES4_1745784391', '2025-04-19 01:37:42', NULL),
(5, 1, 8, 9, 207000.00, 'paye', 'reception', NULL, '2025-04-20 01:29:43', '2025-04-20 01:30:19'),
(6, 1, 3, 3, 3707000.00, 'paye', 'online', 'RES6_1745766687', '2025-04-20 16:43:20', '2025-04-27 15:12:06'),
(11, 1, 2, 2, 852000.00, 'paye', 'online', 'RES11_1745766472', '2025-04-27 15:03:51', '2025-04-27 15:08:37'),
(12, 1, 3, 0, 705000.00, 'en_attente', 'reception', NULL, '2025-04-27 15:54:22', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `reservation_options`
--

DROP TABLE IF EXISTS `reservation_options`;
CREATE TABLE IF NOT EXISTS `reservation_options` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reservation_id` int NOT NULL,
  `option_nom` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `reservation_id` (`reservation_id`)
) ENGINE=MyISAM AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `reservation_options`
--

INSERT INTO `reservation_options` (`id`, `reservation_id`, `option_nom`) VALUES
(23, 13, 'Transport privé'),
(20, 11, 'Petit déjeuner'),
(3, 2, 'Transport privé'),
(4, 2, 'Excursion guidée'),
(5, 3, 'Petit déjeuner'),
(6, 3, 'Excursion guidée'),
(7, 4, 'Petit déjeuner'),
(8, 4, 'Excursion guidée'),
(9, 5, 'Petit déjeuner'),
(10, 5, 'Transport privé'),
(11, 5, 'Excursion guidée'),
(12, 6, 'Petit déjeuner'),
(13, 6, 'Transport privé'),
(21, 12, 'Transport privé'),
(22, 13, 'Petit déjeuner');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `role` enum('client','admin') NOT NULL DEFAULT 'client',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `localisation` varchar(255) DEFAULT NULL,
  `numero_telephone` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `nom`, `email`, `mot_de_passe`, `role`, `created_at`, `localisation`, `numero_telephone`) VALUES
(1, 'sidy samb', 'sambsidy287@gmail.com', '$2y$10$3yT4nK5x2SuRPDOazhe2F.9LZQekYbQ8y28Nr/Ormfxb0cDugwtra', 'client', '2025-04-16 15:05:15', 'yopougon', '2250151516084');

-- --------------------------------------------------------

--
-- Structure de la table `vols`
--

DROP TABLE IF EXISTS `vols`;
CREATE TABLE IF NOT EXISTS `vols` (
  `id` int NOT NULL AUTO_INCREMENT,
  `destination` varchar(100) NOT NULL,
  `ville_depart` varchar(100) NOT NULL DEFAULT 'Abidjan',
  `description` text NOT NULL,
  `prix` decimal(10,2) NOT NULL,
  `date_depart` date NOT NULL,
  `date_retour` date NOT NULL,
  `duree` int NOT NULL,
  `image` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `vols`
--

INSERT INTO `vols` (`id`, `destination`, `ville_depart`, `description`, `prix`, `date_depart`, `date_retour`, `duree`, `image`, `created_at`) VALUES
(2, 'chine', 'Abidjan', 'beau beau beau beau beua ', 500000.00, '2025-04-19', '2025-04-26', 7, 'chine.jpg', '2025-04-18 00:34:56'),
(3, 'japon', 'Abidjan', 'cccccc', 700000.00, '2025-04-24', '2025-05-09', 15, 'japon_1745111935.png', '2025-04-18 00:41:16'),
(8, 'new york', 'Abidjan', 'c', 30000.00, '2025-04-21', '2025-04-29', 8, 'new york_1745112516.png', '2025-04-20 01:28:36');

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `hotels`
--
ALTER TABLE `hotels`
  ADD CONSTRAINT `hotels_ibfk_1` FOREIGN KEY (`vol_id`) REFERENCES `vols` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
