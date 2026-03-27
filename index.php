<?php
/**
 * ============================================================================
 * CryptoLab - Page d'accueil
 * ============================================================================
 * Outil de chiffrement/déchiffrement AES & DES
 * Version 2.0 - Architecture modulaire
 * 
 * @version 2.0
 * @author Votre Nom
 */

// Configuration
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérification OpenSSL
if (!extension_loaded('openssl')) {
    die("
    <div style='
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        color: #e6e6e6;
        font-family: \"Segoe UI\", Arial, sans-serif;
        padding: 40px;
        border-radius: 15px;
        max-width: 600px;
        margin: 50px auto;
        text-align: center;
    '>
        <div style='font-size: 100px; margin-bottom: 20px; color: #ff4757;'>⚠️</div>
        <h2 style='color: #ff6b81;'>Extension OpenSSL Requise</h2>
        <p>L'extension OpenSSL n'est pas activée sur votre serveur.</p>
    </div>
    ");
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CryptoLab - Outil de Chiffrement AES/DES</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            color: #f1f5f9;
            line-height: 1.6;
            min-height: 100vh;
        }

        .hero {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(16, 185, 129, 0.2) 100%);
            padding: 80px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #6366f1 0%, #10b981 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 1.2rem;
            color: #cbd5e1;
            max-width: 600px;
            margin: 0 auto 30px;
        }

        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
            margin-top: 40px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            padding: 20px 30px;
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #6366f1;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #94a3b8;
            margin-top: 5px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }

        .feature-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border-color: rgba(99, 102, 241, 0.5);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 20px;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #6366f1;
        }

        .feature-card p {
            color: #cbd5e1;
            margin-bottom: 25px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #10b981 0%, #0da271 100%);
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        }

        .comparison {
            background: rgba(30, 41, 59, 0.5);
            border-radius: 20px;
            padding: 40px;
            margin-top: 40px;
        }

        .comparison h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #6366f1;
        }

        .comparison-table {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .algo-card {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 15px;
            padding: 25px;
            border-left: 4px solid;
        }

        .algo-card.aes {
            border-left-color: #10b981;
        }

        .algo-card.des {
            border-left-color: #f59e0b;
        }

        .algo-card h3 {
            font-size: 1.3rem;
            margin-bottom: 15px;
        }

        .algo-card ul {
            list-style: none;
            padding-left: 0;
        }

        .algo-card li {
            padding: 8px 0;
            color: #cbd5e1;
        }

        .algo-card li:before {
            content: "✓";
            color: #10b981;
            margin-right: 10px;
        }

        .footer {
            text-align: center;
            padding: 40px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #94a3b8;
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }
            
            .comparison-table {
                grid-template-columns: 1fr;
            }
            
            .features {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="hero">
        <h1><i class="fas fa-lock"></i> CryptoLab</h1>
        <div class="hero-subtitle">
            Plateforme professionnelle de chiffrement et déchiffrement de données
        </div>
        <div class="hero-stats">
            <div class="stat-card">
                <div class="stat-number">AES-256</div>
                <div class="stat-label">Chiffrement ultra-sécurisé</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">3DES</div>
                <div class="stat-label">Compatibilité héritée</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">10 MB</div>
                <div class="stat-label">Taille max fichiers</div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="features">
            <div class="feature-card">
                <div class="feature-icon">🔐</div>
                <h3>AES - Standard Moderne</h3>
                <p>Chiffrement avancé recommandé par le gouvernement américain. 128/192/256 bits de sécurité maximale.</p>
                <a href="AES.php" class="btn btn-primary">
                    <i class="fas fa-arrow-right"></i>
                    Utiliser AES
                </a>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🔓</div>
                <h3>DES/3DES - Compatibilité</h3>
                <p>Pour les systèmes hérités. Triple DES offre une sécurité renforcée avec 168 bits.</p>
                <a href="DES.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-right"></i>
                    Utiliser DES/3DES
                </a>
            </div>
        </div>

        <div class="comparison">
            <h2><i class="fas fa-chart-simple"></i> Comparaison des algorithmes</h2>
            <div class="comparison-table">
                <div class="algo-card aes">
                    <h3><i class="fas fa-shield-haltered"></i> AES (Advanced Encryption Standard)</h3>
                    <ul>
                        <li>Standard officiel depuis 2001</li>
                        <li>Clés : 128, 192 ou 256 bits</li>
                        <li>Très rapide et sécurisé</li>
                        <li>Recommandé pour tous les usages</li>
                        <li>Utilisé par les gouvernements</li>
                        <li>Résistant aux attaques connues</li>
                    </ul>
                </div>
                <div class="algo-card des">
                    <h3><i class="fas fa-clock"></i> DES/3DES (Data Encryption Standard)</h3>
                    <ul>
                        <li>Standard historique (1977)</li>
                        <li>DES : 56 bits (obsolète)</li>
                        <li>3DES : 168 bits (acceptable)</li>
                        <li>Plus lent que AES</li>
                        <li>Compatibilité avec anciens systèmes</li>
                        <li>Progressivement remplacé par AES</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="comparison" style="margin-top: 30px;">
            <h2><i class="fas fa-graduation-cap"></i> Concepts cryptographiques</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div class="algo-card" style="border-left-color: #6366f1;">
                    <h3>Principe de Kerckhoffs</h3>
                    <p style="font-size: 0.9rem;">La sécurité d'un système ne doit pas reposer sur l'obscurité de son algorithme, mais uniquement sur la clé.</p>
                </div>
                <div class="algo-card" style="border-left-color: #6366f1;">
                    <h3>Chiffrement symétrique</h3>
                    <p style="font-size: 0.9rem;">Même clé pour chiffrer et déchiffrer. Rapide mais nécessite un échange sécurisé de la clé.</p>
                </div>
                <div class="algo-card" style="border-left-color: #6366f1;">
                    <h3>Vecteur d'initialisation (IV)</h3>
                    <p style="font-size: 0.9rem;">Évite que des messages identiques produisent le même chiffré. Généré aléatoirement à chaque opération.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>CryptoLab - Outil éducatif de chiffrement | <a href="https://github.com" style="color: #6366f1;">Documentation</a></p>
        <p style="margin-top: 10px; font-size: 0.8rem;">© 2024 - Projet de cryptographie</p>
    </div>
</body>
</html>