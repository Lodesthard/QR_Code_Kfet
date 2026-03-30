-- Migration: Ajout du système QR Code et gestion des stocks
-- À exécuter une seule fois sur la base de données

-- 1. Ajouter un token unique pour identifier chaque commande via QR code
ALTER TABLE orders ADD COLUMN qr_token VARCHAR(64) NULL UNIQUE AFTER datetime;

-- 2. Ajouter un statut de préparation à chaque commande
-- 0 = en attente, 1 = en préparation, 2 = prête / servie
ALTER TABLE orders ADD COLUMN status TINYINT NOT NULL DEFAULT 0 AFTER qr_token;

-- 3. Ajouter la colonne stock si elle n'existe pas déjà
-- (peut déjà exister dans certains backups, ignorer l'erreur le cas échéant)
ALTER TABLE products ADD COLUMN stock INT NOT NULL DEFAULT 0;

-- 4. Ajouter la colonne remember_token pour l'authentification sécurisée par cookie
ALTER TABLE users ADD COLUMN remember_token VARCHAR(64) NULL;
