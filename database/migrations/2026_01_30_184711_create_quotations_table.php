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
    Schema::create('quotations', function (Blueprint $table) {
        $table->id();
        $table->string('quotation_number')->unique();
        $table->foreignId('customer_id')->constrained()->onDelete('cascade');
        $table->date('issue_date');
        $table->date('valid_until');
        $table->decimal('subtotal', 10, 2);
        $table->decimal('tax', 10, 2)->default(0);
        $table->decimal('total', 10, 2);
        $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'expired'])->default('draft');
        $table->text('notes')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
