# travail_pratique_cryptographie
TP DE RECHERCHE D’ÉLÉMENTS DE CRYPTOGRAPHIE ET  DE CRYPTANALYSE 


---

## 🔬 Algorithmes implémentés

### AES (Advanced Encryption Standard)

| Caractéristique | Valeur |
|-----------------|--------|
| **Année** | 2001 |
| **Tailles de clé** | 128, 192, 256 bits |
| **Mode** | CBC (Cipher Block Chaining) |
| **Sécurité** | ★★★★★ (Recommandé) |
| **Performance** | Très rapide |
| **Utilisation** | Données sensibles, stockage sécurisé, communications |

**Principe de fonctionnement** :
1. Dérivation de la clé à partir du mot de passe via SHA-256
2. Génération d'un vecteur d'initialisation (IV) aléatoire
3. Chiffrement par blocs avec mode CBC
4. Stockage de l'IV en tête des données chiffrées

### DES / 3DES (Data Encryption Standard)

| Caractéristique | DES | 3DES |
|-----------------|-----|------|
| **Année** | 1977 | 1998 |
| **Taille de clé** | 56 bits | 168 bits |
| **Sécurité** | ★☆☆☆☆ | ★★★☆☆ |
| **Performance** | Lente | Très lente |
| **Utilisation** | Obsolète | Compatibilité héritée |

**Note importante** : DES est considéré comme cryptographiquement faible depuis 1999 (attaque par force brute en 22 heures). 3DES reste acceptable pour la compatibilité avec d'anciens systèmes.

---

## 📚 Guide d'utilisation

### Chiffrement de texte avec AES

```mermaid
graph LR
    A[Saisir le texte] --> B[Choisir force AES]
    B --> C[Entrer mot de passe]
    C --> D[Cliquer sur Exécuter]
    D --> E[Récupérer texte chiffré]
