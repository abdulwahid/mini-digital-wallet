<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Composite index for queries filtering by sender_id and ordering by created_at
            // This dramatically improves performance for "outgoing transactions" queries
            $table->index(['sender_id', 'created_at'], 'transactions_sender_id_created_at_index');

            // Composite index for queries filtering by receiver_id and ordering by created_at
            // This dramatically improves performance for "incoming transactions" queries
            $table->index(['receiver_id', 'created_at'], 'transactions_receiver_id_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_sender_id_created_at_index');
            $table->dropIndex('transactions_receiver_id_created_at_index');
        });
    }
};

