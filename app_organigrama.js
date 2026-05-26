// app_organigrama.js - Solución Autónoma Corporativa MESS 2026

let chartInstance = null;
let rawDataGlobal = [];

if (typeof OrgChart === 'undefined') {
    window.OrgChart = function(container, options) {
        this.container = container;
        this.options = options;
        
        this.draw = function() {
            let nodes = this.options.nodes || [];
            
            // Generamos la interfaz fluida horizontal nativa usando flexbox estructurado
            let html = `<div style="display: flex; flex-direction: row; gap: 40px; padding: 40px; align-items: flex-start; overflow-x: auto; min-width: 100%; height: 100%; box-sizing: border-box; font-family: 'Helvetica Neue', Arial, sans-serif;">`;
            
            // Separamos las raíces (Dirección / Jefes Máximos)
            let raices = nodes.filter(n => n.pid === null);
            
            raices.forEach(r => {
                html += generarBloqueJerarquicoHorizontal(r, nodes);
            });
            
            html += `</div>`;
            this.container.innerHTML = html;
        };

        // Renderizador recursivo para escalamiento horizontal limpio
        function generarBloqueJerarquicoHorizontal(nodo, todosLosNodos) {
            let subordinados = todosLosNodos.filter(n => n.pid === nodo.id);
            let badgeAlcance = nodo.alcances_extra ? `<div style="margin-top: 6px; font-size: 11px; font-weight: bold; color: #e74a3b; background: rgba(231, 74, 59, 0.1); padding: 2px 6px; border-radius: 4px; display: inline-block;">📍 Alcance: ${nodo.alcances_extra}</div>` : '';
            
            let htmlNodo = `
                <div style="display: flex; flex-direction: row; align-items: center; gap: 20px;">
                    <div class="card shadow-sm border-left-primary bg-white" style="width: 240px; min-width: 240px; padding: 15px; border-radius: 6px; border: 1px solid #e3e6f0; box-sizing: border-box;">
                        <div style="font-size: 14px; font-weight: bold; color: #4e73df; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${nodo.nombre}">${nodo.nombre}</div>
                        <div style="font-size: 12px; font-weight: bold; color: #5a5c69; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${nodo.puesto}</div>
                        <div style="font-size: 11px; text-transform: uppercase; color: #858796; margin-top: 8px; letter-spacing: 0.5px;"><i class="fas fa-layer-group mr-1"></i> ${nodo.area_base}</div>
                        ${badgeAlcance}
                    </div>
            `;
            
            // Si tiene subordinados, abrimos una columna a la derecha y los conectamos horizontalmente
            if (subordinados.length > 0) {
                htmlNodo += `<div style="font-weight: bold; color: #4e73df; font-size: 18px;">➔</div>`;
                htmlNodo += `<div style="display: flex; flex-direction: column; gap: 20px; padding-left: 10px; border-left: 2px dashed #dddfeb;">`;
                subordinados.forEach(s => {
                    htmlNodo += generarBloqueJerarquicoHorizontal(s, todosLosNodos);
                });
                htmlNodo += `</div>`;
            }
            
            htmlNodo += `</div>`;
            return htmlNodo;
        }

        this.draw();
    };
    OrgChart.orientation = { left: 1 };
}

// ==================================================================
// CONTROLADOR CENTRAL DE DATOS (AJAX Y FILTROS)
// ==================================================================
$(document).ready(function() {
    cargarDatosOrganigrama();
});

function cargarDatosOrganigrama() {
    $.ajax({
        url: 'action_controller.php',
        type: 'POST',
        data: { action: 'obtener_estructura_organigrama' },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success' && res.data.length > 0) {
                rawDataGlobal = res.data;
                
                // Cargar el selector de áreas
                let areasUnicas = [...new Set(rawDataGlobal.map(item => item.area_base).filter(Boolean))];
                let selectHtml = '<option value="COMPLETO">-- Ver Estructura Completa --</option>';
                areasUnicas.forEach(area => {
                    selectHtml += `<option value="${area}">${area}</option>`;
                });
                $('#filtro_area').html(selectHtml);

                renderizarOrganigrama(rawDataGlobal);
            } else {
                $('#chart_container').html('<div class="text-center p-5 text-muted">No se encontraron colaboradores activos.</div>');
            }
        },
        error: function(xhr, status, error) {
            console.error("Error crítico de red:", error);
            $('#chart_container').html('<div class="text-center p-5 text-danger">Error al conectar con action_controller.php</div>');
        }
    });
}

function renderizarOrganigrama(dataNodes) {
    let nodosFinalesUnicos = [];
    let registroIds = {};

    // Forzar limpieza y tipado estricto
    dataNodes.forEach(function(nodo) {
        let currentId = parseInt(nodo.id);
        if (registroIds[currentId] === true) return; 

        registroIds[currentId] = true;
        let parentId = nodo.pid ? parseInt(nodo.pid) : null;
        if (parentId === currentId) parentId = null; 

        nodosFinalesUnicos.push({
            id: currentId,
            pid: parentId,
            nombre: nodo.nombre,
            puesto: nodo.puesto,
            area_base: nodo.area_base,
            alcances_extra: nodo.alcances_extra
        });
    });

    // Romper pids huérfanos
    nodosFinalesUnicos.forEach(function(nodo) {
        if (nodo.pid && !registroIds[nodo.pid]) {
            nodo.pid = null; 
        }
    });

    try {
        let containerEl = document.getElementById("chart_container");
        // Inicializamos nuestro motor local limpio y plano
        chartInstance = new OrgChart(containerEl, {
            nodes: nodosFinalesUnicos
        });
    } catch (err) {
        console.error("Error en el renderizador local:", err);
    }
}

function filtrarEstructuraOrganigrama() {
    let areaSeleccionada = $('#filtro_area').val();

    if (areaSeleccionada === "COMPLETO") {
        renderizarOrganigrama(rawDataGlobal);
    } else {
        let filtrados = rawDataGlobal.filter(node => node.area_base === areaSeleccionada);
        let parentIds = filtrados.map(n => n.pid).filter(Boolean).map(id => parseInt(id));
        let jefes = rawDataGlobal.filter(node => parentIds.includes(parseInt(node.id)));
        
        let unidos = [...filtrados, ...jefes];
        let unicos = unidos.filter((v, i, a) => a.findIndex(t => parseInt(t.id) === parseInt(v.id)) === i);

        renderizarOrganigrama(unicos);
    }
}