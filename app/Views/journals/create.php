<?= $this->extend('layouts/template/apps') ?>

<?php $this->setVar('title', 'Tambah Jurnal'); ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Tambah Jurnal</h5>
            </div>
            <div class="card-body">
                <form action="<?= base_url('journals/store') ?>" method="post">
                    <?= csrf_field() ?>

                    <!-- Tanggal -->
                    <div class="form-group mb-3">
                        <label for="date">Tanggal</label>
                        <input type="date"
                            class="form-control <?= session('errors.date') ? 'is-invalid' : '' ?>"
                            name="date"
                            id="date"
                            value="<?= old('date') ?>">
                    </div>

                    <!-- Deskripsi -->
                    <div class="form-group mb-3">
                        <label for="description">Deskripsi</label>
                        <textarea name="description" id="description"
                            class="form-control <?= session('errors.description') ? 'is-invalid' : '' ?>"
                            rows="3"><?= old('description') ?></textarea>
                    </div>

                    <!-- Tabel Entries -->
                    <div class="form-group">
                        <label>Daftar Akun</label>
                        <table class="table table-bordered" id="entries-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Akun</th>
                                    <th>Tipe</th>
                                    <th>Nominal</th>
                                    <th width="5%">
                                        <button type="button" class="btn btn-sm btn-success" onclick="addRow()">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <select name="account_id[]" class="form-control">
                                            <option value="">-- Pilih Akun --</option>
                                            <?php foreach ($accounts as $account): ?>
                                                <option value="<?= $account['id'] ?>">
                                                    <?= esc($account['name']) ?> (<?= esc($account['code']) ?>)
                                                </option>
                                            <?php endforeach ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="type[]" class="form-control">
                                            <option value="debit">Debit</option>
                                            <option value="credit">Kredit</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control amount-input">
                                        <input type="hidden" name="amount[]" class="amount-raw">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <?php if (session()->get('user_role') === 'admin'): ?>
                        <div class="form-group mb-3">
                            <label for="user_id">User</label>
                            <select name="user_id" id="user_id" class="form-control">
                                <option value="">-- Pilih User --</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>"
                                        <?= old('user_id') == $user['id'] ? 'selected' : '' ?>>
                                        <?= esc($user['name']) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <!-- Tombol -->
                    <div class="mt-4 d-flex justify-content-between">
                        <a href="<?= base_url('journals') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Batal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPT TAMBAH/HAPUS BARIS -->
<script>
    function addRow() {
        const tbody = document.querySelector('#entries-table tbody');
        const row = document.createElement('tr');
        row.innerHTML = `
        <td>
            <select name="account_id[]" class="form-control">
                <option value="">-- Pilih Akun --</option>
                <?php foreach ($accounts as $account): ?>
                    <option value="<?= $account['id'] ?>">
                        <?= esc($account['name']) ?> (<?= esc($account['code']) ?>)
                    </option>
                <?php endforeach ?>
            </select>
        </td>
        <td>
            <select name="type[]" class="form-control">
                <option value="debit">Debit</option>
                <option value="credit">Kredit</option>
            </select>
        </td>
        <td>
            <input type="text" class="form-control amount-input">
            <input type="hidden" name="amount[]" class="amount-raw">
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)">
                <i class="fas fa-trash"></i>
            </button>
        </td>`;
        tbody.appendChild(row);
        attachCurrencyFormatting(row.querySelector('.amount-input')); // aktifkan format
    }

    function removeRow(btn) {
        btn.closest('tr').remove();
    }

    // ======== Format Currency ========
    function formatNumber(value) {
        const parts = value.replace(/[^\d.]/g, '').split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return parts.join('.');
    }

    function attachCurrencyFormatting(input) {
        const hidden = input.closest('td').querySelector('.amount-raw');

        input.addEventListener('input', function() {
            const raw = this.value.replace(/,/g, '');
            hidden.value = raw;
            this.value = formatNumber(raw);
        });
    }

    // Aktifkan format untuk baris awal
    document.querySelectorAll('.amount-input').forEach(attachCurrencyFormatting);
</script>

<!-- SWEETALERT2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if (session('errors')): ?>
    <script>
        Swal.fire({
            icon: 'warning',
            title: 'Validasi Gagal ⚠️',
            html: `
            <div style="text-align: left; font-size: 15px;">
                <p>Beberapa data tidak valid atau belum seimbang:</p>
                <ul style="padding-left: 20px; line-height: 1.5; color: #d33;">
                    <?php foreach (session('errors') as $error): ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach ?>
                </ul>
                <hr>
                <p style="font-size: 14px; color: #555;">Pastikan total debit dan kredit memiliki jumlah yang sama sebelum menyimpan jurnal.</p>
            </div>
        `,
            showConfirmButton: true,
            confirmButtonText: 'Perbaiki Sekarang',
            confirmButtonColor: '#3085d6',
            background: '#fff',
            width: 550,
            customClass: {
                popup: 'shadow-lg rounded-3'
            }
        });
    </script>
<?php endif; ?>

<?php if (session('success')): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: '<?= esc(session('success')) ?>',
            timer: 2000,
            showConfirmButton: false
        });
    </script>
<?php endif; ?>

<?= $this->endSection() ?>