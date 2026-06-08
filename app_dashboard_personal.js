// app_dashboard_personal.js - Procesador Métrico Avanzado y Render Dinámico (MESS)

$(document).ready(function() {
    // Inicializar de forma segura si la vista tiene los contenedores adecuados
    if ($('#tabla_analitica_areas').length > 0 || $('#canvas_puestos_maestro').length > 0) {
        cargar_panel_analitico_personal();
    }
});

function cargar_panel_analitico_personal() {
    $.ajax({
        url: 'dashboard_controller.php',
        type: 'POST',
        data: { action: 'obtener_analitica_detallada_personal' },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                
                // 1. Sincronizar tarjetas de indicadores rápidos
                let rg = res.resumen_general;
                $('#num_total_personal').text(rg.total);
                $('#num_mujeres').text(rg.mujeres);
                $('#num_hombres').text(rg.hombres);
                $('#num_planta').text(rg.planta);
                $('#num_contrato').text(rg.contrato);

                // 2. DETALLE MICRO: Construir la tabla de desglose por áreas
                let html_tabla = '';
                res.detalle_areas.forEach(function(row) {
                    html_tabla += `
                        <tr class="align-middle">
                            <td class="font-weight-bold text-dark ps-3"><strong>${row.area}</strong></td>
                            <td class="text-center font-weight-bold text-secondary">${row.total}</td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark px-2 border-0"><i class="fas fa-venus text-pink me-1"></i> ${row.mujeres}</span>
                                <span class="badge bg-light text-dark px-2 border-0"><i class="fas fa-mars text-primary me-1"></i> ${row.hombres}</span>
                            </td>
                            <td class="text-center">
                                <span class="small font-weight-medium d-block text-success">Planta: <strong>${row.planta}</strong></span>
                                <span class="small font-weight-medium d-block text-muted">Contrato: <strong>${row.contrato}</strong></span>
                            </td>
                            <td class="text-center font-weight-bold text-dark pe-3">
                                ${row.antiguedad} <span class="text-muted small font-weight-normal">años</span>
                            </td>
                        </tr>`;
                });
                
                // Destruir instancia previa de DataTable si ya existía para evitar duplicidad en memoria
                if ($.fn.DataTable.isDataTable('#tabla_analitica_areas')) {
                    $('#tabla_analitica_areas').DataTable().destroy();
                }

                $('#tbody_analitica_areas').html(html_tabla);

                // Inicializar DataTables con paginación limpia de 5 registros por vista
                $('#tabla_analitica_areas').DataTable({
                    "responsive": true,
                    "pageLength": 5,
                    "lengthMenu": [5, 10, 25, 50],
                    "dom": 'rtip',
                    "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" }
                });

                // 3. RENDER DE GRÁFICA DE PASTEL (DONA GÉNEROS): Corrección de Id
                if (window.instancia_grafico_genero) { window.instancia_grafico_genero.destroy(); }
                window.instancia_grafico_genero = new Chart(document.getElementById('canvas_grafica_genero'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Mujeres', 'Hombres'],
                        datasets: [{
                            data: [rg.mujeres, rg.hombres],
                            backgroundColor: ['#e83e8c', '#4e73df'],
                            hoverBackgroundColor: ['#ca2a71', '#2e59d9'],
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 12, padding: 10 } }
                        }
                    }
                });

                // 4. RENDER DE GRÁFICA DE BARRAS (PUESTOS)
                if (window.instancia_grafico_puestos) { window.instancia_grafico_puestos.destroy(); }
                let gp = res.grafico_puestos;
                window.instancia_grafico_puestos = new Chart(document.getElementById('canvas_puestos_maestro'), {
                    type: 'bar',
                    data: {
                        labels: gp.labels,
                        datasets: [{
                            label: 'Colaboradores',
                            data: gp.values,
                            backgroundColor: '#1cc88a',
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { stepSize: 1, color: '#858796' } },
                            x: { ticks: { color: '#2c3e50', font: { size: 10 } } }
                        }
                    }
                });

            } else {
                console.error("Error devuelto por el controlador: " + res.message);
            }
        },
        error: function() {
            console.error("Error en la conexión del módulo analítico.");
        }
    });
}