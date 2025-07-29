<?php
// admin.php
session_start();

// Inclure le fichier de connexion à la base de données
require_once 'db-connect.php'; // Assurez-vous que ce chemin est correct

// Nom de l'entreprise pour générer le code apprenant
$nom_entreprise = "SIAB"; // À adapter si nécessaire

// Suppression
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = mysqli_prepare($mysqli, "DELETE FROM inscriptions WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header('Location: admin.php');
    exit;
}

// Récupération d'une inscription pour édition
$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = mysqli_prepare($mysqli, "SELECT * FROM inscriptions WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $edit = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// Mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $id = (int)$_POST['update_id'];
    $fields = [
        'nom', 'prenom', 'sexe', 'date_naissance', 'lieu_naissance', 'nationalite',
        'localite', 'email', 'indicatif', 'telephone', 'niveau_etude', 'diplome',
        'classe', 'formation', 'date_debut'
    ];

    $data = [];
    $types = '';
    $values = [];

    foreach ($fields as $f) {
        $value = htmlspecialchars(trim($_POST[$f] ?? ''));
        $data[$f] = $value; // Stocker pour la construction de la requête
        $values[] = $value;

        // Déterminer le type pour mysqli_stmt_bind_param
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } else {
            $types .= 's'; // Par défaut, traiter comme une chaîne
        }
    }

    $sql = "UPDATE inscriptions SET
        nom=?, prenom=?, sexe=?, date_naissance=?,
        lieu_naissance=?, nationalite=?, localite=?,
        email=?, indicatif=?, telephone=?, niveau_etude=?,
        diplome=?, classe=?, formation=?, date_debut=?
        WHERE id=?";

    $values[] = $id; // Ajouter l'ID à la fin des valeurs
    $types .= 'i';   // Ajouter le type pour l'ID

    $stmt = mysqli_prepare($mysqli, $sql);
    // Utilisation de la syntaxe d'appel dynamique pour mysqli_stmt_bind_param
    mysqli_stmt_bind_param($stmt, $types, ...$values);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header('Location: admin.php');
    exit;
}

// Liste des inscriptions
$result_inscriptions = mysqli_query($mysqli, "SELECT * FROM inscriptions ORDER BY date_inscription DESC");
$inscriptions = [];
if ($result_inscriptions) {
    while ($row = mysqli_fetch_assoc($result_inscriptions)) {
        $inscriptions[] = $row;
    }
    mysqli_free_result($result_inscriptions);
}

// Fermer la connexion MySQLi (optionnel, mais bonne pratique si le script est long)
// mysqli_close($mysqli); // Ne pas fermer ici si d'autres parties du script ou d'autres fichiers inclus en dépendent.
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Admin - Inscriptions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-light">
<div class="container my-5">
    <h1 class="mb-4 text-center">Gestion des Inscriptions</h1>

    <?php if ($edit): ?>
        <h3>Modifier l'inscription de <?= htmlspecialchars($edit['prenom']) ?> <?= htmlspecialchars($edit['nom']) ?></h3>
        <form method="POST" class="mb-4">
            <input type="hidden" name="update_id" value="<?= $edit['id'] ?>">
            <div class="row g-2">
                <div class="col-md-4">
                    <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($edit['nom']) ?>" required placeholder="Nom">
                </div>
                <div class="col-md-4">
                    <input type="text" name="prenom" class="form-control" value="<?= htmlspecialchars($edit['prenom']) ?>" required placeholder="Prénom">
                </div>
                <div class="col-md-4">
                    <select name="sexe" class="form-select" required>
                        <option value="Masculin" <?= $edit['sexe']=='Masculin'?'selected':'' ?>>Masculin</option>
                        <option value="Féminin" <?= $edit['sexe']=='Féminin'?'selected':'' ?>>Féminin</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="date" name="date_naissance" class="form-control" value="<?= htmlspecialchars($edit['date_naissance']) ?>" required>
                </div>
                <div class="col-md-4">
                    <input type="text" name="lieu_naissance" class="form-control" value="<?= htmlspecialchars($edit['lieu_naissance']) ?>" required placeholder="Lieu de naissance">
                </div>
                <div class="col-md-4">
                    <input type="text" name="nationalite" class="form-control" value="<?= htmlspecialchars($edit['nationalite']) ?>" required placeholder="Nationalité">
                </div>
                <div class="col-md-4">
                    <input type="text" name="localite" class="form-control" value="<?= htmlspecialchars($edit['localite']) ?>" required placeholder="Localité">
                </div>
                <div class="col-md-4">
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($edit['email']) ?>" required placeholder="Email">
                </div>
                <div class="col-md-2">
                    <input type="text" name="indicatif" class="form-control" value="<?= htmlspecialchars($edit['indicatif']) ?>" required placeholder="Indicatif">
                </div>
                <div class="col-md-2">
                    <input type="text" name="telephone" class="form-control" value="<?= htmlspecialchars($edit['telephone']) ?>" required placeholder="Téléphone">
                </div>
                <div class="col-md-4">
                    <input type="text" name="niveau_etude" class="form-control" value="<?= htmlspecialchars($edit['niveau_etude']) ?>" required placeholder="Niveau d'étude">
                </div>
                <div class="col-md-4">
                    <input type="text" name="diplome" class="form-control" value="<?= htmlspecialchars($edit['diplome']) ?>" required placeholder="Diplôme">
                </div>
                <div class="col-md-4">
                    <input type="text" name="classe" class="form-control" value="<?= htmlspecialchars($edit['classe']) ?>" required placeholder="Classe">
                </div>
                <div class="col-md-4">
                    <input type="text" name="formation" class="form-control" value="<?= htmlspecialchars($edit['formation']) ?>" required placeholder="Formation">
                </div>
                <div class="col-md-4">
                    <input type="date" name="date_debut" class="form-control" value="<?= htmlspecialchars($edit['date_debut']) ?>" required>
                </div>
            </div>
            <div class="mt-3">
                <button class="btn btn-success" type="submit">Enregistrer</button>
                <a href="admin.php" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    <?php endif; ?>

    <h3>Liste des inscriptions</h3>
    <?php if (empty($inscriptions)): ?>
        <div class="alert alert-info text-center" role="alert">
            Aucun enregistrement trouvé.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Code Apprenant</th> <!-- Nouvelle colonne -->
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Sexe</th>
                        <th>Date Naissance</th>
                        <th>Lieu</th>
                        <th>Nationalité</th>
                        <th>Email</th>
                        <th>Indicatif</th>
                        <th>Téléphone</th>
                        <th>Niveau</th>
                        <th>Diplôme</th>
                        <th>Classe</th>
                        <th>Formation</th>
                        <th>Date début</th>
                        <th>Date inscription</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($inscriptions as $i): ?>
                    <?php
                        // Génération du Code Apprenant
                        $annee_actuelle = date('Y');
                        $deux_derniers_chiffres_annee = substr($annee_actuelle, -2);
                        $premiere_lettre_entreprise = strtoupper(substr($nom_entreprise, 0, 1));
                        $code_apprenant = $deux_derniers_chiffres_annee . $premiere_lettre_entreprise . $i['id'];
                    ?>
                    <tr>
                        <td><?= $i['id'] ?></td>
                        <td><?= htmlspecialchars($code_apprenant) ?></td> <!-- Affichage du code apprenant -->
                        <td><?= htmlspecialchars($i['nom']) ?></td>
                        <td><?= htmlspecialchars($i['prenom']) ?></td>
                        <td><?= htmlspecialchars($i['sexe']) ?></td>
                        <td><?= htmlspecialchars($i['date_naissance']) ?></td>
                        <td><?= htmlspecialchars($i['lieu_naissance']) ?></td>
                        <td><?= htmlspecialchars($i['nationalite']) ?></td>
                        <td><?= htmlspecialchars($i['email']) ?></td>
                        <td><?= htmlspecialchars($i['indicatif']) ?></td>
                        <td><?= htmlspecialchars($i['telephone']) ?></td>
                        <td><?= htmlspecialchars($i['niveau_etude']) ?></td>
                        <td><?= htmlspecialchars($i['diplome']) ?></td>
                        <td><?= htmlspecialchars($i['classe']) ?></td>
                        <td><?= htmlspecialchars($i['formation']) ?></td>
                        <td><?= htmlspecialchars($i['date_debut']) ?></td>
                        <td><?= htmlspecialchars($i['date_inscription']) ?></td>
                        <td>
                            <a href="admin.php?edit=<?= $i['id'] ?>" class="btn btn-sm btn-warning">Modifier</a>
                            <a href="admin.php?delete=<?= $i['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cette inscription ?')">Supprimer</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
