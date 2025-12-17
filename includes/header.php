<?php  
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $isAdmin = !empty($_SESSION['auth']) && (($_SESSION['auth_user']['role'] ?? '') === 'admin');
    $base = $isAdmin ? '../' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?= $base ?>assets/img/libicon1.png" type="image/png">
    <title>Library Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="stylesheet" href="<?= $base ?>assets/css/styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


</head>

<style>
    .bg-primary-soft { background: rgba(25,135,84,.12) !important; }
    .text-primary-solid { color: #198754 !important; }
    .border-primary-solid { border-color: #198754 !important; }

    .btn-primary-soft{
    background: rgba(25,135,84,.12) !important;
    color: #198754 !important;
    border: 1px solid rgba(25,135,84,.25) !important;
    }
    .btn-primary-soft:hover{
    background: rgba(25,135,84,.18) !important;
    }

    .bg-secondary-blue { background: #0d6efd !important; color: #fff !important; }
    .bg-secondary-purple { background: #6f42c1 !important; color: #fff !important; }
    .bg-secondary-orange { background: #fd7e14 !important; color: #fff !important; }
    .bg-secondary-teal { background: #20c997 !important; color: #fff !important; }
    .bg-secondary-gray { background: #6c757d !important; color: #fff !important; }

    .bg-secondary-blue-soft { background: rgba(13,110,253,.12) !important; }
    .bg-secondary-purple-soft { background: rgba(111,66,193,.12) !important; }
    .bg-secondary-orange-soft { background: rgba(253,126,20,.12) !important; }
    .bg-secondary-teal-soft { background: rgba(32,201,151,.12) !important; }
    .bg-secondary-gray-soft { background: rgba(108,117,125,.12) !important; }

    /* layout */
.sidebar-fixed{
  position: fixed;
  top: 0;
  left: 0;
  width: 280px;
  height: 100vh;
  overflow-y: auto;
  z-index: 1030;
  transition: transform .25s ease;
}

.main-content{
  margin-left: 0;
  transition: margin-left .25s ease;
}

/* desktop: sidebar on by default */
@media (min-width: 992px){
  body.has-sidebar .main-content{ margin-left: 280px; }
  body.sidebar-collapsed .sidebar-fixed{ transform: translateX(-100%); }
  body.sidebar-collapsed .main-content{ margin-left: 0; }
}

/* mobile: sidebar hidden (offcanvas style) */
@media (max-width: 991.98px){
  .sidebar-fixed{ transform: translateX(-100%); }
  body.sidebar-open .sidebar-fixed{ transform: translateX(0); }
}

</style>
<body class="g-sidenav-show bg-gray-200 <?= isset($_SESSION['auth']) ? 'has-sidebar' : '' ?>">

<?php if (isset($_SESSION['auth'])): ?>

  <div id="appSidebar" class="sidebar-fixed bg-white border-end shadow-sm">
    <?php include('sidebar.php'); ?>
  </div>
  <main class="p-3 main-content">
    <nav class="navbar navbar-expand-lg bg-transparent px-3 py-2 rounded-4 ">
      <div class="container-fluid">

        <button id="sidebarToggle" class="btn btn-primary-soft me-2" type="button">
          <i class="bi bi-list"></i>
        </button>
      </div>
    </nav>
<?php else: ?>
  <main class="p-3 w-100">
    <div class="container-fluid px-0">
      <!-- content-i (login/register/etj) full width -->
<?php endif; ?>


<script>
  (function () {
    const btn = document.getElementById('sidebarToggle');
    if (!btn) return;

    const isDesktop = () => window.matchMedia('(min-width: 992px)').matches;

    btn.addEventListener('click', function () {
      if (isDesktop()) {
        document.body.classList.toggle('sidebar-collapsed');
      } else {
        document.body.classList.toggle('sidebar-open');
      }
    });

    // kur kalon nga mobile në desktop, pastro "sidebar-open"
    window.addEventListener('resize', function () {
      if (isDesktop()) document.body.classList.remove('sidebar-open');
    });
  })();
</script>
