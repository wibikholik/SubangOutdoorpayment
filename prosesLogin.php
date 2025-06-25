<?php
session_start();
include 'route/koneksi.php'; // Menghubungkan ke file koneksi database

// ======= LOGOUT HANDLER =======
if (isset($_GET['logout'])) {
    session_unset(); // Menghapus semua variabel sesi
    session_destroy(); // Menghancurkan sesi
    header("Location: login.php?message=logout"); // Redirect ke halaman login dengan pesan logout
    exit;
}

// ======= LOGIN HANDLER =======
if (isset($_POST['login'])) {
    // Mengambil dan membersihkan input username dan password
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validasi input kosong
    if ($username === '' || $password === '') {
        header("Location: login.php?message=error"); // Redirect ke login dengan pesan error
        exit;
    }

    // ======= MULTI-ROLE LOGIN SETUP =======
    $roles = [
        [
            'table' => 'admin', // Nama tabel admin
            'id' => 'id_admin', // Primary key
            'username_field' => 'username', // Kolom username
            'redirect' => 'admin/index_admin.php' // Halaman redirect setelah login berhasil
        ],
        [
            'table' => 'owner',
            'id' => 'id_owner',
            'username_field' => 'username',
            'redirect' => 'owner/index_owner.php'
        ],
        [
            'table' => 'penyewa',
            'id' => 'id_penyewa',
            'username_field' => 'nama_penyewa',
            'redirect' => 'penyewa/page/produk.php'
        ],
    ];

    // ======= PROSES PENCARIAN USER PADA MASING-MASING ROLE =======
    foreach ($roles as $role) {
        // Persiapkan query dengan parameter binding untuk keamanan
        $sql = "SELECT * FROM {$role['table']} WHERE {$role['username_field']} = ? AND password = ? LIMIT 1";
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $username, $password); // Bind parameter username dan password
        mysqli_stmt_execute($stmt); // Eksekusi query
        $result = mysqli_stmt_get_result($stmt); // Ambil hasil

        // Jika ditemukan satu user yang cocok
        if ($result && mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result); // Ambil data user

            // Atur sesi pengguna
            session_regenerate_id(true); // Cegah session fixation
            $_SESSION['user_id'] = $user[$role['id']]; // Simpan ID user
            $_SESSION['username'] = $user[$role['username_field']]; // Simpan username
            $_SESSION['role'] = $role['table']; // Simpan role (admin/owner/penyewa)

            // Set pesan selamat datang
            $_SESSION['success_message'] = "Selamat datang, " . $user[$role['username_field']] . "!";

            // Redirect ke halaman sesuai role
            header("Location: {$role['redirect']}");
            exit;
        }
    }

    // Jika tidak ditemukan user di semua role
    header("Location: login.php?message=error");
    exit;
} else {
    // Jika halaman ini diakses tanpa submit login, redirect ke login
    header("Location: login.php");
    exit;
}
?>
