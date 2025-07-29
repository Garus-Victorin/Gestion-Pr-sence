<?php
// Inclure la connexion via db-connect.php (ajustez le chemin si besoin)
require_once 'db-connect.php';

// --- Traitement CRUD ---

$message = '';
$error = '';

// Fonction sécurisation input
function input_clean($data) {
    return trim(htmlspecialchars($data));
}



// CREATE - Ajouter un utilisateur
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $nom = input_clean($_POST['nom'] ?? '');
    $prenom = input_clean($_POST['prenom'] ?? '');
    if ($nom === '' || $prenom === '') {
        $error = "Veuillez remplir tous les champs.";
    } else {
        $sql = "INSERT INTO utilisateurs (nom, prenom) VALUES (?, ?)";
        $stmt = mysqli_prepare($mysqli, $sql);
        mysqli_stmt_bind_param($stmt, 'ss', $nom, $prenom);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Utilisateur ajouté avec succès.";
        } else {
            $error = "Erreur d'ajout : " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    }
}

// UPDATE - Modifier un utilisateur
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int) ($_POST['id'] ?? 0);
    $nom = input_clean($_POST['nom'] ?? '');
    $prenom = input_clean($_POST['prenom'] ?? '');
    if ($id <= 0 || $nom === '' || $prenom === '') {
        $error = "Données de modification invalides.";
    } else {
        $sql = "UPDATE utilisateurs SET nom=?, prenom=? WHERE id=?";
        $stmt = mysqli_prepare($mysqli, $sql);
        mysqli_stmt_bind_param($stmt, 'ssi', $nom, $prenom, $id);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Utilisateur modifié avec succès.";
        } else {
            $error = "Erreur de modification : " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    }
}

// DELETE - Supprimer un utilisateur
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        $error = "ID invalide pour suppression.";
    } else {
        $sql = "DELETE FROM utilisateurs WHERE id=?";
        $stmt = mysqli_prepare($mysqli, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $id);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Utilisateur supprimé avec succès.";
        } else {
            $error = "Erreur de suppression : " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    }
}

// --- Récupérer tous les utilisateurs ---
$sql = "SELECT id, nom, prenom FROM utilisateurs ORDER BY id DESC";
$result = mysqli_query($mysqli, $sql);
$users = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    mysqli_free_result($result);
}

mysqli_close($mysqli);
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Gestion Utilisateurs CRUD avec Modals</title>
    <!-- Bootstrap CSS (cdn) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons (cdn) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            padding: 20px;
            min-height: 100vh;
            color: #000;
        }
        h1 {
            color: #fff;
            margin-bottom: 30px;
            font-weight: 900;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
            font-size: clamp(2rem, 5vw, 3.5rem);
        }
        /* Réduire le padding des boutons sur les petits écrans */
        @media (max-width: 767.98px) {
            .btn-sm {
                padding: .25rem .5rem;
            }
            /* Cacher l'ID et Prénom sur petits écrans */
            .table th:nth-child(1), .table td:nth-child(1),
            .table th:nth-child(3), .table td:nth-child(3) {
                display: none;
            }
        }
        @media (min-width: 768px) {
            .table th:nth-child(1), .table td:nth-child(1),
            .table th:nth-child(3), .table td:nth-child(3) {
                display: table-cell;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h1 class="text-center">Gestion des utilisateurs</h1>

    <!-- Messages -->
    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Bouton ajouter -->
    <div class="mb-3 text-end">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus"></i> <span class="btn-text">Ajouter un utilisateur</span>
        </button>
    </div>

    <!-- Tableau utilisateurs -->
    <div class="table-responsive bg-white rounded shadow-sm p-3">
        <table class="table table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Code Apprenant</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($users) === 0): ?>
                    <tr><td colspan="5" class="text-center">Il n'y a pas d'enregistrement</td></tr>
                <?php else: 
                    $year = date('Y');
                    $year_code = substr($year, -2);
                    $initial = strtoupper(substr($nom_entreprise, 0, 1));
                    $counter = 1; // Pour affichage séquentiel si besoin
                    foreach ($users as $u):
                        // Construction code apprenant : deux derniers chiffres année + 1ère lettre entreprise + id
                        // Exemple: 25s123
                        $code_apprenant = $year_code . $initial ."". $u['id'];
                ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($code_apprenant) ?></td>
                    <td><?= htmlspecialchars($u['nom']) ?></td>
                    <td><?= htmlspecialchars($u['prenom']) ?></td>
                    <td>
                        <button 
                            class="btn btn-sm btn-warning" 
                            data-bs-toggle="modal" 
                            data-bs-target="#editModal"
                            data-id="<?= $u['id'] ?>"
                            data-nom="<?= htmlspecialchars($u['nom'], ENT_QUOTES) ?>"
                            data-prenom="<?= htmlspecialchars($u['prenom'], ENT_QUOTES) ?>"
                        ><i class="bi bi-pencil"></i><span class="btn-text">Modifier</span></button>

                        <button 
                            class="btn btn-sm btn-danger" 
                            data-bs-toggle="modal" 
                            data-bs-target="#deleteModal"
                            data-id="<?= $u['id'] ?>"
                        ><i class="bi bi-trash"></i><span class="btn-text">Supprimer</span></button>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Ajouter -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="action" value="add">
      <div class="modal-header">
        <h5 class="modal-title" id="addModalLabel"><i class="bi bi-plus"></i> Ajouter un utilisateur</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body">
          <div class="mb-3">
            <label for="addNom" class="form-label">Nom</label>
            <input type="text" class="form-control" id="addNom" name="nom" required>
          </div>
          <div class="mb-3">
            <label for="addPrenom" class="form-label">Prénom</label>
            <input type="text" class="form-control" id="addPrenom" name="prenom" required>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
        <button type="submit" class="btn btn-primary">Ajouter</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Modifier -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="editId" value="">
      <div class="modal-header">
        <h5 class="modal-title" id="editModalLabel">Modifier un utilisateur</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body">
          <div class="mb-3">
            <label for="editNom" class="form-label">Nom</label>
            <input type="text" class="form-control" id="editNom" name="nom" required>
          </div>
          <div class="mb-3">
            <label for="editPrenom" class="form-label">Prénom</label>
            <input type="text" class="form-control" id="editPrenom" name="prenom" required>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
        <button type="submit" class="btn btn-warning">Modifier</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Supprimer -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" id="deleteId" value="">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteModalLabel">Supprimer un utilisateur</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body">
        <p>Êtes-vous sûr de vouloir supprimer cet utilisateur ?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Non</button>
        <button type="submit" class="btn btn-danger">Oui, supprimer</button>
      </div>
    </form>
  </div>
</div>

<!-- Bootstrap JS (cdn) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Remplissage modal modifier avec données de l'utilisateur sélectionné
var editModal = document.getElementById('editModal');
editModal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var id = button.getAttribute('data-id');
    var nom = button.getAttribute('data-nom');
    var prenom = button.getAttribute('data-prenom');

    editModal.querySelector('#editId').value = id;
    editModal.querySelector('#editNom').value = nom;
    editModal.querySelector('#editPrenom').value = prenom;
});

// Remplissage modal supprimer avec id utilisateur sélectionné
var deleteModal = document.getElementById('deleteModal');
deleteModal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var id = button.getAttribute('data-id');
    deleteModal.querySelector('#deleteId').value = id;
});
</script>

</body>
</html>
