<?php

declare(strict_types=1);
?>
</main>
<footer class="bg-white py-5 mt-5 border-top">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <a class="navbar-brand d-flex align-items-center gap-3 fw-bold mb-3" href="<?= Security::e(APP_URL); ?>/">
                    <span class="brand-mark">KTX</span>
                    <span class="fs-5"><?= Security::e(APP_NAME); ?></span>
                </a>
                <p class="text-muted">Nền tảng quản lý ký túc xá tập trung, giúp sinh viên và ban quản lý tương tác hiệu quả.</p>
                <div class="d-flex gap-3 mt-4">
                    <a href="#" class="btn btn-light"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="btn btn-light"><i class="bi bi-youtube"></i></a>
                    <a href="#" class="btn btn-light"><i class="bi bi-tiktok"></i></a>
                </div>
            </div>
            <div class="col-lg-2 offset-lg-1">
                <h5 class="mb-3">Liên kết</h5>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2"><a href="/" class="nav-link p-0 text-muted">Trang chủ</a></li>
                    <li class="nav-item mb-2"><a href="/#rooms" class="nav-link p-0 text-muted">Tra cứu phòng</a></li>
                    <li class="nav-item mb-2"><a href="/#notices" class="nav-link p-0 text-muted">Thông báo</a></li>
                    <li class="nav-item mb-2"><a href="/register.php" class="nav-link p-0 text-muted">Đăng ký</a></li>
                    <li class="nav-item mb-2"><a href="/contact.php" class="nav-link p-0 text-muted">Liên hệ</a></li>
                </ul>
            </div>
            <div class="col-lg-4 offset-lg-1">
                <h5 class="mb-3">Liên hệ</h5>
                <p class="text-muted mb-2">
                    <i class="bi bi-geo-alt-fill me-2"></i>Khu II, Đ. 3/2, Xuân Khánh, Ninh Kiều, Cần Thơ
                </p>
                <p class="text-muted mb-2">
                    <i class="bi bi-telephone-fill me-2"></i>(0292) 3834 441
                </p>
                <p class="text-muted mb-3">
                    <i class="bi bi-envelope-fill me-2"></i>bqlktx@ctu.edu.vn
                </p>
                <div class="rounded-3 overflow-hidden" style="height: 200px;">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3929.2738551652567!2d106.26629!3d10.001447!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31395342e22c77ef%3A0x7c3c3c3c3c3c3c3c!2sKhu%20II%2C%203%2F2%2C%20Xuan%20Khanh%2C%20Ninh%20Kieu%2C%20Can%20Tho!5e0!3m2!1svi!2svn!4v1715000000000" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-between py-4 my-4 border-top">
            <p class="text-muted">&copy; <?= date('Y'); ?> <?= APP_NAME ?>. All rights reserved.</p>
            <a class="btn btn-outline-dark rounded-pill px-4" href="#top">Về đầu trang</a>
        </div>
    </div>
</footer>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="<?= Security::e(APP_BASE_URL); ?>/assets/js/app.js?v=1.1"></script>
</body>
</html>
