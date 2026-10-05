<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiTıraş | Premium Berber & Erkek Kuaförü Randevu Sistemi</title>
    <meta name="description"
        content="Türkiye'nin en seçkin berber ve erkek kuaförlerinden anında online randevu alın. Sıra beklemeden tarzınızı keşfedin.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">


    <link rel="stylesheet" href="style.css">
</head>

<body>


    <div class="bg-dark text-white text-center py-2 px-3 text-xs fw-semibold border-bottom border-secondary">
        <span class="pulse-dot me-2"></span>
        <span>Sitemiz Yapım Aşamasındadır Yakında Görüşmek Üzere..</span>
    </div>


    <nav class="navbar navbar-expand-lg bg-white navbar-light sticky-top shadow-sm py-3">
        <div class="container">

            <a class="navbar-brand d-flex align-items-center gap-2" href="#">
                <div class="logo-icon">
                    <i class="fa-solid fa-scissors rotate-90"></i>
                </div>
                <div>
                    <span class="fs-4 fw-extrabold text-dark tracking-tight">BiTıraş<span
                            style="color: var(--accent);">.com</span></span>
                    <span class="d-block text-muted uppercase fw-bold"
                        style="font-size: 10px; letter-spacing: 0.5px; margin-top: -3px;">Kolay Berber Randevu
                        Sistemi</span>
                </div>
            </a>

            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-lg-4 font-semibold text-slate">
                    <li class="nav-item">
                        <a class="nav-link text-dark fw-bold" href="#hero">Ana Sayfa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark fw-bold" href="#barbers">Popüler Berberler</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark fw-bold" href="#how-it-works">Nasıl Çalışır?</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark fw-bold" href="#for-business">İşletmeler İçin</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2 pt-3 pt-lg-0">
                    <button class="btn btn-outline-dark px-4 py-2" onclick="openAuthModal('login')">
                        <i class="fa-solid fa-right-to-bracket me-2" style="color: var(--accent);"></i>Giriş Yap
                    </button>
                    <button class="btn btn-primary px-4 py-2" onclick="openAuthModal('register')">
                        <i class="fa-solid fa-user-plus me-2"></i>Kayıt Ol
                    </button>
                </div>
            </div>

        </div>
    </nav>

    <main>

        <section id="hero" class="py-5 lg-py-6 bg-light border-bottom">
            <div class="container">

                <div class="text-center mx-auto mb-5" style="max-width: 900px;">


                    <h1 class="display-4 fw-extrabold text-dark tracking-tight mb-3">
                        Tarzını Keşfet, <br class="d-none d-sm-inline">
                        <span style="color: var(--accent);">Sıra Beklemeden</span> Randevunu Al
                    </h1>
                    <p class="lead text-muted fw-medium mx-auto" style="max-width: 720px;">
                        Şehrinizdeki en iyi erkek kuaförlerini ve VIP berber salonlarını karşılaştırın, dakikalar
                        içinde
                        boş koltuğunuzu ayırtın.
                    </p>
                </div>

                <div class="w-100">
                    <form id="heroSearchForm" onsubmit="handleHeroSearch(event)"
                        class="bg-white p-4 p-md-5 rounded-4 shadow-lg border">
                        <div class="row g-3 align-items-end">


                            <div class="col-md-3">
                                <label for="searchCity" class="form-label text-uppercase text-muted fw-extrabold"
                                    style="font-size: 11px;">
                                    <i class="fa-solid fa-location-dot me-1" style="color: var(--accent);"></i>
                                    Şehir
                                </label>
                                <select id="searchCity" name="city"
                                    onchange="updateDistrictOptions(this.value, 'searchDistrict')"
                                    class="form-select form-select-lg fs-6 fw-semibold">
                                    <option value="">Tüm Şehirler</option>
                                    <option value="istanbul">İstanbul</option>
                                    <option value="ankara">Ankara</option>
                                    <option value="izmir">İzmir</option>
                                    <option value="bursa">Bursa</option>
                                    <option value="antalya">Antalya</option>
                                </select>
                            </div>


                            <div class="col-md-3">
                                <label for="searchDistrict" class="form-label text-uppercase text-muted fw-extrabold"
                                    style="font-size: 11px;">
                                    <i class="fa-solid fa-map-pin me-1" style="color: var(--accent);"></i> İlçe
                                </label>
                                <select id="searchDistrict" name="district"
                                    class="form-select form-select-lg fs-6 fw-semibold">
                                    <option value="">Önce Şehir Seçin</option>
                                </select>
                            </div>


                            <div class="col-md-4">
                                <label for="searchQuery" class="form-label text-uppercase text-muted fw-extrabold"
                                    style="font-size: 11px;">
                                    <i class="fa-solid fa-store me-1" style="color: var(--accent);"></i> Salon veya
                                    Hizmet
                                </label>
                                <input type="text" id="searchQuery" name="search_query"
                                    placeholder="Örn: VIP Kesim, Sakal..." class="form-control form-control-lg fs-6">
                            </div>


                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-lg w-100 fs-6 py-3">
                                    <i class="fa-solid fa-magnifying-glass me-1"></i> Bul
                                </button>
                            </div>

                        </div>
                    </form>


                </div>

                <div class="row g-3 mt-5 w-100 mx-0">
                    <div class="col-6 col-md-3">
                        <div class="card border text-center p-3 rounded-4 shadow-sm h-100">
                            <div class="fs-2 fw-extrabold text-dark">0<span style="color: var(--accent);">+</span>
                            </div>
                            <div class="text-muted fw-semibold" style="font-size: 12px;">Kayıtlı Berber Salonu</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border text-center p-3 rounded-4 shadow-sm h-100">
                            <div class="fs-2 fw-extrabold text-dark">0<span style="color: var(--accent);">+</span>
                            </div>
                            <div class="text-muted fw-semibold" style="font-size: 12px;">Tamamlanan Randevu</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border text-center p-3 rounded-4 shadow-sm h-100">
                            <div class="fs-2 fw-extrabold text-dark">0 <span
                                    style="color: var(--accent); font-size: 16px;">/ 5.0</span></div>
                            <div class="text-muted fw-semibold" style="font-size: 12px;">Müşteri Puan Ortalaması
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card border text-center p-3 rounded-4 shadow-sm h-100">
                            <div class="fs-2 fw-extrabold text-dark"><span style="color: var(--accent);">%</span>100
                            </div>
                            <div class="text-muted fw-semibold" style="font-size: 12px;">Anında Onaylı Randevu</div>
                        </div>
                    </div>
                </div>

            </div>
        </section>
        <section id="barbers" class="py-5 bg-white border-bottom">
            <div class="container py-4">

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-5">
                    <div>
                        <div class="text-uppercase fw-extrabold mb-1"
                            style="color: var(--accent); font-size: 12px; letter-spacing: 1px;">
                            <span class="d-inline-block me-1 rounded-circle"
                                style="width: 8px; height: 8px; background-color: var(--accent);"></span> Vitrin
                            Salonlar
                        </div>
                        <h2 class="fs-2 fw-extrabold text-dark mb-0">Öne Çıkan Berber Salonları</h2>
                        <p class="text-muted mb-0" style="font-size: 14px;">En yüksek puanlı ve müşteriler tarafından en
                            çok tercih edilen kuaförler</p>
                    </div>

                    <div class="d-flex gap-2 overflow-auto pt-3 pt-md-0">
                        <button class="btn btn-dark btn-sm rounded-3 px-3">Tümü</button>
                        <button class="btn btn-outline-dark btn-sm rounded-3 px-3">VIP Salonlar</button>
                        <button class="btn btn-outline-dark btn-sm rounded-3 px-3">En Yüksek Puanlılar</button>
                        <button class="btn btn-outline-dark btn-sm rounded-3 px-3">Gece Açık</button>
                    </div>
                </div>

                <div class="row g-4">

                    <div class="col-lg-4 col-md-6">
                        <div class="card h-100 border rounded-4 overflow-hidden card-hover shadow-sm">
                            <div class="position-relative" style="height: 220px;">
                                <img src="https://images.unsplash.com/photo-1503951914875-452162b0f3f1?q=80&w=800&auto=format&fit=crop"
                                    alt="Makas & Sakal Luxury Cut" class="w-100 h-100 object-fit-cover">
                                <span
                                    class="position-absolute top-0 start-0 m-3 badge badge-dark-custom uppercase tracking-wider">VIP
                                    SALON</span>
                                <span class="position-absolute top-0 end-0 m-3 badge badge-dark-custom">
                                    <i class="fa-solid fa-star me-1" style="color: var(--accent);"></i> 4.9 <span
                                        class="text-muted fw-normal">(142)</span>
                                </span>
                                <span class="position-absolute bottom-0 start-0 m-3 text-white fw-bold text-xs">
                                    <i class="fa-solid fa-location-dot me-1" style="color: var(--accent);"></i>
                                    İstanbul, Kadıköy / Moda
                                </span>
                            </div>
                            <div class="card-body d-flex flex-column justify-between p-4">
                                <div>
                                    <h3 class="fs-5 fw-extrabold text-dark mb-1">Makas & Sakal Luxury Studio</h3>
                                    <p class="text-muted text-xs mb-3">Baş Berberi: <span
                                            class="text-dark fw-semibold">Ahmet Yılmaz (12 Yıl Tecrübe)</span></p>

                                    <div class="pt-3 border-top">
                                        <div class="text-uppercase text-muted fw-extrabold mb-2"
                                            style="font-size: 11px;">Öne Çıkan Hizmetler</div>
                                        <div class="d-flex flex-wrap gap-1">
                                            <span class="badge bg-light text-dark fw-semibold p-2">Saç Kesimi &
                                                Yıkama</span>
                                            <span class="badge bg-light text-dark fw-semibold p-2">Sakal Tasarım</span>
                                            <span class="badge bg-light text-dark fw-semibold p-2">Sıcak Havlu
                                                Bakımı</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-3 border-top d-flex justify-content-between align-items-center mt-4">
                                    <span class="text-muted fw-semibold" style="font-size: 12px;">
                                        <span class="d-inline-block rounded-circle bg-success me-1"
                                            style="width: 8px; height: 8px;"></span> Bugüne Uygun Seans Var
                                    </span>
                                    <button onclick="triggerAppointmentSimulation('Makas & Sakal Luxury Studio')"
                                        class="btn btn-outline-amber btn-sm">
                                        Randevu Al <i class="fa-solid fa-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="card h-100 border rounded-4 overflow-hidden card-hover shadow-sm">
                            <div class="position-relative" style="height: 220px;">
                                <img src="https://images.unsplash.com/photo-1622286342621-4bd786c2447c?q=80&w=800&auto=format&fit=crop"
                                    alt="Vintage Gentlemen Barbershop" class="w-100 h-100 object-fit-cover">
                                <span
                                    class="position-absolute top-0 start-0 m-3 badge badge-dark-custom uppercase tracking-wider">POPÜLER</span>
                                <span class="position-absolute top-0 end-0 m-3 badge badge-dark-custom">
                                    <i class="fa-solid fa-star me-1" style="color: var(--accent);"></i> 4.8 <span
                                        class="text-muted fw-normal">(98)</span>
                                </span>
                                <span class="position-absolute bottom-0 start-0 m-3 text-white fw-bold text-xs">
                                    <i class="fa-solid fa-location-dot me-1" style="color: var(--accent);"></i> Ankara,
                                    Çankaya / Tunalı
                                </span>
                            </div>
                            <div class="card-body d-flex flex-column justify-between p-4">
                                <div>
                                    <h3 class="fs-5 fw-extrabold text-dark mb-1">Gentlemen Classic Barbershop</h3>
                                    <p class="text-muted text-xs mb-3">İşletme Sahibi: <span
                                            class="text-dark fw-semibold">Volkan Can (Klasik Kesim Uzmanı)</span></p>

                                    <div class="pt-3 border-top">
                                        <div class="text-uppercase text-muted fw-extrabold mb-2"
                                            style="font-size: 11px;">Öne Çıkan Hizmetler</div>
                                        <div class="d-flex flex-wrap gap-1">
                                            <span class="badge bg-light text-dark fw-semibold p-2">Ustura Sakal
                                                Tıraşı</span>
                                            <span class="badge bg-light text-dark fw-semibold p-2">Cilt & Maske
                                                Bakımı</span>
                                            <span class="badge bg-light text-dark fw-semibold p-2">Çocuk Kesimi</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-3 border-top d-flex justify-content-between align-items-center mt-4">
                                    <span class="text-muted fw-semibold" style="font-size: 12px;">
                                        <span class="d-inline-block rounded-circle bg-success me-1"
                                            style="width: 8px; height: 8px;"></span> 3 Koltuk Müsait
                                    </span>
                                    <button onclick="triggerAppointmentSimulation('Gentlemen Classic Barbershop')"
                                        class="btn btn-outline-amber btn-sm">
                                        Randevu Al <i class="fa-solid fa-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="card h-100 border rounded-4 overflow-hidden card-hover shadow-sm">
                            <div class="position-relative" style="height: 220px;">
                                <img src="https://images.unsplash.com/photo-1585747860715-2ba37e788b70?q=80&w=800&auto=format&fit=crop"
                                    alt="Ege Premium Hair Design" class="w-100 h-100 object-fit-cover">
                                <span
                                    class="position-absolute top-0 start-0 m-3 badge badge-dark-custom uppercase tracking-wider">YENİ
                                    SALON</span>
                                <span class="position-absolute top-0 end-0 m-3 badge badge-dark-custom">
                                    <i class="fa-solid fa-star me-1" style="color: var(--accent);"></i> 5.0 <span
                                        class="text-muted fw-normal">(34)</span>
                                </span>
                                <span class="position-absolute bottom-0 start-0 m-3 text-white fw-bold text-xs">
                                    <i class="fa-solid fa-location-dot me-1" style="color: var(--accent);"></i> İzmir,
                                    Alsancak / Kordon
                                </span>
                            </div>
                            <div class="card-body d-flex flex-column justify-between p-4">
                                <div>
                                    <h3 class="fs-5 fw-extrabold text-dark mb-1">Ege Premium Hair Studio</h3>
                                    <p class="text-muted text-xs mb-3">Baş Kuaför: <span
                                            class="text-dark fw-semibold">Mert Ege (Fade Specialist)</span></p>

                                    <div class="pt-3 border-top">
                                        <div class="text-uppercase text-muted fw-extrabold mb-2"
                                            style="font-size: 11px;">Öne Çıkan Hizmetler</div>
                                        <div class="d-flex flex-wrap gap-1">
                                            <span class="badge bg-light text-dark fw-semibold p-2">Skin Fade
                                                Kesim</span>
                                            <span class="badge bg-light text-dark fw-semibold p-2">Keratin Bakımı</span>
                                            <span class="badge bg-light text-dark fw-semibold p-2">Damat Paketi</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-3 border-top d-flex justify-content-between align-items-center mt-4">
                                    <span class="text-muted fw-semibold" style="font-size: 12px;">
                                        <i class="fa-regular fa-clock me-1" style="color: var(--accent);"></i> Gece
                                        23:00'a Kadar Açık
                                    </span>
                                    <button onclick="triggerAppointmentSimulation('Ege Premium Hair Studio')"
                                        class="btn btn-outline-amber btn-sm">
                                        Randevu Al <i class="fa-solid fa-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>

            </div>
        </section>

        <section id="how-it-works" class="py-5 bg-light">
            <div class="container py-4">
                <div class="text-center mx-auto mb-5" style="max-width: 700px;">
                    <span class="text-uppercase fw-extrabold d-block mb-1"
                        style="color: var(--accent); font-size: 12px; letter-spacing: 1px;">Kolay ve Hızlı</span>
                    <h2 class="fs-2 fw-extrabold text-dark mb-2">3 Adımda Randevunu Tamamla</h2>
                    <p class="text-muted mb-0">Telefon açmadan, koltuk sırası beklemeden 30 saniyede randevunu ayarla.
                    </p>
                </div>

                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card h-100 border text-center p-4 rounded-4 shadow-sm card-hover">
                            <div class="step-illustration-box mx-auto mb-4" style="width: 100px; height: 100px;">
                                <svg width="100" height="100" viewBox="0 0 120 120" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <rect width="120" height="120" rx="28" fill="#FDF5EC" />
                                    <ellipse cx="60" cy="102" rx="40" ry="5" fill="#EADCCB" />
                                    <rect x="80" y="32" width="16" height="58" rx="8" fill="#1E242B" />
                                    <rect x="83" y="38" width="10" height="46" rx="2" fill="#FFFFFF" />
                                    <path d="M83 44L93 40M83 54L93 50M83 64L93 60M83 74L93 70" stroke="#C28840"
                                        stroke-width="4.5" stroke-linecap="round" />
                                    <rect x="78" y="30" width="20" height="6" rx="3" fill="#1E242B" />
                                    <rect x="78" y="86" width="20" height="6" rx="3" fill="#1E242B" />
                                    <path d="M46 34V88H53V34H46Z" fill="#1E242B" />
                                    <path d="M53 38H61M53 44H61M53 50H61M53 56H61M53 62H61M53 68H61M53 74H61M53 80H61"
                                        stroke="#1E242B" stroke-width="2.5" stroke-linecap="round" />
                                    <circle cx="26" cy="82" r="7" stroke="#1E242B" stroke-width="4.5" fill="none" />
                                    <circle cx="44" cy="82" r="7" stroke="#1E242B" stroke-width="4.5" fill="none" />
                                    <path d="M26 75L48 35" stroke="#1E242B" stroke-width="4.5" stroke-linecap="round" />
                                    <path d="M44 75L22 35" stroke="#1E242B" stroke-width="4.5" stroke-linecap="round" />
                                    <circle cx="35" cy="55" r="3" fill="#C28840" />
                                    <path
                                        d="M60 44C51.1634 44 44 51.1634 44 60C44 72 60 90 60 90C60 90 76 72 76 60C76 51.1634 68.8366 44 60 44Z"
                                        fill="#C28840" stroke="#1E242B" stroke-width="3.5" stroke-linejoin="round" />
                                    <circle cx="60" cy="58" r="7.5" fill="#FFFFFF" />
                                    <path d="M56 58L59 61L64 55" stroke="#1E242B" stroke-width="2.5"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M22 42L16 40M20 50L14 52" stroke="#C28840" stroke-width="2.5"
                                        stroke-linecap="round" />
                                </svg>
                            </div>
                            <h3 class="fs-5 fw-bold text-dark mb-2">Berberini ve Hizmeti Seç</h3>
                            <p class="text-muted text-sm mb-0">Bulunduğun konuma en yakın berberleri incele, alacağın
                                hizmetleri ve kuaförünü seç.</p>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card h-100 border text-center p-4 rounded-4 shadow-sm card-hover">
                            <div class="step-illustration-box mx-auto mb-4" style="width: 100px; height: 100px;">
                                <svg width="100" height="100" viewBox="0 0 120 120" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <rect width="120" height="120" rx="28" fill="#FDF5EC" />
                                    <ellipse cx="60" cy="102" rx="42" ry="5" fill="#EADCCB" />
                                    <path
                                        d="M24 38C24 33.5817 27.5817 30 32 30H84C88.4183 30 92 33.5817 92 38V82C92 86.4183 88.4183 90 84 90H32C27.5817 90 24 86.4183 24 82V38Z"
                                        fill="#1E242B" />
                                    <rect x="30" y="42" width="56" height="42" rx="4" fill="#FFFFFF" />
                                    <rect x="42" y="24" width="6" height="12" rx="3" fill="#C28840" />
                                    <rect x="68" y="24" width="6" height="12" rx="3" fill="#C28840" />
                                    <rect x="66" y="50" width="8" height="8" rx="2" fill="#C28840" />
                                    <path d="M68 54L70 56L73 52" stroke="#FFFFFF" stroke-width="1.8"
                                        stroke-linecap="round" />
                                    <rect x="52" y="62" width="8" height="8" rx="2" fill="#C28840" />
                                    <path d="M54 66L56 68L59 64" stroke="#FFFFFF" stroke-width="1.8"
                                        stroke-linecap="round" />
                                    <rect x="38" y="50" width="8" height="8" rx="2" fill="#E2E8F0" />
                                    <rect x="52" y="50" width="8" height="8" rx="2" fill="#E2E8F0" />
                                    <rect x="38" y="62" width="8" height="8" rx="2" fill="#E2E8F0" />
                                    <circle cx="76" cy="74" r="21" fill="#FFFFFF" stroke="#1E242B" stroke-width="4.5" />
                                    <circle cx="76" cy="74" r="17" fill="none" stroke="#C28840" stroke-width="2.5" />
                                    <path d="M76 74V62M76 74L84 79" stroke="#1E242B" stroke-width="3"
                                        stroke-linecap="round" />
                                    <circle cx="76" cy="74" r="2.5" fill="#1E242B" />
                                    <path d="M22 28L18 24M28 22L26 16M98 56L104 54M96 46L102 42" stroke="#C28840"
                                        stroke-width="2.5" stroke-linecap="round" />
                                </svg>
                            </div>
                            <h3 class="fs-5 fw-bold text-dark mb-2">Tarih ve Saati Belirle</h3>
                            <p class="text-muted text-sm mb-0">Berberin canlı takviminden sana en uygun boş saat
                                dilimini tek tıkla işaretle.</p>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card h-100 border text-center p-4 rounded-4 shadow-sm card-hover">
                            <div class="step-illustration-box mx-auto mb-4" style="width: 100px; height: 100px;">
                                <svg width="100" height="100" viewBox="0 0 120 120" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <rect width="120" height="120" rx="28" fill="#FDF5EC" />
                                    <ellipse cx="60" cy="102" rx="40" ry="5" fill="#EADCCB" />
                                    <rect x="42" y="22" width="40" height="74" rx="10" fill="#1E242B" stroke="#1E242B"
                                        stroke-width="2" />
                                    <rect x="46" y="28" width="32" height="62" rx="6" fill="#2D3748" />
                                    <circle cx="62" cy="85" r="2" fill="#A0AEC0" />
                                    <circle cx="34" cy="58" r="16" fill="#C28840" stroke="#FFFFFF" stroke-width="2.5" />
                                    <path d="M26 58L31.5 63.5L42 53" stroke="#FFFFFF" stroke-width="3.5"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                    <rect x="58" y="26" width="30" height="22" rx="7" fill="#C28840" />
                                    <path d="M64 48L68 53L70 48H64Z" fill="#C28840" />
                                    <path d="M67 37L71 41L79 33" stroke="#FFFFFF" stroke-width="2.5"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M68 58H88V72C88 81 78 87 78 87C78 87 68 81 68 72V58Z" fill="#C28840"
                                        stroke="#FFFFFF" stroke-width="2" />
                                    <path d="M73 71L76.5 74.5L83 67" stroke="#FFFFFF" stroke-width="2.5"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M88 22C92 24 95 28 95 32M93 17C99 20 102 26 102 32" stroke="#C28840"
                                        stroke-width="2.5" stroke-linecap="round" />
                                    <path d="M18 36L14 34M24 28L22 23" stroke="#C28840" stroke-width="2.5"
                                        stroke-linecap="round" />
                                </svg>
                            </div>
                            <h3 class="fs-5 fw-bold text-dark mb-2">Randevunu Onayla</h3>
                            <p class="text-muted text-sm mb-0">SMS ile gelen doğrulama koduyla randevun anında berberin
                                ekranına düşsün.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="for-business" class="py-5 bg-dark text-white">
            <div class="container py-3">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">

                        <h2 class="fs-2 fw-extrabold text-white mb-2">Salonunuzu BiTıraş.com'a Katın, Müşteri
                            Doluluğunuzu Artırın!</h2>
                        <p class="text-secondary mb-0" style="max-width: 640px;">
                            Randevu telefonları yanıtlamaktan kurtulun. Müşterileriniz 7/24 online randevu alsın, koltuk
                            takviminizi akıllı panelimizle zahmetsizce yönetin.
                        </p>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <button onclick="openAuthModal('register', 'barber')" class="btn btn-primary btn-lg px-4 py-3">
                            <i class="fa-solid fa-store me-2"></i>İşletme Hesabı Oluştur
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-dark text-secondary py-5 border-top border-secondary">
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-lg-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="logo-icon"><i class="fa-solid fa-scissors rotate-90"></i></div>
                        <span class="fs-4 fw-extrabold text-white">BiTıraş<span
                                style="color: var(--accent);">.com</span></span>
                    </div>
                    <p class="text-secondary text-sm" style="max-width: 320px;">
                        Sitemiz Yapım Aşamasındadır Yakında Görüşmek Üzere..
                    </p>
                </div>
                <div class="col-6 col-lg-2">
                    <h5 class="text-white text-uppercase fs-6 fw-bold mb-3">Hızlı Bağlantılar</h5>
                    <ul class="list-unstyled text-sm space-y-2">
                        <li><a href="#hero" class="text-secondary text-decoration-none">Ana Sayfa</a></li>
                        <li><a href="#barbers" class="text-secondary text-decoration-none">Vitrin Berberler</a></li>
                        <li><a href="#how-it-works" class="text-secondary text-decoration-none">Sistem Nasıl
                                Çalışır?</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-2">
                    <h5 class="text-white text-uppercase fs-6 fw-bold mb-3">İşletmeler</h5>
                    <ul class="list-unstyled text-sm space-y-2">
                        <li><a href="javascript:void(0)" onclick="openAuthModal('login', 'barber')"
                                class="text-secondary text-decoration-none">Berber Giriş Paneli</a></li>
                        <li><a href="javascript:void(0)" onclick="openAuthModal('register', 'barber')"
                                class="text-secondary text-decoration-none">Salon Kaydı Oluştur</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h5 class="text-white text-uppercase fs-6 fw-bold mb-3">İletişim & Destek</h5>
                    <p class="text-secondary text-sm mb-1">E-posta: destek@BiTıraş.com</p>
                    <p class="text-secondary text-sm mb-0">Telefon: 0505 508 5158</p>
                </div>
            </div>

            <div
                class="pt-4 border-top border-secondary d-flex flex-column flex-sm-row justify-content-between align-items-center text-xs">
                <p class="mb-2 mb-sm-0">&copy; 2026 BiTıraş.com - Tüm Hakları Saklıdır. (Sistem Analizi Proje Ödevi)</p>
                <div class="fw-bold" style="color: var(--accent);">
                    <i class="fa-solid fa-code me-1"></i> Projected By SAT Team
                </div>
            </div>
        </div>
    </footer>
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
                                    <form id="formCustomerLogin" onsubmit="handleFormSubmit(event, 'Customer Login')"
                                        class="auth-form">
                                        <input type="hidden" name="role" value="customer">

                                        <div class="mb-3">
                                            <label for="custLoginEmail"
                                                class="form-label fw-bold text-xs text-uppercase text-muted">E-Posta
                                                Adresi</label>
                                            <input type="email" id="custLoginEmail" name="email" required
                                                placeholder="ornek@musteri.com"
                                                class="form-control form-control-lg fs-6">
                                        </div>

                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label for="custLoginPass"
                                                    class="form-label fw-bold text-xs text-uppercase text-muted">Şifre</label>
                                                <a href="javascript:void(0)"
                                                    onclick="alert('Şifre sıfırlama bağlantısı simüle edildi.')"
                                                    class="text-xs fw-bold" style="color: var(--accent);">Şifremi
                                                    Unuttum?</a>
                                            </div>
                                            <input type="password" id="custLoginPass" name="password" required
                                                placeholder="••••••••" class="form-control form-control-lg fs-6">
                                        </div>

                                        <button type="submit" class="btn btn-primary btn-lg w-100 fs-6 py-3 mt-2">
                                            <i class="fa-solid fa-right-to-bracket me-2"></i>Müşteri Girişi Yap
                                        </button>
                                    </form>
                                </div>

                                <div class="tab-pane fade" id="cust-reg-pane" role="tabpanel">
                                    <form id="formCustomerRegister"
                                        onsubmit="handleFormSubmit(event, 'Customer Register')" class="auth-form">
                                        <input type="hidden" name="role" value="customer">

                                        <div class="mb-3">
                                            <label for="custRegName"
                                                class="form-label fw-bold text-xs text-uppercase text-muted">Ad
                                                Soyad</label>
                                            <input type="text" id="custRegName" name="full_name" required
                                                placeholder="Ahmet Yılmaz" class="form-control form-control-lg fs-6">
                                        </div>

                                        <div class="mb-3">
                                            <label for="custRegPhone"
                                                class="form-label fw-bold text-xs text-uppercase text-muted">Telefon
                                                Numarası</label>
                                            <input type="tel" id="custRegPhone" name="phone" required
                                                placeholder="05XX XXX XX XX" class="form-control form-control-lg fs-6">
                                        </div>

                                        <div class="mb-3">
                                            <label for="custRegEmail"
                                                class="form-label fw-bold text-xs text-uppercase text-muted">E-Posta
                                                Adresi</label>
                                            <input type="email" id="custRegEmail" name="email" required
                                                placeholder="musteri@mail.com"
                                                class="form-control form-control-lg fs-6">
                                        </div>

                                        <div class="mb-3">
                                            <label for="custRegPass"
                                                class="form-label fw-bold text-xs text-uppercase text-muted">Şifre
                                                Belirleyin</label>
                                            <input type="password" id="custRegPass" name="password" required
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
                                    <form id="formBarberLogin" onsubmit="handleFormSubmit(event, 'Barber Login')"
                                        class="auth-form">
                                        <input type="hidden" name="role" value="barber">

                                        <div class="mb-3">
                                            <label for="barberLoginUser"
                                                class="form-label fw-bold text-xs text-uppercase text-muted">İşletme
                                                E-Postası veya Telefon</label>
                                            <input type="text" id="barberLoginUser" name="business_login" required
                                                placeholder="isletme@berber.com veya 05XX..."
                                                class="form-control form-control-lg fs-6">
                                        </div>

                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <label for="barberLoginPass"
                                                    class="form-label fw-bold text-xs text-uppercase text-muted">Şifre</label>
                                                <a href="javascript:void(0)"
                                                    onclick="alert('İşletme şifre sıfırlama simüle edildi.')"
                                                    class="text-xs fw-bold" style="color: var(--accent);">Şifremi
                                                    Unuttum?</a>
                                            </div>
                                            <input type="password" id="barberLoginPass" name="password" required
                                                placeholder="••••••••" class="form-control form-control-lg fs-6">
                                        </div>

                                        <button type="submit" class="btn btn-dark btn-lg w-100 fs-6 py-3 mt-2">
                                            <i class="fa-solid fa-scissors me-2"></i>Berber Girişi Yap
                                        </button>
                                    </form>
                                </div>
                                <div class="tab-pane fade" id="barber-reg-pane" role="tabpanel">
                                    <form id="formBarberRegister" onsubmit="handleFormSubmit(event, 'Barber Register')"
                                        class="auth-form">
                                        <input type="hidden" name="role" value="barber">

                                        <div class="row g-2 mb-3">
                                            <div class="col-md-6">
                                                <label for="barberOwnerName"
                                                    class="form-label fw-bold text-xs text-uppercase text-muted">Yetkili
                                                    Ad Soyad</label>
                                                <input type="text" id="barberOwnerName" name="owner_name" required
                                                    placeholder="Mehmet Usta" class="form-control">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="barberShopName"
                                                    class="form-label fw-bold text-xs text-uppercase text-muted">Salon /
                                                    Berber Adı</label>
                                                <input type="text" id="barberShopName" name="shop_name" required
                                                    placeholder="Stil Erkek Kuaförü" class="form-control">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="barberPhone"
                                                class="form-label fw-bold text-xs text-uppercase text-muted">İşletme
                                                Telefon Numarası</label>
                                            <input type="tel" id="barberPhone" name="phone" required
                                                placeholder="0212 XXX XX XX veya 05XX..." class="form-control">
                                        </div>

                                        <div class="row g-2 mb-3">
                                            <div class="col-md-6">
                                                <label for="barberCity"
                                                    class="form-label fw-bold text-xs text-uppercase text-muted">Şehir</label>
                                                <select id="barberCity" name="city" required
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
                                                <select id="barberDistrict" name="district" required
                                                    class="form-select">
                                                    <option value="">Önce Şehir Seçin</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="barberTaxNo"
                                                class="form-label fw-bold text-xs text-uppercase text-muted">Vergi /
                                                Esnaf Sicil No</label>
                                            <input type="text" id="barberTaxNo" name="tax_number" required
                                                placeholder="1234567890" class="form-control">
                                        </div>

                                        <div class="mb-3">
                                            <label for="barberPass"
                                                class="form-label fw-bold text-xs text-uppercase text-muted">Şifre
                                                Belirleyin</label>
                                            <input type="password" id="barberPass" name="password" required
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

    <div id="toastContainer" class="toast-container-custom"></div>


    <div class="modal fade" id="dataPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg p-4">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <div class="fw-bold text-dark fs-6 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-database" style="color: var(--accent);"></i> Backend Entegrasyon Veri
                        Paketi
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <p class="text-muted text-xs mb-2">
                    BackEnd Hazır Değil (FormData verileri yakalandı ve konsola aktarıldı):
                </p>

                <div class="bg-dark p-3 rounded-3 font-mono text-xs overflow-auto" style="color: var(--accent);">
                    <pre id="dataPreviewContent" class="mb-0">{}</pre>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <button type="button" class="btn btn-dark btn-sm px-4" data-bs-dismiss="modal">Tamam,
                        Anlaşıldı</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    <script src="app.js"></script>
</body>

</html>