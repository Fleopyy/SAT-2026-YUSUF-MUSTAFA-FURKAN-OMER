<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/data/database.php';

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'customer_login') {
        $email = trim($_POST['eposta'] ?? $_POST['email'] ?? '');
        $password = $_POST['sifre'] ?? $_POST['password'] ?? '';

        if (!empty($email) && !empty($password)) {
            $stmt = $pdo->prepare("SELECT * FROM kullanicilar WHERE eposta = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && (password_verify($password, $user['sifre_hash']) || $password === $user['sifre_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['ad_soyad'];
                $_SESSION['user_role'] = 'customer';
                $_SESSION['mesaj'] = "Giriş başarılı! Hoş geldiniz, " . htmlspecialchars($user['ad_soyad']) . ".";
                $_SESSION['mesaj_turu'] = "success";
            } else {
                $_SESSION['mesaj'] = "Hatalı e-posta veya şifre girdiniz!";
                $_SESSION['mesaj_turu'] = "danger";
            }
        } else {
            $_SESSION['mesaj'] = "Lütfen e-posta ve şifrenizi girin.";
            $_SESSION['mesaj_turu'] = "warning";
        }
        header("Location: index.php");
        exit;
    }

    if ($action === 'customer_register') {
        $fullName = trim($_POST['ad_soyad'] ?? $_POST['full_name'] ?? '');
        $phone    = trim($_POST['telefon'] ?? $_POST['phone'] ?? '');
        $email    = trim($_POST['eposta'] ?? $_POST['email'] ?? '');
        $password = $_POST['sifre'] ?? $_POST['password'] ?? '';

        if (!empty($fullName) && !empty($phone) && !empty($email) && !empty($password)) {
            $check = $pdo->prepare("SELECT id FROM kullanicilar WHERE eposta = ? OR telefon = ?");
            $check->execute([$email, $phone]);

            if ($check->fetch()) {
                $_SESSION['mesaj'] = "Bu e-posta veya telefon ile zaten bir kayıt bulunuyor!";
                $_SESSION['mesaj_turu'] = "danger";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO kullanicilar (ad_soyad, telefon, eposta, sifre_hash) VALUES (?, ?, ?, ?)");
                $stmt->execute([$fullName, $phone, $email, $hash]);

                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['user_name'] = $fullName;
                $_SESSION['user_role'] = 'customer';
                $_SESSION['mesaj'] = "Kaydınız başarıyla oluşturuldu! Hoş geldiniz.";
                $_SESSION['mesaj_turu'] = "success";
            }
        } else {
            $_SESSION['mesaj'] = "Lütfen tüm alanları eksiksiz doldurun.";
            $_SESSION['mesaj_turu'] = "warning";
        }
        header("Location: index.php");
        exit;
    }

    if ($action === 'barber_login') {
        $loginVal = trim($_POST['eposta'] ?? $_POST['telefon'] ?? $_POST['business_login'] ?? '');
        $password = $_POST['sifre'] ?? $_POST['password'] ?? '';

        if (!empty($loginVal) && !empty($password)) {
            $stmt = $pdo->prepare("SELECT * FROM isletmeler WHERE eposta = ? OR telefon = ?");
            $stmt->execute([$loginVal, $loginVal]);
            $barber = $stmt->fetch();

            if ($barber && (password_verify($password, $barber['sifre_hash']) || $password === $barber['sifre_hash'])) {
                                                                                               
                if ($barber['durum'] === 'pending') {
                    $_SESSION['mesaj'] = "İşletme hesabınız henüz onay aşamasındadır. Sistem yöneticisi onayından sonra giriş yapabilirsiniz.";
                    $_SESSION['mesaj_turu'] = "warning";
                } elseif ($barber['durum'] === 'rejected') {
                    $gerekce = !empty($barber['red_gerekce']) ? " Gerekçe: " . $barber['red_gerekce'] : "";
                    $_SESSION['mesaj'] = "İşletme başvurunuz onaylanmamıştır." . htmlspecialchars($gerekce);
                    $_SESSION['mesaj_turu'] = "danger";
                } elseif ($barber['durum'] === 'suspended') {
                    $_SESSION['mesaj'] = "İşletme hesabınız askıya alınmıştır. Lütfen destek ekibi ile iletişime geçin.";
                    $_SESSION['mesaj_turu'] = "danger";
                } else {
                                                                   
                    session_regenerate_id(true);
                    $_SESSION['barber_id'] = $barber['id'];
                    $_SESSION['barber_role'] = 'barber';
                    $_SESSION['barber_name'] = $barber['isletme_adi'];
                    $_SESSION['user_role'] = 'barber';
                    $_SESSION['mesaj'] = "Giriş başarılı! Hoş geldiniz, " . htmlspecialchars($barber['isletme_adi']) . ".";
                    $_SESSION['mesaj_turu'] = "success";
                }
            } else {
                $_SESSION['mesaj'] = "Hatalı işletme bilgisi veya şifre girdiniz!";
                $_SESSION['mesaj_turu'] = "danger";
            }
        } else {
            $_SESSION['mesaj'] = "Lütfen işletme bilginizi ve şifrenizi girin.";
            $_SESSION['mesaj_turu'] = "warning";
        }
        header("Location: index.php");
        exit;
    }

    if ($action === 'barber_register') {
        $ownerName = trim($_POST['isletme_sahibi'] ?? $_POST['owner_name'] ?? '');
        $shopName  = trim($_POST['isletme_adi'] ?? $_POST['shop_name'] ?? '');
        $phone     = trim($_POST['telefon'] ?? $_POST['phone'] ?? '');
        $email     = trim($_POST['eposta'] ?? $_POST['email'] ?? '');
        $city      = trim($_POST['sehir'] ?? $_POST['city'] ?? '');
        $district  = trim($_POST['ilce'] ?? $_POST['district'] ?? '');
        $tamAdres  = trim($_POST['tam_adres'] ?? $_POST['address'] ?? '');
        $password  = $_POST['sifre'] ?? $_POST['password'] ?? '';

        if (!empty($ownerName) && !empty($shopName) && !empty($phone) && !empty($password)) {
            if (empty($email)) {
                $email = preg_replace('/[^0-9]/', '', $phone) . "@bitiras.com";
            }

            $check = $pdo->prepare("SELECT id FROM isletmeler WHERE eposta = ? OR telefon = ?");
            $check->execute([$email, $phone]);

            if ($check->fetch()) {
                $_SESSION['mesaj'] = "Bu telefon veya e-posta ile kayıtlı bir işletme zaten var!";
                $_SESSION['mesaj_turu'] = "danger";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                                                                                    
                $stmt = $pdo->prepare("INSERT INTO isletmeler (isletme_sahibi, isletme_adi, telefon, eposta, sehir, ilce, tam_adres, sifre_hash, durum) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([$ownerName, $shopName, $phone, $email, $city, $district, $tamAdres, $hash]);
                $newBusinessId = (int)$pdo->lastInsertId();

                                                               
                try {
                    $stmtNotif = $pdo->prepare("INSERT INTO bildirimler (baslik, mesaj, tur, link) VALUES (?, ?, 'info', ?)");
                    $stmtNotif->execute([
                        "Yeni İşletme Başvurusu: " . $shopName,
                        "{$ownerName} ({$phone}) yeni bir berber işletmesi kaydı oluşturdu. Onaylamak için tıklayın.",
                        "/admin/pages/applications.php?detail=" . $newBusinessId
                    ]);
                } catch (Exception $e) {}

                                                                          
                unset($_SESSION['barber_id'], $_SESSION['barber_name'], $_SESSION['user_role']);
                $_SESSION['mesaj'] = "İşletme kaydınız başarıyla alındı! Hesabınız sistem yöneticisi onayından sonra aktif hale gelecektir.";
                $_SESSION['mesaj_turu'] = "info";
            }
        } else {
            $_SESSION['mesaj'] = "Lütfen zorunlu alanları doldurun.";
            $_SESSION['mesaj_turu'] = "warning";
        }
        header("Location: index.php");
        exit;
    }
}
?>

    <div class="modal fade" id="authModal" tabindex="-1" aria-labelledby="authModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">


                <div class="modal-header bg-light d-block border-bottom p-4 position-relative">
                    <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"
                        aria-label="Close"></button>

                    <div class="text-center mb-3">
                        <span class="text-uppercase fw-extrabold text-xs tracking-wider" style="color: var(--accent);">
                            <i class="fa-solid fa-shield-halved me-1"></i> Güvenli Giriş & Kayıt Paneli
                        </span>
                        <h3 id="authModalTitle" class="fs-4 fw-extrabold text-dark mt-1 mb-0">BiTıraş.com'a Hoş Geldiniz
                        </h3>
                    </div>

                    <ul class="nav nav-pills nav-justified bg-white p-1 rounded-3 border shadow-sm" id="rolePills"
                        role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pills-customer-tab" data-bs-toggle="pill"
                                data-bs-target="#pills-customer" type="button" role="tab" aria-controls="pills-customer"
                                aria-selected="true">
                                Müşteri Giriş
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-barber-tab" data-bs-toggle="pill"
                                data-bs-target="#pills-barber" type="button" role="tab" aria-controls="pills-barber"
                                aria-selected="false">
                                Berber Giriş
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="modal-body p-4">
                    <div class="tab-content" id="rolePillsContent">


                        <div class="tab-pane fade show active" id="pills-customer" role="tabpanel"
                            aria-labelledby="pills-customer-tab">


                            <ul class="nav nav-tabs nav-justified mb-4" id="customerSubTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="cust-login-tab" data-bs-toggle="tab"
                                        data-bs-target="#cust-login-pane" type="button" role="tab">Giriş Yap</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="cust-reg-tab" data-bs-toggle="tab"
                                        data-bs-target="#cust-reg-pane" type="button" role="tab">Kayıt Ol</button>
                                </li>
                            </ul>

                            <div class="tab-content" id="customerSubTabContent">

                                <div class="tab-pane fade show active" id="cust-login-pane" role="tabpanel">
                                    <form id="formCustomerLogin" method="POST" action="login.php" class="auth-form">
                                        <input type="hidden" name="action" value="customer_login">
                                        <input type="hidden" name="role" value="customer">

                                        <div class="mb-3">
                                            <label for="custLoginEmail"
                                                class="form-label fw-bold text-xs text-uppercase text-muted">E-Posta
                                                Adresi</label>
                                            <input type="email" id="custLoginEmail" name="eposta" required
                                                placeholder="ornek@musteri.com"
                                                class="form-control form-control-lg fs-6">
                                        </div>

                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label for="custLoginPass"
                                                    class="form-label fw-bold text-xs text-uppercase text-muted">Şifre</label>
                                                <a href="javascript:void(0)"
                                                    onclick="alert('Şifre sıfırlama için yöneticinizle iletişime geçin.')"
                                                    class="text-xs fw-bold" style="color: var(--accent);">Şifremi
                                                    Unuttum?</a>
                                            </div>
                                            <input type="password" id="custLoginPass" name="sifre" required
                                                placeholder="••••••••" class="form-control form-control-lg fs-6">
                                        </div>

                                        <button type="submit" class="btn btn-primary btn-lg w-100 fs-6 py-3 mt-2">
                                            <i class="fa-solid fa-right-to-bracket me-2"></i>Müşteri Girişi Yap
                                        </button>
                                    </form>
                                </div>

                                <div class="tab-pane fade" id="cust-reg-pane" role="tabpanel">
                                    <form id="formCustomerRegister" method="POST" action="login.php" class="auth-form">
                                        <input type="hidden" name="action" value="customer_register">
                                        <input type="hidden" name="role" value="customer">

                                        <div class="mb-3">
                                            <label for="custRegName"
                                                class="form-label fw-bold text-xs text-uppercase text-muted">Ad
                                                Soyad</label>
                                            <input type="text" id="custRegName" name="ad_soyad" required
                                                placeholder="Ahmet Yılmaz" class="form-control form-control-lg fs-6">
                                        </div>

                                        <div class="mb-3">
                                            <label for="custRegPhone"
                                                class="form-label fw-bold text-xs text-uppercase text-muted">Telefon
                                                Numarası</label>
                                            <input type="tel" id="custRegPhone" name="telefon" required
                                                placeholder="05XX XXX XX XX" class="form-control form-control-lg fs-6">
                                        </div>

                                        <div class="mb-3">
                                            <label for="custRegEmail"
                                                class="form-label fw-bold text-xs text-uppercase text-muted">E-Posta
                                                Adresi</label>
                                            <input type="email" id="custRegEmail" name="eposta" required
                                                placeholder="musteri@mail.com"
                                                class="form-control form-control-lg fs-6">
                                        </div>

                                        <div class="mb-3">
                                            <label for="custRegPass"
                                                class="form-label fw-bold text-xs text-uppercase text-muted">Şifre
                                                Belirleyin</label>
                                            <input type="password" id="custRegPass" name="sifre" required
                                                placeholder="••••••••" class="form-control form-control-lg fs-6">
                                        </div>

                                        <button type="submit" class="btn btn-primary btn-lg w-100 fs-6 py-3 mt-2">
                                            <i class="fa-solid fa-user-check me-2"></i>Müşteri Hesabı Oluştur
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="pills-barber" role="tabpanel" aria-labelledby="pills-barber-tab">

                            <ul class="nav nav-tabs nav-justified mb-4" id="barberSubTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="barber-login-tab" data-bs-toggle="tab"
                                        data-bs-target="#barber-login-pane" type="button" role="tab">Giriş Yap</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="barber-reg-tab" data-bs-toggle="tab"
                                        data-bs-target="#barber-reg-pane" type="button" role="tab">Kayıt Ol</button>
                                </li>
                            </ul>

                            <div class="tab-content" id="barberSubTabContent">

                                <div class="tab-pane fade show active" id="barber-login-pane" role="tabpanel">
                                    <form id="formBarberLogin" method="POST" action="login.php" class="auth-form">
                                        <input type="hidden" name="action" value="barber_login">
                                        <input type="hidden" name="role" value="barber">

                                        <div class="mb-3">
                                            <label for="barberLoginUser"
                                                class="form-label fw-bold text-xs text-uppercase text-muted">İşletme
                                                E-Postası veya Telefon</label>
                                            <input type="text" id="barberLoginUser" name="eposta" required
                                                placeholder="isletme@berber.com veya 05XX..."
                                                class="form-control form-control-lg fs-6">
                                        </div>

                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label for="barberLoginPass"
                                                    class="form-label fw-bold text-xs text-uppercase text-muted">Şifre</label>
                                                <a href="javascript:void(0)"
                                                    onclick="alert('Şifre sıfırlama için yöneticinizle iletişime geçin.')"
                                                    class="text-xs fw-bold" style="color: var(--accent);">Şifremi
                                                    Unuttum?</a>
                                            </div>
                                            <input type="password" id="barberLoginPass" name="sifre" required
                                                placeholder="••••••••" class="form-control form-control-lg fs-6">
                                        </div>

                                        <button type="submit" class="btn btn-dark btn-lg w-100 fs-6 py-3 mt-2">
                                            <i class="fa-solid fa-scissors me-2"></i>Berber Girişi Yap
                                        </button>
                                    </form>
                                </div>
                                <div class="tab-pane fade" id="barber-reg-pane" role="tabpanel">
                                    <form id="formBarberRegister" method="POST" action="login.php" class="auth-form">
                                        <input type="hidden" name="action" value="barber_register">
                                        <input type="hidden" name="role" value="barber">

                                        <div class="row g-2 mb-3">
                                            <div class="col-md-6">
                                                <label for="barberOwnerName"
                                                    class="form-label fw-bold text-xs text-uppercase text-muted">Yetkili
                                                    Ad Soyad</label>
                                                <input type="text" id="barberOwnerName" name="isletme_sahibi" required
                                                    placeholder="Mehmet Usta" class="form-control">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="barberShopName"
                                                    class="form-label fw-bold text-xs text-uppercase text-muted">Salon /
                                                    Berber Adı</label>
                                                <input type="text" id="barberShopName" name="isletme_adi" required
                                                    placeholder="Stil Erkek Kuaförü" class="form-control">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="barberPhone"
                                                class="form-label fw-bold text-xs text-uppercase text-muted">İşletme
                                                Telefon Numarası</label>
                                            <input type="tel" id="barberPhone" name="telefon" required
                                                placeholder="0212 XXX XX XX veya 05XX..." class="form-control">
                                        </div>

                                        <div class="mb-3">
                                            <label for="barberEmail"
                                                class="form-label fw-bold text-xs text-uppercase text-muted">İşletme
                                                E-Posta Adresi</label>
                                            <input type="email" id="barberEmail" name="eposta" required
                                                placeholder="isletme@berber.com" class="form-control">
                                        </div>

                                        <div class="row g-2 mb-3">
                                            <div class="col-md-6">
                                                <label for="barberCity"
                                                    class="form-label fw-bold text-xs text-uppercase text-muted">Şehir</label>
                                                <select id="barberCity" name="sehir" required
                                                    onchange="updateDistrictOptions(this.value, 'barberDistrict')"
                                                    class="form-select">
                                                    <option value="">Şehir Seçin</option>
                                                    <option value="istanbul">İstanbul</option>
                                                    <option value="ankara">Ankara</option>
                                                    <option value="izmir">İzmir</option>
                                                    <option value="bursa">Bursa</option>
                                                    <option value="antalya">Antalya</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="barberDistrict"
                                                    class="form-label fw-bold text-xs text-uppercase text-muted">İlçe</label>
                                                <select id="barberDistrict" name="ilce" required
                                                    class="form-select">
                                                    <option value="">Önce Şehir Seçin</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="BerberAdres"
                                                class="form-label fw-bold text-xs text-uppercase text-muted">Tam
                                                Adres</label>
                                            <input type="text" id="BerberAdres" name="tam_adres"
                                                placeholder="Adres Bilgisi" class="form-control">
                                        </div>

                                        <div class="mb-3">
                                            <label for="barberPass"
                                                class="form-label fw-bold text-xs text-uppercase text-muted">Şifre
                                                Belirleyin</label>
                                            <input type="password" id="barberPass" name="sifre" required
                                                placeholder="••••••••" class="form-control">
                                        </div>

                                        <button type="submit" class="btn btn-dark btn-lg w-100 fs-6 py-3 mt-2">
                                            <i class="fa-solid fa-shop me-2"></i>Berber Salonu Hesabı Aç
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer bg-light border-top text-center d-block py-2">
                    <span class="text-muted" style="font-size: 11px;">
                        <i class="fa-solid fa-lock me-1" style="color: var(--accent);"></i> Tüm veriler SSL koruması
                        altındadır.
                    </span>
                </div>

            </div>
        </div>
    </div>
