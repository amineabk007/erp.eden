# Multi-tenant — Quickstart

1) Migrer la DB:
- `php artisan migrate`

2) Créer une company (tinker ou seeder)
- `php artisan tinker`
- `\App\Models\Company::create(['name' => 'Demo Company']);`

3) Associer les users à une company_id (à faire dans User model / migration si besoin)

4) Stocker la company courante en session:
- lors du login, définir `session(['company_id' => $user->company_id]);`

5) Filtrer toutes les queries par company_id (ou utiliser un global scope).