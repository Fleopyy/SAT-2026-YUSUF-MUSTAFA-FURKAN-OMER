<?php
require_once __DIR__ . '/_common.php';
$currentBusinessPage = 'profile';
$pageTitle = 'İşletme Profilim';
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    businessRequirePost();
    $fields = [
        'isletme_adi'    => trim((string)($_POST['isletme_adi']    ?? '')),
        'isletme_sahibi' => trim((string)($_POST['isletme_sahibi'] ?? '')),
        'telefon'        => trim((string)($_POST['telefon']        ?? '')),
        'eposta'         => trim((string)($_POST['eposta']         ?? '')),
        'sehir'          => trim((string)($_POST['sehir']          ?? '')),
        'ilce'           => trim((string)($_POST['ilce']           ?? '')),
        'tam_adres'      => trim((string)($_POST['tam_adres']      ?? '')),
        'aciklama'       => trim((string)($_POST['aciklama']       ?? '')),
    ];

    foreach (['isletme_adi', 'isletme_sahibi', 'telefon', 'sehir', 'ilce'] as $required) {
        if ($fields[$required] === '') {
            $errors[] = 'Zorunlu alanları doldurun.';
            break;
        }
    }
    if (!filter_var($fields['eposta'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçerli bir e-posta adresi girin.';
    }
    if (mb_strlen($fields['aciklama'], 'UTF-8') > 2000) {
        $errors[] = 'İşletme tanıtımı en fazla 2000 karakter olabilir.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM isletmeler WHERE id <> ? AND (telefon = ? OR eposta = ?)');
        $stmt->execute([$businessId, $fields['telefon'], $fields['eposta']]);
        if ((int)$stmt->fetchColumn() > 0) {
            $errors[] = 'Bu telefon veya e-posta başka bir işletme tarafından kullanılıyor.';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare('UPDATE isletmeler SET isletme_adi = ?, isletme_sahibi = ?, telefon = ?, eposta = ?, sehir = ?, ilce = ?, tam_adres = ?, aciklama = ? WHERE id = ?');
        $stmt->execute([
            $fields['isletme_adi'], $fields['isletme_sahibi'], $fields['telefon'], $fields['eposta'],
            $fields['sehir'], $fields['ilce'], $fields['tam_adres'], $fields['aciklama'], $businessId,
        ]);
        businessFlash('success', 'İşletme profiliniz kaydedildi.');
        businessRedirect('profil.php');
    }
    $business = array_merge($business, $fields);
}

include __DIR__ . '/_header.php';
?>
<section class="admin-panel">
    <div class="panel-header">
        <h2><i class="fa-solid fa-store"></i><span>İşletme Bilgileri</span></h2>
    </div>
    <div class="panel-body padded">
        <?php foreach ($errors as $error): ?>
            <div class="business-notice error"><?= businessEscape($error) ?></div>
        <?php endforeach; ?>

        <div style="display:grid;grid-template-columns:1fr 340px;gap:32px;align-items:start;">

            <!-- ── FORM ── -->
            <form method="POST" class="admin-form" id="businessProfileForm">
                <input type="hidden" name="csrf_token" value="<?= businessEscape(generateCsrfToken()) ?>">
                <div class="business-form-grid">
                    <?php foreach ([
                        'isletme_adi'    => ['İşletme Adı',  true,  'Örn: Stil Erkek Kuaförü'],
                        'isletme_sahibi' => ['Yetkili Kişi', true,  'Örn: Mehmet Usta'],
                        'telefon'        => ['Telefon',       true,  '05XX XXX XX XX'],
                        'eposta'         => ['E-Posta',       true,  'isletme@mail.com'],
                        'sehir'          => ['Şehir',         true,  'Örn: İstanbul'],
                        'ilce'           => ['İlçe',          true,  'Örn: Beşiktaş'],
                    ] as $name => [$label, $required, $placeholder]): ?>
                        <div class="form-group">
                            <label for="<?= businessEscape($name) ?>"><?= businessEscape($label) ?></label>
                            <input id="<?= businessEscape($name) ?>"
                                   name="<?= businessEscape($name) ?>"
                                   value="<?= businessEscape($business[$name] ?? '') ?>"
                                   placeholder="<?= businessEscape($placeholder) ?>"
                                   <?= $required ? 'required' : '' ?>>
                        </div>
                    <?php endforeach; ?>

                    <div class="form-group" style="grid-column:1/-1">
                        <label for="tam_adres">Tam Adres</label>
                        <textarea id="tam_adres" name="tam_adres" rows="2"
                                  placeholder="Cadde/Sokak, No, Kat"><?= businessEscape($business['tam_adres'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group" style="grid-column:1/-1">
                        <label for="aciklama">İşletme Tanıtımı</label>
                        <textarea id="aciklama" name="aciklama" maxlength="2000" rows="6"
                                  placeholder="İşletmenizi tanıtın: sunduğunuz hizmetler, deneyiminiz, öne çıkan özellikleriniz..."><?= businessEscape($business['aciklama'] ?? '') ?></textarea>
                        <small id="descriptionCount" style="color:#64748B;"></small>
                    </div>
                </div>
                <div class="business-actions" style="margin-top:20px;">
                    <button class="btn-admin primary" type="submit">
                        <i class="fa-solid fa-floppy-disk"></i> Değişiklikleri Kaydet
                    </button>
                    <a class="btn-admin outline" href="/isletme/fotograflar.php">
                        <i class="fa-solid fa-images"></i> Fotoğrafları Yönet
                    </a>
                </div>
            </form>

            <!-- ── CANLI ÖNİZLEME ── -->
            <style>
                .preview-editable {
                    cursor: pointer;
                    border-radius: 8px;
                    transition: background .15s, outline .15s;
                    outline: 2px solid transparent;
                    position: relative;
                }
                .preview-editable:hover {
                    background: #F8FAFC;
                    outline: 2px dashed #CBD5E1;
                }
                .preview-editable:hover::after {
                    content: '\f303';
                    font-family: 'Font Awesome 6 Free';
                    font-weight: 900;
                    font-size: 11px;
                    color: #94A3B8;
                    position: absolute;
                    top: 6px;
                    right: 8px;
                }
                .preview-editable.focused {
                    outline: 2px solid var(--accent, #F97316);
                    background: #FFF7ED;
                }
            </style>
            <div style="position:sticky;top:24px;">
                <p style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;
                          color:#94A3B8;margin:0 0 10px;">
                    <i class="fa-solid fa-eye" style="margin-right:5px;"></i>Müşteri Görünümü — Canlı Önizleme
                </p>
                <div style="border:1px solid #E2E8F0;border-radius:14px;padding:20px 22px;
                            background:#fff;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                    <h2 style="font-size:15px;font-weight:700;color:#1E293B;margin:0 0 10px;">
                        İşletme hakkında
                    </h2>

                    <!-- Tanıtım — tıklayınca aciklama alanına odaklan -->
                    <p id="previewDesc"
                       class="preview-editable"
                       title="Düzenlemek için tıkla"
                       data-target="aciklama"
                       style="font-size:13px;white-space:pre-wrap;margin:0 0 14px;
                              min-height:32px;line-height:1.6;color:#374151;padding:6px 28px 6px 6px;">
                        <?= businessEscape($business['aciklama'] ?? '') ?>
                    </p>

                    <div style="border-top:1px solid #F1F5F9;padding-top:12px;font-size:13px;color:#374151;">

                        <!-- Adres — tıklayınca tam_adres alanına odaklan -->
                        <p class="preview-editable" data-target="tam_adres"
                           title="Düzenlemek için tıkla"
                           style="margin:0 0 6px;padding:5px 28px 5px 6px;">
                            <strong>Adres:</strong>
                            <span id="previewAddrText"><?= businessEscape(trim(
                                implode(', ', array_filter([
                                    $business['tam_adres'] ?? '',
                                    $business['ilce']      ?? '',
                                    $business['sehir']     ?? '',
                                ]))
                            )) ?></span>
                        </p>

                        <!-- Telefon — tıklayınca telefon alanına odaklan -->
                        <p class="preview-editable" data-target="telefon"
                           title="Düzenlemek için tıkla"
                           style="margin:0;padding:5px 28px 5px 6px;">
                            <strong>Telefon:</strong>
                            <span id="previewPhoneText" style="color:#F97316;">
                                <?= businessEscape($business['telefon'] ?? '') ?>
                            </span>
                        </p>

                    </div>
                </div>
                <p style="font-size:11px;color:#94A3B8;margin:10px 0 0;text-align:center;">
                    <i class="fa-solid fa-pencil" style="margin-right:4px;"></i>
                    Düzenlemek için üzerine tıklayın
                </p>
            </div>

        </div><!-- /grid -->
    </div>
</section>

<script>
(function () {
    var fDesc  = document.getElementById('aciklama');
    var fAddr  = document.getElementById('tam_adres');
    var fIlce  = document.getElementById('ilce');
    var fSehir = document.getElementById('sehir');
    var fPhone = document.getElementById('telefon');

    var previewDesc  = document.getElementById('previewDesc');
    var previewAddr  = document.getElementById('previewAddrText');
    var previewPhone = document.getElementById('previewPhoneText');
    var counter      = document.getElementById('descriptionCount');

    function buildAddr() {
        return [
            fAddr  ? fAddr.value.trim()  : '',
            fIlce  ? fIlce.value.trim()  : '',
            fSehir ? fSehir.value.trim() : ''
        ].filter(Boolean).join(', ');
    }

    function syncDesc() {
        var val = fDesc.value.trim();
        previewDesc.textContent  = val || 'İşletme tanıtımı henüz eklenmemiş.';
        previewDesc.style.color  = val ? '#374151' : '#CBD5E1';
        previewDesc.style.fontStyle = val ? 'normal' : 'italic';

        var rem = 2000 - fDesc.value.length;
        counter.textContent  = rem + ' karakter kaldı';
        counter.style.color  = rem < 100 ? '#EF4444' : '#64748B';
    }

    function syncAddr()  { previewAddr.textContent  = buildAddr() || '—'; }
    function syncPhone() { previewPhone.textContent = fPhone ? fPhone.value.trim() : ''; }

    fDesc.addEventListener('input', syncDesc);
    [fAddr, fIlce, fSehir].forEach(function (el) { if (el) el.addEventListener('input', syncAddr); });
    if (fPhone) fPhone.addEventListener('input', syncPhone);

    syncDesc();
    syncAddr();
    syncPhone();

    /* ── Önizleme kartı tıklama → form alanına odaklan ── */
    document.querySelectorAll('.preview-editable').forEach(function (el) {
        el.addEventListener('click', function () {
            var targetId = el.getAttribute('data-target');
            var target   = document.getElementById(targetId);
            if (!target) return;

            /* Formdaki alana scroll et ve odaklan */
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            target.focus();

            /* Geçici vurgu efekti */
            el.classList.add('focused');
            target.style.transition = 'box-shadow .2s';
            target.style.boxShadow  = '0 0 0 3px rgba(249,115,22,.3)';
            setTimeout(function () {
                el.classList.remove('focused');
                target.style.boxShadow = '';
            }, 1800);
        });
    });
})();
</script>
<?php include __DIR__ . '/_footer.php'; ?>
