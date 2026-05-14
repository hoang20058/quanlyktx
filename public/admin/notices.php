<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

$pageTitle = 'Quản lý thông báo - ' . APP_NAME;
$activeMenu = 'notices';

$notices = NoticeRepository::all();
$rooms = RoomRepository::selectOptions();
$students = StudentRepository::all();
$noticeCategories = array_values(array_unique(array_filter(array_map(static fn (array $notice): string => (string) ($notice['category'] ?? ''), $notices))));
sort($noticeCategories);
$noticeTargets = array_values(array_unique(array_filter(array_map(static fn (array $notice): string => (string) ($notice['target_type'] ?? ''), $notices))));
sort($noticeTargets);

require_once __DIR__ . '/../../views/partials/admin_header.php';
?>
<div class="table-panel p-4">
    <div class="datatable-toolbar mb-3">
        <div>
            <div class="section-subtitle text-uppercase fw-semibold small">CRUD chuẩn hóa</div>
            <h2 class="section-title mb-0">Bảng dữ liệu thông báo</h2>
        </div>
        <div class="table-actions d-flex gap-2">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#noticeModal" data-notice-id="0">Thêm thông báo</button>
        </div>
    </div>

    <div class="admin-filter-bar" data-filter-target="noticesTable">
        <div class="admin-filter-field">
            <label for="noticeFilterCategory">Loại</label>
            <select id="noticeFilterCategory" class="form-select form-select-sm" data-filter-key="category">
                <option value="">Tất cả loại</option>
                <?php foreach ($noticeCategories as $category): ?>
                    <option value="<?= Security::e($category); ?>"><?= Security::e($category); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-filter-field">
            <label for="noticeFilterTarget">Đối tượng</label>
            <select id="noticeFilterTarget" class="form-select form-select-sm" data-filter-key="target">
                <option value="">Tất cả đối tượng</option>
                <?php foreach ($noticeTargets as $target): ?>
                    <option value="<?= Security::e($target); ?>"><?= Security::e($target); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-filter-field">
            <label for="noticeFilterPoint">Ảnh hưởng điểm</label>
            <select id="noticeFilterPoint" class="form-select form-select-sm" data-filter-key="pointState">
                <option value="">Tất cả điểm</option>
                <option value="positive">Cộng điểm</option>
                <option value="negative">Trừ điểm</option>
                <option value="zero">Không đổi điểm</option>
            </select>
        </div>
        <div class="admin-filter-actions">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-filter-clear>Đặt lại</button>
        </div>
    </div>

    <table id="noticesTable" class="table datatable table-hover align-middle">
        <thead>
        <tr>
            <th>Ngày</th>
            <th>Loại</th>
            <th>Đối tượng</th>
            <th>Điểm</th>
            <th>Nội dung</th>
            <th>Thao tác</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($notices as $notice): ?>
            <?php
            $noticePoint = (int) ($notice['point_change'] ?? 0);
            $noticePointState = $noticePoint > 0 ? 'positive' : ($noticePoint < 0 ? 'negative' : 'zero');
            ?>
            <tr data-category="<?= Security::e((string) $notice['category']); ?>"
                data-target="<?= Security::e((string) $notice['target_type']); ?>"
                data-point-state="<?= Security::e($noticePointState); ?>">
                <td><?= Security::e((string) $notice['date']); ?></td>
                <td><?= Security::e((string) $notice['category']); ?></td>
                <td>
                    <div><?= Security::e((string) $notice['target_type']); ?></div>
                    <?php if (!empty($notice['room_number'])): ?>
                        <div class="badge bg-info">Phòng P<?= Security::e((string) $notice['room_number']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($notice['student_name'])): ?>
                        <div class="badge bg-secondary"><?= Security::e((string) $notice['student_name']); ?></div>
                    <?php endif; ?>
                </td>
                <td><?= Security::e((string) $notice['point_change']); ?></td>
                <td><?= Security::e((string) $notice['description']); ?></td>
                <td>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-sm btn-outline-primary btn-edit-notice"
                                data-bs-toggle="modal"
                                data-bs-target="#noticeModal"
                                data-notice-id="<?= Security::e((string) $notice['notice_id']); ?>"
                                data-target-type="<?= Security::e((string) $notice['target_type']); ?>"
                                data-category="<?= Security::e((string) $notice['category']); ?>"
                                data-point-change="<?= Security::e((string) $notice['point_change']); ?>"
                                data-room-id="<?= Security::e((string) ($notice['room_id'] ?? 0)); ?>"
                                data-student-id="<?= Security::e((string) ($notice['student_id'] ?? 0)); ?>"
                                data-description="<?= Security::e((string) $notice['description']); ?>"
                                data-date="<?= Security::e((string) $notice['date']); ?>">
                            Sửa
                        </button>
                        <button class="btn btn-sm btn-outline-danger btn-delete-notice" data-notice-id="<?= Security::e((string) $notice['notice_id']); ?>">Xóa</button>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="noticeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div>
                    <div class="section-subtitle text-uppercase fw-semibold small">Form thêm/sửa</div>
                    <h5 class="modal-title">Thông báo / Khen thưởng / Kỷ luật</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="row g-4">
                    <div class="col-lg-8">
                        <form id="noticeForm" class="row g-3">
                            <input type="hidden" name="notice_id" value="0">
                            <div class="col-md-4">
                                <label class="form-label">Đối tượng</label>
                                <select name="target_type" class="form-select">
                                    <option value="Cả tòa" data-mode="building">Cả tòa</option>
                                    <option value="Phòng" data-mode="room">Phòng</option>
                                    <option value="Cá nhân" data-mode="student">Cá nhân</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Loại</label>
                                <select name="category" class="form-select">
                                    <option value="Thông báo chung">Thông báo chung</option>
                                    <option value="Khen thưởng">Khen thưởng</option>
                                    <option value="Kỷ luật">Kỷ luật</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Điểm thay đổi</label>
                                <input name="point_change" class="form-control" type="number" value="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phòng</label>
                                <select name="room_id" class="form-select">
                                    <option value="">-- Không chọn --</option>
                                    <?php foreach ($rooms as $room): ?>
                                        <option value="<?= Security::e((string) $room['room_id']); ?>">P<?= Security::e((string) $room['room_number']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Sinh viên</label>
                                <select name="student_id" class="form-select">
                                    <option value="">-- Không chọn --</option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?= Security::e((string) $student['student_id']); ?>" data-room-id="<?= Security::e((string) ($student['room_id'] ?? '')); ?>"><?= Security::e((string) $student['full_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Nội dung</label>
                                <textarea name="description" class="form-control" rows="4"></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ngày</label>
                                <input name="date" class="form-control" type="date" value="<?= date('Y-m-d'); ?>">
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-4">
                        <div class="panel-glass rounded-4 p-4 h-100">
                            <div class="fw-semibold mb-2">Áp dụng điểm nội trú</div>
                            <p class="text-secondary mb-0">Khi lưu thông báo có điểm thay đổi, hệ thống sẽ cộng/trừ `boarding_score` theo đúng đối tượng đã chọn.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button id="saveNoticeBtn" type="button" class="btn btn-primary">Lưu thông báo</button></div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/partials/admin_footer.php'; ?>
