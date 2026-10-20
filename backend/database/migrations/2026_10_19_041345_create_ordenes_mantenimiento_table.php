<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 30)->unique();

            $table->foreignId('vehiculo_id')
                ->constrained('vehiculos')->restrictOnDelete();
            $table->foreignId('creado_por_id')
                ->constrained('usuarios')->restrictOnDelete();
            $table->foreignId('asignado_a_id')->nullable()
                ->constrained('usuarios')->nullOnDelete();

            $table->enum('tipo', ['preventivo', 'correctivo', 'emergencia'])
                ->default('preventivo');
            $table->enum('prioridad', ['baja', 'media', 'alta', 'critica'])
                ->default('media');
            $table->enum('status', ['pendiente', 'en_progreso', 'completada', 'cancelada'])
                ->default('pendiente');

            $table->text('descripcion');
            $table->text('notas')->nullable();

            $table->date('fecha_programada')->nullable();
            $table->timestamp('fecha_inicio')->nullable();
            $table->timestamp('fecha_cierre')->nullable();

            $table->decimal('costo_estimado', 10, 2)->nullable();
            $table->decimal('costo_real', 10, 2)->nullable();
            $table->unsignedInteger('kilometraje_ingreso')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'prioridad']);
            $table->index('fecha_programada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes');
    }
};