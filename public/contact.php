<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

$pageTitle = 'Liên hệ - ' . APP_NAME;

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim((string) ($_POST['contact_name'] ?? ''));
        $email = trim((string) ($_POST['contact_email'] ?? ''));
        $subject = trim((string) ($_POST['contact_subject'] ?? ''));
        $message = trim((string) ($_POST['contact_message'] ?? ''));
        
        if (empty($name) || empty($email) || empty($message)) {
            throw new RuntimeException('Vui lòng điền đủ thông tin bắt buộc.');
        }

        $line = sprintf("[%s] %s | %s | %s | %s\n", date('Y-m-d H:i:s'), $name, $email, $subject, $message);
        $logFile = __DIR__ . '/../storage/contact_messages.log';
        @mkdir(dirname($logFile), 0755, true);
        file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
        $successMessage = 'Cảm ơn! Tin nhắn của bạn đã được ghi nhận. Chúng tôi sẽ phản hồi sớm nhất.';
    } catch (Throwable $e) {
        $errorMessage = $e->getMessage();
    }
}

require_once __DIR__ . '/../views/partials/public_header.php';
?>

<section class="hero-section rounded-0 p-5 p-lg-8 text-center mb-5" style="margin-left: -12px; margin-right: -12px;">
    <h1 class="hero-title mb-3">Liên hệ với chúng tôi</h1>
    <p class="hero-lead mb-0">Chúng tôi luôn sẵn sàng lắng nghe ý kiến và thắc mắc của bạn</p>
</section>

<?php if ($successMessage !== ''): ?>
    <section class="container my-4"><div class="alert alert-success border-0 rounded-4 mb-0"><i class="bi bi-check-circle me-2"></i><?= Security::e($successMessage); ?></div></section>
<?php endif; ?>
<?php if ($errorMessage !== ''): ?>
    <section class="container my-4"><div class="alert alert-danger border-0 rounded-4 mb-0"><i class="bi bi-exclamation-circle me-2"></i><?= Security::e($errorMessage); ?></div></section>
<?php endif; ?>

<section class="container my-5">
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="feature-card p-4 p-lg-5 h-100">
                <h2 class="section-title mb-4">Gửi tin nhắn</h2>
                <form method="post" class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Họ và tên *</label>
                        <input name="contact_name" class="form-control form-control-lg" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Email *</label>
                        <input name="contact_email" type="email" class="form-control form-control-lg" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Chủ đề</label>
                        <input name="contact_subject" class="form-control form-control-lg" placeholder="Vd: Tư vấn về phòng ở">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Nội dung *</label>
                        <textarea name="contact_message" class="form-control form-control-lg" rows="6" required></textarea>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary btn-lg rounded-pill px-5" type="submit"><i class="bi bi-send me-2"></i>Gửi tin nhắn</button>
                        <a href="/" class="btn btn-outline-dark btn-lg rounded-pill px-5 ms-2">Quay lại</a>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="feature-card p-4 p-lg-5 h-100">
                <h2 class="section-title mb-4">Thông tin liên hệ</h2>
                <div class="d-grid gap-4">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="icon-badge primary flex-shrink-0" style="width: 60px; height: 60px;"><i class="bi bi-geo-alt-fill"></i></div>
                        <div>
                            <h5 class="fw-semibold mb-1">Địa chỉ</h5>
                            <p class="text-muted mb-0">Khu II, Đ. 3/2, Xuân Khánh, Ninh Kiều, Cần Thơ</p>
                        </div>
                    </div>
                    <div class="d-flex gap-3 align-items-start">
                        <div class="icon-badge blue flex-shrink-0" style="width: 60px; height: 60px;"><i class="bi bi-telephone-fill"></i></div>
                        <div>
                            <h5 class="fw-semibold mb-1">Điện thoại</h5>
                            <p class="text-muted mb-0"><a href="tel:+842923834441" class="text-decoration-none">(0292) 3834 441</a></p>
                        </div>
                    </div>
                    <div class="d-flex gap-3 align-items-start">
                        <div class="icon-badge amber flex-shrink-0" style="width: 60px; height: 60px;"><i class="bi bi-envelope-fill"></i></div>
                        <div>
                            <h5 class="fw-semibold mb-1">Email</h5>
                            <p class="text-muted mb-0"><a href="mailto:bqlktx@ctu.edu.vn" class="text-decoration-none">bqlktx@ctu.edu.vn</a></p>
                        </div>
                    </div>
                    <div class="d-flex gap-3 align-items-start">
                        <div class="icon-badge rose flex-shrink-0" style="width: 60px; height: 60px;"><i class="bi bi-clock-fill"></i></div>
                        <div>
                            <h5 class="fw-semibold mb-1">Giờ làm việc</h5>
                            <p class="text-muted mb-0">Thứ Hai - Thứ Sáu: 8:00 - 17:00<br>Thứ Bảy: 8:00 - 12:00</p>
                        </div>
                    </div>
                </div>
                <div class="mt-4 p-4 rounded-4 border" style="border-color: var(--app-border) !important;">
                    <div class="d-flex gap-3 align-items-center mb-3">
                        <i class="bi bi-chat-dots fs-5" style="color: var(--app-primary);"></i>
                        <h5 class="fw-semibold mb-0">Hỗ trợ qua mạng xã hội</h5>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="#" class="btn btn-outline-primary rounded-pill"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="btn btn-outline-primary rounded-pill"><i class="bi bi-youtube"></i></a>
                        <a href="#" class="btn btn-outline-primary rounded-pill"><i class="bi bi-tiktok"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="container my-5">
    <div class="rounded-4 overflow-hidden" style="height: 400px;">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3929.2738551652567!2d106.26629!3d10.001447!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31395342e22c77ef%3A0x7c3c3c3c3c3c3c3c!2sKhu%20II%2C%203%2F2%2C%20Xuan%20Khanh%2C%20Ninh%20Kieu%2C%20Can%20Tho!5e0!3m2!1svi!2svn!4v1715000000000" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>
</section>

<?php require_once __DIR__ . '/../views/partials/public_footer.php'; ?>
