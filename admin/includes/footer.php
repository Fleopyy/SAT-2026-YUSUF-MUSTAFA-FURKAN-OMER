        </main>
    </div>
</div>

<script>
                 
function toggleSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (sidebar) sidebar.classList.toggle('active');
    if (overlay) overlay.classList.toggle('active');
}

                            
function toggleNotificationMenu() {
    const menu = document.getElementById('notificationsMenu');
    if (menu) {
        menu.style.display = (menu.style.display === 'none' || menu.style.display === '') ? 'block' : 'none';
    }
}

                                           
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('notificationsDropdown');
    const menu = document.getElementById('notificationsMenu');
    if (dropdown && menu && !dropdown.contains(e.target)) {
        menu.style.display = 'none';
    }
});

                            
function showToast(message, type = 'success') {
    let container = document.getElementById('adminToastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'adminToastContainer';
        container.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:10px;';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = `admin-toast toast-${type}`;
    const iconClass = type === 'success' ? 'fa-circle-check' : (type === 'error' ? 'fa-circle-xmark' : 'fa-circle-info');
    toast.innerHTML = `<i class="fa-solid ${iconClass}"></i> <span>${message}</span>`;
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}
</script>

<!-- MODERN MERKEZİ ONAY VE UYARI MODALI -->

<style>
</style>
<div id="adminConfirmModal" class="modal-overlay" style="z-index: 999999;">
    <div class="modal-box" style="max-width: 440px; text-align: center; padding: 32px 26px; border-radius: 20px; box-shadow: 0 25px 60px -15px rgba(0,0,0,0.3); border: 1px solid rgba(226, 232, 240, 0.8);">
        <div id="adminConfirmIconWrap" style="width: 64px; height: 64px; border-radius: 50%; background: #FEF3C7; color: #D97706; display: inline-flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: 18px;">
            <i class="fa-solid fa-circle-question" id="adminConfirmIcon"></i>
        </div>
        <h3 id="adminConfirmTitle" style="font-size: 19px; font-weight: 800; color: #0F172A; margin: 0 0 10px; letter-spacing: -0.3px;">İşlem Onayı</h3>
        <p id="adminConfirmMessage" style="font-size: 14px; color: #64748B; margin: 0 0 26px; line-height: 1.55;"></p>
        <div style="display: flex; gap: 12px; justify-content: center;">
            <button type="button" id="adminConfirmCancelBtn" style="flex: 1; padding: 12px 18px; border-radius: 11px; border: 1.5px solid #CBD5E1; background: #fff; color: #475569; font-weight: 700; cursor: pointer; font-size: 14px; transition: all .15s;">Vazgeç</button>
            <button type="button" id="adminConfirmSubmitBtn" style="flex: 1; padding: 12px 20px; border-radius: 11px; background: #C28840; color: #fff; font-weight: 800; border: none; cursor: pointer; font-size: 14px; transition: all .15s; box-shadow: 0 4px 14px rgba(194,136,64,0.35);">Evet, Onayla</button>
        </div>
    </div>
</div>

<script>
(function() {
    var modal = document.getElementById('adminConfirmModal');
    var currentCallback = null;

    window.adminConfirmForm = function(e, form, message) {
        if (form.dataset.confirmed === 'true') {
            form.dataset.confirmed = '';
            return true;
        }
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        adminConfirm(message, function() {
            form.dataset.confirmed = 'true';
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
        return false;
    };

    window.adminConfirm = function(message, callback, options) {
        options = options || {};
        var title = options.title || 'İşlem Onayı';
        var confirmText = options.confirmText || 'Evet, Onayla';
        var cancelText = options.cancelText || 'Vazgeç';
        var isDanger = options.danger || /sil|iptal|askı/i.test(message);

        document.getElementById('adminConfirmTitle').textContent = title;
        document.getElementById('adminConfirmMessage').textContent = message;

        var submitBtn = document.getElementById('adminConfirmSubmitBtn');
        var cancelBtn = document.getElementById('adminConfirmCancelBtn');
        var iconWrap = document.getElementById('adminConfirmIconWrap');
        var icon = document.getElementById('adminConfirmIcon');

        submitBtn.textContent = confirmText;
        cancelBtn.textContent = cancelText;

        if (isDanger) {
            iconWrap.style.background = '#FEE2E2';
            iconWrap.style.color = '#DC2626';
            icon.className = 'fa-solid fa-triangle-exclamation';
            submitBtn.style.background = '#DC2626';
            submitBtn.style.boxShadow = '0 4px 14px rgba(220, 38, 38, 0.35)';
        } else {
            iconWrap.style.background = '#FEF3C7';
            iconWrap.style.color = '#D97706';
            icon.className = 'fa-solid fa-circle-question';
            submitBtn.style.background = '#C28840';
            submitBtn.style.boxShadow = '0 4px 14px rgba(194, 136, 64, 0.35)';
        }

        currentCallback = callback;
        modal.style.display = 'flex';
        modal.classList.add('active');
        submitBtn.focus();
    };

    window.closeAdminConfirm = function() {
        if (modal) {
            modal.classList.remove('active');
            modal.style.display = 'none';
        }
        currentCallback = null;
    };

    document.addEventListener('click', function(e) {
        if (e.target && (e.target.id === 'adminConfirmCancelBtn' || e.target.id === 'adminConfirmModal')) {
            closeAdminConfirm();
        } else if (e.target && e.target.id === 'adminConfirmSubmitBtn') {
            var cb = currentCallback;
            closeAdminConfirm();
            if (typeof cb === 'function') cb();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('active')) {
            closeAdminConfirm();
        }
    });

    function bindConfirmHandlers() {
        document.querySelectorAll('form[onsubmit*="confirm("]').forEach(function(form) {
            var onsubmitStr = form.getAttribute('onsubmit') || '';
            var match = onsubmitStr.match(/confirm\s*\(\s*['"`](.*?)['"`]\s*\)/);
            var msg = match ? match[1] : 'Bu işlemi onaylıyor musunuz?';

            form.removeAttribute('onsubmit');
            form.addEventListener('submit', function(e) {
                if (form.dataset.confirmed === 'true') {
                    form.dataset.confirmed = '';
                    return true;
                }
                e.preventDefault();
                adminConfirm(msg, function() {
                    form.dataset.confirmed = 'true';
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                });
            });
        });

        document.querySelectorAll('button[onclick*="confirm("]').forEach(function(btn) {
            var onclickStr = btn.getAttribute('onclick') || '';
            var match = onclickStr.match(/confirm\s*\(\s*['"`](.*?)['"`]\s*\)/);
            var msg = match ? match[1] : 'Bu işlemi onaylıyor musunuz?';

            btn.removeAttribute('onclick');
            btn.addEventListener('click', function(e) {
                if (btn.dataset.confirmed === 'true') {
                    btn.dataset.confirmed = '';
                    return true;
                }
                e.preventDefault();
                adminConfirm(msg, function() {
                    btn.dataset.confirmed = 'true';
                    btn.click();
                });
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindConfirmHandlers);
    } else {
        bindConfirmHandlers();
    }
})();
</script>
</body>
</html>
