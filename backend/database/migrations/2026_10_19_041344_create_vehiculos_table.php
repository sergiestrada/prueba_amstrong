<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->string('placa', 20)->unique();
            $table->string('numero_economico', 20)->unique();
            $table->string('marca', 80);
            $table->string('modelo', 80);
            $table->unsignedSmallInteger('anio');
            $table->unsignedInteger('odometro_actual')->default(0);
            $table->enum('estatus', ['Activo', 'En taller', 'Baja'])->default('Activo');
            $table->date('fecha_alta');
            $table->timestamps();

            $table->index(['estatus', 'fecha_alta']);
            $table->index(['marca', 'modelo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};
