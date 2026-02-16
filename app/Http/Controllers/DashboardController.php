<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Product;
use App\Models\Order;
use App\Models\DeliveryNote;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __invoke()
    {
        // In some restored databases, legacy rows might have company_id = NULL.
        // Keep multi-company filtering, but also include NULL rows so the dashboard isn't empty.
        $companyId = auth()->user()->company_id;
        if (empty($companyId)) {
            $companyId = 1; // safe fallback for single-company installs
        }

        $scopeCompany = function ($query) use ($companyId) {
            return $query->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            });
        };

        $hasPayments    = Schema::hasTable('payments')
            && Schema::hasColumn('payments', 'invoice_id')
            && Schema::hasColumn('payments', 'amount');

        $hasInvoiceDate = Schema::hasColumn('invoices', 'invoice_date');

        $clientsCount  = $scopeCompany(Client::query())->count();
        $productsCount = $scopeCompany(Product::query())->count();
        $ordersCount   = $scopeCompany(Order::query())->count();
        $blCount       = $scopeCompany(DeliveryNote::query())->count();
        $invoicesCount = $scopeCompany(Invoice::query())->count();

        // Totals
        $totalInvoiced = (float) $scopeCompany(Invoice::query())->sum('total');

        // Some installs don't have payments.company_id; compute paid via join on invoices.company_id
        $totalPaid = 0.0;
        if ($hasPayments) {
            $totalPaid = (float) DB::table('payments')
                ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
                ->whereNull('invoices.deleted_at')
                ->where(function ($q) use ($companyId) {
                    $q->where('invoices.company_id', $companyId)->orWhereNull('invoices.company_id');
                })
                ->sum('payments.amount');
        }

        $totalDue = max(0, $totalInvoiced - $totalPaid);

        // Invoice status counts
        $paidInvoices = 0;
        $partialInvoices = 0;
        $unpaidInvoices = 0;

        if ($hasPayments) {
            $paidInvoices = $scopeCompany(Invoice::query())->whereRaw("
                (SELECT COALESCE(SUM(amount),0) FROM payments WHERE payments.invoice_id = invoices.id) >= invoices.total
            ")->count();

            $partialInvoices = $scopeCompany(Invoice::query())->whereRaw("
                (SELECT COALESCE(SUM(amount),0) FROM payments WHERE payments.invoice_id = invoices.id) > 0
                AND (SELECT COALESCE(SUM(amount),0) FROM payments WHERE payments.invoice_id = invoices.id) < invoices.total
            ")->count();

            $unpaidInvoices = $scopeCompany(Invoice::query())->whereRaw("
                (SELECT COALESCE(SUM(amount),0) FROM payments WHERE payments.invoice_id = invoices.id) = 0
            ")->count();
        } else {
            // No payments table => treat all as unpaid
            $unpaidInvoices = $scopeCompany(Invoice::query())->count();
        }

        // Recent activity
        $recentOrders = Order::with('client')
            ->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            })
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $recentDeliveryNotes = DeliveryNote::with(['order.client'])
            ->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            })
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $recentInvoices = Invoice::with(['client'])
            ->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            })
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        // Top clients by invoiced amount (last 12 months)
        $topClients = Invoice::select('client_id', DB::raw('SUM(total) as total'))
            ->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            })
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('client_id')
            ->with('client')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        // Monthly invoiced totals (last 6 months)
        $dateExpr = $hasInvoiceDate
            ? "DATE_FORMAT(COALESCE(invoice_date, created_at), '%Y-%m')"
            : "DATE_FORMAT(created_at, '%Y-%m')";

        $monthlyInvoiced = Invoice::select(
                DB::raw($dateExpr . ' as ym'),
                DB::raw('SUM(total) as total')
            )
            ->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            })
            ->where('created_at', '>=', now()->subMonths(6)->startOfMonth())
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();

        return view('dashboard', compact(
            'clientsCount','productsCount','ordersCount','blCount','invoicesCount',
            'totalInvoiced','totalPaid','totalDue',
            'paidInvoices','partialInvoices','unpaidInvoices',
            'recentOrders','recentDeliveryNotes','recentInvoices',
            'topClients','monthlyInvoiced'
        ));
    }
}
