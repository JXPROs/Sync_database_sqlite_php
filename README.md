# Synchronisation de Bases de Données SQLite

Ce script PHP permet de synchroniser deux bases de données SQLite en copiant les tables et les données d'une base de données source vers une base de données de destination.

## Fonctionnalités

- Vérification de l'existence et des permissions des fichiers SQLite.
- Création des tables manquantes dans la base de destination.
- Synchronisation des données entre les tables communes dans la base source et la base de destination.
- Gestion des transactions pour assurer une synchronisation atomique.

## Prérequis

- PHP 7.4 ou supérieur
- Extension PDO SQLite activée dans votre configuration PHP.

## Installation

1. Assurez-vous que PHP et l'extension SQLite sont installés.
2. Téléchargez le fichier PHP.
3. Modifiez les chemins vers les bases de données SQLite dans le fichier PHP :

```php
$db1Path = 'x/association.sqlite'; // Chemin vers la base source
$db2Path = 'y/association.sqlite'; // Chemin vers la base de destination
