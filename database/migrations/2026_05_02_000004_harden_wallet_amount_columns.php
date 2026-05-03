<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('wallet_balance')->default(0)->change();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('amount')->change();
        });

        Schema::table('deposits', function (Blueprint $table) {
            $table->unsignedBigInteger('amount')->change();
            $table->unsignedBigInteger('fee')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('wallet_balance')->default(0)->change();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedInteger('amount')->change();
        });

        Schema::table('deposits', function (Blueprint $table) {
            $table->unsignedInteger('amount')->change();
            $table->unsignedInteger('fee')->default(0)->change();
        });
    }
};
