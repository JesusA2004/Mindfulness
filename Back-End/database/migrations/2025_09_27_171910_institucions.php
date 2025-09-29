<?php

use Illuminate\Database\Migrations\Migration;
use MongoDB\Laravel\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('institucions', function (Blueprint $collection) {
            $collection->id();
            $collection->string('nombre')->unique(); // <--- ya crea índice único
            $collection->text('descripcion')->nullable();
            $collection->string('direccion')->nullable();
            $collection->string('telefono')->nullable();
            $collection->string('email')->nullable();
            $collection->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('institucions');
    }
};
