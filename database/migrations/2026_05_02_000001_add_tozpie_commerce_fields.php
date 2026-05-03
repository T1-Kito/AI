<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'tozpie_product_id')) {
                $table->unsignedBigInteger('tozpie_product_id')->nullable()->after('api_product_id');
            }

            if (! Schema::hasColumn('products', 'import_price')) {
                $table->unsignedInteger('import_price')->default(0)->after('description');
            }

            if (! Schema::hasColumn('products', 'selling_price')) {
                $table->unsignedInteger('selling_price')->default(0)->after('import_price');
            }

            if (! Schema::hasColumn('products', 'in_stock')) {
                $table->boolean('in_stock')->default(true)->after('stock');
            }
        });

        DB::table('products')->orderBy('id')->each(function (object $product): void {
            DB::table('products')
                ->where('id', $product->id)
                ->update([
                    'tozpie_product_id' => $product->tozpie_product_id ?? $product->api_product_id ?? null,
                    'import_price' => $product->import_price ?: (int) round(((int) $product->price) * 0.85),
                    'selling_price' => $product->selling_price ?: $product->price,
                    'in_stock' => $product->stock > 0,
                ]);
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('code')->unique();
            $table->unsignedInteger('amount');
            $table->string('method')->default('bank');
            $table->string('status')->default('pending');
            $table->json('meta')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'tozpie_product_id',
                'import_price',
                'selling_price',
                'in_stock',
            ]);
        });
    }
};
