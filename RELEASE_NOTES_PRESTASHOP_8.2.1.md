# Release notes — Compatibilité PrestaShop 8.2.1

## Contexte
Cette version fait évoluer le module initialement compatible PrestaShop 1.7 afin d'assurer un fonctionnement conforme avec **PrestaShop 8.2.1**, en conservant les parcours métier de synchronisation des commandes, de gestion des retours et de mapping des catégories.

## Évolutions fonctionnelles

### 1) Compatibilité globale PrestaShop 8.2.1
- Le module a été adapté pour fonctionner sur PrestaShop 8.2.1.
- Les flux existants ont été validés sur les scénarios métier critiques (synchronisation, retours, statuts et mapping).

### 2) Synchronisation des commandes expédiées
- **Commande expédiée mono-produit** : synchronisation OK, contrôle BDD OK.
- **Commande expédiée multi-produits** : synchronisation OK, contrôle BDD OK.
- **Commande avec statut "en cours de prépa"** : pas de synchronisation (comportement attendu), puis synchronisation OK après passage au statut "à expédier".
- **Prise en compte de l'évolution des statuts de synchro** : ajout d'un statut supplémentaire correctement pris en charge.
- **Commande livrée déjà synchronisée** : mise à jour après changement de statut correctement exécutée.

### 3) Gestion des retours (front office)
- **Commande produit simple** : affichage du bouton de retour OK.
- **Parcours retour au clic** : fonctionnement OK.
- **Dossier retour déjà initié** : non-affichage du bouton retour OK (évite les doublons).
- **Dossier retour clôturé** : ré-affichage du bouton retour OK.
- **Commande multi-produits** : affichage du bouton retour OK.

### 4) Gestion des erreurs et observabilité
- **Commande contenant un produit sans dimensions** : la synchronisation est bloquée en erreur avec des logs explicites (comportement attendu et diagnostiquable).

### 5) Mapping catégories
- Le mapping des catégories supporte correctement :
  - l'ajout,
  - la modification,
  - l'affichage.

## Résumé d'impact
- Migration fonctionnelle réussie vers PrestaShop 8.2.1.
- Aucun écart observé sur les parcours métier testés.
- Meilleure robustesse opérationnelle via logs explicites sur cas d'erreur de données produit.
