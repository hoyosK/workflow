<template>
    <CRow>
        <CCol :xs="12">
            <div v-if="cotizacion.estado !== 'cancelada'">
                <CCard class="mb-4">
                    <CCardHeader>
                        <strong>Cotizar {{ producto.nombreProducto }}
                            <span v-if="cotizacionId > 0">- Cotización No.{{ cotizacionId }}</span></strong>
                            <button class="btn btn-primary float-end btn-sm " @click="$router.push('/admin/tareas')">
                                Ir a listado de cotizaciones
                            </button>
                            <button class="btn btn-success float-end btn-sm me-3" @click="verProgresion">
                                Ver progresión
                            </button>
                    </CCardHeader>
                    <CCardBody v-if="!productoid">
                        <CRow :xs="{ cols: 1, gutter: 4 }" :md="{ cols: 1}">
                            <CCol xs>
                                <CCard class="h-100">
                                    <div>
                                        <div class="text-center">
                                            <CCardImage v-if="typeof producto.extraData !== 'undefined'" orientation="top" :src="producto.extraData.producto.mainImage || ''" style="max-width: 150px;"/>
                                        </div>
                                        <div class="mt-5" v-if="!showWizzard" v-html="producto.descripcion"></div>
                                    </div>
                                </CCard>
                            </CCol>
                        </CRow>
                        <div class="globalModal" v-if="showProgresion">
                            <div class="globalModalContainer p-5">
                                <div @click="showProgresion = false" class="globalModalClose mt-3"><i class="fas fa-times-circle"></i></div>
                                <div>
                                    <h3>Progresión de tarea global</h3>
                                </div>
                                <hr>
                                <div class="mt-5">
                                    <h5 class="text-primary">Porcentaje completado {{progresion.total}}%</h5>
                                    <CProgress
                                        class="mt-2"
                                        color="success"
                                        thin
                                        :precision="1"
                                        :value="progresion.percent"
                                    />
                                    <div class="text-muted mt-3">
                                        Total de campos: {{progresion.total}}
                                    </div>
                                </div>
                                <div class="mt-5">
                                    <h3>Progresión por secciones</h3>
                                </div>
                                <hr>
                                <div class="row">
                                    <template class="mt-5" v-for="item in progresion.nodos">
                                        <div class="col-12 col-sm-4 mb-4" v-for="seccion in item.secciones">
                                            <h5 class="text-primary">{{seccion.nombre}}</h5>
                                            <CProgress
                                                class="mt-2"
                                                color="success"
                                                thin
                                                :precision="1"
                                                :value="seccion.percent"
                                            />
                                            <div class="text-muted mt-3">
                                                Total de campos: {{seccion.total}}
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </CCardBody>
                    <CCardFooter class="p-4 text-center" v-if="!showWizzard">
                        <div v-if="typeof producto.flujo !== 'undefined' && Object.keys(producto.flujo).length > 0">
                            <CButton v-if="cotizacionId === 0" color="primary" @click="iniciarCotizacion">
                                Iniciar cotización <i class="fas fa-check-circle ms-2"></i>
                            </CButton>
                        </div>
                        <div v-else class="text-danger">
                            <i class="fas fa-warning me-2"></i> El flujo no se encuentra activo
                        </div>
                    </CCardFooter>
                </CCard>
                <div v-if="noVisible">
                    <CCard class="mt-4">
                        <CCardHeader>
                            <h3>Tarea no disponible</h3>
                        </CCardHeader>
                        <CCardBody class="text-center py-5">
                            <div>
                                {{ noVisible }}
                            </div>
                        </CCardBody>
                    </CCard>
                </div>
                <div v-else>
                    <template v-if="estadoCotizacion === 'expirada'">
                        <div class="text-success text-center">
                            Esta cotización se encuentra expirada
                        </div>
                    </template>
                    <template v-else>
                        <CCard class="mt-4" v-if="showWizzard && typeof flujoActivo.type !== 'undefined'">
                            <CCardHeader>
                                <h3 v-if="flujoActivo.label">{{ flujoActivo.nodoName }}</h3>
                            </CCardHeader>
                            <CCardBody>
                                <div class="row">
                                    <div v-if="flujoActivo.formulario.secciones.length > 0" :class="(flujoActivo.formulario.secciones.length >= 5)?'col-3':'col-12'">
                                        <ul :class="(flujoActivo.formulario.secciones.length >= 5)?'progress-indicator nocenter stacked':'progress-indicator custom-complex'">
                                            <template v-for="(etapa, index) in flujoActivo.formulario.secciones" :key="index">
                                                <li :class="(etapa.completada)?'completed':((currentTabIndex === index)?'active':'')" @click="cambiarSeccion(index)" v-if="etapa.show">
                                                    <span class="bubble"></span>
                                                    <h6
                                                        class="stacked-text mb-0">
                                                        {{ etapa.nombre }}
                                                    </h6>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                    <div :class="(flujoActivo.formulario.secciones.length >= 5)?'col-9':'col-12'" v-if="typeof (flujoActivo.formulario.secciones[currentTabIndex]) !=='undefined' ">
                                        <div v-if="flujoActivo.formulario.secciones[currentTabIndex].instrucciones">
                                            <div class="bg-light rounded p-3 mb-4">
                                                <h6 class="fw-bold">Instrucciones:</h6>
                                                {{ flujoActivo.formulario.secciones[currentTabIndex].instrucciones }}
                                            </div>
                                        </div>
                                        <CForm class="row">
                                            <template v-if="flujoActivo.formulario.secciones[currentTabIndex].show">
                                                <template v-for="(input, keyInput) in flujoActivo.formulario.secciones[currentTabIndex].campos">
                                                    <template v-if="typeof camposCumplidores[input.id] !== 'undefined' && camposCumplidores[input.id]">

                                                        <div v-if="typeof(input) !=='undefined' && input.activo" :class="'col-' + ((input.layoutSizeMobile) ? input.layoutSizeMobile : '12') + ' col-sm-' + ((input.layoutSizePc) ? input.layoutSizePc : '4') + ' mb-2' + (!input.visible ? ' d-none' : '')">

                                                            <div v-if="(input.tipoCampo === null || input.tipoCampo === 'text') && (input.mascara !== '' || input.mascara !== null)">
                                                                <label :for="input.id"><span v-if="input.requerido">*</span>{{ input.nombre }}</label>
                                                                <input type="text" :class="{'form-control': !input.requeridoError, 'form-control requiredInput': input.requeridoError}"
                                                                       :readonly="input.readonly || input.valorCalculado"
                                                                       :label="input.nombre"
                                                                       :aria-label="input.nombre"
                                                                       v-model="input.valor"
                                                                       :id="input.id"
                                                                       v-maska
                                                                       :data-maska="input.mascara"
                                                                       :data-maska-tokens="input.tokenMask"
                                                                       :disabled="input.deshabilitado"
                                                                       @change="campoCumpleCondiciones"
                                                                >
                                                                <small class="text-muted">{{input.desc}}</small>
                                                            </div>
                                                            <div v-if="(input.tipoCampo === null || input.tipoCampo === 'textArea') && (input.mascara !== '' || input.mascara !== null)">
                                                                <label :for="input.id"><span v-if="input.requerido">*</span>{{ input.nombre }}</label>
                                                                <textarea type="text" :class="{'form-control': !input.requeridoError, 'form-control requiredInput': input.requeridoError}"
                                                                       :readonly="input.readonly || input.valorCalculado"
                                                                       :label="input.nombre"
                                                                       :aria-label="input.nombre"
                                                                       v-model="input.valor"
                                                                       :id="input.id"
                                                                       v-maska
                                                                       :data-maska="input.mascara"
                                                                       :data-maska-tokens="input.tokenMask"
                                                                       :disabled="input.deshabilitado"
                                                                       @change="campoCumpleCondiciones"
                                                                >
                                                                </textarea>
                                                                <small class="text-muted">{{input.desc}}</small>
                                                            </div>
                                                            <div v-if="(input.tipoCampo === null || input.tipoCampo === 'date') && (input.mascara !== '' || input.mascara !== null)">
                                                                <label :for="input.id"><span v-if="input.requerido">*</span>{{ input.nombre }}</label>
                                                                <input type="date" :class="{'form-control': !input.requeridoError, 'form-control requiredInput': input.requeridoError}"
                                                                       :readonly="input.readonly"
                                                                       :label="input.nombre"
                                                                       :aria-label="input.nombre"
                                                                       v-model="input.valor"
                                                                       :id="input.id"
                                                                       v-maska
                                                                       :data-maska="input.mascara"
                                                                       :data-maska-tokens="input.tokenMask"
                                                                       :disabled="input.deshabilitado"
                                                                       @change="campoCumpleCondiciones"
                                                                >
                                                                <small class="text-muted">{{input.desc}}</small>
                                                            </div>
                                                            <div v-if="(input.tipoCampo === null || input.tipoCampo === 'number') && (input.mascara !== '' || input.mascara !== null)">
                                                                <label :for="input.id"><span v-if="input.requerido">*</span>{{ input.nombre }}</label>
                                                                <input type="number" :class="{'form-control': !input.requeridoError, 'form-control requiredInput': input.requeridoError}"
                                                                       :readonly="input.readonly"
                                                                       :aria-label="input.nombre"
                                                                       v-model="input.valor"
                                                                       :id="input.id"
                                                                       v-maska
                                                                       :data-maska="input.mascara"
                                                                       :data-maska-tokens="input.tokenMask"
                                                                       :disabled="input.deshabilitado"
                                                                       @change="campoCumpleCondiciones"
                                                                >
                                                                <small class="text-muted">{{input.desc}}</small>
                                                            </div>
                                                            <div v-if="input.tipoCampo !== null && input.tipoCampo === 'select' && (input.mascara === '' || input.mascara === null)">
                                                                <label :for="input.id"><span v-if="input.requerido">*</span>{{ input.nombre }}</label>
                                                                <select :class="{'form-select': !input.requeridoError, 'form-select requiredInput': input.requeridoError}" v-model="input.valor" aria-label=".form-select-lg example" :readonly="input.readonly" :disabled="input.deshabilitado" @change="campoCumpleCondiciones">
                                                                    <option v-if="typeof (input.catalogoId) !== 'undefined'" v-for="(item , index) in input.catalogoId.items" :value="item[input.catalogoValue]" :key="index">
                                                                        {{ item[input.catalogoLabel] }}
                                                                    </option>
                                                                </select>
                                                                <small class="text-muted">{{input.desc}}</small>
                                                            </div>
                                                            <div v-if="input.tipoCampo !== null && input.tipoCampo === 'multiselect' && (input.mascara === '' || input.mascara === null)">
                                                                <label :for="input.id"><span v-if="input.requerido">*</span>{{ input.nombre }}</label>
                                                                <div :class="{'': !input.requeridoError, 'requiredInput': input.requeridoError}">
                                                                    <multiselect
                                                                        :options="obtenerItemsPorCatalogo(input.catalogoId.nombreCatalogo)"
                                                                        :searchable="true"
                                                                        :mode="'tags'"
                                                                        :label="input.catalogoLabel"
                                                                        :value-prop="input.catalogoValue"
                                                                        :disabled="input.deshabilitado"
                                                                        v-model="input.valor"
                                                                        :max="input.max"
                                                                        :allow-empty="!input.requerido"
                                                                        :min="input.min"
                                                                        @change="campoCumpleCondiciones"/>
                                                                </div>
                                                                <small class="text-muted">{{input.desc}}</small>
                                                            </div>
                                                            <div v-if="input.tipoCampo !== null && input.tipoCampo === 'checkbox' && (input.mascara === '' || input.mascara === null)">
                                                                <label :for="input.id"><span v-if="input.requerido">*</span>{{ input.nombre }}</label>
                                                                <div :class="{'form-check': !input.requeridoError, 'form-check requiredInput': input.requeridoError}" v-if="typeof (input.catalogoId) !== 'undefined'" v-for="(item, indexOption) in input.catalogoId.items">
                                                                    <input class="form-check-input" :name="input.id" v-model="item.selected" :value="item[input.catalogoValue]" type="checkbox" :id="input.id+'_'+item[input.catalogoLabel]+'_'+indexOption" :disabled="input.deshabilitado" @change="campoCumpleCondiciones">
                                                                    <label class="form-check-label" :for="input.id+'_'+item[input.catalogoLabel]+'_'+indexOption"> {{ item[input.catalogoLabel] }}</label>
                                                                </div>
                                                            </div>
                                                            <div v-if="input.tipoCampo !== null && input.tipoCampo === 'option' && (input.mascara === '' || input.mascara === null)">
                                                                <label :for="input.id"><span v-if="input.requerido">*</span>{{ input.nombre }}</label>
                                                                <div :class="{'form-check': !input.requeridoError, 'form-check requiredInput': input.requeridoError}" v-if="typeof (input.catalogoId) !== 'undefined'" v-for="item in obtenerItemsPorCatalogo(input.catalogoId.nombreCatalogo)">
                                                                    <input class="form-check-input" :name="input.id" v-model="input.valor" :value="item[input.catalogoValue]" type="radio" :id="input.id+'_'+item[input.catalogoLabel]" :disabled="input.deshabilitado" @change="campoCumpleCondiciones">
                                                                    <label class="form-check-label" :for="input.id+'_'+item[input.catalogoLabel]">
                                                                        {{ item[input.catalogoLabel] }}
                                                                    </label>
                                                                </div>

                                                            </div>
                                                            <div v-if="(input.tipoCampo !== null && input.tipoCampo === 'range') && (input.mascara === '' || input.mascara === null)">
                                                                <label :for="input.id"><span v-if="input.requerido">*</span>{{ input.nombre }} {{ input.valor }}</label>
                                                                <div class="input-group">
                                                                    <input type="range"
                                                                           class="form-range"
                                                                           :id="input.id"
                                                                           :min="input.longitudMin"
                                                                           :max="input.longitudMax"
                                                                           step="1"
                                                                           v-model="input.valor"
                                                                           :disabled="input.deshabilitado"
                                                                           @change="campoCumpleCondiciones"
                                                                    >
                                                                </div>
                                                            </div>
                                                            <div v-if="(input.tipoCampo !== null && (input.tipoCampo === 'file' ||  input.tipoCampo === 'fileER')) && (input.mascara === '' || input.mascara === null)">
                                                                <label :for="input.id"><span v-if="input.requerido">*</span>{{ input.nombre }}</label>
                                                                <div :class="{'': !input.requeridoError, 'requiredInput': input.requeridoError}">
                                                                    <file-pond type="file"
                                                                               class="filepond"
                                                                               :name="input.id"
                                                                               label-idle="Arrastra tus archivos acá"
                                                                               credits="false"
                                                                               data-allow-reorder="true"
                                                                               data-max-file-size="3MB"
                                                                               :disabled="input.deshabilitado"
                                                                               :server="{
                                                                                process: (fieldName, file, metadata, load, error, progress, abort) => {
                                                                                  handleUpload(file, input.id, load, error, progress);
                                                                                }
                                                                            }"
                                                                               ref="filepondInput">
                                                                    </file-pond>
                                                                </div>
                                                            </div>
                                                            <div v-if="(input.tipoCampo !== null && input.tipoCampo === 'signature')">
                                                                <label :for="input.id"><span v-if="input.requerido">*</span>{{ input.nombre }}</label>
                                                                <div :class="{'': !input.requeridoError, 'requiredInput': input.requeridoError}">
                                                                    <div v-show="input.valor && input.valor !== ''" class="text-center">
                                                                        <div>
                                                                            <div>
                                                                                <img :src="input.valor" style="max-height: 40px"/>
                                                                            </div>
                                                                            <small style="color: #bdbdbd; font-size: 10px">Firma guardada</small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="text-center">
                                                                        <VueSignaturePad :ref="'signature_'+input.id" style="border: 1px solid #c0c0c0; height: 100px; width: 100%" class="rounded"/>
                                                                        <a @click="resetSignature('signature_'+input.id)" class="text-danger cursor-pointer" style="font-size: 10px">Reiniciar</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div v-if="(input.tipoCampo === null || input.tipoCampo === 'aprobacion')">
                                                                <div class="text-center mt-2 mb-2">
                                                                    <div :class="{'': !input.requeridoError, 'requiredInput': input.requeridoError}">
                                                                        <fieldset>
                                                                            <h6><span v-if="input.requerido">*</span>{{input.nombre}}</h6>
                                                                            <div class="toggle m-auto text-center">
                                                                                <input type="radio" value="aprobar" :id="input.id + 'aprobarBtn'" :name="input.id + 'aprobacionBtn'" :disabled="input.deshabilitado" v-model="input.valor" @change="campoCumpleCondiciones"/>
                                                                                <label :for="input.id + 'aprobarBtn'">Aprobar</label>
                                                                                <input type="radio" value="rechazar" :id="input.id + 'rechazarBtn'" :name="input.id + 'aprobacionBtn'" :disabled="input.deshabilitado" v-model="input.valor" @change="campoCumpleCondiciones"/>
                                                                                <label :for="input.id + 'rechazarBtn'" style="background-color: #f5f5f5">Rechazar</label>
                                                                            </div>
                                                                        </fieldset>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </template>
                                            </template>
                                        </CForm>
                                    </div>
                                </div>
                                <div class="row" v-if="showWizzard && (typeof flujoActivo.salidaReplaced !== 'undefined')">
                                    <div class="col-12">
                                        <h5 class="text-primary">Visualización en pantalla</h5>
                                    </div>
                                    <div class="col-12 mt-4" v-html="flujoActivo.salidaReplaced"></div>
                                </div>
                            </CCardBody>
                            <CCardFooter class="p-4 text-end">
                                <div v-if="estadoCotizacion !== 'finalizada'">
                                    <CButton v-if="flujoHasPrev" color="primary" @click="continuarCotizacion('prev')">
                                        {{flujoActivo.btnText.prev}}
                                    </CButton>
                                    <CButton v-if="flujoHasNext" color="primary" @click="continuarCotizacion('next')" class="ms-3">
                                        {{flujoActivo.btnText.next}}
                                    </CButton>
                                    <CButton v-if="!flujoHasNext" color="success" @click="continuarCotizacion('next', 'finalizada')" class="ms-3">
                                        {{flujoActivo.btnText.finish}}
                                    </CButton>
                                    <CButton color="danger" @click="cancelarCotizacion" class="ms-3">
                                        Cancelar cotización
                                    </CButton>
                                </div>
                                <div v-else class="text-success text-center">
                                    Esta cotización se encuentra finalizada
                                </div>
                            </CCardFooter>
                        </CCard>
                    </template>
                    <CCard class="mt-3">
                        <CCardHeader>
                            <h3>Archivos adjuntos</h3>
                        </CCardHeader>
                        <CCardBody>
                            <div class="globalModal" v-if="showImagePreview">
                                <div class="globalModalContainer text-center p-5">
                                    <div @click="showImagePreview = false" class="globalModalClose mt-3"><i class="fas fa-times-circle"></i></div>
                                    <img :src="imagePreviewTmp.url"/>
                                    <div class="text-center">
                                        <a class="btn btn-primary mt-5" :href="imagePreviewTmp.url" :download="imagePreviewTmp.name" target="_blank"><i class="fa fa-download me-2"></i>Descargar</a>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <template v-for="file in previewFiles">
                                    <div class="col-12 col-sm-3" v-if="!file.salida">
                                        <div class="mb-3 fw-bold">{{file.name}}</div>
                                        <div class="text-center rounded cursor-pointer" style="padding: 20px; background: #d7d7d7" @click="openPreview(file)">
                                            <div v-if="file.type === 'image'">
                                                <img :src="file.url" style="max-height: 60px">
                                            </div>
                                            <div v-if="file.type === 'pdf'">
                                                <div style="min-height: 60px">
                                                    <a class="btn btn-primary mt-3" target="_blank">Ver PDF</a>
                                                </div>
                                            </div>
                                            <div v-if="file.type === 'docx'">
                                                <div style="min-height: 60px">
                                                    <a class="btn btn-primary mt-3" target="_blank">Ver documento</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </CCardBody>
                    </CCard>
                    <CCard class="mt-3">
                        <CCardHeader>
                            <h3>Archivos generados</h3>
                        </CCardHeader>
                        <CCardBody>
                            <div class="row">
                                <template v-for="file in previewFiles">
                                    <div class="col-12 col-sm-3" v-if="file.salida">
                                        <div class="mb-3 fw-bold">{{file.name}}</div>
                                        <div class="text-center rounded cursor-pointer" style="padding: 20px; background: #d7d7d7" @click="openPreview(file)">
                                            <div v-if="file.type === 'image'">
                                                <img :src="file.url" style="max-height: 60px">
                                            </div>
                                            <div v-if="file.type === 'pdf'">
                                                <div style="min-height: 60px">
                                                    <a class="btn btn-primary mt-3" target="_blank">Ver PDF</a>
                                                </div>
                                            </div>
                                            <div v-if="file.type === 'docx'">
                                                <div style="min-height: 60px">
                                                    <a class="btn btn-primary mt-3" target="_blank">Ver documento</a>
                                                </div>
                                            </div>
                                            <div>{{file.label}}</div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </CCardBody>
                    </CCard>
                    <CCard class="mt-3">
                        <CCardHeader>
                            <h3>Resumen de cotización</h3>
                            <div class="text-muted">
                                * Únicamente se muestran secciones y campos llenos
                            </div>
                        </CCardHeader>
                        <CCardBody>
                            <div>
                                <div v-for="item in resumen" class="mb-4">
                                    <div v-if="typeof item.campos !== 'undefined'">
                                        <h6 class="cursor-pointer" @click="item.active = !item.active">{{ item.nombre  || 'Sin nombre' }}</h6>
                                        <hr>
                                        <div class="row" v-if="item.active">
                                            <template v-if="typeof item.campos !== 'undefined'">
                                                <template v-for="(campo, key) in item.campos">
                                                    <div class="col-12 col-sm-4 mb-3" v-if="campo.value !== ''">
                                                        <div class="text-primary">
                                                            {{ campo.label }}
                                                        </div>
                                                        <div>
                                                            {{ campo.value }}
                                                        </div>
                                                    </div>
                                                </template>
                                            </template>
                                            <template v-else>
                                                <div class="col-12 text-danger">
                                                    Sin campos llenos
                                                </div>
                                            </template>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CCardBody>
                    </CCard>
                </div>
                <div v-if="typeof authInfo.m['tareas/admin/usuario-asignado'] !== 'undefined' && authInfo.m['tareas/admin/usuario-asignado']" class="mt-3">
                    <CCard class="mt-2">
                        <CCardHeader>
                            <h3>Edición de usuario</h3>
                        </CCardHeader>
                        <CCardBody class="text-center">
                            <div v-if="estadoCotizacion !== 'finalizada'" class="text-start">
                                <h6>Cambiar usuario asignado</h6>
                                <div>
                                    <multiselect :options="users" v-model="usuarioEditar" :searchable="true"></multiselect>
                                </div>
                                <div class="text-end mt-3">
                                    <button class="btn btn-primary" @click="editarUsuarioCotizacion">Cambiar usuario</button>
                                </div>
                            </div>
                            <div v-else class="text-success text-center">
                                Esta cotización se encuentra finalizada
                            </div>
                        </CCardBody>
                    </CCard>
                </div>
                <div v-if="typeof authInfo.m['tareas/admin/usuario-asignado'] !== 'undefined' && authInfo.m['tareas/admin/usuario-asignado']" class="mt-3">
                    <CCard class="mt-2">
                        <CCardHeader>
                            <h3>Edición de paso</h3>
                        </CCardHeader>
                        <CCardBody class="text-center">
                            <div v-if="estadoCotizacion !== 'finalizada'" class="text-start">
                                <h6>Colocar flujo en paso</h6>
                                <div>
                                    <multiselect :options="users" v-model="usuarioEditar" :searchable="true"></multiselect>
                                </div>
                                <div class="text-end mt-3">
                                    <button class="btn btn-primary" @click="editarUsuarioCotizacion">Cambiar usuario</button>
                                </div>
                            </div>
                            <div v-else class="text-success text-center">
                                Esta cotización se encuentra finalizada
                            </div>
                        </CCardBody>
                    </CCard>
                </div>
                <CCard class="mt-3">
                    <CCardHeader>
                        <strong>Bitácora</strong>
                        <div v-if="producto.modoPruebas" class="mt-3">
                            <h5 class="text-danger"><i class="fas fa-warning me-2"></i> Modo pruebas activo</h5>
                        </div>
                    </CCardHeader>
                    <CCardBody v-if="!productoid" style="overflow: auto; max-height: 700px">
                        <div class="row mb-2" v-for="bit in bitacora">
                            <div class="col-12"><b>Operación:</b> {{ bit.log }}</div>
                            <div class="col-12 col-sm-3"><b>Fecha:</b> {{ bit.createdAt }}</div>
                            <div class="col-12 col-sm-3"><b>Usuario:</b> {{ bit.usuarioNombre }}</div>
                            <div class="col-12 col-sm-3"><b>Corporativo:</b> {{ bit.usuarioCorporativo }}</div>
                            <div class="col-12" v-if="bit.dataInfo && bit.dataInfo !== ''">
                                <div class="mb-3 p-3 bg-light" v-html="bit.dataInfo"></div>
                            </div>
                            <div class="col-12">
                                <hr>
                            </div>
                        </div>
                    </CCardBody>
                </CCard>
            </div>
            <div v-else class="text-danger text-center p-4">
                <CCard class="mb-4">
                    <CCardBody v-if="!productoid">
                        <i class="fas fa-warning me-2"></i>Esta cotización se encuentra cancelada
                        <hr>
                        <div class="mt-3">
                            <CButton color="primary" @click="$router.push('/panel-productos')">
                                <i class="fas fa-arrow-left me-2"></i> Regresar
                            </CButton>
                        </div>
                    </CCardBody>
                </CCard>
            </div>
        </CCol>
    </CRow>

</template>
<script>
import toolbox from "@/toolbox";
import 'form-wizard-vue3/dist/form-wizard-vue3.css'
import login from "@/views/pages/Login.vue";
import {CChart} from "@coreui/vue-chartjs";
import {vMaska} from "maska";
import {useRouter, useRoute} from 'vue-router';
import Multiselect from '@vueform/multiselect'

// Import FilePond
import vueFilePond from 'vue-filepond';
import 'filepond/dist/filepond.min.css';
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css';
import Button from "@/views/forms/form_elements/FormElementButton.vue";
import {mapGetters} from "vuex";
import * as dayjs from 'dayjs'

const FilePond = vueFilePond();

export default {
    name: 'Tables',
    props: ['productoid', 'externalshow', 'externalflujo', 'expedienteid'],
    directives: {maska: vMaska},
    components: {
        Button,
        login,
        CChart,
        FilePond,
        useRoute,
        Multiselect
    },
    data() {
        return {
            productoId: 0,
            cotizacionId: 0,
            producto: {},
            cotizacion: {},
            logHistory: [],
            flujoActivo: {},
            flujoHasPrev: false,
            flujoHasNext: false,
            currentTabIndex: 0,
            estadoCotizacion: '',
            bitacora: {},
            resumen: {},
            noVisible: false,
            users: [],
            usuarioEditar: 0,

            // condicionales
            seccionesCumplidoras: {},
            camposCumplidores: {},
            camposValores: {},

            // calculados
            camposCalculados: {},
            userVars: [],

            // preview de adjuntos
            previewFiles: {},
            showImagePreview: false,
            imagePreviewTmp: {},

            // progresión
            showProgresion: false,
            progresion: {},

            // toda la data historica
            allFieldsData: {},







            indexProductoSeleccionado: false,
            visibleVerticallyCenteredDemo: false,
            flujoSelected: {},
            showWizzard: false,
            seccionesActivas: [],
            edgesActuales: '',
            salidasSiguientesTrue: [],
            salidasSiguienteFalse: [],
            productoSelected: {},
            showSalidas: false,
            pdfPreview: false,
            salidaActualHTML: "",
            seccionRaw: [],

            camposSeccion: [],
            productos: [],
            nodeNexId: '',
            dataCotizacion: "",
            parseFormData: "",
            procesoSelected: {},
            nodosSiguientes: [],
            salidasSiguientes: [],
            procesosSiguientes: [],
            formulariosSiguientes: [],
            condicionesSiguientes: [],

        };
    },
    mounted() {
        const self = this;
        if ((typeof useRoute().params.id !== 'undefined')) {
            this.productoId = (typeof useRoute().params.id !== 'undefined') ? parseInt(useRoute().params.id) : 0;
            this.cotizacionId = (typeof useRoute().params.cotizacionId !== 'undefined') ? parseInt(useRoute().params.cotizacionId) : 0;
            this.loadData(function () {
                if (self.cotizacionId > 0) {
                    self.getFlujo();
                    self.showWizzard = true;
                }
            });
        }

        // validación de acceso custom
        if (typeof this.authInfo.m['tareas/admin/usuario-asignado'] !== 'undefined' && this.authInfo.m['tareas/admin/usuario-asignado']) {
            this.getUsers();
        }
    },
    computed: {
        ...mapGetters({
            authLogged: 'authLogged',
            authInfo: 'authInfo',
        }),

    },
    watch: {},
    methods: {
        loadData(callback) {
            const self = this;
            toolbox.doAjax('GET', 'productos/internos/' + this.productoId, {}, function (response) {
                if (response.status) {
                    self.producto = (typeof response.data[0] !== 'undefined' ? response.data[0] : {});

                    if (typeof response.data[0] !== 'undefined' && typeof response.data[0].userVars !== 'undefined' && response.data[0].userVars) {
                        self.userVars = ((typeof response.data[0].userVars !== 'undefined') ? response.data[0].userVars : {});
                    }

                    // obtengo cotización
                    if (self.cotizacionId > 0) {
                        toolbox.doAjax('GET', 'tareas/get-cotizacion/' + self.cotizacionId, {}, function (response) {
                            if (response.status) {
                                self.cotizacion = (typeof response.data !== 'undefined' ? response.data : {});
                            } else {
                                toolbox.alert('Ha ocurrido un error obteniendo la cotización', 'danger');
                            }
                        }, function (response) {
                            toolbox.alert(response.msg, 'danger');
                        })
                    }

                    if (typeof callback === 'function') callback();
                } else {
                    toolbox.alert('Ha ocurrido un error obteniendo el producto', 'danger');
                }
            }, function (response) {
                toolbox.alert(response.msg, 'danger');
            })
        },

        // Control de cotización
        iniciarCotizacion() {
            const self = this;

            toolbox.confirm('Se iniciará una cotización nueva, ¿desea continuar?', function () {
                toolbox.doAjax('POST', 'tareas/iniciar-cotizacion', {
                    productoId: self.productoId,
                }, function (response) {
                    if (response.status) {
                        self.cotizacionId = response.data.id
                        self.$router.push('/cotizar/producto/' + self.productoId + '/' + self.cotizacionId);
                        self.showWizzard = true;

                        self.getFlujo();
                    } else {
                        toolbox.alert('Ha ocurrido un error obteniendo el producto', 'danger');
                    }
                }, function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
            })
        },
        getFlujo() {
            const self = this;
            toolbox.doAjax('POST', 'tareas/calcular-paso', {
                cotizacionId: self.cotizacionId,
            }, function (response) {
                self.flujoActivo = response.data.actual;
                if (typeof response.data.next.nodoId !== 'undefined') {
                    self.flujoHasNext = true;
                } else {
                    self.flujoHasNext = false;
                }
                if (typeof response.data.prev.nodoId !== 'undefined') {
                    self.flujoHasPrev = true;
                } else {
                    self.flujoHasPrev = false;
                }
                if (typeof response.data.estado !== 'undefined') {
                    self.estadoCotizacion = response.data.estado;

                    self.getResumen();
                }
                if (typeof response.data.bit !== 'undefined') {
                    self.bitacora = response.data.bit;
                }
                if (typeof response.data.visible !== 'undefined') {
                    self.noVisible = (response.data.visible !== '' ? response.data.visible : false);
                }
                if (typeof response.data.d !== 'undefined') {
                    self.allFieldsData = (response.data.d !== '' ? response.data.d : {});

                    Object.keys(self.allFieldsData).map(function (a) {
                        if (typeof self.allFieldsData[a].campos !== 'undefined') {
                            self.allFieldsData[a].campos.forEach(function (campo) {
                                self.camposValores[campo.id] = ((campo.valor) ? campo.valor.toString() : '');
                            })
                        }
                    })
                }

                self.campoCumpleCondiciones();
                self.previewAdjunto();
            }, function (response) {
                toolbox.alert(response.msg, 'danger');
            })
        },
        getResumen() {
            const self = this;
            toolbox.doAjax('POST', 'tareas/get-resumen', {
                cotizacionId: self.cotizacionId,
            }, function (response) {
                self.resumen = response.data;
            }, function (response) {
                toolbox.alert(response.msg, 'danger');
            })
        },
        cancelarCotizacion(text) {
            const self = this;
            toolbox.confirm('Se cancelará la cotización, esta acción no se puede revertir, ¿desea continuar?', function () {

                toolbox.doAjax('POST', 'tareas/cambiar-estado', {
                    cotizacionId: self.cotizacionId,
                    estado: 'cancelada',
                }, function (response) {
                    toolbox.alert(response.msg);
                    self.loadData();
                }, function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
            })
        },
        editarUsuarioCotizacion() {
            const self = this;
            toolbox.confirm('Se cambiará el usuario asignado a esta cotización, ¿desea continuar?', function () {

                toolbox.doAjax('POST', 'tareas/cambiar-usuario', {
                    id: self.cotizacionId,
                    usuarioId: self.usuarioEditar,
                }, function (response) {
                    toolbox.alert(response.msg);
                    self.getFlujo();
                }, function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
            })
        },
        getUsers() {

            const self = this;
            toolbox.doAjax('GET', 'users/list', {},
                function (response) {
                    //self.items = response.data;
                    self.users = [];
                    Object.keys(response.data).map(function (a, b) {
                        self.users.push({
                            value: response.data[a].id,
                            label: response.data[a].name + "(" + response.data[a].email + ")",
                        })
                    })
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        continuarCotizacion(operacion, estado) {
            const self = this;
            let arrCampos = {};
            if (!estado) estado = false;

            let requeridosFail = false;
            if (typeof this.flujoActivo.formulario.secciones !== 'undefined' && typeof this.flujoActivo.formulario.secciones[this.currentTabIndex] !== 'undefined') {
                this.flujoActivo.formulario.secciones[this.currentTabIndex].campos.forEach(function (a) {

                    arrCampos[a.id] = a.valor;

                    if (a.tipoCampo === 'signature') {

                        if (typeof self.$refs['signature_' + a.id] !== 'undefined' && typeof self.$refs['signature_' + a.id][0] !== 'undefined' && typeof self.$refs['signature_' + a.id][0].undoSignature === 'function') {
                            const { isEmpty, data } = self.$refs['signature_' + a.id][0].saveSignature();
                            if (!isEmpty) {
                                arrCampos[a.id] = data;
                            }
                        }
                    }

                    const valoresCampo = [];
                    let selected = false;
                    if (a.tipoCampo === 'checkbox') {
                        arrCampos[a.id] = [];
                        if (typeof a.catalogoId !== "undefined") {
                            a.catalogoId.items.forEach(function (campo, keycampo) {
                                if (typeof campo.selected !== 'undefined' && campo.selected) {
                                    valoresCampo.push(campo[a.catalogoValue]);
                                    selected = true;
                                }
                            })
                        }

                        if (selected) {
                            a.valor = true;
                            arrCampos[a.id] = valoresCampo;
                        }
                        else {
                            a.valor = '';
                        }
                    }

                    // console.log(a);
                    if (a.requerido && (a.valor === '' || !a.valor)) {
                        if (typeof self.camposCumplidores[a.id] !== 'undefined' && self.camposCumplidores[a.id]) {
                            a.requeridoError = true;
                            requeridosFail = true;
                        }
                    } else {
                        a.requeridoError = false;
                    }
                });
            }

            if (operacion === 'next') {

                if (requeridosFail) {
                    toolbox.alert('Debe llenar todos los campos requeridos', 'danger');
                    return false;
                }

                if ((this.flujoActivo.formulario.secciones.length-1) > this.currentTabIndex) {
                    const tmpNext = this.currentTabIndex + 1;
                    if (this.cambiarSeccion(tmpNext)) {
                        this.campoCumpleCondiciones();
                        return false;
                    }

                }
            }

            if (operacion === 'prev') {

                this.currentTabIndex = 0;
            }

            const cambiarEstado = function () {
                toolbox.doAjax('POST', 'tareas/cambiar-estado', {
                    cotizacionId: self.cotizacionId,
                    paso: operacion,
                    seccionKey: self.currentTabIndex,
                    campos: arrCampos,
                    estado: estado,
                }, function (response) {
                    toolbox.alert(response.msg);
                    window.scrollTo({top: 0, behavior: 'smooth'});
                    self.getFlujo();
                }, function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
            }

            if (estado === 'finalizada') {
                toolbox.confirm('Si finaliza la cotización no podrá volver a editarla', function () {
                    cambiarEstado();
                })
            } else {
                cambiarEstado();
            }


        },
        downloadPDF() {
            const self = this;
            toolbox.doAjax('POST', 'flujos/pdf', {
                cotizacionId: self.cotizacionId,
            }, function (response) {
                toolbox.alert(response.msg, 'info');
                self.pdfPreview = response.data.pdf

                // Crear un enlace temporal para descargar el archivo
                const link = document.createElement('a');
                link.href = 'data:application/pdf;base64,' + self.pdfPreview; // Agregar el contenido base64 en el href
                link.download = 'archivo.pdf'; // Nombre del archivo que se descargará
                link.target = '_blank'; // Abrir en una nueva pestaña
                document.body.appendChild(link);

                // Simular un clic en el enlace para iniciar la descarga
                link.click();

                // Eliminar el enlace temporal
                document.body.removeChild(link);
            });
        },
        execRegex(regex, valor) {
            const arr = regex.exec(valor);
            if (arr) {
                return arr;
            }
            else {
                return false
            }
        },
        calcularCampos(camposCalcular) {

            toolbox.doAjax('POST', 'tareas/calcular-campos', {
                campos: camposCalcular
            }, function (response) {

            }, function (response) {
                toolbox.alert(response.msg, 'danger');
            })
        },
        campoCumpleCondiciones() {
            //console.log('asdfasdf');
            const self = this;
            const fullCampos = {};

            if (typeof this.flujoActivo.formulario.secciones !== 'undefined' && typeof this.flujoActivo.formulario.secciones[this.currentTabIndex] !== 'undefined') {

                this.flujoActivo.formulario.secciones.forEach(function (seccion, seccionKey) {

                    if (typeof fullCampos[seccionKey] === 'undefined') fullCampos[seccionKey] = {};

                    if (seccion.condiciones.length > 0 && !seccion.condiciones[0].campoId) {
                        self.flujoActivo.formulario.secciones[seccionKey].show = true;
                    }

                    self.flujoActivo.formulario.secciones[seccionKey].campos.forEach(function (a, b) {
                        self.camposValores[a.id] = ((a.valor) ? a.valor.toString() : '');
                    })

                    self.flujoActivo.formulario.secciones[seccionKey].campos.forEach(function (a, b) {

                        self.camposCumplidores[a.id] = true;
                        //console.log(a);
                        //console.log(self.camposValores);

                        if (typeof a.valorCalculado !== 'undefined' && a.valorCalculado) {

                            let valorCalculadoOpt = (a.valorCalculado !== '') ? a.valorCalculado : '';
                            Object.keys(self.camposValores).map(function (key) {
                                valorCalculadoOpt = valorCalculadoOpt.replaceAll("{{"+key+"}}", self.camposValores[key]);
                            })

                            if (typeof self.userVars !== 'undefined') {
                                self.userVars.forEach(function (uvar) {
                                    valorCalculadoOpt = valorCalculadoOpt.replaceAll("{{"+uvar.nombre+"}}", uvar.valor);
                                })
                            }

                            // Funciones
                            let hasDate = false;
                            let valorCalculado = '';

                            // FN.EDAD
                            let regex = self.execRegex(/FN.EDAD\((.*)\)/g, valorCalculadoOpt);
                            if (regex) {
                                let data = (regex[1]) ? regex[1] : '';
                                data = data.replaceAll('"', '').replaceAll("'", '').split(',');
                                const date = (data[0]) ? data[0] : false;
                                const formatInput = (data[1]) ? data[1] : false;
                                let value = '';
                                if (date && formatInput) {
                                    value = dayjs(date, formatInput);
                                    value = dayjs().diff(value, 'year', false)
                                }
                                valorCalculadoOpt = valorCalculadoOpt.replace(/FN.EDAD\((.*)\)/g, value);
                            }

                            // FN.SUMARDIAS
                            regex = self.execRegex(/FN.SUMARDIAS\((.*)\)/g, valorCalculadoOpt);
                            if (regex) {
                                let data = (regex[1]) ? regex[1] : '';
                                data = data.replaceAll('"', '').replaceAll("'", '').split(',');
                                const date = (data[0]) ? data[0] : false;
                                const formatInput = (data[1]) ? data[1] : false;
                                const formatOutput = (data[2]) ? data[2] : 0;
                                const dias = (data[3]) ? data[3] : 0;
                                let value = '';
                                if (date && formatInput && dias) {
                                    value = dayjs(date, formatInput).add(parseInt(dias), 'day');
                                    value = value.format(formatOutput);
                                }
                                valorCalculadoOpt = valorCalculadoOpt.replace(/FN.SUMARDIAS\((.*)\)/g, value);
                                hasDate = true;
                            }

                            valorCalculado = valorCalculadoOpt;

                            //console.log(valorCalculadoOpt);
                            if (!hasDate && valorCalculadoOpt !== '') {
                                try {
                                    valorCalculado = new Function('return ' + valorCalculadoOpt)();
                                }
                                catch (e) {
                                    console.log('Error al realizar campo calculado' + e);
                                }
                            }

                            // reemplazo el valor
                            self.flujoActivo.formulario.secciones[self.currentTabIndex].campos[b].valor = valorCalculado;
                            self.camposValores[a.id] = valorCalculado.toString();
                        }

                        fullCampos[seccionKey][a.id] = self.camposValores[a.id];

                        if (typeof a.dependOn !== 'undefined') {
                            if (typeof a.dependOn[0] !== 'undefined' && typeof a.dependOn[0].campoId !== 'undefined' && a.dependOn[0].campoId) {
                                self.camposCumplidores[a.id] = false;
                            }
                        }
                    })

                    // JS POST
                    self.flujoActivo.formulario.secciones[seccionKey].campos.forEach(function (a, b) {
                        // jsPost
                        let jsTmp = '';
                        if (typeof a.jsPost !== 'undefined' && a.jsPost && a.jsPost !== '') {
                            jsTmp = a.jsPost;
                            Object.keys(self.camposValores).map(function (key) {
                                jsTmp = jsTmp.replaceAll("{{"+key+"}}", self.camposValores[key]);
                            })
                            if (jsTmp !== '') {
                                try {
                                    const tmpJsRes = new Function('return (function(){'+jsTmp+'})();')();
                                    if (!tmpJsRes) {
                                        toolbox.alert("Error en validación de " + a.nombre, 'danger');
                                    }
                                }
                                catch (e) {
                                    console.log('Error al evaluar JS post llenado' + e);
                                }
                            }
                        }
                    })

                    self.flujoActivo.formulario.secciones[seccionKey].campos.forEach(function (a, b) {
                        if (typeof a.dependOn !== 'undefined') {

                            a.dependOn.forEach(function (item) {
                                if (item.campoId) {

                                    if (typeof self.camposValores[item.campoId] !== 'undefined') {

                                        if (item.campoIs === '=') {
                                            self.camposCumplidores[a.id] = (self.camposValores[item.campoId] === item.campoValue);
                                        } else if (item.campoIs === '<') {
                                            self.camposCumplidores[a.id] = (self.camposValores[item.campoId] < item.campoValue);
                                        } else if (item.campoIs === '<=') {
                                            self.camposCumplidores[a.id] = (self.camposValores[item.campoId] <= item.campoValue);
                                        } else if (item.campoIs === '>') {
                                            self.camposCumplidores[a.id] = (self.camposValores[item.campoId] > item.campoValue);
                                        } else if (item.campoIs === '>=') {
                                            self.camposCumplidores[a.id] = (self.camposValores[item.campoId] >= item.campoValue);
                                        } else if (item.campoIs === '<>') {
                                            self.camposCumplidores[a.id] = (self.camposValores[item.campoId] !== item.campoValue);
                                        } else if (item.campoIs === 'like') {
                                            self.camposCumplidores[a.id] = (self.camposValores[item.campoId].toLowerCase().includes(item.campoValue.toLowerCase()));
                                        }
                                    }
                                }
                            })
                        }
                    })
                })

                // console.log(self.camposValores);

                // validacion de secciones
                this.flujoActivo.formulario.secciones.forEach(function (seccion, seccionKey) {

                    if (typeof seccion.condiciones != 'undefined' && seccion.condiciones.length > 0) {
                        seccion.condiciones.forEach(function (item) {

                            if (item.campoId) {

                                if (typeof self.camposValores[item.campoId] !== 'undefined') {

                                    const tmpValue = self.camposValores[item.campoId].toString();
                                    item.value = item.value.toString();

                                    if (item.campoIs === '=') {
                                        self.flujoActivo.formulario.secciones[seccionKey].show = (tmpValue === item.value);
                                    } else if (item.campoIs === '<') {
                                        self.flujoActivo.formulario.secciones[seccionKey].show = (tmpValue < item.value);
                                    } else if (item.campoIs === '<=') {
                                        self.flujoActivo.formulario.secciones[seccionKey].show = (tmpValue <= item.value);
                                    } else if (item.campoIs === '>') {
                                        self.flujoActivo.formulario.secciones[seccionKey].show = (tmpValue > item.value);
                                    } else if (item.campoIs === '>=') {
                                        self.flujoActivo.formulario.secciones[seccionKey].show = (tmpValue >= item.value);
                                    } else if (item.campoIs === '<>') {
                                        self.flujoActivo.formulario.secciones[seccionKey].show = (tmpValue !== item.value);
                                    } else if (item.campoIs === 'like') {
                                        self.flujoActivo.formulario.secciones[seccionKey].show = (tmpValue.toLowerCase().includes(item.value.toLowerCase()));
                                    }
                                    else {
                                        self.flujoActivo.formulario.secciones[seccionKey].show = false;
                                    }
                                }
                                else {
                                    self.flujoActivo.formulario.secciones[seccionKey].show = false;
                                }
                            }
                        })
                    }
                    else {
                        self.flujoActivo.formulario.secciones[seccionKey].show = true;
                    }
                })
            }
        },
        cambiarSeccion(seccion) {

            const nextSection = (typeof this.flujoActivo.formulario.secciones[seccion] !== 'undefined') ? this.flujoActivo.formulario.secciones[seccion] : false;

            if (!nextSection) return false;

            // Filtrar las secciones que cumplen con las condiciones
            this.campoCumpleCondiciones();

            // si está visible
            if (nextSection.show) {
                this.currentTabIndex = seccion;
                return true;
            }
            else {
                return false;
            }
        },

        // archivos adjuntos
        handleUpload(file, campoId, load, error, progress) {
            if (file) {

                const self = this;

                // creo la data
                const formData = new FormData();
                formData.append('file', file);
                formData.append('seccionKey', this.currentTabIndex);
                formData.append('cotizacionId', self.cotizacionId);
                formData.append('campoId', campoId);

                toolbox.doAjax('FILE', 'tareas/upload-file', formData,
                    function (response) {

                        if (response.status) {
                            self.flujoActivo.formulario.secciones[self.currentTabIndex].campos.forEach(function (a, b) {
                                if (campoId === a.id) {
                                    self.flujoActivo.formulario.secciones[self.currentTabIndex].campos[b].valor = true;
                                }
                            })
                        }
                        load();
                        self.previewAdjunto();
                    },
                    function (response) {
                        error('Error en subida de archivo');
                        toolbox.alert(response.msg, 'danger');
                    })
            } else {
                // Indicar que no se ha seleccionado ningún archivo
                error('No se ha seleccionado ningún archivo');
            }
        },
        previewAdjunto() {

            const self = this;
            toolbox.doAjax('POST', 'tareas/file-get-preview', {
                cotizacionId: self.cotizacionId,
                seccionKey: self.currentTabIndex,
            }, function (response) {

                self.previewFiles = response.data;

                //toolbox.alert(response.msg, 'success');

            }, function (response) {
                toolbox.alert(response.msg, 'danger');
            })
        },
        openPreview(file) {
            if (file.type === 'image') {
                this.showImagePreview = true;
                this.imagePreviewTmp = file;
            }
            else {
                window.open(file.url);
            }
        },

        // Ver progresión
        verProgresion() {

            const self = this;
            toolbox.doAjax('POST', 'tareas/get-progression', {
                cotizacionId: self.cotizacionId,
            }, function (response) {
                self.showProgresion = true;
                self.progresion = response.data;
            }, function (response) {
                toolbox.alert(response.msg, 'danger');
            })
        },
        resetSignature(ref, reset, currentTabIndex, keyInput) {
            if (!reset) reset = false;
            if (typeof this.$refs[ref] !== 'undefined' && typeof this.$refs[ref][0] !== 'undefined' && typeof this.$refs[ref][0].undoSignature === 'function') {
                this.$refs[ref][0].undoSignature();
                if (reset) {
                    this.flujoActivo.formulario.secciones[currentTabIndex].campos[keyInput].valor = '';
                }
            }
        },







        todasLasPropiedadesNoSonNulas(obj, propiedades) {
            return propiedades.every(prop => obj[prop] !== null);
        },
        onChangeCurrentTab(index) {
            this.currentTabIndex = index;
        },
        onTabBeforeChange() {
            if (this.currentTabIndex === 0) {
                console.log('First Tab');
            }
        },
        extractFields() {
            // Obtiene todos los campos de todas las secciones y formularios del objeto this.productoSelected.flujo.nodes
            const camposFiltrados = Object.values(this.producto.flujo.nodes)
                .flatMap(node => {
                    if (node.formulario && node.formulario.secciones) {
                        return Object.values(node.formulario.secciones)
                            .flatMap(seccion => Object.values(seccion.campos));
                    } else {
                        return [];
                    }
                })
                .filter(campo => campo.id && campo.nombre)
                .map(campo => ({id: campo.id, label: campo.nombre, value: campo.valor}));

            return camposFiltrados;
        },
        reemplazarTokensEnCotizacion(stringReemplazo, prefix = '::', sufix = '::') {
            if (stringReemplazo) {
                const valores = this.extractFields();
                valores.forEach((valueObj) => {
                    const token = `${prefix}${valueObj.id}${sufix}`;
                    const valor = valueObj.value;

                    stringReemplazo = stringReemplazo.replaceAll(token, valor);
                });
                return stringReemplazo;
            }
        },
        extractConfigProcesoSalida(salidaConfig, respuesta) {
            console.log(salidaConfig);
            const buscarValorEnRespuesta = (respuesta, variableExterna) => {
                if (respuesta.hasOwnProperty(variableExterna)) {
                    return respuesta[variableExterna];
                }

                for (const key in respuesta) {
                    if (respuesta.hasOwnProperty(key) && typeof respuesta[key] === "object") {
                        const valorEncontrado = buscarValorEnRespuesta(respuesta[key], variableExterna);
                        if (valorEncontrado !== undefined) {
                            return valorEncontrado;
                        }
                    }
                }

                return undefined;
            };

            salidaConfig.forEach((config) => {
                const campoEncontrado = this.findCampoById(config.nombreCampo);


                const valorVariableExterna = buscarValorEnRespuesta(respuesta, config.variableExterna);
                /// console.log(respuesta);

                if (campoEncontrado && valorVariableExterna !== undefined) {
                    campoEncontrado.valor = valorVariableExterna;
                }
            });

        },
        findCampoById(campoId) {
            const nodos = this.producto.flujo.nodes;
            for (const nodo of nodos) {
                if (nodo.formulario) {
                    const secciones = Object.values(nodo.formulario.secciones);
                    for (const seccion of secciones) {
                        const campos = Object.values(seccion.campos);
                        const campoEncontrado = campos.find(campo => campo.id === campoId);
                        if (campoEncontrado) {
                            return campoEncontrado;
                        }
                    }
                }
            }
            return ''; // Si no se encuentra el campo, retorna vacío
        },
        edgesSalidaTrue() {
            this.salidasSiguientes = [];
            this.procesosSiguientes = [];
            this.formulariosSiguientes = [];
            this.condicionesSiguientes = [];
            const edgesDestino = this.edgesActuales.filter(edge => edge.sourceHandle === "salidaTrue");
            //const productoActual = this.producto;
            edgesDestino.forEach((valueObj) => {
                const nodo = this.nodosSiguientes.filter(node => node.id === valueObj.target);
                const salidasSiguientesT = nodo[0];
                const procesoValido = this.todasLasPropiedadesNoSonNulas(salidasSiguientesT.procesos, ["url", "type", "method"])


                if (salidasSiguientesT) {
                    if (salidasSiguientesT.formulario.secciones.length > 0) {
                        this.formulariosSiguientes.push(salidasSiguientesT);
                    }
                    if (salidasSiguientesT.label === 'Proceso') {
                        this.procesosSiguientes.push(salidasSiguientesT);
                    }
                    if (salidasSiguientesT.type === 'output') {
                        this.salidasSiguientes.push(salidasSiguientesT);
                    }
                    if (salidasSiguientesT.type === 'rombo') {
                        this.condicionesSiguientes.push(salidasSiguientesT);
                    }
                    this.nodosSiguientes.push(salidasSiguientesT);
                }
            });
        },
        edgesSalidaFalse() {
            this.salidasSiguientes = [];
            this.procesosSiguientes = [];
            this.formulariosSiguientes = [];
            this.condicionesSiguientes = [];
            const edgesDestino = this.edgesActuales.filter(edge => edge.sourceHandle === "salidaFalse");
            const productoActual = this.producto;
            edgesDestino.forEach((valueObj) => {
                const nodo = productoActual.flujo.nodes.filter(node => node.id === valueObj.target);
                const salidasSiguientesT = nodo[0];
                const procesoValido = this.todasLasPropiedadesNoSonNulas(salidasSiguientesT.procesos, ["url", "type", "method"])


                if (salidasSiguientesT) {
                    if (salidasSiguientesT.formulario.secciones.length > 0) {
                        this.formulariosSiguientes.push(salidasSiguientesT);
                    }
                    if (salidasSiguientesT.label === 'Proceso') {
                        this.procesosSiguientes.push(salidasSiguientesT);
                    }
                    if (salidasSiguientesT.type === 'output') {
                        this.salidasSiguientes.push(salidasSiguientesT);
                    }
                    if (salidasSiguientesT.type === 'rombo') {
                        this.condicionesSiguientes.push(salidasSiguientesT);
                    }
                    this.nodosSiguientes.push(salidasSiguientesT);
                }
            });
        },
        nodoCumpleCondiciones(item) {
            return this.cumpleCondiciones(item.decisiones)
            // Filtrar las secciones que cumplen con las condiciones
        },
        filterInputNodes(nodeId) {
            if (nodeId !== false) {
                //console.log(nodeId);
                //REINICIO EL SIGUIENTE FORM
                this.nodosSiguientes = [];
                this.salidasSiguientes = [];
                this.procesosSiguientes = [];
                this.formulariosSiguientes = [];
                this.condicionesSiguientes = [];
                if (typeof (this.productos[0]) !== 'undefined') {
                    this.producto = this.productos[0];
                    this.flujoSelected = this.productos[0].flujo.nodes.find(node => node.id === nodeId);
                }


                this.filterSecciones();
                this.calculateNex(this.flujoSelected.id);
            } else {
                //Solo puede haber un inicio
                if (typeof (this.productos[0]) !== 'undefined') {
                    this.producto = this.productos[0];
                    this.flujoSelected = this.productos[0].flujo.nodes.find(node => node.type === "input");
                }

                this.filterSecciones();
                this.calculateNex(this.flujoSelected.id);
            }

        },
        calculateNex(nodeId) {

            const productoActual = this.producto;
            if (productoActual.flujo) {

                const edgesConOrigen = productoActual.flujo.edges.filter(edge => edge.source === nodeId);
                const edgesDestino = edgesConOrigen.filter(source => source.target === source.target);
                this.edgesActuales = edgesDestino;

                // todas las lineas destinos
                edgesDestino.forEach((valueObj) => {
                    const nodo = productoActual.flujo.nodes.filter(node => node.id === valueObj.target);
                    const salidasSiguientesT = nodo[0];
                    const procesoValido = this.todasLasPropiedadesNoSonNulas(salidasSiguientesT.procesos, ["url", "type", "method"])


                    if (salidasSiguientesT) {
                        if (salidasSiguientesT.formulario.secciones.length > 0) {
                            this.formulariosSiguientes.push(salidasSiguientesT);
                        }
                        if (salidasSiguientesT.label === 'Proceso') {
                            this.procesosSiguientes.push(salidasSiguientesT);
                        }
                        if (salidasSiguientesT.type === 'output') {
                            this.salidasSiguientes.push(salidasSiguientesT);
                        }
                        if (salidasSiguientesT.type === 'rombo') {
                            this.condicionesSiguientes.push(salidasSiguientesT);
                        }
                        this.nodosSiguientes.push(salidasSiguientesT);
                    }
                });
            }

        },
        wizardCompleted() {
            const self = this;

            if (self.procesosSiguientes.length > 0) {
                self.procesarProcesos();
            }
            if (self.condicionesSiguientes.length > 0) {
                self.procesarCondiciones();
            }
            if (self.salidasSiguientes.length > 0) {
                self.procesarSalidas()
            }
            if (self.formulariosSiguientes.length > 0) {
                self.procesarFormularios()
            }
        },
        procesarSalidas() {
            const self = this;
            if (self.salidasSiguientes.length > 0) {
                self.salidasSiguientes.forEach((salida) => {
                    const dataToView = self.reemplazarTokensEnCotizacion(salida.salidas, '::', '::');
                    self.salidaActualHTML = dataToView;
                    self.showSalidas = true;
                    if (salida.salidaIsEmail) {
                        const asunto = self.reemplazarTokensEnCotizacion(salida.procesoEmail.asunto, '{{', '}}');
                        const destino = self.reemplazarTokensEnCotizacion(salida.procesoEmail.destino, '{{', '}}');
                        const body = self.reemplazarTokensEnCotizacion(salida.procesoEmail.salidasEmail, '{{', '}}');

                        const destinoString = destino.replace(/\n/g, '').replace(/\s+/g, ' ');

                        toolbox.doAjax('POST', 'flujos/email', {
                            destino: destinoString,
                            dominio: salida.procesoEmail.mailgun.domain,
                            origen: salida.procesoEmail.mailgun.from,
                            apikey: salida.procesoEmail.mailgun.apiKey,
                            copias: salida.procesoEmail.copia,
                            asunto: asunto,
                            body: body
                        }, function (response) {
                            self.logHistory.push({
                                'type': 'salida',
                                'res': response
                            });
                            toolbox.alert(response.msg, 'info');
                        });
                    }
                    if (salida.salidaIsPDF) {

                        toolbox.doAjax('POST', 'flujos/pdf', {
                            nombre: '',
                            body: dataToView
                        }, function (response) {
                            self.logHistory.push({
                                'type': 'salida',
                                'res': response
                            });
                            toolbox.alert(response.msg, 'info');
                            self.pdfPreview = response.data.pdf
                        });

                    }


                })
            }
        },
        procesarProcesos() {
            const self = this;
            if (self.procesosSiguientes.length > 0) {
                self.procesosSiguientes.forEach((valueObj) => {
                    toolbox.doAjax('POST', 'flujos/cotizar/producto', {
                            nodoId: self.flujoSelected.id,
                            methodo: valueObj.procesos[0].method,
                            header: valueObj.procesos[0].header,
                            url: valueObj.procesos[0].url,
                            productoId: this.productoIdSelected,
                            tipoRespuesta: (valueObj.procesos[0].tipoRecibido) ? 'xml' : 'json',
                            dataToSend: valueObj.procesos[0].entrada,
                            config: valueObj.procesos[0].configuracionS,
                            flujoId: self.producto.flujoId,
                            valores: this.extractFields(),
                            salidaReemplazar: valueObj.procesos[0].salida
                        },
                        function (response) {
                            self.logHistory.push({
                                'type': 'proceso',
                                'res': response
                            });
                            //Reemplazo mis variables por mi proceso.
                            if (response.data.recibidoProcesado) {
                                self.extractConfigProcesoSalida(valueObj.procesos[0].salida, response.data.recibidoProcesado)
                            } else {
                                toolbox.alert('El servicio no está devolviendo ningún dato', 'danger');
                            }

                            //Llamo a la siguiente salida
                            self.calculateNex(valueObj.id)

                            if (self.condicionesSiguientes.length > 0) {
                                self.procesarCondiciones();
                                //los reinicio
                                self.condicionesSiguientes = [];
                            }
                            if (self.salidasSiguientes.length > 0) {
                                self.procesarSalidas()
                                //los reinicio
                                self.salidasSiguientes = [];
                            }

                        }, function (data) {
                            self.logHistory.push({
                                'type': 'proceso',
                                'res': data
                            });
                            toolbox.alert(data.msg);
                        })
                });
            }

        },
        procesarFormularios() {
            const self = this;
            //console.log(self.formulariosSiguientes);
            self.pdfPreview = false;
            if (self.formulariosSiguientes.length > 0) {
                self.formulariosSiguientes.forEach((valueObj) => {
                    self.filterInputNodes(valueObj.id)
                });
            }

        },
        procesarCondiciones() {
            const self = this;
            if (self.condicionesSiguientes.length > 0) {
                self.condicionesSiguientes.forEach((valueObj) => {
                    //Calulo los Siguientes.
                    self.calculateNex(valueObj.id)
                    //Separo los verdaderos
                    if (this.nodoCumpleCondiciones(valueObj)) {
                        self.edgesSalidaTrue()
                    } else {
                        self.edgesSalidaFalse()
                    }


                    if (self.salidasSiguientes.length > 0) {
                        self.procesarSalidas()
                        //los reinicio
                        self.salidasSiguientes = [];
                    }

                });
            }
        },
        generarEntradas() {

        },
        goNext() {
            this.currentTabIndex++;
        },
        goBack() {
            this.showSalidas = false;
            this.currentTabIndex--;
        },

        filterSecciones() {
            const self = this;
            if (self.flujoSelected) {
                const formulario = self.flujoSelected.formulario;

                if (formulario && formulario.secciones) {
                    self.seccionRaw = formulario.secciones.map((seccion, index) => {
                        //console.log(seccion);
                        return seccion;
                    });
                    //console.log( self.seccionRaw);

                }
            }

        },
        obtenerItemsPorCatalogo(nombreCatalogo) {

            // console.log(this.producto)
            const catalogo = this.producto.extraData.planes.find(plan => plan.nombreCatalogo === nombreCatalogo);

            if (catalogo) {
                return catalogo.items;
            } else {
                return [];
            }
        },
        calcularEdad(fechaNacimiento) {
            const fechaActual = new Date();
            const [anio, mes, dia] = fechaNacimiento.split('/');
            const fechaNac = new Date(parseInt(anio), parseInt(mes) - 1, parseInt(dia));

            let edad = fechaActual.getFullYear() - fechaNac.getFullYear();
            const mesActual = fechaActual.getMonth();
            const mesNac = fechaNac.getMonth();

            if (mesActual < mesNac || (mesActual === mesNac && fechaActual.getDate() < fechaNac.getDate())) {
                edad--;
            }

            return edad;
        },
        getPlanes() {
            const self = this;
            if (self.selectedProductModal.producto.codigoInterno) {
                toolbox.doAjax('GET', 'planes?plan=' + self.selectedProductModal.producto.codigoInterno, {}, function (response) {
                    // console.log(response);
                    self.listadoPlan = response.data;
                    $('#findClient').modal('hide');
                }, function (response) {
                    alert(response.msg);
                });
            }
        },
        truncarDescripcion(descripcion, limite) {
            if (!descripcion) return '';
            const palabras = descripcion.split(' ');
            const descripcionCortada = palabras.slice(0, limite).join(' ');
            return descripcionCortada;
        },
        cumpleCondiciones(condiciones) {
            // Verifica si todas las condiciones se cumplen
            return condiciones.every((condition) => this.cumpleCondicion(condition));
        },
        buscarValorEnCamposRecursivo(objeto, valorBuscado) {
            if (Array.isArray(objeto)) {
                for (const item of objeto) {
                    const resultado = this.buscarValorEnCamposRecursivo(item, valorBuscado);
                    if (resultado !== undefined) {
                        return resultado;
                    }
                }
                return undefined;
            } else if (typeof objeto === "object" && objeto !== null) {
                if (objeto.id === valorBuscado) {
                    return objeto;
                }

                for (const clave in objeto) {
                    const resultado = this.buscarValorEnCamposRecursivo(objeto[clave], valorBuscado);
                    if (resultado !== undefined) {
                        return resultado;
                    }
                }
                return undefined;
            }

            return undefined;
        },
        cumpleCondicion(condition) {
            if (!condition.campoId && condition.value === null && condition.is === null) {
                // Si falta algún campo, la condición no se cumple
                return true;
            }

            // Encuentra el campo correspondiente en la lista de campos (seccionesRaw)
            const campo = this.buscarValorEnCamposRecursivo(this.flujoSelected, condition.campoId);

            if (!campo) {
                // Si el campo no se encuentra, la condición no se cumple
                return false;
            }


            // Compara el valor del campo con la condición utilizando el operador "is"
            switch (condition.campoIs) {
                case ">":
                    return campo.valor > condition.value;
                case "<":
                    return campo.valor < condition.value;
                case "=":
                    return campo.valor === condition.value;
                case "=>":
                    return campo.valor >= condition.value;
                // Agrega otros casos según los operadores que desees admitir
                default:
                    return false;
            }
        }
    }
}
</script>
