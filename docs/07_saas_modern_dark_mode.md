# Étape 7 — SaaS Modern (Dark Mode)

## Objectif
Introduire une base UI moderne, sombre et cohérente pour tout le produit ERP.

## Modifications livrées
- Palette globale EDEN dark (bg/sidebar/panel/primary/accent/text/danger/warning).
- `layouts/app.blade.php` comme layout unique avec dark mode forcé (`<html class="dark">`).
- Sidebar fixe avec 6 modules clés (Dashboard, Clients, Produits, Commandes, BL, Factures).
- État actif avec barre verte à gauche + mode collapse (icônes seules).
- Composants UI réutilisables:
  - cards, tables, boutons primary/secondary,
  - inputs dark avec focus vert,
  - style ready pour modales centrées avec backdrop blur.
- Dashboard modernisé:
  - 4 KPI cards,
  - bar chart CA mensuel,
  - pie chart statuts facture,
  - tables factures récentes + BL récents.
- Pages tables dark de base (Clients, Produits, Factures index):
  - header sombre,
  - hover léger,
  - actions icônes,
  - barre search + filtre,
  - pagination minimale.
- Vue facture écran (`invoices/show`) orientée lisibilité:
  - header compact société,
  - tableau principal large,
  - totaux dans une card à droite,
  - montant en lettres,
  - footer simple.

## Typographie
- Police Inter (Google Fonts).
- Titres semibold, texte regular.
- Chiffres tabular (`font-feature-settings: 'tnum' 1`).

## Stack
- Tailwind CSS
- Blade
- Alpine.js (collapse sidebar)
- Chart.js (dashboard)
