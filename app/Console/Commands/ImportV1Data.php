<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ImportV1Data extends Command
{
    protected $signature = 'v1:import {--truncate : Empty ERP tables before import}';
    protected $description = 'Import data from v1_db into ERP database';

    public function handle()
    {
        $this->info('Starting V1 import...');

        /*
        |--------------------------------------------------------------------------
        | 0) TRUNCATE (optional)
        |--------------------------------------------------------------------------
        */
        if ($this->option('truncate')) {

            if (DB::getDriverName() === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
            }

            foreach ([
                'invoice_delivery_note',
                'invoice_details',
                'invoices',
                'delivery_notes',
                'order_details',
                'orders',
                'products',
                'clients',
                'units',
                'categories',
            ] as $table) {
                if ($this->hasTable($table)) {
                    DB::table($table)->truncate();
                }
            }

            if (DB::getDriverName() === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }

            $this->info('ERP tables truncated.');
        }

        /*
        |--------------------------------------------------------------------------
        | 1) CATEGORIES
        |--------------------------------------------------------------------------
        */
        if ($this->hasV1Table('categories')) {
            $rows = DB::connection('v1')->table('categories')->get();

            foreach ($rows as $c) {
                DB::table('categories')->updateOrInsert(
                    ['id' => $c->id],
                    [
                        'name'        => $c->category_name ?? ('Category '.$c->id),
                        'description' => $c->description ?? null,
                        'created_at'  => $c->created_at ?? now(),
                        'updated_at'  => $c->updated_at ?? ($c->created_at ?? now()),
                    ]
                );
            }

            $this->info('Categories imported: '.$rows->count());
        }

        /*
        |--------------------------------------------------------------------------
        | 2) UNIT (default)
        |--------------------------------------------------------------------------
        */
        DB::table('units')->updateOrInsert(
            ['id' => 1],
            [
                'name'       => 'Unité',
                'symbol'     => 'u',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $this->info('Default unit created.');

        /*
        |--------------------------------------------------------------------------
        | 3) PRODUCTS
        |--------------------------------------------------------------------------
        */
        if ($this->hasV1Table('products')) {
            $rows = DB::connection('v1')->table('products')->get();

            foreach ($rows as $p) {
                DB::table('products')->updateOrInsert(
                    ['id' => $p->id],
                    [
                        'name'        => $p->product_name ?? ('Product '.$p->id),
                        'price'       => $p->selling_price ?? 0,
                        'stock'       => 0,
                        'category_id' => $p->category_id ?? 1,
                        'unit_id'     => 1,
                        'created_at'  => $p->created_at ?? now(),
                        'updated_at'  => $p->updated_at ?? ($p->created_at ?? now()),
                    ]
                );
            }

            $this->info('Products imported: '.$rows->count());
        }

        /*
        |--------------------------------------------------------------------------
        | 4) CLIENTS
        |--------------------------------------------------------------------------
        */
        if ($this->hasV1Table('clients')) {
            $rows = DB::connection('v1')->table('clients')->get();

            foreach ($rows as $c) {
                DB::table('clients')->updateOrInsert(
                    ['id' => $c->id],
                    [
                        'name'       => $c->client_name ?? ('Client '.$c->id),
                        'phone'      => $c->telephone ?? null,
                        'email'      => $c->email ?? null,
                        'address'    => $c->address ?? null,
                        'city'       => $c->city ?? null,
                        'ice'        => $c->ice ?? null,
                        'rc'         => $c->rc ?? null,
                        'created_at' => $c->created_at ?? now(),
                        'updated_at' => $c->updated_at ?? ($c->created_at ?? now()),
                    ]
                );
            }

            $this->info('Clients imported: '.$rows->count());
        }

        // fallback client id=1
        DB::table('clients')->updateOrInsert(
            ['id' => 1],
            [
                'name' => DB::table('clients')->where('id', 1)->value('name') ?? 'Client par défaut',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 4.5) ADMIN USER (id=1)
        |--------------------------------------------------------------------------
        */
        if ($this->hasTable('users')) {
            DB::table('users')->updateOrInsert(
                ['id' => 1],
                [
                    'name' => 'Admin',
                    'email' => 'admin@local.test',
                    'password' => Hash::make('password'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            $this->info('Admin user ensured (id=1).');
        }

        /*
        |--------------------------------------------------------------------------
        | 5) ORDERS
        |--------------------------------------------------------------------------
        */
        if ($this->hasV1Table('orders')) {
            $rows = DB::connection('v1')->table('orders')->get();

            foreach ($rows as $o) {
                $clientId = DB::table('clients')->where('id', $o->client_id)->exists()
                    ? $o->client_id : 1;

                DB::table('orders')->updateOrInsert(
                    ['id' => $o->id],
                    [
                        'order_code'  => $o->order_code ?? $o->code ?? null,
                        'client_id'   => $clientId,
                        'order_date'  => $o->order_date ?? $o->created_at ?? now(),
                        'status_id'   => $o->status_id ?? 1,
                        'order_total' => $o->order_total ?? $o->total ?? 0,
                        'created_at'  => $o->created_at ?? now(),
                        'updated_at'  => $o->updated_at ?? ($o->created_at ?? now()),
                    ]
                );
            }

            $this->info('Orders imported: '.$rows->count());
        }

        /*
        |--------------------------------------------------------------------------
        | 6) ORDER DETAILS
        |--------------------------------------------------------------------------
        */
        if ($this->hasV1Table('order_details')) {
            $rows = DB::connection('v1')->table('order_details')->get();

            foreach ($rows as $d) {
                if (!DB::table('orders')->where('id', $d->order_id)->exists()) continue;

                $productId = DB::table('products')->where('id', $d->product_id)->exists()
                    ? $d->product_id : 1;

                DB::table('order_details')->updateOrInsert(
                    ['id' => $d->id],
                    [
                        'order_id'   => $d->order_id,
                        'product_id' => $productId,
                        'quantity'   => $d->quantity ?? 1,
                        'unit_price' => $d->unit_price ?? $d->price ?? 0,
                        'total_price'=> $d->total_price ?? null,
                        'created_at' => $d->created_at ?? now(),
                        'updated_at' => $d->updated_at ?? ($d->created_at ?? now()),
                    ]
                );
            }

            $this->info('Order details imported: '.$rows->count());
        }

        /*
        |--------------------------------------------------------------------------
        | 7) DELIVERY NOTES (BL)
        |--------------------------------------------------------------------------
        */
        if ($this->hasV1Table('delivery_notes')) {
            $rows = DB::connection('v1')->table('delivery_notes')->get();

            foreach ($rows as $bl) {
                if (!DB::table('orders')->where('id', $bl->order_id)->exists()) continue;

                DB::table('delivery_notes')->updateOrInsert(
                    ['id' => $bl->id],
                    [
                        'delivery_note_code' => $bl->delivery_note_code ?? $bl->code ?? null,
                        'order_id'           => $bl->order_id,
                        'delivery_date'      => $bl->delivery_date ?? null,
                        'status_id'          => $bl->status_id ?? 1,
                        'created_at'         => $bl->created_at ?? now(),
                        'updated_at'         => $bl->updated_at ?? ($bl->created_at ?? now()),
                    ]
                );
            }

            $this->info('Delivery notes imported: '.$rows->count());
        }

        $this->info('Import done ✅');
        return Command::SUCCESS;
    }

    /* ================= Helpers ================= */

    private function hasTable(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function hasV1Table(string $table): bool
    {
        try {
            return DB::connection('v1')->getSchemaBuilder()->hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
