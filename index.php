<?php
session_start(); // Obligatoire pour utiliser les sessions ($_SESSION)
require_once 'connexion.php'; // On appelle notre câble de connexion silencieux

// Si l'utilisateur a cliqué sur le bouton du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // On nettoie les données saisies (sécurité)
    $pseudo = trim($_POST['pseudo']);
    $code_salon = trim(strtoupper($_POST['code_salon'])); // On force les majuscules

    if (!empty($pseudo) && !empty($code_salon)) {
        
        // 1. Le salon existe-t-il ? (On utilise des requêtes préparées avec "?" pour éviter les piratages SQL)
        $stmt = $pdo->prepare("SELECT id FROM salons WHERE code_salon = ?");
        $stmt->execute([$code_salon]);
        $salon = $stmt->fetch();

        if (!$salon) {
            // S'il n'existe pas, on l'insère dans la base de données
            $insertSalon = $pdo->prepare("INSERT INTO salons (code_salon) VALUES (?)");
            $insertSalon->execute([$code_salon]);
            $salon_id = $pdo->lastInsertId(); // On récupère l'ID fraîchement créé
        } else {
            $salon_id = $salon['id']; // S'il existe, on récupère son ID
        }

        // 2. L'utilisateur existe-t-il déjà dans CE salon ?
        $stmtUser = $pdo->prepare("SELECT id FROM utilisateurs WHERE pseudo = ? AND salon_id = ?");
        $stmtUser->execute([$pseudo, $salon_id]);
        $user = $stmtUser->fetch();

        if (!$user) {
            // Nouvel utilisateur, on l'insère
            $insertUser = $pdo->prepare("INSERT INTO utilisateurs (pseudo, salon_id) VALUES (?, ?)");
            $insertUser->execute([$pseudo, $salon_id]);
            $user_id = $pdo->lastInsertId();
        } else {
            $user_id = $user['id'];
        }

        // 3. On garde les infos dans le sac à dos de l'utilisateur (la Session)
        $_SESSION['user_id'] = $user_id;
        $_SESSION['pseudo'] = $pseudo;
        $_SESSION['salon_id'] = $salon_id;
        $_SESSION['code_salon'] = $code_salon;

        // 4. On l'envoie vers la page du jeu !
        header("Location: salon.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Prono CDM 2026 - Accueil</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; text-align: center; padding-top: 50px; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); display: inline-block; }
        input { display: block; margin: 10px auto; padding: 10px; width: 80%; border: 1px solid #ccc; border-radius: 5px; }
        button { background-color: #28a745; color: white; border: none; padding: 10px 20px; font-size: 16px; border-radius: 5px; cursor: pointer; }
        button:hover { background-color: #218838; }
    </style>
</head>
<body>

    <div class="container">
        <h1>🏆 Pronostics CDM 2026</h1>
        <p>Crée ou rejoins un salon entre amis !</p>

        <form action="index.php" method="POST">
            <input type="text" name="pseudo" placeholder="Ton pseudo" required>
            <input type="text" name="code_salon" placeholder="Code du salon (ex: ZIDANE98)" required>
            <button type="submit">C'est parti !</button>
        </form>
    </div>

</body>
</html>