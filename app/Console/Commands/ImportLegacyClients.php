<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Client;

class ImportLegacyClients extends Command
{
    protected $signature = 'legacy:import-clients {company_id} {--skip-existing=1}';
    protected $description = 'Import clients from legacy v1_db into current ERP database (multi-company)';

    public function handle()
    {
        $companyId = (int) $this->argument('company_id');
        $skipExisting = (int) $this->option('skip-existing') === 1;

        $this->info("Importing legacy clients into company_id={$companyId} …");

        // Legacy structure (from v1):
        // id, client_code, client_name, client_type, telephone, email, website, status_id, created_at, updated_at
        $legacyClients = DB::connection('legacy')->table('clients')->orderBy('id')->get();

        $imported = 0;
        $skipped = 0;

        DB::transaction(function () use ($legacyClients, $companyId, $skipExisting, &$imported, &$skipped) {

            foreach ($legacyClients as $lc) {

                $name = trim((string)($lc->client_name ?? $lc->name ?? ''));
                if ($name === '') {
                    $skipped++;
                    continue;
                }

                // ✅ avoid duplicates by name within same company
                if ($skipExisting) {
                    $exists = Client::where('company_id', $companyId)
                        ->where('name', $name)
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        continue;
                    }
                }

                $payload = [
                    'company_id' => $companyId,
                    'name'       => $name,
                ];

                // Fill optional fields ONLY if they exist in ERP table
                $clientColumns = DB::getSchemaBuilder()->getColumnListing('clients');

                if (in_array('type', $clientColumns, true)) {
                    $payload['type'] = $lc->client_type ?? 'Client';
                }

                if (in_array('phone', $clientColumns, true)) {
                    $payload['phone'] = $lc->telephone ?? null;
                }

                if (in_array('email', $clientColumns, true)) {
                    $payload['email'] = $lc->email ?? null;
                }

                if (in_array('website', $clientColumns, true)) {
                    $payload['website'] = $lc->website ?? null;
                }

                if (in_array('code', $clientColumns, true)) {
                    $payload['code'] = $lc->client_code ?? null; // only if column exists
                }

                // timestamps (if your Client model uses timestamps, Laravel will handle them)
                Client::create($payload);

                $imported++;
            }
        });

        $this->info("✅ Clients imported: {$imported}");
        $this->info("⏭️ Skipped (empty/duplicates): {$skipped}");

        return self::SUCCESS;
    }
}
