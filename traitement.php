<?php
session_start();
require_once 'connexion.php';

// 1. Sécurité : On vérifie que l'utilisateur est bien connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// 2. On vérifie que le formulaire a bien été envoyé avec des pronostics
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['prono'])) {
    
    $user_id = $_SESSION['user_id'];
    $pronostics = $_POST['prono']; // C'est notre fameux tableau de scores !

    // 3. On prépare la requête SQL (on la prépare une seule fois pour être performant)
    // Le "ON DUPLICATE KEY UPDATE" est une astuce de pro : 
    // Si le prono n'existe pas, il le crée. S'il existe déjà, il le met à jour !
    $sql = "INSERT INTO pronostics (utilisateur_id, match_id, score_equipe_1, score_equipe_2) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE score_equipe_1 = VALUES(score_equipe_1), score_equipe_2 = VALUES(score_equipe_2)";
    
    $stmt = $pdo->prepare($sql);

    // 4. On boucle sur chaque match envoyé par le formulaire
    foreach ($pronostics as $match_id => $scores) {
        // On s'assure que les scores sont bien des nombres entiers (sécurité)
        $scoreA = intval($scores['scoreA']);
        $scoreB = intval($scores['scoreB']);

        // On exécute la requête pour insérer ce match précis dans la base
        $stmt->execute([$user_id, $match_id, $scoreA, $scoreB]);
    }

    // 5. On crée un petit message de succès dans la session
    $_SESSION['message_succes'] = "Tes pronostics ont été enregistrés avec succès !";

    // 6. On redirige l'utilisateur vers son salon
    header("Location: salon.php");
    exit();
} else {
    // Si quelqu'un essaie d'accéder à traitement.php directement, on le renvoie
    header("Location: salon.php");
    exit();
}
?>