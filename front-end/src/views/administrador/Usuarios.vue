<!-- src/views/administrador/Usuarios.vue -->
<template>
  <main class="panel-wrapper container-fluid">
    <!-- ===== Toolbar ===== -->
    <div class="toolbar py-2 px-0 px-lg-2">
      <div class="row g-2 align-items-center">
        <!-- Buscador -->
        <div class="col-12 col-lg-6">
          <div class="input-group input-group-lg search-group shadow-sm rounded-pill animate__animated animate__fadeInDown">
            <span class="input-group-text rounded-start-pill">
              <i class="bi bi-search"></i>
            </span>
            <input
              v-model.trim="searchQuery"
              type="search"
              class="form-control search-input"
              placeholder="Buscar por nombre, matrícula o correo…"
              @input="onInstantSearch"
            />
            <button
              v-if="searchQuery"
              class="btn btn-link text-secondary px-3"
              @click="clearSearch"
              aria-label="Limpiar"
              title="Limpiar búsqueda"
            >
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
        </div>

        <!-- Filtros -->
        <div class="col-12 col-lg-4">
          <div class="d-flex gap-2">
            <select v-model="filters.rol" class="form-select shadow-sm">
              <option value="">Todos los roles</option>
              <option value="estudiante">Estudiantes</option>
              <option value="profesor">Profesores</option>
              <option value="admin">Administradores</option>
            </select>
            <select v-model="filters.estatus" class="form-select shadow-sm">
              <option value="">Todos</option>
              <option value="activo">Activo</option>
              <option value="bajaSistema">Baja del sistema</option>
              <option value="bajaTemporal">Baja temporal</option>
            </select>
          </div>
        </div>

        <!-- Acciones -->
        <div class="col-12 col-lg-2 text-lg-end d-flex gap-2 justify-content-lg-end justify-content-start">
          <button class="btn btn-outline-primary fw-semibold rounded-pill px-3 py-2 animate__animated animate__fadeInRight" @click="openBulkModal">
            <i class="bi bi-cloud-upload"></i><span class="d-none d-xl-inline ms-1"> Cargar</span>
          </button>
          <button class="btn btn-success fw-semibold shadow pulse-btn rounded-pill px-3 py-2 animate__animated animate__fadeInRight" @click="openCreate">
            <i class="bi bi-plus-lg"></i><span class="d-none d-xl-inline ms-1"> Registrar</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ===== Tabla ===== -->
    <div class="card glass-card shadow-sm animate__animated animate__fadeInUp">
      <div class="table-responsive">
        <table class="table align-middle mb-0 table-modern">
          <thead class="sticky-header">
            <tr>
              <th style="min-width: 220px;">Usuario</th>
              <th class="d-none d-lg-table-cell">Matrícula</th>
              <th>Rol</th>
              <th>Estatus</th>
              <th class="d-none d-md-table-cell">Correo</th>
              <th class="d-none d-xl-table-cell">Teléfono</th>
              <th class="d-none d-xl-table-cell">Carrera</th>
              <th class="d-none d-lg-table-cell">Grupo</th>
              <th class="text-end" style="min-width: 140px;">Acciones</th>
            </tr>
          </thead>
          <tbody class="animate__animated animate__fadeIn">
            <tr v-for="u in filteredRows" :key="u._uid" class="row-hover">
              <td>
                <div class="d-flex align-items-center gap-3">
                  <div class="avatar-wrap">
                    <img :src="u.urlFotoPerfil || fallbackAvatar(u.nombreCompleto)" class="avatar rounded-circle" alt="Foto" />
                  </div>
                  <div class="lh-sm">
                    <div class="fw-semibold">{{ u.nombreCompleto || '—' }}</div>
                    <div class="text-muted small">{{ formatDatePretty(u.fechaNacimiento) }}</div>
                  </div>
                </div>
              </td>
              <td class="d-none d-lg-table-cell text-nowrap">{{ u.matricula || '—' }}</td>
              <td><span class="badge rounded-pill" :class="badgeRol(u.rol)">{{ asTitle(u.rol) }}</span></td>
              <td><span class="badge rounded-pill" :class="badgeEstatus(u.estatus)">{{ asTitle(u.estatus) }}</span></td>
              <td class="d-none d-md-table-cell text-nowrap">{{ u.email || '—' }}</td>
              <td class="d-none d-xl-table-cell text-nowrap">{{ u.telefono || '—' }}</td>
              <!-- Arrays o string mostrados de forma uniforme -->
              <td class="d-none d-xl-table-cell text-nowrap">{{ arrOrStr(u.carrera) || '—' }}</td>
              <td class="d-none d-lg-table-cell text-nowrap">{{ arrOrStr(u.grupo) || '—' }}</td>
              <td class="text-end">
                <!-- SOLO ICONOS -->
                <div class="btn-group btn-group-sm">
                  <button class="btn btn-outline-secondary" @click="openView(u)" title="Visualizar" aria-label="Visualizar">
                    <i class="bi bi-eye"></i>
                  </button>
                  <button class="btn btn-outline-primary" @click="openEdit(u)" title="Modificar" aria-label="Modificar">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="btn btn-outline-danger" @click="confirmDelete(u)" title="Eliminar" aria-label="Eliminar">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="!filteredRows.length">
              <td colspan="9" class="text-center text-muted py-4">
                <i class="bi bi-people fs-1 d-block mb-2"></i>
                No se encontraron usuarios con los filtros actuales.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div v-if="server.pagination" class="card-footer d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <div class="small text-muted">
          Mostrando {{ server.pagination.from }}–{{ server.pagination.to }} de {{ server.pagination.total }}
        </div>
        <div class="btn-group">
          <button class="btn btn-outline-secondary btn-sm" :disabled="!server.pagination.prev" @click="goPage(server.pagination.prev)">Anterior</button>
          <button class="btn btn-outline-secondary btn-sm" :disabled="!server.pagination.next" @click="goPage(server.pagination.next)">Siguiente</button>
        </div>
      </div>
    </div>

    <!-- ===== Modal: Registrar / Modificar ===== -->
    <div class="modal fade" ref="formModalRef" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered modal-lg modal-fixed">
        <form class="modal-content shadow-lg animate__animated animate__fadeInUp" @submit.prevent="onSubmit">
          <div class="modal-header border-0 rounded-top modal-header-gradient">
            <h5 class="modal-title fw-bold text-white mb-0">
              <i :class="['me-2', isEditing ? 'bi bi-pencil-square' : 'bi bi-plus-circle']"></i>
              {{ isEditing ? 'Modificar Usuario' : 'Registrar Usuario' }}
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="hideModal" aria-label="Cerrar"></button>
          </div>

          <div class="modal-body">
            <div v-if="hasErrors" class="alert alert-danger animate__animated animate__shakeX">
              <div class="fw-semibold mb-1">Revisa los campos:</div>
              <ul class="mb-0">
                <li v-for="(arr, field) in errors" :key="field">
                  <strong>{{ prettyField(field) }}:</strong> {{ (arr && arr[0]) || '' }}
                </li>
              </ul>
            </div>

            <!-- Persona -->
            <div class="section mb-3">
              <button class="section-toggle" type="button" @click="sec.persona = !sec.persona">
                <i :class="['bi me-2', sec.persona ? 'bi-chevron-down' : 'bi-chevron-right']"></i>
                Datos de la persona
              </button>
              <transition name="collapse-y">
                <div v-show="sec.persona" class="section-body">
                  <div class="row g-2">
                    <div class="col-12 col-md-4">
                      <label class="form-label">Nombre <span class="text-danger">*</span></label>
                      <input v-model.trim="form.persona.nombre" type="text" class="form-control" required />
                    </div>
                    <div class="col-12 col-md-4">
                      <label class="form-label">Apellido paterno <span class="text-danger">*</span></label>
                      <input v-model.trim="form.persona.apellidoPaterno" type="text" class="form-control" required />
                    </div>
                    <div class="col-12 col-md-4">
                      <label class="form-label">Apellido materno</label>
                      <input v-model.trim="form.persona.apellidoMaterno" type="text" class="form-control" />
                    </div>
                    <div class="col-12 col-md-4">
                      <label class="form-label">Fecha de nacimiento <span class="text-danger">*</span></label>
                      <input v-model="form.persona.fechaNacimiento" type="date" :max="today" class="form-control" required />
                    </div>
                    <div class="col-12 col-md-4">
                      <label class="form-label">Teléfono <span class="text-danger">*</span></label>
                      <input v-model.trim="form.persona.telefono" type="tel" class="form-control" required />
                    </div>
                    <div class="col-12 col-md-4">
                      <label class="form-label">Sexo <span class="text-danger">*</span></label>
                      <select v-model="form.persona.sexo" class="form-select" required>
                        <option value="" disabled>Selecciona…</option>
                        <option>Femenino</option>
                        <option>Masculino</option>
                        <option>Otro</option>
                        <option>Prefiero no decir</option>
                      </select>
                    </div>
                  </div>
                </div>
              </transition>
            </div>

            <!-- Escolaridad -->
            <div class="section mb-3">
              <button class="section-toggle" type="button" @click="sec.escolar = !sec.escolar">
                <i :class="['bi me-2', sec.escolar ? 'bi-chevron-down' : 'bi-chevron-right']"></i>
                Datos escolares (según rol)
              </button>
              <transition name="collapse-y">
                <div v-show="sec.escolar" class="section-body">
                  <div class="row g-2">
                    <div class="col-12 col-md-4">
                      <label class="form-label">Matrícula <span class="text-danger">*</span></label>
                      <input v-model.trim="form.persona.matricula" type="text" class="form-control" required />
                    </div>

                    <!-- PROFESOR: múltiples con chips -->
                    <template v-if="isProfessor">
                      <div class="col-12">
                        <label class="form-label">Carreras</label>
                        <div class="chips-input">
                          <div class="chips">
                            <span class="chip" v-for="(v,i) in form.persona.carreras" :key="'car-'+i">
                              {{ v }}
                              <button type="button" class="chip-x" @click="removeTag('carreras', i)" aria-label="Quitar">&times;</button>
                            </span>
                          </div>
                          <div class="input-group">
                            <input
                              v-model.trim="tagInputs.carreras"
                              type="text"
                              class="form-control"
                              placeholder="Agregar carrera (Enter)"
                              @keydown.enter.prevent="addTag('carreras')"
                            />
                            <button class="btn btn-outline-secondary" type="button" @click="addTag('carreras')">Agregar</button>
                          </div>
                        </div>
                        <small class="text-muted">Ej.: TIC, IA, Industrial…</small>
                      </div>

                      <div class="col-12">
                        <label class="form-label">Cuatrimestres</label>
                        <div class="chips-input">
                          <div class="chips">
                            <span class="chip" v-for="(v,i) in form.persona.cuatrimestres" :key="'cuat-'+i">
                              {{ v }}
                              <button type="button" class="chip-x" @click="removeTag('cuatrimestres', i)" aria-label="Quitar">&times;</button>
                            </span>
                          </div>
                          <div class="input-group">
                            <input
                              v-model.trim="tagInputs.cuatrimestres"
                              type="text"
                              class="form-control"
                              placeholder="Agregar cuatrimestre (Enter)"
                              @keydown.enter.prevent="addTag('cuatrimestres')"
                            />
                            <button class="btn btn-outline-secondary" type="button" @click="addTag('cuatrimestres')">Agregar</button>
                          </div>
                        </div>
                        <small class="text-muted">Ej.: 1, 2, 3…</small>
                      </div>

                      <div class="col-12">
                        <label class="form-label">Grupos</label>
                        <div class="chips-input">
                          <div class="chips">
                            <span class="chip" v-for="(v,i) in form.persona.grupos" :key="'grp-'+i">
                              {{ v }}
                              <button type="button" class="chip-x" @click="removeTag('grupos', i)" aria-label="Quitar">&times;</button>
                            </span>
                          </div>
                          <div class="input-group">
                            <input
                              v-model.trim="tagInputs.grupos"
                              type="text"
                              class="form-control"
                              placeholder="Agregar grupo (Enter)"
                              @keydown.enter.prevent="addTag('grupos')"
                            />
                            <button class="btn btn-outline-secondary" type="button" @click="addTag('grupos')">Agregar</button>
                          </div>
                        </div>
                        <small class="text-muted">Ej.: A, B, 3A, 5B…</small>
                      </div>
                    </template>

                    <!-- ESTUDIANTE/ADMIN: simple -->
                    <template v-else>
                      <div class="col-12 col-md-4">
                        <label class="form-label">Carrera <span class="text-danger" v-if="needsCareer">*</span></label>
                        <input v-model.trim="form.persona.carrera" type="text" class="form-control" :required="needsCareer" placeholder="Ej. TIC, IA, Industrial" />
                      </div>
                      <div class="col-6 col-md-2">
                        <label class="form-label">Cuatrimestre <span class="text-danger" v-if="needsCareer">*</span></label>
                        <input v-model.trim="form.persona.cuatrimestre" type="text" class="form-control" :required="needsCareer" placeholder="Ej. 2" />
                      </div>
                      <div class="col-6 col-md-2">
                        <label class="form-label">Grupo <span class="text-danger" v-if="needsCareer">*</span></label>
                        <input v-model.trim="form.persona.grupo" type="text" class="form-control" :required="needsCareer" placeholder="Ej. A" />
                      </div>
                    </template>
                  </div>

                  <small class="text-muted d-block mt-1">
                    Para <strong>Profesor</strong> puedes registrar múltiples carreras, cuatrimestres y grupos.
                  </small>
                </div>
              </transition>
            </div>

            <!-- Datos de acceso -->
            <div class="section">
              <button class="section-toggle" type="button" @click="sec.user = !sec.user">
                <i :class="['bi me-2', sec.user ? 'bi-chevron-down' : 'bi-chevron-right']"></i>
                Datos de acceso
              </button>
              <transition name="collapse-y">
                <div v-show="sec.user" class="section-body">
                  <div class="row g-2">
                    <div class="col-12 col-md-4">
                      <label class="form-label">Rol <span class="text-danger">*</span></label>
                      <select v-model="form.user.rol" class="form-select" required>
                        <option value="" disabled>Selecciona…</option>
                        <option value="estudiante">Estudiante</option>
                        <option value="profesor">Profesor</option>
                        <option value="admin">Administrador</option>
                      </select>
                    </div>
                    <div class="col-12 col-md-4">
                      <label class="form-label">Estatus <span class="text-danger">*</span></label>
                      <select v-model="form.user.estatus" class="form-select" required>
                        <option value="activo">Activo</option>
                        <option value="bajaSistema">Baja del sistema</option>
                        <option value="bajaTemporal">Baja temporal</option>
                      </select>
                    </div>
                    <div class="col-12 col-md-4">
                      <label class="form-label">Correo <span class="text-danger">*</span></label>
                      <input v-model.trim="form.user.email" type="email" class="form-control" required />
                    </div>

                    <!-- Foto de perfil -->
                    <div class="col-12">
                      <label class="form-label d-flex align-items-center gap-2">
                        Foto de perfil
                        <span class="badge bg-info-subtle text-info border">Opcional</span>
                      </label>
                      <div class="photo-uploader d-flex align-items-center gap-3">
                        <div class="preview rounded-circle animate__animated animate__fadeIn">
                          <img :src="photoPreview || form.user.urlFotoPerfil || fallbackAvatar(displayNameFromPersona())" alt="Preview" />
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                          <input ref="fileInputRef" type="file" accept="image/*" class="d-none" @change="onPhotoSelected" />
                          <button type="button" class="btn btn-outline-secondary btn-sm" @click="triggerFile">
                            <i class="bi bi-upload"></i><span class="d-none d-xl-inline ms-1"> Subir</span>
                          </button>
                          <button v-if="photoPreview || form.user.urlFotoPerfil" type="button" class="btn btn-outline-danger btn-sm" @click="clearPhoto">
                            <i class="bi bi-x-circle"></i><span class="d-none d-xl-inline ms-1"> Quitar</span>
                          </button>
                        </div>
                      </div>
                      <small class="text-muted d-block mt-1">Se sube a <code>/subir-foto</code> y se guarda el URL devuelto.</small>
                    </div>

                    <!-- Contraseña (auto) -->
                    <div class="col-12">
                      <div class="alert alert-info py-2 px-3 mt-2 animate__animated animate__fadeIn">
                        <i class="bi bi-shield-lock me-1"></i>
                        La contraseña se <strong>generará automáticamente</strong> y se enviará por correo desde el servidor.
                      </div>
                    </div>

                  </div>
                </div>
              </transition>
            </div>
          </div>

          <div class="modal-footer border-0">
            <button type="button" class="btn btn-outline-secondary" @click="hideModal">Cancelar</button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              <span v-if="!saving">{{ isEditing ? 'Guardar cambios' : 'Registrar' }}</span>
              <span v-else class="spinner-border spinner-border-sm ms-2"></span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ===== Modal: Visualizar ===== -->
    <div class="modal fade" ref="viewModalRef" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered modal-lg modal-fixed">
        <div class="modal-content border-0 shadow-lg animate__animated animate__fadeInUp">
          <div class="modal-header border-0 rounded-top modal-header-gradient">
            <h5 class="modal-title fw-bold text-white">
              <i class="bi bi-card-text me-2"></i> Detalle de Usuario
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="hideView" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <div v-if="!selected" class="text-center text-muted py-3">Sin selección</div>
            <div v-else class="row gy-3">
              <div class="col-12 d-flex align-items-center gap-3">
                <img :src="selected.urlFotoPerfil || fallbackAvatar(selected.nombreCompleto)" class="avatar-lg rounded-circle shadow-sm" alt="Foto" />
                <div>
                  <h5 class="mb-0">{{ selected.nombreCompleto }}</h5>
                  <div class="text-muted small">{{ formatDatePretty(selected.fechaNacimiento) }}</div>
                </div>
              </div>

              <div class="col-12 col-md-6">
                <dl class="mb-0">
                  <dt>Rol</dt>
                  <dd><span class="badge rounded-pill" :class="badgeRol(selected.rol)">{{ asTitle(selected.rol) }}</span></dd>
                  <dt>Estatus</dt>
                  <dd><span class="badge rounded-pill" :class="badgeEstatus(selected.estatus)">{{ asTitle(selected.estatus) }}</span></dd>
                  <dt>Correo</dt>
                  <dd>{{ selected.email || '—' }}</dd>
                  <dt>Matrícula</dt>
                  <dd>{{ selected.matricula || '—' }}</dd>
                </dl>
              </div>

              <div class="col-12 col-md-6">
                <dl class="mb-0">
                  <dt>Teléfono</dt>
                  <dd>{{ selected.telefono || '—' }}</dd>
                  <dt>Carrera</dt>
                  <dd>{{ arrOrStr(selected.carrera) || '—' }}</dd>
                  <dt>Cuatrimestre</dt>
                  <dd>{{ arrOrStr(selected.cuatrimestre) || '—' }}</dd>
                  <dt>Grupo</dt>
                  <dd>{{ arrOrStr(selected.grupo) || '—' }}</dd>
                </dl>
              </div>
            </div>
          </div>
          <div class="modal-footer border-0 d-flex w-100">
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-outline-primary" @click="modifyFromView" title="Editar">
                <i class="bi bi-pencil"></i>
              </button>
              <button type="button" class="btn btn-outline-danger" @click="deleteFromView" title="Eliminar">
                <i class="bi bi-trash"></i>
              </button>
            </div>
            <button class="btn btn-secondary ms-auto" @click="hideView">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== Modal: Carga masiva ===== -->
    <div class="modal fade bulk-modal" ref="bulkModalRef" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered modal-lg modal-fixed">
        <div class="modal-content border-0 shadow-lg animate__animated animate__fadeInUp">
          <div class="modal-header border-0 rounded-top modal-header-gradient">
            <h5 class="modal-title fw-bold text-white">
              <i class="bi bi-cloud-upload me-2"></i> Carga masiva de usuarios
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="hideBulk" aria-label="Cerrar"></button>
          </div>

          <div class="modal-body">
            <div class="mb-2">
              <input ref="bulkFileRef" type="file" accept=".json,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel" class="form-control" @change="onBulkFileSelected" />
              <small class="text-muted">
                Formatos: <strong>JSON</strong>, <strong>CSV</strong>, <strong>XLSX</strong> (si está instalada la librería <code>xlsx</code>).
              </small>
            </div>

            <div v-if="bulk.preview.length" class="mt-3">
              <div class="alert alert-secondary py-2 px-3">
                Se encontraron <strong>{{ bulk.preview.length }}</strong> filas.
                Mapea a: <code>{ nombre, apellidoPaterno, apellidoMaterno, fechaNacimiento, telefono, sexo, carrera, cuatrimestre, grupo, matricula, email, rol, estatus }</code>
              </div>
              <div class="bulk-table-wrapper">
                <table class="table table-sm table-striped">
                  <thead><tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Apellidos</th>
                    <th class="d-none d-md-table-cell">Matrícula</th>
                    <th>Rol</th>
                    <th class="d-none d-lg-table-cell">Correo</th>
                  </tr></thead>
                  <tbody>
                    <tr v-for="(r,i) in bulk.preview.slice(0,8)" :key="i">
                      <td>{{ i+1 }}</td>
                      <td>{{ r.nombre }}</td>
                      <td>{{ [r.apellidoPaterno, r.apellidoMaterno].filter(Boolean).join(' ') }}</td>
                      <td class="d-none d-md-table-cell">{{ r.matricula }}</td>
                      <td>{{ r.rol }}</td>
                      <td class="d-none d-lg-table-cell">{{ r.email }}</td>
                    </tr>
                  </tbody>
                </table>
                <small class="text-muted">Vista previa de las primeras 8 filas.</small>
              </div>
            </div>

            <div v-if="bulk.running" class="mt-3">
              <div class="progress">
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" :style="{ width: bulk.progress + '%' }">
                  {{ bulk.progress }}%
                </div>
              </div>
              <div class="small mt-1">{{ bulk.done }} / {{ bulk.total }} procesados</div>
            </div>

          </div>

          <div class="modal-footer border-0">
            <button type="button" class="btn btn-outline-secondary" :disabled="bulk.running" @click="hideBulk">Cerrar</button>
            <button type="button" class="btn btn-primary" :disabled="!bulk.preview.length || bulk.running" @click="startBulk">
              <i class="bi bi-play-circle"></i><span class="d-none d-xl-inline ms-1"> Iniciar</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </main>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick, watch } from 'vue'
import axios from 'axios'
import Modal from 'bootstrap/js/dist/modal'
import 'animate.css'
import Swal from 'sweetalert2'
import 'sweetalert2/dist/sweetalert2.min.css'

/* =====================
   Endpoints (manuales)
   ===================== */
const API = (process.env.VUE_APP_API_URL || '').replace(/\/+$/,'')
const USERS_URL    = API + '/users'
const PERSONAS_URL = API + '/personas'
const UPLOAD_URL   = API + '/subir-foto'

/* ===== Helpers simples (sin crudUtils) ===== */
function getToken () {
  try {
    const u = JSON.parse(localStorage.getItem('user') || '{}')
    return u?.access_token || u?.token || localStorage.getItem('token') || ''
  } catch { return localStorage.getItem('token') || '' }
}
function authHeaders () {
  const t = getToken()
  return t ? { Authorization: `Bearer ${t}` } : {}
}
function toast (msg, type = 'success') {
  if (type === 'error') console.error(msg); else console.log(msg)
}
function makeDebouncer (ms) {
  let id; return (cb) => { clearTimeout(id); id = setTimeout(cb, ms) }
}
function toDateInputValue (d) {
  if (!(d instanceof Date) || isNaN(d)) return ''
  return d.toISOString().slice(0,10)
}
function isRequired (v) {
  return v !== null && v !== undefined && String(v).trim() !== ''
}

/* ===== Estado base ===== */
const server = reactive({ pagination: null })
const rows   = ref([])

/* ===== UI ===== */
const searchQuery = ref('')
const filters = reactive({ rol: '', estatus: '' })
const debouncer = makeDebouncer(140)

const formModalRef = ref(null)
const viewModalRef = ref(null)
const bulkModalRef = ref(null)
let formModal = null
let viewModal = null
let bulkModal = null

const isEditing = ref(false)
const saving = ref(false)
const errors = reactive({})
const hasErrors = computed(() => Object.keys(errors).length > 0)

const fileInputRef = ref(null)
const photoFile = ref(null)
const photoPreview = ref('')

/* Secciones del modal */
const sec = reactive({ persona: true, escolar: true, user: true })

/* Form principal */
const form = reactive({
  persona: {
    _id: null,
    nombre: '',
    apellidoPaterno: '',
    apellidoMaterno: '',
    fechaNacimiento: '',
    telefono: '',
    sexo: '',
    // estudiante/admin (simple)
    carrera: '',
    cuatrimestre: '',
    grupo: '',
    // profesor (múltiple)
    carreras: [],
    cuatrimestres: [],
    grupos: [],
    matricula: ''
  },
  user: {
    _id: null,
    persona_id: null,
    name: '',
    email: '',
    rol: '',
    estatus: 'activo',
    urlFotoPerfil: ''
  }
})

/* Inputs temporales para chips */
const tagInputs = reactive({ carreras: '', cuatrimestres: '', grupos: '' })

/* Visualizar */
const selected = ref(null)

/* Bulk */
const bulkFileRef = ref(null)
const bulk = reactive({ preview: [], running: false, total: 0, done: 0, progress: 0 })

/* ===== Helpers de UI ===== */
const today = toDateInputValue(new Date())
function asTitle(v) { return (v || '').replace(/_/g, ' ').replace(/\b\w/g, m => m.toUpperCase()) }
function prettyField (f) {
  const map = {
    'persona.nombre': 'Nombre',
    'persona.apellidoPaterno': 'Apellido paterno',
    'persona.apellidoMaterno': 'Apellido materno',
    'persona.fechaNacimiento': 'Fecha de nacimiento',
    'persona.telefono': 'Teléfono',
    'persona.sexo': 'Sexo',
    'persona.carrera': 'Carrera',
    'persona.carreras': 'Carreras',
    'persona.matricula': 'Matrícula',
    'persona.cuatrimestre': 'Cuatrimestre',
    'persona.cuatrimestres': 'Cuatrimestres',
    'persona.grupo': 'Grupo',
    'persona.grupos': 'Grupos',
    'user.email': 'Correo',
    'user.rol': 'Rol',
    'user.estatus': 'Estatus'
  }
  return map[f] || f
}
function badgeRol (rol) {
  switch ((rol || '').toLowerCase()) {
    case 'admin': return 'bg-dark'
    case 'profesor': return 'bg-primary'
    case 'estudiante': return 'bg-success'
    default: return 'bg-secondary'
  }
}
function badgeEstatus (est) {
  switch ((est || '').toLowerCase()) {
    case 'activo': return 'bg-success'
    case 'bajaTemporal': return 'bg-warning text-dark'
    case 'bajaSistema': return 'bg-danger'
    default: return 'bg-secondary'
  }
}
function formatDatePretty (v) {
  if (!v) return ''
  const d = new Date(v)
  return isNaN(d) ? v : d.toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: '2-digit' })
}
function displayNameFromPersona() {
  const p = form.persona
  return [p.nombre, p.apellidoPaterno, p.apellidoMaterno].filter(Boolean).join(' ').trim()
}
function fallbackAvatar(name) {
  const n = encodeURIComponent(name || 'Usuario')
  return `https://ui-avatars.com/api/?name=${n}&background=E0E7FF&color=3730A3`
}
function cryptoRandom() {
  try { return crypto.getRandomValues(new Uint32Array(1))[0] } catch { return Math.floor(Math.random() * 1e9) }
}

/* Rol flags */
const isProfessor = computed(() => (form.user.rol || '').toLowerCase() === 'profesor')
const isStudent   = computed(() => (form.user.rol || '').toLowerCase() === 'estudiante')
const needsCareer = computed(() => isProfessor.value || isStudent.value)

/* ===== Normalización ===== */
function toNombreCompleto(p) {
  return [p?.nombre, p?.apellidoPaterno, p?.apellidoMaterno].filter(Boolean).join(' ').trim()
}
function arrOrStr(v) {
  return Array.isArray(v) ? v.filter(Boolean).join(', ') : (v || '')
}

function normalizeUser(u) {
  const persona = u.persona || {}
  const nombreCompleto = u.name || toNombreCompleto(persona) || null

  // Permite backends que devuelven singular o plural
  const carrera      = persona?.carreras ?? persona?.carrera ?? ''
  const cuatrimestre = persona?.cuatrimestres ?? persona?.cuatrimestre ?? ''
  const grupo        = persona?.grupos ?? persona?.grupo ?? ''

  return {
    _uid: String(u._id ?? u.id ?? cryptoRandom()),
    user_id: String(u._id ?? u.id ?? ''),
    persona_id: String(u.persona_id ?? persona?._id ?? persona?.id ?? ''),
    nombreCompleto,
    fechaNacimiento: persona?.fechaNacimiento ?? null,
    telefono: persona?.telefono ?? '',
    sexo: persona?.sexo ?? '',
    carrera, cuatrimestre, grupo, // pueden ser string o array
    matricula: persona?.matricula ?? '',
    rol: (u.rol || '').toLowerCase(),
    estatus: (u.estatus || '').toLowerCase(),
    email: u.email || '',
    urlFotoPerfil: u.urlFotoPerfil || '',
    raw: { user: u, persona }
  }
}

/* ===== Fetch =====
   Hacemos DOS CONSULTAS manuales (users + personas) y las unimos por persona_id */
async function fetchUsers(pageUrl = null) {
  try {
    const usersUrl = pageUrl || USERS_URL

    const [usersResp, personasResp] = await Promise.all([
      axios.get(usersUrl, { headers: authHeaders(), params: pageUrl ? {} : { per_page: 50 } }),
      axios.get(PERSONAS_URL, { headers: authHeaders(), params: { per_page: 1000 } })
    ])

    // Users (puede ser paginado o simple)
    let users = []
    let pagination = null
    const udata = usersResp.data
    if (Array.isArray(udata)) {
      users = udata
    } else if (udata?.data) {
      users = udata.data
      pagination = {
        total: udata.total, per_page: udata.per_page, current_page: udata.current_page,
        last_page: udata.last_page, from: udata.from, to: udata.to,
        prev: udata.prev_page_url, next: udata.next_page_url
      }
    } else {
      users = udata || []
    }

    // Personas
    const preg = personasResp.data?.registros || personasResp.data?.data || personasResp.data || []
    const pmap = new Map(preg.map(p => [String(p._id ?? p.id), p]))

    // Join user.persona_id -> persona
    const joined = users.map(u => {
      const pid = String(u.persona_id ?? u.persona?._id ?? '')
      const persona = pmap.get(pid) || u.persona || {}
      return { ...u, persona }
    })

    rows.value = joined.map(normalizeUser)
    server.pagination = pagination
  } catch (e) {
    console.error(e)
    toast('No fue posible cargar los usuarios/personas.', 'error')
    rows.value = []
    server.pagination = null
  }
}
function goPage(url) { if (url) fetchUsers(url) }

/* ===== Filtros y búsqueda ===== */
const filteredRows = computed(() => {
  const q = (searchQuery.value || '').toLowerCase()
  return rows.value.filter(u => {
    const rolOk = filters.rol ? (u.rol === filters.rol) : true
    const estOk = filters.estatus ? (u.estatus === filters.estatus) : true

    const bag = [
      u.nombreCompleto, u.matricula, u.email, u.telefono,
      arrOrStr(u.carrera), arrOrStr(u.cuatrimestre), arrOrStr(u.grupo)
    ].join(' ').toLowerCase()

    const qOk = !q || bag.includes(q)
    return rolOk && estOk && qOk
  })
})
function onInstantSearch () { debouncer(() => {}) }
function clearSearch () { searchQuery.value = '' }

/* ===== Lifecycle ===== */
onMounted(async () => {
  await fetchUsers()
  formModal = new Modal(formModalRef.value)
  viewModal = new Modal(viewModalRef.value)
  bulkModal = new Modal(bulkModalRef.value)
})

/* ===== Chips helpers ===== */
function addTag(field) {
  const v = (tagInputs[field] || '').trim()
  if (!v) return
  const arr = form.persona[field]
  if (Array.isArray(arr) && !arr.includes(v)) arr.push(v)
  tagInputs[field] = ''
}
function removeTag(field, idx) {
  const arr = form.persona[field]
  if (Array.isArray(arr)) arr.splice(idx,1)
}

/* ===== Modal: Form ===== */
function hideModal () { formModal.hide() }
function resetForm () {
  Object.assign(form.persona, {
    _id: null, nombre: '', apellidoPaterno: '', apellidoMaterno: '',
    fechaNacimiento: '', telefono: '', sexo: '',
    carrera: '', cuatrimestre: '', grupo: '',
    carreras: [], cuatrimestres: [], grupos: [],
    matricula: ''
  })
  Object.assign(form.user, {
    _id: null, persona_id: null, name: '',
    email: '', rol: '', estatus: 'activo', urlFotoPerfil: ''
  })
  tagInputs.carreras = ''
  tagInputs.cuatrimestres = ''
  tagInputs.grupos = ''
  photoFile.value = null
  photoPreview.value = ''
  clearErrors()
}
function openCreate () {
  isEditing.value = false
  resetForm()
  sec.persona = true; sec.escolar = true; sec.user = true
  formModal.show()
}
function openEdit (row) {
  isEditing.value = true
  resetForm()
  const p = row.raw.persona || {}
  form.persona._id = row.persona_id || null
  form.persona.nombre = p?.nombre || ''
  form.persona.apellidoPaterno = p?.apellidoPaterno || ''
  form.persona.apellidoMaterno = p?.apellidoMaterno || ''
  form.persona.fechaNacimiento = toDateInputValue(new Date(p?.fechaNacimiento || '')) || ''
  form.persona.telefono = p?.telefono || ''
  form.persona.sexo = p?.sexo || ''
  form.persona.matricula = p?.matricula || ''

  // Simples (si vinieron arrays, toma el primero para no romper UI de estudiante)
  form.persona.carrera      = Array.isArray(p.carrera) ? (p.carrera[0] || '') : (p.carrera || '')
  form.persona.cuatrimestre = Array.isArray(p.cuatrimestre) ? (p.cuatrimestre[0] || '') : (p.cuatrimestre || '')
  form.persona.grupo        = Array.isArray(p.grupo) ? (p.grupo[0] || '') : (p.grupo || '')

  // Múltiples (acepta singular/plural desde backend)
  form.persona.carreras      = Array.isArray(p.carreras) ? p.carreras.slice()
                             : Array.isArray(p.carrera)  ? p.carrera.slice()
                             : (p.carrera ? [String(p.carrera)] : [])
  form.persona.cuatrimestres = Array.isArray(p.cuatrimestres) ? p.cuatrimestres.slice()
                             : Array.isArray(p.cuatrimestre)  ? p.cuatrimestre.slice()
                             : (p.cuatrimestre ? [String(p.cuatrimestre)] : [])
  form.persona.grupos        = Array.isArray(p.grupos) ? p.grupos.slice()
                             : Array.isArray(p.grupo)  ? p.grupo.slice()
                             : (p.grupo ? [String(p.grupo)] : [])

  form.user._id = row.user_id || null
  form.user.persona_id = row.persona_id || null
  form.user.name = row.nombreCompleto || ''
  form.user.email = row.email || ''
  form.user.rol = row.rol || ''
  form.user.estatus = row.estatus || 'activo'
  form.user.urlFotoPerfil = row.urlFotoPerfil || ''

  photoPreview.value = ''
  formModal.show()
}

/* ===== Photo uploader ===== */
function triggerFile() { fileInputRef.value?.click() }
function clearPhoto() {
  photoFile.value = null; photoPreview.value = ''; form.user.urlFotoPerfil = ''
}
function onPhotoSelected(e) {
  const f = e.target.files?.[0]; if (!f) return
  photoFile.value = f
  const reader = new FileReader()
  reader.onload = ev => { photoPreview.value = ev.target.result }
  reader.readAsDataURL(f)
}
async function uploadPhotoIfAny() {
  if (!photoFile.value) return null
  const fd = new FormData()
  fd.append('foto', photoFile.value)
  const { data } = await axios.post(UPLOAD_URL, fd, {
    headers: { ...authHeaders(), 'Content-Type': 'multipart/form-data' }
  })
  return data?.url || null
}

/* ===== Validación ===== */
function clearErrors () { Object.keys(errors).forEach(k => delete errors[k]) }
function validateFront() {
  clearErrors()
  const errs = {}
  if (!isRequired(form.persona.nombre)) errs['persona.nombre'] = ['El nombre es obligatorio.']
  if (!isRequired(form.persona.apellidoPaterno)) errs['persona.apellidoPaterno'] = ['El apellido paterno es obligatorio.']
  if (!isRequired(form.persona.fechaNacimiento)) errs['persona.fechaNacimiento'] = ['La fecha de nacimiento es obligatoria.']
  if (!isRequired(form.persona.telefono)) errs['persona.telefono'] = ['El teléfono es obligatorio.']
  if (!isRequired(form.persona.sexo)) errs['persona.sexo'] = ['El sexo es obligatorio.']
  if (!isRequired(form.persona.matricula)) errs['persona.matricula'] = ['La matrícula es obligatoria.']

  const rol = (form.user.rol || '').toLowerCase()
  if (!isRequired(form.user.rol)) errs['user.rol'] = ['El rol es obligatorio.']
  if (!isRequired(form.user.estatus)) errs['user.estatus'] = ['El estatus es obligatorio.']
  if (!isRequired(form.user.email)) errs['user.email'] = ['El correo es obligatorio.']

  if (rol === 'profesor') {
    if (!form.persona.carreras.length)      errs['persona.carreras'] = ['Agrega al menos una carrera.']
    if (!form.persona.cuatrimestres.length) errs['persona.cuatrimestres'] = ['Agrega al menos un cuatrimestre.']
    if (!form.persona.grupos.length)        errs['persona.grupos'] = ['Agrega al menos un grupo.']
  } else if (rol === 'estudiante') {
    if (!isRequired(form.persona.carrera))      errs['persona.carrera'] = ['La carrera es obligatoria.']
    if (!isRequired(form.persona.cuatrimestre)) errs['persona.cuatrimestre'] = ['El cuatrimestre es obligatorio.']
    if (!isRequired(form.persona.grupo))        errs['persona.grupo'] = ['El grupo es obligatorio.']
  }

  if (Object.keys(errs).length) { Object.assign(errors, errs); return false }
  return true
}

/* ===== Envío =====
   NOTA: NO enviamos password. El backend la genera y la envía por correo. */
async function onSubmit () {
  if (!validateFront()) { toast('Revisa los campos obligatorios.', 'error'); return }

  saving.value = true
  try {
    const uploadedUrl = await uploadPhotoIfAny()
    if (uploadedUrl) form.user.urlFotoPerfil = uploadedUrl

    // 1) Persona: payload dinámico
    let personaPayload
    if (isProfessor.value) {
      personaPayload = {
        nombre: form.persona.nombre,
        apellidoPaterno: form.persona.apellidoPaterno,
        apellidoMaterno: form.persona.apellidoMaterno,
        fechaNacimiento: form.persona.fechaNacimiento,
        telefono: form.persona.telefono,
        sexo: form.persona.sexo,
        matricula: form.persona.matricula,
        // múltiples (arrays)
        carrera: form.persona.carreras.slice(),
        cuatrimestre: form.persona.cuatrimestres.slice(),
        grupo: form.persona.grupos.slice(),
      }
    } else {
      personaPayload = {
        nombre: form.persona.nombre,
        apellidoPaterno: form.persona.apellidoPaterno,
        apellidoMaterno: form.persona.apellidoMaterno,
        fechaNacimiento: form.persona.fechaNacimiento,
        telefono: form.persona.telefono,
        sexo: form.persona.sexo,
        matricula: form.persona.matricula,
        // simples (strings)
        carrera: form.persona.carrera || null,
        cuatrimestre: form.persona.cuatrimestre || null,
        grupo: form.persona.grupo || null,
      }
    }

    let personaId = form.persona._id
    if (personaId) {
      await axios.put(`${PERSONAS_URL}/${personaId}`, personaPayload, { headers: authHeaders() })
    } else {
      const { data: resp } = await axios.post(PERSONAS_URL, personaPayload, { headers: authHeaders() })
      personaId = String(resp?.persona?._id ?? resp?.persona?.id ?? resp?._id ?? '')
      form.persona._id = personaId
    }

    // 2) User (sin password). name = nombre completo.
    const displayName = toNombreCompleto(personaPayload)
    const userPayload = {
      name: displayName,
      email: form.user.email,
      rol: form.user.rol,
      estatus: form.user.estatus || 'activo',
      urlFotoPerfil: form.user.urlFotoPerfil || null,
      persona_id: personaId,
      matricula: form.persona.matricula,
      notify_email: !isEditing.value // el backend envía correo si true
    }

    if (form.user._id) {
      await axios.put(`${USERS_URL}/${form.user._id}`, userPayload, { headers: authHeaders() })
      toast('Usuario actualizado.')
    } else {
      await axios.post(USERS_URL, userPayload, { headers: authHeaders() })
      toast('Usuario registrado. Se envió la contraseña por correo.')
    }

    await fetchUsers()
    hideModal()
  } catch (e) {
    console.error(e)
    const resp = e.response?.data
    Object.assign(errors, resp?.errors || {})
    toast(resp?.message || 'Ocurrió un error.', 'error')
  } finally {
    saving.value = false
  }
}

/* ===== Modal: Visualizar ===== */
function openView (row) { selected.value = row; viewModal.show() }
function hideView () { viewModal.hide(); selected.value = null }
async function modifyFromView () {
  if (!selected.value) return
  const row = { ...selected.value }
  hideView()
  await nextTick()
  openEdit(row)
}
async function deleteFromView () {
  if (!selected.value) return
  const row = { ...selected.value }
  hideView()
  await nextTick()
  await confirmDelete(row)
}

/* ===== Delete ===== */
async function confirmDelete (row) {
  const result = await Swal.fire({
    title: '¿Eliminar usuario?',
    text: 'Se eliminará el usuario y su registro de persona asociado.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  })
  if (!result.isConfirmed) return

  try {
    const uid = row.user_id
    const pid = row.persona_id
    if (uid) await axios.delete(`${USERS_URL}/${uid}`, { headers: authHeaders() })
    if (pid) await axios.delete(`${PERSONAS_URL}/${pid}`, { headers: authHeaders() })
    await fetchUsers()
    toast('Usuario eliminado.')
  } catch (e) {
    console.error(e)
    toast('Error al eliminar.', 'error')
  }
}

/* ===== Bulk import ===== */
function openBulkModal() {
  bulk.preview = []; bulk.running = false; bulk.total = 0; bulk.done = 0; bulk.progress = 0
  bulkFileRef.value && (bulkFileRef.value.value = '')
  bulkModal.show()
}
function hideBulk() { bulkModal.hide() }

async function onBulkFileSelected(e) {
  const f = e.target.files?.[0]; if (!f) return
  const name = (f.name || '').toLowerCase()
  try {
    if (name.endsWith('.json')) {
      const text = await f.text(); const arr = JSON.parse(text); bulk.preview = Array.isArray(arr) ? arr : []
    } else if (name.endsWith('.csv')) {
      const text = await f.text(); bulk.preview = parseCSV(text)
    } else {
      const XLSX = await tryLoadXLSX()
      if (!XLSX) { toast('Sube JSON/CSV o instala la librería "xlsx".', 'error'); bulk.preview = []; return }
      const buf = await f.arrayBuffer()
      const wb = XLSX.read(buf); const ws = wb.Sheets[wb.SheetNames[0]]
      bulk.preview = XLSX.utils.sheet_to_json(ws)
    }
  } catch (err) {
    console.error(err); toast('No se pudo leer el archivo. Verifica el formato.', 'error'); bulk.preview = []
  }
}
function parseCSV(text) {
  const lines = text.split(/\r?\n/).filter(Boolean)
  if (!lines.length) return []
  const headers = lines[0].split(',').map(h => h.trim())
  const out = []
  for (let i=1;i<lines.length;i++){
    const row = {}; const cols = splitCSVLine(lines[i])
    headers.forEach((h,idx) => row[h] = (cols[idx] ?? '').trim()); out.push(row)
  }
  return out
}
function splitCSVLine(line) {
  const res = []; let cur = '', inQ = false
  for (let i=0;i<line.length;i++){
    const c = line[i]
    if (c === '"' ) { if (inQ && line[i+1] === '"') { cur += '"'; i++ } else inQ = !inQ }
    else if (c === ',' && !inQ) { res.push(cur); cur = '' }
    else cur += c
  }
  res.push(cur); return res
}
async function tryLoadXLSX() {
  try { const mod = await import(/* @vite-ignore */ 'xlsx').catch(() => null); return mod?.default || mod } catch { return null }
}
async function startBulk() {
  if (!bulk.preview.length) return
  bulk.running = true; bulk.total = bulk.preview.length; bulk.done = 0; bulk.progress = 0

  for (const raw of bulk.preview) {
    try {
      // Soporta "carrera" / "carreras" y similares con coma
      const toArr = (v) => Array.isArray(v)
        ? v
        : (typeof v === 'string' && v.includes(',')) ? v.split(',').map(s => s.trim()).filter(Boolean) : v

      const persona = {
        nombre: raw.nombre ?? '',
        apellidoPaterno: raw.apellidoPaterno ?? '',
        apellidoMaterno: raw.apellidoMaterno ?? '',
        fechaNacimiento: raw.fechaNacimiento ?? '',
        telefono: raw.telefono ?? '',
        sexo: raw.sexo ?? '',
        // admite arrays o string
        carrera: toArr(raw.carreras ?? raw.carrera ?? ''),
        cuatrimestre: toArr(raw.cuatrimestres ?? raw.cuatrimestre ?? ''),
        grupo: toArr(raw.grupos ?? raw.grupo ?? ''),
        matricula: raw.matricula ?? ''
      }

      // 1) Persona
      const { data: presp } = await axios.post(PERSONAS_URL, persona, { headers: authHeaders() })
      const personaId = String(presp?.persona?._id ?? presp?.persona?.id ?? presp?._id ?? '')

      // 2) User (backend genera password y envía)
      const user = {
        name: toNombreCompleto(persona),
        email: raw.email ?? '',
        rol: (raw.rol ?? '').toLowerCase(),
        estatus: (raw.estatus ?? 'activo').toLowerCase(),
        persona_id: personaId,
        matricula: persona.matricula,
        notify_email: true
      }
      await axios.post(USERS_URL, user, { headers: authHeaders() })
    } catch (e) {
      console.error('Fila con error:', e)
    } finally {
      bulk.done++; bulk.progress = Math.round(bulk.done * 100 / bulk.total)
    }
  }

  await fetchUsers(); toast('Importación finalizada.'); bulk.running = false
}
</script>

<!-- Importa tu CSS desde assets -->
<style scoped src="@/assets/css/Usuarios.css"></style>

<!-- Ajustes mínimos de estilo para chips (puedes moverlos a tu Usuarios.css) -->
<style scoped>
.chips-input .chips { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: .5rem; }
.chip { background: #eef2ff; color: #3730a3; border-radius: 999px; padding: .25rem .6rem; display: inline-flex; align-items: center; gap: .4rem; font-weight: 600; }
.chip .chip-x { background: transparent; border: 0; line-height: 1; cursor: pointer; color: #4f46e5; }
.input-group > .form-control { min-width: 200px; }
</style>
