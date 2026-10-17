const cityDistrictsMap = {
    istanbul: ['Kadıköy', 'Beşiktaş', 'Şişli', 'Üsküdar', 'Bakırköy', 'Fatih', 'Beyoğlu', 'Maltepe', 'Ataşehir', 'Pendik'],
    ankara: ['Çankaya', 'Yenimahalle', 'Keçiören', 'Mamak', 'Etimesgut', 'Gölbaşı'],
    izmir: ['Konak', 'Karşıyaka', 'Bornova', 'Alsancak', 'Buca', 'Çeşme', 'Gaziemir'],
    bursa: ['Nilüfer', 'Osmangazi', 'Yıldırım', 'Mudanya'],
    antalya: ['Muratpaşa', 'Konyaaltı', 'Alanya', 'Kepez', 'Manavgat']
};

document.addEventListener('DOMContentLoaded', () => {
    initSearchableFilters();
    updateDistrictOptions('', 'barberDistrict');
    initHeroSlider();
});

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

function updateDistrictOptions(selectedCity, targetSelectId, selectedDistrict = '') {
    const districtSelect = document.getElementById(targetSelectId);
    if (!districtSelect) return;

    districtSelect.innerHTML = '';
    const isBusinessSearch = targetSelectId === 'searchDistrict';
    const districtsMap = isBusinessSearch && window.availableBusinessLocations
        ? window.availableBusinessLocations
        : cityDistrictsMap;

    if (!selectedCity || !districtsMap[selectedCity]) {
        const defaultOpt = document.createElement('option');
        defaultOpt.value = '';
        defaultOpt.textContent = isBusinessSearch ? 'Tüm İlçeler' : 'Önce Şehir Seçin';
        districtSelect.appendChild(defaultOpt);
        return;
    }

    const defaultOpt = document.createElement('option');
    defaultOpt.value = '';
    defaultOpt.textContent = 'Tüm İlçeler';
    districtSelect.appendChild(defaultOpt);

    const districts = districtsMap[selectedCity];
    districts.forEach(district => {
        const opt = document.createElement('option');
        opt.value = isBusinessSearch ? district : district.toLowerCase().replace(/\s+/g, '-');
        opt.textContent = district;
        opt.selected = district === selectedDistrict;
        districtSelect.appendChild(opt);
    });
}

function initSearchableFilters() {
    const data = window.businessSearchOptions || {};
    const locations = data.locations || {};
    const normalizeLocationName = value => value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLocaleLowerCase('tr-TR')
        .replace(/ı/g, 'i');
    const inputs = [
        {
            input: document.getElementById('searchCity'),
            options: () => data.cities || [],
            placeholder: 'Tüm şehirler'
        },
        {
            input: document.getElementById('searchDistrict'),
            options: () => {
                const city = Object.keys(locations).find(
                    name => normalizeLocationName(name) === normalizeLocationName(document.getElementById('searchCity')?.value || '')
                );
                return city ? locations[city] : [];
            },
            placeholder: 'Tüm ilçeler'
        },
        {
            input: document.getElementById('hizmetler'),
            options: () => data.services || [],
            placeholder: 'Tüm hizmetler'
        }
    ];

    inputs.forEach(({ input, options, placeholder }) => {
        if (!input) return;
        const menu = document.getElementById(input.getAttribute('aria-controls'));
        if (!menu) return;

        let activeIndex = -1;
        const close = () => {
            menu.hidden = true;
            input.setAttribute('aria-expanded', 'false');
            activeIndex = -1;
        };
        const render = (query = '') => {
            const normalizedQuery = query.trim().toLocaleLowerCase('tr-TR');
            const uniqueOptions = new Map();
            options().forEach(value => {
                const key = normalizeLocationName(value);
                if (!uniqueOptions.has(key)) uniqueOptions.set(key, value);
            });
            const matches = [...uniqueOptions.values()]
                .filter(value => value.toLocaleLowerCase('tr-TR').startsWith(normalizedQuery))
                .sort((left, right) => left.localeCompare(right, 'tr-TR'))
                .slice(0, 100);
            menu.replaceChildren();
            if (!matches.length) {
                close();
                return;
            }
            matches.forEach(value => {
                const option = document.createElement('button');
                option.type = 'button';
                option.className = 'hero-search-option';
                option.setAttribute('role', 'option');
                option.textContent = value;
                option.addEventListener('mousedown', event => event.preventDefault());
                option.addEventListener('click', () => {
                    input.value = value;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                    close();
                    if (input.id === 'searchCity') {
                        const districtInput = document.getElementById('searchDistrict');
                        if (districtInput && districtInput.value) {
                            const cityKey = Object.keys(locations).find(
                                name => normalizeLocationName(name) === normalizeLocationName(value)
                            );
                            if (!cityKey || !locations[cityKey].includes(districtInput.value)) {
                                districtInput.value = '';
                            }
                        }
                    }
                });
                menu.appendChild(option);
            });
            menu.hidden = false;
            input.setAttribute('aria-expanded', 'true');
            activeIndex = -1;
        };

        input.placeholder = placeholder;
        input.addEventListener('focus', () => render(input.value));
        input.addEventListener('input', () => render(input.value));
        input.addEventListener('keydown', event => {
            const optionsInMenu = [...menu.querySelectorAll('[role="option"]')];
            if (event.key === 'ArrowDown' && !menu.hidden) {
                event.preventDefault();
                activeIndex = (activeIndex + 1) % optionsInMenu.length;
            } else if (event.key === 'ArrowUp' && !menu.hidden) {
                event.preventDefault();
                activeIndex = (activeIndex - 1 + optionsInMenu.length) % optionsInMenu.length;
            } else if (event.key === 'Enter' && !menu.hidden && activeIndex >= 0) {
                event.preventDefault();
                optionsInMenu[activeIndex].click();
            } else if (event.key === 'Escape') {
                close();
            } else {
                return;
            }
            optionsInMenu.forEach((option, index) => option.classList.toggle('active', index === activeIndex));
            if (activeIndex >= 0) optionsInMenu[activeIndex].scrollIntoView({ block: 'nearest' });
        });
        input.addEventListener('blur', () => setTimeout(close, 120));
    });

    document.addEventListener('pointerdown', event => {
        if (!event.target.closest('.hero-search-autocomplete')) {
            document.querySelectorAll('.hero-search-options:not([hidden])').forEach(menu => {
                menu.hidden = true;
                document.getElementById(menu.id === 'searchCityOptions' ? 'searchCity' : menu.id === 'searchDistrictOptions' ? 'searchDistrict' : 'hizmetler')
                    ?.setAttribute('aria-expanded', 'false');
            });
        }
    });
}

function updateHizmetOptions(selectedHizmet, targetSelectId) {
    const hizmetSelect = document.getElementById(targetSelectId);
    if (!hizmetSelect) return;

    hizmetSelect.innerHTML = '';

    if (!selectedHizmet || !hizmetOptions[selectedHizmet]) {
        const defaultOpt = document.createElement('option');
        defaultOpt.value = '';
        defaultOpt.textContent = 'Önce Hizmet Seçin';
        hizmetSelect.appendChild(defaultOpt);
        return;
    }

    const defaultOpt = document.createElement('option');
    defaultOpt.value = '';
    defaultOpt.textContent = 'Tüm Hizmetler';
    hizmetSelect.appendChild(defaultOpt);

    const hizmetler = hizmetOptions[selectedHizmet];
    hizmetler.forEach(hizmet => {
        const opt = document.createElement('option');
        opt.value = hizmet.toLowerCase().replace(/\s+/g, '-');
        opt.textContent = hizmet;
        hizmetSelect.appendChild(opt);
    });
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

function triggerAppointmentSimulation(barberName) {
    openAuthModal('login', 'customer');
}
