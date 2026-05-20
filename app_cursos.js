// app_cursos.js
$(document).ready(function() {
    cargar_lista_cursos();
});

function cargar_lista_cursos() {
    $.ajax({
        url: 'action_controlador_docs.php',
        type: 'POST',
        data: { action: 'listar_cursos_catalogo' },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                let html_tabla = '';
                if (response.data.length === 0) {
                    html_tabla = `<tr><td colspan="6" class="text-center text-muted py-3">No hay cursos registrados en el catálogo general.</td></tr>`;
                } else {
                    response.data.forEach(function(curso) {
                        html_tabla += `
                            <tr>
                                <td>${curso.id}</td>
                                <td class="font-weight-bold text-gray-800">${curso.nombre_curso}</td>
                                <td>${curso.institucion}</td>
                                <td><span class="badge badge-info p-2">${curso.horas} horas</span></td>
                                <td>${curso.descripcion || '<span class="text-muted">Sin descripción</span>'}</td>
                                <td class="text-center">
                                    <button class="btn btn-outline-danger btn-sm btn-circle" onclick="eliminar_curso_catalogo(${curso.id})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                }
                $('#tabla_catalogo_cursos_body').html(html_tabla);
            }
        }
    });
}
