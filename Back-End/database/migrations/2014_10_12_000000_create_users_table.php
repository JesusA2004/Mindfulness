<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('matricula')->unique();
            $table->string('password');
            $table->string('email')->unique();
            $table->string('rol'); // 'Estudiante', 'Profesor', 'Administrador'
            $table->string('urlFotoPerfil'); // Foto de perfil para cada usuario
            $table->string('estatus')->default('activo'); // 'activo', 'bajaSistema', 'bajaTemporal'
            $table->objectId('persona_id'); // Referencia a la colección de personas (profesores, alumnos y administradores)
            $table->rememberToken();
            $table->timestamps();
            $table->index('rol'); //Parámetro de búsqueda
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
