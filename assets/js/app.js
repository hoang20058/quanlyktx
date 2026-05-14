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
        if (!window.__adminTableFiltersRegistered) {
            window.jQuery.fn.dataTable.ext.search.push((settings, data, dataIndex) => {
                const tableId = settings.nTable?.id || '';
                if (!tableId) return true;

                const filterBar = document.querySelector(`[data-filter-target="${tableId}"]`);
                if (!filterBar) return true;

                const row = settings.aoData?.[dataIndex]?.nTr;
                if (!row) return true;

                return Array.from(filterBar.querySelectorAll('[data-filter-key]')).every((control) => {
                    if (control.disabled) return true;

                    const selectedValue = String(control.value || '').trim();
                    if (!selectedValue) return true;

                    const key = control.dataset.filterKey;
                    const filterType = control.dataset.filterType || 'equals';
                    const rowValue = String(row.dataset[key] || '').trim();

                    if (filterType === 'contains') {
                        return rowValue.toLocaleLowerCase('vi-VN').includes(selectedValue.toLocaleLowerCase('vi-VN'));
                    }

                    if (filterType === 'min') {
                        return Number(rowValue || 0) >= Number(selectedValue);
                    }

                    if (filterType === 'max') {
                        return Number(rowValue || 0) <= Number(selectedValue);
                    }

                    return rowValue === selectedValue;
                });
            });

            window.__adminTableFiltersRegistered = true;
        }

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

        document.querySelectorAll('[data-filter-target]').forEach((filterBar) => {
            const targetId = filterBar.dataset.filterTarget;
            const table = targetId ? document.getElementById(targetId) : null;
            if (!table) return;

            const redraw = () => {
                if (window.jQuery.fn.DataTable.isDataTable(table)) {
                    window.jQuery(table).DataTable().draw();
                }
            };

            filterBar.querySelectorAll('[data-filter-key]').forEach((control) => {
                control.addEventListener('change', redraw);
                control.addEventListener('input', redraw);
            });

            filterBar.querySelectorAll('[data-filter-clear]').forEach((button) => {
                button.addEventListener('click', () => {
                    filterBar.querySelectorAll('[data-filter-key]').forEach((control) => {
                        control.value = '';
                    });
                    redraw();
                });
            });
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
    const roomFloorInput = roomForm?.querySelector('[name="floor_number"]');
    const roomSequenceInput = roomForm?.querySelector('[name="room_sequence"]');
    const roomNumberDisplay = document.getElementById('room_number_display');

    const updateRoomNumberDisplay = () => {
        const floor = parseInt(roomFloorInput?.value || '0', 10);
        const sequence = parseInt(roomSequenceInput?.value || '0', 10);
        const roomNumber = floor > 0 && sequence > 0 ? (floor * 100 + sequence) : 0;

        if (roomNumberDisplay) {
            roomNumberDisplay.value = roomNumber > 0 ? `P${roomNumber}` : 'P000';
        }
    };

    const bindRoomNumberPreview = () => {
        if (!roomForm) return;

        roomForm.querySelectorAll('.form-room-input').forEach((element) => {
            element.addEventListener('input', updateRoomNumberDisplay);
            element.addEventListener('change', updateRoomNumberDisplay);
            element.addEventListener('keyup', updateRoomNumberDisplay);
            element.addEventListener('paste', () => setTimeout(updateRoomNumberDisplay, 0));
        });

        updateRoomNumberDisplay();
    };

    bindRoomNumberPreview();

    document.querySelectorAll('.btn-edit-room').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!roomForm || !roomModal) return;
            populateForm(roomForm, {
                room_id: btn.dataset.roomId,
                floor_number: btn.dataset.floorNumber,
                room_sequence: btn.dataset.roomSequence,
                capacity: btn.dataset.capacity,
                room_type: btn.dataset.roomType,
                status: btn.dataset.status,
                price: btn.dataset.price,
            });

            if (roomSequenceInput && !roomSequenceInput.value) {
                const roomNumber = parseInt(btn.dataset.roomNumber || '0', 10);
                const floorNumber = parseInt(btn.dataset.floorNumber || '0', 10);
                const roomSequence = roomNumber > 0 && floorNumber > 0 ? roomNumber - (floorNumber * 100) : 0;
                roomSequenceInput.value = roomSequence > 0 ? String(roomSequence) : '';
            }
            updateRoomNumberDisplay();
        });
    });
    if (roomModal) {
        roomModal.addEventListener('show.bs.modal', (event) => {
            if (!roomForm) {
                return;
            }

            const trigger = event.relatedTarget;
            if (trigger && trigger.getAttribute('data-room-id') === '0') {
                roomForm.reset();
                const hidden = roomForm.querySelector('[name="room_id"]');
                if (hidden) hidden.value = '0';
            }

            updateRoomNumberDisplay();
        });
    }

    const studentForm = document.getElementById('studentForm');
    const studentModal = document.getElementById('studentModal');
    const studentModalTitle = studentModal?.querySelector('.modal-title');
    const updateStudentScoreDisplay = (score) => {
        const parsedScore = parseInt(score ?? '100', 10);
        const scoreValue = Number.isNaN(parsedScore) ? 100 : parsedScore;
        const scoreInput = studentForm?.querySelector('[name="boarding_score"]');
        const scoreDisplay = document.getElementById('displayBoardingScore');

        if (scoreInput) {
            scoreInput.value = String(scoreValue);
        }

        if (scoreDisplay) {
            scoreDisplay.textContent = String(scoreValue);
            scoreDisplay.style.color = scoreValue >= 80 ? '#198754' : (scoreValue >= 60 ? '#0d6efd' : (scoreValue >= 40 ? '#ff9800' : '#dc3545'));
        }
    };

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

            updateStudentScoreDisplay(btn.dataset.boardingScore);
            if (studentModalTitle) {
                studentModalTitle.textContent = btn.dataset.fullName ? `Sửa sinh viên: ${btn.dataset.fullName}` : 'Sửa sinh viên';
            }
        });
    });
    studentModal?.addEventListener('show.bs.modal', (event) => {
        if (!studentForm) return;
        const trigger = event.relatedTarget;
        if (trigger && trigger.getAttribute('data-student-id') === '0') {
            studentForm.reset();
            const hidden = studentForm.querySelector('[name="student_id"]');
            if (hidden) hidden.value = '0';
            updateStudentScoreDisplay('100');
            if (studentModalTitle) {
                studentModalTitle.textContent = 'Thêm sinh viên';
            }
        }
    });

    const noticeForm = document.getElementById('noticeForm');
    const noticeModal = document.getElementById('noticeModal');
    const noticeTargetInput = noticeForm?.querySelector('[name="target_type"]');
    const noticePointInput = noticeForm?.querySelector('[name="point_change"]');
    const noticeRoomInput = noticeForm?.querySelector('[name="room_id"]');
    const noticeStudentInput = noticeForm?.querySelector('[name="student_id"]');

    const noticeMode = () => noticeTargetInput?.selectedOptions?.[0]?.dataset.mode || 'building';

    const filterNoticeStudents = (preferredStudentId = '') => {
        if (!noticeStudentInput) return;

        const roomId = noticeRoomInput?.value || '';
        const selectedStudentId = preferredStudentId || noticeStudentInput.value || '';
        let selectedStudentStillVisible = false;
        let roomHasStudents = false;
        const placeholder = noticeStudentInput.querySelector('option[value=""]');

        noticeStudentInput.querySelectorAll('option').forEach((option) => {
            if (!option.value) {
                option.hidden = false;
                option.disabled = false;
                return;
            }

            const matchesRoom = roomId !== '' && option.dataset.roomId === roomId;
            option.hidden = !matchesRoom;
            option.disabled = !matchesRoom;
            option.style.display = matchesRoom ? '' : 'none';

            if (matchesRoom) {
                roomHasStudents = true;
            }

            if (matchesRoom && option.value === selectedStudentId) {
                selectedStudentStillVisible = true;
            }
        });

        if (placeholder) {
            placeholder.textContent = roomId ? (roomHasStudents ? '-- Chọn sinh viên --' : '-- Phòng này chưa có sinh viên --') : '-- Chọn phòng trước --';
        }

        noticeStudentInput.value = selectedStudentStillVisible ? selectedStudentId : '';
    };

    const syncNoticeForm = (preferredStudentId = '') => {
        if (!noticeForm || !noticeTargetInput || !noticePointInput || !noticeRoomInput || !noticeStudentInput) return;

        const mode = noticeMode();
        const isBuildingTarget = mode === 'building';
        const isRoomTarget = mode === 'room';
        const isStudentTarget = mode === 'student';

        noticeRoomInput.disabled = isBuildingTarget;
        noticeRoomInput.required = isRoomTarget || isStudentTarget;
        if (isBuildingTarget) {
            noticeRoomInput.value = '';
        }

        noticePointInput.disabled = !isStudentTarget;
        if (!isStudentTarget) {
            noticePointInput.value = '0';
        }

        filterNoticeStudents(preferredStudentId);

        noticeStudentInput.disabled = !isStudentTarget || !noticeRoomInput.value;
        noticeStudentInput.required = isStudentTarget;
        if (!isStudentTarget || noticeStudentInput.disabled) {
            noticeStudentInput.value = '';
        }
    };

    noticeTargetInput?.addEventListener('change', () => syncNoticeForm());
    noticeRoomInput?.addEventListener('change', () => syncNoticeForm());

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
            syncNoticeForm(btn.dataset.studentId);
        });
    });
    noticeModal?.addEventListener('show.bs.modal', (event) => {
        if (!noticeForm) return;
        const trigger = event.relatedTarget;
        if (trigger && trigger.getAttribute('data-notice-id') === '0') {
            noticeForm.reset();
            const hidden = noticeForm.querySelector('[name="notice_id"]');
            if (hidden) hidden.value = '0';
            syncNoticeForm();
        }
    });
    syncNoticeForm();

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
                    body: JSON.stringify({ student_id: studentId, new_room_id: newRoomId })
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
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const fd = new FormData(form);
            const data = Object.fromEntries(fd.entries());

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
                        body: JSON.stringify({ [payloadKey]: id })
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
