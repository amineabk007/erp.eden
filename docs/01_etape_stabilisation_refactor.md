# Étape 1 — Stabilisation & Refactor (Tech Lead Plan)

Date: 2026-02-13

Objectif: rendre le code **maintenable**, **sécurisé**, et prêt pour l'évolution (SaaS).

## 1) Refactor logique métier (Business Logic)
### Problème courant
- La logique métier finit souvent dans les Controllers.
- Difficile à tester / maintenir.

### Action recommandée
Créer / compléter une couche `app/Services` et/ou `app/Actions`:

- `InvoiceService` : création, validation, calcul TVA, total, statuts
- `PaymentService` : enregistrement paiements, calcul reste à payer
- `StockService` (si gestion stock) : mouvements, disponibilité
- `ClientService` : règles clients (plafonds, relances)

✅ Livrable:
- Services appelés depuis Controllers (Controllers fins)

## 2) Validation centralisée (Form Requests)
- Utiliser `app/Http/Requests/*` pour:
  - Store/Update Client
  - Store/Update Product
  - Store Invoice, Add lines
  - Store Payment

✅ Livrable:
- Toutes les routes d'écriture (POST/PUT/PATCH) utilisent des FormRequest.

## 3) Gestion d'erreurs & logs
- Standardiser les réponses d'erreurs (flash message web + logs)
- Ajouter un canal `daily` côté logging (si pas déjà)

✅ Livrable:
- logs plus lisibles, erreurs traçables

## 4) Nettoyage & conventions
- Nommage: singular/plural cohérent
- `softDeletes` là où nécessaire (déjà présent sur delivery_notes)

✅ Livrable:
- Code style cohérent (PSR-12), structure claire

## 5) Tests minimum (smoke tests)
- Tests de base:
  - login
  - création client
  - création facture
  - paiement

✅ Livrable:
- 5–10 tests Feature pour éviter les régressions.

--- 
Checklist rapide:
- [ ] Controllers fins
- [ ] Services & Requests en place
- [ ] Logging OK
- [ ] Tests smoke OK