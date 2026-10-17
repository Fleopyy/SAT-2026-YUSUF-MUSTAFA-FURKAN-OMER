<?php
   
                                                     
   

require_once __DIR__ . '/../includes/auth.php';

$admin = requirePermission('content.manage');
$currentPage = 'content';
$pageTitle = 'Site İçeriği & Ayarlar';
$pageSubtitle = 'Ana sayfa metinlerini, duyuru bandını, iletişim ve SMTP e-posta ayarlarını düzenleyin';

                      
$stmt = $pdo->query("SELECT anahtar, deger, aciklama, tur FROM site_ayarlari");
$settingsRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$settings = [];
foreach ($settingsRows as $row) {
    $settings[$row['anahtar']] = $row['deger'];
}

$csrfToken = generateCsrfToken();
include __DIR__ . '/../includes/header.php';
?>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;">
                          
    <div>
        <form method="POST" action="/admin/actions/settings_action.php">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="save_settings">

                                             
            <div class="admin-panel" style="margin-bottom:24px;">
                <div class="panel-header">
                    <h2>
                        <i class="fa-solid fa-globe"></i>
                        <span>Genel & SEO Ayarları</span>
                    </h2>
                </div>
                <div class="panel-body padded">
                    <div style="margin-bottom:16px;">
                        <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">Site Başlığı (Title)</label>
                        <input type="text" name="site_baslik" value="<?= e($settings['site_baslik'] ?? '') ?>" class="form-control" style="width:100%;box-sizing:border-box;">
                        <small style="color:var(--admin-text-muted);font-size:11px;">Tarayıcı sekmesinde ve Google arama sonuçlarında görünen ana başlık.</small>
                    </div>

                    <div>
                        <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">Meta Açıklaması (Description)</label>
                        <textarea name="site_aciklama" rows="2" class="form-control" style="width:100%;box-sizing:border-box;"><?= e($settings['site_aciklama'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

                                               
            <div class="admin-panel" style="margin-bottom:24px;">
                <div class="panel-header">
                    <h2>
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                        <span>Ana Sayfa & Hero Bölümü</span>
                    </h2>
                </div>
                <div class="panel-body padded">
                    <div style="margin-bottom:16px;">
                        <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">Hero Ana Başlığı</label>
                        <input type="text" name="hero_baslik" value="<?= e($settings['hero_baslik'] ?? '') ?>" class="form-control" style="width:100%;box-sizing:border-box;">
                    </div>

                    <div style="margin-bottom:20px;">
                        <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">Hero Açıklama Metni</label>
                        <textarea name="hero_aciklama" rows="3" class="form-control" style="width:100%;box-sizing:border-box;"><?= e($settings['hero_aciklama'] ?? '') ?></textarea>
                    </div>

                    <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:16px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                            <label style="font-weight:700;font-size:13px;color:var(--admin-text);">Üst Duyuru Bandı</label>
                            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;font-weight:600;">
                                <input type="checkbox" name="duyuru_aktif" value="1" <?= ($settings['duyuru_aktif'] ?? '0') === '1' ? 'checked' : '' ?>>
                                <span>Duyuru Bandı Aktif</span>
                            </label>
                        </div>
                        <input type="text" name="duyuru_bandi" value="<?= e($settings['duyuru_bandi'] ?? '') ?>" class="form-control" placeholder="Duyuru metnini yazın..." style="width:100%;box-sizing:border-box;">
                    </div>
                </div>
            </div>

                                           
            <div class="admin-panel" style="margin-bottom:24px;">
                <div class="panel-header">
                    <h2>
                        <i class="fa-solid fa-address-book"></i>
                        <span>İletişim & Footer Metinleri</span>
                    </h2>
                </div>
                <div class="panel-body padded">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                        <div>
                            <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">İletişim Telefonu</label>
                            <input type="text" name="iletisim_telefon" value="<?= e($settings['iletisim_telefon'] ?? '') ?>" class="form-control" style="width:100%;box-sizing:border-box;">
                        </div>
                        <div>
                            <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">İletişim E-posta</label>
                            <input type="email" name="iletisim_eposta" value="<?= e($settings['iletisim_eposta'] ?? '') ?>" class="form-control" style="width:100%;box-sizing:border-box;">
                        </div>
                    </div>

                    <div style="margin-bottom:16px;">
                        <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">Footer Açıklama Metni</label>
                        <textarea name="footer_metin" rows="2" class="form-control" style="width:100%;box-sizing:border-box;"><?= e($settings['footer_metin'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">Footer Telif Metni</label>
                        <input type="text" name="footer_telif" value="<?= e($settings['footer_telif'] ?? '') ?>" class="form-control" style="width:100%;box-sizing:border-box;">
                    </div>
                </div>
            </div>

                                                        
            <div class="admin-panel" id="smtp" style="margin-bottom:24px;">
                <div class="panel-header">
                    <h2>
                        <i class="fa-solid fa-envelope"></i>
                        <span>SMTP / E-Posta Gönderim Yapılandırması</span>
                    </h2>
                </div>
                <div class="panel-body padded">
                    <p style="font-size:13px;color:var(--admin-text-muted);margin-bottom:18px;">
                        İşletme başvurusu onay/red bildirimleri ve randevu teyitleri bu SMTP sunucusu üzerinden otomatik olarak gönderilir.
                    </p>

                    <div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:16px;">
                        <div>
                            <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">SMTP Host</label>
                            <input type="text" name="smtp_host" value="<?= e($settings['smtp_host'] ?? '') ?>" placeholder="örn: smtp.gmail.com veya mail.bitiras.com" class="form-control" style="width:100%;box-sizing:border-box;">
                        </div>
                        <div>
                            <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">Port</label>
                            <input type="number" name="smtp_port" value="<?= e($settings['smtp_port'] ?? '587') ?>" class="form-control" style="width:100%;box-sizing:border-box;">
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                        <div>
                            <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">SMTP Kullanıcı Adı / E-posta</label>
                            <input type="text" name="smtp_kullanici" value="<?= e($settings['smtp_kullanici'] ?? '') ?>" class="form-control" style="width:100%;box-sizing:border-box;">
                        </div>
                        <div>
                            <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">SMTP Şifre</label>
                            <input type="password" name="smtp_sifre" placeholder="<?= !empty($settings['smtp_sifre']) ? '••••••••' : 'Şifre girin' ?>" class="form-control" style="width:100%;box-sizing:border-box;">
                            <small style="color:var(--admin-text-muted);font-size:11px;">Değiştirmek istemiyorsanız boş bırakın.</small>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
                        <div>
                            <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">Gönderen Adı</label>
                            <input type="text" name="smtp_gonderen_ad" value="<?= e($settings['smtp_gonderen_ad'] ?? 'BiTıraş.com') ?>" class="form-control" style="width:100%;box-sizing:border-box;">
                        </div>
                        <div>
                            <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">Gönderen E-posta</label>
                            <input type="email" name="smtp_gonderen_eposta" value="<?= e($settings['smtp_gonderen_eposta'] ?? '') ?>" placeholder="bildirim@bitiras.com" class="form-control" style="width:100%;box-sizing:border-box;">
                        </div>
                        <div>
                            <label class="form-label" style="font-weight:700;font-size:13px;display:block;margin-bottom:6px;">Şifreleme</label>
                            <select name="smtp_sifreleme" class="form-control" style="width:100%;box-sizing:border-box;height:42px;">
                                <option value="tls" <?= ($settings['smtp_sifreleme'] ?? '') === 'tls' ? 'selected' : '' ?>>TLS (587)</option>
                                <option value="ssl" <?= ($settings['smtp_sifreleme'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (465)</option>
                                <option value="none" <?= ($settings['smtp_sifreleme'] ?? '') === 'none' ? 'selected' : '' ?>>Yok (25)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-bottom:40px;">
                <button type="submit" class="btn btn-primary" style="padding:14px 28px;font-size:15px;">
                    <i class="fa-solid fa-floppy-disk"></i> Tüm Ayarları Kaydet
                </button>
            </div>
        </form>
    </div>

                                                                   
    <div>
        <div class="admin-panel" style="margin-bottom:24px;">
            <div class="panel-header">
                <h2>
                    <i class="fa-solid fa-paper-plane"></i>
                    <span>SMTP Test E-postası</span>
                </h2>
            </div>
            <div class="panel-body padded">
                <p style="font-size:13px;color:var(--admin-text-muted);margin-bottom:16px;">
                    Mevcut SMTP yapılandırmanızı test etmek için bir alıcı adresi girerek deneme e-postası tetikleyin.
                </p>

                <form method="POST" action="/admin/actions/settings_action.php">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="action" value="test_smtp">

                    <div style="margin-bottom:16px;">
                        <label class="form-label" style="font-weight:700;font-size:12px;display:block;margin-bottom:6px;">Alıcı E-posta</label>
                        <input type="email" name="test_email" value="<?= e($admin['eposta']) ?>" required class="form-control" style="width:100%;box-sizing:border-box;">
                    </div>

                    <button type="submit" class="btn btn-secondary" style="width:100%;justify-content:center;">
                        <i class="fa-solid fa-vial"></i> Test E-postası Gönder
                    </button>
                </form>
            </div>
        </div>

                                               
        <?php
        $pendingEmails = 0;
        $sentEmails = 0;
        try {
            $pendingEmails = (int)$pdo->query("SELECT COUNT(*) FROM eposta_kuyrugu WHERE durum = 'pending'")->fetchColumn();
            $sentEmails = (int)$pdo->query("SELECT COUNT(*) FROM eposta_kuyrugu WHERE durum = 'sent'")->fetchColumn();
        } catch (Exception $e) {}
        ?>
        <div class="admin-panel">
            <div class="panel-header">
                <h2>
                    <i class="fa-solid fa-inbox"></i>
                    <span>E-Posta Kuyruk Durumu</span>
                </h2>
            </div>
            <div class="panel-body padded">
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #F1F5F9;font-size:13px;">
                    <span style="color:var(--admin-text-muted);">Bekleyen Bildirimler:</span>
                    <strong style="color:var(--admin-warning);"><?= $pendingEmails ?></strong>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:13px;">
                    <span style="color:var(--admin-text-muted);">Gönderilen Bildirimler:</span>
                    <strong style="color:var(--admin-success);"><?= $sentEmails ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
