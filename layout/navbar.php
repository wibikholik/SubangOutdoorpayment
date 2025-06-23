<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$username = $_SESSION['username'] ?? 'Guest';
$role = $_SESSION['role'] ?? ''; // Pastikan role sudah disimpan saat login
?>

<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 sticky-top shadow">
    <!-- Sidebar Toggle (Topbar) -->
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>

    <!-- Topbar Navbar -->
    <ul class="navbar-nav ml-auto">

        <?php if ($role === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link" href="../lupa_password_admin.php">
                    <i class="fas fa-key mr-1"></i> Lupa Password
                </a>
            </li>
        <?php elseif ($role === 'owner'): ?>
            <li class="nav-item">
                <a class="nav-link" href="../lupa_password_owner.php">
                    <i class="fas fa-key mr-1"></i> Lupa Password
                </a>
            </li>
        <?php endif; ?>

        <!-- Topbar Divider -->
        <div class="topbar-divider d-none d-sm-block"></div>

        <!-- Nav Item - User Information -->
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?= htmlspecialchars($username); ?></span>
                <img class="img-profile rounded-circle" src="../assets/img/undraw_profile.svg">
            </a>
            <!-- Dropdown - User Information -->
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                 aria-labelledby="userDropdown">
                <a class="dropdown-item" href="../prosesLogin.php?logout=true">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                    Logout
                </a>
            </div>
        </li>
    </ul>
</nav>
