<?php
    include 'conn.php';
    if(empty($_COOKIE['noEmpleadoGP'])){
        //echo '<script>window.location.assign("../loginMaster")</script>';
    }
?>
<style>        
    .text-bg-orange {
        --bs-bg-opacity: 1;
        background-color: #ff7300ff !important;
        color: #ffffffff !important;
    }
    .btn-logistica{
        --bs-bg-opacity: 1;
        background-color: #bf00ffff !important;
        color: #ffffffff !important;
    }
</style>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
<!-- Sidebar - Brand -->
<a class="sidebar-brand d-flex align-items-center justify-content-center" href="index">
    <div class="sidebar-brand-icon rotate-n-1">
        <img class="sidebar-card-illustration mb-2" href="" src="img/MESS_07_CuboMess_2.png" width="40" alt="Logo">
    </div>
</a>
<!-- Heading -->
<div class="sidebar-heading">
    <span class="badge text-xl-white">Opciones</span>
</div>
<!-- Divider -->
<hr class="sidebar-divider my-0 alert-light">
<li class = "nav-item">
    <a class = "nav-link" href = "index">
        <i class = "fas fa-home"></i>
        Inicio
    </a>
</li>
<hr class="sidebar-divider my-2 alert-light">

<li class="nav-item">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseActivos">
        <i class="fas fa-fw fa-list  text-gray-400"></i>
        <span>Admin</span>
    </a>
    <div id="collapseActivos" class="collapse" data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            <a class="collapse-item" href="admin_personal">Administrar Personal</a>
            <a class="collapse-item" href="config_documentos">Configurar Documentos</a>
            <a class="collapse-item" href="validacion">Auditoría y Validación</a>
            <a class="collapse-item" href="organigrama">Organigrama</a>            
        </div>
    </div>
</li>

<hr class="sidebar-divider my-0 alert-light">
<li class = "nav-item">
    <a class = "nav-link" href = "../loginMaster/inicio">
        <i class = "fas fa-sign-out-alt"></i>
        Salir
    </a>
</li>

<hr class="sidebar-divider my-1 alert-light">

<div class="text-center d-none d-md-inline">
    <button class="rounded-circle border-0" id="sidebarToggle"></button> 
</div>
</ul>
<script>
    $(document).ready(function() {        

    });
</script>