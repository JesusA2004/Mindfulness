<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('bitacoras', function (Blueprint $collection) {
            $collection->string('titulo');
            $collection->text('descripcion')->nullable();
            $collection->date('fecha');
            $collection->objectId('alumno_id');
            $collection->timestamps();
            $collection->index('fecha'); // Parámetro de búsqueda
            $collection->index('titulo'); // Parámetro de búsqueda
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacoras');
    }
};
