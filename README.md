# Gestion Solde Bancaire avec PHP et MySQL (avec Audit)

## Description
Cette application web permet de gérer les comptes bancaires clients avec deux types d’utilisateurs :  
- **Utilisateur** : Peut ajouter des clients avec un solde initial et modifier le solde des clients existants.  
- **Administrateur** : Peut consulter un audit détaillé des actions effectuées par les utilisateurs (ajout, modification, suppression), avec la trace du nom d’utilisateur ayant réalisé chaque action.

## Enter PHPMyAdmin et puis dans SQL 
# voici les trigger
-- Trigger pour INSERT
DELIMITER //
CREATE TRIGGER after_insert_compte
AFTER INSERT ON compte
FOR EACH ROW
BEGIN
    INSERT INTO audit_compte 
    (type_action, num_compte, nom_client, solde_nouveau, utilisateur)
    VALUES 
    ('ajout', NEW.num_compte, NEW.nom_client, NEW.solde, CURRENT_USER());
END//
DELIMITER ;



-- Trigger pour UPDATE
DELIMITER //
CREATE TRIGGER after_update_compte
AFTER UPDATE ON compte
FOR EACH ROW
BEGIN
    INSERT INTO audit_compte 
    (type_action, num_compte, nom_client, solde_ancien, solde_nouveau, utilisateur)
    VALUES 
    ('modification', NEW.num_compte, NEW.nom_client, OLD.solde, NEW.solde, CURRENT_USER());
END//
DELIMITER ;



-- Trigger pour DELETE
DELIMITER //
CREATE TRIGGER after_delete_compte
AFTER DELETE ON compte
FOR EACH ROW
BEGIN
    INSERT INTO audit_compte 
    (type_action, num_compte, nom_client, solde_ancien, utilisateur)
    VALUES 
    ('suppression', OLD.num_compte, OLD.nom_client, OLD.solde, CURRENT_USER());
END//
DELIMITER ;

### Côté Utilisateur
- Ajouter un nouveau client avec solde initial.  
- Modifier le solde d’un client existant.

### Côté Administrateur
- Consulter l’historique des actions (audit) :  
  - Affiche les opérations (ajout, modification, suppression) réalisées sur les comptes clients.  
  - Affiche le nom de l’utilisateur qui a effectué chaque opération.  
  - Affiche la date et l’heure de chaque action.

## Technologies utilisées
- PHP (gestion back-end)  
- MySQL avec triggers et tables d’audit  
- HTML / CSS 

## Installation

1. Cloner ce dépôt :  
   ```bash
   git clone https://github.com/ton-utilisateur/gestion-solde-bancaire.git
