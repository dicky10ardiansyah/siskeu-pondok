<?= $this->extend('layouts/template/apps') ?>
<?php $this->setVar('title', 'Edit Tarif Siswa'); ?>
<?= $this->section('content') ?>

<div class="row">
    <div class="col-md-10 offset-md-1">
        <div class="card">
            <div class="card-header">
                <h5>Edit Tarif Siswa: <?= esc($student['name']) ?> (<?= esc($student['nis']) ?>)</h5>
            </div>

            <div class="card-body">
                <form action="<?= base_url('students/' . $student['id'] . '/payment-rules') ?>" method="post">
                    <?= csrf_field() ?>

                    <?php
                    $rulesByCategory = [];
                    foreach ($rules as $r) {
                        $rulesByCategory[$r['category_id']] = $r;
                    }

                    $classRulesArr = [];
                    foreach ($classRules as $cr) {
                        $classRulesArr[$cr['category_id']] = $cr['amount'];
                    }
                    ?>

                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th width="5%">No</th>
                                <th>Kategori</th>
                                <th width="30%">Tarif</th>
                                <th width="15%" class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php foreach ($categories as $category): ?>
                                <?php
                                $rule = $rulesByCategory[$category['id']] ?? null;
                                $amount = $rule['amount']
                                    ?? ($classRulesArr[$category['id']] ?? $category['default_amount'] ?? 0);
                                $isActive = $rule && (int)$rule['is_mandatory'] === 1;
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= esc($category['name']) ?></td>
                                    <td>
                                        <input type="text"
                                            name="amount[<?= $rule['id'] ?? 'new_' . $category['id'] ?>]"
                                            class="form-control currency"
                                            value="<?= number_format($amount, 0, ',', '.') ?>">
                                    </td>
                                    <td class="text-center">
                                        <?php if ($rule): ?>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox"
                                                    class="custom-control-input toggle-rule"
                                                    id="switch<?= $rule['id'] ?>"
                                                    data-student="<?= $student['id'] ?>"
                                                    data-rule="<?= $rule['id'] ?>"
                                                    <?= $isActive ? 'checked' : '' ?>>
                                                <label class="custom-control-label"
                                                    for="switch<?= $rule['id'] ?>"></label>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Belum aktif</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update
                    </button>
                    <a href="<?= base_url('students') ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Batal
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        // Toggle enable / disable
        document.querySelectorAll('.toggle-rule').forEach(toggle => {
            toggle.addEventListener('change', function() {

                const ruleId = this.dataset.rule;
                const studentId = this.dataset.student;
                const checked = this.checked;

                const url = checked ?
                    `<?= base_url('students') ?>/${studentId}/payment-rules/enable/${ruleId}` :
                    `<?= base_url('students') ?>/${studentId}/payment-rules/disable/${ruleId}`;

                Swal.fire({
                    title: checked ? 'Aktifkan tarif?' : 'Nonaktifkan tarif?',
                    text: checked ?
                        'Siswa akan dikenakan tagihan ini' :
                        'Siswa tidak akan dikenakan tagihan ini',
                    icon: checked ? 'info' : 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    } else {
                        toggle.checked = !checked;
                    }
                });
            });
        });

        // Format Rupiah
        function formatRupiah(val) {
            return val.replace(/\D/g, '')
                .replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        document.querySelectorAll('.currency').forEach(el => {
            el.addEventListener('keyup', function() {
                this.value = formatRupiah(this.value);
            });
        });

        // Submit → hapus titik
        document.querySelector('form').addEventListener('submit', function() {
            this.querySelectorAll('.currency').forEach(el => {
                el.value = el.value.replace(/\./g, '');
            });
        });

    });
</script>

<?= $this->endSection() ?>