<?php
session_start();
require_once 'db-connect.php'; // Connexion $mysqli

// Définition des plages horaires pour le calcul du statut (pour l'arrivée entreprise et les autres événements)
$schedules = [
    'arrivee_entreprise' => ['debut' => '08:00:00','14:00:00', 'fin' => '10:00:00','18:00:00'], // Exemple de plage pour arrivée entreprise
    'depart_entreprise' => ['debut' => '17:00:00', 'fin' => '19:00:00'], // Exemple de plage pour départ entreprise
    'arrivee_pause' => ['debut' => '12:00:00', 'fin' => '12:30:00'],     // Exemple de plage pour arrivée pause
    'depart_pause' => ['debut' => '13:30:00', 'fin' => '14:00:00'],      // Exemple de plage pour départ pause
];

// Requête pour récupérer les présences et utilisateurs
$sql = "SELECT 
            p.id AS presence_id,
            p.utilisateur_id,
            p.date_presence,
            p.arrivee_entreprise,
            p.depart_entreprise,
            p.arrivee_pause,
            p.depart_pause,
            u.nom,
            u.prenom
        FROM presences p
        JOIN utilisateurs u ON u.id = p.utilisateur_id
        ORDER BY p.date_presence DESC, u.nom, u.prenom";

$res = mysqli_query($mysqli, $sql);
if (!$res) {
    die("Erreur SQL: " . mysqli_error($mysqli));
}

// Organisation des données par date
$grouped = [];
while ($row = mysqli_fetch_assoc($res)) {
    $date = $row['date_presence'];
    if (!isset($grouped[$date])) {
        $grouped[$date] = [];
    }
    $grouped[$date][] = $row;
}
mysqli_free_result($res);


// Fonction pour déterminer le statut visuel d'une heure donnée (généralisée)
function getTimeStatusVisual(?string $time, array $validRange = null): array {
    if (is_null($time) || $time === '00:00:00') { // Ajout de '00:00:00' si jamais la BDD le renvoie
        return ['label' => 'Non renseigné', 'class' => 'badge-secondary', 'icon' => '<i class="bi bi-question-circle"></i>'];
    }

    if ($validRange === null || !isset($validRange['debut']) || !isset($validRange['fin'])) {
        // Si aucune plage horaire n'est définie pour ce type d'heure, on considère juste que c'est enregistré
        return ['label' => 'Enregistré', 'class' => 'badge-info', 'icon' => '<i class="bi bi-info-circle"></i>'];
    }

    $hTs = strtotime($time);
    $rangeDebutTs = strtotime($validRange['debut']);
    $rangeFinTs = strtotime($validRange['fin']);

    if ($hTs >= $rangeDebutTs && $hTs <= $rangeFinTs) {
        return ['label' => 'À l\'heure', 'class' => 'badge-success', 'icon' => '<i class="bi bi-check-circle"></i>'];
    } else {
        return ['label' => 'Retard', 'class' => 'badge-danger', 'icon' => '<i class="bi bi-x-circle"></i>'];
    }
}

// Fonction pour afficher la date en français au format long sans utiliser strftime (PHP 8+)
function formatDateFr(string $date): string {
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dt) {
        return htmlspecialchars($date);
    }
    if (class_exists('IntlDateFormatter')) {
        $formatter = new IntlDateFormatter(
            'fr_FR',
            IntlDateFormatter::FULL,
            IntlDateFormatter::NONE,
            'Europe/Paris',
            IntlDateFormatter::GREGORIAN
        );
        return ucfirst($formatter->format($dt));
    }
    // Fallback simple si l'extension intl n'est pas activée (peu probable sur un serveur moderne)
    return $dt->format('d/m/Y');
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Tableau de bord des présences</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
<style>
    :root {
        --text-color: #333;
        --border-color: #eee;
        --header-color: hsl(230, 70%, 40%);
    }
html, body {
    height: 100%;
    margin: 0;
    background: white;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding: 2rem 1rem 4rem; /* Ajusté padding horizontal */
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: hsl(230, 70%, 40%);
}

    .main-container {
        max-width: 1200px; /* Augmenté la largeur max pour mieux utiliser l'écran */
        width: 100%; /* S'assure que le container prend toute la largeur dispo */
        padding: 2.5rem 3rem;
        border-radius: 1rem;
        min-height: 80vh;
        animation: fadeIn 1s ease-out;
    }

    h1 {
        text-align: center;
        font-weight: 700;
        margin-bottom: 2.5rem;
        color: var(--header-color);
        font-size: 2.5rem;
        letter-spacing: 0.05em;
        position: relative;
    }
    h1::after {
        content: '';
        display: block;
        width: 80px;
        height: 4px;
        background: hsl(230, 60%, 50%);
        margin: 1rem auto 0;
        border-radius: 2px;
    }
    h2.date-heading {
        margin-top: 3.5rem;
        margin-bottom: 2rem;
        font-weight: 600;
        color: hsl(230, 60%, 50%);
        padding-bottom: 0.5rem;
        border-bottom: 2px solid hsl(230, 70%, 80%);
        font-size: 1.8rem;
        text-align: left;
    }
    .presence-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); /* Largeur minimale ajustée */
        gap: 1.5rem;
        margin-top: 1.5rem;
    }
    .presence-card {
        background: linear-gradient(135deg, #f9fbfd, #eef3f7);
        border-radius: 0.8rem;
        padding: 1.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        border: 1px solid #e0e6eb;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .presence-card:hover {
        transform: translateY(-5px);
        background:hsl(230, 70%, 80%);
    }
    .card-header {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px dashed #d1d9e0;
    }
    .card-header .name {
        font-weight: 700;
        font-size: 1.25rem;
        color: var(--header-color);
        margin-left: 0.75rem;
    }
    .card-body p {
        margin-bottom: 0.8rem; /* Plus d'espace entre les lignes */
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        flex-wrap: wrap; /* Permet aux éléments de passer à la ligne */
    }
    .card-body p strong {
        color: #555;
        min-width: 130px; /* Alignement */
        display: inline-block;
        margin-right: 0.5rem; /* Espace entre libellé et valeur/badge */
    }
    .card-body p i {
        margin-right: 8px;
        color: var(--primary-gradient-middle);
    }
    .time-value-group {
        display: flex;
        align-items: center;
        gap: 8px; /* Espace entre le badge et l'heure */
    }
    .time-value {
        font-weight: 500;
        color: #333;
        font-family: monospace; /* Pour une meilleure lisibilité des heures */
    }
    .text-placeholder {
        color: #adb5bd;
        font-style: italic;
    }
    .status-mini-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.2em 0.5em; /* Ajusté padding */
        border-radius: 0.35rem; /* Rayon du badge */
        color: white;
        font-size: 0.85rem; /* Taille de police du badge */
        font-weight: 600;
        min-width: 70px; /* Largeur minimale pour les badges */
        text-align: center;
    }
    .status-mini-badge i {
        font-size: 1em; /* Icône proportionnelle */
        margin-right: 0.3em;
    }
    /* Couleurs des badges Bootstrap */
    .badge-success { background-color: #28a745; } /* Vert */
    .badge-danger { background-color: #dc3545; } /* Rouge */
    .badge-secondary { background-color: #6c757d; } /* Gris pour non renseigné */
    .badge-info { background-color: #17a2b8; } /* Bleu clair pour "Enregistré" sans plage */

    .no-records {
        text-align: center;
        padding: 3rem;
        font-size: 1.2rem;
        color: #777;
        background: redempre;
        border-radius: 0.8rem;
        margin-top: 2rem;
        border: 1px dashed #ced4da;
    }
    @media (max-width: 768px) {
        .main-container {
            padding: 1.5rem;
            width: 98%;
        }
        h1 {
            font-size: 2rem;
            margin-bottom: 2rem;
        }
        h2.date-heading {
            font-size: 1.5rem;
        }
        .presence-grid {
            grid-template-columns: 1fr; /* Une seule colonne sur mobile */
            gap: 1rem;
        }
        .presence-card {
            padding: 1.2rem;
        }
        .card-header .name {
            font-size: 1.1rem;
        }
        .card-body p strong {
            min-width: 100px;
        }
        .time-value-group {
            flex-direction: column; /* Badges au-dessus des heures sur mobile si besoin */
            align-items: flex-start;
            gap: 4px;
        }
    }
</style>
</head>
<body>
<div class="main-container" role="main" aria-label="Tableau de bord des présences">
    <h1>Tableau de bord des présences</h1>

    <?php if (empty($grouped)): ?>
        <div class="no-records">
            <p>Aucun enregistrement de présence n'est disponible pour le moment.</p>
            <p>Commencez à enregistrer des présences pour les voir apparaître ici.</p>
        </div>
    <?php else: ?>
        <?php
        foreach ($grouped as $date => $presences):
            $formattedDate = formatDateFr($date);
        ?>
            <h2 class="date-heading"><?= htmlspecialchars($formattedDate) ?></h2>
            <div class="presence-grid">
                <?php foreach ($presences as $presence): ?>
                    <div class="presence-card" tabindex="0" aria-label="Présence de <?= htmlspecialchars($presence['prenom'] . ' ' . $presence['nom']) ?>">
                        <div class="card-header">
                            <div class="name">
                                <?= htmlspecialchars($presence['prenom']) . ' ' . htmlspecialchars($presence['nom']) ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <p>
                                <i class="bi bi-person-fill"></i>
                                <strong>Arrivée Entreprise:</strong>
                                <span class="time-value-group">
                                    <?php $status_ae = getTimeStatusVisual($presence['arrivee_entreprise'], $schedules['arrivee_entreprise']); ?>
                                    <span class="status-mini-badge <?= $status_ae['class'] ?>">
                                        <?= $status_ae['icon'] ?>
                                        <?= $status_ae['label'] ?>
                                    </span>
                                    <span class="time-value"><?= $presence['arrivee_entreprise'] ?? '<span class="text-placeholder">--:--</span>' ?></span>
                                </span>
                            </p>
                            <p>
                                <i class="bi bi-box-arrow-right"></i>
                                <strong>Départ Entreprise:</strong>
                                <span class="time-value-group">
                                    <?php $status_de = getTimeStatusVisual($presence['depart_entreprise'], $schedules['depart_entreprise']); ?>
                                    <span class="status-mini-badge <?= $status_de['class'] ?>">
                                        <?= $status_de['icon'] ?>
                                        <?= $status_de['label'] ?>
                                    </span>
                                    <span class="time-value"><?= $presence['depart_entreprise'] ?? '<span class="text-placeholder">--:--</span>' ?></span>
                                </span>
                            </p>
                            <p>
                                <i class="bi bi-pause-fill"></i>
                                <strong>Arrivée Pause:</strong>
                                <span class="time-value-group">
                                    <?php $status_ap = getTimeStatusVisual($presence['arrivee_pause'], $schedules['arrivee_pause']); ?>
                                    <span class="status-mini-badge <?= $status_ap['class'] ?>">
                                        <?= $status_ap['icon'] ?>
                                        <?= $status_ap['label'] ?>
                                    </span>
                                    <span class="time-value"><?= $presence['arrivee_pause'] ?? '<span class="text-placeholder">--:--</span>' ?></span>
                                </span>
                            </p>
                            <p>
                                <i class="bi bi-box-arrow-in-left"></i>
                                <strong>Départ Pause:</strong>
                                <span class="time-value-group">
                                    <?php $status_dp = getTimeStatusVisual($presence['depart_pause'], $schedules['depart_pause']); ?>
                                    <span class="status-mini-badge <?= $status_dp['class'] ?>">
                                        <?= $status_dp['icon'] ?>
                                        <?= $status_dp['label'] ?>
                                    </span>
                                    <span class="time-value"><?= $presence['depart_pause'] ?? '<span class="text-placeholder">--:--</span>' ?></span>
                                </span>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
