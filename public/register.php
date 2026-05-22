<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

function defaultRegistrationForm(): array
{
    return [
        'full_name' => '',
        'student_code' => '',
        'dob' => '',
        'phone' => '',
        'email' => '',
        'department' => '',
        'priority_level' => '8',
    ];
}

function cleanRegistrationInput(array $input): array
{
    $data = defaultRegistrationForm();

    foreach ($data as $key => $_) {
        $data[$key] = trim((string) ($input[$key] ?? ''));
    }

    $data['priority_level'] = (string) max(1, min(8, (int) ($data['priority_level'] ?: 8)));

    return $data;
}

function nullableText(string $value): ?string
{
    return $value === '' ? null : $value;
}

function handleCreateRegistration(PDO $pdo, array $input): void
{
    if ($input['full_name'] === '' || $input['email'] === '' || $input['department'] === '') {
        throw new InvalidArgumentException('Vui lòng nhập đầy đủ họ tên, email và ngành/khoa.');
    }

    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Email không hợp lệ.');
    }

    if ($input['student_code'] !== '') {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM Student WHERE student_code = :student_code');
        $stmt->execute([':student_code' => $input['student_code']]);

        if ((int) $stmt->fetchColumn() > 0) {
            throw new InvalidArgumentException('Mã sinh viên đã tồn tại.');
        }
    }

    $stmt = $pdo->prepare('
        INSERT INTO Student
            (full_name, student_code, dob, phone, email, department, status, priority_level, boarding_score)
        VALUES
            (:full_name, :student_code, :dob, :phone, :email, :department, :status, :priority_level, :boarding_score)
    ');

    $stmt->execute([
        ':full_name' => $input['full_name'],
        ':student_code' => nullableText($input['student_code']),
        ':dob' => nullableText($input['dob']),
        ':phone' => nullableText($input['phone']),
        ':email' => nullableText($input['email']),
        ':department' => nullableText($input['department']),
        ':status' => 'Chờ duyệt',
        ':priority_level' => (int) $input['priority_level'],
        ':boarding_score' => 100,
    ]);
}

$pdo = Database::connection();
$pageTitle = 'Đăng ký nội trú - ' . APP_NAME;
$formData = defaultRegistrationForm();
$errorMessage = '';
$priorityDescriptions = array_combine(range(1, 8), array_map('getPriorityDescription', range(1, 8)));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'create');
    $formData = cleanRegistrationInput($_POST);

    try {
        if ($action === 'create') {
            handleCreateRegistration($pdo, $formData);
            setFlashMessage('success', 'Hồ sơ đăng ký đã được gửi. Ban quản lý sẽ xem xét và phản hồi sau khi duyệt.');
            redirectTo(APP_URL . '/register.php');
        }

        throw new InvalidArgumentException('Thao tác không hợp lệ.');
    } catch (Throwable $e) {
        $errorMessage = $e->getMessage();
    }
}

require_once __DIR__ . '/../views/partials/public_header.php';
?>
<section class="hero-section rounded-0 p-5 p-lg-8 text-center mb-5" style="margin-left: -12px; margin-right: -12px;">
    <h1 class="hero-title mb-3">Đăng ký nội trú</h1>
    <p class="hero-lead mx-auto mb-0">Gửi hồ sơ để ban quản lý xét duyệt và phân phòng khi còn chỗ phù hợp.</p>
</section>

<?php if ($errorMessage !== ''): ?>
    <section class="container my-4">
        <div class="alert alert-danger border-0 rounded-4 mb-0">
            <i class="bi bi-exclamation-circle me-2"></i><?= h($errorMessage); ?>
        </div>
    </section>
<?php endif; ?>

<section class="container my-5">
    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="feature-card p-4 p-lg-5">
                <div class="mb-4">
                    <div class="section-subtitle text-uppercase fw-semibold small">Hồ sơ sinh viên</div>
                    <h2 class="section-title mb-0">Thông tin đăng ký</h2>
                </div>
                <form method="post" class="row g-4" novalidate>
                    <input type="hidden" name="action" value="create">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Họ và tên *</label>
                        <input name="full_name" class="form-control form-control-lg" value="<?= h($formData['full_name']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Mã sinh viên</label>
                        <input name="student_code" class="form-control form-control-lg" value="<?= h($formData['student_code']); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Ngày sinh</label>
                        <input name="dob" type="date" class="form-control form-control-lg" value="<?= h($formData['dob']); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Số điện thoại</label>
                        <input name="phone" class="form-control form-control-lg" value="<?= h($formData['phone']); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Email *</label>
                        <input name="email" type="email" class="form-control form-control-lg" value="<?= h($formData['email']); ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Ngành / Khoa *</label>
                        <input name="department" class="form-control form-control-lg" value="<?= h($formData['department']); ?>" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Đối tượng ưu tiên</label>
                        <select name="priority_level" id="priority_level" class="form-select form-select-lg">
                            <?php for ($i = 1; $i <= 8; $i++): ?>
                                <option value="<?= $i; ?>" <?= (int) $formData['priority_level'] === $i ? 'selected' : ''; ?>>
                                    <?= $i; ?> - <?= h(mb_substr(getPriorityDescription($i), 0, 70)); ?><?= mb_strlen(getPriorityDescription($i)) > 70 ? '...' : ''; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <div id="priority_desc" class="text-muted p-3 rounded-3 bg-light">
                            <?= h(getPriorityDescription((int) $formData['priority_level'])); ?>
                        </div>
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-2">
                        <button class="btn btn-primary btn-lg rounded-pill px-5" type="submit">
                            <i class="bi bi-send me-2"></i>Gửi đăng ký
                        </button>
                        <a href="<?= h(APP_URL); ?>/" class="btn btn-outline-dark btn-lg rounded-pill px-5">Quay lại</a>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="feature-card p-4 h-100">
                <h3 class="fs-5 fw-bold mb-3">Quy trình xét duyệt</h3>
                <div class="timeline-step mb-4">
                    <div class="fw-semibold">1. Gửi hồ sơ</div>
                    <div class="text-muted small">Thông tin được lưu với trạng thái Chờ duyệt.</div>
                </div>
                <div class="timeline-step mb-4">
                    <div class="fw-semibold">2. Ban quản lý kiểm tra</div>
                    <div class="text-muted small">Hồ sơ được xét theo mức ưu tiên và tình trạng phòng.</div>
                </div>
                <div class="timeline-step">
                    <div class="fw-semibold">3. Phân phòng</div>
                    <div class="text-muted small">Khi được duyệt, sinh viên sẽ được gán phòng và tạo hợp đồng.</div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="container my-5 p-4 p-lg-5 rounded-4" style="background: #0f172a; color: #fff;">
    <div class="row align-items-center g-4">
        <div class="col-lg-6">
            <h3 class="fs-4 mb-3"><i class="bi bi-info-circle me-2"></i>Lưu ý khi đăng ký</h3>
            <ul class="mb-0">
                <li>Hồ sơ đăng ký sẽ được lưu với trạng thái <strong>Chờ duyệt</strong>.</li>
                <li>Ban quản lý xét duyệt theo mức ưu tiên và số chỗ còn trống.</li>
                <li>Sinh viên cần nhập email chính xác để nhận thông tin phản hồi.</li>
            </ul>
        </div>
        <div class="col-lg-6">
            <h3 class="fs-4 mb-3"><i class="bi bi-telephone-fill me-2"></i>Cần hỗ trợ?</h3>
            <p>Nếu có thắc mắc, vui lòng liên hệ ban quản lý ký túc xá.</p>
            <p class="mb-1"><strong>Điện thoại:</strong> 0123456590</p>
            <p class="mb-0"><strong>Email:</strong> bqlktx@ctu.edu.vn</p>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../views/partials/public_footer.php'; ?>

<script>
    document.getElementById('priority_level')?.addEventListener('change', function () {
        const descriptions = <?= json_encode($priorityDescriptions, JSON_UNESCAPED_UNICODE); ?>;
        document.getElementById('priority_desc').textContent = descriptions[this.value] || '';
    });
</script>
