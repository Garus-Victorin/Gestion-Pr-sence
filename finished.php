<?php
$categorie = $_GET['categorie'] ?? '';

$messages = [
    'arrivee_entreprise' => "Bienvenue à l'entreprise !",
    'depart_entreprise' => "Bon appétit et bonne pause !",
    'arrivee_pause' => "Bon retour de pause !",
    'depart_pause' => "Reposez-vous bien durant votre pause !"
];

$message = $messages[$categorie] ?? "Enregistrement effectué.";

?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Fin d'enregistrement</title></head>
<body>
<h1><?= htmlspecialchars($message) ?></h1>
<a href="index.php">Retour</a>
</body>
</html>
