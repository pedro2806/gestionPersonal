////    Función para guardar un nuevo activo
        function guardarActivo() {
            // 1. Obtener el formulario HTML
            var formElement = document.getElementById('formActivos');
            
            // 2. Crear objeto FormData (Captura automáticamente todos los inputs, selects y archivos)
            var formData = new FormData(formElement);

            // 3. Agregar datos manuales que no estén en inputs o que requieran lógica extra
            formData.append('opcion', 'nuevoActivo'); // Tu identificador para PHP

            // Lógica del Checkbox (FormData solo lo incluye si está checked, aquí forzamos 1 o 0)
            var isChecked = document.getElementById('checkEsAccesorio').checked;
            formData.set('es_accesorio', isChecked ? '1' : '0'); 
            
            var isChecked = document.getElementById('checkEsPrestamo').checked;
            formData.set('es_prestamo', isChecked ? '1' : '0'); 

            // Nota: Usamos .set() para sobrescribir si el input ya existía en el form

            // 4. Enviar vía AJAX
            $.ajax({
                url: 'acciones_activos.php',
                method: 'POST',
                data: formData,         // Enviamos el objeto FormData directo
                
                // --- ESTAS DOS LÍNEAS SON OBLIGATORIAS PARA ARCHIVOS ---
                processData: false,     // Evita que jQuery transforme la data a string
                contentType: false,     // Evita que jQuery pongan cabeceras incorrectas
                // -------------------------------------------------------
                
                dataType: 'json',
                success: function(data) {
                    if (data.status === 'success') {
                        Swal.fire({
                            title: "¡Guardado!",
                            text: "La actividad se registró con éxito.",
                            icon: "success",
                            confirmButtonText: "Aceptar"
                        }).then((result) => {
                            if (result.isConfirmed) {
                                location.href = 'verActivos'; // Redirige a la lista de activos
                                formElement.reset(); // Limpia el formulario
                            }
                        });
                    } else {
                        Swal.fire({
                            title: "Error",
                            text: data.message,
                            icon: "error"
                        });
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error(textStatus, errorThrown);
                    Swal.fire({
                        title: "Error de Servidor",
                        text: "No se pudo registrar la actividad. Revise la consola.",
                        icon: "error"
                    });
                }
            });
        }

////    funcion para ver los activos
        async function verActivos() {
            const permiso = await verificarAcceso();

            $.ajax({
                url: 'acciones_activos.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    opcion: 'verActivos'
                },
                success: function(data) {                    
                    // 1. Obtener instancia de DataTable
                    var table = $('#tablaActivos').DataTable();
                    
                    // 2. Limpiar tabla
                    table.clear(); 
                    
                    // 3. Iterar y crear filas
                    data.forEach(function(activo) {
                        
                        // Formateo de moneda para que se vea bien
                        let formatoDinero = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

                        // Lógica visual para Tipos (Badges)
                        let badgeTipo = '';
                        if(activo.tipo_activo.includes('COMPUTO')) {
                            badgeTipo = '<span class="badge bg-primary">'+activo.tipo_activo+'</span>';
                        } else if (activo.tipo_activo.includes('OFICINA')) {
                            badgeTipo = '<span class="badge bg-secondary">'+activo.tipo_activo+'</span>';
                        } else if (activo.tipo_activo.includes('MAQUINAS')) {
                            badgeTipo = '<span class="badge bg-info text-dark">'+activo.tipo_activo+'</span>';
                        } else if (activo.tipo_activo.includes('HERRAMIENTAS')) {
                            badgeTipo = '<span class="badge bg-warning text-dark">'+activo.tipo_activo+'</span>';
                        } else {
                            badgeTipo = '<span class="badge bg-dark text-white">'+activo.tipo_activo+'</span>';
                        }

                        let badgeTipoActivo = '';
                        if(activo.prestamo == 1) {
                            badgeTipoActivo = ' / <span class="badge bg-success">Préstamo/Renta</span>';
                        } else {
                            badgeTipoActivo = '';
                        }

                        let badgeEstatusPrestamo = '';
                        if(activo.estatus_prestamo == 1) {
                            badgeEstatusPrestamo = '<span class="badge bg-danger">En Préstamo / Renta</span>';
                        } else {
                            badgeEstatusPrestamo = '';
                        }

                        let opciones = '<i class="fas fa-fw fa-eye"></i>';
                        if(permiso === 'Edita') {
                            opciones = `
                                <div class="d-flex justify-content-center gap-0">
                                    <a href="reporteRecepcionActivo.php?id=${activo.id}" target="_blank" class="btn btn-sm btn-outline-danger" title="Generar PDF">
                                        <i class="fas fa-fw fa-file-pdf"></i>
                                    </a>
                                    <button class="btn btn-sm btn-outline-primary" title="Ver Detalles" onclick="verDetallesActivo(${activo.id})">
                                        <i class="fas fa-fw fa-eye"></i> 
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning" title="Editar Activo" onclick="editarActivo(${activo.id})">
                                        <i class="fas fa-fw fa-pen"></i> 
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" title="Eliminar Activo" onclick="eliminarActivo(${activo.id})">
                                        <i class="fas fa-fw fa-trash"></i>
                                    </button>                                    
                                </div>
                            `;
                        }
                        
                        var fila = [
                            activo.fecha_registro,
                            activo.fecha_adquisicion,                            
                            badgeTipo + badgeTipoActivo,
                            activo.descripcion,
                            activo.marca + ' / ' + activo.modelo,                            
                            `<span class="fw-bold">${activo.nave + ' / ' + activo.ubicacion }</span>`,
                            activo.usuario,
                            formatoDinero.format(activo.costo) + ' / ' +
                            `<span class="fw-bold text-success">${formatoDinero.format(activo.remanente)}</span>`,
                            badgeEstatusPrestamo +' / ' + activo.observaciones,
                            opciones
                        ];
                        
                        // 4. Agregar fila a DataTables
                        table.row.add(fila);
                    });
                    
                    // 5. Dibujar (Renderizar) cambios
                    table.draw();


                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Swal.fire({
                        title: "No se pudieron cargar los activos!",
                        icon: "error",      
                        draggable: true
                    });
                }
            });
        }

////    función para ver detalles del activo
        function verDetallesActivo(idActivo) {
            // Redirigir a la página de detalles con el ID del activo como parámetro
            window.location.href = 'detallesActivo.php?id=' + idActivo;
        }

//// funcion para cargar detalle del activo
        function cargarDetalleActivo() {
            // 1. Obtener el ID de la URL
            var urlParams = new URLSearchParams(window.location.search);
            var idActivo = urlParams.get('id');
                
            // Validación simple: Si no hay ID en la URL, no intentamos buscar
            if (!idActivo) return;

            $.ajax({
                url: 'acciones_activos.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    opcion: 'detalleActivo',
                    idActivo: idActivo
                },
                success: function(data) {
                    
                    if (data.status === 'success') {
                        var activo = data.activo;                        
                        // --- A. CONFIGURACIÓN DE FORMATO DE MONEDA ---
                        const formatoDinero = new Intl.NumberFormat('es-MX', { 
                            style: 'currency', 
                            currency: 'MXN' 
                        });

                        // --- B. LLENADO DE CAMPOS BÁSICOS ---
                        // Nota: Los IDs (#detalle...) deben existir en tu HTML
                        $('#detalleDescripcion').text(activo.descripcion);
                        $('#detalleMarca').text(activo.marca);
                        $('#detalleModelo').text(activo.modelo);
                        $('#detalleNoSerie').text(activo.no_serie || 'Sin Número de Serie'); // Manejo de nulos
                        $('#detalleIdInterno').text(activo.id_interno);
                        
                        // Ubicación y Usuario
                        $('#detalleNave').text(activo.nave);
                        $('#detalleUsuario').text(activo.usuario);
                        // Si agregaste región en el SQL:
                        if(activo.region) $('#detalleRegion').text(activo.region);

                        // --- C. DATOS FINANCIEROS ---
                        $('#detalleMoi').text(formatoDinero.format(activo.moi));
                        $('#detalleCosto').text(formatoDinero.format(activo.costo));
                        $('#detalleDepreciacion').text(formatoDinero.format(activo.depreciacion));
                        
                        // Lógica de color para el Remanente
                        var remanenteVal = parseFloat(activo.remanente);
                        var elRemanente = $('#detalleRemanente');
                        
                        elRemanente.text(formatoDinero.format(remanenteVal));
                        
                        if (remanenteVal > 0) {
                            elRemanente.addClass('text-success fw-bold').removeClass('text-danger');
                        } else {
                            elRemanente.addClass('text-danger fw-bold').removeClass('text-success');
                        }

                        $('#detalleObservaciones').text(activo.observaciones || "Sin observaciones.");

                        // --- D. LÓGICA DE TIPO DE ACTIVO Y CAMPOS TÉCNICOS ---
                        // Elementos del DOM
                        var badgeContainer = $('#detalleTipoBadge'); // Contenedor para la etiqueta
                        var seccionTecnica = $('#seccionComputo');   // El div que tiene CPU y Monitor
                        
                        // 1. Es Computadora
                        if (activo.tipo_activo && activo.tipo_activo.includes('COMPUTO', 'CÓMPUTO')) {
                            // Badge Azul
                            badgeContainer.html('<h3><span class="badge bg-primary"><i class="bi bi-laptop"></i> CÓMPUTO</span></h3>');
                            
                            // Mostrar Sección Técnica y llenar datos
                            seccionTecnica.removeClass('d-none');
                            $('#detalleCpu').text(activo.cpu_info || 'N/A');
                            $('#detalleMonitor').text(activo.monitor_info || 'N/A');

                        // 2. Es Accesorio (Si agregaste el campo 'es_accesorio' al SQL)
                        } else if (activo.es_accesorio == 1) {
                            // Badge Gris
                            badgeContainer.html('<span class="badge bg-secondary"><i class="bi bi-plug"></i> ACCESORIO</span>');
                            seccionTecnica.addClass('d-none'); // Ocultar técnica

                        // 3. Otro (Mobiliario, Vehículo, etc.)
                        } else {
                            // Badge Info Standard
                            badgeContainer.html('<span class="badge bg-info text-dark">' + activo.tipo_activo + '</span>');
                            seccionTecnica.addClass('d-none'); // Ocultar técnica
                        }

                    } else {
                        // El ID no existe en BD
                        Swal.fire({
                            title: "Activo no encontrado",
                            text: data.message || "El ID solicitado no existe.",
                            icon: "warning"
                        });
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("Error AJAX:", errorThrown);
                    Swal.fire({
                        title: "Error de Conexión",
                        text: "No se pudieron cargar los detalles. Intente recargar la página.",
                        icon: "error"
                    });
                }
            });
        }


////    funciión para obtener datos del formulario
        function getFormData(formId) {
            var formArray = $('#' + formId).serializeArray();
            var formData = {};
            formArray.forEach(function(item) {
                formData[item.name] = item.value;
            });
            return formData;
        }        
////    funcnion para cargar empleados en el select
        function getEmpleados(seleccionado) {
            opcion = "getEmpleados";

            $.ajax({
                url: 'acciones_activos.php',
                method: 'POST',
                dataType: 'json',
                data: {opcion},
                success: function(data) {
                    var select = $(seleccionado);
                    i = 0;
                    data.forEach(function(usuarios) {
                        if (i = 0) {
                            var option = $('<option></option>').attr('value', '0').text('Selecciona...');
                            select.append(option);
                        }
                        var option = $('<option></option>').attr('value', usuarios.noEmpleado).text(usuarios.nombre);
                        select.append(option);
                    });

                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Swal.fire({
                        title: "La solicitúd no se pudo procesar!",
                        icon: "error",
                        draggable: true
                    });

                }
            });

        }
        
////    Función para calcular el remanente
        function calcularRemanente() {
            var moi = parseFloat(document.getElementById('inputMoi').value) || 0;
            var depreciacion = parseFloat(document.getElementById('inputDepreciacion').value) || 0;
            var remanente = moi - depreciacion;
            document.getElementById('inputRemanente').value = remanente.toFixed(2);
        }

/////   Función para convertir texto a mayúsculas y quitar acentos
        function convertirTexto(e) {
            // Convertir a mayúsculas y quitar acentos
            e.value = e.value
            .toUpperCase()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "");
        }
////    Función para obtener el valor de una cookie
        function getCookie(name) {
            const cookies = new URLSearchParams(document.cookie.replace(/; /g, '&'));
            return cookies.get(name) || undefined;
        }

////    Función para eliminar un activo
    function eliminarActivo(id) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "¡No podrás revertir esto! El activo se eliminará permanentemente.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminarlo',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Si el usuario dice que SÍ, hacemos la petición AJAX
                $.ajax({
                    url: 'acciones_activos.php',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        opcion: 'eliminarActivo',
                        idActivo: id
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire(
                                '¡Eliminado!',
                                'El activo ha sido eliminado.',
                                'success'
                            );
                            // Recargamos la tabla para que desaparezca la fila
                            verActivos(); 
                        } else {
                            Swal.fire('Error', response.message || 'No se pudo eliminar', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Hubo un problema de conexión', 'error');
                    }
                });
            }
        });
    }

    function editarActivo(id) {
        // Redirigir a la página de edición con el ID del activo como parámetro
        window.location.href = 'editarActivo.php?id=' + id;
    }
// FUNCIÓN PARA LLENAR EL FORMULARIO
    function cargarDatosParaEditar(id) {
        $.ajax({
            url: 'acciones_activos.php',
            method: 'POST',
            dataType: 'json',
            data: { opcion: 'detalleActivo', idActivo: id }, // Reusamos tu función de detalle
            success: function(data) {
                if(data.status === 'success') {
                    const act = data.activo;
                    
                    // Llenar campos simples
                    $('#editId').val(act.id);
                    $('#editDescripcion').val(act.descripcion);
                    $('#editMarca').val(act.marca);
                    $('#editModelo').val(act.modelo);
                    $('#editSerie').val(act.no_serie);
                    $('#editIdInterno').val(act.id_interno);
                    
                    $('#editMoi').val(act.moi);
                    $('#editDepreciacion').val(act.depreciacion);
                    $('#editRemanente').val(act.remanente);
                    $('#editObservaciones').val(act.observaciones);
                                        
                    $('#editTipoActivo').val(act.id_tipo_activo || 1).trigger('change');
                    $('#editNave').val(act.id_nave);
                    $('#editUbicacion').val(act.ubicacion);
                    
                    var opcionUsuario = new Option(act.usuario, act.id_usuario, true, true);                    
                    $('#editSlcResponsable').append(opcionUsuario).trigger('change');

                    $('#editSelectRegion').val(act.id_region); 
                    $('#editFechaAdquisicion').val(act.fecha_adquisicion);

                    // Llenar Checkbox
                    $('#editEsAccesorio').prop('checked', act.es_accesorio == 1);
                    $('#editEsPrestamo').prop('checked', act.prestamo == 1);

                    // Llenar Técnicos
                    $('#editCpu').val(act.cpu_info);
                    $('#editMonitor').val(act.monitor_info);

                } else {
                    Swal.fire('Error', 'No se encontró el activo', 'error');
                }
            }
        });
    }

    // FUNCION PARA CARGAR REGIONES
    function getRegiones(seleccionado) {
        opcion = "getRegiones";
        $.ajax({
            url: 'acciones_activos.php',
            method: 'POST',
            dataType: 'json',
            data: {opcion},
            success: function(data) {
                var select = $(seleccionado);
                i = 0;
                data.forEach(function(region) {
                    if (i = 0) {
                        var option = $('<option></option>').attr('value', '').text('Selecciona...');
                        select.append(option);
                    }   
                    var option = $('<option></option>').attr('value', region.id).text(region.region);
                    select.append(option);
                });  
            },  
            error: function(jqXHR, textStatus, errorThrown) {
                Swal.fire({
                    title: "La solicitúd no se pudo procesar!",
                    icon: "error",
                    draggable: true
                });
            }
        });
    }

    // FUNCION PARA CARGAR FOTOS EXISTENTES EN EL EDITAR ACTIVO
    function cargarFotosExistentes(idActivo) {
        $.ajax({
            url: 'acciones_activos.php',
            method: 'POST',
            dataType: 'json',
            data: { opcion: 'getFotos', idActivo },
            success: function(data) {
                if(data.status === 'success') {
                    var contenedor = $('#fotosExistentes');
                    contenedor.empty(); // Limpiar fotos anteriores
                    data.fotos.forEach(function(foto) {
                        var fotoDiv = $(`
                            <div class="position-relative d-inline-block m-1">
                                <img src="${foto.ruta_foto}" class="img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0" title="Eliminar Foto" onclick="eliminarFoto(${foto.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>                            
                        `);
                        contenedor.append(fotoDiv);
                    });
                } else {
                    $('#fotosExistentes').html('<p>No hay fotos disponibles.</p>');
                }   
            },
            error: function() {
                $('#fotosExistentes').html('<p>Error al cargar las fotos.</p>');
            }
        });
    }

    // FUNCION PARA ELIMINAR FOTO
    function eliminarFoto(idFoto) {
        Swal.fire({
            title: '¿Eliminar esta foto?',
            text: "¡Esta acción no se puede deshacer!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar', 
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'acciones_activos.php',
                    method: 'POST',
                    dataType: 'json',
                    data: { opcion: 'eliminarFoto', idFoto, idActivo: new URLSearchParams(window.location.search).get('id') },
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire('¡Eliminada!', 'La foto ha sido eliminada.', 'success');
                            // Recargar fotos después de eliminar
                            const urlParams = new URLSearchParams(window.location.search);
                            const idActivo = urlParams.get('id');
                            cargarFotosExistentes(idActivo);
                        } else {
                            Swal.fire('Error', response.message || 'No se pudo eliminar la foto', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Hubo un problema de conexión', 'error');
                    }
                });
            }
        });
    }

// FUNCION PARA SUBIR FOTOS EN EL EDITAR ACTIVO
    function subirFotos() {    
        var inputFotos = document.getElementById('inputFotos');
        if (inputFotos.files.length === 0) {
            Swal.fire('Atención', 'Por favor, selecciona al menos una foto antes de subir.', 'warning');
            return;
        }
        
        var formData = new FormData();

        //Recorrer los archivos seleccionados y agregarlos al FormData    
        for (var i = 0; i < inputFotos.files.length; i++) {
            formData.append('fotos[]', inputFotos.files[i]);
        }

        // 5. Agregar los parámetros extra que necesita tu backend
        formData.append('opcion', 'subirFotos');
        
        // Obtener ID del activo de la URL
        var urlParams = new URLSearchParams(window.location.search);
        var idActivo = urlParams.get('id');
        formData.append('idActivo', idActivo);

        // 6. Enviar la petición AJAX
        $.ajax({
            url: 'acciones_activos.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(data) {
                if (data.status === 'success') {
                    Swal.fire('¡Fotos subidas!', 'Las fotos han sido subidas exitosamente.', 'success');
                    cargarFotosExistentes(idActivo); // Recargar fotos para mostrar las nuevas
                    $('#inputFotos').val(''); // Limpiar el input de archivos
                } else {
                    Swal.fire('Error', data.message || 'No se pudieron subir las fotos.', 'error');
                }
            },
            error: function() {
                Swal.fire('Error de conexión', 'Hubo un problema al comunicarse con el servidor.', 'error');
            }
        });
    }

    // FUNCION PARA CARGAR NAVES
    function getNaves(seleccionado) {
        opcion = "getNaves";
        $.ajax({
            url: 'acciones_activos.php',
            method: 'POST',
            dataType: 'json',
            data: {opcion},
            success: function(data) {
                var select = $(seleccionado);
                i = 0;
                data.forEach(function(nave) {
                    if (i = 0) {
                        var option = $('<option></option>').attr('value', '').text('Selecciona...');
                        select.append(option);
                    }
                    var option = $('<option></option>').attr('value', nave.id).text(nave.nombre);
                    select.append(option);
                }
                );
            },
            error: function(jqXHR, textStatus, errorThrown) {
                Swal.fire({
                    title: "La solicitúd no se pudo procesar!",
                    icon: "error",
                    draggable: true
                });
            }
        });
    }

        