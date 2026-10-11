const cityDistrictsMap = {
    istanbul: ['Kadıköy', 'Beşiktaş', 'Şişli', 'Üsküdar', 'Bakırköy', 'Fatih', 'Beyoğlu', 'Maltepe', 'Ataşehir', 'Pendik'],
    ankara: ['Çankaya', 'Yenimahalle', 'Keçiören', 'Mamak', 'Etimesgut', 'Gölbaşı'],
    izmir: ['Konak', 'Karşıyaka', 'Bornova', 'Alsancak', 'Buca', 'Çeşme', 'Gaziemir'],
    bursa: ['Nilüfer', 'Osmangazi', 'Yıldırım', 'Mudanya'],
    antalya: ['Muratpaşa', 'Konyaaltı', 'Alanya', 'Kepez', 'Manavgat']
};

document.addEventListener('DOMContentLoaded', () => {
    updateDistrictOptions('', 'searchDistrict');
    updateDistrictOptions('', 'barberDistrict');
    initHeroSlider();
});

<<<<<<< HEAD
function initHeroSlider() {
    const slides = document.querySelectorAll('.hero-slider .hero-slide');
    if (!slides || slides.length <= 1) return;

    let currentSlide = 0;
    setInterval(() => {
        slides[currentSlide].classList.remove('active');
        currentSlide = (currentSlide + 1) % slides.length;
        slides[currentSlide].classList.add('active');
    }, 4500);
}

=======
>>>>>>> c64d98ffda22c826f28598755e13fe44fd295fed
function updateDistrictOptions(selectedCity, targetSelectId) {
    const districtSelect = document.getElementById(targetSelectId);
    if (!districtSelect) return;

    districtSelect.innerHTML = '';

    if (!selectedCity || !cityDistrictsMap[selectedCity]) {
        const defaultOpt = document.createElement('option');
        defaultOpt.value = '';
        defaultOpt.textContent = 'Önce Şehir Seçin';
        districtSelect.appendChild(defaultOpt);
        return;
    }

    const defaultOpt = document.createElement('option');
    defaultOpt.value = '';
    defaultOpt.textContent = 'Tüm İlçeler';
    districtSelect.appendChild(defaultOpt);

    const districts = cityDistrictsMap[selectedCity];
    districts.forEach(district => {
        const opt = document.createElement('option');
        opt.value = district.toLowerCase().replace(/\s+/g, '-');
        opt.textContent = district;
        districtSelect.appendChild(opt);
    });
}

function fillQuickSearch(cityName, districtName, searchQuery) {
    const citySelect = document.getElementById('searchCity');
    const districtSelect = document.getElementById('searchDistrict');
    const searchInput = document.getElementById('searchQuery');

    const cityVal = cityName.toLowerCase().replace('i̇', 'i').replace('ı', 'i');

    if (citySelect) {
        citySelect.value = cityVal;
        updateDistrictOptions(cityVal, 'searchDistrict');
    }

    setTimeout(() => {
        if (districtSelect && districtName) {
            for (let opt of districtSelect.options) {
                if (opt.textContent.toLowerCase() === districtName.toLowerCase()) {
                    districtSelect.value = opt.value;
                    break;
                }
            }
        }
    }, 50);

    if (searchInput) {
        searchInput.value = searchQuery;
    }
}

function openAuthModal(mode = 'login', role = 'customer') {
    const modalEl = document.getElementById('authModal');
    if (!modalEl) return;

    const authModal = bootstrap.Modal.getOrCreateInstance(modalEl);
    authModal.show();

    const roleTabTrigger = document.querySelector(role === 'customer' ? '#pills-customer-tab' : '#pills-barber-tab');
    if (roleTabTrigger) {
        const tab = bootstrap.Tab.getOrCreateInstance(roleTabTrigger);
        tab.show();
    }

<<<<<<< HEAD
=======
   
>>>>>>> c64d98ffda22c826f28598755e13fe44fd295fed
    setTimeout(() => {
        let subTabSelector = '';
        if (role === 'customer') {
            subTabSelector = mode === 'login' ? '#cust-login-tab' : '#cust-reg-tab';
        } else {
            subTabSelector = mode === 'login' ? '#barber-login-tab' : '#barber-reg-tab';
        }
        const subTabTrigger = document.querySelector(subTabSelector);
        if (subTabTrigger) {
            const subTab = bootstrap.Tab.getOrCreateInstance(subTabTrigger);
            subTab.show();
        }
    }, 100);
}

function closeAuthModal() {
    const modalEl = document.getElementById('authModal');
    if (!modalEl) return;
    const authModal = bootstrap.Modal.getInstance(modalEl);
    if (authModal) authModal.hide();
}

<<<<<<< HEAD
=======
function handleFormSubmit(event, formTitle) {
    event.preventDefault();
    const formElement = event.target;
    const formData = new FormData(formElement);
    const dataObj = Object.fromEntries(formData.entries());
    console.group(`🚀 [BACKEND SİMÜLASYONU] ${formTitle} Formu Gönderildi`);
    console.log('Form ID:', formElement.id);
    console.log('Rol:', dataObj.role || 'Bilinmiyor');
    console.log('Form Verileri (FormData Object):', dataObj);
    console.log('Backend API JSON Gönderim Formatı:', JSON.stringify(dataObj, null, 2));
    console.groupEnd();


    closeAuthModal();

    const roleText = dataObj.role === 'barber' ? 'Berber (İşletme)' : 'Müşteri';
    const actionText = formTitle.includes('Register') ? 'kaydı simüle edildi' : 'girişi başarılı';
    
    showToast(`✅ ${roleText} ${actionText}! Veriler veritabanına gönderilmeye hazır.`, 'success');

    openDataPreviewModal(formTitle, dataObj);

    formElement.reset();
}

function handleHeroSearch(event) {
    event.preventDefault();
    const formElement = event.target;
    const formData = new FormData(formElement);
    const searchObj = Object.fromEntries(formData.entries());

    console.group('🔍 [BERBER ARAMA SORGUSU]');
    console.log('Filtreleme Kriterleri:', searchObj);
    console.groupEnd();

    const cityText = searchObj.city ? searchObj.city.toUpperCase() : 'Tüm Şehirler';
    const districtText = searchObj.district ? searchObj.district.toUpperCase() : 'Tüm İlçeler';
    const queryText = searchObj.search_query ? `"${searchObj.search_query}"` : 'Tüm Salonlar';

    showToast(`🔎 Berber Araması Yapıldı: ${cityText} / ${districtText} - ${queryText}`, 'info');
}

>>>>>>> c64d98ffda22c826f28598755e13fe44fd295fed
function triggerAppointmentSimulation(barberName) {
    openAuthModal('login', 'customer');
<<<<<<< HEAD
=======
    showToast(`✂️ ${barberName} için randevu alabilmek için lütfen önce Müşteri Girişi yapın.`, 'warning');
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = 'toast-box shadow-lg border rounded-3 p-3 bg-white mb-2 animate-toast-in';

    let icon = '<i class="fa-solid fa-circle-check fs-5 me-2" style="color: var(--accent);"></i>';

    if (type === 'info') {
        icon = '<i class="fa-solid fa-circle-info fs-5 me-2" style="color: var(--accent);"></i>';
    } else if (type === 'warning') {
        icon = '<i class="fa-solid fa-triangle-exclamation fs-5 me-2" style="color: var(--accent);"></i>';
    }

    toast.innerHTML = `
        <div class="d-flex align-items-start">
            <div class="mt-1">${icon}</div>
            <div class="flex-grow-1 ms-2">
                <h6 class="fw-extrabold text-uppercase text-dark mb-0" style="font-size: 11px;">BiTraş Bildirimi</h6>
                <p class="text-muted text-xs mb-0 mt-1">${message}</p>
            </div>
            <button type="button" class="btn-close btn-sm ms-2" onclick="this.closest('.toast-box').remove()"></button>
        </div>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        if (toast && toast.parentElement) {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(50px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }
    }, 4500);
}

function openDataPreviewModal(title, dataObj) {
    const previewContent = document.getElementById('dataPreviewContent');
    const previewModalEl = document.getElementById('dataPreviewModal');

    if (previewContent && previewModalEl) {
        previewContent.textContent = JSON.stringify(dataObj, null, 4);
        const modal = bootstrap.Modal.getOrCreateInstance(previewModalEl);
        modal.show();
    }
}

function closeDataPreviewModal() {
    const previewModalEl = document.getElementById('dataPreviewModal');
    if (previewModalEl) {
        const modal = bootstrap.Modal.getInstance(previewModalEl);
        if (modal) modal.hide();
    }
>>>>>>> c64d98ffda22c826f28598755e13fe44fd295fed
}
