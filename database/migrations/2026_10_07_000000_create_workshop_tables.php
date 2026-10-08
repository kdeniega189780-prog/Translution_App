<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('mobile', 25);
            $t->string('password');
            $t->string('role', 20)->default('customer')->index();
            $t->boolean('active')->default(true);
            $t->boolean('must_change_password')->default(false);
            $t->unsignedInteger('session_version')->default(1);
            $t->timestamp('email_verified_at')->nullable();
            $t->rememberToken();
            $t->timestamps();
        });
        Schema::create('password_reset_tokens', function (Blueprint $t) {
            $t->string('email')->primary();
            $t->string('token');
            $t->timestamp('created_at')->nullable();
        });
        Schema::create('sessions', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->foreignId('user_id')->nullable()->index();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->longText('payload');
            $t->integer('last_activity')->index();
        });
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->unique()->constrained();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('mobile', 25);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('vehicles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->constrained();
            $t->string('plate_number', 20)->unique();
            $t->string('make', 80);
            $t->string('model', 80);
            $t->unsignedSmallInteger('year');
            $t->string('transmission', 40);
            $t->timestamps();
        });
        Schema::create('services', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->decimal('price', 12, 2);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('parts', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->string('category', 80);
            $t->unsignedInteger('quantity')->default(0);
            $t->decimal('unit_cost', 12, 2);
            $t->unsignedInteger('reorder_level')->default(5);
            $t->string('supplier');
            $t->timestamps();
        });
        Schema::create('schedule_days', function (Blueprint $t) {
            $t->date('date')->primary();
        });
        Schema::create('bookings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->constrained();
            $t->foreignId('vehicle_id')->constrained();
            $t->foreignId('service_id')->constrained();
            $t->foreignId('assigned_staff_id')->nullable()->constrained('users');
            $t->foreignId('created_by')->constrained('users');
            $t->dateTime('preferred_at')->index();
            $t->dateTime('scheduled_at')->nullable()->index();
            $t->string('source', 20)->default('online');
            $t->text('problem');
            $t->text('notes')->nullable();
            $t->boolean('pickup_requested')->default(false);
            $t->string('status', 20)->default('pending')->index();
            $t->text('diagnosis')->nullable();
            $t->string('consent', 20)->default('pending');
            $t->foreignId('consent_recorded_by')->nullable()->constrained('users');
            $t->timestamp('consented_at')->nullable();
            $t->text('consent_note')->nullable();
            $t->decimal('service_price', 12, 2);
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_parts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('booking_id')->constrained();
            $t->foreignId('part_id')->constrained();
            $t->unsignedInteger('quantity');
            $t->decimal('unit_price', 12, 2);
            $t->timestamps();
        });
        Schema::create('parts_orders', function (Blueprint $t) {
            $t->id();
            $t->string('supplier');
            $t->string('status', 20)->default('draft');
            $t->foreignId('created_by')->constrained('users');
            $t->timestamp('ordered_at')->nullable();
            $t->timestamp('received_at')->nullable();
            $t->timestamps();
        });
        Schema::create('order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('parts_order_id')->constrained();
            $t->foreignId('part_id')->constrained();
            $t->unsignedInteger('quantity');
            $t->decimal('unit_cost', 12, 2);
            $t->unique(['parts_order_id', 'part_id']);
        });
        Schema::create('stock_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('part_id')->constrained();
            $t->foreignId('user_id')->constrained();
            $t->foreignId('booking_id')->nullable()->constrained();
            $t->foreignId('parts_order_id')->nullable()->constrained();
            $t->string('type', 30);
            $t->integer('quantity');
            $t->unsignedInteger('balance');
            $t->string('note');
            $t->timestamps();
        });
        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('booking_id')->unique()->constrained();
            $t->string('number')->unique();
            $t->decimal('total', 12, 2);
            $t->timestamp('paid_at')->nullable()->index();
            $t->string('payment_method', 30)->nullable();
            $t->foreignId('paid_by')->nullable()->constrained('users');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('invoice_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('invoice_id')->constrained();
            $t->string('description');
            $t->unsignedInteger('quantity');
            $t->decimal('unit_price', 12, 2);
        });
        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained();
            $t->string('action');
            $t->string('subject_type');
            $t->unsignedBigInteger('subject_id');
            $t->json('details')->nullable();
            $t->timestamps();
        });
        Schema::create('notifications', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('type');
            $t->morphs('notifiable');
            $t->text('data');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['notifications', 'activity_logs', 'invoice_items', 'invoices', 'stock_movements', 'order_items', 'parts_orders', 'job_parts', 'bookings', 'schedule_days', 'parts', 'services', 'vehicles', 'customers', 'sessions', 'password_reset_tokens', 'users'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
