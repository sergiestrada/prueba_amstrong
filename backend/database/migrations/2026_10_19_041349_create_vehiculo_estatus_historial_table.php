<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vehiculo_estatus_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->restrictOnDelete();
            $table->enum('estatus', ['Activo', 'En taller', 'Baja']);
            $table->string('motivo', 255)->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('fecha_inicio');
            $table->timestamp('fecha_fin')->nullable();
            $table->timestamps();

            $table->index(['vehiculo_id', 'fecha_inicio']);
            $table->index(['estatus', 'fecha_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculo_estatus_historial');
    }
};
