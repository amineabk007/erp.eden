# Étape 4 — Multi-tenant (Multi-Entreprise)

Date: 2026-02-13

## Pourquoi
Sans multi-tenant, impossible de vendre l'app à plusieurs entreprises en SaaS.

## Stratégie simple (recommandée pour démarrer)
**Single database + company_id** sur les tables métier.

### Tables à lier à une entreprise
- clients
- products
- invoices + invoice_details
- orders + order_details
- delivery_notes
- payments
- categories, units (selon besoin)

## Règle d'or
Toute requête doit être filtrée par `company_id`.

## Implémentation
- Migration: `companies`
- Colonnes `company_id` sur tables métier
- Middleware: `ensure.company`
- Trait: `BelongsToCompany`
- (Optionnel) Global scope: filtre automatique company_id

✅ Livrable:
- isolation des données par entreprise.