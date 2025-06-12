# Gestion Solde Bancaire avec PHP et MySQL (avec Audit)

## Description
Cette application web permet de gérer les comptes bancaires clients avec deux types d’utilisateurs :  
- **Utilisateur** : Peut ajouter des clients avec un solde initial et modifier le solde des clients existants.  
- **Administrateur** : Peut consulter un audit détaillé des actions effectuées par les utilisateurs (ajout, modification, suppression), avec la trace du nom d’utilisateur ayant réalisé chaque action.

## Fonctionnalités

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
