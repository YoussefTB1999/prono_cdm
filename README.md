🏆 Prono CDM 2026 - Moteur de Pronostics (MVP)
Projet de remise à niveau technique (Développement Back-End PHP / MySQL) Réalisé lors d'un sprint de développement d'un week-end complet.

📖 À propos du projet
Cette application web est un Produit Minimum Viable (MVP) permettant de créer des mini-salons privés pour pronostiquer les matchs de la Coupe du Monde de la FIFA 2026.

L'objectif principal de ce projet n'était pas de réaliser une interface graphique complexe, mais de concevoir un moteur de calcul backend robuste, capable de gérer des règles métiers complexes (calculs de points, classements dynamiques, gestion des égalités, et matrice de qualification pour les phases finales).

🎯 À l'attention des recruteurs
Ce projet marque ma remise à niveau technique en vue d'une reprise d'études en alternance (Bac+3) pour septembre 2026, après 2 années passées dans le secteur de la rénovation énergétique. Il démontre ma capacité à :

Me réapproprier rapidement un environnement de développement.
Transformer des règles métiers (règlement FIFA) en algorithmes fonctionnels.
Structurer une base de données.
⚙️ Fonctionnalités Techniques
Système de Salons Privés : Gestion des sessions utilisateurs ($_SESSION) avec création et connexion via un code unique.
CRUD Pronostics : Enregistrement et mise à jour des scores en base de données avec l'objet PDO (utilisation de requêtes préparées pour prévenir les injections SQL).
Algorithme de Classement Dynamique : * Calcul en temps réel des points (Victoire, Nul, Défaite), de la différence de buts et des buts marqués.
Tri des poules automatisé via l'opérateur de comparaison spatiale PHP (<=>) en gérant les cas d'égalité.
** Matrice des 16èmes de Finale :** * Extraction automatique des 24 qualifiés directs (1ers et 2èmes).
Création d'un sous-classement pour isoler les 8 meilleurs troisièmes parmi les 12 groupes.
Intégration d'une matrice combinatoire complexe pour croiser les meilleurs troisièmes avec les premiers de groupe, en respectant les contraintes de tirage au sort.
🛠️ Stack Technique
Back-End : PHP 8+ (Logique métier, sessions, PDO)
Base de données : MySQL (Modélisation relationnelle)
Front-End : HTML5 / CSS3 (Interface simple et responsive)
Environnement : Serveur local Apache (XAMPP / Windows)
🗄️ Structure de la Base de Données
Le projet repose sur 3 tables principales :

salons : Stocke les IDs et codes uniques générés par les utilisateurs.
utilisateurs : Relie un pseudo à un salon spécifique.
pronostics : Enregistre les scores (Score A / Score B) reliés à un match précis et à un utilisateur.
🚀 Installation en local
Si vous souhaitez faire tourner le projet sur votre machine :

Clonez ce dépôt dans le répertoire racine de votre serveur local (ex: htdocs pour XAMPP) :

git clone [https://github.com/VOTRE_PSEUDO/prono-cdm-2026.git](https://github.com/VOTRE_PSEUDO/prono-cdm-2026.git)
Créez une base de données nommée prono_cdm.

Modifiez les identifiants de connexion dans le fichier connexion.php si nécessaire.

Lancez votre serveur (Apache/MySQL) et accédez à http://localhost/prono-cdm-2026/index.php.

🔜 Évolutions prévues (Roadmap du WIP) [ ] En cours : Intégration de la matrice complète des 16èmes de finale pour le placement exact des meilleurs troisièmes.

[ ] Ajouter l'arbre complet des phases à élimination directe (Huitièmes jusqu'à la Finale).

[ ] Créer un tableau des scores global (Leaderboard) pour comparer les points entre les joueurs du salon.

[ ] Refonte visuelle complète (Intégration d'un framework CSS).
