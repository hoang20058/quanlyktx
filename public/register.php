<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

$pageTitle = 'Đăng ký nội trú - ' . APP_NAME;

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $token = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($token)) {
            throw new RuntimeException('CSRF token không hợp lệ.');
        }

        $studentId = StudentRepository::register([
            'full_name' => (string) ($_POST['full_name'] ?? ''),
            'student_code' => (string) ($_POST['student_code'] ?? ''),
            'dob' => (string) ($_POST['dob'] ?? ''),
            'phone' => (string) ($_POST['phone'] ?? ''),
            'email' => (string) ($_POST['email'] ?? ''),
            'department' => (string) ($_POST['department'] ?? ''),
            'priority_level' => (int) ($_POST['priority_level'] ?? 8),
        ]);
        $successMessage = 'Đăng ký đã được ghi nhận. Mã hồ sơ: #' . $studentId;
    } catch (Throwable $e) {
        $errorMessage = $e->getMessage();
    }
}

require_once __DIR__ . '/../views/partials/public_header.php';
?>

<section class="hero-section rounded-0 p-5 p-lg-8 text-center mb-5" style="margin-left: -12px; margin-right: -12px;">
    <h1 class="hero-title mb-3">Đăng ký nội trú</h1>
    <p class="hero-lead mb-0">Gửi hồ sơ của bạn để ban quản lý duyệt và phân phòng</p>
</section>

<?php if ($successMessage !== ''): ?>
    <section class="container my-4"><div class="alert alert-success border-0 rounded-4 mb-0"><i class="bi bi-check-circle me-2"></i><?= Security::e($successMessage); ?></div></section>
<?php endif; ?>
<?php if ($errorMessage !== ''): ?>
    <section class="container my-4"><div class="alert alert-danger border-0 rounded-4 mb-0"><i class="bi bi-exclamation-circle me-2"></i><?= Security::e($errorMessage); ?></div></section>
<?php endif; ?>

<section class="container my-5">
    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="feature-card p-4 p-lg-5">
                <h2 class="section-title mb-4">Biểu mẫu đăng ký</h2>
                <form method="post" class="row g-4">
                    <?= Security::csrfField(); ?>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Họ và tên *</label>
                        <input name="full_name" class="form-control form-control-lg" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Mã sinh viên</label>
                        <input name="student_code" class="form-control form-control-lg">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Ngày sinh</label>
                        <input name="dob" type="date" class="form-control form-control-lg">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Số điện thoại</label>
                        <input name="phone" class="form-control form-control-lg">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Email *</label>
                        <input name="email" type="email" class="form-control form-control-lg" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Ngành / Khoa *</label>
                        <input name="department" class="form-control form-control-lg" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Đối tượng ưu tiên</label>
                        <select name="priority_level" id="priority_level" class="form-select form-select-lg">
                            <?php for ($i = 1; $i <= 8; $i++): ?>
                                <option value="<?= $i; ?>"><?= $i; ?> - <?= Security::e(mb_substr(getPriorityDescription($i), 0, 60)); ?><?= mb_strlen(getPriorityDescription($i)) > 60 ? '...' : ''; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <div id="priority_desc" class="text-muted p-3 rounded-3 bg-light"><?= Security::e(getPriorityDescription(8)); ?></div>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary btn-lg rounded-pill px-5" type="submit"><i class="bi bi-send me-2"></i>Gửi đăng ký</button>
                        <a href="/" class="btn btn-outline-dark btn-lg rounded-pill px-5 ms-2">Quay lại</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<section class="container my-5 p-4 p-lg-5 rounded-4" style="background: linear-gradient(135deg, var(--app-primary), var(--app-accent)); color: #fff;">
    <div class="row align-items-center g-4">
        <div class="col-lg-6">
            <h3 class="fs-4 mb-3"><i class="bi bi-info-circle me-2"></i>Lưu ý khi đăng ký</h3>
            <ul class="mb-0">
                <li>Hồ sơ đăng ký sẽ được lưu với trạng thái <strong>Chờ duyệt</strong></li>
                <li>Ban quản lý sẽ xem xét trong vòng <strong>7 ngày làm việc</strong></li>
                <li>Bạn sẽ nhận thông báo cập nhật qua <strong>email hoặc SMS</strong></li>
                <li>Phí nội trú sẽ được thông báo sau khi duyệt hồ sơ</li>
            </ul>
        </div>
        <div class="col-lg-6">
            <h3 class="fs-4 mb-3"><i class="bi bi-telephone-fill me-2"></i>Cần hỗ trợ?</h3>
            <p>Nếu có thắc mắc, vui lòng liên hệ:</p>
            <p class="mb-1"><strong>Điện thoại:</strong> (0292) 3834 441</p>
            <p class="mb-0"><strong>Email:</strong> bqlktx@ctu.edu.vn</p>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../views/partials/public_footer.php'; ?>

<script>
    document.getElementById('priority_level')?.addEventListener('change', function() {
        const level = parseInt(this.value);
        const descriptions = {
            <?php for ($i = 1; $i <= 8; $i++): ?>
                <?= $i; ?>: `<?= Security::e(addslashes(getPriorityDescription($i))); ?>`,
            <?php endfor; ?>
        };
        document.getElementById('priority_desc').textContent = descriptions[level] || '';
    });
</script>
