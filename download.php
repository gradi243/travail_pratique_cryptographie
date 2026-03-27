<?php
/**
 * download.php
 * Script sécurisé de téléchargement des fichiers temporaires
 */

$temp_dir = __DIR__ . '/temp/';

if (!isset($_GET['file']) || empty($_GET['file'])) {
    die("Fichier non spécifié.");
}

$filename = basename($_GET['file']);
$filepath = $temp_dir . $filename;

if (!file_exists($filepath) || !is_file($filepath)) {
    die("Le fichier demandé n'existe pas.");
}

// Forcer le téléchargement
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($filepath);
exit;
?>