<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/data/security.php';
require_once __DIR__ . '/data/customer_auth_service.php';
require_once __DIR__ . '/data/two_factor_service.php';

$csrfToken = generateCsrfToken();

if (($_GET['action'] ?? '') === 'logout') {
    http_response_code(405);
    header('Allow: POST');
    exit('Çıkış için POST isteği gereklidir.');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'logout') {
    requireCsrf();
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $sessionToken   = $_SESSION['csrf_token'] ?? '';

    if (empty($submittedToken) || empty($sessionToken) || !hash_equals($sessionToken, $submittedToken)) {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

        if ($isAjax) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Geçersiz güvenlik tokeni (CSRF). Lütfen sayfayı yenileyip tekrar deneyin.']);
            exit;
        }

        $_SESSION['mesaj'] = 'Güvenlik doğrulaması (CSRF) zaman aşımına uğradı veya geçersiz. Lütfen sayfayı yenileyip tekrar deneyin.';
        $_SESSION['mesaj_turu'] = 'danger';

        $action = (string)($_POST['action'] ?? '');
        $redirectUrl = 'index.php?login=customer';
        if (str_contains($action, 'barber')) {
            $redirectUrl = 'index.php?login=barber';
        }
        header("Location: " . $redirectUrl);
        exit;
    }
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!checkLoginRateLimit($ip)) {
        http_response_code(429);
        exit('Çok fazla deneme. 15 dakika sonra tekrar deneyin.');
    }
    $attemptSuccessful = false;
    $action = $_POST['action'] ?? '';

    if ($action === 'customer_login') {
        $email = trim($_POST['eposta'] ?? $_POST['email'] ?? '');
        $password = $_POST['sifre'] ?? $_POST['password'] ?? '';

        if (!empty($email) && !empty($password)) {
            $stmt = $pdo->prepare("SELECT * FROM kullanicilar WHERE eposta = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['sifre_hash'])) {
                renewAuthenticatedSession('user', $user['sifre_hash']);
                $attemptSuccessful = true;
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
        recordLoginAttempt($ip, trim((string)($_POST['eposta'] ?? $_POST['email'] ?? $_POST['telefon'] ?? $_POST['business_login'] ?? '')), $attemptSuccessful);
        header("Location: index.php");
        exit;
    }

    if ($action === 'customer_register') {
        $fullName        = trim($_POST['ad_soyad'] ?? $_POST['full_name'] ?? '');
        $phone           = trim($_POST['telefon'] ?? $_POST['phone'] ?? '');
        $email           = trim($_POST['eposta'] ?? $_POST['email'] ?? '');
        $city            = trim($_POST['sehir'] ?? '');
        $password        = $_POST['sifre'] ?? $_POST['password'] ?? '';
        $passwordConfirm = $_POST['sifre_tekrar'] ?? '';
        $kvkkOnay        = isset($_POST['kvkk_onay']) && (string)$_POST['kvkk_onay'] === '1';
        $pazarlamaOnay   = (isset($_POST['pazarlama_onay']) && (string)$_POST['pazarlama_onay'] === '1') ? 1 : 0;

        if (!$kvkkOnay) {
            $_SESSION['mesaj'] = "Kayıt işlemini tamamlamak için lütfen KVKK Aydınlatma Metni'ni okuyup onaylayın.";
            $_SESSION['mesaj_turu'] = "danger";
            recordLoginAttempt($ip, $email ?: $phone, false);
            header("Location: index.php");
            exit;
        }

        if (!empty($fullName) && !empty($phone) && !empty($email) && !empty($city) && !empty($password)) {
            if (!validNewPassword($password) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['mesaj'] = 'Geçerli bir e-posta adresi ve en az 10 karakter uzunluğunda bir şifre gereklidir.';
                $_SESSION['mesaj_turu'] = 'danger';
            } elseif ($password !== $passwordConfirm) {
                $_SESSION['mesaj'] = "Girdiğiniz şifreler birbiriyle eşleşmiyor. Lütfen tekrar deneyin.";
                $_SESSION['mesaj_turu'] = "danger";
            } else {
                $check = $pdo->prepare("SELECT id FROM kullanicilar WHERE eposta = ? OR telefon = ?");
                $check->execute([$email, $phone]);

                if ($check->fetch()) {
                    $_SESSION['mesaj'] = 'Bu e-posta adresi veya telefon numarası sistemde zaten kayıtlıdır. Lütfen bilgilerinizi kontrol edin veya giriş yapın.';
                    $_SESSION['mesaj_turu'] = "danger";
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO kullanicilar (ad_soyad, telefon, eposta, sehir, sifre_hash, eposta_dogrulandi, kvkk_onay_tarihi, pazarlama_onay) VALUES (?, ?, ?, ?, ?, 0, NOW(), ?)");
                    $stmt->execute([$fullName, $phone, $email, $city, $hash, $pazarlamaOnay]);
                    $newUserId = (int)$pdo->lastInsertId();

                    try {
                        $stmtLog = $pdo->prepare("INSERT INTO islem_gunlukleri (islem_turu, hedef_turu, hedef_id, detay, ip_adresi) VALUES ('kvkk_onay', 'kullanici', ?, ?, ?)");
                        $stmtLog->execute([$newUserId, "Müşteri kaydında KVKK metni onaylandı. Pazarlama izni: " . ($pazarlamaOnay ? 'Evet' : 'Hayır'), $ip]);
                    } catch (Throwable $e) {}

                    // Kayıt sonrası otomatik e-posta doğrulama kodu gönder
                    $verificationAccepted = sendCustomerVerificationEmail($newUserId, $email, $fullName);

                    renewAuthenticatedSession('user', $hash);
                    $attemptSuccessful = true;
                    $_SESSION['user_id'] = $newUserId;
                    $_SESSION['user_name'] = $fullName;
                    $_SESSION['user_role'] = 'customer';
                    $_SESSION['mesaj'] = $verificationAccepted ? 'Kayit olusturuldu. Ileti gonderim icin kabul edildi; teslimat henuz dogrulanmadi.' : 'Kayit olusturuldu ancak ileti gonderilemedi. Hesabim sayfasindan yeniden kod isteyin.';
                    $_SESSION['mesaj_turu'] = $verificationAccepted ? 'info' : 'warning';
                }
            }
        } else {
            $_SESSION['mesaj'] = "Lütfen tüm alanları eksiksiz doldurun.";
            $_SESSION['mesaj_turu'] = "warning";
        }
        recordLoginAttempt($ip, trim((string)($_POST['eposta'] ?? $_POST['email'] ?? $_POST['telefon'] ?? $_POST['business_login'] ?? '')), $attemptSuccessful);
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

            if ($barber && password_verify($password, $barber['sifre_hash'])) {
                                                                                               
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
                    completeBarberPasswordLogin($barber, $loginVal, '/barber/dashboard');
                }
            } else {
                $_SESSION['mesaj'] = "Hatalı işletme bilgisi veya şifre girdiniz!";
                $_SESSION['mesaj_turu'] = "danger";
            }
        } else {
            $_SESSION['mesaj'] = "Lütfen işletme bilginizi ve şifrenizi girin.";
            $_SESSION['mesaj_turu'] = "warning";
        }
        recordLoginAttempt($ip, trim((string)($_POST['eposta'] ?? $_POST['email'] ?? $_POST['telefon'] ?? $_POST['business_login'] ?? '')), $attemptSuccessful);
        header("Location: index.php");
        exit;
    }

    if ($action === 'barber_register') {
        $ownerName       = trim($_POST['isletme_sahibi'] ?? $_POST['owner_name'] ?? '');
        $shopName        = trim($_POST['isletme_adi'] ?? $_POST['shop_name'] ?? '');
        $phone           = trim($_POST['telefon'] ?? $_POST['phone'] ?? '');
        $email           = trim($_POST['eposta'] ?? $_POST['email'] ?? '');
        $city            = trim($_POST['sehir'] ?? $_POST['city'] ?? '');
        $district        = trim($_POST['ilce'] ?? $_POST['district'] ?? '');
        $tamAdres        = trim($_POST['tam_adres'] ?? $_POST['address'] ?? '');
        $password        = $_POST['sifre'] ?? $_POST['password'] ?? '';
        $passwordConfirm = $_POST['sifre_tekrar'] ?? '';
        $kvkkOnay        = isset($_POST['kvkk_onay']) && (string)$_POST['kvkk_onay'] === '1';
        $pazarlamaOnay   = (isset($_POST['pazarlama_onay']) && (string)$_POST['pazarlama_onay'] === '1') ? 1 : 0;

        if (!$kvkkOnay) {
            $_SESSION['mesaj'] = "İşletme başvurunuzu tamamlamak için lütfen KVKK Aydınlatma Metni'ni okuyup onaylayın.";
            $_SESSION['mesaj_turu'] = "danger";
            recordLoginAttempt($ip, $email ?: $phone, false);
            header("Location: index.php");
            exit;
        }

        if (!empty($ownerName) && !empty($shopName) && !empty($phone) && !empty($password)) {
            if (!validNewPassword($password) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['mesaj'] = 'Geçerli bir e-posta adresi ve en az 10 karakter uzunluğunda bir şifre gereklidir.';
                $_SESSION['mesaj_turu'] = 'danger';
            } elseif ($password !== $passwordConfirm) {
                $_SESSION['mesaj'] = "Girdiğiniz şifreler birbiriyle eşleşmiyor. Lütfen tekrar deneyin.";
                $_SESSION['mesaj_turu'] = "danger";
            } else {

                $check = $pdo->prepare("SELECT id FROM isletmeler WHERE eposta = ? OR telefon = ?");
                $check->execute([$email, $phone]);

                if ($check->fetch()) {
                    $_SESSION['mesaj'] = 'Bu e-posta adresi veya telefon numarası ile kayıtlı bir işletme zaten mevcut. Lütfen bilgilerinizi kontrol edin veya giriş yapın.';
                    $_SESSION['mesaj_turu'] = "danger";
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                                                                                        
                    $stmt = $pdo->prepare("INSERT INTO isletmeler (isletme_sahibi, isletme_adi, telefon, eposta, sehir, ilce, tam_adres, sifre_hash, durum, kvkk_onay_tarihi, pazarlama_onay) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), ?)");
                    $stmt->execute([$ownerName, $shopName, $phone, $email, $city, $district, $tamAdres, $hash, $pazarlamaOnay]);
                    $newBusinessId = (int)$pdo->lastInsertId();

                    try {
                        $stmtLog = $pdo->prepare("INSERT INTO islem_gunlukleri (islem_turu, hedef_turu, hedef_id, detay, ip_adresi) VALUES ('kvkk_onay', 'isletme', ?, ?, ?)");
                        $stmtLog->execute([$newBusinessId, "İşletme başvurusunda KVKK metni onaylandı. Pazarlama izni: " . ($pazarlamaOnay ? 'Evet' : 'Hayır'), $ip]);
                    } catch (Throwable $e) {}
                    $attemptSuccessful = true;

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
            }
        } else {
            $_SESSION['mesaj'] = "Lütfen zorunlu alanları doldurun.";
            $_SESSION['mesaj_turu'] = "warning";
        }
        recordLoginAttempt($ip, trim((string)($_POST['eposta'] ?? $_POST['email'] ?? $_POST['telefon'] ?? $_POST['business_login'] ?? '')), $attemptSuccessful);
        header("Location: index.php");
        exit;
    }
}

$turkeyDistricts = [];
if (file_exists(__DIR__ . '/data/turkey_districts.json')) {
    $decodedDistricts = json_decode(file_get_contents(__DIR__ . '/data/turkey_districts.json'), true);
    if (!empty($decodedDistricts['districts'])) {
        $turkeyDistricts = $decodedDistricts['districts'];
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
                    <?php 
                    $isAuthModalActive = !empty($_GET['login']) || !empty($_GET['register']);
                    $modalMsg = $isAuthModalActive ? ($flashMessage ?? $_SESSION['mesaj'] ?? null) : null;
                    $modalMsgType = $flashMessageType ?? $_SESSION['mesaj_turu'] ?? 'danger';
                    ?>
                    <?php if (!empty($modalMsg)): ?>
                        <div class="alert alert-<?= htmlspecialchars($modalMsgType, ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show mb-3" role="alert">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid <?= $modalMsgType === 'danger' ? 'fa-triangle-exclamation' : ($modalMsgType === 'success' ? 'fa-circle-check' : 'fa-circle-info') ?>"></i>
                                <span><?= htmlspecialchars($modalMsg, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
                        </div>
                    <?php endif; ?>
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
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
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
                                                <a href="/sifre_sifirla.php"
                                                    class="text-xs fw-bold" style="color: var(--accent);">Şifremi
                                                    Unuttum?</a>
                                            </div>
                                            <div class="input-group">
                                                <input type="password" id="custLoginPass" name="sifre" required
                                                    placeholder="••••••••" class="form-control form-control-lg fs-6"
                                                    autocomplete="current-password">
                                                <button class="btn btn-outline-secondary" type="button"
                                                    onclick="togglePassVis('custLoginPass', this)" tabindex="-1"
                                                    aria-label="Şifreyi göster/gizle">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary btn-lg w-100 fs-6 py-3 mt-2">
                                            <i class="fa-solid fa-right-to-bracket me-2"></i>Müşteri Girişi Yap
                                        </button>
                                    </form>
                                </div>

                                <div class="tab-pane fade" id="cust-reg-pane" role="tabpanel">
                                    <form id="formCustomerRegister" method="POST" action="login.php" class="auth-form" novalidate>
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
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
                                            <label id="custCityLabel"
                                                class="form-label fw-bold text-xs text-uppercase text-muted"
                                                style="cursor: pointer; user-select: none;">
                                                <i class="fa-solid fa-location-dot me-1" style="color:var(--accent);"></i>Şehir</label>
                                            <div class="cust-city-combobox" id="custCityCombobox">
                                                <input type="text" id="custRegCity" name="sehir" required
                                                    placeholder="Şehir yazın veya seçin..."
                                                    autocomplete="chrome-off"
                                                    data-lpignore="true"
                                                    role="combobox"
                                                    aria-autocomplete="list"
                                                    aria-expanded="false"
                                                    aria-controls="custCityDropdown"
                                                    class="form-control form-control-lg fs-6 pe-5">
                                                <button type="button" class="cust-city-toggle" id="custCityToggleBtn" tabindex="-1" aria-label="Şehir listesini aç veya kapat">
                                                    <i class="fa-solid fa-chevron-down"></i>
                                                </button>
                                                <div id="custCityDropdown" class="cust-city-dropdown" role="listbox" hidden></div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="custRegPass"
                                                class="form-label fw-bold text-xs text-uppercase text-muted">Şifre
                                                Belirleyin</label>
                                            <div class="input-group">
                                                <input type="password" id="custRegPass" name="sifre" required minlength="10" maxlength="72" title="Şifre en az 10 karakter uzunluğunda olmalıdır"
                                                    placeholder="••••••••" class="form-control form-control-lg fs-6"
                                                    autocomplete="new-password">
                                                <button class="btn btn-outline-secondary" type="button"
                                                    onclick="togglePassVis('custRegPass', this)" tabindex="-1"
                                                    aria-label="Şifreyi göster/gizle">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="custRegPassConfirm"
                                                class="form-label fw-bold text-xs text-uppercase text-muted">Şifre
                                                Tekrarı</label>
                                            <div class="input-group">
                                                <input type="password" id="custRegPassConfirm" name="sifre_tekrar" required
                                                    placeholder="••••••••" class="form-control form-control-lg fs-6"
                                                    autocomplete="new-password">
                                                <button class="btn btn-outline-secondary" type="button"
                                                    onclick="togglePassVis('custRegPassConfirm', this)" tabindex="-1"
                                                    aria-label="Şifreyi göster/gizle">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                            </div>
                                            <div id="passMatchMsg" class="mt-1" style="font-size:12px;min-height:16px;"></div>
                                        </div>

                                        <div class="mb-3 mt-3 p-3 rounded-3" style="background:#F8FAFC;border:1.5px solid #E2E8F0;">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="kvkk_onay" id="custKvkkOnay" value="1" required style="cursor:pointer;width:18px;height:18px;accent-color:var(--accent);">
                                                <label class="form-check-label text-xs text-muted ms-1" for="custKvkkOnay" style="cursor:pointer;line-height:1.45;">
                                                    <a href="/kvkk.php" target="_blank" class="fw-bold text-dark text-decoration-underline" style="color:var(--accent) !important;">KVKK Aydınlatma Metni</a>'ni okudum ve kişisel verilerimin işlenmesi hakkında bilgilendirildim. <span class="text-danger fw-bold">*</span>
                                                </label>
                                                <div id="custKvkkError" class="text-danger mt-1 fw-semibold" style="font-size:11.5px;display:none;">
                                                    <i class="fa-solid fa-circle-exclamation me-1"></i>Lütfen devam etmek için KVKK metnini onaylayın.
                                                </div>
                                            </div>
                                            <div class="form-check m-0">
                                                <input class="form-check-input" type="checkbox" name="pazarlama_onay" id="custPazarlamaOnay" value="1" style="cursor:pointer;width:17px;height:17px;accent-color:var(--accent);">
                                                <label class="form-check-label text-xs text-muted ms-1" for="custPazarlamaOnay" style="cursor:pointer;line-height:1.45;">
                                                    Kampanya, indirim ve duyurulardan haberdar olmak için ticari elektronik ileti almayı kabul ediyorum. <span class="badge bg-light text-secondary border" style="font-size:10px;">İsteğe Bağlı</span>
                                                </label>
                                            </div>
                                        </div>

                                        <button type="submit" id="custRegSubmitBtn" class="btn btn-primary btn-lg w-100 fs-6 py-3 mt-2">
                                            <i class="fa-solid fa-user-check me-2"></i>Müşteri Hesabı Oluştur
                                        </button>
                                    </form>

                                    <script>
                                    (function(){
                                        var pass    = document.getElementById('custRegPass');
                                        var confirm = document.getElementById('custRegPassConfirm');
                                        var msg     = document.getElementById('passMatchMsg');
                                        var btn     = document.getElementById('custRegSubmitBtn');

                                        function checkMatch() {
                                            if (!confirm.value) { msg.textContent = ''; return; }
                                            if (pass.value === confirm.value) {
                                                msg.innerHTML = '<span style="color:#10B981;"><i class="fa-solid fa-circle-check me-1"></i>Şifreler eşleşiyor</span>';
                                                confirm.style.borderColor = '#10B981';
                                            } else {
                                                msg.innerHTML = '<span style="color:#EF4444;"><i class="fa-solid fa-circle-xmark me-1"></i>Şifreler eşleşmiyor</span>';
                                                confirm.style.borderColor = '#EF4444';
                                            }
                                        }

                                        pass.addEventListener('input', checkMatch);
                                        confirm.addEventListener('input', checkMatch);

                                        document.getElementById('formCustomerRegister').addEventListener('submit', function(e){
                                            var kvkkCb = document.getElementById('custKvkkOnay');
                                            var kvkkErr = document.getElementById('custKvkkError');
                                            if (kvkkCb && !kvkkCb.checked) {
                                                e.preventDefault();
                                                if (kvkkErr) kvkkErr.style.display = 'block';
                                                kvkkCb.focus();
                                                return;
                                            } else if (kvkkErr) {
                                                kvkkErr.style.display = 'none';
                                            }

                                            if (pass.value !== confirm.value) {
                                                e.preventDefault();
                                                confirm.focus();
                                                msg.innerHTML = '<span style="color:#EF4444;"><i class="fa-solid fa-circle-xmark me-1"></i>Şifreler eşleşmiyor, kayıt tamamlanamaz!</span>';
                                            }
                                        });
                                    })();

                                    (function initAllCityComboboxes() {
                                        var cities = [
                                            "Adana", "Adıyaman", "Afyonkarahisar", "Ağrı", "Aksaray", "Amasya", "Ankara", "Antalya", "Ardahan", "Artvin",
                                            "Aydın", "Balıkesir", "Bartın", "Batman", "Bayburt", "Bilecik", "Bingöl", "Bitlis", "Bolu", "Burdur",
                                            "Bursa", "Çanakkale", "Çankırı", "Çorum", "Denizli", "Diyarbakır", "Düzce", "Edirne", "Elazığ", "Erzincan",
                                            "Erzurum", "Eskişehir", "Gaziantep", "Giresun", "Gümüşhane", "Hakkari", "Hatay", "Iğdır", "Isparta", "İstanbul",
                                            "İzmir", "Kahramanmaraş", "Karabük", "Karaman", "Kars", "Kastamonu", "Kayseri", "Kırıkkale", "Kırklareli", "Kırşehir",
                                            "Kilis", "Kocaeli", "Konya", "Kütahya", "Malatya", "Manisa", "Mardin", "Mersin", "Muğla", "Muş",
                                            "Nevşehir", "Niğde", "Ordu", "Osmaniye", "Rize", "Sakarya", "Samsun", "Siirt", "Sinop", "Sivas",
                                            "Şanlıurfa", "Şırnak", "Tekirdağ", "Tokat", "Trabzon", "Tunceli", "Uşak", "Van", "Yalova", "Yozgat", "Zonguldak"
                                        ];

                                        var allDistricts = <?= json_encode($turkeyDistricts, JSON_UNESCAPED_UNICODE) ?>;

                                        function normalizeText(str) {
                                            return (str || '')
                                                .toLocaleLowerCase('tr-TR')
                                                .normalize('NFD')
                                                .replace(/[\u0300-\u036f]/g, '')
                                                .replace(/ı/g, 'i')
                                                .replace(/ğ/g, 'g')
                                                .replace(/ü/g, 'u')
                                                .replace(/ş/g, 's')
                                                .replace(/ö/g, 'o')
                                                .replace(/ç/g, 'c');
                                        }

                                        function createCombobox(opts) {
                                            var input = document.getElementById(opts.inputId);
                                            var menu = document.getElementById(opts.menuId);
                                            var wrapper = document.getElementById(opts.wrapperId);
                                            var toggleBtn = document.getElementById(opts.toggleBtnId);

                                            if (!input || !menu || !wrapper) return;

                                            var activeIndex = -1;

                                            function openMenu() {
                                                renderList(input.value);
                                                menu.hidden = false;
                                                input.setAttribute('aria-expanded', 'true');
                                                wrapper.classList.add('open');
                                            }

                                            function closeMenu() {
                                                menu.hidden = true;
                                                input.setAttribute('aria-expanded', 'false');
                                                wrapper.classList.remove('open');
                                                activeIndex = -1;
                                            }

                                            function renderList(query) {
                                                var normalizedQuery = normalizeText(query);
                                                menu.replaceChildren();

                                                var matches = cities.filter(function(city) {
                                                    return !normalizedQuery || normalizeText(city).includes(normalizedQuery);
                                                });

                                                if (matches.length === 0) {
                                                    var empty = document.createElement('div');
                                                    empty.className = 'cust-city-empty';
                                                    empty.innerHTML = '<i class="fa-solid fa-circle-info me-1 text-muted"></i> Listede bulunamadı. Yazdığınız şehir geçerli sayılacaktır.';
                                                    menu.appendChild(empty);
                                                    return;
                                                }

                                                matches.forEach(function(city) {
                                                    var btn = document.createElement('button');
                                                    btn.type = 'button';
                                                    btn.className = 'cust-city-option';
                                                    btn.setAttribute('role', 'option');
                                                    btn.innerHTML = '<i class="fa-solid fa-location-dot city-icon"></i><span>' + city + '</span>';

                                                    btn.addEventListener('mousedown', function(e) {
                                                        e.preventDefault();
                                                        selectCity(city);
                                                    });

                                                    menu.appendChild(btn);
                                                });

                                                activeIndex = -1;
                                            }

                                            function selectCity(city) {
                                                input.value = city;
                                                closeMenu();
                                                input.dispatchEvent(new Event('input', { bubbles: true }));
                                                input.dispatchEvent(new Event('change', { bubbles: true }));
                                                if (typeof opts.onChange === 'function') {
                                                    opts.onChange(city);
                                                }
                                            }

                                            input.addEventListener('focus', openMenu);
                                            input.addEventListener('input', function() {
                                                openMenu();
                                                if (typeof opts.onChange === 'function') {
                                                    opts.onChange(input.value);
                                                }
                                            });

                                            var label = document.getElementById(opts.labelId);

                                            function toggleDropdown(e) {
                                                if (e) {
                                                    e.preventDefault();
                                                    e.stopPropagation();
                                                }
                                                if (menu.hidden) {
                                                    openMenu();
                                                } else {
                                                    closeMenu();
                                                }
                                            }

                                            if (toggleBtn) {
                                                toggleBtn.addEventListener('click', toggleDropdown);
                                            }

                                            if (label) {
                                                label.addEventListener('click', toggleDropdown);
                                            }

                                            input.addEventListener('keydown', function(e) {
                                                var options = menu.querySelectorAll('.cust-city-option');
                                                if (menu.hidden && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
                                                    openMenu();
                                                    return;
                                                }

                                                if (e.key === 'ArrowDown') {
                                                    e.preventDefault();
                                                    if (options.length > 0) {
                                                        activeIndex = (activeIndex + 1) % options.length;
                                                        updateActive(options);
                                                    }
                                                } else if (e.key === 'ArrowUp') {
                                                    e.preventDefault();
                                                    if (options.length > 0) {
                                                        activeIndex = (activeIndex - 1 + options.length) % options.length;
                                                        updateActive(options);
                                                    }
                                                } else if (e.key === 'Enter') {
                                                    if (!menu.hidden && activeIndex >= 0 && options[activeIndex]) {
                                                        e.preventDefault();
                                                        options[activeIndex].click();
                                                    }
                                                } else if (e.key === 'Escape') {
                                                    closeMenu();
                                                } else if (e.key === 'Tab') {
                                                    closeMenu();
                                                }
                                            });

                                            function updateActive(options) {
                                                options.forEach(function(opt, i) {
                                                    var isActive = (i === activeIndex);
                                                    opt.classList.toggle('active', isActive);
                                                    opt.setAttribute('aria-selected', isActive ? 'true' : 'false');
                                                    if (isActive) {
                                                        opt.scrollIntoView({ block: 'nearest' });
                                                    }
                                                });
                                            }

                                            document.addEventListener('pointerdown', function(e) {
                                                if (!wrapper.contains(e.target)) {
                                                    closeMenu();
                                                }
                                            });
                                        }

                                        function syncBarberDistricts(cityName) {
                                            var districtSelect = document.getElementById('barberDistrict');
                                            if (!districtSelect) return;

                                            districtSelect.innerHTML = '';
                                            var trimmed = (cityName || '').trim();

                                            if (!trimmed) {
                                                var defaultOpt = document.createElement('option');
                                                defaultOpt.value = '';
                                                defaultOpt.textContent = 'Önce Şehir Seçin';
                                                districtSelect.appendChild(defaultOpt);
                                                return;
                                            }

                                            var normalizedCity = normalizeText(trimmed);
                                            var districts = null;

                                            if (allDistricts && typeof allDistricts === 'object') {
                                                for (var key in allDistricts) {
                                                    if (normalizeText(key) === normalizedCity) {
                                                        districts = allDistricts[key];
                                                        break;
                                                    }
                                                }
                                            }

                                            if (!districts && window.businessSearchOptions && window.businessSearchOptions.locations) {
                                                for (var locKey in window.businessSearchOptions.locations) {
                                                    if (normalizeText(locKey) === normalizedCity) {
                                                        districts = window.businessSearchOptions.locations[locKey];
                                                        break;
                                                    }
                                                }
                                            }

                                            if (!districts && typeof cityDistrictsMap === 'object') {
                                                districts = cityDistrictsMap[normalizedCity] || cityDistrictsMap[trimmed.toLowerCase()];
                                            }

                                            if (districts && districts.length > 0) {
                                                var headOpt = document.createElement('option');
                                                headOpt.value = '';
                                                headOpt.textContent = 'İlçe Seçiniz...';
                                                districtSelect.appendChild(headOpt);

                                                districts.forEach(function(district) {
                                                    var opt = document.createElement('option');
                                                    opt.value = district;
                                                    opt.textContent = district;
                                                    districtSelect.appendChild(opt);
                                                });
                                            } else {
                                                var opt = document.createElement('option');
                                                opt.value = 'Merkez';
                                                opt.textContent = 'Merkez / Diğer';
                                                districtSelect.appendChild(opt);
                                            }
                                        }

                                        function init() {
                                            createCombobox({
                                                inputId: 'custRegCity',
                                                menuId: 'custCityDropdown',
                                                wrapperId: 'custCityCombobox',
                                                toggleBtnId: 'custCityToggleBtn',
                                                labelId: 'custCityLabel'
                                            });

                                            createCombobox({
                                                inputId: 'barberCity',
                                                menuId: 'barberCityDropdown',
                                                wrapperId: 'barberCityCombobox',
                                                toggleBtnId: 'barberCityToggleBtn',
                                                labelId: 'barberCityLabel',
                                                onChange: syncBarberDistricts
                                            });

                                            var currentBarberCity = document.getElementById('barberCity');
                                            if (currentBarberCity && currentBarberCity.value) {
                                                syncBarberDistricts(currentBarberCity.value);
                                            }
                                        }

                                        if (document.readyState === 'loading') {
                                            document.addEventListener('DOMContentLoaded', init);
                                        } else {
                                            init();
                                        }
                                    })();

                                    function togglePassVis(inputId, btn) {
                                        var inp = document.getElementById(inputId);
                                        var icon = btn.querySelector('i');
                                        if (inp.type === 'password') {
                                            inp.type = 'text';
                                            icon.classList.replace('fa-eye', 'fa-eye-slash');
                                        } else {
                                            inp.type = 'password';
                                            icon.classList.replace('fa-eye-slash', 'fa-eye');
                                        }
                                    }
                                    </script>
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
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
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
                                                <a href="/sifre_sifirla.php"
                                                    class="text-xs fw-bold" style="color: var(--accent);">Şifremi
                                                    Unuttum?</a>
                                            </div>
                                            <div class="input-group">
                                                <input type="password" id="barberLoginPass" name="sifre" required
                                                    placeholder="••••••••" class="form-control form-control-lg fs-6"
                                                    autocomplete="current-password">
                                                <button class="btn btn-outline-secondary" type="button"
                                                    onclick="togglePassVis('barberLoginPass', this)" tabindex="-1"
                                                    aria-label="Şifreyi göster/gizle">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-dark btn-lg w-100 fs-6 py-3 mt-2">
                                            <i class="fa-solid fa-scissors me-2"></i>Berber Girişi Yap
                                        </button>
                                    </form>
                                </div>
                                <div class="tab-pane fade" id="barber-reg-pane" role="tabpanel">
                                    <form id="formBarberRegister" method="POST" action="login.php" class="auth-form">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
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
                                                <label id="barberCityLabel"
                                                    class="form-label fw-bold text-xs text-uppercase text-muted"
                                                    style="cursor: pointer; user-select: none;">
                                                    <i class="fa-solid fa-location-dot me-1" style="color:var(--accent);"></i>Şehir</label>
                                                <div class="cust-city-combobox" id="barberCityCombobox">
                                                    <input type="text" id="barberCity" name="sehir" required
                                                        placeholder="Şehir yazın veya seçin..."
                                                        autocomplete="chrome-off"
                                                        data-lpignore="true"
                                                        role="combobox"
                                                        aria-autocomplete="list"
                                                        aria-expanded="false"
                                                        aria-controls="barberCityDropdown"
                                                        class="form-control pe-5">
                                                    <button type="button" class="cust-city-toggle" id="barberCityToggleBtn" tabindex="-1" aria-label="Şehir listesini aç veya kapat">
                                                        <i class="fa-solid fa-chevron-down"></i>
                                                    </button>
                                                    <div id="barberCityDropdown" class="cust-city-dropdown" role="listbox" hidden></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="barberDistrict"
                                                    class="form-label fw-bold text-xs text-uppercase text-muted">
                                                    <i class="fa-solid fa-map-pin me-1" style="color:var(--accent);"></i>İlçe</label>
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
                                            <div class="input-group">
                                                <input type="password" id="barberPass" name="sifre" required minlength="10" maxlength="72" title="Şifre en az 10 karakter uzunluğunda olmalıdır"
                                                    placeholder="••••••••" class="form-control"
                                                    autocomplete="new-password">
                                                <button class="btn btn-outline-secondary" type="button"
                                                    onclick="togglePassVis('barberPass', this)" tabindex="-1"
                                                    aria-label="Şifreyi göster/gizle">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="barberPassConfirm"
                                                class="form-label fw-bold text-xs text-uppercase text-muted">Şifre
                                                Tekrarı</label>
                                            <div class="input-group">
                                                <input type="password" id="barberPassConfirm" name="sifre_tekrar" required
                                                    placeholder="••••••••" class="form-control"
                                                    autocomplete="new-password">
                                                <button class="btn btn-outline-secondary" type="button"
                                                    onclick="togglePassVis('barberPassConfirm', this)" tabindex="-1"
                                                    aria-label="Şifreyi göster/gizle">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                            </div>
                                            <div id="barberPassMatchMsg" class="mt-1" style="font-size:12px;min-height:16px;"></div>
                                        </div>

                                        <div class="mb-3 mt-3 p-3 rounded-3" style="background:#F8FAFC;border:1.5px solid #E2E8F0;">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="kvkk_onay" id="barberKvkkOnay" value="1" required style="cursor:pointer;width:18px;height:18px;accent-color:var(--accent);">
                                                <label class="form-check-label text-xs text-muted ms-1" for="barberKvkkOnay" style="cursor:pointer;line-height:1.45;">
                                                    <a href="/kvkk.php" target="_blank" class="fw-bold text-dark text-decoration-underline" style="color:var(--accent) !important;">KVKK Aydınlatma Metni</a>'ni okudum ve kişisel verilerimin işlenmesi hakkında bilgilendirildim. <span class="text-danger fw-bold">*</span>
                                                </label>
                                                <div id="barberKvkkError" class="text-danger mt-1 fw-semibold" style="font-size:11.5px;display:none;">
                                                    <i class="fa-solid fa-circle-exclamation me-1"></i>Lütfen devam etmek için KVKK metnini onaylayın.
                                                </div>
                                            </div>
                                            <div class="form-check m-0">
                                                <input class="form-check-input" type="checkbox" name="pazarlama_onay" id="barberPazarlamaOnay" value="1" style="cursor:pointer;width:17px;height:17px;accent-color:var(--accent);">
                                                <label class="form-check-label text-xs text-muted ms-1" for="barberPazarlamaOnay" style="cursor:pointer;line-height:1.45;">
                                                    İşletmem için özel avantaj ve duyurular hakkında ticari ileti almayı kabul ediyorum. <span class="badge bg-light text-secondary border" style="font-size:10px;">İsteğe Bağlı</span>
                                                </label>
                                            </div>
                                        </div>

                                        <button type="submit" id="barberRegSubmitBtn" class="btn btn-dark btn-lg w-100 fs-6 py-3 mt-2">
                                            <i class="fa-solid fa-shop me-2"></i>Berber Salonu Hesabı Aç
                                        </button>
                                    </form>

                                    <script>
                                    (function(){
                                        var pass    = document.getElementById('barberPass');
                                        var confirm = document.getElementById('barberPassConfirm');
                                        var msg     = document.getElementById('barberPassMatchMsg');

                                        if (!pass || !confirm || !msg) return;

                                        function checkMatch() {
                                            if (!confirm.value) { msg.textContent = ''; return; }
                                            if (pass.value === confirm.value) {
                                                msg.innerHTML = '<span style="color:#10B981;"><i class="fa-solid fa-circle-check me-1"></i>Şifreler eşleşiyor</span>';
                                                confirm.style.borderColor = '#10B981';
                                            } else {
                                                msg.innerHTML = '<span style="color:#EF4444;"><i class="fa-solid fa-circle-xmark me-1"></i>Şifreler eşleşmiyor</span>';
                                                confirm.style.borderColor = '#EF4444';
                                            }
                                        }

                                        pass.addEventListener('input', checkMatch);
                                        confirm.addEventListener('input', checkMatch);

                                        var form = document.getElementById('formBarberRegister');
                                        if (form) {
                                            form.addEventListener('submit', function(e){
                                                var kvkkCb = document.getElementById('barberKvkkOnay');
                                                var kvkkErr = document.getElementById('barberKvkkError');
                                                if (kvkkCb && !kvkkCb.checked) {
                                                    e.preventDefault();
                                                    if (kvkkErr) kvkkErr.style.display = 'block';
                                                    kvkkCb.focus();
                                                    return;
                                                } else if (kvkkErr) {
                                                    kvkkErr.style.display = 'none';
                                                }

                                                if (pass.value !== confirm.value) {
                                                    e.preventDefault();
                                                    confirm.focus();
                                                    msg.innerHTML = '<span style="color:#EF4444;"><i class="fa-solid fa-circle-xmark me-1"></i>Şifreler eşleşmiyor, kayıt tamamlanamaz!</span>';
                                                }
                                            });
                                        }
                                    })();
                                    </script>
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
