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

            $table->foreignId('vehiculo_id')->constrained('vehiculos')->restrictOnDelete();
            $table->foreignId('creado_por_id')->constrained('usuarios')->restrictOnDelete();
            $table->foreignId('asignado_a_id')->nullable()
                  ->constrained('usuarios')->nullOnDelete();

            $table->enum('tipo', ['preventivo', 'correctivo']);
            $table->enum('status', [
                'pendiente', 'en_progreso', 'completada', 'cancelada',
            ])->default('pendiente');
            $table->enum('prioridad', ['baja', 'media', 'alta'])->default('media');

            $table->text('descripcion');
            $table->unsignedInteger('odometro_ingreso');
            $table->date('fecha_programada');
            $table->timestamp('fecha_inicio')->nullable();
            $table->date('fecha_cierre')->nullable();

            $table->decimal('costo', 12, 2)->nullable();
            $table->decimal('costo_estimado', 12, 2)->nullable();
            $table->decimal('costo_real', 12, 2)->nullable();

            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vehiculo_id', 'status']);
            $table->index(['status', 'tipo']);
            $table->index('fecha_programada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes');
    }
};