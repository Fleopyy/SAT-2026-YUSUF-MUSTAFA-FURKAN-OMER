        </main>
    </div>
</div>
<script>
function toggleBusinessSidebar() {
    document.getElementById('businessSidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('active');
}
document.querySelectorAll('#businessSidebar .sidebar-nav-link').forEach(function (link) {
    link.addEventListener('click', function () {
        document.querySelectorAll('#businessSidebar .sidebar-nav-link').forEach(function (item) {
            item.classList.remove('active');
        });
        link.classList.add('active');
        document.getElementById('businessSidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('active');
    });
});
function updateCustomFileInput(input) {
    const trigger = input.parentElement ? input.parentElement.querySelector('.custom-file-trigger') : null;
    if (!trigger) return;
    const textSpan = trigger.querySelector('.file-text');
    const icon = trigger.querySelector('.file-icon');
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
        if (file.size > 2 * 1024 * 1024) {
            alert('Seçilen fotoğrafın boyutu ' + sizeMb + ' MB.\nFotoğraf boyutu en fazla 2 MB olabilir.');
            input.value = '';
            if (textSpan) {
                textSpan.textContent = textSpan.getAttribute('data-default') || 'Fotoğraf seçin...';
                textSpan.style.color = '#DC2626';
                textSpan.style.fontWeight = '500';
            }
            trigger.style.borderColor = '#DC2626';
            if (icon) {
                icon.className = 'fa-solid fa-triangle-exclamation file-icon';
                icon.style.color = '#DC2626';
            }
            return;
        }
        if (textSpan) {
            textSpan.textContent = file.name + ' (' + sizeMb + ' MB)';
            textSpan.style.color = '#0F172A';
            textSpan.style.fontWeight = '700';
        }
        trigger.style.borderColor = 'var(--admin-accent)';
        if (icon) {
            icon.className = 'fa-solid fa-circle-check file-icon';
            icon.style.color = '#059669';
        }
    } else {
        if (textSpan) {
            textSpan.textContent = textSpan.getAttribute('data-default') || 'Fotoğraf seçin...';
            textSpan.style.color = '#64748B';
            textSpan.style.fontWeight = '500';
        }
        trigger.style.borderColor = 'var(--admin-border)';
        if (icon) {
            icon.className = 'fa-solid fa-cloud-arrow-up file-icon';
            icon.style.color = 'var(--admin-accent)';
        }
    }
}
document.addEventListener('change', function(e) {
    if (e.target && e.target.classList.contains('custom-file-input')) {
        updateCustomFileInput(e.target);
    }
});
</script>
</body>
</html>
