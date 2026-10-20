<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->string('placa', 20)->unique();                         // RF-01 + RN-01
            $table->string('numero_economico', 30)->unique();              // RF-01 + RN-01
            $table->string('marca', 80);
            $table->string('modelo', 80);
            $table->unsignedSmallInteger('anio');
            $table->unsignedInteger('odometro_actual')->default(0);        // RF-01
            $table->enum('estatus', ['activo', 'en_taller', 'baja'])       // RF-01 + RN-03/06
                ->default('activo');
            $table->date('fecha_alta');                                    // RN-07 (referencia)
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['estatus', 'odometro_actual']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};