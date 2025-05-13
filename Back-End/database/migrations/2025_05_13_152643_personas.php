<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('personas', function (Blueprint $collection) {
            $collection->string('nombre');
            $collection->string('apellidoPaterno');
            $collection->string('apellidoMaterno');
            $collection->date('fechaNacimiento');
            $collection->string('telefono')->nullable();
            $collection->string('sexo')->nullable();
            $collection->raw('carrera')->nullable(); //lista de carreras que imparte el profesor. En el caso del alumno, carrera a la que pertenece
            $collection->string('matricula')->unique();
            $collection->raw('cuatrimestre')->nullable(); //lista de cuatrimestres que imparte el profesor. En el caso del alumno, cuatrimestre al que pertenece
            $collection->raw('grupo')->nullable(); //lista de grupos que imparte el profesor. En el caso del alumno, grupo al que pertenece
     
            $collection->timestamps();
            $collection->index('matricula'); //Parámetro de búsqueda
            $collection->index('apellidoPaterno'); //Parámetro de búsqueda
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};
