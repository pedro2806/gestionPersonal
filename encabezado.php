<!-- Topbar -->
<nav class = "navbar navbar-expand navbar-light bg-white topbar mb-2 static-top shadow">
<!-- Enlace a Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Enlace a Bootstrap JS (necesario para el funcionamiento del modal) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<!-- Enlace a FontAwesome para los íconos (si usas íconos) -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Topbar Navbar -->
<ul class = "navbar-nav ml-auto">
    <!-- Nav Item - Search Dropdown (Visible Only XS) -->
    <li class = "nav-item dropdown no-arrow d-sm-none">
        <a class = "nav-link dropdown-toggle" href = "#" id = "searchDropdown" role = "button"
            data-toggle = "dropdown" aria-haspopup = "true" aria-expanded = "false">
            <i class = "fas fa-search fa-fw"></i>
        </a>
        
    </li>    
    <!-- Nav Item - User Information -->
    <li class = "nav-item dropdown no-arrow">
        <a class = "nav-link dropdown-toggle" href = "#" id = "userDropdown" role = "button"
            data-toggle = "dropdown" aria-haspopup = "true" aria-expanded = "false">
            <span class = "mr-2 d-none d-lg-inline text-gray-600 small">
                <?php echo $_COOKIE['nombredelusuario']?>
            </span>
            <?php
            $currentURL = $_SERVER['REQUEST_URI']; // Obtiene la ruta actual de la URL

            if (strpos($currentURL, "/incidencias/SalasDeJuntas/") !== false || 
                strpos($currentURL, "/incidencias/inicio") !== false) {
                echo '<img class="img-profile rounded-circle" 
                    src="/incidencias/img/undraw_profile.svg" 
                    style="width: 100%;">';
            } else {
                echo '<img class="img-profile rounded-circle" 
                    src="/incidencias/img/undraw_profile.svg"  
                    style="width: 100%;">';
            }
            ?>
        </a>
        <!-- Dropdown - User Information -->
        <div class = "dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby = "userDropdown">
            <!--<button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
                <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                Cambiar Contraseña
            </button>

            <a class = "dropdown-item" href = "#">
                <i class = "fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                Settings
            </a>
            <a class = "dropdown-item" href = "#">
                <i class = "fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i>
                Activity Log
            </a>-->
            <div class = "dropdown-divider"></div>
            <a class = "dropdown-item" href = "#" data-toggle = "modal" data-target = "#logoutModalN">
                <i class = "fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                Salir
            </a>
        </div>
    </li>

</ul>
    <!-- Logout Modal-->
    <div class = "modal fade" id = "logoutModalN" tabindex = "-1" role = "dialog" aria-labelledby = "exampleModalLabel"aria-hidden = "true">
        <div class = "modal-dialog" role = "document">
            <div class = "modal-content border-left-danger">
                <div class = "modal-header">
                    <h4 class = "modal-title" id = "exampleModalLabel"> Cerrar sesión </h4>
                    <button class = "close" type = "button" data-dismiss = "modal" aria-label = "Close">
                        <span aria-hidden = "true">X</span>
                    </button>
                </div>
                <div class = "modal-body"><h5><b>¿Estas seguro?</b></h5></div>
                <div class = "modal-footer">
                    <button class = "btn btn-warning" type = "button" data-dismiss = "modal">Cancelar</button>
                    <a class = "btn btn-danger" href = "logout">Salir</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Función para mostrar/ocultar contraseñas
    /*document.getElementById('showPassword').addEventListener('change', function () {
        var passwordField = document.getElementById('nuevapass');
        var confirmPasswordField = document.getElementById('confirmapass');
        
        if (this.checked) {
          // Mostrar contraseñas (tipo 'text')
            passwordField.type = 'text';
            confirmPasswordField.type = 'text';
        } else {
          // Ocultar contraseñas (tipo 'password')
          passwordField.type = 'password';
          confirmPasswordField.type = 'password';
        }
    });*/
    
    //Funcion para validar las contraseñas
    function validarContrasenas() {
        var password = $('#nuevapass').val()
        var confirmPassword = $('#confirmapass').val()
        var error = document.getElementById("error");

        // Si las contraseñas no coinciden
        if (password !== confirmPassword) {
            $('#msgPassword').text("Las constraseñas no coinciden."); 
        } else {
            Confirmar();
        }
    }
    
    //Funcion para Enviar los datos
    function Confirmar(){
        var password = $('#nuevapass').val();
        var noEmpleado = $('#noEmpleado').val();
        var accion = "CambioPassword";
        
        $.ajax({
            url: 'acciones_contrasena.php',
            method: 'POST',
            async: false,
            dataType: 'json',
            data:{accion, password, noEmpleado},
            success: function(Registros) {
                Swal.fire({
                    title: "Confirmado!",
                    text: "Contraseña cambiada!",
                    icon: "success",
                    timer: 2000,
                    timerProgressBar: true
                }).then(function() {
                    // Limpiar los campos después de cerrar la alerta
                    $('#nuevapass').val('');
                    $('#confirmapass').val('');
                    $('#staticBackdrop').modal('hide');
                });
            },error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error al aplicar el cambio', error);
            }
        });
    }

    //Funcion para leer cookies
    function getCookie(name) {
        let value = "; " + document.cookie;
        let parts = value.split("; " + name + "=");
        if (parts.length === 2) return parts.pop().split(";").shift();
        return null; // Si no encuentra la cookie, retorna null
    }
    // Asignar el valor de la cookie al input
    window.onload = function() {
        var cookieValue = getCookie("noEmpleado"); // Aquí "noEmpleadoCookie" es el nombre de la cookie
    
        // Verificar si la cookie existe y asignar el valor al input
        if (cookieValue) {
            //document.getElementById("noEmpleado").value = cookieValue;
        }
    };
    </script>
</nav>
<!-- End of Topbar -->