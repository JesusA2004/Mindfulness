<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('users', function (Blueprint $collection) {
            $collection->date('fechaAsignacion'); //automática de acuerdo a la fecha en la que se cree la actividad
            $collection->date('fechaFinalizacion');
            $collection->date('fechaMaxima');
            $collection->string('nombre');
            $collection->objectId('docente_id');
            $collection->objectId('tecnica_id');
            $collection->text('descripcion');
            $collection->raw('participantes'); // [{ user_id, estado }]
            $collection->timestamps();
            $collection->index('nombre');
            $collection->index('fechaMaxima');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividads');
    }
};
