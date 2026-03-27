<?php
/**
 * ============================================================================
 * CRYPTOLAB - DES/3DES (Data Encryption Standard)
 * ============================================================================
 * Module de chiffrement/déchiffrement DES et Triple DES
 * 
 * @version 2.0
 * @author Votre Nom
 */

session_start();

// Configuration
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérification OpenSSL
if (!extension_loaded('openssl')) {
    die("<h2>Extension OpenSSL requise</h2>");
}

// Dossier temporaire
$temp_dir = __DIR__ . '/temp/';
if (!is_dir($temp_dir)) {
    mkdir($temp_dir, 0755, true);
    file_put_contents($temp_dir . '.htaccess', "Deny from all\n");
}

// Nettoyage fichiers temporaires
if (is_dir($temp_dir)) {
    $files = glob($temp_dir . '*');
    $now = time();
    foreach ($files as $file) {
        if (is_file($file) && ($now - filemtime($file) > 3600)) {
            unlink($file);
        }
    }
}

/**
 * Classe DesCrypteur - Version corrigée pour les fichiers
 */
class DesCrypteur {
    private $methode;
    
    public function __construct($methode = 'DES-EDE3-CBC') {
        $this->methode = $methode;
    }
    
    /**
     * Prépare une clé à partir d'une phrase secrète
     */
    public function preparerCle($passphrase) {
        if ($this->methode === 'DES-CBC') {
            return substr(hash('sha256', $passphrase, true), 0, 8);
        } else {
            $hash = hash('sha256', $passphrase, true);
            return str_pad($hash, 24, $hash);
        }
    }
    
    /**
     * Génère un vecteur d'initialisation (8 octets)
     */
    public function genererIV() {
        return openssl_random_pseudo_bytes(8);
    }
    
    /**
     * Chiffre un texte
     */
    public function chiffrer($texte, $cle, $iv) {
        $resultat = openssl_encrypt($texte, $this->methode, $cle, OPENSSL_RAW_DATA, $iv);
        
        if ($resultat === false) {
            throw new Exception("Erreur de chiffrement");
        }
        
        return base64_encode($resultat);
    }
    
    /**
     * Déchiffre un texte
     */
    public function dechiffrer($texteChiffre, $cle, $iv) {
        $data = base64_decode($texteChiffre);
        if ($data === false) {
            throw new Exception("Données base64 invalides");
        }
        
        $resultat = openssl_decrypt($data, $this->methode, $cle, OPENSSL_RAW_DATA, $iv);
        
        if ($resultat === false) {
            throw new Exception("Erreur de déchiffrement - Vérifiez votre clé et IV");
        }
        
        return $resultat;
    }
    
    /**
     * Chiffre un fichier - Version corrigée
     */
    public function chiffrerFichier($fichierEntree, $fichierSortie, $cle, $iv) {
        $contenu = file_get_contents($fichierEntree);
        if ($contenu === false) {
            throw new Exception("Impossible de lire le fichier");
        }
        
        $contenuChiffre = $this->chiffrer($contenu, $cle, $iv);
        $resultat = file_put_contents($fichierSortie, $contenuChiffre);
        
        if ($resultat === false) {
            throw new Exception("Impossible d'écrire le fichier chiffré");
        }
        
        return true;
    }
    
    /**
     * Déchiffre un fichier - Version corrigée
     */
    public function dechiffrerFichier($fichierEntree, $fichierSortie, $cle, $iv) {
        $contenuChiffre = file_get_contents($fichierEntree);
        if ($contenuChiffre === false) {
            throw new Exception("Impossible de lire le fichier chiffré");
        }
        
        $contenu = $this->dechiffrer($contenuChiffre, $cle, $iv);
        $resultat = file_put_contents($fichierSortie, $contenu);
        
        if ($resultat === false) {
            throw new Exception("Impossible d'écrire le fichier déchiffré");
        }
        
        return true;
    }
}

// ============================================================================
// FONCTIONS D'AFFICHAGE
// ============================================================================

function formatTaille($octets) {
    $unites = ['o', 'Ko', 'Mo', 'Go'];
    if ($octets == 0) return '0 o';
    $i = floor(log($octets, 1024));
    return round($octets / pow(1024, $i), 2) . ' ' . $unites[$i];
}

function afficherResultat($titre, $icone, $donnees) {
    echo "
    <div class='result-box'>
        <div class='result-header'>
            <span class='result-icon'>$icone</span>
            <h3>$titre</h3>
        </div>
        <div class='result-content'>";
    
    foreach ($donnees as $label => $valeur) {
        if ($label === 'Résultat') {
            echo "
            <div class='result-field'>
                <label>$label :</label>
                <div class='code-box'>
                    <pre>" . htmlspecialchars($valeur) . "</pre>
                    <button class='btn-copy' onclick='copierTexte(this)'>📋 Copier</button>
                </div>
            </div>";
        } else {
            echo "
            <div class='result-field'>
                <label>$label :</label>
                <span class='result-value'>" . htmlspecialchars($valeur) . "</span>
            </div>";
        }
    }
    
    echo "</div></div>";
}

function afficherSuccesFichier($titre, $donnees, $nom_fichier) {
    echo "
    <div class='success-box'>
        <div class='success-header'>
            <span class='success-icon'>✅</span>
            <h3>$titre</h3>
        </div>
        <div class='success-content'>";
    
    foreach ($donnees as $label => $valeur) {
        echo "
        <div class='success-field'>
            <label>$label :</label>
            <span class='success-value'>" . htmlspecialchars($valeur) . "</span>
        </div>";
    }
    
    echo "
        </div>
        <div class='success-actions'>
            <a href='download.php?file=" . urlencode($nom_fichier) . "' class='btn-download'>📥 Télécharger le fichier</a>
        </div>
    </div>";
}

function afficherErreur($titre, $message) {
    echo "
    <div class='error-box'>
        <div class='error-header'>
            <span class='error-icon'>⚠️</span>
            <h3>$titre</h3>
        </div>
        <div class='error-content'>
            <p>$message</p>
        </div>
    </div>";
}

function listerFichiers() {
    $temp_dir = $GLOBALS['temp_dir'];
    $files = glob($temp_dir . '*');
    
    if (count($files) > 0) {
        echo "<div class='file-list'>
                <h4>📁 Fichiers disponibles</h4>
                <div class='file-grid'>";
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $filename = basename($file);
                $filesize = filesize($file);
                $filetime = date('H:i', filemtime($file));
                
                echo "
                <div class='file-item'>
                    <div class='file-icon'>📄</div>
                    <div class='file-info'>
                        <div class='file-name'>" . htmlspecialchars($filename) . "</div>
                        <div class='file-details'>
                            <span>" . formatTaille($filesize) . "</span>
                            <span>$filetime</span>
                        </div>
                    </div>
                    <div class='file-actions'>
                        <a href='download.php?file=" . urlencode($filename) . "' class='file-btn'>📥</a>
                        <a href='?delete=" . urlencode($filename) . "' class='file-btn' onclick='return confirm(\"Supprimer ?\")'>🗑️</a>
                    </div>
                </div>";
            }
        }
        
        echo "</div></div>";
    }
}

// ============================================================================
// TRAITEMENT DES FORMULAIRES
// ============================================================================

$message = '';

// Traitement texte DES
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'process_des_text') {
            $operation = $_POST['operation'] ?? '';
            $texte = $_POST['texte'] ?? '';
            $cle = $_POST['cle'] ?? '';
            $mode = $_POST['mode'] ?? 'DES-EDE3-CBC';
            $iv_hex = $_POST['iv'] ?? '';
            
            if (empty($texte) || empty($cle)) {
                throw new Exception("Le texte et la clé sont requis");
            }
            
            $crypteur = new DesCrypteur($mode);
            $cle_binaire = $crypteur->preparerCle($cle);
            
            if (!empty($iv_hex)) {
                $iv = hex2bin($iv_hex);
                if ($iv === false) {
                    throw new Exception("Format d'IV invalide");
                }
            } else {
                $iv = $crypteur->genererIV();
                $iv_hex = bin2hex($iv);
            }
            
            if ($operation === 'chiffrer') {
                $resultat = $crypteur->chiffrer($texte, $cle_binaire, $iv);
                afficherResultat("Texte chiffré", "🔒", [
                    'Résultat' => $resultat,
                    'Algorithme' => $mode,
                    'IV (hex)' => $iv_hex,
                    'Longueur clé' => strlen($cle_binaire) . ' octets'
                ]);
            } else {
                $resultat = $crypteur->dechiffrer($texte, $cle_binaire, $iv);
                afficherResultat("Texte déchiffré", "🔓", [
                    'Résultat' => $resultat,
                    'Algorithme' => $mode,
                    'IV (hex)' => $iv_hex
                ]);
            }
        }
        
        // Traitement fichier DES
        elseif ($_POST['action'] === 'process_des_file') {
            if (!isset($_FILES['fichier']) || $_FILES['fichier']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Veuillez sélectionner un fichier");
            }
            
            $operation = $_POST['file_operation'] ?? '';
            $cle = $_POST['file_cle'] ?? '';
            $mode = $_POST['file_mode'] ?? 'DES-EDE3-CBC';
            $iv_hex = $_POST['file_iv'] ?? '';
            
            if (empty($cle)) {
                throw new Exception("La clé est requise");
            }
            
            $crypteur = new DesCrypteur($mode);
            $cle_binaire = $crypteur->preparerCle($cle);
            
            if (!empty($iv_hex)) {
                $iv = hex2bin($iv_hex);
            } else {
                $iv = $crypteur->genererIV();
                $iv_hex = bin2hex($iv);
            }
            
            $fichier_temp = $_FILES['fichier']['tmp_name'];
            $nom_original = $_FILES['fichier']['name'];
            $taille = $_FILES['fichier']['size'];
            
            if ($taille > 10485760) {
                throw new Exception("Fichier trop volumineux (max 10MB)");
            }
            
            $timestamp = time();
            if ($operation === 'chiffrer') {
                $nom_sortie = 'des_chiffre_' . pathinfo($nom_original, PATHINFO_FILENAME) . '_' . $timestamp . '.des';
                $action_text = 'chiffré';
            } else {
                $nom_sortie = 'des_dechiffre_' . pathinfo($nom_original, PATHINFO_FILENAME) . '_' . $timestamp . '.' . pathinfo($nom_original, PATHINFO_EXTENSION);
                $action_text = 'déchiffré';
            }
            
            $chemin_sortie = $temp_dir . $nom_sortie;
            
            if ($operation === 'chiffrer') {
                $crypteur->chiffrerFichier($fichier_temp, $chemin_sortie, $cle_binaire, $iv);
            } else {
                $crypteur->dechiffrerFichier($fichier_temp, $chemin_sortie, $cle_binaire, $iv);
            }
            
            afficherSuccesFichier("Fichier $action_text avec succès", [
                'Fichier original' => $nom_original,
                'Taille' => formatTaille($taille),
                'Algorithme' => $mode,
                'IV utilisé (hex)' => $iv_hex,
                'Fichier généré' => $nom_sortie
            ], $nom_sortie);
        }
        
    } catch (Exception $e) {
        afficherErreur("Erreur", $e->getMessage());
    }
}

// Suppression de fichier
if (isset($_GET['delete'])) {
    $file = basename($_GET['delete']);
    $filepath = $temp_dir . $file;
    if (file_exists($filepath) && is_file($filepath)) {
        unlink($filepath);
        $message = "Fichier supprimé";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CryptoLab - DES/3DES</title>
    <style>
        :root {
            --primary: #f59e0b;
            --primary-dark: #d97706;
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --text-primary: #f1f5f9;
            --text-secondary: #cbd5e1;
            --border: #475569;
            --radius: 12px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, var(--bg-primary) 0%, #1e1b4b 100%);
            color: var(--text-primary);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .nav-bar {
            background: var(--bg-secondary);
            padding: 15px 30px;
            border-radius: var(--radius);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .nav-brand {
            font-size: 1.5rem;
            font-weight: bold;
            background: linear-gradient(135deg, var(--primary) 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .nav-links {
            display: flex;
            gap: 15px;
        }
        
        .nav-link {
            color: var(--text-secondary);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(245, 158, 11, 0.2);
            color: var(--primary);
        }
        
        .form-container {
            background: var(--bg-secondary);
            padding: 30px;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-secondary);
            font-weight: 600;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            background: var(--bg-tertiary);
            border: 2px solid var(--border);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 1rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
            font-family: monospace;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(245, 158, 11, 0.3);
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid var(--border);
            color: var(--text-secondary);
        }
        
        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        
        .result-box, .success-box, .error-box {
            padding: 25px;
            border-radius: var(--radius);
            margin-bottom: 30px;
            animation: slideIn 0.5s ease;
        }
        
        .result-box {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, var(--bg-secondary) 100%);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        
        .error-box {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, var(--bg-secondary) 100%);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .success-box {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, var(--bg-secondary) 100%);
            border: 1px solid rgba(16, 185, 129, 0.5);
        }
        
        .result-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .result-icon {
            font-size: 2rem;
        }
        
        .result-field {
            margin-bottom: 15px;
        }
        
        .result-field label {
            color: var(--text-secondary);
            font-weight: 600;
            display: block;
            margin-bottom: 5px;
        }
        
        .code-box {
            position: relative;
            background: #1a1a2e;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .code-box pre {
            padding: 15px;
            overflow-x: auto;
            font-family: monospace;
            margin: 0;
        }
        
        .btn-copy {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(245, 158, 11, 0.2);
            border: 1px solid var(--primary);
            color: var(--primary);
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .btn-download {
            display: inline-block;
            background: var(--primary);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 15px;
        }
        
        .file-list {
            margin-top: 30px;
        }
        
        .file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .file-item {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .file-info {
            flex: 1;
        }
        
        .file-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .file-details {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }
        
        .file-actions {
            display: flex;
            gap: 10px;
        }
        
        .file-btn {
            background: transparent;
            border: 1px solid var(--border);
            padding: 8px 12px;
            border-radius: 5px;
            text-decoration: none;
            color: var(--text-secondary);
            cursor: pointer;
        }
        
        .file-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
        
        .warning-box {
            background: rgba(245, 158, 11, 0.1);
            border-left: 4px solid var(--primary);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 768px) {
            .nav-bar {
                flex-direction: column;
                text-align: center;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-bar">
            <div class="nav-brand">🔐 CryptoLab - DES/3DES</div>
            <div class="nav-links">
                <a href="index.php" class="nav-link">Accueil</a>
                <a href="AES.php" class="nav-link">AES</a>
                <a href="DES.php" class="nav-link active">DES/3DES</a>
            </div>
        </div>
        
        <div class="warning-box">
            <strong>⚠️ Attention :</strong> DES standard (56 bits) est considéré comme obsolète.<br>
            Utilisez 3DES (Triple DES) pour une meilleure sécurité.
        </div>
        
        <?php if ($message): ?>
            <div class="success-box">
                <div class="result-header">
                    <span class="result-icon">✅</span>
                    <h3><?php echo htmlspecialchars($message); ?></h3>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Onglets -->
        <div class="tabs" style="display: flex; gap: 5px; margin-bottom: 20px; background: var(--bg-secondary); padding: 10px; border-radius: var(--radius);">
            <button class="tab-btn active" onclick="showTab('text')">📝 Texte</button>
            <button class="tab-btn" onclick="showTab('file')">📁 Fichier</button>
        </div>
        
        <!-- Onglet Texte -->
        <div id="tab-text" class="tab-content active">
            <div class="form-container">
                <form method="POST">
                    <input type="hidden" name="action" value="process_des_text">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Opération</label>
                            <select name="operation" class="form-control" required>
                                <option value="chiffrer">🔒 Chiffrer</option>
                                <option value="dechiffrer">🔓 Déchiffrer</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Algorithme</label>
                            <select name="mode" class="form-control">
                                <option value="DES-EDE3-CBC">3DES-CBC (Triple DES - 168 bits - Recommandé)</option>
                                <option value="DES-CBC">DES-CBC (56 bits - Obsolète)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Texte</label>
                        <textarea name="texte" class="form-control" placeholder="Entrez votre texte..." required>Message à protéger avec 3DES</textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Clé secrète</label>
                            <input type="text" name="cle" class="form-control" value="MaCleSecrete123" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">IV (hexadécimal)</label>
                            <input type="text" name="iv" class="form-control" placeholder="Laisser vide pour générer auto">
                            <small style="color: #94a3b8;">16 caractères hex (ex: a1b2c3d4e5f67890)</small>
                        </div>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">🚀 Exécuter</button>
                        <button type="button" class="btn btn-outline" onclick="genererIV()">🎲 Générer IV</button>
                        <button type="button" class="btn btn-outline" onclick="genererCle()">🔑 Générer clé</button>
                        <button type="button" class="btn btn-outline" onclick="exempleDES()">📋 Charger exemple</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Onglet Fichier -->
        <div id="tab-file" class="tab-content">
            <div class="form-container">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="process_des_file">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Opération</label>
                            <select name="file_operation" class="form-control" required>
                                <option value="chiffrer">🔒 Chiffrer un fichier</option>
                                <option value="dechiffrer">🔓 Déchiffrer un fichier</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Algorithme</label>
                            <select name="file_mode" class="form-control">
                                <option value="DES-EDE3-CBC">3DES-CBC (Triple DES)</option>
                                <option value="DES-CBC">DES-CBC</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Fichier</label>
                        <input type="file" name="fichier" class="form-control" required>
                        <small style="color: #94a3b8;">Taille maximale : 10 MB</small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Clé secrète</label>
                            <input type="text" name="file_cle" class="form-control" value="MaCleSecrete123" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">IV (hexadécimal)</label>
                            <input type="text" name="file_iv" class="form-control" placeholder="Laisser vide pour générer auto">
                        </div>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">⚡ Traiter</button>
                        <button type="button" class="btn btn-outline" onclick="genererIVFile()">🎲 Générer IV</button>
                        <button type="button" class="btn btn-outline" onclick="telechargerExemple()">📄 Télécharger exemple</button>
                    </div>
                </form>
            </div>
            
            <?php listerFichiers(); ?>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            document.getElementById(`tab-${tabName}`).classList.add('active');
            event.currentTarget.classList.add('active');
        }
        
        function genererIV() {
            const array = new Uint8Array(8);
            window.crypto.getRandomValues(array);
            const iv = Array.from(array, byte => byte.toString(16).padStart(2, '0')).join('');
            document.querySelector('#tab-text input[name="iv"]').value = iv;
            afficherNotification('IV généré !');
        }
        
        function genererIVFile() {
            const array = new Uint8Array(8);
            window.crypto.getRandomValues(array);
            const iv = Array.from(array, byte => byte.toString(16).padStart(2, '0')).join('');
            document.querySelector('#tab-file input[name="file_iv"]').value = iv;
            afficherNotification('IV généré !');
        }
        
        function genererCle() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*_-+=';
            let length = Math.floor(Math.random() * 11) + 16;
            let cle = '';
            for (let i = 0; i < length; i++) {
                cle += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.querySelectorAll('input[name="cle"], input[name="file_cle"]').forEach(input => {
                input.value = cle;
            });
            afficherNotification('Clé générée !');
        }
        
        function exempleDES() {
            document.querySelector('#tab-text textarea[name="texte"]').value = 
                "Message secret avec 3DES (Triple DES).\n" +
                "3DES applique DES trois fois pour une sécurité renforcée.\n" +
                "Conservez votre clé et votre IV pour déchiffrer !";
            document.querySelector('#tab-text input[name="cle"]').value = 'Exemple3DES2024';
            document.querySelector('#tab-text input[name="iv"]').value = 'a1b2c3d4e5f67890';
            document.querySelector('#tab-text select[name="mode"]').value = 'DES-EDE3-CBC';
            afficherNotification('Exemple DES chargé !');
        }
        
        function telechargerExemple() {
            const content = `FICHIER EXEMPLE POUR TEST 3DES

Ce fichier permet de tester le chiffrement 3DES.
Date: ${new Date().toLocaleString()}

Instructions:
1. Choisissez "Chiffrer"
2. Entrez une clé et un IV
3. Cliquez sur "Traiter"
4. Téléchargez le fichier chiffré
5. Pour déchiffrer, utilisez la même clé et le même IV

Note: 3DES est plus lent qu'AES mais offre une bonne compatibilité.`;
            
            const blob = new Blob([content], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'test_3des.txt';
            a.click();
            URL.revokeObjectURL(url);
            afficherNotification('Fichier exemple téléchargé !');
        }
        
        function copierTexte(button) {
            const pre = button.closest('.code-box').querySelector('pre');
            navigator.clipboard.writeText(pre.textContent);
            button.textContent = '✅ Copié !';
            setTimeout(() => button.textContent = '📋 Copier', 2000);
            afficherNotification('Texte copié !');
        }
        
        function afficherNotification(message) {
            const notification = document.createElement('div');
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #f59e0b;
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                z-index: 1000;
                animation: slideIn 0.3s ease;
            `;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        }
        
        // CSS pour les onglets
        const style = document.createElement('style');
        style.textContent = `
            .tabs {
                display: flex;
                gap: 5px;
                background: #1e293b;
                padding: 10px;
                border-radius: 12px;
                margin-bottom: 20px;
            }
            .tab-btn {
                flex: 1;
                padding: 12px 24px;
                background: transparent;
                border: none;
                color: #94a3b8;
                font-weight: 600;
                cursor: pointer;
                border-radius: 8px;
                transition: all 0.3s;
            }
            .tab-btn:hover {
                background: rgba(245, 158, 11, 0.1);
                color: #f59e0b;
            }
            .tab-btn.active {
                background: rgba(245, 158, 11, 0.2);
                color: #f59e0b;
            }
            .tab-content {
                display: none;
            }
            .tab-content.active {
                display: block;
                animation: fadeIn 0.5s ease;
            }
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>