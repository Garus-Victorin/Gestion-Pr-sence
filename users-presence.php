<?php
session_start();
require_once 'db-connect.php'; // $mysqli
date_default_timezone_set('Europe/Paris'); // Fuseau GMT+1, heure d'hiver ou été automatique
$nom_entreprise = "CFRII";

// Catégories valides
$categories = [
    'arrivee_entreprise' => "Heure d'arrivée à l'entreprise",
    'depart_entreprise' => "Heure de départ de l'entreprise",
    'arrivee_pause' => "Heure d'arrivée pause",
    'depart_pause' => "Heure de départ pause"
];

// Correspondance départ => arrivée obligatoire
$correspondance = [
    'depart_entreprise' => 'arrivee_entreprise',
    'depart_pause' => 'arrivee_pause'
];

$errors = [];
$values = [
    'code_apprenant' => '',
    'categorie' => ''
];
// Pour affichage heure rouge si problème en JS
$show_time_warning = false;
$warning_time_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['code_apprenant'] = trim($_POST['code_apprenant'] ?? '');
    $values['categorie'] = trim($_POST['categorie'] ?? '');

    // Accès admin à partir du champ code_apprenant
    if ($values['code_apprenant'] === 'admin' && $values['categorie'] === '') {
        header("Location: admin/admin.php");
        exit;
    }

    // Validation simple
    if ($values['code_apprenant'] === '') {
        $errors['code_apprenant'] = "Le code apprenant est requis.";
    }
    if ($values['categorie'] === '' || !array_key_exists($values['categorie'], $categories)) {
        $errors['categorie'] = "Veuillez sélectionner une catégorie d'heure valide.";
    }

    if (count($errors) === 0) {
        if (preg_match('/^(\d{2})([a-zA-Z])(\d+)$/', $values['code_apprenant'], $m)) {
            $annee_code = $m[1];
            $initial = strtolower($m[2]);
            $id = (int)$m[3];

            $annee_courante = date('y');
            $initial_entreprise = strtolower(substr($nom_entreprise, 0, 1));

            if ($annee_code === $annee_courante && $initial === $initial_entreprise && $id > 0) {
                // Vérifier si utilisateur existe
                $stmt = mysqli_prepare($mysqli, "SELECT id FROM utilisateurs WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) === 1) {
                    mysqli_stmt_close($stmt);

                    $date_now = date('Y-m-d');
                    $time_now = date('H:i:s');

                    // Récupérer la présence du jour s'il y en a
                    $stmt_check = mysqli_prepare($mysqli,
                        "SELECT id, arrivee_entreprise, depart_entreprise, arrivee_pause, depart_pause 
                        FROM presences WHERE utilisateur_id = ? AND date_presence = ?");
                    mysqli_stmt_bind_param($stmt_check, "is", $id, $date_now);
                    mysqli_stmt_execute($stmt_check);
                    mysqli_stmt_store_result($stmt_check);

                    if (mysqli_stmt_num_rows($stmt_check) === 1) {
                        mysqli_stmt_bind_result($stmt_check, $presence_id,
                            $arrivee_entreprise, $depart_entreprise, $arrivee_pause, $depart_pause);
                        mysqli_stmt_fetch($stmt_check);
                        mysqli_stmt_close($stmt_check);

                        // ********** LOGIQUE DE BLOCAGE **********

                        // Vérification 1: si l'utilisateur n'a pas encore enregistré son arrivée_entreprise, il ne peut rien faire sauf arrivee_entreprise
                        if ($values['categorie'] !== 'arrivee_entreprise' && $arrivee_entreprise === null) {
                            // Afficher l'heure actuelle en rouge sur le champ catégorie
                            $show_time_warning = true;
                            $warning_time_message = "Vous devez d'abord enregistrer votre heure d'arrivée à l'entreprise à " . $time_now;
                            $errors['categorie'] = $warning_time_message;
                        }
                        // Vérifier que la catégorie demandée n'est pas déjà enregistrée sur ce jour (pas de double enregistrement)
                        else {
                            $valeur_cat = null;
                            switch ($values['categorie']) {
                                case 'arrivee_entreprise':
                                    $valeur_cat = $arrivee_entreprise;
                                    break;
                                case 'depart_entreprise':
                                    $valeur_cat = $depart_entreprise;
                                    break;
                                case 'arrivee_pause':
                                    $valeur_cat = $arrivee_pause;
                                    break;
                                case 'depart_pause':
                                    $valeur_cat = $depart_pause;
                                    break;
                            }

                            if ($valeur_cat !== null) {
                                $errors['categorie'] = "Vous avez déjà enregistré cette catégorie ({$categories[$values['categorie']]}) aujourd'hui à $valeur_cat.";
                            }
                            else {
                                // Si catégorie est un départ, vérifier arrivée correspondante
                                if (isset($correspondance[$values['categorie']])) {
                                    $cat_arrivee = $correspondance[$values['categorie']];
                                    $arrivee_valeur = null;
                                    switch ($cat_arrivee) {
                                        case 'arrivee_entreprise':
                                            $arrivee_valeur = $arrivee_entreprise;
                                            break;
                                        case 'arrivee_pause':
                                            $arrivee_valeur = $arrivee_pause;
                                            break;
                                    }
                                    if ($arrivee_valeur === null) {
                                        $errors['categorie'] = "Impossible d'enregistrer un départ sans avoir d'abord enregistré l'arrivée correspondante (" . htmlspecialchars($categories[$cat_arrivee]) . ").";
                                    }
                                }

                                // Règle spécifique : arrivée pause possible que si départ entreprise déjà fait auparavant (ce qui évite arrivée pause sans départ entreprise)
                                if ($values['categorie'] === 'arrivee_pause' && $depart_entreprise === null) {
                                    $errors['categorie'] = "Impossible d'enregistrer l'arrivée en pause sans avoir quitté l'entreprise auparavant.";
                                }
                            }
                        }

                        // Si pas d'erreur, mise à jour présence
                        if (count($errors) === 0) {
                            $sql_upd = "UPDATE presences SET {$values['categorie']} = ? WHERE id = ?";
                            $stmt_upd = mysqli_prepare($mysqli, $sql_upd);
                            mysqli_stmt_bind_param($stmt_upd, "si", $time_now, $presence_id);
                            if (!mysqli_stmt_execute($stmt_upd)) {
                                $errors['general'] = "Erreur lors de la mise à jour : " . mysqli_stmt_error($stmt_upd);
                            }
                            mysqli_stmt_close($stmt_upd);
                        }
                    }
                    else {
                        // Pas de présence aujourd'hui => insertion initiale
                        mysqli_stmt_close($stmt_check);

                        // Autoriser uniquement arrivee_entreprise comme premier enregistrement
                        if ($values['categorie'] !== 'arrivee_entreprise') {
                            $errors['categorie'] = "Vous devez commencer par enregistrer votre arrivée à l'entreprise.";
                        }

                        if (count($errors) === 0) {
                            $sql_ins = "INSERT INTO presences (
                                utilisateur_id, date_presence, arrivee_entreprise, depart_entreprise, arrivee_pause, depart_pause, timestamp_creation
                                ) VALUES (?, ?, NULL, NULL, NULL, NULL, NOW())";
                            $stmt_ins = mysqli_prepare($mysqli, $sql_ins);
                            mysqli_stmt_bind_param($stmt_ins, "is", $id, $date_now);
                            if (!mysqli_stmt_execute($stmt_ins)) {
                                $errors['general'] = "Erreur lors de l'insertion de la présence : " . mysqli_stmt_error($stmt_ins);
                            }
                            else {
                                $presence_id = mysqli_insert_id($mysqli);
                            }
                            mysqli_stmt_close($stmt_ins);
                        }

                        if (count($errors) === 0) {
                            // Mettre à jour la catégorie arrivee_entreprise avec heure
                            $sql_upd = "UPDATE presences SET {$values['categorie']} = ? WHERE id = ?";
                            $stmt_upd = mysqli_prepare($mysqli, $sql_upd);
                            mysqli_stmt_bind_param($stmt_upd, "si", $time_now, $presence_id);
                            if (!mysqli_stmt_execute($stmt_upd)) {
                                $errors['general'] = "Erreur lors de la mise à jour : " . mysqli_stmt_error($stmt_upd);
                            }
                            mysqli_stmt_close($stmt_upd);
                        }
                    }

                    // Si tout OK : redirection vers finished.php avec param catégorie
                    if (count($errors) === 0) {
                        header("Location: finished.php?categorie=" . urlencode($values['categorie']));
                        exit;
                    }
                }
                else {
                    mysqli_stmt_close($stmt);
                    $errors['code_apprenant'] = "Code apprenant incorrect ou utilisateur introuvable.";
                }
            }
            else {
                $errors['code_apprenant'] = "Code apprenant incorrect ou non conforme à l'année/initiale attendue.";
            }
        }
        else {
            $errors['code_apprenant'] = "Format du code apprenant invalide.";
        }
    }
}




?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Enregistrement Présence</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<style>
html, body {
    height: 100%;
    margin: 0;
    background: linear-gradient(270deg, hsl(210,70%,50%), hsl(230,60%,60%), hsl(250,80%,55%));
    background-size: 600% 600%;
    animation: gradientAnimation 10s ease infinite;
    display: flex;
    justify-content: center;
    align-items: center;
    font-family: Arial, sans-serif;
}
@keyframes gradientAnimation {
    0% {background-position:0% 50%;}
    50% {background-position:100% 50%;}
    100% {background-position:0% 50%;}
}
.container {
    max-width: 480px;
    background: white;
    border-radius: .5rem;
    box-shadow: 0 0 15px rgba(0,0,0,0.2);
    padding: 2rem;
    width: 100%;
}
h2 {
    text-align: center;
    font-weight: 700;
    margin-bottom: 1.5rem;
}
.is-invalid {
    border-color: #dc3545 !important;
}
.error-msg {
    color: #dc3545;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}
/* Ajouter style pour l'heure en rouge dans la sélection en cas d'erreur critique */
.select-time-warning option {
    color: red !important;
    font-weight: bold;
}
</style>
</head>
<body>
<div class="container" role="main" aria-label="Formulaire de présence">
    <h2>Marquer ma présence</h2>
    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errors['general']) ?></div>
    <?php endif; ?>
    <form method="post" novalidate>
        <div class="mb-3">
            <label for="code_apprenant" class="form-label">Code Apprenant</label>
            <input
                type="text"
                id="code_apprenant"
                name="code_apprenant"
                class="form-control <?= isset($errors['code_apprenant']) ? 'is-invalid' : '' ?>"
                value="<?= htmlspecialchars($values['code_apprenant']) ?>"
                placeholder="Ex: <?= date('y').strtoupper(substr($nom_entreprise,0,1)) ?>123"
                required
                autocomplete="off"
                inputmode="text"
                />
            <?php if (isset($errors['code_apprenant'])): ?>
                <div class="error-msg"><?= htmlspecialchars($errors['code_apprenant']) ?></div>
            <?php endif; ?>
        </div>

        <div class="mb-4">
            <label for="categorie" class="form-label">Catégorie d'heure</label>
            <select id="categorie" name="categorie" class="form-select <?= isset($errors['categorie']) ? 'is-invalid' : '' ?> <?= $show_time_warning ? 'select-time-warning' : '' ?>" required>
                <option value="" <?= $values['categorie'] === '' ? 'selected' : '' ?>>Sélectionnez une catégorie</option>
                <?php foreach ($categories as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= $values['categorie'] === $key ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                        <?php if ($show_time_warning && $key === 'arrivee_entreprise'): ?>
                            (Heure actuelle: <span style="color:red; font-weight:bold;"><?= htmlspecialchars(date('H:i:s')) ?></span>)
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['categorie'])): ?>
                <div class="error-msg"><?= htmlspecialchars($errors['categorie']) ?></div>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary w-100">Marquer ma présence</button>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
