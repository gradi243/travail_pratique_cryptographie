<?php
/**
 * ============================================================================
 * CRYPTOLAB - AES (Advanced Encryption Standard)
 * ============================================================================
 * Module de chiffrement/déchiffrement AES 128/192/256 bits
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
 * Classe AesCrypteur - Version corrigée pour les fichiers
 */
class AesCrypteur {
    private $methode;
    
    public function __construct($methode = 'AES-256-CBC') {
        $this->methode = $methode;
    }
    
    /**
     * Chiffre un texte avec AES
     */
    public function chiffrer($texte, $password) {
        // Déterminer la taille de clé
        $key_size = (int) substr($this->methode, 4, 3) / 8;
        $key = substr(hash('sha256', $password, true), 0, $key_size);
        
        // Générer IV
        $iv_length = openssl_cipher_iv_length($this->methode);
        $iv = openssl_random_pseudo_bytes($iv_length);
        
        // Chiffrement
        $chiffre = openssl_encrypt($texte, $this->methode, $key, OPENSSL_RAW_DATA, $iv);
        
        if ($chiffre === false) {
            throw new Exception("Erreur de chiffrement AES");
        }
        
        // Retourner IV + données chiffrées en base64
        return base64_encode($iv . $chiffre);
    }
    
    /**
     * Déchiffre un texte avec AES
     */
    public function dechiffrer($texteChiffre, $password) {
        // Décodage base64
        $data = base64_decode($texteChiffre);
        if ($data === false) {
            throw new Exception("Format base64 invalide");
        }
        
        // Extraire IV
        $iv_length = openssl_cipher_iv_length($this->methode);
        $iv = substr($data, 0, $iv_length);
        $chiffre = substr($data, $iv_length);
        
        // Générer clé
        $key_size = (int) substr($this->methode, 4, 3) / 8;
        $key = substr(hash('sha256', $password, true), 0, $key_size);
        
        // Déchiffrement
        $texte = openssl_decrypt($chiffre, $this->methode, $key, OPENSSL_RAW_DATA, $iv);
        
        if ($texte === false) {
            throw new Exception("Erreur de déchiffrement AES - Vérifiez votre mot de passe");
        }
        
        return $texte;
    }
    
    /**
     * Chiffre un fichier - Version corrigée
     */
    public function chiffrerFichier($fichierEntree, $fichierSortie, $password) {
        // Lire le fichier en binaire
        $contenu = file_get_contents($fichierEntree);
        if ($contenu === false) {
            throw new Exception("Impossible de lire le fichier source");
        }
        
        // Chiffrer le contenu
        $contenuChiffre = $this->chiffrer($contenu, $password);
        
        // Sauvegarder en binaire
        $resultat = file_put_contents($fichierSortie, $contenuChiffre);
        if ($resultat === false) {
            throw new Exception("Impossible d'écrire le fichier chiffré");
        }
        
        return true;
    }
    
    /**
     * Déchiffre un fichier - Version corrigée
     */
    public function dechiffrerFichier($fichierEntree, $fichierSortie, $password) {
        // Lire le fichier chiffré
        $contenuChiffre = file_get_contents($fichierEntree);
        if ($contenuChiffre === false) {
            throw new Exception("Impossible de lire le fichier chiffré");
        }
        
        // Déchiffrer le contenu
        $contenu = $this->dechiffrer($contenuChiffre, $password);
        
        // Sauvegarder le résultat
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
                    <button class='btn-copy' onclick='copierTexte(this)'>
                        📋 Copier
                    </button>
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
            <a href='download.php?file=" . urlencode($nom_fichier) . "' class='btn-download'>
                📥 Télécharger le fichier
            </a>
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
$message_type = '';

// Traitement texte AES
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'process_aes_text') {
            $operation = $_POST['aes_operation'] ?? '';
            $texte = $_POST['aes_texte'] ?? '';
            $cle = $_POST['aes_cle'] ?? '';
            $method = $_POST['aes_method'] ?? 'AES-256-CBC';
            
            if (empty($texte) || empty($cle)) {
                throw new Exception("Le texte et la clé sont requis");
            }
            
            $crypteur = new AesCrypteur($method);
            
            if ($operation === 'chiffrer') {
                $resultat = $crypteur->chiffrer($texte, $cle);
                afficherResultat("Texte chiffré avec succès", "🔒", [
                    'Résultat' => $resultat,
                    'Algorithme' => $method,
                    'Force' => substr($method, 4, 3) . ' bits'
                ]);
            } else {
                $resultat = $crypteur->dechiffrer($texte, $cle);
                afficherResultat("Texte déchiffré avec succès", "🔓", [
                    'Résultat' => $resultat,
                    'Algorithme' => $method,
                    'Force' => substr($method, 4, 3) . ' bits'
                ]);
            }
        }
        
        // Traitement fichier AES
        elseif ($_POST['action'] === 'process_aes_file') {
            if (!isset($_FILES['aes_fichier']) || $_FILES['aes_fichier']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Veuillez sélectionner un fichier valide");
            }
            
            $operation = $_POST['aes_file_operation'] ?? '';
            $cle = $_POST['aes_file_cle'] ?? '';
            $method = $_POST['aes_file_method'] ?? 'AES-256-CBC';
            
            if (empty($cle)) {
                throw new Exception("Le mot de passe est requis");
            }
            
            $crypteur = new AesCrypteur($method);
            
            $fichier_temp = $_FILES['aes_fichier']['tmp_name'];
            $nom_original = $_FILES['aes_fichier']['name'];
            $taille = $_FILES['aes_fichier']['size'];
            
            if ($taille > 10485760) {
                throw new Exception("Fichier trop volumineux (max 10MB)");
            }
            
            $timestamp = time();
            if ($operation === 'chiffrer') {
                $nom_sortie = 'aes_chiffre_' . pathinfo($nom_original, PATHINFO_FILENAME) . '_' . $timestamp . '.aes';
                $action_text = 'chiffré';
            } else {
                $nom_sortie = 'aes_dechiffre_' . pathinfo($nom_original, PATHINFO_FILENAME) . '_' . $timestamp . '.' . pathinfo($nom_original, PATHINFO_EXTENSION);
                $action_text = 'déchiffré';
            }
            
            $chemin_sortie = $temp_dir . $nom_sortie;
            
            if ($operation === 'chiffrer') {
                $crypteur->chiffrerFichier($fichier_temp, $chemin_sortie, $cle);
            } else {
                $crypteur->dechiffrerFichier($fichier_temp, $chemin_sortie, $cle);
            }
            
            afficherSuccesFichier("Fichier $action_text avec succès", [
                'Fichier original' => $nom_original,
                'Taille' => formatTaille($taille),
                'Algorithme' => $method,
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
        $message = "Fichier supprimé avec succès";
        $message_type = "success";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CryptoLab - AES (Advanced Encryption Standard)</title>
    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #0da271;
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
            background: rgba(16, 185, 129, 0.2);
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
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
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
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, var(--bg-secondary) 100%);
            border: 1px solid rgba(16, 185, 129, 0.3);
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
            background: rgba(16, 185, 129, 0.2);
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
            <div class="nav-brand">🔐 CryptoLab - AES</div>
            <div class="nav-links">
                <a href="index.php" class="nav-link">Accueil</a>
                <a href="AES.php" class="nav-link active">AES</a>
                <a href="DES.php" class="nav-link">DES/3DES</a>
            </div>
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
                    <input type="hidden" name="action" value="process_aes_text">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Opération</label>
                            <select name="aes_operation" class="form-control" required>
                                <option value="chiffrer">🔒 Chiffrer</option>
                                <option value="dechiffrer">🔓 Déchiffrer</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Force AES</label>
                            <select name="aes_method" class="form-control">
                                <option value="AES-256-CBC">AES-256-CBC (256 bits - Recommandé)</option>
                                <option value="AES-192-CBC">AES-192-CBC (192 bits)</option>
                                <option value="AES-128-CBC">AES-128-CBC (128 bits)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Texte</label>
                        <textarea name="aes_texte" class="form-control" placeholder="Entrez votre texte..." required>Message secret à protéger avec AES-256</textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Mot de passe</label>
                        <input type="text" name="aes_cle" class="form-control" value="MonMotDePasseSuperSecret123" required>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">🚀 Exécuter</button>
                        <button type="button" class="btn btn-outline" onclick="exempleAES()">📋 Charger exemple</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Onglet Fichier -->
        <div id="tab-file" class="tab-content">
            <div class="form-container">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="process_aes_file">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Opération</label>
                            <select name="aes_file_operation" class="form-control" required>
                                <option value="chiffrer">🔒 Chiffrer un fichier</option>
                                <option value="dechiffrer">🔓 Déchiffrer un fichier</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Force AES</label>
                            <select name="aes_file_method" class="form-control">
                                <option value="AES-256-CBC">AES-256-CBC</option>
                                <option value="AES-192-CBC">AES-192-CBC</option>
                                <option value="AES-128-CBC">AES-128-CBC</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Fichier</label>
                        <input type="file" name="aes_fichier" class="form-control" required>
                        <small style="color: #94a3b8;">Taille maximale : 10 MB</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Mot de passe</label>
                        <input type="text" name="aes_file_cle" class="form-control" value="MonMotDePasseSuperSecret123" required>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">⚡ Traiter</button>
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
        
        function exempleAES() {
            document.querySelector('textarea[name="aes_texte"]').value = 
                "Message ultra sécurisé avec AES-256.\n" +
                "AES est le standard de chiffrement moderne utilisé par les gouvernements.\n" +
                "Il offre une sécurité maximale et des performances excellentes.";
            document.querySelector('input[name="aes_cle"]').value = 'ExempleAES2024!@#SuperSecurise';
            document.querySelector('select[name="aes_method"]').value = 'AES-256-CBC';
            afficherNotification('Exemple AES chargé !');
        }
        
        function telechargerExemple() {
            const content = `FICHIER EXEMPLE POUR TEST AES

Ce fichier permet de tester le chiffrement AES.
Date: ${new Date().toLocaleString()}
Taille: Environ 300 octets

Instructions:
1. Choisissez "Chiffrer"
2. Entrez un mot de passe
3. Cliquez sur "Traiter"
4. Téléchargez le fichier chiffré
5. Pour déchiffrer, utilisez le même mot de passe

Ce fichier est parfait pour vos tests !`;
            
            const blob = new Blob([content], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'test_aes.txt';
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
                background: #10b981;
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
                background: rgba(16, 185, 129, 0.1);
                color: #10b981;
            }
            .tab-btn.active {
                background: rgba(16, 185, 129, 0.2);
                color: #10b981;
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