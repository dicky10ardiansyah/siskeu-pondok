<?= $this->extend('layouts/template/apps') ?>

<?php $this->setVar('title', 'Tabel Transaksi'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header d-flex align-items-center w-100">
                <h5 class="mb-0">Tabel Transaksi</h5>
                <a href="<?= base_url('transactions/create') ?>" class="btn btn-primary ml-auto">
                    <i class="fas fa-plus"></i> Tambah Transaksi
                </a>
            </div>

            <div class="card-body">

                <!-- Search Form -->
                <form action="<?= base_url('transactions') ?>" method="get" class="form-inline mb-3">
                    <input type="text" name="search" class="form-control mr-2" placeholder="Cari deskripsi/type..." value="<?= esc($keyword ?? '') ?>">
                    <button type="submit" class="btn btn-outline-primary">Cari</button>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Tanggal</th>
                                <th>Deskripsi</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Debit Account</th>
                                <th>Credit Account</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($transactions)) : ?>
                                <?php
                                // Mapping akun untuk menampilkan kode + nama
                                $accountsMap = [];
                                foreach ($accounts as $acc) {
                                    $accountsMap[$acc['id']] = $acc['code'] . ' - ' . $acc['name'];
                                }

                                $no = 1 + (10 * ($pager->getCurrentPage('transactions') - 1));
                                foreach ($transactions as $trx) :
                                ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= date('d-m-Y', strtotime($trx['date'])) ?></td>
                                        <td><?= esc($trx['description']) ?></td>
                                        <td>
                                            <span class="badge <?= $trx['type'] === 'income' ? 'badge-success' : 'badge-danger' ?>">
                                                <?= esc(ucfirst($trx['type'])) ?>
                                            </span>
                                        </td>
                                        <td><?= number_format($trx['amount'], 2) ?></td>
                                        <td><?= esc($accountsMap[$trx['debit_account_id']] ?? '-') ?></td>
                                        <td><?= esc($accountsMap[$trx['credit_account_id']] ?? '-') ?></td>
                                        <td>
                                            <div class="d-flex justify-content-center">
                                                <a href="<?= base_url('transactions/edit/' . $trx['id']) ?>" class="btn btn-sm btn-warning mr-2">Edit</a>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $trx['id'] ?>)">
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="8" class="text-center">Tidak ada data transaksi.</td>
                                </tr>
                            <?php endif ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-3">
                    <?= custom_pagination_with_query(
                        $pager,
                        ['search' => $keyword ?? ''],
                        'transactions',
                        'bootstrap_full'
                    ) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 for delete confirmation -->
<script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'Yakin ingin menghapus?',
            text: "Data transaksi tidak dapat dikembalikan!",
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
                form.action = '/transactions/delete/' + id;

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

<!-- SweetAlert2 for flash success -->
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