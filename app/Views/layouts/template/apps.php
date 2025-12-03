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

    <!-- ✅ Summernote CSS -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">

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
                <?php if (session()->get('isLoggedIn')): ?>
                    <nav class="mt-2">
                        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                            <li class="nav-item">
                                <a href="<?= site_url('home') ?>" class="nav-link <?= service('uri')->getSegment(1) === 'home' ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-home"></i>
                                    <p>Home</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="<?= site_url('accounts') ?>" class="nav-link <?= service('uri')->getSegment(1) === 'accounts' ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-user"></i>
                                    <p>Akun</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="<?= site_url('transactions') ?>" class="nav-link <?= service('uri')->getSegment(1) === 'transactions' ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-exchange-alt"></i>
                                    <p>Transaksi</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="<?= site_url('journals') ?>" class="nav-link <?= service('uri')->getSegment(1) === 'journals' ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-book"></i>
                                    <p>Jurnal</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="<?= site_url('students') ?>" class="nav-link <?= service('uri')->getSegment(1) === 'students' ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-book"></i>
                                    <p>Siswa</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="<?= site_url('billing') ?>" class="nav-link <?= service('uri')->getSegment(1) === 'billing' ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-receipt"></i>
                                    <p>Tagihan</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="<?= site_url('payments') ?>" class="nav-link <?= service('uri')->getSegment(1) === 'payments' ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-hand-holding-usd"></i>
                                    <p>Pembayaran</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="<?= site_url('ledger') ?>" class="nav-link <?= service('uri')->getSegment(1) === 'ledger' ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-book-open"></i>
                                    <p>Ledger Bank/Buku Besar</p>
                                </a>
                            </li>

                            <!-- Laporan Keuangan -->
                            <li class="nav-item">
                                <a href="<?= site_url('financial-statement') ?>" class="nav-link <?= service('uri')->getSegment(1) === 'financial-statement' && !service('uri')->getSegment(2) ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-chart-line"></i>
                                    <p>Laporan Keuangan</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="<?= site_url('financial-statement/neraca') ?>" class="nav-link <?= service('uri')->getSegment(1) === 'financial-statement' && service('uri')->getSegment(2) === 'neraca' ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-balance-scale"></i>
                                    <p>Neraca</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="<?= site_url('financial-statement/laba-rugi') ?>" class="nav-link <?= service('uri')->getSegment(1) === 'financial-statement' && service('uri')->getSegment(2) === 'laba-rugi' ? 'active' : '' ?>">
                                    <i class="nav-icon fas fa-file-invoice-dollar"></i>
                                    <p>Laba Rugi</p>
                                </a>
                            </li>

                            <!-- Menu Admin -->
                            <?php if (session()->get('user_role') === 'admin'): ?>
                                <li class="nav-item">
                                    <a href="<?= site_url('user') ?>" class="nav-link <?= service('uri')->getSegment(1) === 'user' ? 'active' : '' ?>">
                                        <i class="nav-icon fas fa-users-cog"></i>
                                        <p>Pengguna</p>
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a href="<?= site_url('setting') ?>" class="nav-link <?= service('uri')->getSegment(1) === 'setting' ? 'active' : '' ?>">
                                        <i class="nav-icon fas fa-cogs"></i>
                                        <p>Pengaturan</p>
                                    </a>
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
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
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
                <b>Version</b> 3.2.0
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