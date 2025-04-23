<template>
    <CRow>
        <CCol :xs="12">
            <CCard class="mb-4">
                <CCardHeader>
                    <strong>Generar reporte</strong>
                </CCardHeader>
                <CCardBody>
                    <h5>Datos generales</h5>
                    <hr>
                    <div class="row">
                        <div class="col-12 col-sm-4">
                            <div class="mb-3">
                                <label class="form-label">Fecha Inicial</label>
                            </div>
                            <input type="date" class="form-control" placeholder="Selecciona la fecha" v-model="fechaIni" @change="getGraph">
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="mb-3">
                                <label class="form-label">Fecha Final</label>
                            </div>
                            <input type="date" class="form-control" placeholder="Selecciona la fecha" v-model="fechaFin" @change="getGraph">
                        </div>
                    </div>
                </CCardBody>
            </CCard>
        </CCol>
    </CRow>
    <CRow :xs="{ cols: 12, gutter: 4 }">
        <CCol class="mb-4">
            <CCard class="h-100">
                <CCardBody>
                    <highcharts :options="conteoDistriCoti" class="mb-4 mt-4"></highcharts>
                </CCardBody>
            </CCard>
        </CCol>
        <CCol class="mb-4">
            <CCard class="h-100">
                <CCardBody>
                    <highcharts :options="conteoDistriPoli" class="mb-4 mt-4"></highcharts>
                </CCardBody>
            </CCard>
        </CCol>
        <CCol class="mb-4">
            <CCard class="h-100">
                <CCardBody>
                    <highcharts :options="conteoDistriPrimaNeta" class="mb-4 mt-4"></highcharts>
                </CCardBody>
            </CCard>
        </CCol>
    </CRow>
    <CRow :xs="{ cols: 12, gutter: 4 }">
        <CCol class="mb-4">
            <CCard class="h-100">
                <CCardBody>
                    <highcharts :options="conteoCotiPoliAcepta" class="mb-4 mt-4"></highcharts>
                </CCardBody>
            </CCard>
        </CCol>
        <CCol class="mb-4">
            <CCard class="h-100">
                <CCardBody>
                    <highcharts :options="conteoFactorComercialDistri" class="mb-4 mt-4"></highcharts>
                </CCardBody>
            </CCard>
        </CCol>
    </CRow>
    <CRow :xs="{ cols: 12, gutter: 4 }">
        <CCol class="mb-4">
            <CCard class="h-100">
                <CCardBody>
                    <highcharts :options="conteoProdPoli" class="mb-4 mt-4"></highcharts>
                </CCardBody>
            </CCard>
        </CCol>
        <CCol class="mb-4">
            <CCard class="h-100">
                <CCardBody>
                    <highcharts :options="conteoProdCoti" class="mb-4 mt-4"></highcharts>
                </CCardBody>
            </CCard>
        </CCol>
    </CRow>
    <CRow :xs="{ cols: 12, gutter: 4 }">
        <CCol class="mb-4">
            <CCard class="h-100">
                <CCardBody>
                    <highcharts :options="conteoProdPrimaNetaPoli" class="mb-4 mt-4"></highcharts>
                </CCardBody>
            </CCard>
        </CCol>
        <CCol class="mb-4">
            <CCard class="h-100">
                <CCardBody>
                    <highcharts :options="conteoProdCotiPoliAcepta" class="mb-4 mt-4"></highcharts>
                </CCardBody>
            </CCard>
        </CCol>
    </CRow>
</template>

<script>
import toolbox from "@/toolbox";
import Multiselect from '@vueform/multiselect'
import Select from "@/views/forms/Select.vue";
import 'form-wizard-vue3/dist/form-wizard-vue3.css';
import {Chart as highcharts} from "highcharts-vue";
import Highcharts from 'highcharts';

export default {
    name: 'Tables',
    components: {Select, Multiselect, highcharts,
    },
    data() {
        return {
            reportes: {},
            reporte: 0,
            fechaIni: new Date().toISOString().slice(0,10),
            fechaFin: new Date().toISOString().slice(0,10),
            graphData: {},
            dataChartMonth: {},
            graphDataReport: {},
            dataChartMonthReport: {},
            totalTareas: 0,
            //por distribuidor
            conteoDistriCoti: {},
            conteoDistriPoli: {},
            conteoDistriPrimaNeta: {},
            conteoCotiPoliAcepta: {},
            conteoFactorComercialDistri: {},
            //por producto 
            conteoProdCoti: {},
            conteoProdPoli: {},
            conteoProdPrimaNetaPoli: {},
            conteoProdCotiPoliAcepta: {},
        };
    },
    mounted() {
       // this.getItems();
        this.getGraph();
    },
    methods: {
        getGraph() {

            const meses = {
                1: 'ene',
                2: 'feb',
                3: 'mar',
                4: 'abr',
                5: 'may',
                6: 'jun',
                7: 'jul',
                8: 'ago',
                9: 'sep',
                10: 'oct',
                11: 'nov',
                12: 'dic',
            }

            const colors = [];
            while (colors.length < 100) {
                do {
                    var color = Math.floor((Math.random()*1000000)+1);
                } while (colors.indexOf(color) >= 0);
                colors.push("#" + ("000000" + color.toString(16)).slice(-6));
            }

            const self = this;
            
            toolbox.doAjax('POST', 'reportes/get-graph', {
                    fechaIni: self.fechaIni,
                    fechaFin: self.fechaFin,
                },
                function (response) {

                    let totalCotiDistri = 0;
                    let totalPoliDistri = 0;
                    let distribuidor1 = [];
                    let polizas1 = [];
                    let primaneta1 = [];
                    let cotizaciones1 = [];
                    let aceptacion1 = [];
                    let factorComercial1 = [];
                    let primanetapromedio1 = [];
                    const dataCotiDistri = response.data.z.map(function (a) {
                        totalCotiDistri += Number(a.cotizaciones);
                        totalPoliDistri += Number(a.polizas);
                        distribuidor1.push(a.distribuidor);
                        polizas1.push(Number(a.polizas));
                        primaneta1.push(Number(a.primaneta));
                        cotizaciones1.push(Number(a.cotizaciones));
                        aceptacion1.push(Number(a.aceptacion));
                        factorComercial1.push(Number(a.factorcomercial));
                        primanetapromedio1.push(Number(a.primapromedio));
                        return {
                            name: a.distribuidor,
                            y: Number(a.cotizaciones)
                        }
                    });
                    
                    const dataPoliDistri = response.data.z.map(function (a) {
                        return {
                            name: a.distribuidor,
                            y: Number(a.polizas)*100/totalPoliDistri
                        }
                    });

                    self.conteoDistriCoti = {
                        chart: {
                            type: 'pie'
                        },
                        title: {
                            text: 'Cotizaciones x Distribuidor'
                        },
                        tooltip: {
                            valueSuffix: '%'
                        },
                        subtitle: {
                            text: 'Source:<a href="https://www.mdpi.com/2072-6643/11/3/684/htm" target="_default">MDPI</a>'
                        },
                        plotOptions: {
                            series: {
                                allowPointSelect: true,
                                cursor: 'pointer',
                                dataLabels: [{
                                    enabled: true,
                                    distance: 20
                                }, {
                                    enabled: true,
                                    distance: -40,
                                    format: '{point.percentage:.1f}%',
                                    style: {
                                        fontSize: '1.2em',
                                        textOutline: 'none',
                                        opacity: 0.7
                                    },
                                    filter: {
                                        operator: '>',
                                        property: 'percentage',
                                        value: 10
                                    }
                                }]
                            }
                        },
                        series: [
                            {
                                name: 'Percentage',
                                colorByPoint: true,
                                data: dataCotiDistri.map(e => {return {...e, y: e.y/totalCotiDistri*100}})
                            }
                        ]
                    };

                    self.conteoDistriPoli = {
                        chart: {
                            type: 'pie'
                        },
                        title: {
                            text: 'Polizas x Distribuidor'
                        },
                        tooltip: {
                            valueSuffix: '%'
                        },
                        subtitle: {
                            text: 'Source:<a href="https://www.mdpi.com/2072-6643/11/3/684/htm" target="_default">MDPI</a>'
                        },
                        plotOptions: {
                            series: {
                                allowPointSelect: true,
                                cursor: 'pointer',
                                dataLabels: [{
                                    enabled: true,
                                    distance: 20
                                }, {
                                    enabled: true,
                                    distance: -40,
                                    format: '{point.percentage:.1f}%',
                                    style: {
                                        fontSize: '1.2em',
                                        textOutline: 'none',
                                        opacity: 0.7
                                    },
                                    filter: {
                                        operator: '>',
                                        property: 'percentage',
                                        value: 10
                                    }
                                }]
                            }
                        },
                        series: [
                            {
                                name: 'Percentage',
                                colorByPoint: true,
                                data: dataPoliDistri
                            }
                        ]
                    };

                    self.conteoDistriPrimaNeta = {
                        chart: {
                            zooming: {
                                type: 'xy'
                            }
                        },
                        title: {
                            text: 'Prima neta y polizas por distribuidor',
                            align: 'left'
                        },
                        credits: {
                            text: 'Source: ' +
                                '<a href="https://www.yr.no/nb/historikk/graf/5-97251/Norge/Finnmark/Karasjok/Karasjok?q=2023"' +
                                'target="_blank">YR</a>'
                        },
                        xAxis: [{
                            categories: distribuidor1,
                            crosshair: true
                        }],
                        yAxis: [{ // Primary yAxis
                            labels: {
                                format: 'Q {value}',
                                style: {
                                    color: Highcharts.getOptions().colors[1]
                                }
                            },
                            title: {
                                text: 'Prima neta',
                                style: {
                                    color: Highcharts.getOptions().colors[1]
                                }
                            }
                        }, { // Secondary yAxis
                            title: {
                                text: 'Polizas',
                                style: {
                                    color: Highcharts.getOptions().colors[0]
                                }
                            },
                            labels: {
                                format: '{value}',
                                style: {
                                    color: Highcharts.getOptions().colors[0]
                                }
                            },
                            opposite: true
                        }],
                        tooltip: {
                            shared: true
                        },
                        legend: {
                            align: 'left',
                            verticalAlign: 'top',
                            backgroundColor:
                                Highcharts.defaultOptions.legend.backgroundColor || // theme
                                'rgba(255,255,255,0.25)'
                        },
                        series: [
                            {
                            name: 'Prima neta',
                            type: 'column',
                            data: primaneta1,
                            tooltip: {
                                valueSuffix: 'Q'
                            }
                        },
                        {
                            name: 'Polizas',
                            type: 'spline',
                            yAxis: 1,
                            data: polizas1,
                            tooltip: {
                                valueSuffix: ' '
                            }

                        }]
                    };

                    self.conteoCotiPoliAcepta = {
                        chart: {
                            zooming: {
                                type: 'xy'
                            }
                        },
                        title: {
                            text: 'Cotizaciones y Polizas por distribuidor',
                            align: 'left'
                        },
                        credits: {
                            text: 'Source: ' +
                                '<a href="https://www.yr.no/nb/historikk/graf/5-97251/Norge/Finnmark/Karasjok/Karasjok?q=2023"' +
                                'target="_blank">YR</a>'
                        },
                        xAxis: [{
                            categories: distribuidor1,
                            crosshair: true
                        }],
                        yAxis: [
                        { // Primary yAxis
                            labels: {
                                format: '{value}',
                                style: {
                                    color: Highcharts.getOptions().colors[1]
                                }
                            },
                            title: {
                                text: 'Cotizaciones y polizas',
                                style: {
                                    color: Highcharts.getOptions().colors[1]
                                }
                            }
                        },
                        { // Secondary yAxis
                            title: {
                                text: 'Aceptación',
                                style: {
                                    color: Highcharts.getOptions().colors[0]
                                }
                            },
                            labels: {
                                format: '{value} %',
                                style: {
                                    color: Highcharts.getOptions().colors[0]
                                }
                            },
                            opposite: true
                        }],
                        tooltip: {
                            shared: true
                        },
                        legend: {
                            align: 'left',
                            verticalAlign: 'top',
                            backgroundColor:
                                Highcharts.defaultOptions.legend.backgroundColor || // theme
                                'rgba(255,255,255,0.25)'
                        },
                        series: [{
                            name: 'Polizas',
                            type: 'column',
                            yAxis: 1,
                            data: polizas1,
                            tooltip: {
                                valueSuffix: ' '
                            }

                        },
                        {
                            name: 'Cotizaciones',
                            type: 'column',
                            yAxis: 1,
                            data: cotizaciones1,
                            tooltip: {
                                valueSuffix: ' '
                            }

                        }, {
                            name: 'Aceptación',
                            type: 'spline',
                            data: aceptacion1,
                            tooltip: {
                                valueSuffix: '%'
                            }
                        }]
                    };

                    self.conteoFactorComercialDistri = {
                        chart: {
                            zooming: {
                                type: 'xy'
                            }
                        },
                        title: {
                            text: 'Factor comercial x distribuidor',
                            align: 'left'
                        },
                        credits: {
                            text: 'Source: ' +
                                '<a href="https://www.yr.no/nb/historikk/graf/5-97251/Norge/Finnmark/Karasjok/Karasjok?q=2023"' +
                                'target="_blank">YR</a>'
                        },
                        xAxis: [{
                            categories: distribuidor1,
                            crosshair: true
                        }],
                        yAxis: [{ // Primary yAxis
                            labels: {
                                format: 'Q {value}',
                                style: {
                                    color: Highcharts.getOptions().colors[1]
                                }
                            },
                            title: {
                                text: 'Prima neta promedio',
                                style: {
                                    color: Highcharts.getOptions().colors[1]
                                }
                            }
                        },
                        { // Secondary yAxis
                            title: {
                                text: 'Factor comercial',
                                style: {
                                    color: Highcharts.getOptions().colors[0]
                                }
                            },
                            labels: {
                                format: '{value}',
                                style: {
                                    color: Highcharts.getOptions().colors[0]
                                }
                            },
                            opposite: true
                        }],
                        tooltip: {
                            shared: true
                        },
                        legend: {
                            align: 'left',
                            verticalAlign: 'top',
                            backgroundColor:
                                Highcharts.defaultOptions.legend.backgroundColor || // theme
                                'rgba(255,255,255,0.25)'
                        },
                        series: [ {
                            name: 'Factor comercial',
                            type: 'spline',
                            data: factorComercial1,
                            tooltip: {
                                valueSuffix: ''
                            }
                        },
                        {
                            name: 'Prima neta promedio',
                            type: 'spline',
                            data: primanetapromedio1,
                            tooltip: {
                                valueSuffix: ''
                            }
                        }]
                    };

                    let totalCotiProd = 0;
                    let totalPoliProd = 0;
                    let productos2 = [];
                    let polizas2 = [];
                    let primaneta2 = [];
                    let cotizaciones2 = [];
                    let aceptacion2 = [];
                    let factorComercial2 = [];
                    let primanetapromedio2 = [];
                    const dataCotiProd = response.data.w.map(function (a) {
                        totalCotiProd += Number(a.cotizaciones);
                        totalPoliProd += Number(a.polizas);
                        productos2.push(a.producto);
                        polizas2.push(Number(a.polizas));
                        primaneta2.push(Number(a.primaneta));
                        cotizaciones2.push(Number(a.cotizaciones));
                        aceptacion2.push(Number(a.aceptacion));
                        factorComercial2.push(Number(a.factorcomercial));
                        primanetapromedio2.push(Number(a.primapromedio));
                        return {
                            name: a.producto,
                            y: Number(a.cotizaciones)
                        }
                    });
                    
                    const dataPoliProd = response.data.w.map(function (a) {
                        return {
                            name: a.producto,
                            y: Number(a.polizas)*100/totalPoliProd
                        }
                    });

                    self.conteoProdCoti = {
                        chart: {
                            type: 'pie'
                        },
                        title: {
                            text: 'Cotizaciones x Producto'
                        },
                        tooltip: {
                            valueSuffix: '%'
                        },
                        subtitle: {
                            text: 'Source:<a href="https://www.mdpi.com/2072-6643/11/3/684/htm" target="_default">MDPI</a>'
                        },
                        plotOptions: {
                            series: {
                                allowPointSelect: true,
                                cursor: 'pointer',
                                dataLabels: [{
                                    enabled: true,
                                    distance: 20
                                }, {
                                    enabled: true,
                                    distance: -40,
                                    format: '{point.percentage:.1f}%',
                                    style: {
                                        fontSize: '1.2em',
                                        textOutline: 'none',
                                        opacity: 0.7
                                    },
                                    filter: {
                                        operator: '>',
                                        property: 'percentage',
                                        value: 10
                                    }
                                }]
                            }
                        },
                        series: [
                            {
                                name: 'Percentage',
                                colorByPoint: true,
                                data: dataCotiProd.map(e => {return {...e, y: e.y/totalCotiProd*100}})
                            }
                        ]
                    };

                    self.conteoProdPoli = {
                        chart: {
                            type: 'pie'
                        },
                        title: {
                            text: 'Polizas x Producto'
                        },
                        tooltip: {
                            valueSuffix: '%'
                        },
                        subtitle: {
                            text: 'Source:<a href="https://www.mdpi.com/2072-6643/11/3/684/htm" target="_default">MDPI</a>'
                        },
                        plotOptions: {
                            series: {
                                allowPointSelect: true,
                                cursor: 'pointer',
                                dataLabels: [{
                                    enabled: true,
                                    distance: 20
                                }, {
                                    enabled: true,
                                    distance: -40,
                                    format: '{point.percentage:.1f}%',
                                    style: {
                                        fontSize: '1.2em',
                                        textOutline: 'none',
                                        opacity: 0.7
                                    },
                                    filter: {
                                        operator: '>',
                                        property: 'percentage',
                                        value: 10
                                    }
                                }]
                            }
                        },
                        series: [
                            {
                                name: 'Percentage',
                                colorByPoint: true,
                                data: dataPoliProd
                            }
                        ]
                    };
                    
                    self.conteoProdPrimaNetaPoli = {
                        chart: {
                            zooming: {
                                type: 'xy'
                            }
                        },
                        title: {
                            text: 'Prima neta y polizas por producto',
                            align: 'left'
                        },
                        credits: {
                            text: 'Source: ' +
                                '<a href="https://www.yr.no/nb/historikk/graf/5-97251/Norge/Finnmark/Karasjok/Karasjok?q=2023"' +
                                'target="_blank">YR</a>'
                        },
                        xAxis: [{
                            categories: productos2,
                            crosshair: true
                        }],
                        yAxis: [{ // Primary yAxis
                            labels: {
                                format: 'Q {value}',
                                style: {
                                    color: Highcharts.getOptions().colors[1]
                                }
                            },
                            title: {
                                text: 'Prima neta',
                                style: {
                                    color: Highcharts.getOptions().colors[1]
                                }
                            }
                        }, { // Secondary yAxis
                            title: {
                                text: 'Polizas',
                                style: {
                                    color: Highcharts.getOptions().colors[0]
                                }
                            },
                            labels: {
                                format: '{value}',
                                style: {
                                    color: Highcharts.getOptions().colors[0]
                                }
                            },
                            opposite: true
                        }],
                        tooltip: {
                            shared: true
                        },
                        legend: {
                            align: 'left',
                            verticalAlign: 'top',
                            backgroundColor:
                                Highcharts.defaultOptions.legend.backgroundColor || // theme
                                'rgba(255,255,255,0.25)'
                        },
                        series: [{
                            name: 'Prima neta',
                            type: 'column',
                            data: primaneta2,
                            tooltip: {
                                valueSuffix: 'Q'
                            }
                        },
                        {
                            name: 'Polizas',
                            type: 'spline',
                            yAxis: 1,
                            data: polizas2,
                            tooltip: {
                                valueSuffix: ' '
                            }

                        }]
                    };
                    
                    self.conteoProdCotiPoliAcepta = {
                        chart: {
                            zooming: {
                                type: 'xy'
                            }
                        },
                        title: {
                            text: 'Cotizaciones y Polizas por producto',
                            align: 'left'
                        },
                        credits: {
                            text: 'Source: ' +
                                '<a href="https://www.yr.no/nb/historikk/graf/5-97251/Norge/Finnmark/Karasjok/Karasjok?q=2023"' +
                                'target="_blank">YR</a>'
                        },
                        xAxis: [{
                            categories: productos2,
                            crosshair: true
                        }],
                        yAxis: [
                        { // Primary yAxis
                            labels: {
                                format: '{value}',
                                style: {
                                    color: Highcharts.getOptions().colors[1]
                                }
                            },
                            title: {
                                text: 'Cotizaciones y Polizas',
                                style: {
                                    color: Highcharts.getOptions().colors[1]
                                }
                            }
                        },
                        { // Secondary yAxis
                            title: {
                                text: 'Aceptación',
                                style: {
                                    color: Highcharts.getOptions().colors[0]
                                }
                            },
                            labels: {
                                format: '{value} %',
                                style: {
                                    color: Highcharts.getOptions().colors[0]
                                }
                            },
                            opposite: true
                        }],
                        tooltip: {
                            shared: true
                        },
                        legend: {
                            align: 'left',
                            verticalAlign: 'top',
                            backgroundColor:
                                Highcharts.defaultOptions.legend.backgroundColor || // theme
                                'rgba(255,255,255,0.25)'
                        },
                        series: [{
                            name: 'Polizas',
                            type: 'column',
                            yAxis: 1,
                            data: polizas2,
                            tooltip: {
                                valueSuffix: ' '
                            }

                        },
                        {
                            name: 'Cotizaciones',
                            type: 'column',
                            yAxis: 1,
                            data: cotizaciones2,
                            tooltip: {
                                valueSuffix: ' '
                            }

                        }, {
                            name: 'Aceptación',
                            type: 'spline',
                            data: aceptacion2,
                            tooltip: {
                                valueSuffix: '%'
                            }
                        }]
                    };

                },
                function (response) {
                    toolbox.alert(response.msg, 'danger');
                })
   
        },
     }
}
</script>
