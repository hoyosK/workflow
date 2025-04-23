<script setup>
import {VueFlow, useVueFlow, Position, Handle, MarkerType} from '@vue-flow/core'
import {Background} from '@vue-flow/background';
import {MiniMap} from '@vue-flow/minimap';
import {Controls} from '@vue-flow/controls';
import RomboNode from './customNode.vue';
import {nextTick, watch, markRaw} from 'vue'
import {ref, defineExpose} from 'vue'
import Select from "@/views/forms/Select.vue";
import toolbox from "@/toolbox";
import {useRouter, useRoute} from 'vue-router';
import {onMounted} from 'vue'
import ExcelJS from 'exceljs';
import Button from "@/views/forms/form_elements/FormElementButton.vue";

let route = useRoute()
let signaturePad = ref();
let flujosDisponibles = ref({});
let flujoName = ref('');
let flujoId = ref(0);
let flujoIsActive = ref(0);
let modoPruebas = ref(0);
let activo = ref(0);
let showNodeConfig = ref(false);
let flujoSelected = ref({});
let nodeSelection = ref({});
let flujoDescribir = ref({});
let dragObject = {};
let id = ref(0);
let allFields = ref({});
let draggedSeccion = ref(null);
let draggedCampo= ref(null);

function toDataURL() {
    const dataURL = signaturePad.value.toDataURL();
}

function getId() {
    return 'nodo_' + new Date().valueOf();
}

function handleFileUpload(file, load, error, progress, abort) {
    if (file) {
        const reader = new FileReader();
        console.log(reader);
        reader.onload = (e) => {
            const data = new Uint8Array(e.target.result);

            const workbook = new ExcelJS.Workbook();
            workbook.xlsx.load(data)
                .then(() => {
                    const worksheet = workbook.getWorksheet(1);

                    const jsonData = [];

                    worksheet.eachRow((row, rowNumber) => {
                        if (rowNumber > 1) {
                            const rowData = {
                                'PAGINA': row.getCell(1).value,
                                'ID-GRUPO': row.getCell(2).value,
                                'NOMBRE-GRUPO': row.getCell(3).value,
                                'INDICADOR GRUPO DINAMICO': row.getCell(4).value,
                                'NOMBRE-CAMPO': row.getCell(5).value,
                                'TEXTO-CAMPOS': row.getCell(6).value,
                                'CODIGO A USAR': row.getCell(7).value,
                                'layoutSizePc': row.getCell(8).value,
                                'layoutSizeMobile': row.getCell(9).value,
                                'cssClass': row.getCell(10).value,
                                'requerido': row.getCell(11).value,
                                'readonly': row.getCell(12).value,
                                'deshabilitado': row.getCell(13).value,
                                'visible': row.getCell(14).value,
                                'activo': row.getCell(15).value,
                                'mascara': row.getCell(16).value,
                                'tokenMask': row.getCell(17).value,
                                'contieneMask': row.getCell(18).value,
                                //'contienePadre': row.getCell(19).value,
                                'padre': row.getCell(20).value,
                                'catalogoValue': row.getCell(21).value,
                                'catalogoLabel': row.getCell(22).value,
                                'longitudMin': row.getCell(23).value,
                                'longitudMax': row.getCell(24).value,
                                'grupos_assign': row.getCell(25).value,
                                'roles_assign': row.getCell(26).value,
                            };

                            jsonData.push(rowData);
                        }
                    });

                    jsonData.forEach((row) => {
                        // Asignar valores por defecto para nombreGrupo, nombreCampo y textoCampos
                        const nombreGrupo = row['NOMBRE-GRUPO'] || '';
                        const nombreCampo = row['NOMBRE-CAMPO'] || '';
                        const textoCampos = row['TEXTO-CAMPOS'] || '';

                        // Resto del código para crear el objeto 'campo' y la sección 'seccion' ...
                        const campo = {
                            id: nombreCampo,
                            activo: (row['activo']) ? true : false,
                            nombre: textoCampos,
                            mascara: row['mascara'] || '',
                            tokenMask: row['tokenMask'] || '',
                            outputMask: row['outputMask'] || '',
                            contieneMask: row['contieneMask'] || false,
                            valor: null,
                            options: [{label: null, value: null}],
                            visible: !!(row['visible']),
                            readOnly: !!(row['readOnly']),
                            group: row['group'] || '',
                            cssClass: row['cssClass'] || null,
                            dependOn: [{campoId: null, campoIs: null, campoValue: null, campoVar: null}],
                            requerido: row['requerido'] || 0,
                            readonly: row['readonly'] || 0,
                            forceReplaceDef: !!(row['forceReplaceDef']),
                            tipoCampo: row['tipoCampo'] || null,
                            mime: row['mime'] || null,
                            filePath: row['filePath'] || null,
                            grupos_assign: row['grupos_assign'] || [],
                            roles_assign: row['roles_assign'] || [],
                            longitudMax: row['longitudMax'] || 0,
                            longitudMin: row['longitudMin'] || 0,
                            layoutSizePc: row['layoutSizePc'] || 4,
                            //contienePadre: row['contienePadre'] || false,
                            padre: row['padre'] || false,
                            valorCalculado: row['valorCalculado'] || '',
                            ocrConfig: row['ocrConfig'] || '',
                            jsPost: row['jsPost'] || '',
                            catalogoValue: row['catalogoValue'] || '',
                            catalogoLabel: row['catalogoLabel'] || '',
                            catalogoId: '',
                            showInReports: false,
                            deshabilitado: row['deshabilitado'] || false,
                            layoutSizeMobile: row['layoutSizeMobile'] || 12,
                            proceso: {
                                serviceEndpoint: "",
                                method: "GET",
                                responseType: "json",
                                selectElementId: "",
                                optionKeyField: "",
                                optionValueField: ""
                            },
                            fixedField: row['fixedField'] || false,
                        };

                        let seccion = nodeSelection.value.formulario.secciones.find(
                            (s) => s.nombre === nombreGrupo
                        );

                        if (!seccion) {
                            seccion = {
                                condiciones: [
                                    {campoId: '', is: '', value: '', glue: 'AND'}
                                ],
                                nombre: nombreGrupo,
                                instrucciones: '',
                                campos: []
                            };

                            nodeSelection.value.formulario.secciones.push(seccion);
                        }

                        seccion.campos.push(campo);
                    });


                    // Aquí puedes hacer lo que desees con el JSON generado

                    // Indicar que la carga ha sido exitosa
                    load();
                })
                .catch((error) => {
                    console.error(error);
                    // Indicar que ha ocurrido un error en la carga
                    error('Ha ocurrido un error en la carga del archivo');
                });
        };

        reader.onerror = (e) => {
            console.error(e);
            // Indicar que ha ocurrido un error en la lectura del archivo
            error('Ha ocurrido un error en la lectura del archivo');
        };

        reader.onprogress = (e) => {
            // Calcular el progreso de la carga y actualizarlo
            if (e.lengthComputable) {
                const percentLoaded = Math.round((e.loaded / e.total) * 100);
                progress(percentLoaded);
            }
        };

        reader.readAsArrayBuffer(file);
    } else {
        // Indicar que no se ha seleccionado ningún archivo
        error('No se ha seleccionado ningún archivo');
    }
}

function handleCatalogoUpload(file, catalogoName, load, error, progress, plan) {
    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {

            if (!e.target.result) return false;

            const data = new Uint8Array(e.target.result);

            const workbook = new ExcelJS.Workbook();
            workbook.xlsx.load(data)
                .then(() => {
                    const worksheet = workbook.getWorksheet(1);

                    const keySlug = Object.keys(plan).length + 1;

                    const existingCatalogo = (typeof plan[catalogoName] !== 'undefined');
                    if (existingCatalogo) plan[catalogoName].items = [];

                    worksheet.eachRow((row, rowNumber) => {
                        if (rowNumber > 1) {
                            const rowData = {};

                            row.eachCell((cell, colNumber) => {
                                const headerCell = worksheet.getRow(1).getCell(colNumber);
                                const headerValue = String(headerCell.value).trim();

                                if (headerValue) {
                                    rowData[headerValue] = String(cell.value).trim();
                                }
                            });

                            //const existingCatalogo = plan.find((catalogo) => catalogo.nombreCatalogo === nombreCatalogo);

                            if (existingCatalogo) {
                                plan[catalogoName].items.push(rowData);
                            }
                            else {
                                const nuevoCatalogo = {
                                    slug: catalogoName,
                                    nombreCatalogo: '',
                                    items: [rowData],
                                };
                                plan[catalogoName] = nuevoCatalogo;

                                //plan.push(nuevoCatalogo);
                            }
                        }
                    });

                    // Aquí puedes hacer lo que desees con el JSON generado

                    // Indicar que la carga ha sido exitosa
                    load();
                })
                .catch((error) => {
                    console.error(error);
                    // Indicar que ha ocurrido un error en la carga
                    error('Ha ocurrido un error en la carga del archivo');
                });
        };

        reader.onerror = (e) => {
            console.error(e);
            // Indicar que ha ocurrido un error en la lectura del archivo
            error('Ha ocurrido un error en la lectura del archivo');
        };

        reader.onprogress = (e) => {
            // Calcular el progreso de la carga y actualizarlo
            if (e.lengthComputable) {
                const percentLoaded = Math.round((e.loaded / e.total) * 100);
                progress(percentLoaded);
            }
        };

        reader.readAsArrayBuffer(file);
    } else {
        // Indicar que no se ha seleccionado ningún archivo
        error('No se ha seleccionado ningún archivo');
    }
}

function obtenerItems(jsonData) {
    const dataArray = [];
    Object.keys(jsonData).map(function (a) {
        dataArray[a] = jsonData[a];
    });
    return dataArray;
}

function toggleNodeType() {
    const nodeToUpdate = nodeSelection.value;

    if (nodeToUpdate) {
        // Creo una copia del nodo seleccionado para no modificar el original directamente
        const updatedNode = JSON.parse(JSON.stringify(nodeToUpdate));
        //const updatedNode = {...nodeToUpdate};

        // Verifico el tipo actual del nodo y cámbialo según el tipo deseado.
        // Mi toggle
        if (updatedNode.type === "input") {
            updatedNode.type = "default";
            updatedNode.typeObject = "input";
            updatedNode.label = "Entradas";
        } else if (updatedNode.type === "default") {
            updatedNode.typeObject = "start";
            updatedNode.type = "input";
            updatedNode.label = "Inicio";
        }

        // Actualizo el nodo seleccionado usando el valor de la variable ref
        nodeSelection.value = updatedNode;
        // Actualizo el nodo en la lista de nodos de VueFlow
        setNodes((prevNodes) => {
            const updatedNodes = prevNodes.map((node) =>
                node.id === updatedNode.id ? updatedNode : node
            );
            return updatedNodes;
        });
        extractFields();
    }
}

function updatedName() {
    const nodeToUpdate = nodeSelection.value;
    // console.log(nodeToUpdate);

    let nodeName = '';
    if (nodeToUpdate.typeObject == 'input') {
        nodeName = 'Entrada';
    } else if (nodeToUpdate.typeObject == 'process') {
        nodeName = 'Proceso';
    } else if (nodeToUpdate.typeObject == 'output') {
        nodeName = 'Salida';
    } else if (nodeToUpdate.typeObject == 'condition') {
        nodeName = 'Condición';
    } else if (nodeToUpdate.typeObject == 'setuser') {
        nodeName = 'Usuario';
    } else if (nodeToUpdate.typeObject == 'vehiculo') {
        nodeName = 'Vehículo';
    } else if (nodeToUpdate.typeObject == 'vehiculo_comp') {
        nodeName = 'Cotización';
    } else if (nodeToUpdate.typeObject == 'pagador') {
        nodeName = 'Pago';
    }

    if (nodeToUpdate) {
        // Creo una copia del nodo seleccionado para no modificar el original directamente
        //const updatedNode = {...nodeToUpdate};
        const updatedNode = JSON.parse(JSON.stringify(nodeToUpdate));
        updatedNode.label = "<div><b>" + nodeName + "</b></div><div><small>" + updatedNode.nodoName + "</small></div>";
        nodeSelection.value = updatedNode;

        setNodes((prevNodes) => {
            const updatedNodes = prevNodes.map((node) =>
                node.id === updatedNode.id ? updatedNode : node
            );
            return updatedNodes;
        });
        extractFields();
    }
}

function onDragOver(event) {
    event.preventDefault()
    if (event.dataTransfer) {
        event.dataTransfer.dropEffect = 'move'
    }
}

function addEdgesWithArrow(connection) {
    const sourceNode = findNode(connection.source);
    let sourceNodeLabel = sourceNode ? sourceNode.nodoName : 'Origen Desconocido';

    let style = {};
    let labelBgStyle = {};
    let animated = false;
    sourceNodeLabel = '';


    if (connection.sourceHandle === 'salidaFalse') {
        style = {stroke: '#fd7a7a', color: 'white'};
        labelBgStyle = {fill: 'red', color: 'white'};
    } else if (connection.sourceHandle === 'salidaTrue') {
        style = {stroke: '#72d55b', color: 'white'};
        labelBgStyle = {fill: 'green', color: 'white'};
    }

    const edgeWithArrow = {
        ...connection,
        markerEnd: MarkerType.ArrowClosed,
        label: sourceNodeLabel,
        style: style, // Estilo para el conector (source)
        animated: false,
        strokeWidth: 4,
        //labelBgStyle: labelBgStyle,
    };

    addEdges([edgeWithArrow]);
}

function deleteEdge(edge) {
    removeEdges(edge.edge.id);
    extractFields();
}

function cloneNode() {
    const nodeToUpdate = nodeSelection.value;

    // Creo una copia profunda del nodo seleccionado para no modificar el original directamente
    const updatedNode = JSON.parse(JSON.stringify(nodeToUpdate));
    updatedNode.id = getId();

    // Actualizo el nodo seleccionado usando el valor de la variable ref
    addNodes([updatedNode]);

    // Deseleccionamos el nodo original
    nodeToUpdate.selected = false;

    // Esperamos al siguiente ciclo de actualización para asegurarnos de que el nodo clonado esté disponible en el lienzo
    nextTick(() => {
        const node = findNode(updatedNode.id);
        const stop = watch(
            () => node.dimensions,
            (dimensions) => {
                if (dimensions.width > 0 && dimensions.height > 0) {
                    node.position = {
                        x: node.position.x - dimensions.width / 2,
                        y: node.position.y - dimensions.height / 2,
                    };
                    stop();
                }
            },
            {deep: true, flush: 'post'}
        );

        // Ahora seleccionamos el nodo clonado
        nodeSelection.value = node;
        extractFields();
    });
}

function onDrop(event) {
    const type = event.dataTransfer?.getData('application/vueflow')

    const {left, top} = vueFlowRef.value.getBoundingClientRect()

    const position = project({
        x: event.clientX - left,
        y: event.clientY - top,
    })

    //console.log(type);

    const newNode = {
        id: getId(),
        type,
        position,
        nodoName: '',
        label: dragObject.name,
        typeObject: dragObject.type,
        estOut: '',
        estIo: 'e',
        cmT: '',
        gVh: 'd',
        ocr: 'd',
        ocrTpl: '',
        ocrDesc: '',
        ocrVC: '',
        priv: '',
        style: dragObject.style,
        formulario: {
            tipo: '',
            secciones: []

        },
        logicaAsig: '',
        roles_assign: [],
        grupos_assign: [],
        canales_assign: [],
        tareas_programadas: [],
        decisiones: [{glue: "AND", value: "", campoId: "", campoIs: ""}],
        decisionesL: '',
        review: [],
        salidas: '',
        salidaIsHTML: false,
        salidaIsPDF: false,
        salidaPDFId: '',
        salidaPDFGroup: '',
        salidaPDFDp: '',
        salidaPDFLabel: '',
        salidaPDFconf: {
            path: '',
            fileLabel: '',
            fileTipo: '',
            fileTipo2: '',
            fileRamo: '',
            fileProducto: '',
            jsonSend: '',
            fileFechaExp: '',
            fileReclamo: '',
            filePoliza: '',
            fileEstadoPoliza: '',
            fileNit: '',
            fileDPI: '',
            fileCIF: '',
            expNewConf: {
                label: '',
                tipo: '',
                ramo: '',
                sobreescribir: '',
                attr: [],
            },
        },
        salidaIsEmail: false,
        salidaIsWhatsapp: false,
        saltoAutomatico: false,
        procesoEmail: {
            salidasEmail: '',
            asunto: '',
            destino: '',
            attachments: '',
            autoSend: false,
            reenvio: false,
            copia: [{destino: ''}],
            mailgun: {
                apiKey: '',
                domain: '',
                from: '',
            }
        },
        procesoWhatsapp: {
            url: '',
            tpl: '',
            token: '',
            data: '',
            attachments: '',
            reenvio: false,
            autoSend: false,
        },
        procesos: [{
            entrada: '',
            salida: [],
            url: '',
            method: '',
            type: '',
            tipoRecibido: false,
            isXML: false,
            header: '',
            configuracionS: [],
            pf: []
        }],
    }

    addNodes([newNode])

    // align node position after drop, so it's centered to the mouse
    nextTick(() => {
        const node = findNode(newNode.id)
        const stop = watch(
            () => node.dimensions,
            (dimensions) => {
                if (dimensions.width > 0 && dimensions.height > 0) {
                    node.position = {
                        x: node.position.x - node.dimensions.width / 2,
                        y: node.position.y - node.dimensions.height / 2
                    }
                    stop()
                }
            },
            {deep: true, flush: 'post'},
        )
    })
}

function onDragStart(event, nodeType) {
    if (event.dataTransfer) {
        event.dataTransfer.setData('application/vueflow', nodeType)
        event.dataTransfer.effectAllowed = 'move'
    }

    const element = event.target; // Obtenemos el elemento que recibió el evento
    const name = element.getAttribute('data-title'); // Obtenemos el valor del atributo data-title
    const type = element.getAttribute('data-type'); // Obtenemos el valor del atributo data-type

    dragObject = {
        type: '',
        name: '',
        style: {}
    };
    dragObject.name = name;
    dragObject.type = type;
    if (type === 'start') {
        dragObject.style.color = '#3535f3';
        dragObject.animated = true;
    } else if (type === 'input') {
        dragObject.style.color = '#0f0f8c';
        dragObject.animated = true;
    } else if (type === 'condition') {
        dragObject.style.color = '#f68003';
        dragObject.animated = true;
    } else if (type === 'process') {
        dragObject.style.color = 'rgba(34,208,34,0.51)';
        dragObject.animated = true;
    } else if (type === 'subProcess') {
        dragObject.style.color = '#f30808';
        dragObject.animated = true;
    }
}

function eliminarNodo() {
    if (nodeSelection.value) {
        toolbox.confirm("¿Está seguro que deseas eliminar el nodo? esta acción no se puede deshacer", function () {
            removeNodes(nodeSelection.value);
            showNodeConfig.value = false;
            nodeSelection.value = {};
        })
        extractFields();
    }
}

function extractFields() {
    let connectedNodes = {};
    let arrCamposExist = {};
    let arrCampos = [];

    const objectGlobal = toObject();

    Object.keys(objectGlobal.edges).map(function (a) {
        if (objectGlobal.edges[a].source !== '' || objectGlobal.edges[a].target !== '') {
            connectedNodes[objectGlobal.edges[a].source] = true;
            connectedNodes[objectGlobal.edges[a].target] = true;
        }
    })

    Object.keys(objectGlobal.nodes).map(function (a) {
        if (typeof connectedNodes[objectGlobal.nodes[a].id] !== 'undefined') {
            objectGlobal.nodes[a].formulario.secciones.forEach(function (seccion) {
                seccion.campos.forEach(function (campo) {
                    if (campo.id && campo.id !== '') {
                        if (typeof arrCamposExist[campo.id] === 'undefined') {
                            arrCamposExist[campo.id] = true;
                            arrCampos.push({
                                id: campo.id,
                                label: campo.nombre,
                                isArray: campo.isArray,
                                valor: campo.valor,
                                seccion: seccion.nombre,
                                nodo: (objectGlobal.nodes[a].nodoName !== '' ? objectGlobal.nodes[a].nodoName : 'Nodo sin nombre'),
                            })
                        }
                    }
                })
            })
        }
    })

    allFields.value = arrCampos;

    return arrCampos;
}

function createSlugField(event, sectionKey, fieldKey) {

    const preValue = nodeSelection.value.formulario.secciones[sectionKey].campos[fieldKey].id;
    const fieldSlug = event.target.value;
    nodeSelection.value.formulario.secciones[sectionKey].campos[fieldKey].id = fieldSlug;
    if (typeof nodeSelection.value.formulario !== 'undefined') {
        nodeSelection.value.formulario.secciones[sectionKey].campos[fieldKey].id = nodeSelection.value.formulario.secciones[sectionKey].campos[fieldKey].id.replace(/[^a-zA-Z0-9]/g, '_');
        if(nodeSelection.value.formulario.secciones[sectionKey].campos[fieldKey].tipoCampo === 'currency'){
            nodeSelection.value.formulario.secciones[sectionKey].campos =
                nodeSelection.value.formulario.secciones[sectionKey].campos.map(camp => {
                    if(camp.id === `${preValue}_FORMATEADO`){
                        return {...camp, id: `${fieldSlug}_FORMATEADO` }
                    } else if(camp.id === `${preValue}_MONEDA`){
                        return {...camp, id: `${fieldSlug}_MONEDA` }
                    } else {
                        return camp
                    }
                });
        }
    }
}

function validateFieldSlug(event, keySeccion, key) {
    let exist = false;
    const fieldSlug = event.target.value;
    allFields.value.forEach(function (field) {
        if (field.id === fieldSlug) {
            toolbox.alert('El nombre de variable "' + fieldSlug + '" ya existe en otro campo, no debe utilizar nombres de variables repetidos', 'danger');
            exist = true;
        }
    })
    if (!exist) {
        extractFields();
    }
}

function handleCurrencyValue(event, sectionKey, preValue){
    const currencyValue = event.target.value;

    nodeSelection.value.formulario.secciones[sectionKey].campos =
        nodeSelection.value.formulario.secciones[sectionKey].campos.map(camp => {
            if(camp.id === `${preValue}_MONEDA`){
                return {...camp, valor: currencyValue}
            } else {
                return camp
            }
        });
}

function validateFieldName(id, name, tipoCampo, sectionKey){
    if(tipoCampo === 'currency'){
        nodeSelection.value.formulario.secciones[sectionKey].campos =
        nodeSelection.value.formulario.secciones[sectionKey].campos.map(camp => {
            if(camp.id === `${id}_FORMATEADO`){
                return {...camp, nombre: `valor formateado de ${name}` }
            } else if(camp.id === `${id}_MONEDA`){
                return {...camp, nombre: `valor moneda de ${name}` }
            } else {
                return camp
            }
        });
        extractFields();
    }
}

function addCampo(keySeccion, fixedField = false, id = '', nombre = '', visible = true, tipoCampo = '' ) {
    const campis = {
        id,
        nombre,
        showInReports: false,
        valorCalculado: '',
        ocrConfig: '',
        jsPost: '',
        jsPostAlert: '',
        canales_assign: [],
        grupos_assign: [],
        roles_assign: [],
        layoutSizePc: 4,
        currency: '',
        ttp: '',
        layoutSizeMobile: 12,
        desc: '',
        group: '',
        cssClass: '',
        requerido: 0,
        readonly: 0,
        deshabilitado: 0,
        visible,
        activo: true,
        forceReplaceDef: 0,
        archivadorDetalleId: 0,
        tipoCampo,
        mime: '',
        filePath: '',
        mascara: '',
        tokenMask: '',
        outputMask: '',
        contieneMask: false,
        catalogoId: '',
        catFValue: '',
        catFLabel: '',
        catFId: '',
        longitudMin: 0,
        longitudMax: 0,
        options: [{value: '', label: ''}],
        dependOn: [{campoId: '', campoIs: '', campoValue: '', campoVar: null}],
        padre: false,
        expNewConf: {
            label: '',
            tipo: '',
            ramo: '',
            sobreescribir: '',
            attr: [],
        },
        catalogoValue: '',
        catalogoLabel: '',
        proceso: {
            entrada: '',
            salida: [],
            url: '',
            method: '',
            type: '',
            tipoRecibido: false,
            isXML: false,
            header: '',
            configuracionS: []

        },
        opened: true,
        fixedField,
        procesoExec: null,
    }

    if (typeof nodeSelection.value.formulario.secciones[keySeccion] !== 'undefined') {
        nodeSelection.value.formulario.secciones[keySeccion].campos.push(campis);
        extractFields();
    } else {
        toolbox.alert('Error al agregar campo, por favor intente de nuevo', 'danger');
    }
}

function duplicarCampo(campo, keySeccion) {

    //const campoNew = { ...campo }
    const campoNew = JSON.parse(JSON.stringify(campo));
    campoNew.id = '';

    if (typeof nodeSelection.value.formulario.secciones[keySeccion] !== 'undefined') {
        nodeSelection.value.formulario.secciones[keySeccion].campos.push(campoNew);
        if(campo.tipoCampo === 'currency'){
        addCampo(keySeccion, true, `_FORMATEADO`, `valor formateado de ${campo.nombre}`, false, 'text');
        addCampo(keySeccion, true, `_MONEDA`, `valor moneda de ${campo.nombre}`, false, 'text');
        }
        extractFields();
    } else {
        toolbox.alert('Error al agregar campo, por favor intente de nuevo', 'danger');
    }
}

function validarYEstablecerPredeterminados(proceso) {
    // Verificar si todas las propiedades necesarias están definidas y no son vacías
    if (typeof proceso.entrada !== 'string') {
        proceso.entrada = ''; // Establecer valor predeterminado para 'entrada'
    }
    if (!Array.isArray(proceso.salida)) {
        proceso.salida = []; // Establecer valor predeterminado para 'salida'
    }
    if (typeof proceso.url !== 'string') {
        proceso.url = ''; // Establecer valor predeterminado para 'url'
    }
    if (typeof proceso.header !== 'string') {
        proceso.header = ''; // Establecer valor predeterminado para 'header'
    }
    // Verificar y establecer otros valores predeterminados para las propiedades si es necesario

    // Puedes agregar más validaciones y asignaciones predeterminadas según tus necesidades

    return proceso; // Devolver el proceso validado y con valores predeterminados
}

function validarYEstablecerPredeterminadosFormulario(formulario) {
    if (!formulario) {
        formulario = {
            tipo: '',
            secciones: [],
        };
    } else {
        formulario.tipo = formulario.tipo || 'privado';
        formulario.secciones = formulario.secciones || [];

        formulario.secciones = formulario.secciones.map(seccion => {
            // Establecer valores predeterminados para cada sección en caso de que estén ausentes o sean inválidos
            return {
                nombre: seccion.nombre || '',
                campos: seccion.campos || [],
                condiciones: seccion.condiciones || [{campoId: '', is: '', value: '', glue: 'AND'}],
                instrucciones: seccion.instrucciones || '',
            };
        });

        // Asegurar que los campos en cada sección tengan valores predeterminados
        formulario.secciones.forEach(seccion => {
            seccion.campos = seccion.campos.map(campo => {
                return {
                    id: campo.id || 0,
                    archivadorCampo: campo.archivadorCampo || '',
                    valorCalculado: campo.valorCalculado || '',
                    ocrConfig: campo.ocrConfig || '',
                    jsPost: campo.jsPost || '',
                    jsPostAlert: campo.jsPostAlert || '',
                    showInReports: ((typeof campo.showInReports !== 'undefined') ? campo.showInReports : 0),
                    nombre: campo.nombre || '',
                    valor: campo.valor || '',
                    canales_assign: campo.canales_assign || [],
                    grupos_assign: campo.grupos_assign || [],
                    roles_assign: campo.roles_assign || [],
                    ttp: campo.ttp || '',
                    layoutSizePc: campo.layoutSizePc || '',
                    currency: campo.currency || '',
                    layoutSizeMobile: campo.layoutSizeMobile || '',
                    ph: campo.ph || '',
                    desc: campo.desc || '',
                    group: campo.group || '',
                    cssClass: campo.cssClass || '',
                    requerido: ((typeof campo.requerido !== 'undefined') ? campo.requerido : 0),
                    readonly: ((typeof campo.readonly !== 'undefined') ? campo.readonly : 0),
                    deshabilitado: campo.deshabilitado || 0,
                    visible: ((typeof campo.visible !== 'undefined') ? campo.visible : 0),
                    activo: ((typeof campo.activo !== 'undefined') ? campo.activo : 0),
                    forceReplaceDef: ((typeof campo.forceReplaceDef !== 'undefined') ? campo.forceReplaceDef : 0),
                    archivadorDetalleId: campo.archivadorDetalleId || 0,
                    tipoCampo: campo.tipoCampo || '',
                    mime: campo.mime || '',
                    fileLabel: campo.fileLabel || '',
                    fileTipo: campo.fileTipo || '',
                    fileTipo2: campo.fileTipo2 || '',
                    fileRamo: campo.fileRamo || '',
                    fileProducto: campo.fileProducto || '',
                    jsonSend: campo.jsonSend || '',
                    fileFechaExp: campo.fileFechaExp || '',
                    fileReclamo: campo.fileReclamo || '',
                    filePoliza: campo.filePoliza || '',
                    fileEstadoPoliza: campo.fileEstadoPoliza || '',
                    fileNit: campo.fileNit || '',
                    fileDPI: campo.fileDPI || '',
                    fileCIF: campo.fileCIF || '',
                    filePath: campo.filePath || '',
                    mascara: campo.mascara || '',
                    tokenMask: campo.tokenMask || false,
                    outputMask: campo.outputMask || '',
                    contieneMask: campo.contieneMask || false,
                    //contienePadre: campo.contienePadre || false,
                    padre: campo.padre || false,
                    isArray: campo.isArray || false,
                    expNewConf: (typeof campo.expNewConf !== 'undefined' && typeof campo.expNewConf.label !== 'undefined') ? campo.expNewConf : {label: '',tipo: '',ramo: '',sobreescribir: '',attr: [],},
                    catalogoValue: campo.catalogoValue || '',
                    catalogoLabel: campo.catalogoLabel || '',
                    catalogoId: campo.catalogoId || '',
                    catFValue: campo.catFValue || '',
                    catFLabel: campo.catFLabel || '',
                    catFId: campo.catFId || '',
                    longitudMin: campo.longitudMin || 0,
                    dependOn: campo.dependOn || [{campoId: '', campoIs: '', campoValue: '', campoVar: ''}],
                    longitudMax: campo.longitudMax || 0,
                    proceso: campo.proceso || {
                        entrada: '',
                        salida: [],
                        url: '',
                        method: '',
                        type: '',
                        tipoRecibido: false,
                        isXML: false,
                        header: '',
                        configuracionS: []
                    },
                    archivadorRel: campo.archivadorRel || [],
                    multiValor: campo.multiValor || [],
                    fixedField: campo.fixedField || false,
                    procesoExec: campo.procesoExec || null,
                };
            });
        });
    }

    return formulario;
}

function onRestore(flow) {
    if (flow) {
        const [x = 0, y = 0] = flow.position
        const newNode = {
            id: flow.id,
            type: flow.type,
            position: flow.position,
            label: dragObject.name,
            typeObject: dragObject.type,
            estOut: '',
            estIo: 'e',
            cmT: '',
            ocr: 'd',
            ocrTpl: '',
            ocrDesc: '',
            ocrVC: '',
            priv: '',
            style: dragObject.style,
            formulario: {
                tipo: '',
                secciones: [{
                    nombre: '',
                    condiciones: [
                        {campoId: '', is: '', value: '', glue: 'AND'}
                    ],
                    instrucciones: '',
                    campos: [{
                        id: 0,
                        archivadorCampo: '',
                        nombre: '',
                        valorCalculado: '',
                        ocrConfig: '',
                        jsPost: '',
                        jsPostAlert: '',
                        showInReports: false,
                        roles_assign: [],
                        grupos_assign: [],
                        canales_assign: [],
                        ttp: '',
                        layoutSizePc: '',
                        layoutSizeMobile: '',
                        currency: '',
                        ph: '',
                        desc: '',
                        group: '',
                        cssClass: '',
                        requerido: 0,
                        readonly: 0,
                        valor: '',
                        deshabilitado: 0,
                        visible: 1,
                        activo: 1,
                        forceReplaceDef: 0,
                        archivadorDetalleId: 0,
                        tipoCampo: '',
                        mime: '',
                        filePath: '',
                        mascara: '',
                        tokenMask: false,
                        outputMask: '',
                        contieneMask: false,
                        //contienePadre: false,
                        padre: false,
                        expNewConf: {
                            label: '',
                            tipo: '',
                            ramo: '',
                            sobreescribir: '',
                            attr: [],
                        },
                        catalogoValue: '',
                        catalogoLabel: '',
                        catalogoId: '',
                        catFValue: '',
                        catFLabel: '',
                        catFId: '',
                        longitudMin: 0,
                        longitudMax: 0,
                        dependOn: [{campoId: '', campoIs: '', campoValue: '', campoVar: ''}],
                        isArray: false,
                        proceso: {
                            entrada: '',
                            salida: [],
                            url: '',
                            method: '',
                            type: '',
                            tipoRecibido: false,
                            isXML: false,
                            header: '',
                            configuracionS: []

                        },
                        fixedField: false,
                        procesoExec: null,
                    }],
                }
                ]

            },
            logicaAsig: '',
            roles_assign: [],
            grupos_assign: [],
            canales_assign: [],
            tareas_programadas: [],
            decisiones: [{glue: "AND", value: "", campoId: "", campoIs: ""}],
            decisionesL: '',
            review: [],
            salidas: '',
            salidaIsHTML: false,
            salidaIsPDF: false,
            salidaPDFId: '',
            salidaPDFGroup: '',
            salidaPDFDp: '',
            salidaPDFLabel: '',
            salidaPDFconf: {
                path: '',
                fileLabel: '',
                fileTipo: '',
                fileTipo2: '',
                fileRamo: '',
                fileProducto: '',
                jsonSend: '',
                fileFechaExp: '',
                fileReclamo: '',
                filePoliza: '',
                fileEstadoPoliza: '',
                fileNit: '',
                fileDPI: '',
                fileCIF: '',
                expNewConf: {
                    label: '',
                    tipo: '',
                    ramo: '',
                    sobreescribir: '',
                    attr: [],
                },
            },
            salidaIsEmail: false,
            salidaIsWhatsapp: false,
            saltoAutomatico: false,
            procesoEmail: {
                salidasEmail: '',
                asunto: '',
                destino: '',
                attachments: '',
                autoSend: false,
                reenvio: false,
                copia: [{destino: ''}],
                mailgun: {
                    apiKey: '',
                    domain: '',
                    from: '',
                }
            },
            procesoWhatsapp: {
                url: '',
                token: '',
                tpl: '',
                data: '',
                attachments: '',
                autoSend: false,
                reenvio: false,
            },
            procesos: [{
                entrada: '',
                salida: [],
                url: '',
                method: '',
                type: '',
                tipoRecibido: false,
                isXML: false,
                header: '',
                configuracionS: [],
                pf: [],
            }],
        }

        const restoredNodes = flow.nodes.map(node => {
            const restoredNode = {...newNode, ...node};
            if (restoredNode.formulario) {
                restoredNode.formulario = validarYEstablecerPredeterminadosFormulario(restoredNode.formulario);
            }
            if (restoredNode.procesoEmail) {
                restoredNode.procesoEmail = {...newNode.procesoEmail, ...restoredNode.procesoEmail};
            }
            if (restoredNode.salidaPDFconf) {
                restoredNode.salidaPDFconf = {...newNode.salidaPDFconf, ...restoredNode.salidaPDFconf};
            }
            if (restoredNode.procesoWhatsapp) {
                restoredNode.procesoWhatsapp = {...newNode.procesoWhatsapp, ...restoredNode.procesoWhatsapp};
            }
            if (restoredNode.procesos) {
                restoredNode.procesos = restoredNode.procesos.map(proceso => {
                    // Validar y establecer valores predeterminados para cada objeto proceso
                    return validarYEstablecerPredeterminados(proceso);
                });
            }
            return restoredNode;
        });

        setNodes(restoredNodes)
        setEdges(flow.edges)
        setTransform({x, y, zoom: flow.zoom || 1.5})
        extractFields();
    }
}

function onSelect(nodeClick) {
    //console.log(nodeClick);
    nodeSelection.value = nodeClick.node;
    extractFields();
}

function loadProduct() {

    toolbox.doAjax('GET', 'flujos/list/' + id.value, {},
        function (response) {

            if (response.data) {
                flujosDisponibles = response.data;

                let activo = 0;
                Object.keys(flujosDisponibles).forEach(function (key) {
                    if (parseInt(flujosDisponibles[key].activo) === 1) {
                        if (activo) {
                            toolbox.alert('Existen dos flujos activos, por favor, realice la desactivación de uno. Se cargará el último disponible');
                        }
                        seleccionarFlujo(flujosDisponibles[key]);
                    }
                })
                flujoSelected.value = response.data;
            }
        },
        function (response) {
            // toolbox.alert(response.msg, 'danger');
        }
    )

}

function clonarFlujo() {

    toolbox.confirm('Se creará una copia de este flujo y se colocará como activa', function () {
        //self.selected.id = idCliente;
        const dataToSend = {
            flujo: toObject(),
            producto: id.value,
            flujoId: 0,
            activo: flujoIsActive.value,
            modoPruebas: modoPruebas.value,
            nombre: flujoName.value
        }
        toolbox.doAjax('POST', 'flujos/new', dataToSend, function (response) {
            // loadProduct()
            extractFields();
            const newFlujo = response.data;
            flujosDisponibles = [newFlujo, ...flujosDisponibles].map(e => {
                if(!!newFlujo.activo &&  e.id !== newFlujo.id){
                    return  {...e, activo:  e.activo*0}
                }
                return  {...e, activo: e.activo*1, modoPruebas:  e.modoPruebas*1}
            });
            seleccionarFlujo(response.data);

        }, function (response) {
            toolbox.alert(response.msg);
        });
    });
    //save();
}

function changeFlujoName(event){
    const value = event.target.value;
    flujosDisponibles = flujosDisponibles.map(e => {
        return !!e.activo? {...e, nombre: value} : e
    });
}

function changeFlujoActivo(){
    flujosDisponibles = flujosDisponibles.map((e,i) => {
        let value = !flujoIsActive.value? 1 : 0;
        return flujoId.value == e.id ? {...e, activo: value} : {
            ...e, activo: flujoIsActive.value && i === 0 ? 1 : 0}
    });
}

function changeFlujoModoPruebas(){
    flujosDisponibles = flujosDisponibles.map(e => {
        return flujoId.value == e.id ? {...e, modoPruebas: !modoPruebas.value? 1 : 0} : e
    });
}

function describirFlujo() {
    flujoDescribir = toObject();
}

function exportarFlujo() {

    toolbox.confirm('Se descargará una copia de este flujo, ¿desea continuar?', function () {
        //self.selected.id = idCliente;
        const flujo = toObject();
        const nameFile = flujoName.value + '_' + id.value + '.flow';

        let dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(flujo));
        let dlAnchorElem = document.getElementById('downloadAnchorElem');
        dlAnchorElem.setAttribute("href",     dataStr     );
        dlAnchorElem.setAttribute("download", nameFile);
        dlAnchorElem.click();
    });
    //save();
}

function importarFlujo(event) {

    const self = this;
    toolbox.confirm('Se descargará una copia de este flujo, ¿desea continuar?', function () {

        let input = event.target;

        let reader = new FileReader();
        reader.onload = function() {
            var text = reader.result;

            let flowConfig = JSON.parse(text);
            onRestore(flowConfig);
            extractFields();
            self.importarFlujoShow = false;
        };
        reader.readAsText(input.files[0]);
    });
    //save();
}

function seleccionarFlujo(flujo) {
    let flowConfig = JSON.parse(flujo.flujo_config);
    onRestore(flowConfig);
    flujoIsActive.value = flujo.activo;
    modoPruebas.value = flujo.modoPruebas,
        flujoName.value = flujo.nombre;
    flujoId.value = flujo.id;
    activo.value = true;
    extractFields();
}

// Currency Aditional
function handleSelectionChange(event, keySeccion, key) {
    const prevValue = nodeSelection.value.formulario.secciones[keySeccion].campos[key].tipoCampo;
    const newValue = event.target.value;
    nodeSelection.value.formulario.secciones[keySeccion].campos[key].tipoCampo = newValue;
    const currencyCampo = nodeSelection.value.formulario.secciones[keySeccion].campos[key];
    if(prevValue === 'currency'){
        nodeSelection.value.formulario.secciones[keySeccion].campos =
        nodeSelection.value.formulario.secciones[keySeccion].campos
        .filter((camp) => ![`${currencyCampo.id}_FORMATEADO`,`${currencyCampo.id}_MONEDA`].includes(camp.id));
        extractFields();
    }
    if(newValue === 'currency'){
        addCampo(keySeccion, true, `${currencyCampo.id}_FORMATEADO`, `valor formateado de ${currencyCampo.nombre}`, false, 'text');
        addCampo(keySeccion, true, `${currencyCampo.id}_MONEDA`, `valor moneda de ${currencyCampo.nombre}`, false, 'text');
        extractFields();
    }
}

function updatedIdNodoName() {
    const nodeToUpdate = nodeSelection.value;
    if(nodeToUpdate.nodoId){
        nodeToUpdate.nodoId = nodeToUpdate.nodoId.replace(/[^a-zA-Z0-9]/g, '_');
    }
}

function updateNodeSelection() {
    const nodeToUpdate = nodeSelection.value;
    if (nodeToUpdate) {
        //const updatedNode = {...nodeToUpdate};
        const updatedNode = JSON.parse(JSON.stringify(nodeToUpdate));
        setNodes((prevNodes) => {
            const updatedNodes = prevNodes.map((node) =>
                node.id === updatedNode.id ? updatedNode : node
            );
            return updatedNodes;
        });
    }
}

//Drag and Drop
function onDragStartSeccion(event, index) {
    draggedSeccion.value = index;
    event.dataTransfer.effectAllowed = 'move';
}
function onDragOverSeccion(event) {
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';
}
function onDropSeccion(event, index) {
    event.preventDefault();
    if(draggedSeccion.value !== null){
        const draggedItem = nodeSelection.value.formulario.secciones[draggedSeccion.value];
        nodeSelection.value.formulario.secciones.splice(draggedSeccion.value, 1);
        nodeSelection.value.formulario.secciones.splice(index, 0, draggedItem);
        draggedSeccion.value = null;
    }
    draggedCampo.value = null;
}

function onDragStartCampo(event, index) {
    draggedCampo.value = index;
    event.dataTransfer.effectAllowed = 'move';
}
function onDragOverCampo(event) {
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';
}
function onDropCampo(event, keySeccion, index) {
    event.preventDefault();
    if(draggedCampo.value !== null){
        const draggedItem = nodeSelection.value.formulario.secciones[keySeccion].campos[draggedCampo.value];
        nodeSelection.value.formulario.secciones[keySeccion].campos.splice(draggedCampo.value, 1);
        nodeSelection.value.formulario.secciones[keySeccion].campos.splice(index, 0, draggedItem);
        draggedCampo.value = null;
    }
    draggedSeccion.value = null;
}

const nodeTypes = {
    rombo: markRaw(RomboNode),
}

const {
    findNode,
    onConnect,
    addEdges,
    addNodes,
    removeNodes,
    project,
    vueFlowRef,
    nodes,
    setNodes,
    setEdges,
    zoomOnScroll,
    panOnScroll,
    dimensions,
    setTransform,
    toObject,
    removeEdges
} = useVueFlow({
    defaultZoom: 1.5,
    maxZoom: 4,
    minZoom: 1,
    zoomOnScroll: false,
    panOnScroll: true
})

defineExpose({
    dragObject,
    nodeSelection,
    flujoSelected,
    showNodeConfig,
})

onConnect((params) => addEdgesWithArrow(params));

onMounted(() => {
    const self = this;
    id.value = (typeof route.params.id !== 'undefined') ? parseInt(route.params.id) : 0;
    if (id.value > 0) {
        loadProduct();
    }
})

window.ToOPT = {
    toObject: toObject,
    flujoId: flujoId,
    flujoIsActive: flujoIsActive,
    modoPruebas: modoPruebas,
    flujoName: flujoName,
    activo: activo,
    loadProduct: loadProduct,
};
</script>
<template>
    <CRow>
        <CCol :xs="12">
            <CCard class="mb-4">
                <CCardHeader>
                    <strong>Editar {{ producto.nombreProducto }}</strong>
                </CCardHeader>
                <CCardBody>
                    <CNav variant="tabs" role="tablist">
                        <CNavItem>
                            <CNavLink
                                href="javascript:void(0);"
                                :active="tabPaneActiveKey === 1"
                                @click="() => {tabPaneActiveKey = 1}"
                            >
                                Contenido
                            </CNavLink>
                        </CNavItem>
                        <CNavItem v-if="id > 0">
                            <CNavLink
                                href="javascript:void(0);"
                                :active="tabPaneActiveKey === 2"
                                @click="() => {tabPaneActiveKey = 2;}"
                            >
                                Catálogos
                            </CNavLink>
                        </CNavItem>
                        <CNavItem v-if="id > 0">
                            <CNavLink
                                href="javascript:void(0);"
                                :active="tabPaneActiveKey === 3"
                                @click="() => {tabPaneActiveKey = 3}"
                            >
                                Estados
                            </CNavLink>
                        </CNavItem>
                        <CNavItem v-if="id > 0">
                            <CNavLink
                                href="javascript:void(0);"
                                :active="tabPaneActiveKey === 4"
                                @click="() => {tabPaneActiveKey = 4}"
                            >
                                Flujo
                            </CNavLink>
                        </CNavItem>
                        <CNavItem v-if="id > 0">
                            <CNavLink
                                href="javascript:void(0);"
                                :active="tabPaneActiveKey === 5"
                                @click="() => {tabPaneActiveKey = 5}"
                            >
                                Personalización
                            </CNavLink>
                        </CNavItem>
                    </CNav>
                    <CTabContent class="mt-4">
                        <CTabPane role="tabpanel" aria-labelledby="home-tab" :visible="tabPaneActiveKey === 1">
                            <div>
                                <div>
                                    <h5>Datos del Producto</h5>
                                    <hr>
                                </div>
                                <div class="row">
                                    <div class="col-12 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label">Nombre</label>
                                            <input type="text" class="form-control" placeholder="Escribe aquí" v-model="producto.nombreProducto">
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-4">
                                        <div class="form-group">
                                            <label class="mb-1">Código interno o identificador</label>
                                            <input type="text" v-model="producto.codigoInterno" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-2 text-center">
                                        <div>
                                            <label class="form-check-label" for="appActiva">
                                                Activo
                                            </label>
                                        </div>
                                        <input class="form-check-input" type="checkbox" value="1" id="appActiva" v-model="producto.status" :checked="producto.status"/>
                                    </div>
                                    <div class="col-12 col-sm-2 text-center">
                                        <div>
                                            <label class="form-check-label" for="appActiva">
                                                Sincronizar AS400
                                            </label>
                                        </div>
                                        <input class="form-check-input" type="checkbox" value="1" id="appActiva" v-model="producto.sincronizar" :checked="producto.sincronizar"/>
                                    </div>
                                    <div class="col-12 col-sm-2 text-center">
                                        <div>
                                            <label class="form-check-label" for="appActiva">
                                                Activar manejo de errores
                                            </label>
                                        </div>
                                        <input class="form-check-input" type="checkbox" value="1" id="appActiva" v-model="producto.manErr" :checked="producto.manErr"/>
                                    </div>
                                    <div class="col-12 col-sm-4">
                                        <div class="form-group">
                                            <label class="mb-1">Revivir cotización</label>
                                            <select class="form-control" v-model="producto.revC">
                                                <option value="d">Desactivado</option>
                                                <option value="i">Revivir desde nodo inicial</option>
                                                <option value="u">Revivir desde último nodo</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-4">
                                        <div class="form-group">
                                            <label class="mb-1">Área del producto</label>
                                            <select class="form-control" v-model="producto.area">
                                                <option :value="key" v-for="(item, key) in areas">{{item}}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-12 mt-3">
                                        <div class="form-group">
                                            <label class="mb-1">Token de producto</label>
                                            <input type="text" v-model="producto.token" class="form-control" readonly>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label class="mb-1">Enlace</label>
                                            <input type="text" v-model="enlace" class="form-control" readonly>
                                        </div>
                                    </div>
                                    <CCard class="mb-4">
                                        <h5 class="mt-4 mb-3">Imagen</h5>
                                        <CCardBody>
                                            <!--<div class="row">
                                                <div class="col-12 col-sm-5 text-center">
                                                    <CCardImage v-if="producto.imagenData" :src="producto.imagenData" rounded thumbnail orientation="top" style="max-width: 150px"/>
                                                </div>
                                                <div class="col-12 col-sm-7">
                                                    <CCardText>Se recomienda imágenes optimizadas para web.</CCardText>
                                                    <CFormInput type="file" @change="onMainImageChange" class="form-control form-control-file" accept="image/jpg, image/jpeg, image/png/ image/gif"/>
                                                </div>
                                            </div>-->
                                            <div class="row">
                                                <!--<div class="col-12 col-sm-5 text-center">
                                                    <CCardImage v-if="producto.imagenData" :src="producto.imagenData" rounded thumbnail orientation="top" style="max-width: 150px"/>
                                                </div>-->
                                                <div class="col-12 col-sm-12">
                                                    <CCardText>Se recomienda imágenes optimizadas para web.</CCardText>
                                                    <CFormInput type="file" @change="onMainImageChange" class="form-control form-control-file" accept="image/jpg, image/jpeg, image/png/ image/gif"/>
                                                </div>
                                            </div>
                                        </CCardBody>
                                    </CCard>
                                    <CCard>
                                        <h5 class="mt-4 mb-3">Contenido</h5>
                                        <Editor
                                            :init="{
                                                    plugins: plugins,
                                                    toolbar: toolbar,
                                                    language: language,
                                                    promotion: false,
                                                    branding: false
                                                }"
                                            v-model="producto.descripcion"
                                            tinymce-script-src="https://tinyroble.s3.amazonaws.com/tinymce/tinymce.min.js"

                                        />
                                    </CCard>
                                </div>
                                <div class="mt-4">
                                    <h6 class="text-primary">Configuración de visibilidad</h6>
                                    <div class="row">
                                        <div class="col-12 col-sm-4">
                                            <div class="mb-3">
                                                <span>Selecciona el canal de usuarios</span>
                                                <multiselect
                                                    v-model="producto.canales_assign"
                                                    :options="canales"
                                                    :mode="'tags'"
                                                    :searchable="true"/>
                                            </div>
                                        </div>
                                        <div class="col-12 col-sm-4">
                                            <div class="mb-3">
                                                <span>Selecciona los distribuidores</span>
                                                <multiselect
                                                    v-model="producto.grupos_assign"
                                                    :options="grupos"
                                                    :mode="'tags'"
                                                    :searchable="true"/>
                                            </div>
                                        </div>
                                        <div class="col-12 col-sm-4">
                                            <div class="mb-3">
                                                <span>Selecciona los roles</span>
                                                <multiselect
                                                    v-model="producto.roles_assign"
                                                    :options="roles"
                                                    :mode="'tags'"
                                                    :searchable="true"/>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="text-muted">
                                                * Atención, si selecciona accesos se sobreescribirán en cascada Canales > Grupos > Roles
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <h6 class="text-primary">Otras configuraciones</h6>
                                    <div class="col-12 mb-3">
                                        <input class="form-check-input" type="checkbox" value="1" id="appActiva" v-model="producto.valSin" :checked="producto.valSin"/>
                                        Activar validación de siniestralidad
                                    </div>
                                    <!--<div>
                                        <small>Requiere un campo en el flujo llamado EMISION_DATOS_CLIENTE_AS400.datosIdEmpresaGC.datos03.datosClientePersonalGestorComercial.codigoCliente</small>
                                    </div>-->
                                </div>
                            </div>
                        </CTabPane>
                        <CTabPane role="tabpanel" aria-labelledby="profile-tab" :visible="tabPaneActiveKey === 2">
                            <div class="mb-3">
                                <b>Instrucciones:</b>
                                <div class="text-muted">
                                    Los catálogos pueden contener N cantidad de columnas, lo recomendable es 2, una columna de encabezado y otra de valor. <br>
<!--                                    La columna reservada "condicional" permite configurar expresiones condicionales donde se utilizan variables, esto permite filtrar el contenido del catálogo.-->
                                </div>
                            </div>
                            <div :id="plan.nombreCatalogo"  v-for="(plan, indexPlan) in planes">
                                <div class="accordionSimulatedHeader" @click="planes[indexPlan].show = !planes[indexPlan].show">
                                    {{ plan.nombreCatalogo || 'Catálogo sin nombre' }}
                                </div>
                                <div class="accordionSimulatedBody" v-if="planes[indexPlan].show">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="form-group pt-2">
                                                <label class="block-label">Identificador del catálogo:</label>
                                                <input v-model="plan.slug" type="text" class="form-control">
                                                <small>Identificador único de catálogo, sin caracteres especiales</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="form-group pt-2">
                                                <label class="block-label">Nombre del catálogo:</label>
                                                <input v-model="plan.nombreCatalogo" type="text" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <file-pond type="file"
                                                       class="filepond"
                                                       name="Plantilla"
                                                       label-idle="Arrastra una plantilla acá"
                                                       credits="false"
                                                       data-allow-reorder="true"
                                                       data-max-file-size="3MB"
                                                       :server="{
                                                    process: (fieldName, file, metadata, load, error, progress, abort) => {
                                                      handleCatalogoUpload(file, plan.slug, load, error, progress, planes);
                                                    }
                                                }"
                                                       ref="filepondInput">
                                            </file-pond>
                                        </div>
                                        <CCard class="pt-2">
                                            <CCardHeader>Detalle</CCardHeader>
                                            <CCardBody>
                                                <blockquote class="blockquote mb-0">
                                                    <EasyDataTable v-if="plan.items" :headers="preparedHeaders(plan.items, 'Opciones')"
                                                                   :items="obtenerItems(plan.items)"
                                                                   alternating
                                                                   rows-per-page-message="filas por página"
                                                                   rows-of-page-separator-message="de"
                                                    >
                                                        <template #item-Opciones="item">
                                                            <div>
                                                                <i class="fas fa-trash icon" @click="deleteItem(plan.slug, item)"></i>
                                                            </div>
                                                        </template>
                                                    </EasyDataTable>
                                                    <footer class="blockquote-footer"></footer>
                                                </blockquote>
                                            </CCardBody>
                                        </CCard>
                                        <div class="text-end mt-3">
                                            <button class="me-2 btn btn-danger" @click="eliminarPlan(plan.slug)">Eliminar catálogo</button>
                                            <button class="btn btn-primary" @click="downloadCatalogo(plan.slug)">Descargar catálogo</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button @click="addCatalogo" class="btn btn-sm btn-primary mb-5 mt-2">
                                <i class="fas fa-plus"></i> Agregar catálogo
                            </button>
                        </CTabPane>
                        <CTabPane role="tabpanel" aria-labelledby="profile-tab" :visible="tabPaneActiveKey === 3">
                            <div class="text-muted">
                                Estados obligatorios no modificables: "Creada", "Expirada".
                            </div>
                            <div class="mt-4">
                                <vue3-tags-input :tags="producto.estadosList" placeholder="Ingresa los estados a manejar" @on-tags-changed="handleEstadosTags" :add-tag-on-keys="[13, 188]" :allow-duplicates="false"/>
                            </div>
                        </CTabPane>
                        <CTabPane v-if="id > 0" role="tabpanel" aria-labelledby="contact-tab" :visible="tabPaneActiveKey === 4">
                            <div class="row mt-4">
                                <div class="col-lg-6 col-xs-12">
                                    <div class="mb-3">
                                        Versiones disponibles:
                                    </div>
                                    <EasyDataTable :headers="preparedHeaders(flujosDisponibles,'Editar',['flujo_config', 'productoId', 'descripcion', 'id', 'slug'])"
                                                   :items="preparedItems(flujosDisponibles)"
                                                   alternating
                                                   :hide-footer="true"
                                                   :show-pagination="'infinite-scroll'"
                                                   style="max-height: 200px; overflow-y: auto;"
                                                   rows-per-page-message="filas por página"
                                                   rows-of-page-separator-message="de"
                                    >
                                        <template #item-Editar="item">
                                            <div @click="()=>{ seleccionarFlujo(item) }" :key="item.id">
                                                <i class="fas fa-pencil icon me-3" v-if="flujoId !== parseInt(item.id)"></i>
                                                <span v-else class="text-success">
                                                    <i class="fas fa-check-circle icon me-2 text-success"></i>
                                                </span>
                                            </div>
                                        </template>
                                    </EasyDataTable>
                                </div>
                                <div class="col-xs-12 col-lg-6">
                                    <CFormSwitch label="Flujo activo" v-model="flujoIsActive" @input="changeFlujoActivo()"/>
                                    <CFormSwitch label="Modo pruebas" v-model="modoPruebas" @input="changeFlujoModoPruebas()"/>
                                    <input type="text" class="form-control" v-model="flujoName" placeholder="Nombre del flujo" @input="changeFlujoName($event)"/>
                                    <h6 class="mt-3">Operaciones adicionales</h6>
                                    <div class="btn btn-sm btn-primary mt-2 mb-2 me-2" @click="()=>{clonarFlujo()}">
                                        <i class="fa fa-clone"></i>
                                        Clonar flujo
                                    </div>
                                    <div class="btn btn-sm btn-primary mt-2 mb-2 me-2" @click="showDescribirFlujo = true; describirFlujo()">
                                        <i class="fa fa-eye"></i>
                                        Describir
                                    </div>
                                    <div class="btn btn-sm btn-primary mt-2 mb-2 me-2" @click="()=>{exportarFlujo()}">
                                        <i class="fa fa-download"></i>
                                        Exportar flujo
                                    </div>
                                    <div class="btn btn-sm btn-primary mt-2 mb-2 me-2" @click="importarFlujoShow = true">
                                        <i class="fa fa-upload"></i>
                                        Importar flujo
                                    </div>
                                    <a id="downloadAnchorElem" style="display:none"></a>
                                    <div class="mt-3" v-if="importarFlujoShow">
                                        <label>Importar flujo</label>
                                        <input type='file' class="form-control" accept='text/flow' @change='importarFlujo($event)'><br>
                                        <div class="mt-2 text-end">
                                            <button class="btn btn-danger btn-sm" @click="importarFlujoShow = false">Cancelar</button>
                                        </div>
                                    </div>
                                    <div v-if="showDescribirFlujo" @close="() => { showDescribirFlujo = false }" class="globalModal">
                                        <div class="globalModalContainer">
                                            <div ref="htmlContent" v-if="(typeof flujoDescribir.nodes !== 'undefined')">
                                                <div v-for="fl in flujoDescribir.nodes" class="mb-3">
                                                    <h5 class="text-success">
                                                        Nodo: <span class="text-uppercase fw-bold">{{fl.label.replace(/<\/?[^>]+(>|$)/g, "")}}</span> ({{fl.id}})
                                                    </h5>
                                                    <div>
                                                        <div><b>Visibilidad</b>: {{fl.formulario.tipo}}</div>
                                                        <div><b>Tipo</b>: {{fl.typeObject}}</div>
                                                    </div>
                                                    <div class="mt-3">
                                                        <h5 class="text-info">Campos disponibles:</h5>
                                                        <div v-if="typeof fl.formulario.secciones !== 'undefined' && fl.formulario.secciones.length > 0">
                                                            <div v-for="seccionTmp in fl.formulario.secciones">
                                                                <div v-for="campoTmp in seccionTmp.campos" class="mb-3">
                                                                    <div><b>Sección:</b> {{seccionTmp.nombre}}</div>
                                                                    <div><b>Identificador:</b> {{campoTmp.id}}</div>
                                                                    <div><b>Nombre:</b> {{campoTmp.nombre}}</div>
                                                                    <div><b>Tipo:</b> {{campoTmp.tipoCampo}}</div>
                                                                    <div><b>Usar para búsqueda:</b> {{campoTmp.showInReports}}</div>
                                                                    <hr>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="text-danger text-center my-3">
                                                            No posee campos configurados
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="globalModalFooter text-end">
                                                <CButton @click="showDescribirFlujo = false"
                                                         class="btn btn-primary me-2">
                                                    Volver a editor
                                                </CButton>
                                                <CButton @click="printHtmlFlujo()"
                                                         class="btn btn-primary me-2">
                                                    Descargar
                                                </CButton>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="mt-3">
                                        <div class="mb-3">
                                            Símbolos disponibles:
                                        </div>
                                        <div class="vue-flowNodes position-relative mb-2">
                                            <div class="vue-flow__node-input mb-1" :draggable="true" @dragstart="onDragStart($event, 'input')" data-title="Inicio" data-type="start">
                                                <i class="fa fa-play-circle me-1"></i> Inicio
                                            </div>
                                            <div class="vue-flow__node-input mb-1" :draggable="true" @dragstart="onDragStart($event, 'default')" data-title="Entradas" data-type="input">
                                                <i class="fa fa-inbox me-1"></i> Entradas
                                            </div>
                                            <div class="vue-flow__node-default mb-1" :draggable="true" @dragstart="onDragStart($event, 'rombo')" data-title="Condición" data-type="condition">
                                                <i class="fa fa-compact-disc"></i> Condición
                                            </div>
                                            <div class="vue-flow__node-default mb-1" :draggable="true" @dragstart="onDragStart($event, 'default')" data-title="Proceso" data-type="process">
                                                <i class="fa fa-database me-1"></i> Proceso
                                            </div>
                                            <div class="vue-flow__node-default mb-1" :draggable="true" @dragstart="onDragStart($event, 'default')" data-title="Usuario" data-type="setuser">
                                                <i class="fa fa-user me-1"></i> Asignar usuario
                                            </div>
                                            <div class="vue-flow__node-output mb-1" :draggable="true" @dragstart="onDragStart($event, 'default')" data-title='Salida de datos' data-type="output">
                                                <i class="fa fa-medal me-1"></i> Salidas
                                            </div>
                                            <br>
                                            <div class="mt-2 mb-3">Procesos específicos:</div>
                                            <br>
                                            <div class="vue-flow__node-default mb-1" :draggable="true" @dragstart="onDragStart($event, 'vehiculo')" data-title='Vehículos' data-type="vehiculo">
                                                <i class="fa fa-car me-1"></i> Vehículos
                                            </div>
                                            <div class="vue-flow__node-default mb-1" :draggable="true" @dragstart="onDragStart($event, 'vehiculo_comp')" data-title='Validación de cotización' data-type="vehiculo_comp">
                                                <i class="fa fa-check me-1"></i> Validación de cotización
                                            </div>
                                            <div class="vue-flow__node-default mb-1" :draggable="true" @dragstart="onDragStart($event, 'pagador')" data-title='Pago' data-type="pagador">
                                                <i class="fa-solid fa-credit-card"></i> Pago
                                            </div>
                                        </div>
                                        <div class="text-end mb-2">
                                            <CButton @click="save" class="btn btn-primary">
                                                Guardar
                                            </CButton>
                                        </div>
                                        <div v-if="showNodeConfig && typeof nodeSelection.typeObject !== 'undefined'" @close="() => { showNodeConfig = false }" class="globalModal">
                                            <div class="globalModalContainer">
                                                <div>
                                                    <div class="row">
                                                        <div class="col-12 col-sm-4">
                                                            <div class="mb-3">
                                                                <label>Nombre de nodo ({{nodeSelection.id}}):</label>
                                                                <input v-model="nodeSelection.nodoName" class="form-control" @change="updatedName"/>
                                                            </div>
                                                        </div>
                                                        <div class="col-12 col-sm-4">
                                                            <div class="mb-3">
                                                                <span class="mt-2">Asignar estado al entrar al nodo</span>
                                                                <multiselect :options="producto.estadosList" v-model="nodeSelection.estOut" :searchable="true"></multiselect>
                                                            </div>
                                                        </div>
                                                        <!--
                                                        <div class="col-12 col-sm-3">
                                                            <div class="mb-3">
                                                                <span class="mt-2">Lógica de estado</span>
                                                                <select class="form-select" v-model="nodeSelection.estIo">
                                                                    <option value="e">Al entrar</option>
                                                                    <option value="s">Al salir</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        -->
                                                        <div class="col-12 col-sm-4">
                                                            <div class="mb-3">
                                                                <label>Habilitar comentarios</label>
                                                                <select class="form-select" v-model="nodeSelection.cmT">
                                                                    <option value="d">Desactivados</option>
                                                                    <option value="p">Solo privados</option>
                                                                    <option value="m">Públicos y privados</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <hr>
                                                    </div>
                                                    <div v-if="nodeSelection.typeObject === 'start' || nodeSelection.typeObject === 'input' || nodeSelection.typeObject === 'output' || nodeSelection.typeObject === 'vehiculo' || nodeSelection.typeObject === 'vehiculo_comp' || nodeSelection.typeObject === 'pagador'">
                                                        <div class="col-12">
                                                            <div class="row">
                                                                <div class="col-12 col-sm-4">
                                                                    <div class="mb-3">
                                                                        <span>Seleccione el tipo de formulario</span>
                                                                        <multiselect :options="tiposFormularios" v-model="nodeSelection.formulario.tipo" :searchable="true"></multiselect>
                                                                    </div>
                                                                </div>
                                                                <div class="col-12 col-sm-4">
                                                                    <div class="mb-3">
                                                                        <span class="mt-2">Días de expiración</span>
                                                                        <input type="number" v-model="nodeSelection.expiracionNodo" class="form-control"/>
                                                                    </div>
                                                                </div>
                                                                <div class="col-12 col-sm-4">
                                                                    <div class="mb-3">
                                                                        <span class="mt-2">Continuar a siguiente etapa</span>
                                                                        <input type="date" v-model="nodeSelection.contFecha" class="form-control"/>
                                                                    </div>
                                                                </div>
                                                                <div class="col-12 col-sm-4">
                                                                    <div class="mb-3">
                                                                        <span class="mt-2">Agrupador por vehículos</span>
                                                                        <select class="form-select" v-model="nodeSelection.gVh">
                                                                            <option value="d">Desactivado</option>
                                                                            <option value="a">Activado</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="col-12 col-sm-4" v-if="nodeSelection.typeObject === 'start' || nodeSelection.typeObject === 'input'">
                                                                    <file-pond type="file"
                                                                               class="filepond"
                                                                               name="Plantilla"
                                                                               label-idle="Carga mediante plantilla"
                                                                               credits="false"
                                                                               data-allow-reorder="true"
                                                                               data-max-file-size="3MB"
                                                                               :server="{
                                                                            process: (fieldName, file, metadata, load, error, progress, abort) => {
                                                                                handleFileUpload(file, load, error, progress, abort);
                                                                            }
                                                                        }"
                                                                               ref="filepondInput">
                                                                    </file-pond>
                                                                </div>
                                                                <div class="col-12 col-sm-4">
                                                                    <div class="mb-3">
                                                                        <span class="mt-2">Habilitar OCR</span>
                                                                        <select class="form-select" v-model="nodeSelection.ocr">
                                                                            <option value="d">Desactivado</option>
                                                                            <option value="a">Activado</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="col-12 col-sm-4">
                                                                    <div class="mb-3">
                                                                        <span class="mt-2">Token de plantilla OCR</span>
                                                                        <input type="text" v-model="nodeSelection.ocrTpl" class="form-control"/>
                                                                    </div>
                                                                </div>
                                                                <div class="col-12 col-sm-4">
                                                                    <div class="mb-3">
                                                                        <span class="mt-2">Descripción OCR</span>
                                                                        <input type="text" v-model="nodeSelection.ocrDesc" class="form-control"/>
                                                                    </div>
                                                                </div>
                                                                <div class="col-12 col-sm-4">
                                                                    <div class="mb-3">
                                                                        <span class="mt-2">Vincular a campo</span>
                                                                        <input type="text" v-model="nodeSelection.ocrVC" class="form-control"/>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="mt-4">
                                                                <h6 class="text-primary">Configuración de visibilidad</h6>
                                                                <div class="row"  v-show="typeof nodeSelection.formulario !== 'undefined' && (nodeSelection.formulario.tipo === 'privado' || nodeSelection.formulario.tipo === 'mixto')">
                                                                    <div class="col-12 col-sm-4">
                                                                        <div class="mb-3">
                                                                            <span>Selecciona el canal de usuarios</span>
                                                                            <multiselect
                                                                                v-model="nodeSelection.canales_assign"
                                                                                :options="canales"
                                                                                :mode="'tags'"
                                                                                :searchable="true"/>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-12 col-sm-4">
                                                                        <div class="mb-3">
                                                                            <span>Selecciona los distribuidores</span>
                                                                            <multiselect
                                                                                v-model="nodeSelection.grupos_assign"
                                                                                :options="grupos"
                                                                                :mode="'tags'"
                                                                                :searchable="true"/>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-12 col-sm-4">
                                                                        <div class="mb-3">
                                                                            <span>Selecciona los roles</span>
                                                                            <multiselect
                                                                                v-model="nodeSelection.roles_assign"
                                                                                :options="roles"
                                                                                :mode="'tags'"
                                                                                :searchable="true"/>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-12">
                                                                        <div class="text-muted">
                                                                            * Atención, si selecciona accesos se sobreescribirán en cascada Canales > Grupos > Roles
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div>
                                                                    <div v-if="nodeSelection.formulario.tipo === 'mixto' || nodeSelection.formulario.tipo === 'publico'" class="text-danger mt-2">
                                                                        <i class="fas fa-warning"></i> Las cotizaciones públicas o mixtas no se asignan automáticamente, debe configurar un nodo de asignación de usuario para asignarlas.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="mt-4">
                                                                <h6 class="text-primary">Configuración de botones de flujo</h6>
                                                                <div class="row mt-3">
                                                                    <div class="col-12 col-sm-3">
                                                                        <div class="mb-3">
                                                                            <span class="mt-2">Texto de botón "Siguiente"</span>
                                                                            <input type="text" v-model="nodeSelection.btnTextNext" class="form-control"/>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-12 col-sm-3">
                                                                        <div class="mb-3">
                                                                            <span class="mt-2">Texto de botón "Anterior"</span>
                                                                            <input type="text" v-model="nodeSelection.btnTextPrev" class="form-control"/>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-12 col-sm-3">
                                                                        <div class="mb-3">
                                                                            <span class="mt-2">Texto de botón "Finalizar"</span>
                                                                            <input type="text" v-model="nodeSelection.btnTextFinish" class="form-control"/>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-12 col-sm-3">
                                                                        <div class="mb-3">
                                                                            <span class="mt-2">Texto de botón "Cancelar"</span>
                                                                            <input type="text" v-model="nodeSelection.btnTextCancel" class="form-control"/>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div>
                                                                    <span class="mt-2">Orden de botones</span>
                                                                </div>
                                                                <div class="row mt-3">
                                                                    <div class="col-12 col-sm-3">
                                                                        <div class="mb-3">
                                                                            <input type="text" v-model="nodeSelection.btnTextNextO" class="form-control"/>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-12 col-sm-3">
                                                                        <div class="mb-3">
                                                                            <input type="text" v-model="nodeSelection.btnTextPrevO" class="form-control"/>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-12 col-sm-3">
                                                                        <div class="mb-3">
                                                                            <input type="text" v-model="nodeSelection.btnTextFinishO" class="form-control"/>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-12 col-sm-3">
                                                                        <div class="mb-3">
                                                                            <input type="text" v-model="nodeSelection.btnTextCancelO" class="form-control"/>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="text-muted mb-3">
                                                                    Atención, los botones del formulario se desactivarán si no contienen texto.
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3" v-if="nodeSelection.typeObject === 'start' || nodeSelection.typeObject === 'input'">
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <div class="mt-5">
                                                                    <div>
                                                                        <h5>Pasos de formulario</h5>
                                                                        <hr>
                                                                        <div class="mb-4">
                                                                            Los formularios pueden incluir diversos pasos para ser completados.
                                                                        </div>
                                                                    </div>
                                                                    <div class="formBuilder">
                                                                        <div>
                                                                            <div :id="keySeccion + '-' + paso.nombre" v-for="(paso, keySeccion) in nodeSelection.formulario.secciones">
                                                                                <div class="accordionSimulatedHeader accordionSimulatedHeaderDark"
                                                                                @click="nodeSelection.formulario.secciones[keySeccion].show = !nodeSelection.formulario.secciones[keySeccion].show"
                                                                                    :key="keySeccion"
                                                                                    draggable="true"
                                                                                    @dragstart="onDragStartSeccion($event, keySeccion)"
                                                                                    @dragover="onDragOverSeccion($event)"
                                                                                    @drop="onDropSeccion($event, keySeccion)"
                                                                                >
                                                                                    {{ paso.nombre || 'Sección sin nombre' }}
                                                                                </div>
                                                                                <div class="accordionSimulatedBody" v-if="nodeSelection.formulario.secciones[keySeccion].show">
                                                                                    <div>
                                                                                        <h6>Condicionales</h6>
                                                                                        <div>
                                                                                            <div v-for="(dependencias, indexDep ) in nodeSelection.formulario.secciones[keySeccion].condiciones">
                                                                                                <CInputGroup class="mb-3">
                                                                                                    <CDropdown variant="input-group" v-if="indexDep > 0">
                                                                                                        <CDropdownToggle color="secondary" variant="outline">{{ (dependencias.glue) ? dependencias.glue : 'Pegamento' }}</CDropdownToggle>
                                                                                                        <CDropdownMenu>
                                                                                                            <CDropdownItem v-model="dependencias.glue" @click="dependencias.glue = 'AND'"> AND</CDropdownItem>
                                                                                                            <CDropdownItem v-model="dependencias.glue" @click="dependencias.glue = 'OR'"> OR</CDropdownItem>
                                                                                                        </CDropdownMenu>
                                                                                                    </CDropdown>
                                                                                                    <select class="form-control" v-model="dependencias.campoId">
                                                                                                        <option>Seleccione un campo</option>
                                                                                                        <option v-for="(campo, indexCampo) in allFields" :value="campo.id">{{ campo.nodo }} - {{ campo.label }} ({{ campo.id }})</option>
                                                                                                    </select>
                                                                                                    <CDropdown variant="input-group">
                                                                                                        <CDropdownToggle v-html="dependencias.campoIs" color="secondary" variant="outline" split/>
                                                                                                        <CDropdownMenu>
                                                                                                            <CDropdownItem v-model="dependencias.campoIs" @click="dependencias.campoIs = '='"> Igual</CDropdownItem>
                                                                                                            <CDropdownItem v-model="dependencias.campoIs" @click="dependencias.campoIs = '<'"> Menor que</CDropdownItem>
                                                                                                            <CDropdownItem v-model="dependencias.campoIs" @click="dependencias.campoIs = '<='"> Menor o igual que</CDropdownItem>
                                                                                                            <CDropdownItem v-model="dependencias.campoIs" @click="dependencias.campoIs = '>'"> Mayor que</CDropdownItem>
                                                                                                            <CDropdownItem v-model="dependencias.campoIs" @click="dependencias.campoIs = '>='"> Igual o mayor que</CDropdownItem>
                                                                                                            <CDropdownItem v-model="dependencias.campoIs" @click="dependencias.campoIs = '<>'"> Diferente</CDropdownItem>
                                                                                                            <CDropdownDivider/>
                                                                                                            <CDropdownItem v-model="dependencias.campoIs" @click="dependencias.campoIs = 'like'"> Contiene</CDropdownItem>
                                                                                                        </CDropdownMenu>
                                                                                                    </CDropdown>
                                                                                                    <CFormInput v-model="dependencias.value"/>
                                                                                                </CInputGroup>
                                                                                                <div class="text-end mb-3">
                                                                                                    <div class="btn btn-sm btn-danger py-0" @click="nodeSelection.formulario.secciones[keySeccion].condiciones.splice(indexDep, 1)">-</div>
                                                                                                </div>
                                                                                            </div>
                                                                                            <div class="text-end mb-3">
                                                                                                <div class="btn btn-sm btn-primary py-0 me-2" @click="nodeSelection.formulario.secciones[keySeccion].condiciones.push({ glue: 'AND', campoValue: '', campoId: '', campoIs: '', campoVar: ''});">+</div>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="row p-2">
                                                                                        <div class="col-12 col-sm-6">
                                                                                            <label class="form-label">Nombre de sección</label>
                                                                                            <input class="form-control" v-model="paso.nombre">
                                                                                        </div>
                                                                                        <div class="col-12 col-sm-12 mt-3">
                                                                                            <label class="form-label">Comentarios de sección</label>
                                                                                            <textarea class="form-control" v-model="paso.instrucciones"></textarea>
                                                                                        </div>
                                                                                    </div>
                                                                                    <hr>
                                                                                    <div>
                                                                                        <h5>Configuración de campos</h5>
                                                                                        <div class="small mb-2 mt-2">
                                                                                            <i class="fas fa-layer-group ms-3 acceptVarsField"></i> Soporta reemplazo de variables
                                                                                        </div>
                                                                                        <div v-for="(campito, key) in nodeSelection.formulario.secciones[keySeccion].campos">
                                                                                            <div v-if="!campito.fixedField">
                                                                                                <div class="accordionSimulatedHeader"
                                                                                                :id="campito.id + '-' + key"
                                                                                                @click="campito.show = !campito.show"
                                                                                                draggable="true"
                                                                                                @dragstart="onDragStartCampo($event, key)"
                                                                                                @dragover="onDragOverCampo($event)"
                                                                                                @drop="onDropCampo($event, keySeccion, key)"
                                                                                                >
                                                                                                    {{ campito.nombre || 'Campo sin nombre' }}
                                                                                                </div>
                                                                                                <div v-if="campito.show" class="accordionSimulatedBody">
                                                                                                    <div class="col-12">
                                                                                                        <h6 class="mb-4 fw-bold">Acceso y visibilidad</h6>
                                                                                                        <div class="row">
                                                                                                            <div class="col-12 col-sm-6">
                                                                                                                <div class="mb-3">
                                                                                                                    <span>Selecciona los distribuidores</span>
                                                                                                                    <multiselect
                                                                                                                        v-model="campito.grupos_assign"
                                                                                                                        :options="grupos"
                                                                                                                        :mode="'tags'"
                                                                                                                        :searchable="true"/>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                            <div class="col-12 col-sm-6">
                                                                                                                <div class="mb-3">
                                                                                                                    <span>Selecciona los roles</span>
                                                                                                                    <multiselect
                                                                                                                        v-model="campito.roles_assign"
                                                                                                                        :options="roles"
                                                                                                                        :mode="'tags'"
                                                                                                                        :searchable="true"/>
                                                                                                                    <div class="text-muted">
                                                                                                                        * Atención, si selecciona roles específicos se sobreescribirá la configuración de distribuidores.
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                            <div class="col-12">
                                                                                                                <div>
                                                                                                                    <label>Condicionales</label>
                                                                                                                    <div v-for="(campoDependencia, indexDep ) in campito.dependOn">
                                                                                                                        <CInputGroup class="mb-1">
                                                                                                                            <CDropdown variant="input-group" v-if="indexDep > 0">
                                                                                                                                <CDropdownToggle color="secondary" variant="outline">{{ (campoDependencia.glue) ? campoDependencia.glue : 'Pegamento' }}</CDropdownToggle>
                                                                                                                                <CDropdownMenu>
                                                                                                                                    <CDropdownItem v-model="campoDependencia.glue" @click="campoDependencia.glue = 'AND'"> AND</CDropdownItem>
                                                                                                                                    <CDropdownItem v-model="campoDependencia.glue" @click="campoDependencia.glue = 'OR'"> OR</CDropdownItem>
                                                                                                                                </CDropdownMenu>
                                                                                                                            </CDropdown>
                                                                                                                            <CFormSelect v-model="campoDependencia.campoId">
                                                                                                                                <option>Seleccione un campo</option>
                                                                                                                                <option v-for="(campo, indexCampo) in allFields" :value="campo.id">{{ campo.nodo }} - {{ campo.label }} ({{ campo.id }})</option>
                                                                                                                            </CFormSelect>
                                                                                                                            <input v-model="campoDependencia.campoVar" class="form-control" placeholder="{{variable}}"/>
                                                                                                                            <CDropdown variant="input-group">
                                                                                                                                <CDropdownToggle v-html="campoDependencia.campoIs" color="secondary" variant="outline" split/>
                                                                                                                                <CDropdownMenu>
                                                                                                                                    <CDropdownItem v-model="campoDependencia.campoIs" @click="campoDependencia.campoIs = '='"> Igual</CDropdownItem>
                                                                                                                                    <CDropdownItem v-model="campoDependencia.campoIs" @click="campoDependencia.campoIs = '<'"> Menor que</CDropdownItem>
                                                                                                                                    <CDropdownItem v-model="campoDependencia.campoIs" @click="campoDependencia.campoIs = '>'"> Mayor que</CDropdownItem>
                                                                                                                                    <CDropdownItem v-model="campoDependencia.campoIs" @click="campoDependencia.campoIs = '<='"> Menor o igual que</CDropdownItem>
                                                                                                                                    <CDropdownItem v-model="campoDependencia.campoIs" @click="campoDependencia.campoIs = '>='"> Igual o mayor que</CDropdownItem>
                                                                                                                                    <CDropdownItem v-model="campoDependencia.campoIs" @click="campoDependencia.campoIs = '<>'"> Diferente</CDropdownItem>
                                                                                                                                    <CDropdownDivider/>
                                                                                                                                    <CDropdownItem v-model="campoDependencia.campoIs" @click="campoDependencia.campoIs = 'like'"> Contiene</CDropdownItem>
                                                                                                                                </CDropdownMenu>
                                                                                                                            </CDropdown>
                                                                                                                            <CFormInput v-model="campoDependencia.campoValue" aria-label="Text input with segmented dropdown button"/>
                                                                                                                        </CInputGroup>
                                                                                                                        <div class="text-end mb-3">
                                                                                                                            <div class="btn btn-sm btn-danger py-0" @click="campito.dependOn.splice(indexDep, 1)">-</div>
                                                                                                                        </div>
                                                                                                                    </div>
                                                                                                                    <div class="text-end mb-3">
                                                                                                                        <div class="btn btn-sm btn-primary py-0 me-2" @click="campito.dependOn.push({ glue: 'AND', campoValue: '', campoId: '', campoIs: '', campoVar: '' });">+</div>
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                        </div>
                                                                                                        <h6 class="mb-4 fw-bold">Configuración de campo</h6>
                                                                                                        <div class="row">
                                                                                                            <div class="col-12 col-sm-4 mb-2">
                                                                                                                <label class="form-label text-primary">Nombre del campo interno(*)</label>
                                                                                                                <input class="form-control" type="text" :value="campito.id" @change="validateFieldSlug($event, keySeccion, key)" @keyup="createSlugField($event, keySeccion, key)"/>
                                                                                                            </div>
                                                                                                            <div class="col-12 col-sm-4 mb-2">
                                                                                                                <label class="form-label text-primary">Nombre a mostrar(*) <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                                                                <input class="form-control" type="text" v-model="campito.nombre" @change="extractFields" @keyup="validateFieldName(campito.id, campito.nombre, campito.tipoCampo, keySeccion)">
                                                                                                            </div>
                                                                                                            <div class="col-12 col-sm-4 mb-2">
                                                                                                                <label class="form-label text-primary">Tipo de campo(*)</label>
                                                                                                                <select class="form-control" :value="campito.tipoCampo" @change="handleSelectionChange($event, keySeccion, key)">
                                                                                                                    <option v-for="itemF in fieldTypes" :value="itemF.type">
                                                                                                                        <i :class="'me-2 fa ' + itemF.icon"></i> {{ itemF.name }}
                                                                                                                    </option>
                                                                                                                </select>
                                                                                                            </div>
                                                                                                            <div class="col-12 col-sm-4 mb-2" v-if="campito.tipoCampo === 'currency'">
                                                                                                                <label class="form-label">Código de moneda (USD, $, Q, etc)</label>
                                                                                                                <input class="form-control" v-model="campito.currency" @change="handleCurrencyValue($event, keySeccion, campito.id)"/>
                                                                                                            </div>
                                                                                                            <div class="col-12 col-sm-4 mb-2" v-if="campito.tipoCampo !== 'txtlabel' && campito.tipoCampo !== 'subtitle' && campito.tipoCampo !== 'title'">
                                                                                                                <label class="form-label">Descripción <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                                                                <input class="form-control" v-model="campito.desc"/>
                                                                                                            </div>
                                                                                                            <div class="col-12 col-sm-4 mb-2" v-if="campito.tipoCampo !== 'txtlabel' && campito.tipoCampo !== 'subtitle' && campito.tipoCampo !== 'title'">
                                                                                                                <label class="form-label">Texto pre llenado (Placeholder) <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                                                                <input class="form-control" v-model="campito.ph"/>
                                                                                                            </div>
                                                                                                            <div class="col-12 col-sm-4 mb-2">
                                                                                                                <label class="form-label">Tooltip (Descripción flotante) <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                                                                <input class="form-control" v-model="campito.ttp"/>
                                                                                                            </div>
                                                                                                            <div class="col-12 col-sm-4 mb-2">
                                                                                                                <label class="form-label">Tamaño en PC</label>
                                                                                                                <select class="form-control" v-model="campito.layoutSizePc">
                                                                                                                    <option v-for="i in 12" :value="i">{{ i }} columna(s)</option>
                                                                                                                </select>
                                                                                                            </div>
                                                                                                            <div class="col-12 col-sm-4 mb-2">
                                                                                                                <label class="form-label">Tamaño en móvil</label>
                                                                                                                <select class="form-control" v-model="campito.layoutSizeMobile">
                                                                                                                    <option v-for="i in 12" :value="i">{{ i }} columna(s)</option>
                                                                                                                </select>
                                                                                                            </div>
                                                                                                            <div class="col-12 col-sm-4 mb-2" v-if="campito.tipoCampo !== 'txtlabel' && campito.tipoCampo !== 'subtitle' && campito.tipoCampo !== 'title'">
                                                                                                                <label class="form-label">Valor por defecto <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                                                                <input class="form-control" v-model="campito.valor"/>
                                                                                                            </div>
                                                                                                            <div class="col-12 col-sm-4 mb-2" v-if="campito.tipoCampo !== 'add'">
                                                                                                                <label class="form-label">Agrupador</label>
                                                                                                                <input class="form-control" v-model="campito.group"/>
                                                                                                            </div>
                                                                                                            <div class="col-12 col-sm-4 mb-2" v-if="(campito.mascara === '' || campito.mascara === null) && (campito.tipoCampo === 'text' || campito.tipoCampo === 'textArea' || campito.tipoCampo === 'number' || campito.tipoCampo === 'numslider' || campito.tipoCampo === 'encrypt' || campito.tipoCampo === 'add')">
                                                                                                                <div class="mb-3">
                                                                                                                    <label class="form-label" v-if="campito.tipoCampo === 'number' || campito.tipoCampo === 'numslider'">Dimensión</label>
                                                                                                                    <label class="form-label" v-else>Longitud <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                                                                    <div class="input-group mb-3">
                                                                                                                        <span class="input-group-text">Min</span>
                                                                                                                        <input type="text" class="form-control" v-model="campito.longitudMin">
                                                                                                                        <span class="input-group-text">Max</span>
                                                                                                                        <input type="text" class="form-control" v-model="campito.longitudMax">
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                            <div class="col-12 col-sm-4 mb-2">
                                                                                                                <label class="form-label">Proceso</label>
                                                                                                                <select class="form-control" v-model="campito.procesoExec">
                                                                                                                    <option :value="''">--Seleccione un nodo de proceso--</option>
                                                                                                                    <option v-for="i in filterNodosProcess()" :value="i.id">{{ i.nodoName }}</option>
                                                                                                                </select>
                                                                                                            </div>
                                                                                                            <!--<div class="col-12 mb-2" v-if="(campito.mascara === '' || campito.mascara === null) && (campito.tipoCampo === 'text' || campito.tipoCampo === 'textarea' || campito.tipoCampo === 'number' || campito.tipoCampo === 'numslider')">
                                                                                                                <div class="col-12 col-sm-6">
                                                                                                                    <label>Asociar archivador</label>
                                                                                                                    <multiselect
                                                                                                                        v-model="campito.archivadorRel"
                                                                                                                        :options="optionsArchivadores"
                                                                                                                        :searchable="true"/>
                                                                                                                </div>
                                                                                                            </div>-->
                                                                                                            <div class="col-12">
                                                                                                                <div class="row">
                                                                                                                    <div class="col-12 col-sm-6 mb-2" v-if="campito.tipoCampo === 'text' || campito.tipoCampo === 'number' || campito.tipoCampo === 'textArea' || campito.tipoCampo === 'encrypt'">
                                                                                                                        <label class="form-label">Valor calculado <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                                                                        <input class="form-control" v-model="campito.valorCalculado"/>
                                                                                                                        <div class="text-muted mt-2 mb-2" style="font-size: 10px">
                                                                                                                            <div class="fw-bold">Funciones disponibles</div>
                                                                                                                            <div class="row">
                                                                                                                                <div class="col-12 col-sm-6">
                                                                                                                                    FN.EDAD("FECHA O IDENTIFICADOR DE CAMPO", "FORMATO DE ENTRADA")
                                                                                                                                </div>
                                                                                                                                <div class="col-12 col-sm-6">
                                                                                                                                    FN.SUMARDIAS("FECHA O IDENTIFICADOR DE CAMPO", "FORMATO DE ENTRADA", "FORMATO DE SALIDA", "Dias a sumar")
                                                                                                                                </div>
                                                                                                                            </div>
                                                                                                                            <div class="mt-2">
                                                                                                                                <div class="text-muted">* Si el campo es calculado, se ignorará el valor por defecto y se desactivará su escritura</div>
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                    </div>
                                                                                                                    <div class="col-12 col-sm-6 mb-2" v-if="campito.tipoCampo === 'text' || campito.tipoCampo === 'number' || campito.tipoCampo === 'textArea' || campito.tipoCampo === 'encrypt' || campito.tipoCampo === 'date'">
                                                                                                                        <label class="form-label">Validación por JS personalizado <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                                                                        <textarea class="form-control" v-model="campito.jsPost"></textarea>
                                                                                                                    </div>
                                                                                                                    <div class="col-12 col-sm-6 mb-2" v-if="campito.tipoCampo === 'text' || campito.tipoCampo === 'number' || campito.tipoCampo === 'textArea' || campito.tipoCampo === 'encrypt' || campito.tipoCampo === 'date'">
                                                                                                                        <label class="form-label">Mensaje de validación por JS personalizado <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                                                                        <input class="form-control" v-model="campito.jsPostAlert"/>
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                            <div class="col-12 col-sm-12 mb-4" v-if="campito.tipoCampo === 'txtlabel'">
                                                                                                                <label class="form-label">Contenido</label>
                                                                                                                <Editor
                                                                                                                    :init="{
                                                                                                                        plugins: plugins,
                                                                                                                        toolbar: toolbar,
                                                                                                                        language: language,
                                                                                                                        promotion: false,
                                                                                                                        branding: false
                                                                                                                    }"
                                                                                                                    v-model="campito.valor"
                                                                                                                    tinymce-script-src="https://tinyroble.s3.amazonaws.com/tinymce/tinymce.min.js"
                                                                                                                />
                                                                                                            </div>
                                                                                                            <div class="col-12 col-sm-6">
                                                                                                                <div class="row">
                                                                                                                    <div class="col-12">
                                                                                                                        <input type="checkbox" v-model="campito.visible" value="1" :id="campito.id + 'vis'">
                                                                                                                        <label class="form-label ms-2" :for="campito.id + 'vis'">Visible</label>
                                                                                                                    </div>
                                                                                                                    <div class="col-12">
                                                                                                                        <input type="checkbox" v-if="campito.tipoCampo !== 'encrypt'" v-model="campito.showInReports" value="1" :id="campito.id + 'reporte'">
                                                                                                                        <label class="form-label ms-2" :for="campito.id + 'reporte'">Mostrar en listado de tareas</label>
                                                                                                                    </div>
                                                                                                                    <div class="col-12">
                                                                                                                        <input type="checkbox" v-model="campito.deshabilitado" value="1" :id="campito.id + 'de'">
                                                                                                                        <label class="form-label ms-2" :for="campito.id + 'de'">Deshabilitado</label>
                                                                                                                    </div>
                                                                                                                    <div class="col-12">
                                                                                                                        <input type="checkbox" v-model="campito.requerido" value="1" :id="campito.id + 'rq'">
                                                                                                                        <label class="form-label ms-2" :for="campito.id + 'rq'">Requerido</label>
                                                                                                                    </div>
                                                                                                                    <div class="col-12">
                                                                                                                        <input type="checkbox" v-model="campito.readonly" value="1" :id="campito.id + 'sl'">
                                                                                                                        <label class="form-label ms-2" :for="campito.id + 'sl'">Solo lectura</label>
                                                                                                                    </div>
                                                                                                                    <div class="col-12">
                                                                                                                        <input type="checkbox" v-model="campito.activo" value="1" :id="campito.id + 'ac'">
                                                                                                                        <label class="form-label ms-2" :for="campito.id + 'ac'">Activo</label>
                                                                                                                    </div>
                                                                                                                    <div class="col-12">
                                                                                                                        <input type="checkbox" v-model="campito.forceReplaceDef" value="1" :id="campito.id + 'frp'">
                                                                                                                        <label class="form-label ms-2" :for="campito.id + 'frp'">Forzar reemplazo de valor por defecto</label>
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                            <div class="col-12 col-sm-6">
                                                                                                                <div v-if="campito.tipoCampo === 'text' || campito.tipoCampo === 'number' || campito.tipoCampo === 'textArea' || campito.tipoCampo === 'date'  ||  campito.tipoCampo === 'currency' || campito.tipoCampo === 'encrypt'">
                                                                                                                    <div class="mb-3">
                                                                                                                        <CFormSwitch label="Configurar máscaras" v-model="campito.contieneMask"/>
                                                                                                                    </div>
                                                                                                                    <div v-if="campito.contieneMask">
                                                                                                                        <div class="row">
                                                                                                                            <div class="col-12 col-sm-6">
                                                                                                                                <label class="form-label">Máscara</label>
                                                                                                                                <input class="form-control" v-model="campito.mascara"/>
                                                                                                                            </div>
                                                                                                                            <div class="col-12 col-sm-6">
                                                                                                                                <label class="form-label">Patrón</label>
                                                                                                                                <input class="form-control" v-model="campito.tokenMask"/>
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                                <div class="row mt-2" v-if="campito.tipoCampo === 'checkbox' || campito.tipoCampo === 'option' || campito.tipoCampo === 'select' || campito.tipoCampo === 'multiselect'">
                                                                                                                    <div class="col-12">
                                                                                                                        <div class="row">
                                                                                                                            <div class="col-12 mb-3">
                                                                                                                                <label class="form-label">Seleccione el catálogo</label>
                                                                                                                                <select class="form-control" v-model="campito.catalogoId">
                                                                                                                                    <option v-for="(catalogo, keyCat) in planes" :value="catalogo.slug">{{ catalogo.nombreCatalogo }}</option>
                                                                                                                                </select>
                                                                                                                            </div>
                                                                                                                            <div class="col-6"  v-if="typeof planes[campito.catalogoId] !== 'undefined' && typeof planes[campito.catalogoId].items[0] !== 'undefined'">
                                                                                                                                <label class="form-label">Valor de campo</label>
                                                                                                                                <div>
                                                                                                                                    <CButton class="w-100 mb-2  btn-sm" color="primary" v-for="item in Object.keys(planes[campito.catalogoId].items[0])"
                                                                                                                                             @click="campito.catalogoValue = item;"
                                                                                                                                             :active="(campito.catalogoValue === item)"
                                                                                                                                             :id="'label' + campito.id + item"
                                                                                                                                    >
                                                                                                                                        {{ item }}
                                                                                                                                    </CButton>
                                                                                                                                </div>
                                                                                                                            </div>
                                                                                                                            <div class="col-6"  v-if="typeof planes[campito.catalogoId] !== 'undefined' && typeof planes[campito.catalogoId].items[0] !== 'undefined'">
                                                                                                                                <label class="form-label">Texto a mostrar</label>
                                                                                                                                <div>
                                                                                                                                    <CButton class="w-100 mb-2  btn-sm" color="primary" v-for="item in Object.keys(planes[campito.catalogoId].items[0])"
                                                                                                                                             @click="campito.catalogoLabel = item;"
                                                                                                                                             :active="(campito.catalogoLabel === item)"
                                                                                                                                             :id="'label' + campito.id + item"
                                                                                                                                    >
                                                                                                                                        {{ item }}
                                                                                                                                    </CButton>
                                                                                                                                </div>
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                        <div class="row">
                                                                                                                            <div class="col-12 mb-3 mt-3">
                                                                                                                                <label class="form-label">Campo de filtrado</label>
                                                                                                                                <div>
                                                                                                                                    <CFormSelect v-model="campito.catFId">
                                                                                                                                        <option value="">-- Seleccione un campo --</option>
                                                                                                                                        <option v-for="(campo, indexCampo) in allFields" :value="campo.id">{{ campo.nodo }} - {{ campo.label }} ({{ campo.id }})</option>
                                                                                                                                    </CFormSelect>
                                                                                                                                </div>
                                                                                                                            </div>
                                                                                                                            <div class="col-12"  v-if="typeof planes[campito.catalogoId] !== 'undefined' && typeof planes[campito.catalogoId].items[0] !== 'undefined' && (campito.catFId !== '' && campito.catFId && (typeof campito.catFId === 'string' || campito.catFId instanceof String))">
                                                                                                                                <label class="form-label">Sea igual a:</label>
                                                                                                                                <div>
                                                                                                                                    <CButton class="w-100 mb-2  btn-sm" color="primary" v-for="item in Object.keys(planes[campito.catalogoId].items[0])"
                                                                                                                                             @click="campito.catFValue = item;"
                                                                                                                                             :active="(campito.catFValue === item)"
                                                                                                                                             :id="'label' + campito.id + item"
                                                                                                                                    >
                                                                                                                                        {{ item }}
                                                                                                                                    </CButton>
                                                                                                                                </div>
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                        </div>
                                                                                                        <div>
                                                                                                            <div class="row">
                                                                                                                <div class="col-12" v-if="campito.tipoCampo === 'file'">
                                                                                                                    <div class="row">
                                                                                                                        <div class="col-12 col-sm-6">
                                                                                                                            <div class="mb-3">
                                                                                                                                <label class="form-label">Tipos de archivo (MIME)</label>
                                                                                                                                <textarea class="form-control" v-model="campito.mime"></textarea>
                                                                                                                                <div class="text-muted">
                                                                                                                                    Ingrese los MIME Type separados por coma, en caso desee definir tamaño separe por pipe el tamaño en mb deseado, ejemplo: image/gif, image/png|5, image/jpg|3
                                                                                                                                </div>
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                        <div class="col-12 col-sm-6">
                                                                                                                            <div class="mb-3">
                                                                                                                                <label class="form-label">Directorio o Path (S3) <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                                                                                <input type="text" class="form-control" v-model="campito.filePath"/>
                                                                                                                                <div class="text-muted">
                                                                                                                                    Ingrese el directorio donde se almacenará el archivo
                                                                                                                                </div>
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                                <div class="col-12" v-if="campito.tipoCampo === 'fileER' || campito.tipoCampo === 'signature'">
                                                                                                                    <div class="row">
                                                                                                                        <div class="col-12 col-sm-6">
                                                                                                                            <div class="mb-3">
                                                                                                                                <label class="form-label">Tipos de archivo (MIME)</label>
                                                                                                                                <textarea class="form-control" v-model="campito.mime"></textarea>
                                                                                                                                <div class="text-muted">
                                                                                                                                    Ingrese los MIME Type separados por coma, en caso desee definir tamaño separe por pipe el tamaño en mb deseado, ejemplo: image/gif, image/png|5, image/jpg|3
                                                                                                                                </div>
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                        <div class="col-12 col-sm-6">
                                                                                                                            <div class="mb-3">
                                                                                                                                <label class="form-label">Directorio o Path (S3) <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                                                                                <input type="text" class="form-control" v-model="campito.filePath"/>
                                                                                                                                <div class="text-muted">
                                                                                                                                    Ingrese el directorio donde se almacenará el archivo
                                                                                                                                </div>
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                        <div class="col-12">
                                                                                                                            <h6>Campos para expedientes</h6>
                                                                                                                        </div>
                                                                                                                        <div class="col-12 col-sm-4">
                                                                                                                            <div class="mb-3">
                                                                                                                                <label class="form-label">Label o nombre a mostrar <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                                                                                <input type="text" class="form-control" v-model="campito.fileLabel"/>
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                        <div class="col-12 col-sm-4">
                                                                                                                            <div class="mb-3">
                                                                                                                                <label class="form-label">Tipo <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                                                                                <input type="text" class="form-control" v-model="campito.fileTipo"/>
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                        <div class="col-12 col-sm-4">
                                                                                                                            <div class="mb-3">
                                                                                                                                <label class="form-label">Tipo secundario <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                                                                                <input type="text" class="form-control" v-model="campito.fileTipo2"/>
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                        <div class="col-12 col-sm-4">
                                                                                                                            <div class="mb-3">
                                                                                                                                <label class="form-label">Ramo <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                                                                                <input type="text" class="form-control" v-model="campito.fileRamo"/>
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                        <div class="col-12 col-sm-4">
                                                                                                                            <div class="mb-3">
                                                                                                                                <label class="form-label">Producto <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                                                                                <input type="text" class="form-control" v-model="campito.fileProducto"/>
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                        <div class="col-12 col-sm-4">
                                                                                                                            <div class="mb-3">
                                                                                                                                <label class="form-label">Fecha Caducidad <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                                                                                <input type="text" class="form-control" v-model="campito.fileFechaExp"/>
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                        <div class="col-12 col-sm-4">
                                                                                                                            <div class="mb-3">
                                                                                                                                <label class="form-label">Reclamo <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                                                                                <input type="text" class="form-control" v-model="campito.fileReclamo"/>
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                        <div class="col-12 col-sm-4">
                                                                                                                            <div class="mb-3">
                                                                                                                                <label class="form-label">Póliza <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                                                                                <input type="text" class="form-control" v-model="campito.filePoliza"/>
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                        <div class="col-12 col-sm-4">
                                                                                                                            <div class="mb-3">
                                                                                                                                <label class="form-label">Estado Póliza <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                                                                                <input type="text" class="form-control" v-model="campito.fileEstadoPoliza"/>
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                        <div class="col-12 col-sm-4">
                                                                                                                            <div class="mb-3">
                                                                                                                                <label class="form-label">NIT <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                                                                                <input type="text" class="form-control" v-model="campito.fileNit"/>
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                        <div class="col-12 col-sm-4">
                                                                                                                            <div class="mb-3">
                                                                                                                                <label class="form-label">DPI <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                                                                                <input type="text" class="form-control" v-model="campito.fileDPI"/>
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                        <div class="col-12 col-sm-4">
                                                                                                                            <div class="mb-3">
                                                                                                                                <label class="form-label">CIF <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                                                                                <input type="text" class="form-control" v-model="campito.fileCIF"/>
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                        <div class="col-12">
                                                                                                                            <h6>Expedientes Nuevo</h6>
                                                                                                                            <div class="row">
                                                                                                                                <div class="col-12 col-sm-4">
                                                                                                                                    <div class="mb-3">
                                                                                                                                        <label class="form-label">Label o nombre a mostrar <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                                                                                        <input type="text" class="form-control" v-model="campito.expNewConf.label"/>
                                                                                                                                    </div>
                                                                                                                                </div>
                                                                                                                                <div class="col-12 col-sm-3">
                                                                                                                                    <div class="mb-3">
                                                                                                                                        <label class="form-label">Tipo <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                                                                                        <input type="text" class="form-control" v-model="campito.expNewConf.tipo"/>
                                                                                                                                    </div>
                                                                                                                                </div>
                                                                                                                                <div class="col-12 col-sm-3">
                                                                                                                                    <div class="mb-3">
                                                                                                                                        <label class="form-label">Ramo <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                                                                                        <input type="text" class="form-control" v-model="campito.expNewConf.ramo"/>
                                                                                                                                    </div>
                                                                                                                                </div>
                                                                                                                                <div class="col-12 col-sm-2">
                                                                                                                                    <div class="mb-3">
                                                                                                                                        <label class="form-label">Sobreescribir</label>
                                                                                                                                        <select class="form-control" v-model="campito.expNewConf.sobreescribir">
                                                                                                                                            <option value="S">Si</option>
                                                                                                                                            <option value="N">No</option>
                                                                                                                                        </select>
                                                                                                                                    </div>
                                                                                                                                </div>
                                                                                                                                <div class="col-12">
                                                                                                                                    <div class="mb-3">
                                                                                                                                        <h6>Atributos <i class="fas fa-layer-group ms-3 acceptVarsField"></i></h6>
                                                                                                                                        <div v-if="typeof campito.expNewConf.attr !== 'undefined'">
                                                                                                                                            <div v-for="(attrTmp, attrKey) in campito.expNewConf.attr" class="mb-3">
                                                                                                                                                <div class="input-group">
                                                                                                                                                    <input type="text" class="form-control" placeholder="Atributo" v-model="attrTmp.attr">
                                                                                                                                                    <span class="input-group-text">=</span>
                                                                                                                                                    <input type="text" class="form-control" placeholder="Valor" v-model="attrTmp.value">
                                                                                                                                                </div>
                                                                                                                                                <div class="text-end">
                                                                                                                                                    <span @click="campito.expNewConf.attr.splice(attrKey, 1);" class="text-danger cursor-pointer">Eliminar</span>
                                                                                                                                                </div>
                                                                                                                                            </div>
                                                                                                                                        </div>
                                                                                                                                    </div>
                                                                                                                                    <span class="text-success cursor-pointer" @click="() => {
                                                                                                                                if (typeof campito.expNewConf.attr === 'undefined') {
                                                                                                                                    campito.expNewConf.attr = [];
                                                                                                                                }

                                                                                                                                campito.expNewConf.attr.push({
                                                                                                                                    attr: '',
                                                                                                                                    value: '',
                                                                                                                                })
                                                                                                                            }">Agregar nuevo atributo</span>
                                                                                                                                </div>
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                                <div class="col-12" v-if="campito.tipoCampo === 'aprobacion'">
                                                                                                                    <div class="mt-4 text-muted">
                                                                                                                        El campo de aprobación tiene dos valores (aprobado, rechazado). Puede utilizarlos para realizar comparaciones, condicionales y salidas con el identificador del campo.
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                        </div>
                                                                                                        <div v-if="nodeSelection.ocr === 'a' && campito.tipoCampo === 'text' || campito.tipoCampo === 'number' || campito.tipoCampo === 'textArea' || campito.tipoCampo === 'date'  ||  campito.tipoCampo === 'currency' || campito.tipoCampo === 'encrypt'">
                                                                                                            <label class="form-label">Configuración de OCR</label>
                                                                                                            <div>
                                                                                                                <small class="text-muted">Pre llenar si se encuentran las siguientes variables (separadas por coma)</small>
                                                                                                            </div>
                                                                                                            <textarea class="form-control" v-model="campito.ocrConfig" rows="3"/>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                    <div class="row mt-4">
                                                                                                        <div class="col-12 text-end">
                                                                                                            <button @click="()=>{campito.show = false}" class="btn btn-outline-dark me-2">
                                                                                                                <i class="fa fa-angle-up me-2"></i> Cerrar campo
                                                                                                            </button>
                                                                                                            <button v-if="key > 0" @click="()=>{
                                                                                                                        if (key > 0) {
                                                                                                                          const seccion = campito;
                                                                                                                            nodeSelection.formulario.secciones[keySeccion].campos.splice(key, 1);
                                                                                                                            nodeSelection.formulario.secciones[keySeccion].campos.splice(key - 1, 0, seccion);
                                                                                                                        }
                                                                                                                    }"
                                                                                                                    class="btn btn-outline-dark me-2">
                                                                                                                <i class="fa fa-arrow-up me-2"></i> Subir campo
                                                                                                            </button>
                                                                                                            <button @click="()=>{
                                                                                                                        if (key < nodeSelection.formulario.secciones[keySeccion].campos.length - 1) {
                                                                                                                          const seccion = campito;
                                                                                                                           nodeSelection.formulario.secciones[keySeccion].campos.splice(key, 1);
                                                                                                                           nodeSelection.formulario.secciones[keySeccion].campos.splice(key + 1, 0, seccion);
                                                                                                                        }
                                                                                                                    }" class="btn btn-outline-dark me-2">
                                                                                                                <i class="fa fa-arrow-down me-2"></i> Bajar campo
                                                                                                            </button>
                                                                                                            <button @click="()=> {
                                                                                                                    toolbox.confirm('Se eliminará el campo, ¿desea continuar?', function () {
                                                                                                                        const index = nodeSelection.formulario.secciones[keySeccion].campos.indexOf(campito);
                                                                                                                            if (index !== -1) {
                                                                                                                              nodeSelection.formulario.secciones[keySeccion].campos.splice(index, 1);
                                                                                                                            }
                                                                                                                            if(campito.tipoCampo === 'currency'){
                                                                                                                                nodeSelection.formulario.secciones[keySeccion].campos =
                                                                                                                                nodeSelection.formulario.secciones[keySeccion].campos
                                                                                                                                .filter((camp) => ![`${campito.id}_FORMATEADO`,`${campito.id}_MONEDA`].includes(camp.id));
                                                                                                                            }
                                                                                                                    })
                                                                                                                }"
                                                                                                                    class="btn btn-outline-danger me-2">
                                                                                                                <i class="fa fa-trash me-2"></i>
                                                                                                                Campo
                                                                                                            </button>
                                                                                                            <button class="btn btn-outline-danger me-2" @click="duplicarCampo(campito, keySeccion)">
                                                                                                                <i class="fa fa-copy me-2"></i>
                                                                                                                Duplicar
                                                                                                            </button>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                    <hr>
                                                                                    <div class="row mt-5">
                                                                                        <div class="col-12 col-sm-3">
                                                                                            <button @click="addCampo(keySeccion)"
                                                                                                    class="btn btn-secondary">
                                                                                                <i class="fa fa-plus me-2"></i>
                                                                                                Agregar campo
                                                                                            </button>
                                                                                        </div>
                                                                                        <div class="col-12 col-sm-9 text-end">
                                                                                            <button @click="()=>{nodeSelection.formulario.secciones[keySeccion].show = false}" class="btn btn-dark me-2">
                                                                                                <i class="fa fa-angle-up me-2"></i> Cerrar sección
                                                                                            </button>
                                                                                            <button v-if="keySeccion > 0" @click="()=>{
                                                                                                        if (keySeccion > 0) {
                                                                                                          const seccion = nodeSelection.formulario.secciones[keySeccion];
                                                                                                          nodeSelection.formulario.secciones.splice(keySeccion, 1);
                                                                                                          nodeSelection.formulario.secciones.splice(keySeccion - 1, 0, seccion);
                                                                                                        }
                                                                                                    }"
                                                                                                    class="btn btn-dark ms-5">
                                                                                                <i class="fa fa-arrow-up me-2"></i> Subir sección
                                                                                            </button>
                                                                                            <button @click="()=>{
                                                                                                        if (keySeccion < nodeSelection.formulario.secciones.length - 1) {
                                                                                                          const seccion = nodeSelection.formulario.secciones[keySeccion];
                                                                                                          nodeSelection.formulario.secciones.splice(keySeccion, 1);
                                                                                                          nodeSelection.formulario.secciones.splice(keySeccion + 1, 0, seccion);
                                                                                                        }
                                                                                                    }" class="btn btn-dark ms-2">
                                                                                                <i class="fa fa-arrow-down me-2"></i> Bajar sección
                                                                                            </button>
                                                                                            <button class="btn btn-danger ms-2" @click="() => {
                                                                                                const newPaso = JSON.parse(JSON.stringify(paso));
                                                                                               nodeSelection.formulario.secciones.push(newPaso);
                                                                                            }">
                                                                                                <i class="fa fa-clone me-2"></i>
                                                                                                Duplicar
                                                                                            </button>
                                                                                            <button @click="()=> {
                                                                                                 const index = nodeSelection.formulario.secciones.indexOf(paso);
                                                                                                         toolbox.confirm('Está a punto de eliminar una sección incluyendo sus campos, ¿desea continuar?', function () {
                                                                                                            if (index !== -1) {
                                                                                                              nodeSelection.formulario.secciones.splice(index, 1);
                                                                                                            }
                                                                                                        })
                                                                                                    }"
                                                                                                    class="btn btn-danger ms-2">
                                                                                                <i class="fa fa-trash me-2"></i>
                                                                                                Sección
                                                                                            </button>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <hr>
                                                                        <button
                                                                            @click="()=>{
                                                                                    nodeSelection.formulario.secciones.forEach(function (item, key){
                                                                                        nodeSelection.formulario.secciones[key].opened = false;
                                                                                    });

                                                                                     const section =   {
                                                                                        condiciones:[{
                                                                                            campoId:'',
                                                                                            is:'',
                                                                                            value:'',
                                                                                            glue: 'AND'
                                                                                        }],
                                                                                        nombre:'',
                                                                                        instrucciones:'',
                                                                                        campos:[],
                                                                                        opened: true,
                                                                                    }
                                                                                    nodeSelection.formulario.secciones.push(section);
                                                                                }"
                                                                            class="btn btn-outline-dark">
                                                                            <i class="fa fa-plus me-2"></i>
                                                                            Agregar sección
                                                                        </button>

                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-12 mt-5 text-muted">
                                                                <h5><i class="fa fa-question-circle me-2"></i>Ayuda</h5>
                                                                <hr>
                                                                <div class="small">
                                                                    <h6 class="mb-2">Máscaras:</h6>
                                                                    <div class="row">
                                                                        <div class="col-12 col-sm-3">
                                                                            <b>Tokens reservados:</b><br>
                                                                            #: Dígitos ##/##/#####<br>
                                                                            @: Letras<br>
                                                                            *: Letras y números<br/>
                                                                            !: Escape de símbolos
                                                                        </div>
                                                                        <div class="col-12 col-sm-5">
                                                                            <b>Ejemplos</b><br/>
                                                                            (tel): +1 ### ###-##-##<br/>
                                                                            (hex): !#HHHHHH PATRON:
                                                                            <i>H:[0-9a-fA-F]</i><br/><br/>
                                                                        </div>
                                                                        <div class="col-12 col-sm-4">
                                                                            <b>Modificadores (ejemplos)</b><br/>
                                                                            (ip): #00.#00.#00.#00 -> 0:[0-9]:<b>optional</b><br/>
                                                                            (nombre): A A -> A:[A-Z]:<b>multiple</b><br/>
                                                                            (moneda): 9 99#,## -> 9:[0-9]:<b>repeated</b><br/>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="small mt-3">
                                                                    <h6 class="mb-2">Variables por defecto:</h6>
                                                                    <div class="row">
                                                                        <div class="col-12">
                                                                            <b>FECHA_COTIZACION:</b> Imprime la fecha en la cual se realizó la cotización<br>
                                                                            <b>FECHA_HOY:</b> Imprime la fecha del día<br>
                                                                            <b>FECHA_EMISION:</b> Imprime la fecha de emisión<br>
                                                                            <b>ID_COTIZACION:</b> Imprime el identificador de la cotización<br>
                                                                            <b>CREADOR_NOMBRE:</b> Imprime el nombre del usuario creador (solo nodos privados)<br>
                                                                            <b>CREADOR_NOMBRE_USUARIO:</b> Imprime el usuario del usuario creador (solo nodos privados)<br>
                                                                            <b>CREADOR_CORP:</b> Imprime el corporativo del usuario creador (solo nodos privados)<br>
                                                                            <b>CREADOR_CANAL:</b> Imprime los canales del usuario creador (solo nodos privados)<br>
                                                                            <b>CREADOR_CANAL_CODIGO_INTERNO:</b> Imprime los codigos internos de los canales del usuario creador (solo nodos privados)<br>
                                                                            <b>CREADOR_DISTRIBUIDOR:</b> Imprime los distribuidores del usuario creador (solo nodos privados)<br>
                                                                            <b>CREADOR_TIENDA:</b> Imprime las tiendas del usuario creador (solo nodos privados)<br>
                                                                            <b>CREADOR_EJECUTIVO:</b> Imprime los ejecutivo del usuario creador (solo nodos privados)<br>
                                                                            <b>LINK_FORM:</b> Imprime el enlace de la cotización<br>
                                                                            <b>ESTADO_ACTUAL:</b> Imprime el estado de la cotización<br>
                                                                            <b>veh[NUMERO_VEHICULO]|[CAMPO_ID]</b> Imprime la informacion por vehiculo. Ej. veh1|datos_vehiculo_color<br>
                                                                            <b>cot[NUMERO_COTIZACION]|[CAMPO_ID]</b> Imprime la informacion por cotizacion. Ej. cot1|COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaCoberturas.coberturas.12.descripcion<br>
                                                                            <br>
                                                                            <h6 class="text-info"><strong>DATA VEHÍCULO:</strong></h6>
                                                                            <p>Eludir el [[NUMERO_VEHICULO]] si la generación es a nivel de vehículo</p>
                                                                            <b>veh[NUMERO_VEHICULO]|id</b> Imprime la informacion por vehiculo (id).<br>
                                                                            <b>veh[NUMERO_VEHICULO]|marca</b> Imprime la informacion por vehiculo (marca).<br>
                                                                            <b>veh[NUMERO_VEHICULO]|linea</b> Imprime la informacion por vehiculo (linea).<br>
                                                                            <b>veh[NUMERO_VEHICULO]|tipo</b> Imprime la informacion por vehiculo (tipo).<br>
                                                                            <b>veh[NUMERO_VEHICULO]|noPasajeros</b> Imprime la informacion por vehiculo (noPasajeros).<br>
                                                                            <b>veh[NUMERO_VEHICULO]|noChasis</b> Imprime la informacion por vehiculo (noChasis).<br>
                                                                            <b>veh[NUMERO_VEHICULO]|noMotor</b> Imprime la informacion por vehiculo (noMotor).<br>
                                                                            <b>veh[NUMERO_VEHICULO]|modelo</b> Imprime la informacion por vehiculo (modelo).<br>
                                                                            <b>veh[NUMERO_VEHICULO]|valorProm</b> Imprime la informacion por vehiculo (valorProm).<br>
                                                                            <b>veh[NUMERO_VEHICULO]|valorPromDef</b> Imprime la informacion por vehiculo (valorPromDef).<br>
                                                                            <b>veh[NUMERO_VEHICULO]|placa</b> Imprime la informacion por vehiculo (placa).<br>
                                                                            <b>veh[NUMERO_VEHICULO]|vehiculoNuevo</b> Imprime la informacion por vehiculo (vehiculo Nuevo).<br>
                                                                            <br>
                                                                            <h6 class="text-info"><strong>DATA COTIZACIÓN:</strong></h6>
                                                                            <p>Eludir el [[NUMERO_COTIZACION]] si la generación es a nivel de cotización</p>
                                                                            <p>Agregar la preposición siguiente en la variable: (eludirla en caso la cotización sea a nivel de cotización)</p>
                                                                            <p><b>veh[NUMERO_VEHICULO] |</b></p>
                                                                            <div :style="{marginLeft: '20px'}">
                                                                                <b>cot[NUMERO_COTIZACION]|id</b> Imprime la informacion por cotizacion (id).<br>
                                                                                <b>cot[NUMERO_COTIZACION]|producto</b> Imprime la informacion por cotizacion (producto).<br>
                                                                                <b>cot[NUMERO_COTIZACION]|tarifa</b> Imprime la informacion por cotizacion (tarifa).<br>
                                                                                <b>cot[NUMERO_COTIZACION]|descuentoPorcentaje</b> Imprime la informacion por cotizacion (descuento Porcentaje).<br>
                                                                                <b>cot[NUMERO_COTIZACION]|formaPago</b> Imprime la informacion por cotizacion (forma Pago).<br>
                                                                                <b>cot[NUMERO_COTIZACION]|numeroPagos</b> Imprime la informacion por cotizacion (numero Pagos).<br>
                                                                                <b>cot[NUMERO_COTIZACION]|primaNeta</b> Imprime la informacion por cotizacion (prima Neta).<br>
                                                                                <b>cot[NUMERO_COTIZACION]|primaTotal</b> Imprime la informacion por cotizacion (prima Total).<br>
                                                                                <b>cot[NUMERO_COTIZACION]|recargoPorcentaje</b> Imprime la informacion por cotizacion (recargo).<br>
                                                                                <b>cot[NUMERO_COTIZACION]|emitirPoliza</b> Imprime la informacion por cotizacion (emitir poliza).<br>
                                                                                <b>cot[NUMERO_COTIZACION]|numeroCotizacionAS400</b> Imprime la informacion por cotizacion (numero cotizacion desde el AS400).<br>
                                                                                <b>cot[NUMERO_COTIZACION]|idCorrelativo</b> Imprime la informacion por cotizacion (correlativo id).<br>
                                                                                <b>cot[NUMERO_COTIZACION]|cob[NUMERO_COBERTURA]|cobertura</b> Imprime la informacion por cobertura (nombre cobertura).<br>
                                                                                <b>cot[NUMERO_COTIZACION]|cob[NUMERO_COBERTURA]|monto</b> Imprime la informacion por cobertura (monto).<br>
                                                                                <b>cot[NUMERO_COTIZACION]|cob[NUMERO_COBERTURA]|codigoCobertura</b> Imprime la informacion por cobertura (codigo cobertura).<br>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3" v-if="nodeSelection.typeObject === 'process'">
                                                        <div>
                                                            <div class="text-muted">
                                                                Tipo de proceso:
                                                            </div>
                                                            <CNav variant="pills" class="mt-2 mb-3">
                                                                <CNavItem @click="nodeSelection.startFrom = 'ws';">
                                                                    <CButton color="primary" :active="nodeSelection.startFrom === 'ws'"> Consumo de servicio web</CButton>
                                                                </CNavItem>
                                                            </CNav>
                                                            <hr>
                                                        </div>
                                                        <div v-if="nodeSelection.startFrom === 'ws'">
                                                            <div v-if="nodeSelection.typeObject === 'process'" class="mb-2">
                                                                <button class="btn btn-primary btn-sm me-2" color="primary" @click="() => { visibleSideBar = !visibleSideBar }">
                                                                    <i class="fa fa-square-envelope"></i> Configurar envío
                                                                </button>
                                                                <button class="btn btn-sm btn-secondary" @click="() => { visibleSideBarLeft = !visibleSideBarLeft }">
                                                                    <i class="fa fa-envelope-open-text"></i> Prueba de servicios
                                                                </button>
                                                            </div>
                                                            <hr>
                                                            <div class="row" v-for="(proceso,indexP) in nodeSelection.procesos">
                                                                <div class="col-12">
                                                                    <h4 class="mb-3">Configuración de proceso</h4>
                                                                    <div class="mb-3">
                                                                        <div>
                                                                            <label class="form-label">Identificador</label>
                                                                            <input v-model="proceso.identificadorWs" class="form-control"/>
                                                                        </div>
                                                                        <div class="mt-2">
                                                                            <label class="form-label">Tipo de consumo</label>
                                                                            <select v-model="proceso.method" class="form-control">
                                                                                <option value="post">POST</option>
                                                                                <option value="get">GET</option>
                                                                                <option value="put">PUT</option>
                                                                                <option value="patch">PATCH</option>
                                                                                <option value="delete">DELETE</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="mt-2">
                                                                            <CFormInput
                                                                                type="text"
                                                                                v-model="proceso.url"
                                                                                id="exampleFormControlInput1"
                                                                                label="Url a consumir"
                                                                                placeholder="https://una-url-ejemplo.com/ws"
                                                                            />
                                                                        </div>
                                                                        <div class="mt-4">
                                                                            <CFormSwitch label="Parsear respuesta como XML" v-model="proceso.respuestaXML" id="formSwitchCheckDefault"/>
                                                                        </div>
                                                                        <div class="mt-2">
                                                                            <CFormSwitch label="Ejecutar proceso por cada vehículo de la cotización (si aplica)" v-model="proceso.execVehi" id="formSwitchexecVehi"/>
                                                                        </div>
                                                                        <div class="mt-2">
                                                                            <CFormSwitch label="Habilitar paso con excepción" v-model="proceso.manErrE" id="manejoErroresExcepcion"/>
                                                                            <CFormSwitch label="Manejo de errores personalizado" v-model="proceso.manErrP" id="manejoErroresPersonalizado"/>
                                                                            <div class="mt-3" v-if="proceso.manErrP">
                                                                                <label>Configuración de errores personalizados</label>
                                                                                <textarea v-model="proceso.manErrC" class="form-control" style="width: 100%; min-height: 100px"></textarea>
                                                                                <div>
                                                                                    Ejemplo:
                                                                                    <pre>
[
    {
        "campo_ws": "{{proceso.identificadorWs}}.empresa.mensaje",
        "tomarSatisfactorio": "SATISFACTORIO",
        "textoSatisfactorio": "mensaje ok",
        "textoError": "mensaje malo",
        "wkToken": "",
        "procesoOnError": ""
    }
]
                                                                                    </pre>
                                                                                    <b>La salida de variables será:</b><br>
                                                                                    {{proceso.identificadorWs}}.WSEXEC.nodo, (Id de nodo de proceso)<br>
                                                                                    {{proceso.identificadorWs}}.WSEXEC.status, (1 satisfactorio, 0 fallido)<br>
                                                                                    {{proceso.identificadorWs}}.WSEXEC.codigoHttp, (200, 500, etc)<br>
                                                                                    {{proceso.identificadorWs}}.WSEXEC.msg, (mensaje tomado de configuración según status)<br><br>

                                                                                    <b>El flujo de workflow envía los siguientes campos:</b><br><br>
                                                                                    mensaje, campo que incluye el mensaje personalizado (dependiendo si fue satisfactorio o con error)<br>
                                                                                    status, campo que incluye el estatus del servicio (dependiendo si fue satisfactorio o con error)<br>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="mt-4">
                                                                        <h6>Identificadores de sistema</h6>
                                                                        <ul>
                                                                            <li>
                                                                                COTIZACION_AS400
                                                                            </li>
                                                                            <li>
                                                                                EMISION_DATOS_CLIENTE_AS400
                                                                            </li>
                                                                        </ul>
                                                                    </div>
                                                                </div>
                                                                <COffcanvas placement="start" :visible="visibleSideBar" @hide="() => { visibleSideBar = !visibleSideBar }">
                                                                    <COffcanvasHeader>
                                                                        <COffcanvasTitle>Configuración de consumo (envío de datos)</COffcanvasTitle>
                                                                        <CCloseButton class="text-reset" @click="() => { visibleSideBar = false }"/>
                                                                    </COffcanvasHeader>
                                                                    <COffcanvasBody>
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Tipo de autenticación</label>
                                                                            <select v-model="proceso.authType" class="form-control">
                                                                                <option value="ninguna">Sin autenticación</option>
                                                                                <option value="elroble">El Roble</option>
                                                                                <option value="bearer">Bearer Token</option>
                                                                            </select>
                                                                            <div v-if="proceso.authType === 'bearer'" class="mt-2">
                                                                                <label class="form-label">Token a enviar</label>
                                                                                <input type="text" class="form-control" v-model="proceso.bearerToken">
                                                                            </div>
                                                                            <div v-if="proceso.authType === 'elroble'" class="mt-2">
                                                                                <div>
                                                                                    <label class="form-label">Url autenticación</label>
                                                                                    <input type="text" class="form-control" v-model="proceso.authUrl">
                                                                                </div>
                                                                                <div class="mt-2">
                                                                                    <label class="form-label">Payload</label>
                                                                                    <textarea class="form-control" v-model="proceso.authPayload"></textarea>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <CCard>
                                                                            <CCardHeader><h4 class="m-0">Headers</h4>
                                                                            </CCardHeader>
                                                                            <CCardBody>
                                                                                <CodeEditor :languages="[['json', 'JSON'],['xml', 'XML']]" border-radius="4px" style="border: 1px solid var(--cui-primary); width: 100%" theme="vs" v-model="proceso.header"></CodeEditor>
                                                                            </CCardBody>
                                                                        </CCard>
                                                                        <CCard>
                                                                            <CCardHeader>
                                                                                <h4 class="m-0">Pre-formatos de envío</h4>
                                                                            </CCardHeader>
                                                                            <CCardBody>
                                                                                <div class="text-muted mt-1 mb-2">
                                                                                    Puedes agregar pre-formatos de envío para crear de forma dinámica tu formato de envío final. Si una expresión condicional se cumple, la variable contendrá el contenido colocado.
                                                                                </div>
                                                                                <div v-for="(pfTmp, keyPf) in proceso.pf">
                                                                                    <div class="mb-4">
                                                                                        <div class="row">
                                                                                            <div class="col-12 col-sm-6">
                                                                                                <label>Variable (sin caracteres especiales)</label>
                                                                                                <input type="text" class="form-control" placeholder="Nombre de variable" v-model="pfTmp.va">
                                                                                            </div>
                                                                                            <div class="col-12 col-sm-6">
                                                                                                <label>Expresión condicional</label>
                                                                                                <input type="text" class="form-control" placeholder="Ingresa tu expresión condicional" v-model="pfTmp.con">
                                                                                            </div>
                                                                                        </div>
                                                                                        <label>Contenido</label>
                                                                                        <textarea class="form-control" style="min-height: 100px" v-model="pfTmp.c"></textarea>
                                                                                        <div class="text-end mt-1">
                                                                                            <a class="text-danger cursor-pointer" @click="() => {
                                                                                                         proceso.pf.splice(keyPf, 1);
                                                                                                    }">- Eliminar</a>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="text-end mt-2">
                                                                                    <a class="text-success cursor-pointer" @click="() => {
                                                                                                if (typeof proceso.pf === 'undefined') proceso.pf = [];
                                                                                                 proceso.pf.push({
                                                                                                    va: '',
                                                                                                    con: '',
                                                                                                    c: '',
                                                                                                 })
                                                                                            }">+ Agregar pre-formato</a>
                                                                                </div>
                                                                            </CCardBody>
                                                                        </CCard>
                                                                        <CCard>
                                                                            <CCardHeader>
                                                                                <h4 class="m-0">Formato de envío final</h4>
                                                                            </CCardHeader>
                                                                            <CCardBody>
                                                                                <div class="text-muted mt-1 mb-2">
                                                                                    Ingresa el XML o JSON a enviar
                                                                                </div>
                                                                                <CodeEditor :languages="[['json', 'JSON'],['xml', 'XML']]" border-radius="4px" style="border: 1px solid var(--cui-primary); width: 100%" theme="vs" v-model="proceso.entrada"></CodeEditor>
                                                                            </CCardBody>
                                                                        </CCard>
                                                                        <CCard>
                                                                            <CCardHeader>
                                                                                <h4 class="m-0">Variables disponibles</h4>
                                                                            </CCardHeader>
                                                                            <CCardBody>
                                                                                <div class="mb-3">
                                                                                    <input class="form-control" type="text" v-model="variableSearch" placeholder="Escribe aquí para buscar">
                                                                                </div>
                                                                                <EasyDataTable v-if="allFields" :headers="preparedHeaders(allFields,false, ['isArray', 'valor'])"
                                                                                               :items="preparedItems(allFields)"
                                                                                               alternating
                                                                                               :search-field="preparedHeadersSearcg(allFields,false, ['isArray', 'valor'])"
                                                                                               :search-value="variableSearch"
                                                                                               rows-per-page-message="filas por página"
                                                                                               rows-of-page-separator-message="de">
                                                                                </EasyDataTable>
                                                                            </CCardBody>
                                                                        </CCard>
                                                                        <CCard>
                                                                            <CCardHeader>
                                                                                <h4 class="m-0">Información sobre operadores condicionales</h4>
                                                                            </CCardHeader>
                                                                            <CCardBody>
                                                                                <div class="mb-3">
                                                                                    <input class="form-control" type="text" v-model="variableSearch" placeholder="Escribe aquí para buscar">
                                                                                </div>
                                                                                <div>
                                                                                    <h6 class="text-primary">Operadores aritméticos</h6>
                                                                                    +: suma<br>
                                                                                    -: resta<br>
                                                                                    *: multiplicación<br>
                                                                                    /: division<br>
                                                                                    %: módulo (a*b%c*d == (a*b)%(c*d))<br>
                                                                                    **: potencia (a*b**c*d == (a*b)**(c*d))<br>

                                                                                    <h6 class="text-primary mt-2">Operadores de comparación</h6>
                                                                                    ===: igual comparación estricta (tipo de dato)<br>
                                                                                    !==: no es igual comparación estricta (tipo de dato)<br>
                                                                                    ==: igualdad<br>
                                                                                    !=: no es igual<br>
                                                                                    >: mayor que<br>
                                                                                    &lt;: menor que<br>
                                                                                    >=: mayor o igual que<br>
                                                                                    &lt;=: menor o igual que<br>

                                                                                    <h6 class="text-primary mt-2">Operadores lógicos</h6>
                                                                                    &&: Y o AND<br>
                                                                                    ||: OR o Ó<br>
                                                                                    !: NO o NOT<br>

                                                                                    <h6 class="text-primary mt-2">Concatenación</h6>
                                                                                    ~: concatenar cadenas<br>

                                                                                    <h6 class="text-primary mt-2">Expresiones ternarias</h6>
                                                                                    a ? b : c<br>
                                                                                    a ?: b (es equivalente ? a : b)<br>
                                                                                    a ? b (es equivalente ? b : null)<br>
                                                                                </div>
                                                                            </CCardBody>
                                                                        </CCard>
                                                                    </COffcanvasBody>
                                                                </COffcanvas>
                                                                <COffcanvas placement="end" :visible="visibleSideBarLeft" @hide="() => { visibleSideBarLeft = !visibleSideBarLeft }">
                                                                    <COffcanvasHeader>
                                                                        <CButton
                                                                            class="p-1 m-auto"
                                                                            color="success"
                                                                            @click="() => {
                                                                                         toolbox.doAjax('POST', 'flujos/prueba', {
                                                                                             authUrl: proceso.authUrl,
                                                                                             authPayload: proceso.authPayload,
                                                                                             authType: proceso.authType,
                                                                                             entrada: proceso.entrada,
                                                                                             url: proceso.url,
                                                                                             header: proceso.header,
                                                                                             respuestaXML: proceso.respuestaXML,
                                                                                             execVehi: proceso.execVehi,
                                                                                             manErrC: proceso.manErrC || '',
                                                                                             manErrP: proceso.manErrP || '',
                                                                                             manErrE: proceso.manErrE || '',
                                                                                             metodo: proceso.method,
                                                                                             identificadorWs: proceso.identificadorWs,
                                                                                             campos: datosPruebas,
                                                                                             tokenAuth: proceso.bearerToken,
                                                                                         }, function (response) {
                                                                                             toolbox.alert(response.msg, 'info');
                                                                                             respuestaWsLog = (typeof response.data.log !== 'undefined' ? response.data.log : {});
                                                                                         }, function (response) {
                                                                                             toolbox.alert(response.msg, 'danger');
                                                                                         });
                                                                                    }">
                                                                            <i v-if="proceso.header" class="fa fa-paper-plane me-2"></i> Realizar petición de prueba
                                                                        </CButton>
                                                                        <CCloseButton class="text-reset" @click="() => { visibleSideBarLeft = false }"/>
                                                                    </COffcanvasHeader>
                                                                    <COffcanvasBody>
                                                                        <CCard>
                                                                            <CCardBody>
                                                                                <h3>Prueba de servicio</h3>
                                                                                <div class="text-muted">
                                                                                    Esta herramienta permite probar tus servicios sin necesidad de herramientas externas
                                                                                </div>
                                                                                <div>
                                                                                    <div v-if="parseFormData(proceso.header).length > 0">
                                                                                        <h5 class="my-3">Cabeceras o Headers</h5>
                                                                                        <CRow class="align-items-start">
                                                                                            <CCol :xs="6" v-for="(campoPrueba, indexPrueba) in parseFormData(proceso.header)">
                                                                                                <CFormInput v-model="datosPruebas[indexPrueba].value" :label="campoPrueba.label"/>
                                                                                            </CCol>
                                                                                        </CRow>
                                                                                    </div>
                                                                                    <div class="mt-2" v-if="parseFormData(proceso.entrada)">
                                                                                        <h5 class="my-3">Campos a enviar</h5>
                                                                                        <CRow class="align-items-start">
                                                                                            <CCol :xs="6" v-for="(campoPrueba, indexPrueba) in parseFormData(proceso.entrada)">
                                                                                                <!--<CFormInput @change="()=>{ datosPruebas[indexPrueba] = this.value; }" v-model="campoPrueba.value" :label="campoPrueba.label"/>-->
                                                                                                <CFormInput @change="changeValueInlinee(indexPrueba, $event)" :label="campoPrueba.label" :v-model="campoPrueba.value"/>
                                                                                            </CCol>
                                                                                        </CRow>
                                                                                    </div>
                                                                                    <div class="mt-2">
                                                                                        <h5 class="my-3">Cabeceras enviadas</h5>
                                                                                        <div>
                                                                                            <textarea class="form-control" style="min-height: 300px" v-model="respuestaWsLog.enviadoH"></textarea>
                                                                                        </div>
                                                                                        <h5 class="my-3">Payload enviado</h5>
                                                                                        <div>
                                                                                            <textarea class="form-control" style="min-height: 300px" v-model="respuestaWsLog.enviado"></textarea>
                                                                                        </div>
                                                                                        <h5 class="my-3">Respuesta plana</h5>
                                                                                        <div>
                                                                                            <textarea class="form-control" style="min-height: 300px" v-model="respuestaWsLog.recibido"></textarea>
                                                                                        </div>
                                                                                        <h5 class="my-3">Respuesta procesada</h5>
                                                                                        <div>
                                                                                            <textarea class="form-control" style="min-height: 300px" v-model="respuestaWsLog.data"></textarea>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </CCardBody>
                                                                        </CCard>
                                                                    </COffcanvasBody>
                                                                </COffcanvas>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3" v-if="nodeSelection.typeObject === 'output'">
                                                        <h6>Configuración de salida de datos</h6>
                                                        <div class="text-muted">
                                                            <div>
                                                                Para salida de datos de tipo HTML o PDF, utilice prefijos y sufijos de tipo "&lbrace;&lbrace; &rbrace;&rbrace;", por ejemplo &lbrace;&lbrace;VARIABLE&rbrace;&rbrace;
                                                            </div>
                                                            <div>
                                                                Para salida de datos de tipo correo electrónico, utilice prefijos y sufijos de tipo "&lbrace;&lbrace; &rbrace;&rbrace;", por ejemplo &lbrace;&lbrace;VARIABLE&rbrace;&rbrace;
                                                            </div>
                                                        </div>
                                                        <div class="row mt-3">
                                                            <div class="mb-5">
                                                                <h5>Listado de variables disponibles</h5>
                                                                <EasyDataTable v-if="allFields" :headers="preparedHeaders(allFields, false, ['isArray', 'valor'])"
                                                                               :items="preparedItems(allFields)"
                                                                               alternating
                                                                               rows-per-page-message="filas por página"
                                                                               rows-of-page-separator-message="de"
                                                                               table-height="200"
                                                                >
                                                                </EasyDataTable>
                                                            </div>
                                                            <div class="col-12">
                                                                <div class="mb-5">
                                                                    <h5>Salida en pantalla (HTML)</h5>
                                                                    <hr>
                                                                    <div>
                                                                        <CFormSwitch label="Habilitar salida en pantalla" v-model="nodeSelection.salidaIsHTML"/>
                                                                    </div>
                                                                    <div>
                                                                        <div class="mb-3">
                                                                            <Editor
                                                                                :init="{
                                                                                            plugins: plugins,
                                                                                            toolbar: toolbar,
                                                                                            language: language,
                                                                                            promotion: false,
                                                                                            branding: false
                                                                                        }"
                                                                                v-model="nodeSelection.salidas"
                                                                                tinymce-script-src="https://tinyroble.s3.amazonaws.com/tinymce/tinymce.min.js"
                                                                            />
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="mb-5">
                                                                    <h5>Salida en PDF</h5>
                                                                    <hr>
                                                                    <div>
                                                                        <CFormSwitch label="Habilitar salida PDF" v-model="nodeSelection.salidaIsPDF"/>
                                                                    </div>
                                                                    <div v-if="nodeSelection.salidaIsPDF">
                                                                        <div>
                                                                            <label>Selecciona la plantilla PDF a utilizar</label>
                                                                            <multiselect
                                                                                v-model="nodeSelection.pdfTpl"
                                                                                :options="pdfTpl"
                                                                                :searchable="true"/>
                                                                        </div>
                                                                        <div class="mt-3">
                                                                            <label class="form-label">Plantilla PDF desde +Docs</label>
                                                                            <div class="text-muted">Coloque el token del formulario deseado para +Docs, deje vacío para no utilizar esta opción</div>
                                                                            <input type="text" class="form-control" placeholder="Escribe aquí" v-model="nodeSelection.salidaPDFDp">
                                                                        </div>
                                                                        <div class="mt-3">
                                                                            <label class="form-label">Identificador de salida</label>
                                                                            <div class="text-muted">Coloque el identificador del achivo, este no debe repetirse a lo largo del flujo</div>
                                                                            <input type="text" class="form-control" placeholder="Escribe aquí" v-model="nodeSelection.salidaPDFId">
                                                                        </div>
                                                                        <div class="mt-3">
                                                                            <label class="form-label">Agrupar por</label>
                                                                            <CFormSelect v-model="nodeSelection.salidaPDFGroup">
                                                                                <option value="">-- Seleccione un campo --</option>
                                                                                <!--<option value="veh">Vehículo</option>-->
                                                                                <option value="cot">Cotización</option>
                                                                                <option value="cot_emi">Cotización emitida</option>
                                                                            </CFormSelect>
                                                                        </div>
                                                                        <div class="mt-3" v-if="nodeSelection.salidaPDFId && nodeSelection.salidaPDFId !== ''">
                                                                            <label class="form-label">JSON a enviar a Docs+ <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                            <textarea type="text" class="form-control" style="min-height: 300px" v-model="nodeSelection.salidaPDFconf.jsonSend"></textarea>
                                                                        </div>
                                                                        <div class="row mt-3">
                                                                            <div class="col-12">
                                                                                <h6 class="mb-3">Campos para índice</h6>
                                                                                <div class="col-12 col-sm-6">
                                                                                    <div class="mb-3">
                                                                                        <label class="form-label">Directorio o Path (S3) <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                                        <input type="text" class="form-control" v-model="nodeSelection.salidaPDFconf.path"/>
                                                                                        <div class="text-muted">
                                                                                            Ingrese el directorio donde se almacenará el archivo
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div>
                                                                                    <h6>Expedientes Nuevo</h6>
                                                                                    <div class="row">
                                                                                        <div class="col-12 col-sm-4">
                                                                                            <div class="mb-3">
                                                                                                <label class="form-label">Label o nombre a mostrar <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                                                <input type="text" class="form-control" v-model="nodeSelection.salidaPDFconf.expNewConf.label"/>
                                                                                            </div>
                                                                                        </div>
                                                                                        <div class="col-12 col-sm-3">
                                                                                            <div class="mb-3">
                                                                                                <label class="form-label">Tipo <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                                                <input type="text" class="form-control" v-model="nodeSelection.salidaPDFconf.expNewConf.tipo"/>
                                                                                            </div>
                                                                                        </div>
                                                                                        <div class="col-12 col-sm-3">
                                                                                            <div class="mb-3">
                                                                                                <label class="form-label">Ramo <i class="fas fa-layer-group ms-3 acceptVarsField"></i></label>
                                                                                                <input type="text" class="form-control" v-model="nodeSelection.salidaPDFconf.expNewConf.ramo"/>
                                                                                            </div>
                                                                                        </div>
                                                                                        <div class="col-12 col-sm-2">
                                                                                            <div class="mb-3">
                                                                                                <label class="form-label">Sobreescribir</label>
                                                                                                <select class="form-control" v-model="nodeSelection.salidaPDFconf.expNewConf.sobreescribir">
                                                                                                    <option value="S">Si</option>
                                                                                                    <option value="N">No</option>
                                                                                                </select>
                                                                                            </div>
                                                                                        </div>
                                                                                        <div class="col-12">
                                                                                            <div class="mb-3">
                                                                                                <h6>Atributos <i class="fas fa-layer-group ms-3 acceptVarsField"></i></h6>
                                                                                                <div v-if="typeof nodeSelection.salidaPDFconf.expNewConf.attr !== 'undefined'">
                                                                                                    <div v-for="(attrTmp, attrKey) in nodeSelection.salidaPDFconf.expNewConf.attr" class="mb-3">
                                                                                                        <div class="input-group">
                                                                                                            <input type="text" class="form-control" placeholder="Atributo" v-model="attrTmp.attr">
                                                                                                            <span class="input-group-text">=</span>
                                                                                                            <input type="text" class="form-control" placeholder="Valor" v-model="attrTmp.value">
                                                                                                        </div>
                                                                                                        <div class="text-end">
                                                                                                            <span @click="nodeSelection.salidaPDFconf.expNewConf.attr.splice(attrKey, 1);" class="text-danger cursor-pointer">Eliminar</span>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                            <span class="text-success cursor-pointer" @click="() => {
                                                                                                                                if (typeof nodeSelection.salidaPDFconf.expNewConf.attr === 'undefined') {
                                                                                                                                    nodeSelection.salidaPDFconf.expNewConf.attr = [];
                                                                                                                                }

                                                                                                                                nodeSelection.salidaPDFconf.expNewConf.attr.push({
                                                                                                                                    attr: '',
                                                                                                                                    value: '',
                                                                                                                                })
                                                                                                                            }">Agregar nuevo atributo</span>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="mb-5">
                                                                    <h5>Salida de correo electrónico</h5>
                                                                    <hr>
                                                                    <div>
                                                                        <CFormSwitch label="Habilitar salida de correo" v-model="nodeSelection.salidaIsEmail"/>
                                                                    </div>
                                                                    <div v-if="nodeSelection.salidaIsEmail">
                                                                        <h6 class="text-primary">Salida Mailgun</h6>
                                                                        <div class="row mb-4">
                                                                            <div class="col-12 col-sm-4">
                                                                                <label class="form-label">API Key</label>
                                                                                <input type="text" class="form-control" placeholder="Escribe aquí" v-model="nodeSelection.procesoEmail.mailgun.apiKey">
                                                                            </div>
                                                                            <div class="col-12 col-sm-4">
                                                                                <label class="form-label">Dominio</label>
                                                                                <input type="text" class="form-control" placeholder="Escribe aquí" v-model="nodeSelection.procesoEmail.mailgun.domain">
                                                                            </div>
                                                                            <div class="col-12 col-sm-4">
                                                                                <label class="form-label">Enviar desde (from)</label>
                                                                                <input type="text" class="form-control" placeholder="Escribe aquí" v-model="nodeSelection.procesoEmail.mailgun.from">
                                                                            </div>
                                                                        </div>
                                                                        <div class="row mb-4">
                                                                            <div class="col-12 col-sm-4">
                                                                                <CFormSwitch label="Deshabilitar salida automática" v-model="nodeSelection.procesoEmail.autoSend"/>
                                                                                <div class="text-muted">
                                                                                    * Si no activa el reenvío, solo se intentará enviar una vez al iniciar el de salida.
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="row mb-4">
                                                                            <div class="col-12 col-sm-4">
                                                                                <CFormSwitch label="Habilitar reenvío" v-model="nodeSelection.procesoEmail.reenvio"/>
                                                                                <div class="text-muted">
                                                                                    * Si no activa el reenvío, solo se intentará enviar una vez al iniciar el de salida.
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div>
                                                                            <h4 class="mb-4">Configuración de envío</h4>
                                                                            <h5>Dirección de envío</h5>
                                                                            <input class="form-control mb-1" v-model="nodeSelection.procesoEmail.destino"/>
                                                                            <h5>Asunto</h5>
                                                                            <input class="form-control mb-1" v-model="nodeSelection.procesoEmail.asunto"/>
                                                                            <CInputGroup class="mb-3" v-for="(copia, indexC) in nodeSelection.procesoEmail.copia">
                                                                                <CFormInput v-model="copia.destino" placeholder="Cc" aria-label="Cc" :aria-describedby="'Cc'+indexC"/>
                                                                                <CButton type="button" @click="()=>{nodeSelection.procesoEmail.copia.splice(indexC, 1);}" color="secondary" variant="outline" :id="'Cc'+indexC">-</CButton>
                                                                                <CButton type="button" @click="()=>{nodeSelection.procesoEmail.copia.push({ destino: '' });}" color="primary" variant="outline" :id="'Cc'+indexC">+</CButton>
                                                                            </CInputGroup>
                                                                            <div class="mb-3">
                                                                                <Editor
                                                                                    :init="{
                                                                                                plugins: plugins,
                                                                                                toolbar: toolbar,
                                                                                                language: language,
                                                                                                promotion: false,
                                                                                                branding: false
                                                                                            }"
                                                                                    v-model="nodeSelection.procesoEmail.salidasEmail"
                                                                                    tinymce-script-src="https://tinyroble.s3.amazonaws.com/tinymce/tinymce.min.js"
                                                                                />
                                                                            </div>
                                                                            <div class="mt-3">
                                                                                <label class="form-label">Identificadores de adjuntos</label>
                                                                                <div class="text-muted">Puede colocar identificadores de salidas PDF o identificadores de campos de tipo archivo</div>
                                                                                <input type="text" class="form-control" placeholder="Escribe aquí" v-model="nodeSelection.procesoEmail.attachments">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div>
                                                                    <h5>Salida de Whatsapp</h5>
                                                                    <hr>
                                                                    <div>
                                                                        <CFormSwitch label="Habilitar salida de Whatsapp" v-model="nodeSelection.salidaIsWhatsapp"/>
                                                                    </div>
                                                                    <div v-if="nodeSelection.salidaIsWhatsapp">
                                                                        <h6 class="text-primary">Salida Mailgun</h6>
                                                                        <div class="row mb-4">
                                                                            <div class="col-12 col-sm-4 mb-4">
                                                                                <label class="form-label">Whatsapp Url</label>
                                                                                <input type="text" class="form-control" placeholder="Escribe aquí" v-model="nodeSelection.procesoWhatsapp.url">
                                                                            </div>
                                                                            <div class="col-12 col-sm-4 mb-4">
                                                                                <label class="form-label">Whatsapp Token</label>
                                                                                <input type="text" class="form-control" placeholder="Escribe aquí" v-model="nodeSelection.procesoWhatsapp.token">
                                                                            </div>
                                                                            <div class="col-12 col-sm-4 mb-4">
                                                                                <CFormSwitch label="Desactivar salida automática" v-model="nodeSelection.procesoWhatsapp.autoSend"/>
                                                                            </div>
                                                                            <div class="col-12 col-sm-4 mb-4">
                                                                                <CFormSwitch label="Habilitar reenvío" v-model="nodeSelection.procesoWhatsapp.reenvio"/>
                                                                                <div class="text-muted">
                                                                                    * Si no activa el reenvío, solo se intentará enviar una vez al iniciar el de salida.
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div>
                                                                            <h4 class="mb-4">Configuración de envío</h4>
                                                                            <div class="mb-3">
                                                                                <textarea class="form-control" v-model="nodeSelection.procesoWhatsapp.data"></textarea>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="mt-5">
                                                                    <h5>Salto Automático</h5>
                                                                    <hr>
                                                                    <div>
                                                                        <CFormSwitch label="Habilitar salto automático" v-model="nodeSelection.saltoAutomatico"/>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="mt-4 text-muted">
                                                            <h6 class="mb-2">Variables por defecto:</h6>
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <b>FECHA_COTIZACION:</b> Imprime la fecha en la cual se realizó la cotización<br>
                                                                    <b>FECHA_HOY:</b> Imprime la fecha del día<br>
                                                                    <b>FECHA_EMISION:</b> Imprime la fecha de emisión<br>
                                                                    <b>SYS_COT_DAY:</b> Imprime el día de la cotización<br>
                                                                    <b>SYS_COT_MONTH:</b> Imprime el mes de la cotización<br>
                                                                    <b>SYS_COT_YEAR:</b> Imprime el año de la cotización<br>
                                                                    <b>ID_COTIZACION:</b> Imprime el identificador de la cotización<br>
                                                                    <b>CREADOR_NOMBRE:</b> Imprime el nombre del usuario creador (solo nodos privados)<br>
                                                                    <b>CREADOR_NOMBRE_USUARIO:</b> Imprime el usuario del usuario creador (solo nodos privados)<br>
                                                                    <b>CREADOR_CORP:</b> Imprime el corporativo del usuario creador (solo nodos privados)<br>
                                                                    <b>CREADOR_CANAL:</b> Imprime los canales del usuario creador (solo nodos privados)<br>
                                                                    <b>CREADOR_CANAL_CODIGO_INTERNO:</b> Imprime los codigos internos de los canales del usuario creador (solo nodos privados)<br>
                                                                    <b>CREADOR_DISTRIBUIDOR:</b> Imprime los distribuidores del usuario creador (solo nodos privados)<br>
                                                                    <b>CREADOR_TIENDA:</b> Imprime las tiendas del usuario creador (solo nodos privados)<br>
                                                                    <b>CREADOR_EJECUTIVO:</b> Imprime los ejecutivo del usuario creador (solo nodos privados)<br>
                                                                    <b>LINK_FORM:</b> Imprime el enlace de la cotización<br>
                                                                    <b>ESTADO_ACTUAL:</b> Imprime el estado de la cotización<br>
                                                                    <b>veh[NUMERO_VEHICULO]|[CAMPO_ID]</b> Imprime la informacion por vehiculo. Ej. veh1|datos_vehiculo_color<br>
                                                                    <b>cot[NUMERO_COTIZACION]|[CAMPO_ID]</b> Imprime la informacion por cotizacion. Ej. cot1|COTIZACION_AS400.datosIdEmpresaGC.datos03.datosCotizacionGestorComercial2.listaCoberturas.coberturas.12.descripcion<br>
                                                                    <b>[IDENTIFICADOR_DE_CAMPO]_fecha_as</b> Imprime la fecha en formato dia/mes/año<br>
                                                                    <b>[IDENTIFICADOR_DE_CAMPO]_fecha_D</b> Imprime el día seleccionado en la fecha<br>
                                                                    <b>[IDENTIFICADOR_DE_CAMPO]_fecha_M</b> Imprime el mes seleccionado en la fecha<br>
                                                                    <b>[IDENTIFICADOR_DE_CAMPO]_fecha_Y</b> Imprime el año seleccionado en la fecha<br>
                                                                    <br>
                                                                    <h6 class="text-info"><strong>DATA VEHÍCULO:</strong></h6>
                                                                    <p>Eludir el [[NUMERO_VEHICULO]] si la generación es a nivel de vehículo</p>
                                                                    <b>veh[NUMERO_VEHICULO]|id</b> Imprime la informacion por vehiculo (id).<br>
                                                                    <b>veh[NUMERO_VEHICULO]|marca</b> Imprime la informacion por vehiculo (marca).<br>
                                                                    <b>veh[NUMERO_VEHICULO]|linea</b> Imprime la informacion por vehiculo (linea).<br>
                                                                    <b>veh[NUMERO_VEHICULO]|tipo</b> Imprime la informacion por vehiculo (tipo).<br>
                                                                    <b>veh[NUMERO_VEHICULO]|noPasajeros</b> Imprime la informacion por vehiculo (noPasajeros).<br>
                                                                    <b>veh[NUMERO_VEHICULO]|noChasis</b> Imprime la informacion por vehiculo (noChasis).<br>
                                                                    <b>veh[NUMERO_VEHICULO]|noMotor</b> Imprime la informacion por vehiculo (noMotor).<br>
                                                                    <b>veh[NUMERO_VEHICULO]|modelo</b> Imprime la informacion por vehiculo (modelo).<br>
                                                                    <b>veh[NUMERO_VEHICULO]|valorProm</b> Imprime la informacion por vehiculo (valorProm).<br>
                                                                    <b>veh[NUMERO_VEHICULO]|valorPromDef</b> Imprime la informacion por vehiculo (valorPromDef).<br>
                                                                    <b>veh[NUMERO_VEHICULO]|placa</b> Imprime la informacion por vehiculo (placa).<br>
                                                                    <b>veh[NUMERO_VEHICULO]|vehiculoNuevo</b> Imprime la informacion por vehiculo (vehiculo Nuevo).<br>
                                                                    <br>
                                                                    <h6 class="text-info"><strong>DATA COTIZACIÓN:</strong></h6>
                                                                    <p>Eludir el [[NUMERO_COTIZACION]] si la generación es a nivel de cotización</p>
                                                                    <p>Agregar la preposición siguiente en la variable: (eludirla en caso la cotización sea a nivel de cotización)</p>
                                                                    <p><b>veh[NUMERO_VEHICULO] |</b></p>
                                                                    <div :style="{marginLeft: '20px'}">
                                                                        <b>cot[NUMERO_COTIZACION]|id</b> Imprime la informacion por cotizacion (id).<br>
                                                                        <b>cot[NUMERO_COTIZACION]|producto</b> Imprime la informacion por cotizacion (producto).<br>
                                                                        <b>cot[NUMERO_COTIZACION]|TARIFA_CODIGO</b> Imprime el código de tarifa por cotizacion.<br>
                                                                        <b>cot[NUMERO_COTIZACION]|TARIFA_NOMBRE</b> Imprime el nombre de tarifa por cotizacion.<br>
                                                                        <b>cot[NUMERO_COTIZACION]|tarifa</b> Imprime la informacion por cotizacion (tarifa).<br>
                                                                        <b>cot[NUMERO_COTIZACION]|descuentoPorcentaje</b> Imprime la informacion por cotizacion (descuento Porcentaje).<br>
                                                                        <b>cot[NUMERO_COTIZACION]|formaPago</b> Imprime la informacion por cotizacion (forma Pago).<br>
                                                                        <b>cot[NUMERO_COTIZACION]|numeroPagos</b> Imprime la informacion por cotizacion (numero Pagos).<br>
                                                                        <b>cot[NUMERO_COTIZACION]|primaNeta</b> Imprime la informacion por cotizacion (prima Neta).<br>
                                                                        <b>cot[NUMERO_COTIZACION]|primaTotal</b> Imprime la informacion por cotizacion (prima Total).<br>
                                                                        <b>cot[NUMERO_COTIZACION]|recargoPorcentaje</b> Imprime la informacion por cotizacion (recargo).<br>
                                                                        <b>cot[NUMERO_COTIZACION]|emitirPoliza</b> Imprime la informacion por cotizacion (emitir poliza).<br>
                                                                        <b>cot[NUMERO_COTIZACION]|numeroCotizacionAS400</b> Imprime la informacion por cotizacion (numero cotizacion desde el AS400).<br>
                                                                        <b>cot[NUMERO_COTIZACION]|idCorrelativo</b> Imprime la informacion por cotizacion (correlativo id).<br>
                                                                        <b>cot[NUMERO_COTIZACION]|cob[NUMERO_COBERTURA]|cobertura</b> Imprime la informacion por cobertura (nombre cobertura).<br>
                                                                        <b>cot[NUMERO_COTIZACION]|cob[NUMERO_COBERTURA]|monto</b> Imprime la informacion por cobertura (monto).<br>
                                                                        <b>cot[NUMERO_COTIZACION]|cob[NUMERO_COBERTURA]|codigoCobertura</b> Imprime la informacion por cobertura (codigo cobertura).<br>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3" v-if="nodeSelection.typeObject === 'condition'">
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <div class="mb-4">
                                                                    <span>Lógica de decisiones</span>
                                                                    <div>
                                                                        <select class="form-select" v-model="nodeSelection.decisionesL">
                                                                            <option value="iv">Ignorar variables vacías</option>
                                                                            <option value="ev">Evaluar variables vacías</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <label class="form-label">Configuración de condiciones</label>
                                                                <CInputGroup class="mb-3" v-for="(dependencias, indexDep ) in nodeSelection.decisiones">
                                                                    <CDropdown variant="input-group" v-if="indexDep > 0">
                                                                        <CDropdownToggle color="secondary" variant="outline">{{ (dependencias.glue) ? dependencias.glue : 'Pegamento' }}</CDropdownToggle>
                                                                        <CDropdownMenu>
                                                                            <CDropdownItem v-model="dependencias.glue" @click="dependencias.glue = 'AND'"> AND</CDropdownItem>
                                                                            <CDropdownItem v-model="dependencias.glue" @click="dependencias.glue = 'OR'"> OR</CDropdownItem>
                                                                        </CDropdownMenu>
                                                                    </CDropdown>
                                                                    <CFormSelect id="inputGroupSelect04" aria-label="Condiciones" v-model="dependencias.campoId">
                                                                        <option :value="0">-- Seleccione un campo --</option>
                                                                        <option v-for="(campo, indexCampo) in allFields" :value="campo.id">{{ campo.nodo }} - {{ campo.label }} ({{ campo.id }})</option>
                                                                    </CFormSelect>
                                                                    <input class="form-control" v-model="dependencias.vDin" placeholder="o insertar variable dinámica, ej. {{prueba}}"/>
                                                                    <CDropdown variant="input-group">
                                                                        <CDropdownToggle v-html="dependencias.campoIs" color="secondary" variant="outline" split/>
                                                                        <CDropdownMenu>
                                                                            <CDropdownItem v-model="dependencias.campoIs" @click="dependencias.campoIs = '='"> Igual</CDropdownItem>
                                                                            <CDropdownItem v-model="dependencias.campoIs" @click="dependencias.campoIs = '<'"> Menor que</CDropdownItem>
                                                                            <CDropdownItem v-model="dependencias.campoIs" @click="dependencias.campoIs = '<='"> Menor o igual que</CDropdownItem>
                                                                            <CDropdownItem v-model="dependencias.campoIs" @click="dependencias.campoIs = '>'"> Mayor que</CDropdownItem>
                                                                            <CDropdownItem v-model="dependencias.campoIs" @click="dependencias.campoIs = '>='"> Igual o mayor que</CDropdownItem>
                                                                            <CDropdownItem v-model="dependencias.campoIs" @click="dependencias.campoIs = '<>'"> Diferente</CDropdownItem>
                                                                            <CDropdownDivider/>
                                                                            <CDropdownItem v-model="dependencias.campoIs" @click="dependencias.campoIs = 'like'"> Contiene</CDropdownItem>
                                                                        </CDropdownMenu>
                                                                    </CDropdown>
                                                                    <CFormInput v-model="dependencias.value" aria-label="Text input with segmented dropdown button"/>
                                                                    <div class="btn btn" v-if="indexDep > 0" @click="nodeSelection.decisiones.splice(indexDep, 1)">-</div>
                                                                </CInputGroup>
                                                                <div class="btn btn-primary btn-sm" @click="nodeSelection.decisiones.push({ glue: 'AND', value: '', campoId: '', campoIs: '' });">Agregar +</div>
                                                                <!--                                                                        <CFormInput v-model="nodeSelection.presedenciaDecisiones" label="presedencia"/>-->

                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3" v-if="nodeSelection.typeObject === 'subProcess'">
                                                        <label class="form-label">Configuración de tareas programadas</label>
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <CInputGroup class="mb-3" v-for="(dependencias, indexDep ) in nodeSelection.decisiones">

                                                                    <CFormSelect id="inputGroupSelect04" aria-label="Condiciones" v-model="dependencias.campoId">
                                                                        <option v-for="(campo, indexCampo) in allFields" :value="campo.id">{{ campo.nodo }} - {{ campo.label }} ({{ campo.id }})</option>
                                                                    </CFormSelect>
                                                                    <CDropdown variant="input-group">
                                                                        <CDropdownToggle v-html="dependencias.campoIs" color="secondary" variant="outline" split/>
                                                                        <CDropdownMenu>
                                                                            <CDropdownItem v-model="dependencias.campoIs" @click="dependencias.campoIs = '='"> Igual</CDropdownItem>
                                                                            <CDropdownItem v-model="dependencias.campoIs" @click="dependencias.campoIs = '<'"> Menor que</CDropdownItem>
                                                                            <CDropdownItem v-model="dependencias.campoIs" @click="dependencias.campoIs = '<='"> Menor o igual que</CDropdownItem>
                                                                            <CDropdownItem v-model="dependencias.campoIs" @click="dependencias.campoIs = '>='"> Igual o mayor que</CDropdownItem>
                                                                            <CDropdownItem v-model="dependencias.campoIs" @click="dependencias.campoIs = '<>'"> Diferente</CDropdownItem>
                                                                            <CDropdownDivider/>
                                                                            <CDropdownItem v-model="dependencias.campoIs" @click="dependencias.campoIs = 'like'"> Contiene</CDropdownItem>
                                                                        </CDropdownMenu>
                                                                    </CDropdown>
                                                                    <CFormInput v-model="dependencias.value" aria-label="Text input with segmented dropdown button"/>
                                                                    <div class="btn btn" @click="nodeSelection.decisiones.push({ glue: 'AND', value: '', campoId: '', campoIs: '' });">+</div>
                                                                    <div class="btn btn" @click="nodeSelection.decisiones.splice(-1, 1)">-</div>
                                                                </CInputGroup>

                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3" v-if="nodeSelection.typeObject === 'setuser'">
                                                        <div>
                                                            <label class="form-label">Asignar a usuario de rol</label>
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <multiselect
                                                                        v-model="nodeSelection.setuser_roles"
                                                                        :options="roles"
                                                                        :searchable="true"/>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="mt-4">
                                                            <label class="form-label">Asignar a distribuidor</label>
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <multiselect
                                                                        v-model="nodeSelection.setuser_group"
                                                                        :options="grupos"
                                                                        :searchable="true"/>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="mt-4">
                                                            <label class="form-label">Asignar a usuario específico</label>
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <multiselect
                                                                        v-model="nodeSelection.setuser_user"
                                                                        :options="users"
                                                                        :searchable="true"/>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="mt-4">
                                                            <label class="form-label">Asignar a usuario por variable</label>
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <input class="form-control" v-model="nodeSelection.setuser_variable" placeholder="{{VARIABLE_QUE_TRAE_EL_ID_DEL_USUARIO}}"/>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="mt-4">
                                                            <label class="form-label">Método de asignación</label>
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <select class="form-control" v-model="nodeSelection.setuser_method">
                                                                        <option value="load">Por carga de trabajo</option>
                                                                        <option value="random">Por sorteo aleatorio</option>
                                                                        <option value="order">Por orden de usuarios</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="mt-3">
                                                            <hr>
                                                            <h5 class="mb-3">Consideraciones</h5>
                                                            <div class="text-muted">- Si se asigna un usuario específico, se ignorará la asignación a rol y a distribuidores. También se ignorará el método de asignación</div>
                                                            <div class="text-muted mt-2">- La asignación de usuario anterior permite devolver el flujo al usuario que poseía anteriormente la tarea</div>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3" v-if="nodeSelection.typeObject === 'review'">
                                                        <label class="form-label">Configuración de revisión</label>
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <CFormSelect aria-label="Condiciones" @change="(event) => {nodeSelection.review.push(event.target.value); event.target.selectedIndex = 0}">
                                                                    <option :value="0">-- Seleccione un campo para agregar a revisión --</option>
                                                                    <option v-for="(campo, indexCampo) in allFields" :value="campo.id">{{ campo.nodo }} - {{ campo.label }} ({{ campo.id }})</option>
                                                                </CFormSelect>
                                                                <div class="mt-3">
                                                                    <CInputGroup class="mb-3" v-for="(reviewItem, indexDep ) in nodeSelection.review">
                                                                        <CFormInput :value="reviewItem" disabled/>
                                                                        <div class="btn btn" @click="nodeSelection.review.splice(-1, 1)">-</div>
                                                                    </CInputGroup>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3" v-if="nodeSelection.typeObject === 'pagador'">
                                                        <h6>Configuración</h6>
                                                        <div class="row mt-3">
                                                            <div class="mt-5">
                                                                <h5>Agregar CVV</h5>
                                                                <hr>
                                                                <div>
                                                                    <CFormSwitch label="agregar CVV" v-model="nodeSelection.addcvv"/>
                                                                </div>
                                                            </div>
                                                            <div class="mt-5">
                                                                <h5>Afilacion</h5>
                                                                <hr>
                                                                <div>
                                                                    <input type="text" class="form-control" placeholder="Escribe aquí" v-model="nodeSelection.afiliacion">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="globalModalFooter text-end">
                                                    <CButton @click="showNodeConfig = false"
                                                             class="btn btn-primary me-2">
                                                        Volver a editor
                                                    </CButton>
                                                    <CButton @click="updateNodeSelection(); save()" class="btn btn-success">
                                                        Guardar
                                                    </CButton>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="position-relative">
                                                <small class="text-muted">* Puedes hacer doble clic a un símbolo para entrar a su configuración de forma rápida</small>
                                                <div class="dndflow" @drop="onDrop">
                                                    <VueFlow
                                                        height=100
                                                        @dragover="onDragOver"
                                                        @node-click="onSelect"
                                                        @nodeDragStart="onSelect"
                                                        @edge-click="deleteEdge"
                                                        @nodeDoubleClick="onSelect; showNodeConfig = true;"
                                                        :default-edge-options="{ type: 'smoothstep' }"
                                                        :default-viewport="{ zoom: 1.2 }"
                                                        @pane-click="nodeSelection = {}"
                                                        :connection-radius="60"
                                                        :min-zoom="0"
                                                        :max-zoom="1.8"
                                                        :snap-to-grid="false"
                                                        :pan-on-scroll="false"
                                                        :prevent-scrolling="false"
                                                        :zoom-on-scroll="false"
                                                        :zoom-activation-key-code="'Shift'"
                                                        :delete-key-code="false"
                                                        :node-types="nodeTypes"
                                                        :stroke-width="1"
                                                    >
                                                        <MiniMap/>
                                                        <Controls/>
                                                        <Background pattern-color="#aaa" gap="16"/>
                                                        <div class="updatenode__controls" v-if="nodeSelection && Object.keys(nodeSelection).length > 0">
                                                            <label>Nombre:</label>
                                                            <input v-model="nodeSelection.nodoName" @change="updatedName"/>

                                                            <label class="updatenode__bglabel">Controles:</label>
                                                            <button type="button" class="btn btn-primary btn-sm my-1 mx-1" @click="eliminarNodo">
                                                                <i class="fa fa-trash"></i></button>
                                                            <button type="button" class="btn btn-secondary btn-sm my-1 mx-1" @click="cloneNode">
                                                                <i class="fa fa-clone"></i></button>
                                                            <button type="button" class="btn btn-secondary btn-sm my-1 mx-1" @click="()=>{ showNodeConfig = true;}">
                                                                <i class="fa fa-edit"></i></button>
                                                            <button type="button" class="btn btn-secondary btn-sm my-1 mx-1" @click="save">
                                                                <i class="fa fa-save"></i>
                                                            </button>
                                                            <label>Tipo</label>
                                                            <!--                                                            <select v-model="nodeSelection.typeObject" class="w-100">
                                                                                                                            <option value="start">Inicio</option>
                                                                                                                            <option value="input">Entrada</option>
                                                                                                                            <option value="condition">Decidir</option>
                                                                                                                            <option value="process">Proceso</option>
                                                                                                                            <option value="output">Salida de datos</option>
                                                                                                                        </select>-->
                                                            <CFormSwitch class="m-2" label="Convertir (inicio/entrada)" @click="toggleNodeType"/>
                                                        </div>
                                                    </VueFlow>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CTabPane>
                        <CTabPane v-if="id > 0" role="tabpanel" aria-labelledby="contact-tab" :visible="tabPaneActiveKey === 5">
                            <div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="mb-1">CSS Personalizado</label>
                                        <textarea v-model="producto.cssCustom" class="form-control" style="min-height: 600px"></textarea>
                                    </div>
                                </div>
                                <div class="col-12 mt-3">
                                    <div class="form-group">
                                        <label class="mb-1">JS Personalizado</label>
                                        <textarea v-model="producto.jsCustom" class="form-control" style="min-height: 600px"></textarea>
                                    </div>
                                </div>
                            </div>
                        </CTabPane>
                    </CTabContent>

                    <div class="mt-5 text-end">
                        <button @click="$router.push('/admin/flujos')" class="btn btn-danger me-2">Cancelar</button>
                        <button @click="save" class="btn btn-primary">Guardar</button>
                    </div>
                </CCardBody>
            </CCard>
        </CCol>
    </CRow>
</template>

<script>
import toolbox from "@/toolbox";
import Multiselect from '@vueform/multiselect';

import {Background} from '@vue-flow/background';
import {MiniMap} from '@vue-flow/minimap';
import {Controls} from '@vue-flow/controls';
import {nextTick, ref, watch, defineProps, onMounted, defineExpose} from 'vue';
import Editor from '@tinymce/tinymce-vue';
import CodeEditor from "simple-code-editor";
import {config} from "/src/config";
import Vue3TagsInput from 'vue3-tags-input';

import {vMaska} from "maska";

// Import FilePond
import vueFilePond from 'vue-filepond';
import {AccordionList, AccordionItem} from "vue3-rich-accordion";
import "vue3-rich-accordion/accordion-library-styles.css";

// Filepond
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
import 'filepond/dist/filepond.min.css';
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css';

const FilePond = vueFilePond(FilePondPluginFileValidateType, FilePondPluginImagePreview);

// Vueflow
import '@vue-flow/core/dist/style.css';
import '@vue-flow/core/dist/theme-default.css';
import '@vue-flow/minimap/dist/style.css';
import '@vue-flow/controls/dist/style.css';

export default {
    // Otras propiedades y métodos del componente
    directives: {maska: vMaska},
    name: 'Tables',
    components: {
        Multiselect,
        Background,
        Controls,
        MiniMap,
        Editor,
        FilePond,
        AccordionList,
        AccordionItem,
        CodeEditor,
        Vue3TagsInput,
    },
    data() {
        return {
            variableSearch: '',
            datosPruebas: {},
            visibleSideBar: false,
            visibleSideBarLeft: false,
            catalogoName: '',
            tiposFormularios: [
                {
                    label: 'Privado',
                    value: 'privado'
                },
                {
                    label: 'Público',
                    value: 'publico'
                },
                {
                    label: 'Mixto',
                    value: 'mixto'
                }
            ],
            extraData: {},
            apiKey: 'n8ab72lgcjz7weqad287mk9pgjg0acg88z7xzhdf0y0hc9zn',
            plugins: 'code autoresize autosave link image',
            toolbar: 'undo redo | fontsizeselect formatselect | bold italic forecolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent blockquote | link image media',
            language: 'es',
            id: (typeof useRoute().params.id !== 'undefined') ? parseInt(useRoute().params.id) : 0,
            tabPaneActiveKey: 1,

            // agregar campo
            agregarCampo: false,
            //archivadorSelected: null,

            formularios: [],
            campo: {
                id: 0,
                archivadorCampo: '',
                nombre: '',
                showInReports: false,
                ocrConfig: '',
                valorCalculado: '',
                jsPost: '',
                jsPostAlert: '',
                ttp: '',
                layoutSizePc: '',
                currency: '',
                canales_assign: [],
                grupos_assign: [],
                roles_assign: [],
                layoutSizeMobile: '',
                ph: '', // placeholder
                desc: '',
                group: '',
                cssClass: '',
                requerido: 0,
                readonly: 0,
                deshabilitado: 0,
                dependOn: [{campoId: '', campoIs: '', campoValue: '', campoVar: ''}],
                visible: 0,
                activo: 0,
                forceReplaceDef: 0,
                archivadorDetalleId: 0,
                tipoCampo: '',
                mime: '',
                filePath: '',
                mascara: '',
                tokenMask: '',
                outputMask: false,
                contieneMask: false,
                //contienePadre: false,
                padre: false,
                expNewConf: {
                    label: '',
                    tipo: '',
                    ramo: '',
                    sobreescribir: '',
                    attr: [],
                },
                catalogoValue: '',
                catalogoLabel: '',
                catalogoId: '',
                catFValue: '',
                catFLabel: '',
                catFId: '',
                longitudMin: 0,
                longitudMax: 0,
                proceso: {
                    entrada: '',
                    salida: [],
                    url: '',
                    method: '',
                    type: '',
                    tipoRecibido: false,
                    isXML: false,
                    header: '',
                    configuracionS: []

                },
                archivadorRel: [],
                fixedField: '',
                procesoExec: null,
            },

            // tipos de campos
            fieldTypes: {
                text: {
                    name: 'Texto de una sola línea',
                    icon: 'fa fa-font',
                    type: 'text',
                },
                textarea: {
                    name: 'Texto de varias líneas',
                    icon: 'fa fa-text-height',
                    type: 'textArea',
                },
                number: {
                    name: 'Número',
                    icon: 'fa fa-hashtag',
                    type: 'number',
                },
                numslider: {
                    name: 'Control deslizante',
                    icon: 'fa fa-number',
                    type: 'numslider',
                },
                date: {
                    name: 'Fecha',
                    icon: 'fa fa-calendar',
                    type: 'date',
                },
                dateMask: {
                    name: 'Fecha 2',
                    icon: 'fa fa-calendar',
                    type: 'dateMask',
                },
                checkbox: {
                    name: 'Casilla de verificación (Checkbox)',
                    icon: 'fa fa-square-check',
                    type: 'checkbox',
                },
                option: {
                    name: 'Opciones de respuesta (Option)',
                    icon: 'fa fa-circle-dot',
                    type: 'option',
                },
                select: {
                    name: 'Lista desplegable (Dropdown)',
                    icon: 'fa fa-chevron-circle-down',
                    type: 'select',
                },
                multiselect: {
                    name: 'Selección múltiple',
                    icon: 'fa fa-chevron-circle-down',
                    type: 'multiselect',
                },
                file: {
                    name: 'Archivo',
                    icon: 'fa fa-paperclip',
                    type: 'file',
                },
                fileER: {
                    name: 'Archivo indexado (expedientes)',
                    icon: 'fa fa-paperclip',
                    type: 'fileER',
                },
                signature: {
                    name: 'Firma',
                    icon: 'fa fa-signature',
                    type: 'signature',
                },
                aprobacion: {
                    name: 'Aprobación',
                    icon: 'fa fa-check',
                    type: 'aprobacion',
                },
                title: {
                    name: 'Título',
                    icon: 'fa fa-font',
                    type: 'title',
                },
                subtitulo: {
                    name: 'Subtítulo',
                    icon: 'fa fa-font',
                    type: 'subtitle',
                },
                txtlabel: {
                    name: 'Texto informativo',
                    icon: 'fa fa-font',
                    type: 'txtlabel',
                },
                currency: {
                    name: 'Moneda',
                    icon: 'fas fa-money-bill-wave',
                    type: 'currency',
                },
                add: {
                    name: 'Agregar (+)',
                    icon: 'fa fa-font',
                    type: 'add',
                },
                encrypt: {
                    name: 'Texto Encriptado',
                    icon: 'fa fa-font',
                    type: 'encrypt',
                },
            },

            // formulario valores
            optionsArchivadores: [],
            optionsArchivadoresT: [],
            stateMachineConfig: { // Variable stateMachineConfig que representa la configuración de la máquina de estados
                id: 'kanban',
                initial: 'inicio',
                states: {},
            },
            msg: '',
            producto: {
                status: false,
                valSin: false,
                imagenData: '',
                nombreProducto: '',
                token: '',
                descripcion: '',
                codigoInterno: '',
                cssCustom: '',
                jsCustom: '',
                estadosList: [],
                revC: [],
                area: [],
                canales_assign: [],
                grupos_assign: [],
                roles_assign: [],
                sincronizar: false,
                manErr: false,
            },
            planes: {},
            canales: [],
            grupos: [],
            roles: [],
            users: [],
            areas: [],
            pdfTpl: [],
            enlace: '',

            // Prueba de WS
            respuestaWsLog: {},

            // revisión
            fieldToReview: {},


            // importar flujos
            importarFlujoShow: false,
            showDescribirFlujo: false,
        };
    },

    mounted() {
        const self = this;

        this.getRoles();
        this.getCanales();
        this.getGrupos();
        this.getUsers();
        this.getPdfTemplates();
        this.getArchivadores();

        if (self.id > 0) {
            toolbox.doAjax('GET', 'productos/internos/' + self.id, {
                userid: 0
            }, function (response) {
                self.producto = response.data[0];
                /*if (response.data[0].extraData.bloques) {
                    self.blocks = response.data[0].extraData.bloques;
                }*/
                if (response.data[0].extraData.flujo) {
                    self.stateMachineConfig = response.data[0].extraData.flujo;
                }
                if (typeof response.data[0].extraData.planes !== 'undefined') {
                    self.planes = {};

                    if (typeof response.data[0].extraData.planes === 'object') {
                        Object.keys(response.data[0].extraData.planes).map(function (a, b){
                            if (typeof response.data[0].extraData.planes[a].slug === 'undefined') {
                                response.data[0].extraData.planes[a].slug = 'c_' + b;
                            }
                            self.planes[response.data[0].extraData.planes[a].slug] = response.data[0].extraData.planes[a];
                        });
                    }
                    else {
                        response.data[0].extraData.planes.forEach(function (a, b){
                            if (typeof a.slug === 'undefined') {
                                a.slug = 'c_' + b;
                            }
                            self.planes[a.slug] = a;
                        });
                    }
                }
                if (response.data[0].sva) {
                    self.areas = response.data[0].sva;
                }
                if (response.data[0].extraData.revC) {
                    self.producto.revC = response.data[0].extraData.revC;
                }
                if (response.data[0].extraData.area) {
                    self.producto.area = response.data[0].extraData.area;
                }
                if (response.data[0].extraData.roles_assign) {
                    self.producto.roles_assign = response.data[0].extraData.roles_assign;
                }
                if (response.data[0].extraData.grupos_assign) {
                    self.producto.grupos_assign = response.data[0].extraData.grupos_assign;
                }
                if (response.data[0].extraData.canales_assign) {
                    self.producto.canales_assign = response.data[0].extraData.canales_assign;
                }
                if (response.data[0].extraData.e) {
                    self.producto.estadosList = response.data[0].extraData.e;
                }

                self.enlace = config.appUrl + '#/f/' + self.producto.token + '/view';

            }, function (response) {
               // toolbox.alert(response.msg);
            });
        }
    },
    methods: {
        handleEstadosTags(tags) {
            this.producto.estadosList = tags;
        },
        deleteItem(indexPlan, val) {
            this.planes[indexPlan].items = this.planes[indexPlan].items.filter((item) => {
                delete val.key;
                return JSON.stringify(item) !== JSON.stringify(val);
            });
        },
        changeValueInlinee(indexPrueba, object) {
            this.datosPruebas[indexPrueba].value = object.target.value;
        },
        parseFormData(formSalida) {
            const regex = /\{\{(.*?)\}\}/g;
            let match;
            let formField = {};
            while ((match = regex.exec(formSalida)) !== null) {
                const token = match[0];
                const fieldName = match[1];

                if (!formField[fieldName]) {
                    this.datosPruebas[fieldName] = {
                        label: fieldName,
                        value: '',
                        name: fieldName,
                        type: 'text'
                    };
                    formField[fieldName] = {
                        label: fieldName,
                        value: '',
                        name: fieldName,
                        type: 'text'
                    }
                }


            }
            return formField;

        },
        addCatalogo() {
            let key = Math.max(...Object.keys(this.planes).map(e =>{
                if(e.slice(0,2) === 'c_')  {
                    return Number(e.split('c_')[1])? Number(e.split('c_')[1]) : 0}
                else return 0
            }))+ 1;
            if(!Number.isInteger(key)) key = 1;
            this.planes['c_' + key] = {
                slug: 'c_' + key,
                nombreCatalogo: '',
                items: [],
            };
        },
        eliminarPlan(slug) {
            const self = this;
            toolbox.confirm('Se eliminará el catálogo', function () {
                if (typeof self.planes[slug] !== 'undefined') {
                    delete self.planes[slug];
                }
            })
        },
        downloadCatalogo(slug) {
            const self = this;
            const dataToSend = [];
            if(self.planes[slug].items && self.planes[slug].items[0]){
                let arrayHeader = Object.keys(self.planes[slug].items[0]);
                dataToSend.push(arrayHeader);
                self.planes[slug].items.forEach(item => dataToSend.push(arrayHeader.map(e => item[e])))
            };
            toolbox.doAjax('POST', 'productos/catalogo/download', {dataToSend}, function (response) {
                    toolbox.alert(response.msg, 'success');
                    window.open(response.data.url);
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        onMainImageChange(event) {
            const file = event.target.files[0];
            const reader = new FileReader();
            const self = this;
            reader.onload = () => {
                self.producto.imagenData = reader.result;
            };

            reader.readAsDataURL(file);
        },
        save() {
            const self = this;
            let data = {
                id: self.id,
                imagenData: self.producto.imagenData,
                nombreProducto: self.producto.nombreProducto,
                descripcion: self.producto.descripcion,
                codigoInterno: self.producto.codigoInterno,
                cssCustom: self.producto.cssCustom,
                jsCustom: self.producto.jsCustom,
                prActivo: self.producto.status,
                status: self.producto.status,
                sincronizar: self.producto.sincronizar,
                manErr: self.producto.manErr,
                valSin: self.producto.valSin,
                extraData: {
                    e: self.producto.estadosList,
                    planes: self.planes,
                    revC: self.producto.revC,
                    area: self.producto.area,
                    canales_assign: self.producto.canales_assign,
                    grupos_assign: self.producto.grupos_assign,
                    roles_assign: self.producto.roles_assign,
                }
            };

            toolbox.doAjax('PUT', 'productos/editar/' + self.id, data,
                function (response) {

                    if (!self.id) {
                        window.location.href = '/#/admin/flujo/edit/' + response.data.id;
                        location.reload();
                    } else {
                        const dataToSend = {
                            flujo: window.ToOPT.toObject(),
                            producto: self.id,
                            flujoId: window.ToOPT.flujoId.value,
                            activo: window.ToOPT.flujoIsActive.value,
                            modoPruebas: window.ToOPT.modoPruebas.value,
                            nombre: window.ToOPT.flujoName.value
                        }

                        if (!window.ToOPT.flujoId.value) {
                            dataToSend.activo = 1;
                        }

                        toolbox.doAjax('POST', 'flujos/new', dataToSend, function (response) {
                            toolbox.alert(response.msg);

                            if (!window.ToOPT.flujoId.value) {
                                window.ToOPT.flujoId.value = response.data.id;
                                location.reload();
                            }
                            // window.ToOPT.loadProduct();
                        }, function (response) {
                            toolbox.alert(response.msg);
                        });
                    }
                }, function (response) {
                    toolbox.alert(response.msg);
                });
        },

        //###Roles######
        getUsers() {

            const self = this;
            toolbox.doAjax('GET', 'users/list', {},
                function (response) {
                    //self.items = response.data;
                    self.users = [
                        {
                            value: '_PREV_',
                            label: '-- Asignar usuario anterior --',
                        },
                        {
                            value: '_ORI_',
                            label: '-- Asignar usuario original --',
                        },
                    ];

                    Object.keys(response.data).map(function (a, b) {
                        self.users.push({
                            value: response.data[a].id,
                            label: response.data[a].name,
                        })
                    })
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        getRoles() {

            const self = this;
            toolbox.doAjax('GET', 'users/role/list', {},
                function (response) {
                    //self.items = response.data;
                    self.roles = [];
                    Object.keys(response.data).map(function (a, b) {
                        self.roles.push({
                            value: response.data[a].id,
                            label: response.data[a].name,
                        })
                    })
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        getPdfTemplates() {

            const self = this;
            toolbox.doAjax('GET', 'tareas/get/pdf-template-list', {},
                function (response) {
                    //self.items = response.data;
                    self.pdfTpl = [];
                    Object.keys(response.data).map(function (a, b) {
                        self.pdfTpl.push({
                            value: response.data[a].id,
                            label: response.data[a].nombre,
                        })
                    })
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        getGrupos() {

            const self = this;
            toolbox.doAjax('GET', 'users/grupo/list', {},
                function (response) {
                    //self.items = response.data;
                    self.grupos = [];
                    Object.keys(response.data).map(function (a, b) {
                        self.grupos.push({
                            value: response.data[a].id,
                            label: response.data[a].nombre,
                        })
                    })
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },
        getCanales() {

            const self = this;
            toolbox.doAjax('GET', 'users/canal/list', {},
                function (response) {
                    //self.items = response.data;
                    self.canales = [];
                    Object.keys(response.data).map(function (a, b) {
                        self.canales.push({
                            value: response.data[a].id,
                            label: response.data[a].nombre,
                        })
                    })
                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
        },

        //#######Flujos#########
        preparedHeaders(data, extraColumn = false, hideColumns = []) {
            // Verificar si flatMap está disponible y, si no lo está, implementar una alternativa
            const flatMapFn = Array.prototype.flatMap || function (callback) {
                return Array.prototype.concat.apply([], this.map(callback));
            };

            // Obtén los nombres de las propiedades únicas en los objetos del array
            const properties = Array.from(new Set(flatMapFn.call(data, Object.keys)));

            // Crea los encabezados en el formato requerido por vue3-easy-data-table
            // Filtra los encabezados para no incluir aquellos que están en la lista hideColumns
            let headers = properties
                .filter(property => !hideColumns.includes(property))
                .map(property => {
                    return {
                        text: property,
                        value: property,
                        hideHeader: false // Los encabezados que no están en hideColumns no se ocultan
                    };
                });

            if (extraColumn && !hideColumns.includes(extraColumn)) {
                headers.push({
                    text: extraColumn,
                    value: extraColumn,
                    hideHeader: false // La columna extra también se incluirá si no está en hideColumns
                });
            }

            return headers;
        },
        preparedHeadersSearcg(data, extraColumn = false, hideColumns = []) {
            // Verificar si flatMap está disponible y, si no lo está, implementar una alternativa
            const flatMapFn = Array.prototype.flatMap || function (callback) {
                return Array.prototype.concat.apply([], this.map(callback));
            };

            // Obtén los nombres de las propiedades únicas en los objetos del array
            const properties = Array.from(new Set(flatMapFn.call(data, Object.keys)));

            // Crea los encabezados en el formato requerido por vue3-easy-data-table
            // Filtra los encabezados para no incluir aquellos que están en la lista hideColumns
            let headers = properties
                .filter(property => !hideColumns.includes(property))
                .map(property => {
                    return {
                        text: property,
                        value: property,
                        hideHeader: false // Los encabezados que no están en hideColumns no se ocultan
                    };
                });

            if (extraColumn && !hideColumns.includes(extraColumn)) {
                headers.push({
                    text: extraColumn,
                    value: extraColumn,
                    hideHeader: false // La columna extra también se incluirá si no está en hideColumns
                });
            }

            const resultArray = headers.map(item => item.value);

            return resultArray;
        },
        preparedItems(data) {
            // Verificar si map está disponible y, si no lo está, implementar una alternativa
            const mapFn = Array.prototype.map || function (callback) {
                const result = [];
                for (let i = 0; i < this.length; i++) {
                    result.push(callback(this[i], i, this));
                }
                return result;
            };

            // Convierte los objetos del array a un formato compatible con vue3-easy-data-table
            const items = mapFn.call(data, obj => {
                const item = {};
                for (const key in obj) {
                    if (Object.prototype.hasOwnProperty.call(obj, key)) {
                        item[key] = obj[key];
                    }
                }
                return item;
            });

            return items;
        },

        // Preformatos ws
        addWsPreformat(procesoKey) {
            const self = this;

            if (typeof self.nodeSelection[procesoKey] !== 'undefined') {
                self.nodeSelection[procesoKey].push({
                    va: '',
                    con: '',
                    c: '',
                })
            }
        },

        //##### Formularios ######
        getArchivadores() {
            const self = this;
            toolbox.doAjax('GET', 'admin/archivador/fields', {},
                function (response) {
                    Object.keys(response.data).map(function (a, b) {
                        self.optionsArchivadores.push({
                            value: response.data[a].id,
                            label: response.data[a].nombre,
                        })
                    })
                },
                function (responseRole) {
                  //  toolbox.alert(responseRole.msg, 'danger');
                });
        },

        filterNodosProcess(){
            const self = this;
            if(!self.producto || !self.producto.flujo || !self.producto.flujo.nodes) return [];
            else return self.producto.flujo.nodes.filter(e => e.typeObject === 'process');
        }
    }
}
</script>
