<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ordenes_mantenimiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->enum('tipo', ['Preventivo', 'Correctivo']);
            $table->text('descripcion');
            $table->unsignedInteger('odometro_ingreso');
            $table->date('fecha_programada');
            $table->date('fecha_cierre')->nullable();
            $table->decimal('costo', 12, 2)->nullable();
            $table->enum('estatus', ['Abierta', 'En proceso', 'Cerrada', 'Cancelada'])->default('Abierta');
            $table->timestamps();

            $table->index(['vehiculo_id', 'estatus']);
            $table->index(['tipo', 'estatus']);
            $table->index(['fecha_programada', 'fecha_cierre']);
            $table->index(['estatus', 'fecha_cierre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_mantenimiento');
    }
};
