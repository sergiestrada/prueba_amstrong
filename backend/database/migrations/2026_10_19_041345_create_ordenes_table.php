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

            $table->enum('tipo', ['preventivo', 'correctivo']);            // RF-02
            $table->enum('estatus', [                                       // RF-02
                'abierta', 'en_proceso', 'cerrada', 'cancelada',
            ])->default('abierta');

            $table->text('descripcion');
            $table->unsignedInteger('odometro_ingreso');                    // RF-02 + RN-04
            $table->date('fecha_programada');                               // RF-02 (obligatoria)
            $table->date('fecha_cierre')->nullable();                       // RN-05
            $table->decimal('costo', 10, 2)->nullable();                    // RN-05

            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vehiculo_id', 'estatus']);                      // RN-02
            $table->index(['estatus', 'tipo']);                             // Reportes
            $table->index('fecha_programada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes');
    }
};