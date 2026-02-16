<?php

namespace App\Http\Controllers;

use App\Models\CompanyProfile;
use App\Models\Category;
use App\Models\DeliveryNote;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Product;
use App\Models\Unit;
use App\Services\NumberingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InvoiceController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id;

        $invoices = Invoice::with(['client'])
            ->where('company_id', $companyId)
            ->orderByDesc('id')
            ->paginate(20);

        return view('invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice)
    {
        $this->authorizeCompany($invoice);

        $invoice->load(['client', 'details.product', 'deliveryNotes.order.client', 'deliveryNotes.order.details.product']);

        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        $this->authorizeCompany($invoice);

        $companyId = $invoice->company_id;

        // BLs of same company (we keep it simple: allow any BL of the company)
        $bls = DeliveryNote::with('order.client')
            ->where('company_id', $companyId)
            ->orderByDesc('id')
            ->get();

        $invoice->load('deliveryNotes');

        return view('invoices.edit', compact('invoice', 'bls'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->authorizeCompany($invoice);

        $data = $request->validate([
            'invoice_date' => ['nullable', 'date'],
            'status_id' => ['required', 'integer', 'exists:statuses,id'],
            'delivery_note_ids' => ['nullable', 'array'],
            'delivery_note_ids.*' => ['integer', 'exists:delivery_notes,id'],
            'invoice_type' => ['nullable', 'in:detailed,recap'],
        ]);

        return DB::transaction(function () use ($invoice, $data) {

            if (!empty($data['invoice_date'])) {
                $invoice->invoice_date = $data['invoice_date'];
            }

            $invoice->status_id = (int) $data['status_id'];

            if (!empty($data['invoice_type'])) {
                $invoice->invoice_type = $data['invoice_type'];
            }

            // ✅ Sync BLs
            $blIds = array_map('intval', $data['delivery_note_ids'] ?? []);
            $invoice->deliveryNotes()->sync($blIds);

            // ✅ Fetch BLs (multi-company safe)
            $bls = DeliveryNote::with(['order.details.product'])
                ->whereIn('id', $blIds)
                ->where('company_id', $invoice->company_id)
                ->get();

            // ✅ Recalculate total from BLs
            $total = 0.0;
            foreach ($bls as $bl) {
                $blTotal = (float) ($bl->total_amount ?? 0);

                if ($blTotal <= 0 && $bl->order && $bl->order->details) {
                    $blTotal = (float) $bl->order->details->sum(fn ($d) => ((float)($d->quantity ?? 0)) * ((float)($d->unit_price ?? 0)));
                }

                $total += $blTotal;
            }

            $invoice->total = $total;
            $invoice->save();

            // ✅ Rebuild invoice_details from BLs (so print/pdf show lines)
            $this->rebuildInvoiceDetailsFromBls($invoice, $bls);

            return redirect()->route('invoices.show', $invoice->id)
                ->with('success', 'Facture mise à jour ✅');
        });
    }

    // -----------------------------
    // Create invoice from BL
    // -----------------------------
    public function createFromBl()
    {
        $companyId = auth()->user()->company_id;

        $clients = \App\Models\Client::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        // Simple list of BLs (optionally filter "non facturés" if you want later)
        $bls = DeliveryNote::with('order.client')
            ->where('company_id', $companyId)
            ->orderByDesc('id')
            ->get();

        return view('invoices.create_from_bl', compact('clients', 'bls'));
    }

    public function storeFromBl(Request $request, NumberingService $numbering)
    {
        $companyId = auth()->user()->company_id;

        $data = $request->validate([
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'bl_ids' => ['required', 'array', 'min:1'],
            'bl_ids.*' => ['integer', 'exists:delivery_notes,id'],
            'invoice_type' => ['nullable', 'in:detailed,recap'],
        ]);

        return DB::transaction(function () use ($data, $companyId, $numbering) {

            $blIds = array_map('intval', $data['bl_ids']);

            $bls = DeliveryNote::with(['order.details.product'])
                ->whereIn('id', $blIds)
                ->where('company_id', $companyId)
                ->get();

            if ($bls->isEmpty()) {
                return back()->withErrors(['error' => '❌ ما كاين حتى BL صالح لهاد الشركة.'])->withInput();
            }

            // Total
            $total = 0.0;
            foreach ($bls as $bl) {
                $blTotal = (float) ($bl->total_amount ?? 0);

                if ($blTotal <= 0 && $bl->order && $bl->order->details) {
                    $blTotal = (float) $bl->order->details->sum(fn ($d) => ((float)($d->quantity ?? 0)) * ((float)($d->unit_price ?? 0)));
                }

                $total += $blTotal;
            }

            $invoice = Invoice::create([
                'company_id'   => $companyId,
                'invoice_code' => $numbering->next('invoice', 'FA'),
                'client_id'    => (int) $data['client_id'],
                'user_id'      => auth()->id(),
                'status_id'    => 1, // Draft
                'invoice_type' => $data['invoice_type'] ?? 'detailed',
                'total'        => $total,
            ]);

            $invoice->deliveryNotes()->sync($blIds);

            $this->rebuildInvoiceDetailsFromBls($invoice, $bls);

            return redirect()->route('invoices.show', $invoice->id)
                ->with('success', 'Facture créée ✅');
        });
    }

    public function updateStatus(Request $request, Invoice $invoice)
    {
        $this->authorizeCompany($invoice);

        $data = $request->validate([
            'status_id' => ['required', 'integer', 'exists:statuses,id'],
        ]);

        $invoice->status_id = (int) $data['status_id'];
        $invoice->save();

        return back()->with('success', 'Statut mis à jour ✅');
    }

    public function print(Invoice $invoice)
    {
        $this->authorizeCompany($invoice);

        $invoice->load(['client', 'details.product', 'deliveryNotes.order.details.product']);
        $company = CompanyProfile::first();
        if (Schema::hasColumn('company_profiles', 'company_id')) {
            $company = CompanyProfile::where('company_id', $invoice->company_id)->first() ?? $company;
        }
        // ✅ Compute total + recap rows (so views don't depend on undefined variables)
        $total = (float) ($invoice->total ?? ($invoice->total_amount ?? 0));
        if ($total <= 0 && $invoice->relationLoaded('details')) {
            $total = (float) $invoice->details->sum(fn ($d) => ((float)($d->quantity ?? 0)) * ((float)($d->price ?? 0)));
        }
        $recap = [];
        if ($invoice->relationLoaded('details')) {
            $recap = $invoice->details->map(function ($d) {
                return [
                    'product' => $d->product->name ?? ($d->description ?? ''),
                    'qty' => (float) ($d->quantity ?? 0),
                    'unit_price' => (float) ($d->price ?? 0),
                    'amount' => (float) ($d->quantity ?? 0) * (float) ($d->price ?? 0),
                ];
            })->toArray();
        }
        return view('invoices.print', compact('invoice', 'company', 'total', 'recap'));
    }

    public function pdf(Invoice $invoice)
    {
        $this->authorizeCompany($invoice);

        $invoice->load(['client', 'details.product', 'deliveryNotes.order.details.product']);
        $company = CompanyProfile::first();
        if (Schema::hasColumn('company_profiles', 'company_id')) {
            $company = CompanyProfile::where('company_id', $invoice->company_id)->first() ?? $company;
        }
        // ✅ Compute total + recap rows (so views don't depend on undefined variables)
        $total = (float) ($invoice->total ?? ($invoice->total_amount ?? 0));
        if ($total <= 0 && $invoice->relationLoaded('details')) {
            $total = (float) $invoice->details->sum(fn ($d) => ((float)($d->quantity ?? 0)) * ((float)($d->price ?? 0)));
        }
        $recap = [];
        if ($invoice->relationLoaded('details')) {
            $recap = $invoice->details->map(function ($d) {
                return [
                    'product' => $d->product->name ?? ($d->description ?? ''),
                    'qty' => (float) ($d->quantity ?? 0),
                    'unit_price' => (float) ($d->price ?? 0),
                    'amount' => (float) ($d->quantity ?? 0) * (float) ($d->price ?? 0),
                ];
            })->toArray();
        }
        $pdf = \PDF::loadView('invoices.pdf', compact('invoice', 'company', 'total', 'recap'));
        return $pdf->download(($invoice->invoice_code ?: ('FA_'.$invoice->id)) . '.pdf');
    }

    // -----------------------------
    // Helpers
    // -----------------------------
    private function authorizeCompany(Invoice $invoice): void
    {
        $companyId = auth()->user()->company_id;
        if ((int)$invoice->company_id !== (int)$companyId) {
            abort(403);
        }
    }

    private function rebuildInvoiceDetailsFromBls(Invoice $invoice, $bls): void
    {
        // Clean old details
        $invoice->details()->delete();

        $type = $invoice->invoice_type ?? 'detailed';

        // Always initialize to avoid "Undefined variable" notices if the controller gets edited.
        $serviceProduct = null;

        // ✅ For recap invoices, invoice_details.product_id is NOT NULL in DB.
        // We use a dedicated "service" product (created once per company) to hold recap lines.
        if ($type === 'recap') {
            $serviceProduct = $this->getOrCreateRecapServiceProduct((int) $invoice->company_id);
        }

        foreach ($bls as $bl) {

            if ($type === 'recap') {
                // Safety: if something cleared the variable, recreate it.
                if (!$serviceProduct) {
                    $serviceProduct = $this->getOrCreateRecapServiceProduct((int) $invoice->company_id);
                }
                $blTotal = (float) ($bl->total_amount ?? 0);

                if ($blTotal <= 0 && $bl->order && $bl->order->details) {
                    $blTotal = (float) $bl->order->details->sum(fn ($d) => ((float)($d->quantity ?? 0)) * ((float)($d->unit_price ?? 0)));
                }

                InvoiceDetail::create([
                    'company_id'   => $invoice->company_id,
                    'invoice_id'   => $invoice->id,
                    'product_id'   => $serviceProduct->id,
                    'description'  => $bl->delivery_code ?: ('BL#'.$bl->id),
                    'quantity'     => 1,
                    'price'        => $blTotal,
                ]);

                continue;
            }

            // detailed
            if ($bl->order && $bl->order->details) {
                foreach ($bl->order->details as $d) {
                    InvoiceDetail::create([
                        'company_id'  => $invoice->company_id,
                        'invoice_id'  => $invoice->id,
                        'product_id'  => $d->product_id,
                        'description' => $d->product->name ?? null,
                        'quantity'    => (float) ($d->quantity ?? 0),
                        'price'       => (float) ($d->unit_price ?? 0),
                    ]);
                }
            }
        }
    }

    /**
     * Create (if missing) and return the service product used for "recap" invoice lines.
     * This avoids FK errors when product_id is NOT NULL.
     */
    private function getOrCreateRecapServiceProduct(int $companyId): Product
    {
        // Category
        $category = Category::where('company_id', $companyId)->orderBy('id')->first();
        if (!$category) {
            $category = Category::create([
                'company_id' => $companyId,
                'name' => 'Services',
                'description' => 'Articles de service / récapitulatif',
            ]);
        }

        // Unit
        $unit = Unit::where('company_id', $companyId)->orderBy('id')->first();
        if (!$unit) {
            $unit = Unit::create([
                'company_id' => $companyId,
                'name' => 'Unité',
                'symbol' => 'u',
            ]);
        }

        // Product (service)
        $product = Product::where('company_id', $companyId)
            ->where('name', 'Récap Bon de Livraison')
            ->first();

        if (!$product) {
            $product = Product::create([
                'company_id' => $companyId,
                'name' => 'Récap Bon de Livraison',
                'price' => 0,
                'stock' => 0,
                'category_id' => $category->id,
                'unit_id' => $unit->id,
            ]);
        }

        return $product;
    }
}