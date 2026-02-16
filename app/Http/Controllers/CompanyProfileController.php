<?php

namespace App\Http\Controllers;

use App\Models\CompanyProfile;
use App\Models\DeliveryNote;
use App\Models\Invoice;
use App\Models\NumberingSequence;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CompanyProfileController extends Controller
{
    public function edit()
    {
        $company = CompanyProfile::first() ?? CompanyProfile::create([]);
        return view('settings.company', compact('company'));
    }

    public function update(Request $request)
    {
        $company = CompanyProfile::first() ?? CompanyProfile::create([]);

        $data = $request->validate([
            'name' => ['nullable','string','max:255'],
            'address' => ['nullable','string','max:255'],
            'city' => ['nullable','string','max:255'],
            'phone' => ['nullable','string','max:50'],
            'email' => ['nullable','email','max:255'],
            'ice' => ['nullable','string','max:50'],
            'if' => ['nullable','string','max:50'],
            'rc' => ['nullable','string','max:50'],
            'cnss' => ['nullable','string','max:50'],
            'patente' => ['nullable','string','max:50'],
            'footer_notes' => ['nullable','string','max:2000'],
            'logo' => ['nullable','image','max:2048'],
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo_path'] = $path;
        }

        unset($data['logo']);

        $company->update($data);

        return back()->with('success', 'Données entreprise mises à jour ✅');
    }

    /**
     * Reset/sync annual numbering sequences for the given year (default: current year).
     * This does NOT delete any documents; it only adjusts counters so the next number is correct.
     */
    public function resetYear(Request $request)
    {
        $year = (int) ($request->input('year') ?: now()->format('Y'));

        return DB::transaction(function () use ($year) {
            // Helper to extract the max sequence from codes like PREFIX-YYYY-0001
            $maxFrom = function (string $table, string $column, string $prefix) use ($year): int {
                $like = sprintf('%s-%d-%%', $prefix, $year);

                $row = DB::table($table)
                    ->selectRaw("MAX(CAST(SUBSTRING_INDEX($column, '-', -1) AS UNSIGNED)) as m")
                    ->where($column, 'like', $like)
                    ->first();

                return (int) ($row->m ?? 0);
            };

            $maxCmd = $maxFrom('orders', 'order_code', 'CMD');
            $maxBl  = $maxFrom('delivery_notes', 'delivery_code', 'BL');
            $maxInv = $maxFrom('invoices', 'invoice_code', 'INV');

            // Upsert sequences
            NumberingSequence::updateOrCreate(
                ['type' => 'order', 'year' => $year],
                ['last_number' => $maxCmd]
            );

            NumberingSequence::updateOrCreate(
                ['type' => 'delivery_note', 'year' => $year],
                ['last_number' => $maxBl]
            );

            NumberingSequence::updateOrCreate(
                ['type' => 'invoice', 'year' => $year],
                ['last_number' => $maxInv]
            );

            // Next numbers (informational)
            $nextCmd = sprintf('CMD-%d-%04d', $year, $maxCmd + 1);
            $nextBl  = sprintf('BL-%d-%04d', $year, $maxBl + 1);
            $nextInv = sprintf('INV-%d-%04d', $year, $maxInv + 1);

            return back()->with('success', "Numérotation synchronisée pour $year ✅ Prochains codes: $nextCmd / $nextBl / $nextInv");
        });
    }
}
