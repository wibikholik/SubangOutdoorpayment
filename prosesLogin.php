<?php
session_start();
include 'route/koneksi.php';

// Jika logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php?message=logout");
    exit;
}

if (isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        header("Location: login.php?message=error");
        exit;
    }

    // Multi-role login
    $roles = [
        ['table' => 'admin', 'id' => 'id_admin', 'username_field' => 'username', 'redirect' => 'admin/index_admin.php'],
        ['table' => 'owner', 'id' => 'id_owner', 'username_field' => 'username', 'redirect' => 'owner/index_owner.php'],
        ['table' => 'penyewa', 'id' => 'id_penyewa', 'username_field' => 'nama_penyewa', 'redirect' => 'penyewa/page/produk.php'],
    ];

    foreach ($roles as $role) {
        $sql = "SELECT * FROM {$role['table']} WHERE {$role['username_field']} = ? AND password = ? LIMIT 1";
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $username, $password);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user[$role['id']];
            $_SESSION['username'] = $user[$role['username_field']];
            $_SESSION['role'] = $role['table'];

            // SET PESAN SUKSES DI SINI
            $_SESSION['success_message'] = "Selamat datang, " . $user[$role['username_field']] . "!";

            header("Location: {$role['redirect']}");
            exit;
        }
    }

    // Jika tidak ditemukan user yang cocok
    header("Location: login.php?message=error");
    exit;
} else {
    header("Location: login.php");
    exit;
}
?>
