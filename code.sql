-- Création de la base de données
CREATE DATABASE IF NOT EXISTS centre_formation;
USE centre_formation;

-- Création de la table des inscriptions
CREATE TABLE inscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    date_naissance DATE,
    lieu_naissance VARCHAR(100),
    sexe ENUM('Masculin', 'Féminin', 'Autre', '') DEFAULT '',
    nationalite VARCHAR(100),
    adresse VARCHAR(255),
    telephone VARCHAR(50),
    email VARCHAR(150) NOT NULL,
    niveau_etudes VARCHAR(100),
    diplomes TEXT,
    parcours_professionnel TEXT,
    formation_souhaitee VARCHAR(150) NOT NULL,
    date_debut DATE,
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE utilisateurs (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE presences (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT UNSIGNED NOT NULL,
    date_presence DATE NOT NULL,
    arrivee_entreprise TIME DEFAULT NULL,
    depart_entreprise TIME DEFAULT NULL,
    arrivee_pause TIME DEFAULT NULL,
    depart_pause TIME DEFAULT NULL,
    timestamp_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    UNIQUE KEY unique_presence (utilisateur_id, date_presence)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
