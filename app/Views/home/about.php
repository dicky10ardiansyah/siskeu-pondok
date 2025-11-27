<?= $this->extend('layouts/landing/apps') ?>

<?= $this->section('content') ?>

<!-- About Section -->
<section class="py-5 bg-light" id="about">
    <div class="container px-5 my-5">
        <div class="row gx-5 align-items-center">
            <div class="col-lg-6">
                <h2 class="fw-bolder">Tentang Pondok Abdul Dhohir</h2>
                <p class="lead mb-4">
                    Pondok Abdul Dhohir didirikan dengan tujuan mencetak generasi muda yang berakhlak mulia, berpengetahuan luas, dan mampu berkontribusi bagi masyarakat. Kami menggabungkan pembelajaran agama yang mendalam dengan keterampilan modern agar santri siap menghadapi tantangan zaman.
                </p>
                <p>
                    Dengan fasilitas lengkap, lingkungan yang mendukung, dan tenaga pengajar profesional, kami memastikan setiap santri mendapatkan pendidikan yang holistik — spiritual, intelektual, dan sosial.
                </p>
            </div>
            <div class="col-lg-6">
                <img class="img-fluid rounded" src="<?= base_url('landings') ?>/assets/fasilitas-1.png" alt="Pondok Abdul Dhohir">
            </div>
        </div>
    </div>
</section>

<!-- Vision & Mission Section -->
<section class="py-5" id="vision-mission">
    <div class="container px-5 my-5">
        <div class="text-center mb-5">
            <h2 class="fw-bolder">Visi & Misi</h2>
            <p class="text-muted">Landasan yang membimbing setiap langkah kami</p>
        </div>
        <div class="row gx-5">
            <div class="col-lg-6 mb-4">
                <div class="card h-100 border-0 shadow">
                    <div class="card-body">
                        <h3 class="h5 fw-bolder">Visi</h3>
                        <p>Menghasilkan Generasi yang ber-Akhlaqul Karimah, Faqih, Intelektual, dan Mandiri.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="card h-100 border-0 shadow">
                    <div class="card-body">
                        <h3 class="h5 fw-bolder">Misi</h3>
                        <ul>
                            <li>Menciptakan lingkungan belajar yang kreatif, adil, dan menyenangkan.</li>
                            <li>Menghasilkan lulusan yang berkarakter islami dan berkualitas tinggi.</li>
                            <li>Menjawab tantangan dalam pengembangan sumber daya manusia.</li>
                            <li>Mengembangkan budaya yang bersih dan sehat.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>