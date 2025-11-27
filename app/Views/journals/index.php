<?= $this->extend('layouts/template/apps') ?>

<?php $this->setVar('title', 'Jurnal'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header d-flex align-items-center w-100">
                <h5 class="mb-0">Tabel Jurnal</h5>
                <a href="<?= base_url('journals/create') ?>" class="btn btn-primary ml-auto">
                    <i class="fas fa-plus"></i> Tambah Jurnal
                </a>
            </div>

            <div class="card-body">

                <!-- Form Search -->
                <form action="<?= base_url('journals') ?>" method="get" class="form-inline mb-3">
                    <input type="text" name="keyword" class="form-control mr-2" placeholder="Cari deskripsi/tanggal..." value="<?= esc($keyword ?? '') ?>">
                    <button type="submit" class="btn btn-outline-primary">Cari</button>
                </form>

                <!-- Tabel Jurnal -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Tanggal</th>
                                <th>Deskripsi</th>
                                <th>User</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($journals)) : ?>
                                <?php
                                $no = 1 + (10 * (($pager->getCurrentPage('journals') ?? 1) - 1));
                                foreach ($journals as $journal) :
                                ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= esc($journal['date']) ?></td>
                                        <td><?= esc($journal['description']) ?></td>
                                        <td><?= esc($journal['user_id']) ?></td> <!-- bisa diganti nama user jika join -->
                                        <td>
                                            <div class="d-flex justify-content-center">
                                                <a href="<?= base_url('journals/' . $journal['id']) ?>" class="btn btn-sm btn-info mr-2">
                                                    <i class="fas fa-eye"></i> Detail
                                                </a>
                                                <a href="<?= base_url('journals/edit/' . $journal['id']) ?>" class="btn btn-sm btn-warning mr-2">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $journal['id'] ?>)">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="5" class="text-center">Tidak ada data jurnal.</td>
                                </tr>
                            <?php endif ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-3">
                    <?= custom_pagination_with_query(
                        $pager,
                        ['keyword' => $keyword ?? ''],
                        'journals',          // group sesuai paginate()
                        'bootstrap_full'     // template
                    ) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 untuk konfirmasi hapus -->
<script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'Yakin ingin menghapus?',
            text: "Data jurnal tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'post';
                form.action = '/journals/delete/' + id;

                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '<?= csrf_token() ?>';
                csrf.value = '<?= csrf_hash() ?>';

                form.appendChild(csrf);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>

<!-- SweetAlert2 untuk pesan sukses -->
<?php if (session()->getFlashdata('success')) : ?>
    <script>
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: '<?= session()->getFlashdata('success') ?>',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
    </script>
<?php endif ?>

<?= $this->endSection() ?>