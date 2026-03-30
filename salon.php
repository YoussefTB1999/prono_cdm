<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'data.php';

$pseudo = $_SESSION['pseudo'];
$code_salon = $_SESSION['code_salon'];
$user_id = $_SESSION['user_id'];

// 1. On récupère les scores déjà enregistrés
$stmtPronos = $pdo->prepare("SELECT match_id, score_equipe_1, score_equipe_2 FROM pronostics WHERE utilisateur_id = ?");
$stmtPronos->execute([$user_id]);
$pronosEnBase = $stmtPronos->fetchAll(PDO::FETCH_ASSOC);

$mesScores = [];
foreach ($pronosEnBase as $prono) {
    $mesScores[$prono['match_id']] = [
        'A' => $prono['score_equipe_1'],
        'B' => $prono['score_equipe_2']
    ];
}

// ==========================================
// LE CERVEAU : CALCUL DU CLASSEMENT
// ==========================================
$classements = [];

foreach ($groupesCDM as $nomGroupe => $equipes) {
    // Étape A : On met tout le monde à 0
    foreach ($equipes as $equipe) {
        $classements[$nomGroupe][$equipe] = ['pts' => 0, 'diff' => 0, 'bp' => 0, 'bc' => 0, 'joues' => 0];
    }

    $matchs = [
        [$equipes[0], $equipes[1]], [$equipes[2], $equipes[3]],
        [$equipes[0], $equipes[2]], [$equipes[1], $equipes[3]],
        [$equipes[0], $equipes[3]], [$equipes[1], $equipes[2]]
    ];

    // Étape B : On distribue les points selon les pronostics
    foreach ($matchs as $index => $match) {
        $equipeA = $match[0];
        $equipeB = $match[1];
        $match_id = str_replace(' ', '_', $nomGroupe) . "_" . $index;

        if (isset($mesScores[$match_id]) && $mesScores[$match_id]['A'] !== '' && $mesScores[$match_id]['B'] !== '') {
            $scoreA = (int)$mesScores[$match_id]['A'];
            $scoreB = (int)$mesScores[$match_id]['B'];

            // On ajoute les matchs joués et les buts
            $classements[$nomGroupe][$equipeA]['joues']++;
            $classements[$nomGroupe][$equipeB]['joues']++;
            $classements[$nomGroupe][$equipeA]['bp'] += $scoreA;
            $classements[$nomGroupe][$equipeB]['bp'] += $scoreB;
            $classements[$nomGroupe][$equipeA]['bc'] += $scoreB;
            $classements[$nomGroupe][$equipeB]['bc'] += $scoreA;
            $classements[$nomGroupe][$equipeA]['diff'] += ($scoreA - $scoreB);
            $classements[$nomGroupe][$equipeB]['diff'] += ($scoreB - $scoreA);

            // Victoire, Nul, Défaite
            if ($scoreA > $scoreB) {
                $classements[$nomGroupe][$equipeA]['pts'] += 3;
            } elseif ($scoreB > $scoreA) {
                $classements[$nomGroupe][$equipeB]['pts'] += 3;
            } else {
                $classements[$nomGroupe][$equipeA]['pts'] += 1;
                $classements[$nomGroupe][$equipeB]['pts'] += 1;
            }
        }
    }

    // Étape C : Le Tri (Points > Différence > Buts Marqués)
    uasort($classements[$nomGroupe], function($a, $b) {
        if ($a['pts'] !== $b['pts']) return $b['pts'] <=> $a['pts']; // Si points différents, on trie par points
        if ($a['diff'] !== $b['diff']) return $b['diff'] <=> $a['diff']; // Sinon, par différence de buts
        return $b['bp'] <=> $a['bp']; // Sinon, par buts pour
    });
}
// ==========================================
// LE BOSS FINAL : LES QUALIFIÉS (AVEC LA MATRICE !)
// ==========================================
$premiers = [];
$deuxiemes = [];
$tousLesTroisiemes = [];

// 1. Récupération des 1ers, 2èmes et 3èmes
foreach ($classements as $nomGroupe => $equipesTriees) {
    $lettreGroupe = str_replace('Groupe ', '', $nomGroupe);
    $nomsEquipes = array_keys($equipesTriees);

    $premiers[$lettreGroupe] = $nomsEquipes[0];
    $deuxiemes[$lettreGroupe] = $nomsEquipes[1];

    $team3 = $nomsEquipes[2];
    $stats3 = $equipesTriees[$team3];
    $stats3['nom'] = $team3;
    $stats3['groupe'] = $lettreGroupe;
    $tousLesTroisiemes[] = $stats3;
}

// 2. Tri des 12 troisièmes et sélection du Top 8
usort($tousLesTroisiemes, function($a, $b) {
    if ($a['pts'] !== $b['pts']) return $b['pts'] <=> $a['pts'];
    if ($a['diff'] !== $b['diff']) return $b['diff'] <=> $a['diff'];
    return $b['bp'] <=> $a['bp'];
});

$huitMeilleursTroisiemes = array_slice($tousLesTroisiemes, 0, 8);

// 3. On extrait les lettres des groupes qualifiés et on les trie alphabétiquement
$lettresQualifies = [];
$mapTroisiemes = []; // Pour retrouver le nom de l'équipe à partir de son groupe
foreach($huitMeilleursTroisiemes as $t) {
    $lettresQualifies[] = $t['groupe'];
    $mapTroisiemes[$t['groupe']] = $t['nom'];
}
sort($lettresQualifies);
$combinaisonCle = implode('_', $lettresQualifies); // Ex: "E_F_G_H_I_J_K_L"

// 4. LA MATRICE (Traduit en PHP)
// La clé est la combinaison triée. La valeur est un tableau associatif qui dit quel 3ème va contre quel 1er.
$matriceTroisiemes = [
    'E_F_G_H_I_J_K_L' => ['1A'=>'E', '1B'=>'J', '1D'=>'I', '1E'=>'F', '1G'=>'H', '1I'=>'G', '1K'=>'L', '1L'=>'K'],
    'D_F_G_H_I_J_K_L' => ['1A'=>'H', '1B'=>'G', '1D'=>'I', '1E'=>'D', '1G'=>'J', '1I'=>'F', '1K'=>'L', '1L'=>'K'],
    'D_E_G_H_I_J_K_L' => ['1A'=>'E', '1B'=>'J', '1D'=>'I', '1E'=>'D', '1G'=>'H', '1I'=>'G', '1K'=>'L', '1L'=>'K'],
    'D_E_F_H_I_J_K_L' => ['1A'=>'E', '1B'=>'J', '1D'=>'I', '1E'=>'D', '1G'=>'H', '1I'=>'F', '1K'=>'L', '1L'=>'K'],
    // ... On ajoutera les autres lignes du Bloc-notes ici au même format ...
];

// Si on n'a pas encore fini de remplir les scores, la combinaison n'existera pas dans la matrice.
// On crée donc un tableau de fallback vide pour éviter les erreurs PHP.
$adversairesTroisiemes = isset($matriceTroisiemes[$combinaisonCle]) ? $matriceTroisiemes[$combinaisonCle] : [];

// Fonction pour récupérer le bon 3ème (ou afficher TBD si non calculé)
function getTroisieme($idPremier, $adversairesTroisiemes, $mapTroisiemes) {
    if (isset($adversairesTroisiemes[$idPremier])) {
        $lettreGroupeAdversaire = $adversairesTroisiemes[$idPremier];
        return $mapTroisiemes[$lettreGroupeAdversaire];
    }
    return "3ème (En attente)";
}

// 5. Tableau des 16èmes de finale !
$seizieme_matchs = [
    ['eqA' => $premiers['E'], 'eqB' => getTroisieme('1E', $adversairesTroisiemes, $mapTroisiemes), 'id' => '16_1'],
    ['eqA' => $premiers['I'], 'eqB' => getTroisieme('1I', $adversairesTroisiemes, $mapTroisiemes), 'id' => '16_2'],
    ['eqA' => $deuxiemes['A'], 'eqB' => $deuxiemes['B'], 'id' => '16_3'],
    ['eqA' => $premiers['F'], 'eqB' => $deuxiemes['C'], 'id' => '16_4'],
    ['eqA' => $deuxiemes['K'], 'eqB' => $deuxiemes['L'], 'id' => '16_5'],
    ['eqA' => $premiers['H'], 'eqB' => $deuxiemes['J'], 'id' => '16_6'],
    ['eqA' => $premiers['D'], 'eqB' => getTroisieme('1D', $adversairesTroisiemes, $mapTroisiemes), 'id' => '16_7'],
    ['eqA' => $premiers['G'], 'eqB' => getTroisieme('1G', $adversairesTroisiemes, $mapTroisiemes), 'id' => '16_8'],
    ['eqA' => $premiers['C'], 'eqB' => $deuxiemes['F'], 'id' => '16_9'],
    ['eqA' => $deuxiemes['E'], 'eqB' => $deuxiemes['I'], 'id' => '16_10'],
    ['eqA' => $premiers['A'], 'eqB' => getTroisieme('1A', $adversairesTroisiemes, $mapTroisiemes), 'id' => '16_11'],
    ['eqA' => $premiers['L'], 'eqB' => getTroisieme('1L', $adversairesTroisiemes, $mapTroisiemes), 'id' => '16_12'],
    ['eqA' => $premiers['J'], 'eqB' => $deuxiemes['H'], 'id' => '16_13'],
    ['eqA' => $deuxiemes['D'], 'eqB' => $deuxiemes['G'], 'id' => '16_14'],
    ['eqA' => $premiers['B'], 'eqB' => getTroisieme('1B', $adversairesTroisiemes, $mapTroisiemes), 'id' => '16_15'],
    ['eqA' => $premiers['K'], 'eqB' => getTroisieme('1K', $adversairesTroisiemes, $mapTroisiemes), 'id' => '16_16'],
];
// ==========================================
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Salon <?php echo htmlspecialchars($code_salon); ?></title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; padding: 20px; max-width: 800px; margin: auto; }
        .header { background: #28a745; color: white; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; border: 1px solid #c3e6cb; }
        .groupe { background: white; margin-bottom: 30px; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .groupe h3 { margin-top: 0; color: #333; border-bottom: 2px solid #28a745; padding-bottom: 5px; }
        .match { display: flex; justify-content: space-between; align-items: center; margin: 10px 0; padding: 10px; background: #f9f9f9; border-radius: 5px; border-left: 3px solid #28a745; }
        .equipe { width: 35%; text-align: center; font-weight: bold; }
        input[type="number"] { width: 40px; text-align: center; padding: 5px; border: 1px solid #ccc; border-radius: 4px; font-size: 16px; }
        .btn-save { display: block; width: 100%; padding: 15px; background: #007bff; color: white; border: none; border-radius: 8px; font-size: 18px; cursor: pointer; margin-top: 20px; font-weight: bold; position: sticky; bottom: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .btn-save:hover { background: #0056b3; }
        
        /* Design du tableau de classement */
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px; }
        th { background: #eee; padding: 8px; text-align: center; }
        td { padding: 8px; text-align: center; border-bottom: 1px solid #ddd; }
        .qualifie { background: #e8f5e9; font-weight: bold; } /* Vert clair pour les qualifiés directs */
    </style>
</head>
<body>

    <div class="header">
        <h2>🏆 Salon : <?php echo htmlspecialchars($code_salon); ?></h2>
        <p>Connecté : <strong><?php echo htmlspecialchars($pseudo); ?></strong></p>
    </div>

    <?php if (isset($_SESSION['message_succes'])): ?>
        <div class="alert-success">
            <?php echo $_SESSION['message_succes']; unset($_SESSION['message_succes']); ?>
        </div>
    <?php endif; ?>

    <form action="traitement.php" method="POST">
        
        <?php
        foreach ($groupesCDM as $nomGroupe => $equipes) {
            echo "<div class='groupe'>";
            echo "<h3>$nomGroupe</h3>";

            // --- AFFICHAGE DU CLASSEMENT ---
            echo "<table>";
            echo "<tr><th>Pos</th><th>Équipe</th><th>J</th><th>Pts</th><th>Diff</th><th>BP</th></tr>";
            $pos = 1;
            foreach ($classements[$nomGroupe] as $nomEquipe => $stats) {
                // On met en vert les 2 premiers de chaque groupe
                $classe = ($pos <= 2) ? 'qualifie' : ''; 
                echo "<tr class='$classe'>";
                echo "<td>$pos</td><td>$nomEquipe</td><td>{$stats['joues']}</td><td>{$stats['pts']}</td><td>{$stats['diff']}</td><td>{$stats['bp']}</td>";
                echo "</tr>";
                $pos++;
            }
            echo "</table>";
            // --------------------------------

            // Les matchs du groupe
            $matchs = [
                [$equipes[0], $equipes[1]], [$equipes[2], $equipes[3]],
                [$equipes[0], $equipes[2]], [$equipes[1], $equipes[3]],
                [$equipes[0], $equipes[3]], [$equipes[1], $equipes[2]]
            ];

            foreach ($matchs as $index => $match) {
                $equipeA = $match[0];
                $equipeB = $match[1];
                $match_id = str_replace(' ', '_', $nomGroupe) . "_" . $index; 
                
                $valA = isset($mesScores[$match_id]) ? $mesScores[$match_id]['A'] : '';
                $valB = isset($mesScores[$match_id]) ? $mesScores[$match_id]['B'] : '';
                ?>
                
                <div class="match">
                    <span class="equipe"><?php echo htmlspecialchars($equipeA); ?></span>
                    <div>
                        <input type="number" name="prono[<?php echo $match_id; ?>][scoreA]" min="0" max="15" value="<?php echo htmlspecialchars($valA); ?>" required>
                        -
                        <input type="number" name="prono[<?php echo $match_id; ?>][scoreB]" min="0" max="15" value="<?php echo htmlspecialchars($valB); ?>" required>
                    </div>
                    <span class="equipe"><?php echo htmlspecialchars($equipeB); ?></span>
                </div>

                <?php
            }
            echo "</div>";
        }
        ?>
<h2 style="text-align: center; margin-top: 40px; color: #d9534f;">🔥 16èmes de Finale 🔥</h2>
        <div class='groupe' style='border: 2px solid #d9534f;'>
            <?php
            foreach ($seizieme_matchs as $match16) {
                $equipeA = $match16['eqA'];
                $equipeB = $match16['eqB'];
                $match_id = $match16['id'];
                
                // On vérifie si un prono existe déjà pour ce 16ème
                $valA = isset($mesScores[$match_id]) ? $mesScores[$match_id]['A'] : '';
                $valB = isset($mesScores[$match_id]) ? $mesScores[$match_id]['B'] : '';
                ?>
                
                <div class="match" style="border-left: 3px solid #d9534f; background: #fff5f5;">
                    <span class="equipe"><?php echo htmlspecialchars($equipeA); ?></span>
                    <div>
                        <input type="number" name="prono[<?php echo $match_id; ?>][scoreA]" min="0" max="15" value="<?php echo htmlspecialchars($valA); ?>">
                        -
                        <input type="number" name="prono[<?php echo $match_id; ?>][scoreB]" min="0" max="15" value="<?php echo htmlspecialchars($valB); ?>">
                    </div>
                    <span class="equipe"><?php echo htmlspecialchars($equipeB); ?></span>
                </div>

                <?php
            }
            ?>
        </div>
        <button type="submit" class="btn-save">Enregistrer tous mes pronostics</button>
    </form>

</body>
</html>
