<?php
// Les informations de connexion par défaut de XAMPP
$host = 'localhost';
$dbname = 'prono_cdm'; // Le nom de la base que tu as créée
$username = 'root';    // Utilisateur par défaut
$password = '';        // Pas de mot de passe par défaut sur Windows

try {
    // On tente de se connecter avec l'outil PDO (le standard moderne en PHP)
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // On demande à PDO d'afficher les erreurs s'il y en a (super utile pour débugger)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Génial : Connexion réussie à la base de données !";
    
} catch (PDOException $e) {
    // Si ça plante, on arrête tout et on affiche l'erreur
    die("Erreur de connexion : " . $e->getMessage());
}
?>