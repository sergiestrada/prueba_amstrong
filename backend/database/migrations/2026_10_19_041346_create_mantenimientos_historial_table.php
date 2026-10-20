<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mantenimientos_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->restrictOnDelete();
            // Una orden cerrada puede producir como máximo un registro de historial.
            $table->foreignId('orden_id')->unique()->constrained('ordenes')->restrictOnDelete();
            $table->enum('tipo', ['Preventivo', 'Correctivo']);
            $table->unsignedInteger('odometro');
            $table->date('fecha');
            $table->decimal('costo', 12, 2);
            $table->timestamps();

            $table->index(['vehiculo_id', 'tipo', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mantenimientos_historial');
    }
};
