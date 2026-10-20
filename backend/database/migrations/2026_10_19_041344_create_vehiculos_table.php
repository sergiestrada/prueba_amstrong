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
            $table->string('placa', 20)->unique();
            $table->string('marca', 80);
            $table->string('modelo', 80);
            $table->year('anio');
            $table->string('color', 50)->nullable();
            $table->string('vin', 17)->nullable()->unique();
            $table->enum('tipo', ['sedan', 'suv', 'pickup', 'camion', 'moto', 'otro'])
                ->default('sedan');
            $table->unsignedInteger('kilometraje')->default(0);
            $table->date('fecha_ultimo_servicio')->nullable();
            $table->date('fecha_proximo_servicio')->nullable();
            $table->enum('estado', ['activo', 'mantenimiento', 'inactivo'])
                ->default('activo');
            $table->boolean('activo')->default(true);
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['activo', 'estado']);
            $table->index('fecha_proximo_servicio');
            $table->index(['marca', 'modelo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};