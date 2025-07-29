<?php
session_start();

// Inclure la connexion MySQLi
require_once 'db-connect.php'; // Ajustez le chemin si besoin

// Simple token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$values = [
    'nom' => '',
    'prenom' => '',
    'sexe' => '',
    'date_naissance' => '',
    'lieu_naissance' => '',
    'nationalite' => '',
    'localite' => '',
    'email' => '',
    'indicatif' => '',
    'telephone' => '',
    'niveau_etude' => '',
    'diplome' => '',
    'classe' => '',
    'formation' => '',
    'date_debut' => ''
];

// Liste des pays africains + européens avec drapeau et indicatif téléphonique
$countries = [
    ['name' => 'Algérie', 'flag' => 'https://flagcdn.com/dz.svg', 'code' => '+213'],
    ['name' => 'Angola', 'flag' => 'https://flagcdn.com/ao.svg', 'code' => '+244'],
    ['name' => 'Bénin', 'flag' => 'https://flagcdn.com/bj.svg', 'code' => '+229'],
    ['name' => 'Burkina Faso', 'flag' => 'https://flagcdn.com/bf.svg', 'code' => '+226'],
    ['name' => 'Cameroun', 'flag' => 'https://flagcdn.com/cm.svg', 'code' => '+237'],
    ['name' => 'Côte d\'Ivoire', 'flag' => 'https://flagcdn.com/ci.svg', 'code' => '+225'],
    ['name' => 'Égypte', 'flag' => 'https://flagcdn.com/eg.svg', 'code' => '+20'],
    ['name' => 'Éthiopie', 'flag' => 'https://flagcdn.com/et.svg', 'code' => '+251'],
    ['name' => 'Ghana', 'flag' => 'https://flagcdn.com/gh.svg', 'code' => '+233'],
    ['name' => 'Kenya', 'flag' => 'https://flagcdn.com/ke.svg', 'code' => '+254'],
    ['name' => 'Allemagne', 'flag' => 'https://flagcdn.com/de.svg', 'code' => '+49'],
    ['name' => 'Belgique', 'flag' => 'https://flagcdn.com/be.svg', 'code' => '+32'],
    ['name' => 'Espagne', 'flag' => 'https://flagcdn.com/es.svg', 'code' => '+34'],
    ['name' => 'France', 'flag' => 'https://flagcdn.com/fr.svg', 'code' => '+33'],
    ['name' => 'Italie', 'flag' => 'https://flagcdn.com/it.svg', 'code' => '+39']
];

// Trier les pays par nom
usort($countries, fn($a,$b) => strcasecmp($a['name'], $b['name']));

// Classes 6ème à Terminale (simplifié)
$classes = [
    "6ème", "5ème", "4ème", "3ème",
    "2nde", "1ère", "Terminale"
];

// Formations disponibles (exemple)
$formations = [
    "Développement Web",
    "Gestion de Projet",
    "Marketing Digital",
    "Data Science",
    "Langues Vivantes"
];

// Fonction de nettoyage
function clean($v) {
    return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token invalide');
    }

    // Récupération + nettoyage
    foreach ($values as $key => $_) {
        $values[$key] = clean($_POST[$key] ?? '');
    }

    // Validation côté serveur
    if (empty($values['nom'])) {
        $errors['nom'] = "Le nom est obligatoire.";
    }
    if (empty($values['prenom'])) {
        $errors['prenom'] = "Le prénom est obligatoire.";
    }
    if (!in_array($values['sexe'], ['Masculin','Féminin'])) {
        $errors['sexe'] = "Sélectionnez un sexe valide.";
    }
    if (empty($values['date_naissance'])) {
        $errors['date_naissance'] = "La date de naissance est obligatoire.";
    } else {
        $d = DateTime::createFromFormat('Y-m-d', $values['date_naissance']);
        if (!$d || $d->format('Y-m-d') !== $values['date_naissance']) {
            $errors['date_naissance'] = "La date de naissance est invalide.";
        }
    }
    if (empty($values['lieu_naissance'])) {
        $errors['lieu_naissance'] = "Le lieu de naissance est obligatoire.";
    }
    if (empty($values['nationalite']) || !in_array($values['nationalite'], array_column($countries, 'name'))) {
        $errors['nationalite'] = "Sélectionnez une nationalité valide.";
    }
    if (empty($values['localite'])) {
        $errors['localite'] = "La localité est obligatoire.";
    }
    if (empty($values['email'])) {
        $errors['email'] = "L'email est obligatoire.";
    } elseif (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "L'email est invalide.";
    }
    if (empty($values['indicatif']) || !in_array($values['indicatif'], array_column($countries, 'code'))) {
        $errors['indicatif'] = "Sélectionnez un indicatif valide.";
    }
    if (empty($values['telephone'])) {
        $errors['telephone'] = "Le numéro de téléphone est obligatoire.";
    } elseif (!preg_match('/^\d{6,15}$/', $values['telephone'])) {
        $errors['telephone'] = "Le numéro de téléphone doit contenir uniquement entre 6 et 15 chiffres.";
    }
    if (empty($values['niveau_etude'])) {
        $errors['niveau_etude'] = "Le niveau d'étude est obligatoire.";
    }
    if (empty($values['diplome'])) {
        $errors['diplome'] = "Le diplôme est obligatoire.";
    }
    if (empty($values['classe']) || !in_array($values['classe'], $classes)) {
        $errors['classe'] = "Sélectionnez une classe valide.";
    }
    if (empty($values['formation']) || !in_array($values['formation'], $formations)) {
        $errors['formation'] = "Sélectionnez une formation valide.";
    }
    if (empty($values['date_debut'])) {
        $errors['date_debut'] = "La date de début est obligatoire.";
    } else {
        $d = DateTime::createFromFormat('Y-m-d', $values['date_debut']);
        if (!$d || $d->format('Y-m-d') !== $values['date_debut']) {
            $errors['date_debut'] = "La date de début est invalide.";
        }
    }

    if (count($errors) === 0) {
        // Préparer la requête d'insertion
        $sql = "INSERT INTO inscriptions (
            nom, prenom, sexe, date_naissance, lieu_naissance, nationalite,
            localite, email, indicatif, telephone, niveau_etude, diplome,
            classe, formation, date_debut, date_inscription
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = mysqli_prepare($mysqli, $sql);
        if (!$stmt) {
            $errors['bdd'] = "Erreur lors de la préparation de la requête : " . mysqli_error($mysqli);
        } else {
            mysqli_stmt_bind_param(
                $stmt,
                "sssssssssssssss",
                $values['nom'],
                $values['prenom'],
                $values['sexe'],
                $values['date_naissance'],
                $values['lieu_naissance'],
                $values['nationalite'],
                $values['localite'],
                $values['email'],
                $values['indicatif'],
                $values['telephone'],
                $values['niveau_etude'],
                $values['diplome'],
                $values['classe'],
                $values['formation'],
                $values['date_debut']
            );

            if (mysqli_stmt_execute($stmt)) {
                $success = true;
                $values = array_map(fn($v) => '', $values);
            } else {
                $errors['bdd'] = "Erreur lors de l'insertion : " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Formulaire d'Inscription Centre de Formation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        .is-invalid {
            border-color: #dc3545 !important;
        }
        .error-msg {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }
        .flag-option {
            width: 20px;
            height: 14px;
            margin-right: 8px;
            vertical-align: middle;
            border: 1px solid #ddd;
            border-radius: 2px;
        }
    </style>
</head>
<body class="bg-light">
<div class="container my-5">
    <h1 class="mb-4 text-center">Formulaire d'Inscription</h1>

    <?php if(!empty($success)): ?>
        <div class="alert alert-success text-center">Inscription enregistrée avec succès.</div>
    <?php endif; ?>

    <?php if (isset($errors['bdd'])): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($errors['bdd']) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate id="inscriptionForm">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>" />

        <!-- Informations Personnelles -->
        <h4 class="mb-3">Informations Personnelles</h4>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="nom" class="form-label">Nom *</label>
                <input
                    type="text"
                    class="form-control <?= isset($errors['nom']) ? 'is-invalid' : '' ?>"
                    id="nom" name="nom"
                    value="<?= $values['nom'] ?>"
                    required
                >
                <?php if (isset($errors['nom'])): ?>
                <div class="error-msg"><?= $errors['nom'] ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label for="prenom" class="form-label">Prénom *</label>
                <input
                    type="text"
                    class="form-control <?= isset($errors['prenom']) ? 'is-invalid' : '' ?>"
                    id="prenom" name="prenom"
                    value="<?= $values['prenom'] ?>"
                    required
                >
                <?php if (isset($errors['prenom'])): ?>
                <div class="error-msg"><?= $errors['prenom'] ?></div>
                <?php endif; ?>
            </div>

            <div class="col-md-4">
                <label for="sexe" class="form-label">Sexe *</label>
                <select
                    class="form-select <?= isset($errors['sexe']) ? 'is-invalid' : '' ?>"
                    id="sexe" name="sexe"
                    required
                >
                    <option value="" disabled <?= $values['sexe'] === '' ? 'selected' : '' ?>>Choisir...</option>
                    <option value="Masculin" <?= $values['sexe'] === 'Masculin' ? 'selected' : '' ?>>Masculin</option>
                    <option value="Féminin" <?= $values['sexe'] === 'Féminin' ? 'selected' : '' ?>>Féminin</option>
                </select>
                <?php if (isset($errors['sexe'])): ?>
                <div class="error-msg"><?= $errors['sexe'] ?></div>
                <?php endif; ?>
            </div>

            <div class="col-md-4">
                <label for="date_naissance" class="form-label">Date de naissance *</label>
                <input
                    type="date"
                    class="form-control <?= isset($errors['date_naissance']) ? 'is-invalid' : '' ?>"
                    id="date_naissance" name="date_naissance"
                    value="<?= $values['date_naissance'] ?>"
                    max="<?= date('Y-m-d') ?>"
                    required
                >
                <?php if (isset($errors['date_naissance'])): ?>
                <div class="error-msg"><?= $errors['date_naissance'] ?></div>
                <?php endif; ?>
            </div>

            <div class="col-md-4">
                <label for="lieu_naissance" class="form-label">Lieu de naissance *</label>
                <input
                    type="text"
                    class="form-control <?= isset($errors['lieu_naissance']) ? 'is-invalid' : '' ?>"
                    id="lieu_naissance" name="lieu_naissance"
                    value="<?= $values['lieu_naissance'] ?>"
                    required
                >
                <?php if (isset($errors['lieu_naissance'])): ?>
                <div class="error-msg"><?= $errors['lieu_naissance'] ?></div>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <label for="nationalite" class="form-label">Nationalité *</label>
                <select
                    class="form-select <?= isset($errors['nationalite']) ? 'is-invalid' : '' ?>"
                    id="nationalite" name="nationalite"
                    required
                >
                    <option value="" disabled <?= $values['nationalite'] === '' ? 'selected' : '' ?>>Choisir un pays...</option>
                    <?php foreach ($countries as $c): ?>
                        <option value="<?= htmlspecialchars($c['name']) ?>" <?= $values['nationalite'] === $c['name'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['nationalite'])): ?>
                <div class="error-msg"><?= $errors['nationalite'] ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Coordonnées -->
        <h4 class="mt-4 mb-3">Coordonnées</h4>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="localite" class="form-label">Localité *</label>
                <input
                    type="text"
                    class="form-control <?= isset($errors['localite']) ? 'is-invalid' : '' ?>"
                    id="localite" name="localite"
                    value="<?= $values['localite'] ?>"
                    required
                >
                <?php if (isset($errors['localite'])): ?>
                <div class="error-msg"><?= $errors['localite'] ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label">Adresse Email *</label>
                <input
                    type="email"
                    class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                    id="email" name="email"
                    value="<?= $values['email'] ?>"
                    required
                >
                <?php if (isset($errors['email'])): ?>
                <div class="error-msg"><?= $errors['email'] ?></div>
                <?php endif; ?>
            </div>

            <div class="col-md-3">
                <label for="indicatif" class="form-label">Indicatif *</label>
                <select
                    id="indicatif"
                    name="indicatif"
                    class="form-select <?= isset($errors['indicatif']) ? 'is-invalid' : '' ?>"
                    required
                >
                    <option value="" disabled <?= $values['indicatif'] === '' ? 'selected' : '' ?>>Code pays</option>
                    <?php foreach ($countries as $c):
                        $selected = $values['indicatif'] === $c['code'] ? 'selected' : '';
                    ?>
                        <option value="<?= $c['code'] ?>" <?= $selected ?>>
                            <?= "(" . htmlspecialchars($c['code']) . ") " . htmlspecialchars($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['indicatif'])): ?>
                <div class="error-msg"><?= $errors['indicatif'] ?></div>
                <?php endif; ?>
            </div>

            <div class="col-md-9">
                <label for="telephone" class="form-label">Numéro de téléphone *</label>
                <input
                    type="tel"
                    id="telephone"
                    name="telephone"
                    class="form-control <?= isset($errors['telephone']) ? 'is-invalid' : '' ?>"
                    placeholder="7 à 15 chiffres - sans indicatif"
                    value="<?= $values['telephone'] ?>"
                    pattern="[0-9]{6,15}"
                    required
                >
                <?php if (isset($errors['telephone'])): ?>
                <div class="error-msg"><?= $errors['telephone'] ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Niveau d'étude et diplôme -->
        <div class="row g-3">
            <h4>Niveau d'étude et Diplôme</h4>
            <div class="col-md-6">
                <label>Classe</label>
                <select
                    id="classe" name="classe"
                    class="form-select <?= isset($errors['classe']) ? 'is-invalid' : '' ?>"
                    required>
                    <option value="" disabled <?= $values['classe'] === '' ? 'selected' : '' ?>>Choisir une classe</option>
                    <?php foreach ($classes as $cl): ?>
                        <option value="<?= $cl ?>" <?= $values['classe'] === $cl ? 'selected' : '' ?>><?= $cl ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['classe'])): ?>
                <div class="error-msg"><?= $errors['classe'] ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label for="diplome" class="form-label">Diplôme obtenu *</label>
                <input
                    type="text"
                    id="diplome"
                    name="diplome"
                    class="form-control <?= isset($errors['diplome']) ? 'is-invalid' : '' ?>"
                    placeholder="Ex: Bac S, Bac ES, Licence pro, etc."
                    value="<?= $values['diplome'] ?>"
                    required
                >
                <?php if (isset($errors['diplome'])): ?>
                <div class="error-msg"><?= $errors['diplome'] ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Choix de la formation -->
        <h4 class="mt-4 mb-3">Choix de la formation</h4>
        <select
            id="formation" name="formation"
            class="form-select <?= isset($errors['formation']) ? 'is-invalid' : '' ?>"
            required
        >
            <option value="" disabled <?= $values['formation'] === '' ? 'selected' : '' ?>>Sélectionnez une formation</option>
            <?php foreach ($formations as $f): ?>
                <option value="<?= htmlspecialchars($f) ?>" <?= $values['formation'] === $f ? 'selected' : '' ?>><?= htmlspecialchars($f) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($errors['formation'])): ?>
        <div class="error-msg"><?= $errors['formation'] ?></div>
        <?php endif; ?>

        <!-- Date début formation -->
        <h4 class="mt-4 mb-3">Date de début de formation</h4>
        <input
            type="date"
            id="date_debut" name="date_debut"
            class="form-control <?= isset($errors['date_debut']) ? 'is-invalid' : '' ?>"
            value="<?= $values['date_debut'] ?>"
            min="<?= date('Y-m-d') ?>"
            required
        >
        <?php if (isset($errors['date_debut'])): ?>
        <div class="error-msg"><?= $errors['date_debut'] ?></div>
        <?php endif; ?>

        <div class="mt-4 text-center">
            <button type="submit" class="btn btn-primary btn-lg px-5">S'inscrire</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Bootstrap custom validation + add red border on error
(() => {
    'use strict'

    const form = document.querySelector('#inscriptionForm');

    form.addEventListener('submit', event => {
        // Reset previous validation
        form.querySelectorAll('.is-invalid').forEach(i => i.classList.remove('is-invalid'));
        form.querySelectorAll('.error-msg').forEach(e => e.remove());

        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();

            // Manually show message under invalid fields
            form.querySelectorAll(':invalid').forEach(el => {
                el.classList.add('is-invalid');
                const msg = document.createElement('div');
                msg.classList.add('error-msg');
                msg.textContent = el.validationMessage;
                el.after(msg);
            });
        }
    }, false);
})();
</script>
</body>
</html>
