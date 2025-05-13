<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('recompensas', function (Blueprint $collection) {
            $collection->string('nombre');
            $collection->text('descripcion')->nullable();
            $collection->integer('puntos_necesarios');
            $collection->integer('stock')->nullable(); // cantidad de recompensas disponibles
            $collection->raw('canjeo', [ 
                'usuario_id' => 'ObjectId',
                'fechaCanjeo' => 'date'
            ]); 
            $collection->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recompensas');
    }
};
