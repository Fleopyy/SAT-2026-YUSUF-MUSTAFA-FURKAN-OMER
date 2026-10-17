<?php
$customerPageTitle = $customerPageTitle ?? 'Hesabım';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= customerEscape($customerPageTitle) ?> — BiTıraş</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#F8FAFC; color:#1E293B; font-family:'Plus Jakarta Sans',sans-serif; }
        .customer-shell { max-width:1040px; margin:0 auto; padding:32px 20px 64px; }
        .customer-topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom:28px; }
        .customer-brand { color:#14253D; font-weight:800; font-size:18px; text-decoration:none; }
        .customer-nav { display:flex; gap:8px; }
        .customer-nav a { color:#475569; padding:9px 14px; border-radius:10px; text-decoration:none; font-size:14px; font-weight:700; }
        .customer-nav a.active,.customer-nav a:hover { background:#fff; color:#A66A2C; box-shadow:0 2px 12px #0f172a0d; }
        .customer-title { font-size:28px; font-weight:800; letter-spacing:-.5px; margin:0; }
        .customer-subtitle { color:#64748B; margin-top:6px; }
        .customer-card { background:#fff; border:1px solid #E7ECF2; border-radius:18px; box-shadow:0 5px 22px #0f172a08; }
        .customer-notice { border-radius:12px; padding:13px 16px; margin:20px 0; }
        .customer-notice.success { color:#166534; background:#F0FDF4; border:1px solid #BBF7D0; }
        .customer-notice.error { color:#991B1B; background:#FEF2F2; border:1px solid #FECACA; }
        .customer-muted { color:#64748B; }
        .customer-button { border:0; border-radius:10px; padding:10px 16px; color:#fff; background:#B9813E; font-weight:700; }
        .customer-button:hover { background:#A66A2C; color:#fff; }
        @media(max-width:600px) { .customer-shell { padding:20px 14px 42px; } .customer-topbar { align-items:flex-start; flex-direction:column; } .customer-title { font-size:24px; } }
    </style>
</head>
<body>
<main class="customer-shell">
    <header class="customer-topbar">
        <a class="customer-brand" href="/">BiTıraş</a>
        <nav class="customer-nav" aria-label="Müşteri hesabı">
            <a href="/musteri/randevularim.php" class="<?= ($customerActivePage ?? '') === 'appointments' ? 'active' : '' ?>">Randevularım</a>
            <a href="/musteri/hesabim.php" class="<?= ($customerActivePage ?? '') === 'account' ? 'active' : '' ?>">Hesabım</a>
        </nav>
    </header>
    <?php if ($customerFlash): ?>
        <div class="customer-notice <?= customerEscape($customerFlash['type']) ?>" role="status"><?= customerEscape($customerFlash['message']) ?></div>
    <?php endif; ?>
