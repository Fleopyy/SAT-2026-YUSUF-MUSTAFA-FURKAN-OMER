<?php
function businessCanBook(PDO $pdo, int $businessId, int $employeeId, int $serviceId, string $start, ?int $excludeAppointmentId = null): array {
    $begin = DateTime::createFromFormat('!Y-m-d H:i', $start);
    $dateErrors = DateTime::getLastErrors();
    if (!$begin || ($dateErrors && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)) || $begin->format('Y-m-d H:i') !== $start || $begin < new DateTime()) {
        return [false, 'Geçerli ve ileri bir randevu saati seçin.'];
    }
    $stmt = $pdo->prepare('SELECT sure_dakika FROM hizmetler WHERE id = ? AND isletme_id = ? AND aktif_mi = 1');
    $stmt->execute([$serviceId, $businessId]);
    $duration = $stmt->fetchColumn();
    if ($duration === false) {
        return [false, 'Seçilen hizmet aktif değil veya işletmenize ait değil.'];
    }
    $end = (clone $begin)->modify('+' . (int)$duration . ' minutes');
    $weekday = (int)$begin->format('N');
    $date = $begin->format('Y-m-d');
    $startTime = $begin->format('H:i:s');
    $endTime = $end->format('H:i:s');

    $stmt = $pdo->prepare('SELECT kapali, baslangic, bitis FROM isletme_ozel_calisma_gunleri WHERE isletme_id = ? AND tarih = ?');
    $stmt->execute([$businessId, $date]);
    $special = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($special && (int)$special['kapali'] === 1) {
        return [false, 'İşletme seçilen tarihte kapalı.'];
    }
    if ($special) {
        $businessOpen = $startTime >= $special['baslangic'] && $endTime <= $special['bitis'];
    } else {
        $stmt = $pdo->prepare('SELECT baslangic, bitis, mola_baslangic, mola_bitis FROM isletme_calisma_araliklari WHERE isletme_id = ? AND haftanin_gunu = ?');
        $stmt->execute([$businessId, $weekday]);
        $businessOpen = false;
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $period) {
            $within = $startTime >= $period['baslangic'] && $endTime <= $period['bitis'];
            $duringBreak = $period['mola_baslangic'] !== null
                && $startTime < $period['mola_bitis'] && $endTime > $period['mola_baslangic'];
            if ($within && !$duringBreak) {
                $businessOpen = true;
                break;
            }
        }
    }
    if (!$businessOpen) {
        return [false, 'Seçilen saat işletmenin çalışma saatleri dışında.'];
    }

    if ($employeeId > 0) {
        $stmt = $pdo->prepare('SELECT id FROM calisanlar WHERE id = ? AND isletme_id = ? AND aktif_mi = 1');
        $stmt->execute([$employeeId, $businessId]);
        if (!$stmt->fetchColumn()) {
            return [false, 'Seçilen çalışan aktif değil veya işletmenize ait değil.'];
        }
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM hizmet_calisanlari WHERE hizmet_id = ? AND calisan_id = ?');
        $stmt->execute([$serviceId, $employeeId]);
        if ((int)$stmt->fetchColumn() === 0) {
            return [false, 'Seçilen çalışan bu hizmet için atanmadı.'];
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM calisan_calisma_araliklari WHERE calisan_id = ? AND haftanin_gunu = ? AND baslangic <= ? AND bitis >= ?');
        $stmt->execute([$employeeId, $weekday, $startTime, $endTime]);
        if ((int)$stmt->fetchColumn() === 0) {
            return [false, 'Çalışan bu saat aralığında çalışmıyor.'];
        }
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM calisan_izinleri WHERE calisan_id = ? AND tarih = ? AND (baslangic IS NULL OR (? < bitis AND ? > baslangic))');
        $stmt->execute([$employeeId, $date, $startTime, $endTime]);
        if ((int)$stmt->fetchColumn() > 0) {
            return [false, 'Çalışan seçilen tarih veya saat için izinli.'];
        }
    }

    $sql = "SELECT COUNT(*) FROM randevular r
            LEFT JOIN randevu_ek_bilgileri x ON x.randevu_id = r.id
            LEFT JOIN hizmetler h ON h.id = r.hizmet_id
            WHERE r.isletme_id = ? AND r.calisan_id " . ($employeeId > 0 ? '= ?' : 'IS NULL') . " AND r.durum IN ('pending', 'confirmed')
              AND r.randevu_zamani < ? AND COALESCE(x.bitis_zamani, DATE_ADD(r.randevu_zamani, INTERVAL COALESCE(h.sure_dakika, 30) MINUTE)) > ?";
    $params = [$businessId];
    if ($employeeId > 0) {
        $params[] = $employeeId;
    }
    $params[] = $end->format('Y-m-d H:i:s');
    $params[] = $begin->format('Y-m-d H:i:s');
    if ($excludeAppointmentId !== null) {
        $sql .= ' AND r.id <> ?';
        $params[] = $excludeAppointmentId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    if ((int)$stmt->fetchColumn() > 0) {
        return [false, 'Çalışanın seçilen saat aralığında başka bir randevusu bulunuyor.'];
    }

    return [true, $end->format('Y-m-d H:i:s')];
}

function businessAvailableSlots(PDO $pdo, int $businessId, int $employeeId, int $serviceId, string $date): array {
    $parsedDate = DateTime::createFromFormat('!Y-m-d', $date);
    $dateErrors = DateTime::getLastErrors();
    if (!$parsedDate || ($dateErrors && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)) || $parsedDate->format('Y-m-d') !== $date || $date < date('Y-m-d')) {
        return [];
    }

    $stmt = $pdo->prepare('SELECT kapali, baslangic, bitis FROM isletme_ozel_calisma_gunleri WHERE isletme_id = ? AND tarih = ?');
    $stmt->execute([$businessId, $date]);
    $special = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($special && (int)$special['kapali'] === 1) {
        return [];
    }

    $weekday = (int)$parsedDate->format('N');
    if ($special) {
        $periods = [['baslangic' => $special['baslangic'], 'bitis' => $special['bitis']]];
    } else {
        $stmt = $pdo->prepare('SELECT baslangic, bitis, mola_baslangic, mola_bitis FROM isletme_calisma_araliklari WHERE isletme_id = ? AND haftanin_gunu = ? ORDER BY baslangic');
        $stmt->execute([$businessId, $weekday]);
        $periods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($employeeId > 0) {
        $stmt = $pdo->prepare('SELECT baslangic, bitis FROM calisan_calisma_araliklari WHERE calisan_id = ? AND haftanin_gunu = ? ORDER BY baslangic');
        $stmt->execute([$employeeId, $weekday]);
        $employeePeriods = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $eligiblePeriods = [];
        foreach ($periods as $businessPeriod) {
            foreach ($employeePeriods as $employeePeriod) {
                $start = max($businessPeriod['baslangic'], $employeePeriod['baslangic']);
                $end = min($businessPeriod['bitis'], $employeePeriod['bitis']);
                if ($start >= $end) {
                    continue;
                }
                if (!empty($businessPeriod['mola_baslangic'])
                    && $businessPeriod['mola_baslangic'] < $end
                    && $businessPeriod['mola_bitis'] > $start) {
                    if ($start < $businessPeriod['mola_baslangic']) {
                        $eligiblePeriods[] = ['baslangic' => $start, 'bitis' => $businessPeriod['mola_baslangic']];
                    }
                    if ($businessPeriod['mola_bitis'] < $end) {
                        $eligiblePeriods[] = ['baslangic' => $businessPeriod['mola_bitis'], 'bitis' => $end];
                    }
                    continue;
                }
                $eligiblePeriods[] = ['baslangic' => $start, 'bitis' => $end];
            }
        }
    } else {
        $eligiblePeriods = $periods;
    }

    $slots = [];
    foreach ($eligiblePeriods as $period) {
        $slot = new DateTime($date . ' ' . $period['baslangic']);
        $periodEnd = new DateTime($date . ' ' . $period['bitis']);
        while ($slot < $periodEnd) {
            $slotValue = $slot->format('H:i');
            [$available] = businessCanBook($pdo, $businessId, $employeeId, $serviceId, $date . ' ' . $slotValue);
            $slots[] = ['time' => $slotValue, 'available' => $available];
            $slot->modify('+30 minutes');
        }
    }

    return $slots;
}
