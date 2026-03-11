CREATE DATABASE IF NOT EXISTS orientation
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE orientation;

CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role ENUM('admin') NOT NULL DEFAULT 'admin',
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    nom_utilisateur VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    telephone VARCHAR(30) DEFAULT NULL,
    adresse VARCHAR(255) DEFAULT NULL,
    code_postal VARCHAR(20) DEFAULT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modifie_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS series (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    label VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS mentions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(150) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS parcours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(200) NOT NULL,
    mention VARCHAR(150),
    duree VARCHAR(20),
    niveau VARCHAR(50),
    conditions TEXT,
    description TEXT,
    objectifs TEXT,
    debouches TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS metiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(200) NOT NULL,
    description TEXT,
    parcours JSON,
    mention VARCHAR(150),
    serie JSON,
    niveau VARCHAR(20),
    parcoursFormation JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS etablissements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(200) NOT NULL,
    province VARCHAR(100),
    region VARCHAR(100),
    type ENUM('Public', 'Privé') DEFAULT 'Public',
    mention VARCHAR(150),
    parcours VARCHAR(200),
    metier VARCHAR(200),
    niveau VARCHAR(50),
    duree VARCHAR(20),
    admission VARCHAR(100),
    contact VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS page_views (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    page       VARCHAR(100) NOT NULL,
    metier_id  INT NULL,
    ip_address VARCHAR(45)  NULL,
    user_agent TEXT         NULL,
    viewed_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_page       (page),
    INDEX idx_viewed_at  (viewed_at),
    INDEX idx_metier_id  (metier_id)
);

CREATE TABLE IF NOT EXISTS metier_searches (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    metier_id  INT          NOT NULL,
    metier_label VARCHAR(150) NOT NULL,
    searched_at  DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_metier_id   (metier_id),
    INDEX idx_searched_at (searched_at)
);
