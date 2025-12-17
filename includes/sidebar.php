<?php
  $page = substr($_SERVER['SCRIPT_NAME'], strrpos($_SERVER['SCRIPT_NAME'],"/") + 1);
  $isAdmin = !empty($_SESSION['auth']) && (($_SESSION['auth_user']['role'] ?? '') === 'admin');
?>

<style>
.sidebar{
  width: 280px;
  z-index: 1030;
  background: #0f172a;            
  color: #e5e7eb;
  border-right: 1px solid rgba(255,255,255,.08);
  box-shadow: 0 10px 30px rgba(0,0,0,.35);
}

/* Brand */
.sidebar .brand{
  padding: 18px 18px 12px;
}
.sidebar .brand-badge{
  width: 44px;
  height: 44px;
  border-radius: 14px;
  background: rgba(25,135,84,.12);
  border: 1px solid rgba(25,135,84,.25);
  display: grid;
  place-items: center;
  color: #33d29b;
}
.sidebar .brand-title{
  font-weight: 800;
  letter-spacing: .2px;
  line-height: 1.1;
}
.sidebar .brand-sub{
  font-size: .85rem;
  color: rgba(229,231,235,.7);
}

/* Nav */
.sidebar .nav{
  padding: 10px;
}
.sidebar .nav-link{
  color: rgba(229,231,235,.85) !important;
  border-radius: 14px;
  padding: 10px 12px;
  transition: 180ms ease;
  border: 1px solid transparent;
  background: transparent;
}
.sidebar .nav-link i{
  width: 22px;
  display: inline-grid;
  place-items: center;
  opacity: .9;
}
.sidebar .nav-link:hover{
  background: rgba(255,255,255,.06);
  border-color: rgba(255,255,255,.08);
  transform: translateX(2px);
}

/* Active */
.sidebar .nav-pills .nav-link.active{
  background: rgba(25,135,84,.12) !important;
  border-color: rgba(25,135,84,.28) !important;
  color: #eafff6 !important;
}
.sidebar .nav-pills .nav-link.active i{
  color: #33d29b;
}

/* Divider */
.sidebar .divider{
  border-color: rgba(255,255,255,.10) !important;
  margin: 0;
}

/* Bottom (logout) */
.sidebar .sidebar-footer{
  padding: 16px;
}
.sidebar .btn-logout{
  border-radius: 14px;
  padding: 10px 12px;
  font-weight: 700;
  border: 1px solid rgba(255,255,255,.12);
  background: rgba(255,255,255,.06);
  color: #fff;
}
.sidebar .btn-logout:hover{
  background: rgba(255,255,255,.10);
}

.main-with-sidebar{
  margin-left: 280px;
}

</style>


<aside class="position-fixed top-0 start-0 vh-100 d-flex flex-column sidebar">

  <div class="brand d-flex align-items-center gap-3">
    <div class="brand-badge">
      <i class="bi bi-book-half fs-4"></i>
    </div>
    <div class="text-start">
      <div class="brand-title">Library Management</div>
      <div class="brand-sub"><?= $isAdmin ? 'Admin Panel' : 'User Dashboard' ?></div>
    </div>
  </div>

  <hr class="divider">

  <div class="flex-grow-1 overflow-auto">
    <ul class="nav nav-pills flex-column gap-2">

      <?php if($isAdmin): ?>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2 <?= $page == "index.php" ? 'active' : ''; ?>"
             href="../admin/index.php">
            <i class="bi bi-house-door-fill fs-5"></i>
            <span>Dashboard</span>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2 <?= $page == "manageUsers.php" ? 'active' : ''; ?>"
             href="../admin/manageUsers.php">
            <i class="bi bi-people-fill fs-5"></i>
            <span>Manage Users</span>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2 <?= $page == "manageBooks.php" ? 'active' : ''; ?>"
             href="../admin/manageBooks.php">
            <i class="bi bi-journal-text fs-5"></i>
            <span>Manage Books</span>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2 <?= $page == "aiassistant.php" ? 'active' : ''; ?>"
             href="../admin/aiassistant.php">
            <i class="bi bi-robot fs-5"></i>
            <span>AI Assistant</span>
          </a>
        </li>

      <?php else: ?>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2 <?= $page == "index.php" ? 'active' : ''; ?>"
             href="index.php">
            <i class="bi bi-house-door-fill fs-5"></i>
            <span>Dashboard</span>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2 <?= $page == "mybooks.php" ? 'active' : ''; ?>"
             href="mybooks.php">
            <i class="bi bi-journal-bookmark-fill fs-5"></i>
            <span>My Books</span>
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link d-flex align-items-center gap-2 <?= $page == "aiassistant.php" ? 'active' : ''; ?>"
             href="aiassistant.php">
            <i class="bi bi-robot fs-5"></i>
            <span>AI Assistant</span>
          </a>
        </li>
      <?php endif; ?>

    </ul>
  </div>

  <div class="sidebar-footer">
    <a class="btn btn-logout w-100"
       href="<?= $isAdmin ? '../logout.php' : 'logout.php' ?>">
      <i class="bi bi-box-arrow-right me-2"></i> Logout
    </a>
  </div>

</aside>