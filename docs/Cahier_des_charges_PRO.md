# Cahier des charges — ERP (version PRO)

Date: 2026-02-13

## 1. Contexte
Application ERP web pour la gestion commerciale (clients, produits, devis, factures, BL, paiements) destinée aux PME.

## 2. Périmètre fonctionnel
### 2.1 Gestion clients / fournisseurs
- CRUD clients
- Historique (devis, factures, paiements)
- Solde & relances

### 2.2 Catalogue produits
- CRUD produits, catégories, unités
- SKU / code produit
- Prix & taxes

### 2.3 Devis
- création devis, lignes, PDF
- conversion devis -> facture

### 2.4 Facturation
- numérotation automatique
- statuts (draft/sent/paid/overdue)
- TVA (taux paramétrable)
- PDF, export

### 2.5 Bons de livraison (BL)
- création BL, lien facture
- PDF

### 2.6 Paiements
- multi-modes, pièces jointes (optionnel)
- paiement partiel
- calcul reste à payer

### 2.7 Dashboard
- CA, impayés, TVA
- filtres période

## 3. Périmètre SaaS
- multi-entreprises (company_id)
- rôles & permissions
- plans (Free/Pro/Business)
- onboarding entreprise

## 4. Exigences techniques
- Laravel 11+
- Auth sécurisée
- logs & audit
- tests feature minimum
- déploiement Docker (dev) + VPS/cloud (prod)
- backups automatiques

## 5. Livrables
- Code source + migrations
- Documentation installation
- Documentation API (si activée)
- Maquettes UI (option)
- Plan de test