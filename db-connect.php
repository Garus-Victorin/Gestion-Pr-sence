<?php
// db-connect.php
// Connexion MySQLi à la base de données centre_formation

$host = 'localhost';
$db   = 'centre_formation';
$user = 'root'; // À adapter selon votre configuration
$pass = '';     // À adapter selon votre configuration
$charset = 'utf8mb4';
// Nom de l'entreprise pour le code apprenant
$nom_entreprise = 'CFRII';

// Configuration du rapport d'erreurs pour MySQLi
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Connexion à la base de données
$mysqli = mysqli_connect($host, $user, $pass, $db);

// Gestion des erreurs de connexion
if (!$mysqli) {
    exit('Erreur de connexion à la base de données : ' . mysqli_connect_error());
}

// Définir l'encodage des caractères
mysqli_set_charset($mysqli, $charset);

?>
