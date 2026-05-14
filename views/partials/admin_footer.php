<?php

declare(strict_types=1);
?>
        </main>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    window.APP_BASE_URL = "<?= Security::e(APP_BASE_URL); ?>";
</script>
<script src="<?= Security::e(APP_BASE_URL); ?>/assets/js/app.js"></script>
<!-- Toast container + global alert override to use Bootstrap toasts -->
<div aria-live="polite" aria-atomic="true" class="position-fixed top-0 end-0 p-3" style="z-index: 1080;">
    <div id="globalToastContainer"></div>
</div>
<script>
    (function () {
        const container = document.getElementById('globalToastContainer');
        window.showToast = function (message, type = 'primary', title = '') {
            const toastId = 't' + Date.now();
            const el = document.createElement('div');
            el.className = 'toast align-items-center text-bg-' + type + ' border-0';
            el.setAttribute('role', 'alert');
            el.setAttribute('aria-live', 'assertive');
            el.setAttribute('aria-atomic', 'true');
            el.style.minWidth = '280px';
            el.id = toastId;
            el.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${title ? '<strong>' + title + '</strong><br>' : ''}${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>`;
            container.appendChild(el);
            const toast = new bootstrap.Toast(el, { delay: 5000 });
            toast.show();
            el.addEventListener('hidden.bs.toast', () => el.remove());
        };

        // Override window.alert to show toast (danger)
        const originalAlert = window.alert;
        window.alert = function (msg) { try { window.showToast(String(msg), 'danger'); } catch (e) { originalAlert(msg); } };
    })();
</script>
</body>
</html>
