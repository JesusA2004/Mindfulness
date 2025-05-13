<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('encuestas', function (Blueprint $collection) {
            $collection->string('titulo')->unique();
            $collection->text('descripcion')->nullable();
            $collection->date('fechaAsignacion'); //automática de acuerdo a la fecha en la que se cree la encuesta
            $collection->date('fechaFinalizacion');
            $collection->integer('duracion_estimada');
            $collection->raw('cuestionario'); // [{ preguntas, respuestas, id_usuario }]
            $collection->timestamps();
            $collection->index('titulo'); // Parámetro de búsqueda
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encuestas');
    }
};
