<?php
/**
 * BiTıraş.com — Modern Çerez Onay ve Yönetim Sistemi
 * KVKK ve GDPR uyumlu, kategorize edilmiş çerez tercihi paneli ve modalı.
 */
?>
<!-- ÇEREZ ONAY BANNERI -->
<div id="bitirasCookieBanner" class="bitiras-cookie-banner" style="display:none;" role="dialog" aria-live="polite" aria-label="Çerez Kullanımı">
    <div class="bitiras-cookie-container">
        <div class="bitiras-cookie-content">
            <div class="bitiras-cookie-header">
                <i class="fa-solid fa-cookie-bite bitiras-cookie-icon"></i>
                <h4 class="bitiras-cookie-title">Çerez (Cookie) Tercihleriniz</h4>
            </div>
            <p class="bitiras-cookie-text">
                BiTıraş olarak, sitemizin güvenli ve sorunsuz çalışmasını sağlamak, randevu hizmetlerinizi kolaylaştırmak ve deneyiminizi geliştirmek için çerezler kullanıyoruz. Zorunlu çerezler sitenin çalışması için zorunludur; analitik ve pazarlama çerezleri ise yalnızca izninizle kullanılır. Detaylı bilgi için <a href="/cerez-politikasi.php" target="_blank" class="bitiras-cookie-link">Çerez Politikası</a> ve <a href="/kvkk.php" target="_blank" class="bitiras-cookie-link">KVKK Aydınlatma Metni</a>'ni inceleyebilirsiniz.
            </p>
        </div>
        <div class="bitiras-cookie-actions">
            <button type="button" class="btn btn-warning fw-bold btn-sm px-3 py-2 text-dark" onclick="BitirasCookie.acceptAll()">
                <i class="fa-solid fa-check me-1"></i> Tümünü Kabul Et
            </button>
            <button type="button" class="btn btn-outline-light btn-sm px-3 py-2" onclick="BitirasCookie.rejectAll()">
                <i class="fa-solid fa-xmark me-1"></i> Tümünü Reddet
            </button>
            <button type="button" class="btn btn-link text-white-50 text-decoration-none btn-sm px-2 py-2" onclick="BitirasCookie.openSettings()">
                <i class="fa-solid fa-sliders me-1"></i> Tercihleri Yönet
            </button>
        </div>
    </div>
</div>

<!-- ÇEREZ TERCİHLERİ YÖNETİM MODALI -->
<div class="modal fade" id="cookieSettingsModal" tabindex="-1" style="display:none;" aria-labelledby="cookieSettingsTitle" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
            <div class="modal-header border-bottom py-3 px-4" style="background:#0F172A;color:#fff;">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:36px;height:36px;border-radius:10px;background:rgba(217,119,6,0.2);color:#F59E0B;display:flex;align-items:center;justify-content:center;font-size:18px;">
                        <i class="fa-solid fa-cookie-bite"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fs-5 fw-extrabold mb-0 text-white" id="cookieSettingsTitle">Çerez Tercihlerini Yönet</h5>
                        <small class="text-white-50" style="font-size:12px;">Hangi çerez kategorilerine izin vermek istediğinizi belirleyin.</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body p-4" style="background:#F8FAFC;">
                <p class="text-muted text-sm mb-4">
                    Çerezler, web sitemizin düzgün çalışmasını sağlamak, site trafiğimizi analiz etmek ve size özelleştirilmiş bir randevu deneyimi sunmak için cihazınıza yerleştirilen küçük veri dosyalarıdır. Tercihlerinizi dilediğiniz zaman değiştirebilirsiniz.
                </p>

                <div class="d-flex flex-column gap-3">
                    <!-- Kategori 1: Zorunlu Çerezler -->
                    <div class="card border rounded-3 p-3 bg-white shadow-sm">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-shield-halved text-success"></i>
                                <span class="fw-bold text-dark fs-6">Zorunlu Çerezler</span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:11px;">Her Zaman Aktif</span>
                            </div>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" checked disabled style="cursor:not-allowed;">
                            </div>
                        </div>
                        <p class="text-muted mb-0" style="font-size:13px;line-height:1.45;">
                            Web sitesinin temel işlevlerinin (güvenli oturum açma, form CSRF koruması, randevu adımları ve çerez tercihinizin kaydedilmesi) çalışması için zorunludur. Bu çerezler kapatılamaz.
                        </p>
                    </div>

                    <!-- Kategori 2: İşlevsel Çerezler -->
                    <div class="card border rounded-3 p-3 bg-white shadow-sm">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-sliders text-primary"></i>
                                <span class="fw-bold text-dark fs-6">İşlevsel ve Tercih Çerezleri</span>
                            </div>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" id="cookieOptFunctional" style="cursor:pointer;width:40px;height:22px;">
                            </div>
                        </div>
                        <p class="text-muted mb-0" style="font-size:13px;line-height:1.45;">
                            İsteğe bağlı harita servislerini (Google Maps, OpenStreetMap) ve filtreleme tercihlerinizi etkinleştirir. İzin kapalıyken harici harita sağlayıcıları yüklenmez ve konum tercihleri oturum sonunda sıfırlanır. Tercihi değiştirdiğinizde sayfa yenilenir.
                        </p>
                    </div>

                    <!-- Kategori 3: Analitik Çerezler -->
                    <div class="card border rounded-3 p-3 bg-white shadow-sm">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-chart-line text-info"></i>
                                <span class="fw-bold text-dark fs-6">Analitik ve Performans Çerezleri</span>
                            </div>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" id="cookieOptAnalytics" style="cursor:pointer;width:40px;height:22px;">
                            </div>
                        </div>
                        <p class="text-muted mb-0" style="font-size:13px;line-height:1.45;">
                            Hangi sayfaların daha çok ziyaret edildiğini, sayfa yüklenme hızlarını ve teknik hataları anonim olarak analiz etmemize ve platformumuzu iyileştirmemize yardımcı olur.
                        </p>
                    </div>

                    <!-- Kategori 4: Pazarlama Çerezleri -->
                    <div class="card border rounded-3 p-3 bg-white shadow-sm">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-bullhorn text-warning"></i>
                                <span class="fw-bold text-dark fs-6">Pazarlama ve Duyuru Çerezleri</span>
                            </div>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" id="cookieOptMarketing" style="cursor:pointer;width:40px;height:22px;">
                            </div>
                        </div>
                        <p class="text-muted mb-0" style="font-size:13px;line-height:1.45;">
                            Size ve ilgi alanlarınıza özel kampanya, indirim ve yeni eklenen VIP berber duyurularını sunmak için kullanılır.
                        </p>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top bg-white px-4 py-3 d-flex flex-wrap justify-content-between gap-2">
                <a href="/cerez-politikasi.php" target="_blank" class="text-muted text-xs text-decoration-none">
                    <i class="fa-solid fa-file-contract me-1"></i> Detaylı Çerez Politikası
                </a>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-dark btn-sm px-3" onclick="BitirasCookie.savePreferences()">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Tercihleri Kaydet
                    </button>
                    <button type="button" class="btn btn-warning btn-sm px-3 fw-bold text-dark" onclick="BitirasCookie.acceptAll()">
                        <i class="fa-solid fa-check-double me-1"></i> Tümünü Kabul Et
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
#cookieSettingsModal:not(.show) {
    display: none !important;
}

.bitiras-cookie-banner {
    position: fixed;
    bottom: 20px;
    left: 20px;
    right: 20px;
    max-width: 1100px;
    margin: 0 auto;
    z-index: 1050;
    background: #0F172A;
    color: #F8FAFC;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 18px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.45);
    backdrop-filter: blur(8px);
    animation: bitirasSlideUp 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes bitirasSlideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.bitiras-cookie-container {
    padding: 22px 26px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
}

.bitiras-cookie-content {
    flex: 1;
    min-width: 0;
}

.bitiras-cookie-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 6px;
}

.bitiras-cookie-icon {
    color: #F59E0B;
    font-size: 20px;
}

.bitiras-cookie-title {
    margin: 0;
    font-size: 16px;
    font-weight: 800;
    color: #FFFFFF;
    letter-spacing: -0.2px;
}

.bitiras-cookie-text {
    margin: 0;
    font-size: 13px;
    line-height: 1.5;
    color: #94A3B8;
}

.bitiras-cookie-link {
    color: #F59E0B;
    text-decoration: underline;
    text-underline-offset: 2px;
}

.bitiras-cookie-link:hover {
    color: #FCD34D;
}

.bitiras-cookie-actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    flex-shrink: 0;
}

@media (max-width: 860px) {
    .bitiras-cookie-banner {
        bottom: 12px;
        left: 12px;
        right: 12px;
    }
    .bitiras-cookie-container {
        flex-direction: column;
        align-items: flex-start;
        padding: 18px;
        gap: 16px;
    }
    .bitiras-cookie-actions {
        width: 100%;
        justify-content: flex-start;
    }
}
</style>

<script>
var BitirasCookie = (function() {
    var COOKIE_NAME = 'bitiras_cookie_consent';
    var COOKIE_DAYS = 180;

    function getCookie(name) {
        var match = document.cookie.match(new RegExp('(^|;\\s*)(' + name + ')=([^;]*)'));
        try { return match ? decodeURIComponent(match[3]) : null; } catch(e) { return null; }
    }

    function setCookie(name, val, days) {
        var d = new Date();
        d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
        var secure = location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = name + '=' + encodeURIComponent(val) + '; expires=' + d.toUTCString() + '; path=/; SameSite=Lax' + secure;
    }

    function getConsent() {
        var raw = getCookie(COOKIE_NAME);
        if (!raw) return null;
        try {
            var c = JSON.parse(raw);
            var updated = c && Date.parse(c.updated_at);
            if (!c || c.version !== 1 || !Number.isFinite(updated) || updated > Date.now() + 60000 || updated < Date.now() - COOKIE_DAYS * 86400000) return null;
            return { necessary: true, functional: c.functional === true, analytics: c.analytics === true, marketing: c.marketing === true, updated_at: c.updated_at, version: 1 };
        } catch(e) {
            return null;
        }
    }

    function applyConsent(consent) {
        var previous = getConsent();
        setCookie(COOKIE_NAME, JSON.stringify(consent), COOKIE_DAYS);
        hideBanner();

        // Dispatch global event for external scripts/analytics
        try {
            window.dispatchEvent(new CustomEvent('bitirasCookieConsentChanged', { detail: consent }));
        } catch(e) {}

        if (!!(previous && previous.functional) !== consent.functional) {
            window.location.reload();
            return;
        }
        // Enable or disable scripts based on consent
        if (!consent.analytics) {
            // Disable analytics cookies/scripts if any
        }
        if (!consent.marketing) {
            // Disable marketing cookies/scripts if any
        }
    }

    function showBanner() {
        var el = document.getElementById('bitirasCookieBanner');
        if (el) el.style.display = 'block';
    }

    function hideBanner() {
        var el = document.getElementById('bitirasCookieBanner');
        if (el) el.style.display = 'none';
    }

    function init() {
        var consent = getConsent();
        if (!consent) {
            setTimeout(showBanner, 400);
        } else {
            // Sync preferences modal switches with current saved state
            syncModalInputs(consent);
        }
    }

    function syncModalInputs(consent) {
        var f = document.getElementById('cookieOptFunctional');
        var a = document.getElementById('cookieOptAnalytics');
        var m = document.getElementById('cookieOptMarketing');
        if (f) f.checked = !!(consent && consent.functional);
        if (a) a.checked = !!(consent && consent.analytics);
        if (m) m.checked = !!(consent && consent.marketing);
    }

    function acceptAll() {
        var consent = {
            necessary: true,
            functional: true,
            analytics: true,
            marketing: true,
            updated_at: new Date().toISOString(),
            version: 1
        };
        syncModalInputs(consent);
        closeSettingsModal();
        applyConsent(consent);
    }

    function rejectAll() {
        var consent = {
            necessary: true,
            functional: false,
            analytics: false,
            marketing: false,
            updated_at: new Date().toISOString(),
            version: 1
        };
        syncModalInputs(consent);
        closeSettingsModal();
        applyConsent(consent);
    }

    function savePreferences() {
        var f = document.getElementById('cookieOptFunctional');
        var a = document.getElementById('cookieOptAnalytics');
        var m = document.getElementById('cookieOptMarketing');

        var consent = {
            necessary: true,
            functional: f ? f.checked : false,
            analytics: a ? a.checked : false,
            marketing: m ? m.checked : false,
            updated_at: new Date().toISOString(),
            version: 1
        };
        closeSettingsModal();
        applyConsent(consent);
    }

    function openSettings() {
        var consent = getConsent() || { necessary: true, functional: false, analytics: false, marketing: false };
        syncModalInputs(consent);

        var modalEl = document.getElementById('cookieSettingsModal');
        if (modalEl && window.bootstrap && bootstrap.Modal) {
            var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        } else if (modalEl) {
            modalEl.classList.add('show');
            modalEl.style.display = 'block';
        }
    }

    function closeSettingsModal() {
        var modalEl = document.getElementById('cookieSettingsModal');
        if (modalEl && window.bootstrap && bootstrap.Modal) {
            var modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        } else if (modalEl) {
            modalEl.classList.remove('show');
            modalEl.style.display = 'none';
        }
    }

    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    return {
        acceptAll: acceptAll,
        rejectAll: rejectAll,
        savePreferences: savePreferences,
        openSettings: openSettings,
        getConsent: getConsent,
        hasConsent: function(category) {
            var c = getConsent();
            return !!(c && c[category]);
        }
    };
})();

// Global alias for footers and legal pages
window.openCookieSettings = function() {
    BitirasCookie.openSettings();
};
</script>

