document.addEventListener('DOMContentLoaded', () => {
    const dataTableSelector = '.datatable';

    const flash = (message, type = 'success') => {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} small position-fixed top-0 end-0 m-3`;
        alert.style.zIndex = 1060;
        alert.textContent = message;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 2000);
    };

    const apiUrl = (path) => (window.APP_BASE_URL || '') + path;

    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.DataTable) {
        window.jQuery(dataTableSelector).each(function () {
            const table = window.jQuery(this);

            if (!window.jQuery.fn.DataTable.isDataTable(table)) {
                table.DataTable({
                    pageLength: 10,
                    responsive: true,
                    language: {
                        search: 'Tìm kiếm:',
                        lengthMenu: 'Hiển thị _MENU_ dòng',
                        info: 'Đang xem _START_ đến _END_ của _TOTAL_ dòng',
                        paginate: { previous: 'Trước', next: 'Sau' },
                        zeroRecords: 'Không tìm thấy dữ liệu phù hợp'
                    }
                });
            }
        });
    }

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
        if (window.bootstrap) new bootstrap.Tooltip(element);
    });

    document.querySelectorAll('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const text = button.getAttribute('data-copy') || '';
            try {
                await navigator.clipboard.writeText(text);
                button.classList.add('btn-success');
                button.textContent = 'Đã sao chép';
                setTimeout(() => {
                    button.classList.remove('btn-success');
                    button.textContent = 'Sao chép';
                }, 1500);
            } catch (error) {
                console.error(error);
            }
        });
    });

    const populateForm = (form, data) => {
        Object.entries(data).forEach(([key, value]) => {
            const field = form.querySelector(`[name="${key}"]`);
            if (field) {
                field.value = value ?? '';
            }
        });
    };

    const roomForm = document.getElementById('roomForm');
    const roomModal = document.getElementById('roomModal');
    document.querySelectorAll('.btn-edit-room').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!roomForm || !roomModal) return;
            populateForm(roomForm, {
                room_id: btn.dataset.roomId,
                room_number: btn.dataset.roomNumber,
                floor_number: btn.dataset.floorNumber,
                capacity: btn.dataset.capacity,
                room_type: btn.dataset.roomType,
                status: btn.dataset.status,
                price: btn.dataset.price,
            });
        });
    });
    roomModal?.addEventListener('show.bs.modal', (event) => {
        if (!roomForm) return;
        const trigger = event.relatedTarget;
        if (trigger && trigger.getAttribute('data-room-id') === '0') {
            roomForm.reset();
            const hidden = roomForm.querySelector('[name="room_id"]');
            if (hidden) hidden.value = '0';
        }
    });

    const studentForm = document.getElementById('studentForm');
    const studentModal = document.getElementById('studentModal');
    document.querySelectorAll('.btn-edit-student').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!studentForm) return;
            populateForm(studentForm, {
                student_id: btn.dataset.studentId,
                student_code: btn.dataset.studentCode,
                full_name: btn.dataset.fullName,
                dob: btn.dataset.dob,
                phone: btn.dataset.phone,
                email: btn.dataset.email,
                department: btn.dataset.department,
                status: btn.dataset.status,
                priority_level: btn.dataset.priorityLevel,
                boarding_score: btn.dataset.boardingScore,
            });
        });
    });
    studentModal?.addEventListener('show.bs.modal', (event) => {
        if (!studentForm) return;
        const trigger = event.relatedTarget;
        if (trigger && trigger.getAttribute('data-student-id') === '0') {
            studentForm.reset();
            const hidden = studentForm.querySelector('[name="student_id"]');
            if (hidden) hidden.value = '0';
        }
    });

    const noticeForm = document.getElementById('noticeForm');
    const noticeModal = document.getElementById('noticeModal');
    document.querySelectorAll('.btn-edit-notice').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!noticeForm) return;
            populateForm(noticeForm, {
                notice_id: btn.dataset.noticeId,
                target_type: btn.dataset.targetType,
                category: btn.dataset.category,
                point_change: btn.dataset.pointChange,
                room_id: btn.dataset.roomId,
                student_id: btn.dataset.studentId,
                description: btn.dataset.description,
                date: btn.dataset.date,
            });
        });
    });
    noticeModal?.addEventListener('show.bs.modal', (event) => {
        if (!noticeForm) return;
        const trigger = event.relatedTarget;
        if (trigger && trigger.getAttribute('data-notice-id') === '0') {
            noticeForm.reset();
            const hidden = noticeForm.querySelector('[name="notice_id"]');
            if (hidden) hidden.value = '0';
        }
    });

    document.querySelectorAll('.btn-switch-room').forEach((btn) => {
        btn.addEventListener('click', () => {
            const studentId = btn.getAttribute('data-student-id') || '';
            const studentName = btn.getAttribute('data-student-name') || '';
            const currentRoomId = btn.getAttribute('data-current-room-id') || '';
            const modalEl = document.getElementById('switchRoomModal');
            if (!modalEl) return;
            const studentInput = modalEl.querySelector('#switch_student_id');
            const nameInput = modalEl.querySelector('#switch_student_name');
            const newSelect = modalEl.querySelector('#switch_new_room_id');
            if (studentInput) studentInput.value = studentId;
            if (nameInput) nameInput.value = studentName;
            if (newSelect && currentRoomId) {
                for (const opt of newSelect.options) {
                    opt.selected = opt.value === currentRoomId;
                }
            }
        });
    });

    const confirmSwitch = document.getElementById('confirmSwitchRoom');
    if (confirmSwitch) {
        confirmSwitch.addEventListener('click', async () => {
            const modalEl = document.getElementById('switchRoomModal');
            if (!modalEl) return;
            const studentId = modalEl.querySelector('#switch_student_id')?.value || '';
            const newRoomId = modalEl.querySelector('#switch_new_room_id')?.value || '';

            try {
                const resp = await fetch(apiUrl('/api/rooms/switch.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ student_id: studentId, new_room_id: newRoomId, csrf_token: window.APP_CSRF })
                });
                const data = await resp.json();
                if (!resp.ok || !data.ok) {
                    flash(data.message || 'Lỗi khi chuyển phòng', 'danger');
                    return;
                }
                flash(data.message || 'Chuyển phòng thành công', 'success');
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                setTimeout(() => location.reload(), 400);
            } catch (err) {
                console.error(err);
                flash('Lỗi kết nối', 'danger');
            }
        });
    }

    const bindSave = (buttonId, formId, endpoint) => {
        const button = document.getElementById(buttonId);
        const form = document.getElementById(formId);
        if (!button || !form) return;

        button.addEventListener('click', async () => {
            const fd = new FormData(form);
            const data = Object.fromEntries(fd.entries());
            data.csrf_token = window.APP_CSRF;

            try {
                const resp = await fetch(apiUrl(endpoint), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const json = await resp.json();
                if (resp.ok && json.ok) {
                    location.reload();
                } else {
                    flash(json.message || 'Lưu dữ liệu thất bại', 'danger');
                }
            } catch (err) {
                console.error(err);
                flash('Lỗi kết nối', 'danger');
            }
        });
    };

    bindSave('saveRoomBtn', 'roomForm', '/api/rooms/save.php');
    bindSave('saveStudentBtn', 'studentForm', '/api/students/save.php');
    bindSave('saveNoticeBtn', 'noticeForm', '/api/notices/save.php');

    const bindDelete = (selector, endpoint, idKey, payloadKey) => {
        document.querySelectorAll(selector).forEach((button) => {
            button.addEventListener('click', async () => {
                const id = button.getAttribute(idKey) || '0';
                if (!id || id === '0') return;
                if (!confirm('Bạn chắc chắn muốn xóa bản ghi này?')) return;

                try {
                    const resp = await fetch(apiUrl(endpoint), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ [payloadKey]: id, csrf_token: window.APP_CSRF })
                    });
                    const json = await resp.json();
                    if (resp.ok && json.ok) {
                        location.reload();
                    } else {
                        flash(json.message || 'Xóa dữ liệu thất bại', 'danger');
                    }
                } catch (err) {
                    console.error(err);
                    flash('Lỗi kết nối', 'danger');
                }
            });
        });
    };

    bindDelete('.btn-delete-room', '/api/rooms/delete.php', 'data-room-id', 'room_id');
    bindDelete('.btn-delete-student', '/api/students/delete.php', 'data-student-id', 'student_id');
    bindDelete('.btn-delete-notice', '/api/notices/delete.php', 'data-notice-id', 'notice_id');
});
