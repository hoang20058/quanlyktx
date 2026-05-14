<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
Security::requireAdminAuth();

$pageTitle = 'Quản lý hợp đồng - ' . APP_NAME;
$activeMenu = 'contracts';

$contracts = ContractRepository::all();
$students = StudentRepository::all();
$rooms = RoomRepository::selectOptions();
$contractStatuses = array_values(array_unique(array_filter(array_map(static fn (array $contract): string => (string) ($contract['status'] ?? ''), $contracts))));
sort($contractStatuses);
$contractRooms = array_values(array_unique(array_filter(array_map(static fn (array $contract): string => !empty($contract['room_number']) ? 'P' . (string) $contract['room_number'] : '', $contracts))));
sort($contractRooms);
$today = new DateTimeImmutable('today');
$contractSoonLimit = $today->modify('+30 days');

require_once __DIR__ . '/../../views/partials/admin_header.php';
?>
<div class="table-panel p-4">
    <div class="datatable-toolbar mb-3">
        <div>
            <div class="section-subtitle text-uppercase fw-semibold small">CRUD chuẩn hóa</div>
            <h2 class="section-title mb-0">Bảng dữ liệu hợp đồng</h2>
        </div>
        <div class="table-actions d-flex gap-2">
            <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#contractModal" data-contract-id="0">
                <i class="bi bi-plus-lg me-1"></i>Thêm hợp đồng
            </button>
        </div>
    </div>

    <div class="admin-filter-bar" data-filter-target="contractsTable">
        <div class="admin-filter-field">
            <label for="contractFilterStatus">Trạng thái</label>
            <select id="contractFilterStatus" class="form-select form-select-sm" data-filter-key="status">
                <option value="">Tất cả trạng thái</option>
                <?php foreach ($contractStatuses as $status): ?>
                    <option value="<?= Security::e($status); ?>"><?= Security::e($status); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-filter-field">
            <label for="contractFilterRoom">Phòng</label>
            <select id="contractFilterRoom" class="form-select form-select-sm" data-filter-key="room">
                <option value="">Tất cả phòng</option>
                <?php foreach ($contractRooms as $roomNumber): ?>
                    <option value="<?= Security::e($roomNumber); ?>"><?= Security::e($roomNumber); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-filter-field">
            <label for="contractFilterDebt">Công nợ</label>
            <select id="contractFilterDebt" class="form-select form-select-sm" data-filter-key="debtState">
                <option value="">Tất cả công nợ</option>
                <option value="debt">Còn nợ</option>
                <option value="clear">Đã tất toán</option>
            </select>
        </div>
        <div class="admin-filter-field">
            <label for="contractFilterExpiry">Hạn hợp đồng</label>
            <select id="contractFilterExpiry" class="form-select form-select-sm" data-filter-key="expiryState">
                <option value="">Tất cả hạn</option>
                <option value="open">Không ngày ra</option>
                <option value="active">Còn hạn</option>
                <option value="soon">Sắp hết hạn 30 ngày</option>
                <option value="overdue">Quá hạn</option>
            </select>
        </div>
        <div class="admin-filter-actions">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-filter-clear>Đặt lại</button>
        </div>
    </div>

    <table id="contractsTable" class="table datatable table-hover align-middle">
        <thead>
        <tr>
            <th>Sinh viên</th>
            <th>Phòng</th>
            <th>Ngày vào</th>
            <th>Ngày ra</th>
            <th>Công nợ</th>
            <th>Trạng thái</th>
            <th>Thao tác</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($contracts as $contract): ?>
            <?php
            $contractStatus = (string) ($contract['status'] ?? '');
            $contractRoomNumber = !empty($contract['room_number']) ? 'P' . (string) $contract['room_number'] : '';
            $contractDebt = (float) ($contract['debt'] ?? 0);
            $contractDebtState = $contractDebt > 0 ? 'debt' : 'clear';
            $contractExpiryState = 'open';
            if (!empty($contract['end_date'])) {
                try {
                    $contractEndDate = new DateTimeImmutable((string) $contract['end_date']);
                    if ($contractEndDate < $today) {
                        $contractExpiryState = 'overdue';
                    } elseif ($contractEndDate <= $contractSoonLimit) {
                        $contractExpiryState = 'soon';
                    } else {
                        $contractExpiryState = 'active';
                    }
                } catch (Exception) {
                    $contractExpiryState = 'open';
                }
            }
            ?>
            <tr data-status="<?= Security::e($contractStatus); ?>"
                data-room="<?= Security::e($contractRoomNumber); ?>"
                data-debt-state="<?= Security::e($contractDebtState); ?>"
                data-expiry-state="<?= Security::e($contractExpiryState); ?>">
                <td><?= Security::e((string) $contract['full_name']); ?></td>
                <td>P<?= Security::e((string) $contract['room_number']); ?></td>
                <td><?= Security::e((string) $contract['start_date']); ?></td>
                <td><?= $contract['end_date'] ? Security::e((string) $contract['end_date']) : '-'; ?></td>
                <td class="<?= ((float)($contract['debt'] ?? 0) > 0) ? 'text-danger fw-bold' : 'text-success'; ?>"><?= number_format((float) ($contract['debt'] ?? 0), 0, ',', '.'); ?> đ</td>
                <td><span class="badge text-bg-info"><?= Security::e((string) $contract['status']); ?></span></td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">⋮</button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item btn-action-extend" href="#" data-contract-id="<?= Security::e((string) $contract['contract_id']); ?>">Gia hạn</a></li>
                            <li><a class="dropdown-item btn-action-terminate" href="#" data-contract-id="<?= Security::e((string) $contract['contract_id']); ?>">Kết thúc</a></li>
                            <li><a class="dropdown-item" href="./contract-detail.php?id=<?= Security::e((string) $contract['contract_id']); ?>">Chi tiết</a></li>
                        </ul>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="contractModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Thêm/Sửa hợp đồng</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="contractForm" class="row g-3">
                    <input type="hidden" name="contract_id" value="0">
                    <div class="col-12"><label class="form-label">Sinh viên</label>
                        <select name="student_id" class="form-select" required>
                            <option value="">-- Chọn sinh viên --</option>
                            <?php foreach ($students as $s): ?>
                                <option value="<?= Security::e((string) $s['student_id']); ?>" data-priority="<?= Security::e((string) $s['priority_level']); ?>"><?= Security::e((string) $s['full_name']); ?> (<?= Security::e((string) $s['student_code']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12"><label class="form-label">Phòng</label>
                        <select name="room_id" class="form-select" required>
                            <option value="">-- Chọn phòng --</option>
                            <?php foreach ($rooms as $r): ?>
                                <option value="<?= Security::e((string) $r['room_id']); ?>" data-price="<?= Security::e((string) $r['price']); ?>">P<?= Security::e((string) $r['room_number']); ?> (<?= number_format((float) $r['price'], 0, ',', '.'); ?> đ/tháng)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6"><label class="form-label">Ngày vào</label><input name="start_date" type="date" class="form-control" required></div>
                    <div class="col-6"><label class="form-label">Ngày ra <span class="text-danger">*</span></label><input name="end_date" type="date" class="form-control" required></div>
                    <div class="col-12">
                        <div class="p-3 rounded-3 bg-light">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <div class="small text-muted">Giảm giá</div>
                                    <div id="discountDisplay" class="fw-semibold">-</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="small text-muted">Tiền phòng</div>
                                    <div id="priceDisplay" class="fw-semibold">-</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="small text-muted">Công nợ</div>
                                    <div id="debtDisplay" class="fw-semibold text-danger">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12"><label class="form-label">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option>Đang ở</option>
                            <option>Đã chuyển ra</option>
                            <option>Đã hủy</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button id="saveContractBtn" type="button" class="btn btn-primary">Lưu</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="extendModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Gia hạn hợp đồng</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="extendForm" class="row g-3">
                    <input type="hidden" name="contract_id" value="0">
                    <div class="col-12"><label class="form-label">Ngày kết thúc mới</label><input name="new_end_date" type="date" class="form-control" required></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button id="confirmExtendBtn" type="button" class="btn btn-primary">Xác nhận</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="terminateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Kết thúc hợp đồng</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="terminateForm" class="row g-3">
                    <input type="hidden" name="contract_id" value="0">
                    <div class="col-12"><label class="form-label">Lý do</label>
                        <select name="reason" class="form-select">
                            <option value="Chuyển đi">Chuyển đi</option>
                            <option value="Vi phạm">Vi phạm</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>
                    <div class="col-6"><label class="form-label">Ngày kết thúc</label><input name="end_date" type="date" class="form-control" value="<?= date('Y-m-d'); ?>"></div>
                    <div class="col-12"><label class="form-label">Ghi chú</label><textarea name="note" class="form-control" rows="3"></textarea></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button id="confirmTerminateBtn" type="button" class="btn btn-danger">Xác nhận kết thúc</button></div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/partials/admin_footer.php'; ?>

<script>
(function () {
    const bindDelete = (selector, endpoint, idKey, payloadKey) => {
        document.querySelectorAll(selector).forEach((button) => {
            button.addEventListener('click', async () => {
                const id = button.getAttribute(idKey) || '0';
                if (!id || id === '0') return;
                if (!confirm('Bạn chắc chắn muốn xóa bản ghi này?')) return;

                try {
                    const resp = await fetch((window.APP_BASE_URL || '') + endpoint, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ [payloadKey]: id })
                    });
                    const json = await resp.json();
                    if (resp.ok && json.ok) {
                        location.reload();
                    } else {
                        alert('Lỗi: ' + (json.message || 'Xóa thất bại'));
                    }
                } catch (err) {
                    alert('Lỗi kết nối');
                }
            });
        });
    };

    bindDelete('.btn-delete-contract', '/api/contracts/delete.php', 'data-contract-id', 'contract_id');

    const getDiscountByPriority = (priority) => {
        if (priority <= 2) return 50;
        if (priority <= 4) return 30;
        return 10;
    };

    const calculatePrice = () => {
        const form = document.getElementById('contractForm');
        if (!form) return;

        const studentSelect = form.querySelector('[name="student_id"]');
        const roomSelect = form.querySelector('[name="room_id"]');
        const startDateInput = form.querySelector('[name="start_date"]');
        const endDateInput = form.querySelector('[name="end_date"]');
        const discountDisplay = document.getElementById('discountDisplay');
        const priceDisplay = document.getElementById('priceDisplay');
        const debtDisplay = document.getElementById('debtDisplay');

        if (!studentSelect || !roomSelect || !startDateInput || !endDateInput || !discountDisplay || !priceDisplay || !debtDisplay) {
            return;
        }

        const selectedStudent = studentSelect.options[studentSelect.selectedIndex];
        const selectedRoom = roomSelect.options[roomSelect.selectedIndex];
        const priorityLevel = parseInt(selectedStudent?.getAttribute('data-priority') || '8');
        const roomPrice = parseFloat(selectedRoom?.getAttribute('data-price') || '0');
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        const deposit = 0; // deposit is no longer entered at contract creation; payments handled separately

        const discountPercent = getDiscountByPriority(priorityLevel);

        if (startDate && endDate && roomPrice > 0) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            const daysInContract = Math.ceil((end - start) / (1000 * 60 * 60 * 24));

            if (daysInContract > 0) {
                const basePrice = (roomPrice / 30) * daysInContract;
                const price = basePrice * (100 - discountPercent) / 100;
                const debt = Math.max(0, price - deposit);

                discountDisplay.textContent = discountPercent + '%';
                priceDisplay.textContent = price.toLocaleString('vi-VN', {minimumFractionDigits: 0, maximumFractionDigits: 2}) + ' đ';
                debtDisplay.textContent = debt.toLocaleString('vi-VN', {minimumFractionDigits: 0, maximumFractionDigits: 2}) + ' đ';
            }
        } else {
            discountDisplay.textContent = discountPercent + '%';
            priceDisplay.textContent = '-';
            debtDisplay.textContent = '-';
        }
    };

    const form = document.getElementById('contractForm');
    if (form) {
        form.querySelector('[name="student_id"]')?.addEventListener('change', calculatePrice);
        form.querySelector('[name="room_id"]')?.addEventListener('change', calculatePrice);
        form.querySelector('[name="start_date"]')?.addEventListener('change', calculatePrice);
        form.querySelector('[name="end_date"]')?.addEventListener('change', calculatePrice);
    }

    const saveBtn = document.getElementById('saveContractBtn');
    if (saveBtn) {
        saveBtn.addEventListener('click', async () => {
            const form = document.getElementById('contractForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const data = new FormData(form);

            try {
                const resp = await fetch((window.APP_BASE_URL || '') + '/api/contracts/save.php', {
                    method: 'POST',
                    body: data
                });
                const json = await resp.json();
                if (resp.ok && json.ok) {
                    location.reload();
                } else {
                    // show nicer message if available
                    alert('Lỗi: ' + (json.message || 'Không thể lưu hợp đồng'));
                }
            } catch (err) {
                alert('Lỗi kết nối');
            }
        });
    }

    calculatePrice();
})();
</script>

<script>
(function () {
    const extendModal = new bootstrap.Modal(document.getElementById('extendModal'));
    const terminateModal = new bootstrap.Modal(document.getElementById('terminateModal'));

    document.querySelectorAll('.btn-action-extend').forEach((a) => {
        a.addEventListener('click', (e) => {
            e.preventDefault();
            const id = a.getAttribute('data-contract-id');
            document.querySelector('#extendForm [name=contract_id]').value = id;
            extendModal.show();
        });
    });

    document.querySelectorAll('.btn-action-terminate').forEach((a) => {
        a.addEventListener('click', (e) => {
            e.preventDefault();
            const id = a.getAttribute('data-contract-id');
            document.querySelector('#terminateForm [name=contract_id]').value = id;
            terminateModal.show();
        });
    });

    document.getElementById('confirmExtendBtn').addEventListener('click', async () => {
        const form = document.getElementById('extendForm');
        const data = Object.fromEntries(new FormData(form));
        try {
            const resp = await fetch((window.APP_BASE_URL || '') + '/api/contracts/extend.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
            const json = await resp.json();
            if (resp.ok && json.ok) { location.reload(); } else { alert('Lỗi: ' + (json.message || 'Không thành công')); }
        } catch (err) { alert('Lỗi kết nối'); }
    });

    document.getElementById('confirmTerminateBtn').addEventListener('click', async () => {
        const form = document.getElementById('terminateForm');
        const data = Object.fromEntries(new FormData(form));
        try {
            const resp = await fetch((window.APP_BASE_URL || '') + '/api/contracts/terminate.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
            const json = await resp.json();
            if (resp.ok && json.ok) { location.reload(); } else { alert('Lỗi: ' + (json.message || 'Không thành công')); }
        } catch (err) { alert('Lỗi kết nối'); }
    });
})();
</script>
