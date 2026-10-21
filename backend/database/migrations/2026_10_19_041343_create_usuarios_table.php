<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('email', 150)->unique();
            $table->string('password');
            $table->foreignId('rol_id')->constrained('roles')->restrictOnDelete();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->index(['rol_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
