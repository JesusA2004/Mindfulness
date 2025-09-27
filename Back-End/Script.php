<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Jenssegers\Mongodb\Schema\Blueprint;

/**
 * 1. Users Collection
 * Solo almacena credenciales y tipo de usuario.
 */
class CreateUsersCollection extends Migration {
    public function up() {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('matricula')->unique();
            $table->string('password');
            $table->string('email')->unique();
            $table->string('rol'); // 'Estudiante', 'Profesor', 'Administrador'
            $table->string('urlFotoPerfil'); // Foto de perfil para cada usuario
            $table->objectId('persona_id'); // Referencia a la colección de personas (profesores, alumnos y administradores)
            $table->objectId('institucion_id');
            $table->rememberToken();
            $table->timestamps(); 
            $table->index('institucion_id');
            $table->index('rol'); //Parámetro de búsqueda
        });
    }

    public function down() {
         Schema::dropIfExists('users');
    }
}


/**
 * 2. Personas Collection (Profesores, alumnos y administradores)
 * Datos y registros embebidos.
 */
class CreatePersonasCollection extends Migration {
    public function up() {
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
            $collection->index('apellidoPaterno'); //Parámetro de búsqueda
        });
    }

    public function down() {
         Schema::dropIfExists('personas');
    }
}

/**
 * 3. Técnicas Mindfulness Collection
 * Con comentarios y calificaciones embebidos.
 */
class CreateTecnicasCollection extends Migration {
    public function up() {
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
        });
    }

    public function down() {
         Schema::dropIfExists('tecnicas');
    }
}

/**
 * 4. Test Emocional Collection
 * Con preguntas embebidas.
 */
class CreateTestEmocionalCollection extends Migration {
    public function up() {
       Schema::create('tests', function (Blueprint $collection) {
            $collection->string('nombre')->unique();
            $collection->text('descripcion')->nullable();
            $collection->integer('duracion_estimada');
            $collection->date('fechaAplicacion')->nullable();
            $collection->raw('cuestionario'); // [{ pregunta, respuestas, idUsuario }]
            $collection->timestamps();
        });
    }

    public function down() {
         Schema::dropIfExists('tests_emocionales');
    }
}

/**
 * 5. Recompensas Collection
 */
class CreateRecompensasCollection extends Migration {
    public function up() {
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

    public function down() {
         Schema::dropIfExists('recompensas');
    }
}

/**
 * 6. Encuestas Collection
 * Con preguntas embebidas.
 */
class CreateEncuestasCollection extends Migration {
    public function up() {
       Schema::create('encuestas', function (Blueprint $collection) {
            $collection->string('titulo')->unique();
            $collection->text('descripcion')->nullable();
            $collection->date('fechaAsignacion'); //automática de acuerdo a la fecha en la que se cree la encuesta
            $collection->date('fechaFinalizacion');
            $collection->integer('duracion_estimada');
            $collection->raw('cuestionario'); // [{ preguntas, respuestas, id_usuario }]
            $collection->timestamps();
        });
    }

    public function down() {
         Schema::dropIfExists('encuestas');
    }
}

/**
 * 7. Actividades en el Aula Collection
 */
class CreateActividadesCollection extends Migration {
    public function up() {
       Schema::create('actividads', function (Blueprint $collection) {
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

    public function down() {
         Schema::dropIfExists('actividades');
    }
}

/**
 * 8. Citas Collection
 */
class CreateCitasCollection extends Migration {
    public function up() {
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

    public function down() {
         Schema::dropIfExists('citas');
    }
}


/**
 * 9. Bitácoras Collection
 */
class CreateBitacorasCollection extends Migration {
    public function up() {
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

    public function down() {
         Schema::dropIfExists('bitacoras');
    }
}


/**
 * 9. Coleccion de instituciones
 */
class CreateBitacorasCollection extends Migration {
    public function up() {
       Schema::create('instituciones', function (Blueprint $collection) {
            $collection->id();                
            $collection->string('nombre')->unique();
            $collection->text('descripcion')->nullable();
            $collection->string('direccion')->nullable();
            $collection->string('telefono')->nullable();
            $collection->string('email')->nullable();
            $collection->timestamps();
            $collection->index('nombre');
        });
    }

    public function down() {
         Schema::dropIfExists('bitacoras');
    }
}