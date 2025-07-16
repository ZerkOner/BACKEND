=== Projet "Qui est là ?" - BACKEND ===

Ce dossier contient les fichiers PHP nécessaires au bon fonctionnement de l'application de pointage.

➡ À placer à la racine du serveur PHP (Hostinger).

=== CONFIGURATION DE LA BASE DE DONNÉES ===

Nom de la base : qui_est_la

Merci d'importer la base depuis le fichier SQL fourni (non inclus ici).

Adapter le fichier `includes/db.php` avec les identifiants de connexion fournis par Hostinger :

    $host = 'localhost';       // OK sur Hostinger
    $dbname = 'qui_est_la';    // ou autre nom selon votre base
    $user = '...';             // votre login
    $pass = '...';             // votre mot de passe

=== FRONTEND DE L’APPLICATION ===

Le frontend (interface tablette) est déployé sur :  
➡ https://qui-est-la.netlify.app

Cette application interroge les API PHP suivantes :
- `/includes/api_get_formations.php`
- `/includes/api_get_personnels.php`
- `/traitement_entree.php`
- `/traitement_sortie.php`

Ces endpoints doivent être accessibles depuis l’extérieur.

=== SÉCURITÉ ===

Les mots de passe admin sont hashés (`bcrypt`), via `password_hash` en PHP.

=== DÉPANNAGE ===

- CORS est activé dans tous les fichiers API pour le fonctionnement entre domaines.
- Le site supporte les requêtes `OPTIONS` pour compatibilité avec les navigateurs.

Merci !
