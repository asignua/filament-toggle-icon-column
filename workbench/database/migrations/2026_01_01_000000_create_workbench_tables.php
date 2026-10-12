<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // `workbench:build` runs Testbench's own users migration first; the tests run only this one.
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('is_active')->default(true);
            });
        } else {
            Schema::create('users', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->boolean('is_active')->default(true);
                $table->rememberToken();
                $table->timestamps();
            });
        }

        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->boolean('is_done')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique();
            $table->unsignedInteger('price_cents');
            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
        });
    }
};
