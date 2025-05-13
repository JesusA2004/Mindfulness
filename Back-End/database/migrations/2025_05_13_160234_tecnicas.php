<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  
    public function up(): void
    {
        Schema::create('tecnicas', function (Blueprint $collection) {
            $collection->string('nombre')->unique();
            $collection->text('descripcion');
            $collection->string('dificultad'); // Bajo, Medio, Alto
            $collection->integer('duracion'); // minutos
            $collection->string('categoria'); // Respiración, Relajación, Meditación, etc.

            $collection->raw('calificaciones', [ 
                'usuario_id' => 'ObjectId',
                'puntaje' => 'integer',
                'comentario' => 'string',
                'fecha' => 'date'
            ]);
            
            $collection->raw('recursos', [ 
                'tipo' => 'string',
                'url' => 'string',
                'descripcion' => 'string',
                'fecha' => 'date'
            ]); 

            $collection->timestamps();
            $collection->index('categoria');
            $collection->index('nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tecnicas');
    }
};
