<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FMIS | <?= $title ?? 'CodeIgniter 4' ?></title>
    <link rel="icon" href="<?= base_url('templates') ?>/dist/img/budget.png" type="image/x-icon">
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= base_url('templates') ?>/plugins/fontawesome-free/css/all.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?= base_url('templates') ?>/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="<?= base_url('templates') ?>/dist/css/custom.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- SweetAlert2 CSS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Choices.js CSS & JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    <style>
        .main-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
        }

        .sidebar {
            height: calc(100vh - 57px);
            /* Kurangi tinggi navbar adminlte */
            overflow-y: auto;
            overflow-x: hidden;
            padding-bottom: 30px;
            /* biar scroll nyaman */
        }
    </style>

</head>

<body class="hold-transition sidebar-mini">
    <!-- Site wrapper -->
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
            </ul>

            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                        <i class="fas fa-expand-arrows-alt"></i>
                    </a>
                </li>
            </ul>
        </nav>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
            <a href="<?= site_url('/') ?>" class="brand-link">
                <img src="<?= base_url('templates') ?>/dist/img/budget.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
                <span class="brand-text font-weight-light">FMIS</span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Sidebar user (optional) -->
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="image">
                        <img src="<?= base_url('templates') ?>/dist/img/bussiness-man.png" class="img-circle elevation-2" alt="User Image">
                    </div>
                    <div class="info">
                        <a href="<?= site_url('profile') ?>" class="d-block">
                            <?= esc(session()->get('user_name') ?? 'Guest') ?>
                        </a>
                    </div>
                </div>

                <!-- Sidebar Menu -->
                <?php
                $seg1 = service('uri')->getSegment(1);
                $seg2 = service('uri')->getSegment(2);

                $isDataUtamaOpen = in_array($seg1, ['accounts', 'students', 'graduates', 'classes']) || ($seg1 === 'students' && $seg2 === 'bulk-edit');
                ?>

                <?php if (session()->get('isLoggedIn')): ?>
                    <nav class="mt-2">
                        <ul class="nav nav-pills nav-sidebar flex-column"
                            data-widget="treeview"
                            role="menu"
                            data-accordion="false">

                            <!-- ================= MENU UTAMA ================= -->
                            <li class="nav-header">MENU UTAMA</li>

                            <li class="nav-item">
                                <a href="<?= site_url('home') ?>"
                                    class="nav-link <?= $seg1 === 'home' ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-home"></i>
                                    <p>Home</p>
                                </a>
                            </li>

                            <!-- ================= MASTER DATA ================= -->
                            <li class="nav-header">MASTER DATA</li>

                            <li class="nav-item has-treeview <?= $isDataUtamaOpen ? 'menu-open' : '' ?>">
                                <a href="#"
                                    class="nav-link <?= $isDataUtamaOpen ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-database"></i>
                                    <p>
                                        Data Utama
                                        <i class="fas fa-angle-left right"></i>
                                    </p>
                                </a>

                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="<?= site_url('accounts') ?>"
                                            class="nav-link <?= $seg1 === 'accounts' ? 'active' : '' ?>">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Akun</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="<?= site_url('students') ?>"
                                            class="nav-link <?= ($seg1 === 'students' && !$seg2) ? 'active' : '' ?>">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Siswa / Santri</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="<?= site_url('classes') ?>"
                                            class="nav-link <?= $seg1 === 'classes' ? 'active' : '' ?>">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Kelas</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="<?= site_url('students/bulk-edit') ?>"
                                            class="nav-link <?= ($seg1 === 'students' && $seg2 === 'bulk-edit') ? 'active' : '' ?>">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Edit Siswa Sekaligus</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="<?= site_url('graduates') ?>"
                                            class="nav-link <?= $seg1 === 'graduates' ? 'active' : '' ?>">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Lulusan</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <!-- ================= TRANSAKSI ================= -->
                            <li class="nav-header">TRANSAKSI</li>

                            <li class="nav-item has-treeview <?= in_array($seg1, ['transactions', 'journals']) ? 'menu-open' : '' ?>">
                                <a href="#"
                                    class="nav-link <?= in_array($seg1, ['transactions', 'journals']) ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-exchange-alt"></i>
                                    <p>
                                        Transaksi
                                        <i class="fas fa-angle-left right"></i>
                                    </p>
                                </a>

                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="<?= site_url('transactions') ?>"
                                            class="nav-link <?= $seg1 === 'transactions' ? 'active' : '' ?>">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Transaksi Umum</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="<?= site_url('journals') ?>"
                                            class="nav-link <?= $seg1 === 'journals' ? 'active' : '' ?>">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Jurnal</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <!-- ================= KEUANGAN ================= -->
                            <li class="nav-header">KEUANGAN</li>

                            <li class="nav-item has-treeview <?= in_array($seg1, ['billing', 'payments', 'payment-categories']) ? 'menu-open' : '' ?>">
                                <a href="#"
                                    class="nav-link <?= in_array($seg1, ['billing', 'payments', 'payment-categories']) ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-wallet"></i>
                                    <p>
                                        Keuangan
                                        <i class="fas fa-angle-left right"></i>
                                    </p>
                                </a>

                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="<?= site_url('billing') ?>"
                                            class="nav-link <?= $seg1 === 'billing' ? 'active' : '' ?>">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Tagihan</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="<?= site_url('payments') ?>"
                                            class="nav-link <?= $seg1 === 'payments' ? 'active' : '' ?>">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Pembayaran</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="<?= site_url('payment-categories') ?>"
                                            class="nav-link <?= $seg1 === 'payment-categories' ? 'active' : '' ?>">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Kategori Pembayaran</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <!-- ================= LAPORAN ================= -->
                            <li class="nav-header">LAPORAN</li>

                            <li class="nav-item has-treeview <?= $seg1 === 'financial-statement' ? 'menu-open' : '' ?>">
                                <a href="#"
                                    class="nav-link <?= $seg1 === 'financial-statement' ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-chart-line"></i>
                                    <p>
                                        Laporan Keuangan
                                        <i class="fas fa-angle-left right"></i>
                                    </p>
                                </a>

                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="<?= site_url('financial-statement') ?>"
                                            class="nav-link <?= $seg1 === 'financial-statement' && !$seg2 ? 'active' : '' ?>">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Ringkasan</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="<?= site_url('financial-statement/neraca') ?>"
                                            class="nav-link <?= $seg2 === 'neraca' ? 'active' : '' ?>">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Neraca</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="<?= site_url('financial-statement/laba-rugi') ?>"
                                            class="nav-link <?= $seg2 === 'laba-rugi' ? 'active' : '' ?>">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Laba Rugi</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <!-- ================= ADMIN ================= -->
                            <?php if (session()->get('user_role') === 'admin'): ?>
                                <li class="nav-header">ADMINISTRASI</li>

                                <li class="nav-item has-treeview <?= in_array($seg1, ['user', 'setting']) ? 'menu-open' : '' ?>">
                                    <a href="#"
                                        class="nav-link <?= in_array($seg1, ['user', 'setting']) ? 'active' : '' ?>">
                                        <i class="nav-icon fas fa-cogs"></i>
                                        <p>
                                            Administrasi
                                            <i class="fas fa-angle-left right"></i>
                                        </p>
                                    </a>

                                    <ul class="nav nav-treeview">
                                        <li class="nav-item">
                                            <a href="<?= site_url('user') ?>"
                                                class="nav-link <?= $seg1 === 'user' ? 'active' : '' ?>">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Pengguna</p>
                                            </a>
                                        </li>

                                        <li class="nav-item">
                                            <a href="<?= site_url('setting') ?>"
                                                class="nav-link <?= $seg1 === 'setting' ? 'active' : '' ?>">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Pengaturan</p>
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            <?php endif; ?>

                        </ul>
                    </nav>
                <?php endif; ?>

                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1><?= $title; ?></h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="<?= site_url('home') ?>">Home</a></li>
                                <li class="breadcrumb-item active"><?= $title; ?></li>
                            </ol>
                        </div>
                    </div>
                </div><!-- /.container-fluid -->
            </section>

            <!-- Main content -->
            <section class="content">

                <!-- Default box -->
                <?= $this->renderSection('content') ?>
                <!-- /.card -->

            </section>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->

        <footer class="main-footer">
            <div class="float-right d-none d-sm-block">
                <b>Version</b> 1.0.5
            </div>
            <strong>Copyright &copy; <?= date('Y') ?> <a href="#">FMIS</a>.</strong> All rights reserved.
        </footer>

        <!-- Control Sidebar -->
        <aside class="control-sidebar control-sidebar-dark">
            <!-- Control sidebar content goes here -->
        </aside>
        <!-- /.control-sidebar -->
    </div>
    <!-- ./wrapper -->

    <!-- jQuery -->
    <script src="<?= base_url('templates') ?>/plugins/jquery/jquery.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="<?= base_url('templates') ?>/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App -->
    <script src="<?= base_url('templates') ?>/dist/js/adminlte.min.js"></script>

    <!-- ✅ jQuery + Summernote JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
</body>

</html>