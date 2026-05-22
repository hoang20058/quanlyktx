document.addEventListener('DOMContentLoaded', () => {
    const flash = (message, type = 'success') => {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} small position-fixed top-0 end-0 m-3`;
        alert.style.zIndex = 1060;
        alert.textContent = message;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 2200);
    };

    const ensureConfirmToastContainer = () => {
        let container = document.getElementById('confirmToastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'confirmToastContainer';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = 1080;
            document.body.appendChild(container);
        }
        return container;
    };

    const confirmToast = (message, options = {}) => new Promise((resolve) => {
        const { title = 'Xác nhận', confirmText = 'Đồng ý', cancelText = 'Hủy', variant = 'danger' } = options;

        if (!window.bootstrap || !window.bootstrap.Toast) {
            resolve(confirm(message));
            return;
        }

        const container = ensureConfirmToastContainer();
        const toastEl = document.createElement('div');
        toastEl.className = 'toast align-items-center text-bg-light border-0 shadow';
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        toastEl.innerHTML = `
            <div class="toast-header">
                <strong class="me-auto">${title}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                <div class="mb-3">${message}</div>
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-confirm-cancel>${cancelText}</button>
                    <button type="button" class="btn btn-sm btn-${variant}" data-confirm-ok>${confirmText}</button>
                </div>
            </div>
        `;

        container.appendChild(toastEl);
        const toast = new window.bootstrap.Toast(toastEl, { autohide: false });

        let resolved = false;
        const cleanup = (result) => {
            if (resolved) return;
            resolved = true;
            resolve(result);
            toast.hide();
        };

        toastEl.querySelector('[data-confirm-ok]')?.addEventListener('click', () => cleanup(true));
        toastEl.querySelector('[data-confirm-cancel]')?.addEventListener('click', () => cleanup(false));
        toastEl.addEventListener('hidden.bs.toast', () => {
            if (!resolved) resolve(false);
            toastEl.remove();
        });

        toast.show();
    });

    window.appFlash = flash;
    window.appConfirm = confirmToast;

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

        window.jQuery('.datatable').each(function () {
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
            const table = document.getElementById(filterBar.dataset.filterTarget || '');
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
        if (window.bootstrap) {
            new bootstrap.Tooltip(element);
        }
    });

    document.querySelectorAll('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const text = button.getAttribute('data-copy') || '';
            try {
                await navigator.clipboard.writeText(text);
                const oldText = button.textContent;
                button.classList.add('btn-success');
                button.textContent = 'Đã sao chép';
                setTimeout(() => {
                    button.classList.remove('btn-success');
                    button.textContent = oldText || 'Sao chép';
                }, 1500);
            } catch (error) {
                console.error(error);
            }
        });
    });
});
