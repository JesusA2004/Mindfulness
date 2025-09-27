<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('citas', function (Blueprint $collection) {
            $collection->objectId('alumno_id'); //realmente es un user_id tipo alumno
            $collection->objectId('docente_id'); //realmente es un user_id tipo docente
            $collection->dateTime('fecha_cita');
            $collection->string('modalidad');
            $collection->text('motivo')->nullable();
            $collection->text('observaciones');
            $collection->string('estado')->default('Pendiente');
            $collection->timestamps();
            $collection->index('fecha_cita'); // Parámetro de búsqueda
            $collection->index('estado'); // Parámetro de búsqueda
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
