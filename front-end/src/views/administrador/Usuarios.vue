<!-- src/views/administrador/Usuarios.vue -->
<template>
  <main class="panel-wrapper container-fluid">
    <!-- ===== Toolbar ===== -->
    <div class="toolbar py-2 px-0 px-lg-2">
      <div class="row g-2 align-items-center">
        <!-- Buscador -->
        <div class="col-12 col-xl-6">
          <div class="input-group input-group-lg search-group shadow-sm rounded-pill w-100">
            <span class="input-group-text rounded-start-pill">
              <i class="bi bi-search"></i>
            </span>
            <input
              v-model.trim="searchQuery"
              type="search"
              class="form-control search-input"
              placeholder="Buscar por nombre, matrícula, correo o grupo"
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
        <div class="col-12 col-md-7 col-xl-4">
          <div class="d-flex gap-2 w-100">
            <select v-model="filters.rol" class="form-select shadow-sm flex-fill">
              <option value="">Todos los roles</option>
              <option value="estudiante">Estudiantes</option>
              <option value="profesor">Profesores</option>
              <option value="admin">Administradores</option>
            </select>
          </div>
        </div>

        <!-- Acciones -->
        <div class="col-12 col-md-5 col-xl-2 d-flex gap-2 justify-content-end">
          <button class="btn btn-outline-primary fw-semibold rounded-pill px-3 py-2" @click="openBulkModal">
            <i class="bi bi-cloud-upload"></i>
            <span class="d-none d-xxl-inline ms-1">Cargar</span>
          </button>
          <button class="btn btn-success fw-semibold shadow pulse-btn rounded-pill px-3 py-2" @click="openCreate">
            <i class="bi bi-plus-lg"></i>
            <span class="d-none d-xxl-inline ms-1">Registrar</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ===== LISTA HORIZONTAL ===== -->
    <section class="users-list">
      <article
        v-for="u in filteredRows"
        :key="u._uid"
        class="user-item"
        :class="{
          'is-admin': u.rol === 'admin',
          'is-prof':  u.rol === 'profesor',
          'is-student': u.rol === 'estudiante'
        }"
      >
        <!-- Izquierda: avatar + nombre + matrícula -->
        <div class="ui-left">
          <img
            class="ui-avatar"
            :src="(u.urlFotoPerfil && u.urlFotoPerfil.startsWith('http'))
                   ? u.urlFotoPerfil
                   : safeImg(u.urlFotoPerfil, avatarNameFromRow(u))"
            alt="Avatar"
          />
          <div class="ui-id">
            <h6 class="mb-1 fw-bold text-truncate">{{ u.nombreCompleto || '—' }}</h6>
            <div class="ui-sub small">
              <span class="me-2"><i class="bi bi-hash me-1"></i>{{ u.matricula || '—' }}</span>
              <span><i class="bi bi-calendar3 me-1"></i>{{ formatDatePretty(u.fechaNacimiento) }}</span>
            </div>
          </div>
        </div>

        <!-- Medio: chips + datos -->
        <div class="ui-mid">
          <div class="d-flex align-items-center flex-wrap">
            <span class="ui-chip me-1" :class="badgeRol(u.rol)" :title="asTitle(u.rol)">{{ asTitle(u.rol) }}</span>
          </div>

          <div class="ui-field">
            <i class="bi bi-envelope me-1"></i>
            <span class="text-truncate" style="max-width: 36ch">{{ u.email || '—' }}</span>
          </div>

          <div class="ui-field">
            <i class="bi bi-people me-1"></i>
            <span class="text-truncate" :title="arrOrStr(u.cohorte) || '—'" style="max-width: 28ch">
              {{ arrOrStr(u.cohorte) || '—' }}
            </span>
          </div>

          <div class="ui-field d-none d-lg-flex">
            <i class="bi bi-telephone me-1"></i>
            <span>{{ u.telefono || '—' }}</span>
          </div>
        </div>

        <!-- Derecha: acciones -->
        <div class="ui-right">
          <button class="btn btn-outline-secondary btn-sm" @click="openView(u)" title="Visualizar" aria-label="Visualizar">
            <i class="bi bi-eye"></i>
          </button>
          <button class="btn btn-outline-primary btn-sm" @click="openEdit(u)" title="Modificar" aria-label="Modificar">
            <i class="bi bi-pencil"></i>
          </button>
          <button class="btn btn-outline-danger btn-sm" @click="confirmDelete(u)" title="Eliminar" aria-label="Eliminar">
            <i class="bi bi-trash"></i>
          </button>
        </div>
      </article>

      <div v-if="!filteredRows.length" class="users-empty">
        <i class="bi bi-people fs-1 d-block mb-2"></i>
        No se encontraron usuarios con los filtros actuales.
      </div>
    </section>

    <!-- ===== Modal: Registrar / Modificar ===== -->
    <div class="modal fade" ref="formModalRef" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered modal-lg modal-fixed">
        <form class="modal-content shadow-lg" @submit.prevent="onSubmit">
          <div class="modal-header border-0 rounded-top modal-header-gradient">
            <h5 class="modal-title fw-bold text-white mb-0">
              <i :class="['me-2', isEditing ? 'bi bi-pencil-square' : 'bi bi-plus-circle']"></i>
              {{ isEditing ? 'Modificar Usuario' : 'Registrar Usuario' }}
            </h5>
            <button type="button" class="btn-close btn-close-white close-contrast" @click="hideModal" aria-label="Cerrar"></button>
          </div>

          <div class="modal-body">
            <div v-if="hasErrors" class="alert alert-danger">
              <div class="fw-semibold mb-1">Revisa los campos:</div>
              <ul class="mb-0">
                <li v-for="(arr, field) in errors" :key="field">
                  <strong>{{ prettyField(field) }}:</strong> {{ (arr && arr[0]) || '' }}
                </li>
              </ul>
            </div>

            <!-- 1) Datos de acceso -->
            <div class="section mb-3">
              <button class="section-toggle" type="button" @click="sec.user = !sec.user">
                <i :class="['bi me-2', sec.user ? 'bi-chevron-down' : 'bi-chevron-right']"></i>
                Datos de acceso
              </button>
              <transition name="collapse-y">
                <div v-show="sec.user" class="section-body">
                  <div class="row g-2 align-items-end">
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
                      <label class="form-label">Correo <span class="text-danger">*</span></label>
                      <input
                        v-model.trim="form.user.email"
                        type="email"
                        class="form-control"
                        required
                        :class="{'is-invalid': emailInvalid}"
                        @blur="touch.email = true"
                        placeholder="ejemplo@dominio.com"
                      />
                      <div v-if="emailInvalid" class="invalid-feedback">Ingresa un correo válido.</div>
                    </div>

                    <!-- Imagen automática -->
                    <div class="col-12">
                      <label class="form-label d-flex align-items-center gap-2">
                        Imagen <span class="badge bg-info-subtle text-info border">Automática</span>
                      </label>
                      <div class="d-flex align-items-center gap-3">
                        <div class="preview rounded-circle secure-preview">
                          <img
                            :src="safeImg(form.user.urlFotoPerfil, displayNameFromPersona())"
                            alt="Avatar automático"
                            @error="onImgError($event, displayNameFromPersona())"
                          />
                        </div>
                        <small class="text-muted">
                          Se genera con iniciales (Nombre + Apellido paterno).
                        </small>
                      </div>
                    </div>

                    <div class="col-12">
                      <div class="alert alert-info py-2 px-3 mt-2">
                        <i class="bi bi-shield-lock me-1"></i>
                        La contraseña se <strong>generará automáticamente</strong> y se enviará por correo.
                      </div>
                    </div>
                  </div>
                </div>
              </transition>
            </div>

            <!-- 2) Datos de la persona -->
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
                      <input
                        v-model="form.persona.fechaNacimiento"
                        type="date"
                        class="form-control date-pretty"
                        :max="maxAdultDOB"
                        required
                        @change="touch.fecha = true"
                      />
                      <div v-if="touch.fecha && !isAdult" class="invalid-hint">Debes ser mayor de 18 años.</div>
                    </div>
                    <div class="col-12 col-md-4">
                      <label class="form-label">Teléfono <span class="text-danger">*</span></label>
                      <input
                        v-model.trim="form.persona.telefono"
                        type="tel"
                        class="form-control"
                        inputmode="numeric"
                        maxlength="10"
                        placeholder="10 dígitos"
                        required
                        :class="{'is-invalid': phoneInvalid}"
                        @keypress="allowOnlyDigits"
                        @input="onPhoneInput"
                        @blur="touch.telefono = true"
                      />
                      <div v-if="phoneInvalid" class="invalid-feedback">Debe tener exactamente 10 dígitos.</div>
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

            <!-- 3) Escolar -->
            <div class="section mb-2" :class="{'section-disabled': !form.user.rol}">
              <button class="section-toggle" type="button" :disabled="!form.user.rol" @click="form.user.rol && (sec.escolar = !sec.escolar)">
                <i :class="['bi me-2', sec.escolar ? 'bi-chevron-down' : 'bi-chevron-right']"></i>
                Datos escolares (según rol)
                <small v-if="!form.user.rol" class="text-muted ms-2">(elige primero el rol)</small>
              </button>
              <transition name="collapse-y">
                <div v-show="sec.escolar && form.user.rol" class="section-body">
                  <div class="row g-2">
                    <div class="col-12 col-md-4">
                      <label class="form-label">Matrícula <span class="text-danger">*</span></label>
                      <input v-model.trim="form.persona.matricula" type="text" class="form-control" required />
                    </div>

                    <!-- PROFESOR: 3 inputs + Botón Agregar + chips -->
                    <template v-if="isProfessor">
                      <div class="col-12 col-md-4">
                        <label class="form-label">Carrera <span class="text-danger">*</span></label>
                        <input
                          v-model.trim="currentCarrera"
                          type="text"
                          class="form-control"
                          placeholder="Ej. ITI"
                          inputmode="text"
                          autocomplete="off"
                          @keydown="allowOnlyLettersSpaces"
                          @paste="onLettersPaste"
                          @input="onCarreraInput"
                          pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]+$"
                          title="Solo letras y espacios"
                        />
                      </div>

                      <div class="col-6 col-md-2">
                        <label class="form-label">Cuatrimestre <span class="text-danger">*</span></label>
                        <input
                          v-model.trim="currentCuatrimestre"
                          type="number"
                          min="1" max="12" step="1"
                          class="form-control"
                          inputmode="numeric"
                          @keydown="blockNonNumber"
                          @input="onCuatriInput"
                        />
                      </div>

                      <div class="col-6 col-md-2">
                        <label class="form-label">Grupo <span class="text-danger">*</span></label>
                        <input
                          v-model.trim="currentGrupo"
                          type="text"
                          class="form-control"
                          placeholder="Ej. A"
                          inputmode="text"
                          autocomplete="off"
                          @keydown="allowOnlyLettersSpaces"
                          @paste="onLettersPaste"
                          @input="onGrupoInput"
                          pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]+$"
                          title="Solo letras y espacios"
                        />
                      </div>

                      <div class="col-12 col-md-4 d-flex align-items-end">
                        <button
                          type="button"
                          class="btn btn-outline-secondary w-100"
                          :disabled="!canAddProfCohorte"
                          @click="addProfCohorte"
                          title="Agregar grupo a la lista del profesor"
                          aria-label="Agregar grupo"
                        >
                          <i class="bi bi-plus-lg me-1"></i> Agregar grupo
                        </button>
                      </div>

                      <div class="col-12" v-if="form.persona.cohortes.length">
                        <label class="form-label">Grupos del profesor</label>
                        <div class="chips-input">
                          <div class="chips">
                            <span class="chip" v-for="(v,i) in form.persona.cohortes" :key="'coh-'+i">
                              {{ v }}
                              <button type="button" class="chip-x" @click="removeTag('cohortes', i)" aria-label="Quitar">&times;</button>
                            </span>
                          </div>
                        </div>
                        <small class="text-muted">Cada chip = <strong>CARRERA CUAT GRUPO</strong> (ej.: <code>ITI 10 A</code>).</small>
                      </div>
                    </template>

                    <!-- ESTUDIANTE / ADMIN -->
                    <template v-else>
                      <div class="col-12 col-md-4">
                        <label class="form-label">Carrera <span class="text-danger">*</span></label>
                        <input
                          v-model.trim="currentCarrera"
                          type="text"
                          class="form-control"
                          placeholder="Ej. ITI"
                          required
                          inputmode="text"
                          autocomplete="off"
                          @keydown="allowOnlyLettersSpaces"
                          @paste="onLettersPaste"
                          @input="onCarreraInput"
                          pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]+$"
                          title="Solo letras y espacios"
                        />
                      </div>

                      <div class="col-6 col-md-2">
                        <label class="form-label">Cuatrimestre <span class="text-danger">*</span></label>
                        <input
                          v-model.trim="currentCuatrimestre"
                          type="number"
                          min="1" max="12" step="1"
                          class="form-control"
                          required
                          inputmode="numeric"
                          @keydown="blockNonNumber"
                          @input="onCuatriInput"
                        />
                      </div>

                      <div class="col-6 col-md-2">
                        <label class="form-label">Grupo <span class="text-danger">*</span></label>
                        <input
                          v-model.trim="currentGrupo"
                          type="text"
                          class="form-control"
                          placeholder="Ej. A"
                          required
                          inputmode="text"
                          autocomplete="off"
                          @keydown="allowOnlyLettersSpaces"
                          @paste="onLettersPaste"
                          @input="onGrupoInput"
                          pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]+$"
                          title="Solo letras y espacios"
                        />
                      </div>
                    </template>
                  </div>
                </div>
              </transition>
            </div>
          </div>

          <div class="modal-footer border-0">
            <button type="button" class="btn btn-outline-secondary" @click="hideModal">Cancelar</button>
            <button type="submit" class="btn btn-primary" :disabled="saving || !canSubmitUser">
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
        <div class="modal-content border-0 shadow-xl">
          <div class="modal-header border-0 rounded-top modal-header-gradient">
            <div class="d-flex align-items-center gap-3">
              <img
                class="ui-avatar"
                :src="(selected?.urlFotoPerfil && selected.urlFotoPerfil.startsWith('http')) ? selected.urlFotoPerfil : safeImg(selected?.urlFotoPerfil, avatarNameFromRow(selected))"
                alt="Avatar"
              />
              <div>
                <h4 class="mb-0 text-white">{{ selected?.nombreCompleto }}</h4>
                <div class="text-white-50 small">{{ formatDatePretty(selected?.fechaNacimiento) }}</div>
              </div>
            </div>
            <button type="button" class="btn-close btn-close-white close-contrast" @click="hideView" aria-label="Cerrar"></button>
          </div>

          <div class="modal-body">
            <div v-if="!selected" class="text-center text-muted py-3">Sin selección</div>
            <div v-else class="row g-3">
              <div class="col-12">
                <div class="show-chips">
                  <span class="badge rounded-pill" :class="badgeRol(selected.rol)">{{ asTitle(selected.rol) }}</span>
                  <span class="badge rounded-pill" :class="badgeEstatus(selected.estatus)">{{ asTitle(selected.estatus) }}</span>
                </div>
              </div>
              <div class="col-12 col-lg-6">
                <div class="show-card">
                  <div class="sc-row"><i class="bi bi-envelope"></i><span class="text-truncate">{{ selected.email || '—' }}</span></div>
                  <div class="sc-row"><i class="bi bi-hash"></i><span>{{ selected.matricula || '—' }}</span></div>
                  <div class="sc-row"><i class="bi bi-telephone"></i><span>{{ selected.telefono || '—' }}</span></div>
                </div>
              </div>
              <div class="col-12 col-lg-6">
                <div class="show-card">
                  <div class="sc-row"><i class="bi bi-people"></i><span>{{ arrOrStr(selected.cohorte) || '—' }}</span></div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer border-0 d-flex w-100">
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-outline-primary" @click="modifyFromView" title="Editar">
                <i class="bi bi-pencil"></i><span class="d-none d-md-inline ms-1">Editar</span>
              </button>
              <button type="button" class="btn btn-outline-danger" @click="deleteFromView" title="Eliminar">
                <i class="bi bi-trash"></i><span class="d-none d-md-inline ms-1">Eliminar</span>
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
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header border-0 rounded-top modal-header-gradient">
            <h5 class="modal-title fw-bold text-white">
              <i class="bi bi-cloud-upload me-2"></i> Carga masiva de usuarios
            </h5>
            <button type="button" class="btn-close btn-close-white close-contrast" @click="hideBulk" aria-label="Cerrar"></button>
          </div>

          <div class="modal-body">
            <div class="mb-2">
              <input ref="bulkFileRef" type="file" accept=".json,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel" class="form-control" @change="onBulkFileSelected" />
              <small class="text-muted">
                Formatos: <strong>JSON</strong>, <strong>CSV</strong>, <strong>XLSX</strong>.
                Campos esperados: <code>{ nombre, apellidoPaterno, apellidoMaterno, fechaNacimiento, telefono, sexo, matricula, email, rol, estatus, cohorte }</code>.
                Si no traen <code>cohorte</code>, se intentará construir con <code>carrera</code>, <code>cuatrimestre</code>, <code>grupo</code>.
              </small>
            </div>

            <div v-if="bulk.preview.length" class="mt-3">
              <div class="alert alert-secondary py-2 px-3">
                Se encontraron <strong>{{ bulk.preview.length }}</strong> filas.
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
                    <th>Cohorte</th>
                  </tr></thead>
                  <tbody>
                    <tr v-for="(r,i) in bulk.preview.slice(0,8)" :key="i">
                      <td>{{ i+1 }}</td>
                      <td>{{ r.nombre }}</td>
                      <td>{{ [r.apellidoPaterno, r.apellidoMaterno].filter(Boolean).join(' ') }}</td>
                      <td class="d-none d-md-table-cell">{{ r.matricula }}</td>
                      <td>{{ r.rol }}</td>
                      <td class="d-none d-lg-table-cell">{{ r.email }}</td>
                      <td>{{ r.cohorte || buildCohorte(r.carrera, r.cuatrimestre, r.grupo) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div v-if="bulk.running" class="mt-3">
              <div class="progress">
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" :style="{ width: bulk.progress + '%' }">{{ bulk.progress }}%</div>
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
import { useUsuarios } from '@/assets/js/Usuarios'

const {
  server, rows, filteredRows, searchQuery, filters,
  form, isEditing, saving, errors, hasErrors, sec,
  selected, bulk, bulkFileRef,
  formModalRef, viewModalRef, bulkModalRef,
  touch,
  onImgError, safeImg,
  badgeRol, badgeEstatus, asTitle, formatDatePretty, arrOrStr,
  maxAdultDOB, emailInvalid, phoneInvalid, isAdult,
  isProfessor, buildCohorte, canAddProfCohorte, addProfCohorte, canSubmitUser,
  onInstantSearch, clearSearch, fetchUsers, goPage,
  openCreate, openEdit, hideModal, onSubmit,
  blockNonNumber, allowOnlyDigits, onPhoneInput,
  openView, hideView, modifyFromView, deleteFromView, confirmDelete,
  openBulkModal, hideBulk, onBulkFileSelected, startBulk,
  parseCohorte, displayNameFromPersona,
  prettyField, avatarNameFromRow, allowOnlyLettersSpaces, onLettersPaste,
  currentCarrera, currentCuatrimestre, currentGrupo, onCarreraInput, onGrupoInput, onCuatriInput,
  removeTag,
} = useUsuarios()
</script>

<style>
@import "@/assets/css/Usuarios.css";
</style>
