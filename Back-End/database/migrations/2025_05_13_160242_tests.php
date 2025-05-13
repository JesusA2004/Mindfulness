<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('tests', function (Blueprint $collection) {
            $collection->string('nombre')->unique();
            $collection->text('descripcion')->nullable();
            $collection->integer('duracion_estimada');
            $collection->date('fechaAplicacion')->nullable();
            $collection->raw('cuestionario'); // [{ pregunta, respuestas, idUsuario }]
            $collection->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tecnicas');
    }
};
