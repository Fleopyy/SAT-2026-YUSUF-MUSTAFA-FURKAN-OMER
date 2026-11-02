-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: bitiras
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `bildirimler`
--

DROP TABLE IF EXISTS `bildirimler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bildirimler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hedef_yonetici_id` int(11) DEFAULT NULL,
  `baslik` varchar(255) NOT NULL,
  `mesaj` text NOT NULL,
  `tur` varchar(50) NOT NULL DEFAULT 'info',
  `link` varchar(255) DEFAULT NULL,
  `okundu` tinyint(1) NOT NULL DEFAULT 0,
  `olusturulma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_bildirim_hedef` (`hedef_yonetici_id`),
  KEY `idx_bildirim_okundu` (`okundu`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bildirimler`
--

LOCK TABLES `bildirimler` WRITE;
/*!40000 ALTER TABLE `bildirimler` DISABLE KEYS */;
INSERT INTO `bildirimler` VALUES (1,NULL,'Yeni İşletme Başvurusu: stil','mehmet usta (0505 508 5158) yeni bir berber işletmesi kaydı oluşturdu. Onaylamak için tıklayın.','info','/admin/pages/applications.php?detail=5',0,'2026-10-16 23:36:20'),(3,NULL,'İşletme Başvurusu Reddedildi','Test Onay Bekleyen Berber Salonu başvurusu reddedildi. Gerekçe: Eksik işletme belgeleri ve doğrulanamayan telefon numarası.','warning',NULL,0,'2026-10-16 23:44:19'),(5,NULL,'İşletme Başvurusu Reddedildi','Test Onay Bekleyen Berber Salonu başvurusu reddedildi. Gerekçe: Eksik işletme belgeleri ve doğrulanamayan telefon numarası.','warning',NULL,0,'2026-10-16 23:45:28'),(6,NULL,'İşletme Onaylandı','stil başlıklı işletme başvurusu onaylandı ve yayına alındı.','success','/admin/pages/businesses.php?id=5',0,'2026-10-16 23:54:17'),(7,NULL,'Yeni İşletme Başvurusu: stil','mehmet usta (05532325574) yeni bir berber işletmesi kaydı oluşturdu. Onaylamak için tıklayın.','info','/admin/pages/applications.php?detail=9',0,'2026-10-16 23:55:46'),(8,NULL,'İşletme Onaylandı','stil başlıklı işletme başvurusu onaylandı ve yayına alındı.','success','/admin/pages/businesses.php?id=9',0,'2026-10-16 23:56:01'),(9,NULL,'İşletme Başvurusu Reddedildi','stil başvurusu reddedildi. Gerekçe: Telefon Numarası Yanlış','warning',NULL,0,'2026-10-16 23:56:40'),(10,NULL,'Yeni İşletme Başvurusu: Deneme berber salonu','Mustafa Açıkgöz (05467209327) yeni bir berber işletmesi kaydı oluşturdu. Onaylamak için tıklayın.','info','/admin/pages/applications.php?detail=10',0,'2026-10-17 16:13:34'),(11,NULL,'İşletme Onaylandı','Deneme berber salonu başlıklı işletme başvurusu onaylandı ve yayına alındı.','success','/admin/pages/businesses.php?id=10',0,'2026-10-17 16:13:51'),(13,NULL,'İşletme Başvurusu Reddedildi','Test Onay Bekleyen Berber Salonu başvurusu reddedildi. Gerekçe: Eksik işletme belgeleri ve doğrulanamayan telefon numarası.','warning','/admin/pages/applications.php?tab=rejected&detail=79',0,'2026-10-19 12:52:06'),(15,NULL,'İşletme Başvurusu Reddedildi','Test Onay Bekleyen Berber Salonu başvurusu reddedildi. Gerekçe: Eksik işletme belgeleri ve doğrulanamayan telefon numarası.','warning','/admin/pages/applications.php?tab=rejected&detail=86',0,'2026-10-19 13:19:50'),(17,NULL,'İşletme Başvurusu Reddedildi','Test Onay Bekleyen Berber Salonu başvurusu reddedildi. Gerekçe: Eksik işletme belgeleri ve doğrulanamayan telefon numarası.','warning','/admin/pages/applications.php?tab=rejected&detail=93',0,'2026-10-19 20:08:14'),(18,NULL,'İşletme yorumu bildirimi','Deneme berber salonu bir müşteri yorumunun incelenmesini istedi.','review_report','/admin/pages/customers.php',0,'2026-10-19 20:27:54'),(20,NULL,'İşletme Başvurusu Reddedildi','Test Onay Bekleyen Berber Salonu başvurusu reddedildi. Gerekçe: Eksik işletme belgeleri ve doğrulanamayan telefon numarası.','warning','/admin/pages/applications.php?tab=rejected&detail=100',0,'2026-10-19 20:39:05'),(22,NULL,'İşletme Başvurusu Reddedildi','Test Onay Bekleyen Berber Salonu başvurusu reddedildi. Gerekçe: Eksik işletme belgeleri ve doğrulanamayan telefon numarası.','warning','/admin/pages/applications.php?tab=rejected&detail=109',0,'2026-10-26 22:13:42'),(24,NULL,'İşletme Başvurusu Reddedildi','Test Onay Bekleyen Berber Salonu başvurusu reddedildi. Gerekçe: Eksik işletme belgeleri ve doğrulanamayan telefon numarası.','warning','/admin/pages/applications.php?tab=rejected&detail=112',0,'2026-10-26 22:15:48'),(26,NULL,'İşletme Başvurusu Reddedildi','Test Onay Bekleyen Berber Salonu başvurusu reddedildi. Gerekçe: Eksik işletme belgeleri ve doğrulanamayan telefon numarası.','warning','/admin/pages/applications.php?tab=rejected&detail=119',0,'2026-10-26 22:24:31'),(28,NULL,'İşletme Başvurusu Reddedildi','Test Onay Bekleyen Berber Salonu başvurusu reddedildi. Gerekçe: Eksik işletme belgeleri ve doğrulanamayan telefon numarası.','warning','/admin/pages/applications.php?tab=rejected&detail=122',0,'2026-10-26 22:29:18'),(29,NULL,'İşletme Başvurusu Reddedildi','Test Randevu Berber başvurusu reddedildi. Gerekçe: sdfsd','warning',NULL,0,'2026-10-28 19:14:19'),(30,NULL,'İşletme Başvurusu Reddedildi','Test Randevu Berber başvurusu reddedildi. Gerekçe: vdfg','warning',NULL,0,'2026-10-28 19:14:25'),(31,NULL,'İşletme Başvurusu Reddedildi','Test Randevu Berber başvurusu reddedildi. Gerekçe: fsdfsd','warning',NULL,0,'2026-10-28 19:14:29'),(32,NULL,'İşletme Başvurusu Reddedildi','Test Randevu Berber başvurusu reddedildi. Gerekçe: fdsfsdf','warning',NULL,0,'2026-10-28 19:14:32'),(34,NULL,'İşletme Başvurusu Reddedildi','Test Onay Bekleyen Berber Salonu başvurusu reddedildi. Gerekçe: Eksik işletme belgeleri ve doğrulanamayan telefon numarası.','warning','/admin/pages/applications.php?tab=rejected&detail=136',0,'2026-10-30 01:46:56'),(35,NULL,'Yeni İşletme Başvurusu: Yılmaz Berber Salonu','Yılmaz (0505 508 5158) yeni bir berber işletmesi kaydı oluşturdu. Onaylamak için tıklayın.','info','/admin/pages/applications.php?detail=137',0,'2026-10-30 01:47:31'),(36,NULL,'İşletme Onaylandı','Yılmaz Berber Salonu başlıklı işletme başvurusu onaylandı ve yayına alındı.','success','/admin/pages/businesses.php?id=137',0,'2026-10-30 01:49:21'),(38,NULL,'İşletme Başvurusu Reddedildi','Test Onay Bekleyen Berber Salonu başvurusu reddedildi. Gerekçe: Eksik işletme belgeleri ve doğrulanamayan telefon numarası.','warning','/admin/pages/applications.php?tab=rejected&detail=150',0,'2026-10-30 09:38:11'),(39,NULL,'Yeni İşletme Başvurusu: Çetinkaya Berber Salonu','ömer çetinkaya (05010590721) yeni bir berber işletmesi kaydı oluşturdu. Onaylamak için tıklayın.','info','/admin/pages/applications.php?detail=171',0,'2026-10-30 11:49:07'),(40,NULL,'İşletme Onaylandı','Çetinkaya Berber Salonu başlıklı işletme başvurusu onaylandı ve yayına alındı.','success','/admin/pages/businesses.php?id=171',0,'2026-10-30 11:49:51'),(41,NULL,'Yeni İşletme Başvurusu: Bayraktar Berber Salonu','Gökhan Bayraktar (05384439834) yeni bir berber işletmesi kaydı oluşturdu. Onaylamak için tıklayın.','info','/admin/pages/applications.php?detail=176',0,'2026-10-31 15:52:02'),(42,NULL,'İşletme Onaylandı','Bayraktar Berber Salonu başlıklı işletme başvurusu onaylandı ve yayına alındı.','success','/admin/pages/businesses.php?id=176',0,'2026-10-31 15:52:26');
/*!40000 ALTER TABLE `bildirimler` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `calisan_calisma_araliklari`
--

DROP TABLE IF EXISTS `calisan_calisma_araliklari`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calisan_calisma_araliklari` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `calisan_id` int(11) NOT NULL,
  `haftanin_gunu` tinyint(3) unsigned NOT NULL,
  `baslangic` time NOT NULL,
  `bitis` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_calisan_calisma_gun` (`calisan_id`,`haftanin_gunu`),
  CONSTRAINT `fk_calisan_calisma_calisan` FOREIGN KEY (`calisan_id`) REFERENCES `calisanlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calisan_calisma_araliklari`
--

LOCK TABLES `calisan_calisma_araliklari` WRITE;
/*!40000 ALTER TABLE `calisan_calisma_araliklari` DISABLE KEYS */;
/*!40000 ALTER TABLE `calisan_calisma_araliklari` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `calisan_detaylari`
--

DROP TABLE IF EXISTS `calisan_detaylari`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calisan_detaylari` (
  `calisan_id` int(11) NOT NULL,
  `uzmanlik` varchar(180) DEFAULT NULL,
  `fotograf_yolu` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`calisan_id`),
  CONSTRAINT `fk_calisan_detay_calisan` FOREIGN KEY (`calisan_id`) REFERENCES `calisanlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calisan_detaylari`
--

LOCK TABLES `calisan_detaylari` WRITE;
/*!40000 ALTER TABLE `calisan_detaylari` DISABLE KEYS */;
INSERT INTO `calisan_detaylari` VALUES (61,'Berber','uploads/isletme/137/employees/4e6f44b233a20f57755df79076eabb5e50156cc0.png');
/*!40000 ALTER TABLE `calisan_detaylari` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `calisan_izinleri`
--

DROP TABLE IF EXISTS `calisan_izinleri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calisan_izinleri` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `calisan_id` int(11) NOT NULL,
  `tarih` date NOT NULL,
  `baslangic` time DEFAULT NULL,
  `bitis` time DEFAULT NULL,
  `neden` varchar(255) DEFAULT NULL,
  `olusturulma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_calisan_izin_tarih` (`calisan_id`,`tarih`),
  CONSTRAINT `fk_calisan_izin_calisan` FOREIGN KEY (`calisan_id`) REFERENCES `calisanlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calisan_izinleri`
--

LOCK TABLES `calisan_izinleri` WRITE;
/*!40000 ALTER TABLE `calisan_izinleri` DISABLE KEYS */;
/*!40000 ALTER TABLE `calisan_izinleri` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `calisanlar`
--

DROP TABLE IF EXISTS `calisanlar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calisanlar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `isletme_id` int(11) NOT NULL,
  `ad_soyad` varchar(150) NOT NULL,
  `telefon` varchar(25) DEFAULT NULL,
  `aktif_mi` tinyint(1) NOT NULL DEFAULT 1,
  `olusturulma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `business_id` (`isletme_id`),
  CONSTRAINT `calisanlar_ibfk_1` FOREIGN KEY (`isletme_id`) REFERENCES `isletmeler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calisanlar`
--

LOCK TABLES `calisanlar` WRITE;
/*!40000 ALTER TABLE `calisanlar` DISABLE KEYS */;
INSERT INTO `calisanlar` VALUES (61,137,'Furkan İğde','0505 508 5158',1,'2026-10-30 02:52:38');
/*!40000 ALTER TABLE `calisanlar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `degerlendirmeler`
--

DROP TABLE IF EXISTS `degerlendirmeler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `degerlendirmeler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `randevu_id` int(11) NOT NULL,
  `degerlendiren_turu` enum('customer','business') NOT NULL,
  `degerlendiren_id` int(11) NOT NULL,
  `puan` tinyint(3) unsigned NOT NULL,
  `yorum` text DEFAULT NULL,
  `olusturulma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_review` (`randevu_id`,`degerlendiren_turu`,`degerlendiren_id`),
  CONSTRAINT `degerlendirmeler_ibfk_1` FOREIGN KEY (`randevu_id`) REFERENCES `randevular` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=127 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `degerlendirmeler`
--

LOCK TABLES `degerlendirmeler` WRITE;
/*!40000 ALTER TABLE `degerlendirmeler` DISABLE KEYS */;
INSERT INTO `degerlendirmeler` VALUES (123,217,'customer',6,5,NULL,'2026-11-02 16:09:27'),(124,216,'customer',6,4,NULL,'2026-11-02 16:09:32'),(125,42,'customer',6,1,NULL,'2026-11-02 16:09:35'),(126,265,'customer',6,5,'çok beğendim','2026-11-02 18:48:14');
/*!40000 ALTER TABLE `degerlendirmeler` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_business_customer_review_created` AFTER INSERT ON `degerlendirmeler` FOR EACH ROW BEGIN
    IF NEW.degerlendiren_turu = 'customer' THEN
        INSERT INTO isletme_bildirimleri (isletme_id, baslik, mesaj, tur, link)
        SELECT r.isletme_id, 'Yeni müşteri yorumu', CONCAT(COALESCE(k.ad_soyad, 'Bir müşteri'), ' işletmenizi değerlendirdi.'),
               'review', '/isletme/yorumlar.php'
        FROM randevular r
        LEFT JOIN kullanicilar k ON k.id = NEW.degerlendiren_id
        WHERE r.id = NEW.randevu_id;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `eposta_kuyrugu`
--

DROP TABLE IF EXISTS `eposta_kuyrugu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eposta_kuyrugu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alici_eposta` varchar(190) NOT NULL,
  `alici_ad` varchar(150) DEFAULT NULL,
  `konu` varchar(255) NOT NULL,
  `icerik` longtext NOT NULL,
  `durum` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `idempotency_key` varchar(100) DEFAULT NULL,
  `tur` varchar(50) DEFAULT NULL,
  `deneme_sayisi` int(11) NOT NULL DEFAULT 0,
  `hata_mesaji` text DEFAULT NULL,
  `gonderim_tarihi` datetime DEFAULT NULL,
  `olusturulma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_idempotency` (`idempotency_key`),
  KEY `idx_eposta_durum` (`durum`)
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `eposta_kuyrugu`
--

LOCK TABLES `eposta_kuyrugu` WRITE;
/*!40000 ALTER TABLE `eposta_kuyrugu` DISABLE KEYS */;
INSERT INTO `eposta_kuyrugu` VALUES (66,'yilmazberber@gmail.com','Yılmaz','Tebrikler! BiTıraş.com İşletme Başvurunuz Onaylandı','Merhaba Yılmaz,<br><br><strong>Yılmaz Berber Salonu</strong> isimli işletmeniz için yaptığınız başvuru incelenmiş ve onaylanmıştır. Artık BiTıraş.com üzerinden randevu almaya başlayabilirsiniz.<br><br>Giriş yapmak için: <a href=\'http://localhost/admin/login.php\'>Giriş Yap</a><br><br>İyi çalışmalar dileriz,<br>BiTıraş.com Ekibi','pending',NULL,NULL,0,NULL,NULL,'2026-10-30 01:49:21'),(69,'yusuffurkankucukgozlu@gmail.com','ömer çetinkaya','Tebrikler! BiTıraş.com İşletme Başvurunuz Onaylandı','Merhaba ömer çetinkaya,<br><br><strong>Çetinkaya Berber Salonu</strong> isimli işletmeniz için yaptığınız başvuru incelenmiş ve onaylanmıştır. Artık BiTıraş.com üzerinden randevu almaya başlayabilirsiniz.<br><br>Giriş yapmak için: <a href=\'http://localhost/admin/login.php\'>Giriş Yap</a><br><br>İyi çalışmalar dileriz,<br>BiTıraş.com Ekibi','pending',NULL,NULL,0,NULL,NULL,'2026-10-30 11:49:51'),(70,'mststorehesap@gmail.com','Gökhan Bayraktar','Tebrikler! BiTıraş.com İşletme Başvurunuz Onaylandı','Merhaba Gökhan Bayraktar,<br><br><strong>Bayraktar Berber Salonu</strong> isimli işletmeniz için yaptığınız başvuru incelenmiş ve onaylanmıştır. Artık BiTıraş.com üzerinden randevu almaya başlayabilirsiniz.<br><br>Giriş yapmak için: <a href=\'http://localhost/admin/login.php\'>Giriş Yap</a><br><br>İyi çalışmalar dileriz,<br>BiTıraş.com Ekibi','pending',NULL,NULL,0,NULL,NULL,'2026-10-31 15:52:26'),(71,'mustafaacz37@gmail.com','Mustafa Açıkgöz','BİTİRAS — Randevunuz Onaylandı: Keser Berber salonu','<!DOCTYPE html>\r\n<html lang=\'tr\'>\r\n<head>\r\n    <meta charset=\'UTF-8\'>\r\n    <meta name=\'viewport\' content=\'width=device-width, initial-scale=1.0\'>\r\n    <title>Randevunuz Onaylandı!</title>\r\n</head>\r\n<body style=\'margin: 0; padding: 0; background-color: #F1F5F9; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;\'>\r\n    <table role=\'presentation\' width=\'100%\' border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'background-color: #F1F5F9; padding: 40px 10px;\'>\r\n        <tr>\r\n            <td align=\'center\'>\r\n                <table role=\'presentation\' width=\'100%\' border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'max-width: 580px; background-color: #FFFFFF; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); overflow: hidden;\'>\r\n                    <!-- Brand Header -->\r\n                    <tr>\r\n                        <td align=\'center\' style=\'background-color: #0F172A; padding: 32px 20px; border-bottom: 3px solid #C28840;\'>\r\n                            <h1 style=\'margin: 0; font-size: 24px; font-weight: 800; color: #F8FAFC; letter-spacing: 2px;\'>BiTıraş</h1>\r\n                            <p style=\'margin: 6px 0 0; font-size: 12px; color: #94A3B8; text-transform: uppercase; letter-spacing: 1px;\'>Online Berber & Randevu Platformu</p>\r\n                        </td>\r\n                    </tr>\r\n                    <!-- Content Body -->\r\n                    <tr>\r\n                        <td style=\'padding: 36px 32px; color: #1E293B; line-height: 1.65; font-size: 15px;\'>\r\n                            <h2 style=\'margin-top: 0; margin-bottom: 20px; font-size: 19px; color: #0F172A; font-weight: 700;\'>Randevunuz Onaylandı!</h2>\r\n                            \r\n        <p>Merhaba <strong>Mustafa Açıkgöz</strong>,</p>\r\n        <p>Harika haber! <strong>Keser Berber salonu</strong> randevunuzu onayladı.</p>\r\n        <div style=\'background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 8px; padding: 16px; margin: 20px 0; font-size: 14px; color: #065F46;\'>\r\n            <strong>Randevu Zamanı:</strong> 28.10.2026 günü saat 11:30<br>\r\n            <strong>Hizmet:</strong> Saç Kesim<br>\r\n            <strong>Adres:</strong> Aydın efeler cumhuriyet mahallesi tevfik fikret caddes 42/4\r\n        </div>\r\n        \r\n                            \r\n        <table role=\'presentation\' border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'margin: 28px auto;\'>\r\n            <tr>\r\n                <td align=\'center\' style=\'border-radius: 8px; background: #C28840;\'>\r\n                    <a href=\'http://localhost/musteri/randevularim.php\' target=\'_blank\' style=\'border: 1px solid #C28840; border-radius: 8px; color: #FFFFFF; display: inline-block; font-size: 15px; font-weight: bold; padding: 14px 28px; text-decoration: none;\'>\r\n                        Detayları İncele\r\n                    </a>\r\n                </td>\r\n            </tr>\r\n        </table>\r\n                            \r\n                        </td>\r\n                    </tr>\r\n                    <!-- Footer -->\r\n                    <tr>\r\n                        <td align=\'center\' style=\'background-color: #F8FAFC; padding: 24px 20px; border-top: 1px solid #E2E8F0; font-size: 12px; color: #64748B; line-height: 1.5;\'>\r\n                            <p style=\'margin: 0;\'>Bu e-posta <strong>BİTİRAS Güvenli Otomatik E-Posta Sistemi</strong> tarafından gönderilmiştir.</p>\r\n                            <p style=\'margin: 6px 0 0;\'>Eğer bu işlemi siz gerçekleştirmediyseniz, lütfen derhal destek ekibimizle iletişime geçin.</p>\r\n                            <p style=\'margin: 8px 0 0; color: #94A3B8;\'>&copy; 2026 BİTİRAS. Tüm hakları saklıdır.</p>\r\n                        </td>\r\n                    </tr>\r\n                </table>\r\n            </td>\r\n        </tr>\r\n    </table>\r\n</body>\r\n</html>','pending','apt_conf_217_1793464531','randevu_onay',0,NULL,NULL,'2026-10-31 16:35:31'),(86,'mustafaacz37@gmail.com','Mustafa Açıkgöz','BİTİRAS — Randevu Talebiniz Alındı: Bayraktar Berber Salonu','<!DOCTYPE html>\r\n<html lang=\'tr\'>\r\n<head>\r\n    <meta charset=\'UTF-8\'>\r\n    <meta name=\'viewport\' content=\'width=device-width, initial-scale=1.0\'>\r\n    <title>Randevu Talebiniz Alındı</title>\r\n</head>\r\n<body style=\'margin: 0; padding: 0; background-color: #F1F5F9; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;\'>\r\n    <table role=\'presentation\' width=\'100%\' border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'background-color: #F1F5F9; padding: 40px 10px;\'>\r\n        <tr>\r\n            <td align=\'center\'>\r\n                <table role=\'presentation\' width=\'100%\' border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'max-width: 580px; background-color: #FFFFFF; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); overflow: hidden;\'>\r\n                    <!-- Brand Header -->\r\n                    <tr>\r\n                        <td align=\'center\' style=\'background-color: #0F172A; padding: 32px 20px; border-bottom: 3px solid #C28840;\'>\r\n                            <h1 style=\'margin: 0; font-size: 24px; font-weight: 800; color: #F8FAFC; letter-spacing: 2px;\'>BiTıraş</h1>\r\n                            <p style=\'margin: 6px 0 0; font-size: 12px; color: #94A3B8; text-transform: uppercase; letter-spacing: 1px;\'>Online Berber & Randevu Platformu</p>\r\n                        </td>\r\n                    </tr>\r\n                    <!-- Content Body -->\r\n                    <tr>\r\n                        <td style=\'padding: 36px 32px; color: #1E293B; line-height: 1.65; font-size: 15px;\'>\r\n                            <h2 style=\'margin-top: 0; margin-bottom: 20px; font-size: 19px; color: #0F172A; font-weight: 700;\'>Randevu Talebiniz Alındı</h2>\r\n                            \r\n        <p>Merhaba <strong>Mustafa Açıkgöz</strong>,</p>\r\n        <p><strong>Bayraktar Berber Salonu</strong> için randevu talebiniz başarıyla alınmıştır. İşletme randevunuzu onayladığında ayrıca bilgilendirileceksiniz.</p>\r\n        \r\n        <table role=\'presentation\' width=\'100%\' style=\'background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 16px; margin: 20px 0; font-size: 14px;\'>\r\n            <tr><td style=\'padding: 6px 0; color: #64748B;\'>İşletme:</td><td style=\'padding: 6px 0; font-weight: 700; text-align: right;\'>Bayraktar Berber Salonu</td></tr>\r\n            <tr><td style=\'padding: 6px 0; color: #64748B;\'>Tarih & Saat:</td><td style=\'padding: 6px 0; font-weight: 700; text-align: right;\'>03.11.2026 — 11:30</td></tr>\r\n            <tr><td style=\'padding: 6px 0; color: #64748B;\'>Hizmet:</td><td style=\'padding: 6px 0; font-weight: 700; text-align: right;\'>Saç Kesim</td></tr>\r\n            <tr><td style=\'padding: 6px 0; color: #64748B;\'>Personel:</td><td style=\'padding: 6px 0; font-weight: 700; text-align: right;\'>İşletme Sahibi (Gökhan Bayraktar)</td></tr>\r\n            <tr><td style=\'padding: 6px 0; color: #64748B;\'>Ücret:</td><td style=\'padding: 6px 0; font-weight: 700; text-align: right; color: #C28840;\'>350,00 TL</td></tr>\r\n            <tr><td style=\'padding: 6px 0; color: #64748B;\'>Adres:</td><td style=\'padding: 6px 0; text-align: right; font-size: 12px;\'>Sularbaşı Mah., No: 10, 58800, Merkez, Sivas</td></tr>\r\n        </table>\r\n        \r\n                            \r\n        <table role=\'presentation\' border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'margin: 28px auto;\'>\r\n            <tr>\r\n                <td align=\'center\' style=\'border-radius: 8px; background: #C28840;\'>\r\n                    <a href=\'http://localhost/musteri/randevularim.php\' target=\'_blank\' style=\'border: 1px solid #C28840; border-radius: 8px; color: #FFFFFF; display: inline-block; font-size: 15px; font-weight: bold; padding: 14px 28px; text-decoration: none;\'>\r\n                        Randevularımı Görüntüle\r\n                    </a>\r\n                </td>\r\n            </tr>\r\n        </table>\r\n                            \r\n        <div style=\'margin-top: 24px; padding: 14px; background: #F8FAFC; border-left: 4px solid #C28840; border-radius: 4px; font-size: 13px; color: #475569;\'>\r\n            <strong>Güvenlik Notu:</strong> Randevunuza gelemeyecekseniz lütfen işletmeyi zor durumda bırakmamak için panelinizden iptal ediniz.\r\n        </div>\r\n                        </td>\r\n                    </tr>\r\n                    <!-- Footer -->\r\n                    <tr>\r\n                        <td align=\'center\' style=\'background-color: #F8FAFC; padding: 24px 20px; border-top: 1px solid #E2E8F0; font-size: 12px; color: #64748B; line-height: 1.5;\'>\r\n                            <p style=\'margin: 0;\'>Bu e-posta <strong>BİTİRAS Güvenli Otomatik E-Posta Sistemi</strong> tarafından gönderilmiştir.</p>\r\n                            <p style=\'margin: 6px 0 0;\'>Eğer bu işlemi siz gerçekleştirmediyseniz, lütfen derhal destek ekibimizle iletişime geçin.</p>\r\n                            <p style=\'margin: 8px 0 0; color: #94A3B8;\'>&copy; 2026 BİTİRAS. Tüm hakları saklıdır.</p>\r\n                        </td>\r\n                    </tr>\r\n                </table>\r\n            </td>\r\n        </tr>\r\n    </table>\r\n</body>\r\n</html>','pending','apt_created_cust_265','randevu_olusturuldu',0,NULL,NULL,'2026-11-02 18:36:29'),(87,'mststorehesap@gmail.com','Bayraktar Berber Salonu','BİTİRAS — Yeni Randevu Talebi: Mustafa Açıkgöz','<!DOCTYPE html>\r\n<html lang=\'tr\'>\r\n<head>\r\n    <meta charset=\'UTF-8\'>\r\n    <meta name=\'viewport\' content=\'width=device-width, initial-scale=1.0\'>\r\n    <title>Yeni Randevu Talebi Var</title>\r\n</head>\r\n<body style=\'margin: 0; padding: 0; background-color: #F1F5F9; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;\'>\r\n    <table role=\'presentation\' width=\'100%\' border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'background-color: #F1F5F9; padding: 40px 10px;\'>\r\n        <tr>\r\n            <td align=\'center\'>\r\n                <table role=\'presentation\' width=\'100%\' border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'max-width: 580px; background-color: #FFFFFF; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); overflow: hidden;\'>\r\n                    <!-- Brand Header -->\r\n                    <tr>\r\n                        <td align=\'center\' style=\'background-color: #0F172A; padding: 32px 20px; border-bottom: 3px solid #C28840;\'>\r\n                            <h1 style=\'margin: 0; font-size: 24px; font-weight: 800; color: #F8FAFC; letter-spacing: 2px;\'>BiTıraş</h1>\r\n                            <p style=\'margin: 6px 0 0; font-size: 12px; color: #94A3B8; text-transform: uppercase; letter-spacing: 1px;\'>Online Berber & Randevu Platformu</p>\r\n                        </td>\r\n                    </tr>\r\n                    <!-- Content Body -->\r\n                    <tr>\r\n                        <td style=\'padding: 36px 32px; color: #1E293B; line-height: 1.65; font-size: 15px;\'>\r\n                            <h2 style=\'margin-top: 0; margin-bottom: 20px; font-size: 19px; color: #0F172A; font-weight: 700;\'>Yeni Randevu Talebi Var</h2>\r\n                            \r\n        <p>Merhaba <strong>Bayraktar Berber Salonu</strong>,</p>\r\n        <p>İşletmeniz için yeni bir müşteri randevusu oluşturuldu. Lütfen işletme panelinizden onaylayınız veya detayları inceleyiniz.</p>\r\n        \r\n        <table role=\'presentation\' width=\'100%\' style=\'background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 16px; margin: 20px 0; font-size: 14px;\'>\r\n            <tr><td style=\'padding: 6px 0; color: #64748B;\'>Müşteri:</td><td style=\'padding: 6px 0; font-weight: 700; text-align: right;\'>Mustafa Açıkgöz (05467209327)</td></tr>\r\n            <tr><td style=\'padding: 6px 0; color: #64748B;\'>Tarih & Saat:</td><td style=\'padding: 6px 0; font-weight: 700; text-align: right;\'>03.11.2026 — 11:30</td></tr>\r\n            <tr><td style=\'padding: 6px 0; color: #64748B;\'>Hizmet:</td><td style=\'padding: 6px 0; font-weight: 700; text-align: right;\'>Saç Kesim</td></tr>\r\n            <tr><td style=\'padding: 6px 0; color: #64748B;\'>Personel:</td><td style=\'padding: 6px 0; font-weight: 700; text-align: right;\'>İşletme Sahibi (Gökhan Bayraktar)</td></tr>\r\n        </table>\r\n        \r\n                            \r\n        <table role=\'presentation\' border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'margin: 28px auto;\'>\r\n            <tr>\r\n                <td align=\'center\' style=\'border-radius: 8px; background: #C28840;\'>\r\n                    <a href=\'http://localhost/isletme/randevular.php\' target=\'_blank\' style=\'border: 1px solid #C28840; border-radius: 8px; color: #FFFFFF; display: inline-block; font-size: 15px; font-weight: bold; padding: 14px 28px; text-decoration: none;\'>\r\n                        İşletme Paneline Git\r\n                    </a>\r\n                </td>\r\n            </tr>\r\n        </table>\r\n                            \r\n        <div style=\'margin-top: 24px; padding: 14px; background: #F8FAFC; border-left: 4px solid #C28840; border-radius: 4px; font-size: 13px; color: #475569;\'>\r\n            <strong>Güvenlik Notu:</strong> Randevuyu zamanında onaylamak müşteri memnuniyetinizi ve işletme puanınızı artırır.\r\n        </div>\r\n                        </td>\r\n                    </tr>\r\n                    <!-- Footer -->\r\n                    <tr>\r\n                        <td align=\'center\' style=\'background-color: #F8FAFC; padding: 24px 20px; border-top: 1px solid #E2E8F0; font-size: 12px; color: #64748B; line-height: 1.5;\'>\r\n                            <p style=\'margin: 0;\'>Bu e-posta <strong>BİTİRAS Güvenli Otomatik E-Posta Sistemi</strong> tarafından gönderilmiştir.</p>\r\n                            <p style=\'margin: 6px 0 0;\'>Eğer bu işlemi siz gerçekleştirmediyseniz, lütfen derhal destek ekibimizle iletişime geçin.</p>\r\n                            <p style=\'margin: 8px 0 0; color: #94A3B8;\'>&copy; 2026 BİTİRAS. Tüm hakları saklıdır.</p>\r\n                        </td>\r\n                    </tr>\r\n                </table>\r\n            </td>\r\n        </tr>\r\n    </table>\r\n</body>\r\n</html>','pending','apt_created_biz_265','yeni_randevu_isletme',0,NULL,NULL,'2026-11-02 18:36:29'),(88,'mustafaacz37@gmail.com','Mustafa Açıkgöz','BİTİRAS — Randevunuz Onaylandı: Bayraktar Berber Salonu','<!DOCTYPE html>\r\n<html lang=\'tr\'>\r\n<head>\r\n    <meta charset=\'UTF-8\'>\r\n    <meta name=\'viewport\' content=\'width=device-width, initial-scale=1.0\'>\r\n    <title>Randevunuz Onaylandı!</title>\r\n</head>\r\n<body style=\'margin: 0; padding: 0; background-color: #F1F5F9; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;\'>\r\n    <table role=\'presentation\' width=\'100%\' border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'background-color: #F1F5F9; padding: 40px 10px;\'>\r\n        <tr>\r\n            <td align=\'center\'>\r\n                <table role=\'presentation\' width=\'100%\' border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'max-width: 580px; background-color: #FFFFFF; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); overflow: hidden;\'>\r\n                    <!-- Brand Header -->\r\n                    <tr>\r\n                        <td align=\'center\' style=\'background-color: #0F172A; padding: 32px 20px; border-bottom: 3px solid #C28840;\'>\r\n                            <h1 style=\'margin: 0; font-size: 24px; font-weight: 800; color: #F8FAFC; letter-spacing: 2px;\'>BiTıraş</h1>\r\n                            <p style=\'margin: 6px 0 0; font-size: 12px; color: #94A3B8; text-transform: uppercase; letter-spacing: 1px;\'>Online Berber & Randevu Platformu</p>\r\n                        </td>\r\n                    </tr>\r\n                    <!-- Content Body -->\r\n                    <tr>\r\n                        <td style=\'padding: 36px 32px; color: #1E293B; line-height: 1.65; font-size: 15px;\'>\r\n                            <h2 style=\'margin-top: 0; margin-bottom: 20px; font-size: 19px; color: #0F172A; font-weight: 700;\'>Randevunuz Onaylandı!</h2>\r\n                            \r\n        <p>Merhaba <strong>Mustafa Açıkgöz</strong>,</p>\r\n        <p>Harika haber! <strong>Bayraktar Berber Salonu</strong> randevunuzu onayladı.</p>\r\n        <div style=\'background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 8px; padding: 16px; margin: 20px 0; font-size: 14px; color: #065F46;\'>\r\n            <strong>Randevu Zamanı:</strong> 03.11.2026 günü saat 11:30<br>\r\n            <strong>Hizmet:</strong> Saç Kesim<br>\r\n            <strong>Adres:</strong> Sularbaşı Mah., No: 10, 58800, Merkez, Sivas\r\n        </div>\r\n        \r\n                            \r\n        <table role=\'presentation\' border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'margin: 28px auto;\'>\r\n            <tr>\r\n                <td align=\'center\' style=\'border-radius: 8px; background: #C28840;\'>\r\n                    <a href=\'http://localhost/musteri/randevularim.php\' target=\'_blank\' style=\'border: 1px solid #C28840; border-radius: 8px; color: #FFFFFF; display: inline-block; font-size: 15px; font-weight: bold; padding: 14px 28px; text-decoration: none;\'>\r\n                        Detayları İncele\r\n                    </a>\r\n                </td>\r\n            </tr>\r\n        </table>\r\n                            \r\n                        </td>\r\n                    </tr>\r\n                    <!-- Footer -->\r\n                    <tr>\r\n                        <td align=\'center\' style=\'background-color: #F8FAFC; padding: 24px 20px; border-top: 1px solid #E2E8F0; font-size: 12px; color: #64748B; line-height: 1.5;\'>\r\n                            <p style=\'margin: 0;\'>Bu e-posta <strong>BİTİRAS Güvenli Otomatik E-Posta Sistemi</strong> tarafından gönderilmiştir.</p>\r\n                            <p style=\'margin: 6px 0 0;\'>Eğer bu işlemi siz gerçekleştirmediyseniz, lütfen derhal destek ekibimizle iletişime geçin.</p>\r\n                            <p style=\'margin: 8px 0 0; color: #94A3B8;\'>&copy; 2026 BİTİRAS. Tüm hakları saklıdır.</p>\r\n                        </td>\r\n                    </tr>\r\n                </table>\r\n            </td>\r\n        </tr>\r\n    </table>\r\n</body>\r\n</html>','pending','apt_conf_265_1793645123','randevu_onay',0,NULL,NULL,'2026-11-02 18:45:23'),(89,'mustafaacz37@gmail.com','Mustafa Açıkgöz','BİTİRAS — Randevunuz Onaylandı: Keser Berber salonu','<!DOCTYPE html>\r\n<html lang=\'tr\'>\r\n<head>\r\n    <meta charset=\'UTF-8\'>\r\n    <meta name=\'viewport\' content=\'width=device-width, initial-scale=1.0\'>\r\n    <title>Randevunuz Onaylandı!</title>\r\n</head>\r\n<body style=\'margin: 0; padding: 0; background-color: #F1F5F9; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;\'>\r\n    <table role=\'presentation\' width=\'100%\' border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'background-color: #F1F5F9; padding: 40px 10px;\'>\r\n        <tr>\r\n            <td align=\'center\'>\r\n                <table role=\'presentation\' width=\'100%\' border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'max-width: 580px; background-color: #FFFFFF; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); overflow: hidden;\'>\r\n                    <!-- Brand Header -->\r\n                    <tr>\r\n                        <td align=\'center\' style=\'background-color: #0F172A; padding: 32px 20px; border-bottom: 3px solid #C28840;\'>\r\n                            <h1 style=\'margin: 0; font-size: 24px; font-weight: 800; color: #F8FAFC; letter-spacing: 2px;\'>BiTıraş</h1>\r\n                            <p style=\'margin: 6px 0 0; font-size: 12px; color: #94A3B8; text-transform: uppercase; letter-spacing: 1px;\'>Online Berber & Randevu Platformu</p>\r\n                        </td>\r\n                    </tr>\r\n                    <!-- Content Body -->\r\n                    <tr>\r\n                        <td style=\'padding: 36px 32px; color: #1E293B; line-height: 1.65; font-size: 15px;\'>\r\n                            <h2 style=\'margin-top: 0; margin-bottom: 20px; font-size: 19px; color: #0F172A; font-weight: 700;\'>Randevunuz Onaylandı!</h2>\r\n                            \r\n        <p>Merhaba <strong>Mustafa Açıkgöz</strong>,</p>\r\n        <p>Harika haber! <strong>Keser Berber salonu</strong> randevunuzu onayladı.</p>\r\n        <div style=\'background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 8px; padding: 16px; margin: 20px 0; font-size: 14px; color: #065F46;\'>\r\n            <strong>Randevu Zamanı:</strong> 18.10.2026 günü saat 15:16<br>\r\n            <strong>Hizmet:</strong> Saç Kesim<br>\r\n            <strong>Adres:</strong> Aydın efeler cumhuriyet mahallesi tevfik fikret caddes 42/4\r\n        </div>\r\n        \r\n                            \r\n        <table role=\'presentation\' border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'margin: 28px auto;\'>\r\n            <tr>\r\n                <td align=\'center\' style=\'border-radius: 8px; background: #C28840;\'>\r\n                    <a href=\'http://localhost/musteri/randevularim.php\' target=\'_blank\' style=\'border: 1px solid #C28840; border-radius: 8px; color: #FFFFFF; display: inline-block; font-size: 15px; font-weight: bold; padding: 14px 28px; text-decoration: none;\'>\r\n                        Detayları İncele\r\n                    </a>\r\n                </td>\r\n            </tr>\r\n        </table>\r\n                            \r\n                        </td>\r\n                    </tr>\r\n                    <!-- Footer -->\r\n                    <tr>\r\n                        <td align=\'center\' style=\'background-color: #F8FAFC; padding: 24px 20px; border-top: 1px solid #E2E8F0; font-size: 12px; color: #64748B; line-height: 1.5;\'>\r\n                            <p style=\'margin: 0;\'>Bu e-posta <strong>BİTİRAS Güvenli Otomatik E-Posta Sistemi</strong> tarafından gönderilmiştir.</p>\r\n                            <p style=\'margin: 6px 0 0;\'>Eğer bu işlemi siz gerçekleştirmediyseniz, lütfen derhal destek ekibimizle iletişime geçin.</p>\r\n                            <p style=\'margin: 8px 0 0; color: #94A3B8;\'>&copy; 2026 BİTİRAS. Tüm hakları saklıdır.</p>\r\n                        </td>\r\n                    </tr>\r\n                </table>\r\n            </td>\r\n        </tr>\r\n    </table>\r\n</body>\r\n</html>','pending','apt_conf_42_1793646616','randevu_onay',0,NULL,NULL,'2026-11-02 19:10:16'),(90,'mustafaacz37@gmail.com','Mustafa Açıkgöz','BİTİRAS — Randevunuz Onaylandı: Keser Berber salonu','<!DOCTYPE html>\r\n<html lang=\'tr\'>\r\n<head>\r\n    <meta charset=\'UTF-8\'>\r\n    <meta name=\'viewport\' content=\'width=device-width, initial-scale=1.0\'>\r\n    <title>Randevunuz Onaylandı!</title>\r\n</head>\r\n<body style=\'margin: 0; padding: 0; background-color: #F1F5F9; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;\'>\r\n    <table role=\'presentation\' width=\'100%\' border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'background-color: #F1F5F9; padding: 40px 10px;\'>\r\n        <tr>\r\n            <td align=\'center\'>\r\n                <table role=\'presentation\' width=\'100%\' border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'max-width: 580px; background-color: #FFFFFF; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); overflow: hidden;\'>\r\n                    <!-- Brand Header -->\r\n                    <tr>\r\n                        <td align=\'center\' style=\'background-color: #0F172A; padding: 32px 20px; border-bottom: 3px solid #C28840;\'>\r\n                            <h1 style=\'margin: 0; font-size: 24px; font-weight: 800; color: #F8FAFC; letter-spacing: 2px;\'>BiTıraş</h1>\r\n                            <p style=\'margin: 6px 0 0; font-size: 12px; color: #94A3B8; text-transform: uppercase; letter-spacing: 1px;\'>Online Berber & Randevu Platformu</p>\r\n                        </td>\r\n                    </tr>\r\n                    <!-- Content Body -->\r\n                    <tr>\r\n                        <td style=\'padding: 36px 32px; color: #1E293B; line-height: 1.65; font-size: 15px;\'>\r\n                            <h2 style=\'margin-top: 0; margin-bottom: 20px; font-size: 19px; color: #0F172A; font-weight: 700;\'>Randevunuz Onaylandı!</h2>\r\n                            \r\n        <p>Merhaba <strong>Mustafa Açıkgöz</strong>,</p>\r\n        <p>Harika haber! <strong>Keser Berber salonu</strong> randevunuzu onayladı.</p>\r\n        <div style=\'background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 8px; padding: 16px; margin: 20px 0; font-size: 14px; color: #065F46;\'>\r\n            <strong>Randevu Zamanı:</strong> 28.10.2026 günü saat 11:00<br>\r\n            <strong>Hizmet:</strong> Saç Kesim<br>\r\n            <strong>Adres:</strong> Aydın efeler cumhuriyet mahallesi tevfik fikret caddes 42/4\r\n        </div>\r\n        \r\n                            \r\n        <table role=\'presentation\' border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'margin: 28px auto;\'>\r\n            <tr>\r\n                <td align=\'center\' style=\'border-radius: 8px; background: #C28840;\'>\r\n                    <a href=\'http://localhost/musteri/randevularim.php\' target=\'_blank\' style=\'border: 1px solid #C28840; border-radius: 8px; color: #FFFFFF; display: inline-block; font-size: 15px; font-weight: bold; padding: 14px 28px; text-decoration: none;\'>\r\n                        Detayları İncele\r\n                    </a>\r\n                </td>\r\n            </tr>\r\n        </table>\r\n                            \r\n                        </td>\r\n                    </tr>\r\n                    <!-- Footer -->\r\n                    <tr>\r\n                        <td align=\'center\' style=\'background-color: #F8FAFC; padding: 24px 20px; border-top: 1px solid #E2E8F0; font-size: 12px; color: #64748B; line-height: 1.5;\'>\r\n                            <p style=\'margin: 0;\'>Bu e-posta <strong>BİTİRAS Güvenli Otomatik E-Posta Sistemi</strong> tarafından gönderilmiştir.</p>\r\n                            <p style=\'margin: 6px 0 0;\'>Eğer bu işlemi siz gerçekleştirmediyseniz, lütfen derhal destek ekibimizle iletişime geçin.</p>\r\n                            <p style=\'margin: 8px 0 0; color: #94A3B8;\'>&copy; 2026 BİTİRAS. Tüm hakları saklıdır.</p>\r\n                        </td>\r\n                    </tr>\r\n                </table>\r\n            </td>\r\n        </tr>\r\n    </table>\r\n</body>\r\n</html>','pending','apt_conf_216_1793646619','randevu_onay',0,NULL,NULL,'2026-11-02 19:10:19'),(91,'mustafaacz37@gmail.com','Mustafa Açıkgöz','BİTİRAS — Randevunuz Onaylandı: Keser Berber salonu','<!DOCTYPE html>\r\n<html lang=\'tr\'>\r\n<head>\r\n    <meta charset=\'UTF-8\'>\r\n    <meta name=\'viewport\' content=\'width=device-width, initial-scale=1.0\'>\r\n    <title>Randevunuz Onaylandı!</title>\r\n</head>\r\n<body style=\'margin: 0; padding: 0; background-color: #F1F5F9; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;\'>\r\n    <table role=\'presentation\' width=\'100%\' border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'background-color: #F1F5F9; padding: 40px 10px;\'>\r\n        <tr>\r\n            <td align=\'center\'>\r\n                <table role=\'presentation\' width=\'100%\' border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'max-width: 580px; background-color: #FFFFFF; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); overflow: hidden;\'>\r\n                    <!-- Brand Header -->\r\n                    <tr>\r\n                        <td align=\'center\' style=\'background-color: #0F172A; padding: 32px 20px; border-bottom: 3px solid #C28840;\'>\r\n                            <h1 style=\'margin: 0; font-size: 24px; font-weight: 800; color: #F8FAFC; letter-spacing: 2px;\'>BiTıraş</h1>\r\n                            <p style=\'margin: 6px 0 0; font-size: 12px; color: #94A3B8; text-transform: uppercase; letter-spacing: 1px;\'>Online Berber & Randevu Platformu</p>\r\n                        </td>\r\n                    </tr>\r\n                    <!-- Content Body -->\r\n                    <tr>\r\n                        <td style=\'padding: 36px 32px; color: #1E293B; line-height: 1.65; font-size: 15px;\'>\r\n                            <h2 style=\'margin-top: 0; margin-bottom: 20px; font-size: 19px; color: #0F172A; font-weight: 700;\'>Randevunuz Onaylandı!</h2>\r\n                            \r\n        <p>Merhaba <strong>Mustafa Açıkgöz</strong>,</p>\r\n        <p>Harika haber! <strong>Keser Berber salonu</strong> randevunuzu onayladı.</p>\r\n        <div style=\'background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 8px; padding: 16px; margin: 20px 0; font-size: 14px; color: #065F46;\'>\r\n            <strong>Randevu Zamanı:</strong> 28.10.2026 günü saat 11:30<br>\r\n            <strong>Hizmet:</strong> Saç Kesim<br>\r\n            <strong>Adres:</strong> Aydın efeler cumhuriyet mahallesi tevfik fikret caddes 42/4\r\n        </div>\r\n        \r\n                            \r\n        <table role=\'presentation\' border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'margin: 28px auto;\'>\r\n            <tr>\r\n                <td align=\'center\' style=\'border-radius: 8px; background: #C28840;\'>\r\n                    <a href=\'http://localhost/musteri/randevularim.php\' target=\'_blank\' style=\'border: 1px solid #C28840; border-radius: 8px; color: #FFFFFF; display: inline-block; font-size: 15px; font-weight: bold; padding: 14px 28px; text-decoration: none;\'>\r\n                        Detayları İncele\r\n                    </a>\r\n                </td>\r\n            </tr>\r\n        </table>\r\n                            \r\n                        </td>\r\n                    </tr>\r\n                    <!-- Footer -->\r\n                    <tr>\r\n                        <td align=\'center\' style=\'background-color: #F8FAFC; padding: 24px 20px; border-top: 1px solid #E2E8F0; font-size: 12px; color: #64748B; line-height: 1.5;\'>\r\n                            <p style=\'margin: 0;\'>Bu e-posta <strong>BİTİRAS Güvenli Otomatik E-Posta Sistemi</strong> tarafından gönderilmiştir.</p>\r\n                            <p style=\'margin: 6px 0 0;\'>Eğer bu işlemi siz gerçekleştirmediyseniz, lütfen derhal destek ekibimizle iletişime geçin.</p>\r\n                            <p style=\'margin: 8px 0 0; color: #94A3B8;\'>&copy; 2026 BİTİRAS. Tüm hakları saklıdır.</p>\r\n                        </td>\r\n                    </tr>\r\n                </table>\r\n            </td>\r\n        </tr>\r\n    </table>\r\n</body>\r\n</html>','pending','apt_conf_217_1793646620','randevu_onay',0,NULL,NULL,'2026-11-02 19:10:20'),(92,'mustafaacz37@gmail.com','Mustafa Açıkgöz','BİTİRAS — Randevunuz Onaylandı: Bayraktar Berber Salonu','<!DOCTYPE html>\r\n<html lang=\'tr\'>\r\n<head>\r\n    <meta charset=\'UTF-8\'>\r\n    <meta name=\'viewport\' content=\'width=device-width, initial-scale=1.0\'>\r\n    <title>Randevunuz Onaylandı!</title>\r\n</head>\r\n<body style=\'margin: 0; padding: 0; background-color: #F1F5F9; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;\'>\r\n    <table role=\'presentation\' width=\'100%\' border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'background-color: #F1F5F9; padding: 40px 10px;\'>\r\n        <tr>\r\n            <td align=\'center\'>\r\n                <table role=\'presentation\' width=\'100%\' border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'max-width: 580px; background-color: #FFFFFF; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); overflow: hidden;\'>\r\n                    <!-- Brand Header -->\r\n                    <tr>\r\n                        <td align=\'center\' style=\'background-color: #0F172A; padding: 32px 20px; border-bottom: 3px solid #C28840;\'>\r\n                            <h1 style=\'margin: 0; font-size: 24px; font-weight: 800; color: #F8FAFC; letter-spacing: 2px;\'>BiTıraş</h1>\r\n                            <p style=\'margin: 6px 0 0; font-size: 12px; color: #94A3B8; text-transform: uppercase; letter-spacing: 1px;\'>Online Berber & Randevu Platformu</p>\r\n                        </td>\r\n                    </tr>\r\n                    <!-- Content Body -->\r\n                    <tr>\r\n                        <td style=\'padding: 36px 32px; color: #1E293B; line-height: 1.65; font-size: 15px;\'>\r\n                            <h2 style=\'margin-top: 0; margin-bottom: 20px; font-size: 19px; color: #0F172A; font-weight: 700;\'>Randevunuz Onaylandı!</h2>\r\n                            \r\n        <p>Merhaba <strong>Mustafa Açıkgöz</strong>,</p>\r\n        <p>Harika haber! <strong>Bayraktar Berber Salonu</strong> randevunuzu onayladı.</p>\r\n        <div style=\'background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 8px; padding: 16px; margin: 20px 0; font-size: 14px; color: #065F46;\'>\r\n            <strong>Randevu Zamanı:</strong> 03.11.2026 günü saat 11:30<br>\r\n            <strong>Hizmet:</strong> Saç Kesim<br>\r\n            <strong>Adres:</strong> Sularbaşı Mah., No: 10, 58800, Merkez, Sivas\r\n        </div>\r\n        \r\n                            \r\n        <table role=\'presentation\' border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'margin: 28px auto;\'>\r\n            <tr>\r\n                <td align=\'center\' style=\'border-radius: 8px; background: #C28840;\'>\r\n                    <a href=\'http://localhost/musteri/randevularim.php\' target=\'_blank\' style=\'border: 1px solid #C28840; border-radius: 8px; color: #FFFFFF; display: inline-block; font-size: 15px; font-weight: bold; padding: 14px 28px; text-decoration: none;\'>\r\n                        Detayları İncele\r\n                    </a>\r\n                </td>\r\n            </tr>\r\n        </table>\r\n                            \r\n                        </td>\r\n                    </tr>\r\n                    <!-- Footer -->\r\n                    <tr>\r\n                        <td align=\'center\' style=\'background-color: #F8FAFC; padding: 24px 20px; border-top: 1px solid #E2E8F0; font-size: 12px; color: #64748B; line-height: 1.5;\'>\r\n                            <p style=\'margin: 0;\'>Bu e-posta <strong>BİTİRAS Güvenli Otomatik E-Posta Sistemi</strong> tarafından gönderilmiştir.</p>\r\n                            <p style=\'margin: 6px 0 0;\'>Eğer bu işlemi siz gerçekleştirmediyseniz, lütfen derhal destek ekibimizle iletişime geçin.</p>\r\n                            <p style=\'margin: 8px 0 0; color: #94A3B8;\'>&copy; 2026 BİTİRAS. Tüm hakları saklıdır.</p>\r\n                        </td>\r\n                    </tr>\r\n                </table>\r\n            </td>\r\n        </tr>\r\n    </table>\r\n</body>\r\n</html>','pending','apt_conf_265_1793646621','randevu_onay',0,NULL,NULL,'2026-11-02 19:10:21'),(93,'mustafaacz37@gmail.com','Mustafa Açıkgöz','BİTİRAS — Randevunuz Onaylandı: Bayraktar Berber Salonu','<!DOCTYPE html>\r\n<html lang=\'tr\'>\r\n<head>\r\n    <meta charset=\'UTF-8\'>\r\n    <meta name=\'viewport\' content=\'width=device-width, initial-scale=1.0\'>\r\n    <title>Randevunuz Onaylandı!</title>\r\n</head>\r\n<body style=\'margin: 0; padding: 0; background-color: #F1F5F9; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;\'>\r\n    <table role=\'presentation\' width=\'100%\' border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'background-color: #F1F5F9; padding: 40px 10px;\'>\r\n        <tr>\r\n            <td align=\'center\'>\r\n                <table role=\'presentation\' width=\'100%\' border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'max-width: 580px; background-color: #FFFFFF; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); overflow: hidden;\'>\r\n                    <!-- Brand Header -->\r\n                    <tr>\r\n                        <td align=\'center\' style=\'background-color: #0F172A; padding: 32px 20px; border-bottom: 3px solid #C28840;\'>\r\n                            <h1 style=\'margin: 0; font-size: 24px; font-weight: 800; color: #F8FAFC; letter-spacing: 2px;\'>BiTıraş</h1>\r\n                            <p style=\'margin: 6px 0 0; font-size: 12px; color: #94A3B8; text-transform: uppercase; letter-spacing: 1px;\'>Online Berber & Randevu Platformu</p>\r\n                        </td>\r\n                    </tr>\r\n                    <!-- Content Body -->\r\n                    <tr>\r\n                        <td style=\'padding: 36px 32px; color: #1E293B; line-height: 1.65; font-size: 15px;\'>\r\n                            <h2 style=\'margin-top: 0; margin-bottom: 20px; font-size: 19px; color: #0F172A; font-weight: 700;\'>Randevunuz Onaylandı!</h2>\r\n                            \r\n        <p>Merhaba <strong>Mustafa Açıkgöz</strong>,</p>\r\n        <p>Harika haber! <strong>Bayraktar Berber Salonu</strong> randevunuzu onayladı.</p>\r\n        <div style=\'background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 8px; padding: 16px; margin: 20px 0; font-size: 14px; color: #065F46;\'>\r\n            <strong>Randevu Zamanı:</strong> 03.11.2026 günü saat 11:30<br>\r\n            <strong>Hizmet:</strong> Saç Kesim<br>\r\n            <strong>Adres:</strong> Sularbaşı Mah., No: 10, 58800, Merkez, Sivas\r\n        </div>\r\n        \r\n                            \r\n        <table role=\'presentation\' border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'margin: 28px auto;\'>\r\n            <tr>\r\n                <td align=\'center\' style=\'border-radius: 8px; background: #C28840;\'>\r\n                    <a href=\'http://localhost/musteri/randevularim.php\' target=\'_blank\' style=\'border: 1px solid #C28840; border-radius: 8px; color: #FFFFFF; display: inline-block; font-size: 15px; font-weight: bold; padding: 14px 28px; text-decoration: none;\'>\r\n                        Detayları İncele\r\n                    </a>\r\n                </td>\r\n            </tr>\r\n        </table>\r\n                            \r\n                        </td>\r\n                    </tr>\r\n                    <!-- Footer -->\r\n                    <tr>\r\n                        <td align=\'center\' style=\'background-color: #F8FAFC; padding: 24px 20px; border-top: 1px solid #E2E8F0; font-size: 12px; color: #64748B; line-height: 1.5;\'>\r\n                            <p style=\'margin: 0;\'>Bu e-posta <strong>BİTİRAS Güvenli Otomatik E-Posta Sistemi</strong> tarafından gönderilmiştir.</p>\r\n                            <p style=\'margin: 6px 0 0;\'>Eğer bu işlemi siz gerçekleştirmediyseniz, lütfen derhal destek ekibimizle iletişime geçin.</p>\r\n                            <p style=\'margin: 8px 0 0; color: #94A3B8;\'>&copy; 2026 BİTİRAS. Tüm hakları saklıdır.</p>\r\n                        </td>\r\n                    </tr>\r\n                </table>\r\n            </td>\r\n        </tr>\r\n    </table>\r\n</body>\r\n</html>','pending','apt_conf_265_1793646622','randevu_onay',0,NULL,NULL,'2026-11-02 19:10:22'),(94,'mustafaacz37@gmail.com','Mustafa Açıkgöz','BİTİRAS — Randevunuz Onaylandı: Bayraktar Berber Salonu','<!DOCTYPE html>\r\n<html lang=\'tr\'>\r\n<head>\r\n    <meta charset=\'UTF-8\'>\r\n    <meta name=\'viewport\' content=\'width=device-width, initial-scale=1.0\'>\r\n    <title>Randevunuz Onaylandı!</title>\r\n</head>\r\n<body style=\'margin: 0; padding: 0; background-color: #F1F5F9; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;\'>\r\n    <table role=\'presentation\' width=\'100%\' border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'background-color: #F1F5F9; padding: 40px 10px;\'>\r\n        <tr>\r\n            <td align=\'center\'>\r\n                <table role=\'presentation\' width=\'100%\' border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'max-width: 580px; background-color: #FFFFFF; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); overflow: hidden;\'>\r\n                    <!-- Brand Header -->\r\n                    <tr>\r\n                        <td align=\'center\' style=\'background-color: #0F172A; padding: 32px 20px; border-bottom: 3px solid #C28840;\'>\r\n                            <h1 style=\'margin: 0; font-size: 24px; font-weight: 800; color: #F8FAFC; letter-spacing: 2px;\'>BiTıraş</h1>\r\n                            <p style=\'margin: 6px 0 0; font-size: 12px; color: #94A3B8; text-transform: uppercase; letter-spacing: 1px;\'>Online Berber & Randevu Platformu</p>\r\n                        </td>\r\n                    </tr>\r\n                    <!-- Content Body -->\r\n                    <tr>\r\n                        <td style=\'padding: 36px 32px; color: #1E293B; line-height: 1.65; font-size: 15px;\'>\r\n                            <h2 style=\'margin-top: 0; margin-bottom: 20px; font-size: 19px; color: #0F172A; font-weight: 700;\'>Randevunuz Onaylandı!</h2>\r\n                            \r\n        <p>Merhaba <strong>Mustafa Açıkgöz</strong>,</p>\r\n        <p>Harika haber! <strong>Bayraktar Berber Salonu</strong> randevunuzu onayladı.</p>\r\n        <div style=\'background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 8px; padding: 16px; margin: 20px 0; font-size: 14px; color: #065F46;\'>\r\n            <strong>Randevu Zamanı:</strong> 03.11.2026 günü saat 11:30<br>\r\n            <strong>Hizmet:</strong> Saç Kesim<br>\r\n            <strong>Adres:</strong> Sularbaşı Mah., No: 10, 58800, Merkez, Sivas\r\n        </div>\r\n        \r\n                            \r\n        <table role=\'presentation\' border=\'0\' cellpadding=\'0\' cellspacing=\'0\' style=\'margin: 28px auto;\'>\r\n            <tr>\r\n                <td align=\'center\' style=\'border-radius: 8px; background: #C28840;\'>\r\n                    <a href=\'http://localhost/musteri/randevularim.php\' target=\'_blank\' style=\'border: 1px solid #C28840; border-radius: 8px; color: #FFFFFF; display: inline-block; font-size: 15px; font-weight: bold; padding: 14px 28px; text-decoration: none;\'>\r\n                        Detayları İncele\r\n                    </a>\r\n                </td>\r\n            </tr>\r\n        </table>\r\n                            \r\n                        </td>\r\n                    </tr>\r\n                    <!-- Footer -->\r\n                    <tr>\r\n                        <td align=\'center\' style=\'background-color: #F8FAFC; padding: 24px 20px; border-top: 1px solid #E2E8F0; font-size: 12px; color: #64748B; line-height: 1.5;\'>\r\n                            <p style=\'margin: 0;\'>Bu e-posta <strong>BİTİRAS Güvenli Otomatik E-Posta Sistemi</strong> tarafından gönderilmiştir.</p>\r\n                            <p style=\'margin: 6px 0 0;\'>Eğer bu işlemi siz gerçekleştirmediyseniz, lütfen derhal destek ekibimizle iletişime geçin.</p>\r\n                            <p style=\'margin: 8px 0 0; color: #94A3B8;\'>&copy; 2026 BİTİRAS. Tüm hakları saklıdır.</p>\r\n                        </td>\r\n                    </tr>\r\n                </table>\r\n            </td>\r\n        </tr>\r\n    </table>\r\n</body>\r\n</html>','pending','apt_conf_265_1793646625','randevu_onay',0,NULL,NULL,'2026-11-02 19:10:25');
/*!40000 ALTER TABLE `eposta_kuyrugu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `giris_denemeleri`
--

DROP TABLE IF EXISTS `giris_denemeleri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `giris_denemeleri` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_adresi` varchar(45) NOT NULL,
  `eposta` varchar(190) DEFAULT NULL,
  `basarili` tinyint(1) NOT NULL DEFAULT 0,
  `olusturulma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_giris_ip` (`ip_adresi`),
  KEY `idx_giris_tarih` (`olusturulma_tarihi`)
) ENGINE=InnoDB AUTO_INCREMENT=240 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `giris_denemeleri`
--

LOCK TABLES `giris_denemeleri` WRITE;
/*!40000 ALTER TABLE `giris_denemeleri` DISABLE KEYS */;
INSERT INTO `giris_denemeleri` VALUES (139,'::1','mustafaacz37@gmail.com',1,'2026-10-30 11:34:38'),(140,'::1','yusuf107838@gmail.com',1,'2026-10-30 11:34:42'),(141,'::1','mustafaacz37@gmail.com',1,'2026-10-30 11:34:46'),(142,'::1','yusuf107838@gmail.com',1,'2026-10-30 11:44:37'),(143,'::1','yusuf107838@gmail.com',1,'2026-10-30 11:44:52'),(144,'::1','yusuffurkankucukgozlu@gmail.com',1,'2026-10-30 11:46:31'),(145,'::1','yusuffurkankucukgozlu@gmail.com',1,'2026-10-30 11:47:10'),(146,'::1','yusuffurkankucukgozlu@gmail.com',1,'2026-10-30 11:49:07'),(147,'::1','yusuffurkankucukgozlu@gmail.com',0,'2026-10-30 11:49:27'),(148,'::1','yusuf107838@gmail.com',1,'2026-10-30 11:49:34'),(149,'::1','yusuf107838@gmail.com',1,'2026-10-30 11:49:43'),(150,'::1','yusuffurkankucukgozlu@gmail.com',1,'2026-10-30 11:51:03'),(151,'::1','yusuffurkankucukgozlu@gmail.com',1,'2026-10-30 11:51:16'),(152,'127.0.0.1','mustafaacz37@gmail.com',0,'2026-10-30 14:51:37'),(153,'127.0.0.1','mustafaacz37@gmail.com',1,'2026-10-30 14:51:46'),(154,'127.0.0.1','mustafaacz37@gmail.com',1,'2026-10-30 14:52:05'),(155,'127.0.0.1','testisletmesi@gmail.com',0,'2026-10-30 14:53:17'),(156,'127.0.0.1','testisletmesi@gmail.com',0,'2026-10-30 14:53:29'),(157,'127.0.0.1','testisletmesi@gmail.com',0,'2026-10-30 14:53:41'),(158,'127.0.0.1','mustafaacz37@gmail.com',1,'2026-10-30 14:54:52'),(159,'127.0.0.1','mustafaacz37@gmail.com',1,'2026-10-30 14:55:02'),(160,'127.0.0.1','testisletmesi@gmail.com',0,'2026-10-30 14:56:19'),(161,'127.0.0.1','mehmetucar123q@gmail.com',0,'2026-10-30 15:11:05'),(162,'127.0.0.1','mehmetucar123q@gmail.com',0,'2026-10-30 15:11:10'),(163,'127.0.0.1','mehmetucar123q@gmail.com',0,'2026-10-30 15:11:30'),(164,'127.0.0.1','36504125005@ogr.bozok.edu.tr',1,'2026-10-30 15:11:52'),(165,'127.0.0.1','mustafaacz37@gmail.com',1,'2026-10-30 15:33:10'),(166,'127.0.0.1','mustafaacz37@gmail.com',1,'2026-10-30 15:33:25'),(167,'127.0.0.1','36504125005@ogr.bozok.edu.tr',1,'2026-10-30 15:40:59'),(168,'mail:2f692428c5207064478d645203651149007617db','mail:a38a398a4001bb58fab6a8a3468b60482abb944b6c8368c088adc42f6c4ae70c',0,'2026-10-31 00:10:21'),(169,'127.0.0.1','mustafaacz37@gmail.com',1,'2026-10-31 00:10:25'),(170,'127.0.0.1','mustafaacz37@gmail.com',1,'2026-10-31 00:10:38'),(171,'mail:59e16cc8b82e2fd3e57ae5a791c55d63ca1b02b8','mail:3dcfa871c0669ca566bbe0717d346dad2836697b7e9ef1e1c876a86d15eea0ca',0,'2026-10-31 00:26:21'),(172,'mail:2f692428c5207064478d645203651149007617db','mail:a38a398a4001bb58fab6a8a3468b60482abb944b6c8368c088adc42f6c4ae70c',0,'2026-10-31 00:27:42'),(173,'127.0.0.1','mustafaacz37@gmail.com',1,'2026-10-31 00:27:46'),(174,'127.0.0.1','mustafaacz37@gmail.com',1,'2026-10-31 00:27:56'),(175,'127.0.0.1','mststorehesap@gmail.com',1,'2026-10-31 15:52:02'),(176,'mail:e9295218208411a9fb5fdefc36d5cf1fbf619c2e','mail:0432152b0ebfc2d5bb457d0ba40de461f3aabac69ba3082bb7698c030d5e344d',0,'2026-10-31 15:52:53'),(177,'127.0.0.1','mststorehesap@gmail.com',1,'2026-10-31 15:52:56'),(178,'127.0.0.1','mststorehesap@gmail.com',1,'2026-10-31 15:53:31'),(179,'mail:4606d96a7b1f7c95f373e7f87c2c207d2aceaca7','mail:ad9dfb7ab54dd769300d01c14d638e1f5dbd81e6f8f8c52d73692ab879255728',0,'2026-10-31 16:25:01'),(180,'mail:3bbac1f74b38578ee4927c829b16117d9a3b4923','mail:837a4a78d99fa627739d992f7d4587df7afd7dc9afec2440d50ddeebcb4d3ff9',0,'2026-10-31 16:25:04'),(181,'127.0.0.1','mststorehesap@gmail.com',1,'2026-10-31 16:27:15'),(182,'mail:e9295218208411a9fb5fdefc36d5cf1fbf619c2e','mail:0432152b0ebfc2d5bb457d0ba40de461f3aabac69ba3082bb7698c030d5e344d',0,'2026-10-31 16:27:50'),(183,'127.0.0.1','mststorehesap@gmail.com',1,'2026-10-31 16:27:53'),(184,'mail:e9295218208411a9fb5fdefc36d5cf1fbf619c2e','mail:0432152b0ebfc2d5bb457d0ba40de461f3aabac69ba3082bb7698c030d5e344d',0,'2026-10-31 16:27:53'),(185,'127.0.0.1','mststorehesap@gmail.com',1,'2026-10-31 16:27:58'),(186,'127.0.0.1','mststorehesap@gmail.com',1,'2026-10-31 16:28:22'),(187,'127.0.0.1','mststorehesap@gmail.com',1,'2026-10-31 16:28:54'),(188,'127.0.0.1','mustafaacz27@gmail.com',0,'2026-10-31 16:33:07'),(189,'mail:2f692428c5207064478d645203651149007617db','mail:a38a398a4001bb58fab6a8a3468b60482abb944b6c8368c088adc42f6c4ae70c',0,'2026-10-31 16:33:14'),(190,'127.0.0.1','mustafaacz37@gmail.com',1,'2026-10-31 16:33:18'),(191,'127.0.0.1','mustafaacz37@gmail.com',1,'2026-10-31 16:33:32'),(192,'mail:e9295218208411a9fb5fdefc36d5cf1fbf619c2e','mail:68629e812f2d1a6ffc7a9d2849b3641fd221cd01388648f09f9661591d31a3e1',0,'2026-11-02 14:09:02'),(193,'127.0.0.1','mustafaacz37@gmail.com',1,'2026-11-02 14:09:05'),(194,'127.0.0.1','mustafaacz37@gmail.com',1,'2026-11-02 14:09:23'),(195,'127.0.0.1','mustafaacz37@gmail.com',1,'2026-11-02 14:11:04'),(196,'mail:99863e835b2d7c3a438fba8d671ee066ad67b04f','mail:fe026ab7dec627c95edad8d08000a2d308944507bff80db7e23adf68371924ba',0,'2026-11-02 14:11:17'),(197,'127.0.0.1','mustafaacz37@gmail.com',1,'2026-11-02 14:27:34'),(198,'mail:e9295218208411a9fb5fdefc36d5cf1fbf619c2e','mail:0432152b0ebfc2d5bb457d0ba40de461f3aabac69ba3082bb7698c030d5e344d',0,'2026-11-02 14:51:00'),(199,'127.0.0.1','mststorehesap@gmail.com',1,'2026-11-02 14:51:03'),(200,'127.0.0.1','mststorehesap@gmail.com',1,'2026-11-02 14:51:22'),(201,'127.0.0.1','mustafaacz37@gmail.com',0,'2026-11-02 15:05:33'),(202,'127.0.0.1','mustafaacz37@gmail.com',1,'2026-11-02 15:05:39'),(203,'127.0.0.1','mststorehesap@gmail.com',1,'2026-11-02 15:20:16'),(204,'127.0.0.1','yusuffurkankucukgozlu@gmail.com',0,'2026-11-02 15:26:05'),(205,'127.0.0.1','yusuffurkankucukgozlu@gmail.com',0,'2026-11-02 15:26:20'),(206,'127.0.0.1','yusuffurkankucukgozlu@gmail.com',0,'2026-11-02 15:26:34'),(207,'127.0.0.1','yusuffurkankucukgozlu@gmail.com',0,'2026-11-02 15:26:45'),(208,'mail:e9295218208411a9fb5fdefc36d5cf1fbf619c2e','mail:bb4de94ce3b5780fc24026c9ef05bd1f110039e88a2594412e60bb006e14eba4',0,'2026-11-02 15:26:54'),(209,'127.0.0.1','yusuffurkankucukgozlu@gmail.com',1,'2026-11-02 15:26:57'),(210,'127.0.0.1','yusuffurkankucukgozlu@gmail.com',1,'2026-11-02 15:28:12'),(211,'mail:e9295218208411a9fb5fdefc36d5cf1fbf619c2e','mail:bb4de94ce3b5780fc24026c9ef05bd1f110039e88a2594412e60bb006e14eba4',0,'2026-11-02 15:28:40'),(212,'127.0.0.1','yusuffurkankucukgozlu@gmail.com',1,'2026-11-02 15:28:43'),(213,'127.0.0.1','yusuffurkankucukgozlu@gmail.com',1,'2026-11-02 15:28:55'),(214,'mail:e9295218208411a9fb5fdefc36d5cf1fbf619c2e','mail:2ccd920c6d43837491811ff72942a093c3f8065a544eeeb74494200b75d28c64',0,'2026-11-02 15:32:37'),(215,'127.0.0.1','36504125005@ogr.bozok.edu.tr',1,'2026-11-02 15:32:40'),(216,'127.0.0.1','36504125005@ogr.bozok.edu.tr',1,'2026-11-02 15:33:23'),(217,'127.0.0.1','mustafaacz37@gmail.com',1,'2026-11-02 15:35:37'),(218,'127.0.0.1','mustafaacz37@gmail.com',1,'2026-11-02 15:39:52'),(219,'127.0.0.1','mustafaacz37@gmail.com',1,'2026-11-02 15:40:53'),(220,'mail:e9295218208411a9fb5fdefc36d5cf1fbf619c2e','mail:68629e812f2d1a6ffc7a9d2849b3641fd221cd01388648f09f9661591d31a3e1',0,'2026-11-02 15:43:22'),(221,'127.0.0.1','mustafaacz37@gmail.com',1,'2026-11-02 15:43:39'),(222,'127.0.0.1','mustafaacz37@gmail.com',0,'2026-11-02 16:00:31'),(223,'127.0.0.1','mustafaacz37@gmail.com',1,'2026-11-02 16:00:38'),(224,'mail:e9295218208411a9fb5fdefc36d5cf1fbf619c2e','mail:0432152b0ebfc2d5bb457d0ba40de461f3aabac69ba3082bb7698c030d5e344d',0,'2026-11-02 16:11:05'),(225,'127.0.0.1','mststorehesap@gmail.com',1,'2026-11-02 16:11:08'),(226,'127.0.0.1','mststorehesap@gmail.com',1,'2026-11-02 16:11:27'),(227,'::1','mustafaacz37@gmail.com',1,'2026-11-02 18:36:09'),(228,'mail:ae27892395a4ccd1aab2d84ab32f17bc13a9fbb6','mail:0432152b0ebfc2d5bb457d0ba40de461f3aabac69ba3082bb7698c030d5e344d',0,'2026-11-02 18:37:12'),(229,'::1','mststorehesap@gmail.com',1,'2026-11-02 18:37:15'),(230,'mail:ae27892395a4ccd1aab2d84ab32f17bc13a9fbb6','mail:0432152b0ebfc2d5bb457d0ba40de461f3aabac69ba3082bb7698c030d5e344d',0,'2026-11-02 18:37:16'),(231,'::1','mststorehesap@gmail.com',1,'2026-11-02 18:37:19'),(232,'::1','mststorehesap@gmail.com',1,'2026-11-02 18:37:54'),(233,'::1','mustafaacz37@gmail.com',1,'2026-11-02 18:48:01'),(234,'mail:2346237ccdb814289ed0c70564ebf53e5b354015','mail:587cd75fc29aa385901794679c92c8c6197dd4fc74a14379ac2beee2179f79da',0,'2026-11-02 18:53:44'),(235,'::1','yusuf107838@gmail.com',1,'2026-11-02 18:53:47'),(236,'::1','yusuf107838@gmail.com',1,'2026-11-02 18:54:10'),(237,'mail:2346237ccdb814289ed0c70564ebf53e5b354015','mail:0491578c95c1cfdfee5be0a2c30e7c5fb6ce51317b9aae145ab2a85ecb81419e',0,'2026-11-02 19:06:50'),(238,'::1','figde0118@gmail.com',1,'2026-11-02 19:06:53'),(239,'::1','figde0118@gmail.com',1,'2026-11-02 19:08:23');
/*!40000 ALTER TABLE `giris_denemeleri` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `guvenilir_cihazlar`
--

DROP TABLE IF EXISTS `guvenilir_cihazlar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `guvenilir_cihazlar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kullanici_turu` enum('yonetici','isletme','musteri') NOT NULL,
  `kullanici_id` int(11) NOT NULL,
  `cihaz_belirtec_hash` varchar(64) NOT NULL,
  `cihaz_adi` varchar(255) DEFAULT NULL,
  `ip_adresi` varchar(45) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `son_kullanim` datetime NOT NULL,
  `son_gecerlilik` datetime NOT NULL,
  `olusturulma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_kullanici` (`kullanici_turu`,`kullanici_id`),
  KEY `idx_belirtec` (`cihaz_belirtec_hash`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `guvenilir_cihazlar`
--

LOCK TABLES `guvenilir_cihazlar` WRITE;
/*!40000 ALTER TABLE `guvenilir_cihazlar` DISABLE KEYS */;
INSERT INTO `guvenilir_cihazlar` VALUES (4,'yonetici',4,'b09fc0ace8fb3e553988254e2d181fd8102db77979d239df08d14564137cf205','Web Tarayıcı (28.10.2026 17:31)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 OPR/138.0.0.0','2026-10-28 19:31:41','2026-11-27 19:31:41','2026-10-28 16:31:41'),(7,'isletme',10,'8a770d5d65376690f5abc4dc69861bc1599126ba7f1ddbe9b01c034d1fd15d90','Web Tarayıcı (28.10.2026 20:26)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 OPR/138.0.0.0','2026-10-28 23:24:36','2026-11-27 22:26:56','2026-10-28 19:26:56'),(8,'yonetici',4,'c59110e3b8c8a8d07c13a763d739ff6009946adf7c7d6575bfd95a45296fb540','Web Tarayıcı (28.10.2026 21:33)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 OPR/138.0.0.0','2026-10-28 23:33:18','2026-11-27 23:33:18','2026-10-28 20:33:18'),(9,'yonetici',13,'01d01e1a0f6ed1d1fdabf91157e6166dbdd769d992adf25490da730b6f81b14d','Web Tarayıcı (28.10.2026 21:34)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 OPR/138.0.0.0','2026-10-31 03:27:56','2026-11-27 23:34:52','2026-10-28 20:34:52'),(10,'yonetici',4,'6bf2ec7cc70caf09b014f1c65058f9f90d4224c5825196ef146c970f7192b52a','Web Tarayıcı (30.10.2026 02:32)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-10-30 04:49:08','2026-11-29 04:32:00','2026-10-30 01:32:00'),(11,'isletme',137,'ebe646f617abf9c8efb43ace407e3a84ea9527e2c5ba7df7b339efb06e14490c','Web Tarayıcı (30.10.2026 02:54)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-10-30 12:16:27','2026-11-29 04:54:04','2026-10-30 01:54:04'),(12,'yonetici',4,'0b76493d1e7bd1df15898f62fcc20875a640a7d2f023e57db09b63d41ddb1be2','Web Tarayıcı (30.10.2026 14:44)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-10-30 14:49:43','2026-11-29 14:44:50','2026-10-30 11:44:50'),(13,'isletme',171,'4f32bec9bf97845eade59809f4be2da8a043f67dd8501ec3f74c3e446ca814c8','Web Tarayıcı (30.10.2026 14:51)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-10-30 14:51:13','2026-11-29 14:51:13','2026-10-30 11:51:13'),(14,'isletme',176,'82fd25388b10862f57a340fb138e84c9744346b8ae0c2e5dc350e56aff571953','Web Tarayıcı (31.10.2026 18:53)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 OPR/138.0.0.0','2026-10-31 19:27:15','2026-11-30 18:53:29','2026-10-31 15:53:29'),(16,'isletme',176,'cf3914964b49c07b1900eb60e8eb8119373b828240113aca368ac5f21f865d53','Web Tarayıcı (31.10.2026 19:28)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-10-31 19:28:54','2026-11-30 19:28:19','2026-10-31 16:28:19'),(17,'yonetici',13,'f25fecc464b7ac8e6138e3a68e6baf222be3561f8e67db6720883d0a2a07f0cf','Web Tarayıcı (31.10.2026 19:33)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 OPR/138.0.0.0','2026-10-31 19:33:28','2026-11-30 19:33:28','2026-10-31 16:33:28'),(18,'isletme',10,'b6a3716db083edae319d178be8d4e878f2e640ef66a1f51a3e381edb1eefa952','Web Tarayıcı (02.11.2026 17:09)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 OPR/138.0.0.0','2026-11-02 17:09:20','2026-12-02 17:09:20','2026-11-02 14:09:20'),(19,'isletme',176,'184ff421a1edb9a9e6c26ee8f310ae7f35a2929823415f6652025d610b77657d','Web Tarayıcı (02.11.2026 17:51)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 OPR/138.0.0.0','2026-11-02 18:20:16','2026-12-02 17:51:19','2026-11-02 14:51:19'),(20,'isletme',171,'8f08285e4aaa6d81d4808a85b896ae905799728a897164fa95b3717b61f53490','Web Tarayıcı (02.11.2026 18:28)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 OPR/138.0.0.0','2026-11-02 18:28:08','2026-12-02 18:28:08','2026-11-02 15:28:08'),(21,'isletme',171,'f6d703b50eba5e81c30b83b284771dc2d4655ed9bc2531d5fa64d0df782f06a0','Web Tarayıcı (02.11.2026 18:28)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 OPR/138.0.0.0','2026-11-02 18:28:53','2026-12-02 18:28:53','2026-11-02 15:28:53'),(22,'isletme',137,'6e22d0e74a8d14d0424d11f9a1bd1dac9218c477fbd2fadcca7a20c489299a01','Web Tarayıcı (02.11.2026 18:33)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 OPR/138.0.0.0','2026-11-02 18:33:19','2026-12-02 18:33:19','2026-11-02 15:33:19'),(23,'isletme',10,'6c4aca05e6cde0c5219ed73e85665f5ce2927bf1c1e35b326d1e119dd08b0969','Web Tarayıcı (02.11.2026 18:43)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 OPR/138.0.0.0','2026-11-02 18:43:36','2026-12-02 18:43:36','2026-11-02 15:43:36'),(24,'isletme',176,'cd4e4b7d5717fe3f1d24e82165baf468f088d511d2aa16db34babcd254a629fe','Web Tarayıcı (02.11.2026 19:11)','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 OPR/138.0.0.0','2026-11-02 19:11:22','2026-12-02 19:11:22','2026-11-02 16:11:22'),(25,'isletme',176,'784981161dbf56af26888c6be9356d368191f883e2a791c88c199bf6e3ee0517','Web Tarayıcı (02.11.2026 21:37)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-02 21:37:52','2026-12-02 21:37:52','2026-11-02 18:37:52'),(26,'yonetici',4,'8416804f0a6ae5b6811bee862d075d02ccb4aa023e0610024c0560538cefa051','Web Tarayıcı (02.11.2026 21:54)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-02 21:54:07','2026-12-02 21:54:07','2026-11-02 18:54:07'),(27,'yonetici',7,'8c0d80bf936b77da2fe3a3349c4f236b4e64003731449ae1ab3528d8103ade32','Web Tarayıcı (02.11.2026 22:08)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','2026-11-02 22:08:20','2026-12-02 22:08:20','2026-11-02 19:08:20');
/*!40000 ALTER TABLE `guvenilir_cihazlar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hizmet_calisanlari`
--

DROP TABLE IF EXISTS `hizmet_calisanlari`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hizmet_calisanlari` (
  `hizmet_id` int(11) NOT NULL,
  `calisan_id` int(11) NOT NULL,
  PRIMARY KEY (`hizmet_id`,`calisan_id`),
  KEY `idx_hizmet_calisan_calisan` (`calisan_id`),
  CONSTRAINT `fk_hizmet_calisan_calisan` FOREIGN KEY (`calisan_id`) REFERENCES `calisanlar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hizmet_calisan_hizmet` FOREIGN KEY (`hizmet_id`) REFERENCES `hizmetler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hizmet_calisanlari`
--

LOCK TABLES `hizmet_calisanlari` WRITE;
/*!40000 ALTER TABLE `hizmet_calisanlari` DISABLE KEYS */;
INSERT INTO `hizmet_calisanlari` VALUES (89,61),(90,61);
/*!40000 ALTER TABLE `hizmet_calisanlari` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hizmet_detaylari`
--

DROP TABLE IF EXISTS `hizmet_detaylari`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hizmet_detaylari` (
  `hizmet_id` int(11) NOT NULL,
  `aciklama` text DEFAULT NULL,
  PRIMARY KEY (`hizmet_id`),
  CONSTRAINT `fk_hizmet_detay_hizmet` FOREIGN KEY (`hizmet_id`) REFERENCES `hizmetler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hizmet_detaylari`
--

LOCK TABLES `hizmet_detaylari` WRITE;
/*!40000 ALTER TABLE `hizmet_detaylari` DISABLE KEYS */;
INSERT INTO `hizmet_detaylari` VALUES (14,'İStediğiniz modelde saç kesimi'),(89,NULL),(90,'Sakal kesimi ve şekillendirmesi'),(101,'Detaylı Saç Keratin Bakımı Saç Yapısı Güçlendirme'),(104,NULL),(105,NULL),(106,NULL),(107,NULL),(113,NULL),(114,NULL),(115,NULL),(116,NULL),(117,NULL),(118,NULL),(119,NULL),(120,NULL),(121,NULL),(122,NULL),(123,NULL),(124,NULL),(125,NULL),(126,NULL),(127,NULL),(128,NULL),(129,NULL),(130,NULL),(131,NULL),(132,NULL),(133,NULL);
/*!40000 ALTER TABLE `hizmet_detaylari` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hizmetler`
--

DROP TABLE IF EXISTS `hizmetler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hizmetler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `isletme_id` int(11) NOT NULL,
  `hizmet_adi` varchar(150) NOT NULL,
  `fiyat` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sure_dakika` int(11) NOT NULL DEFAULT 30,
  `aktif_mi` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `business_id` (`isletme_id`),
  CONSTRAINT `hizmetler_ibfk_1` FOREIGN KEY (`isletme_id`) REFERENCES `isletmeler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=139 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hizmetler`
--

LOCK TABLES `hizmetler` WRITE;
/*!40000 ALTER TABLE `hizmetler` DISABLE KEYS */;
INSERT INTO `hizmetler` VALUES (14,10,'Saç Kesim',300.00,30,1),(89,137,'Saç Kesim',300.00,40,1),(90,137,'Sakal Tıraşı',150.00,20,1),(101,171,'Keratin bakımı',1500.00,60,1),(104,176,'Saç Kesim',350.00,30,1),(105,176,'Saç  Sakal Kesim',500.00,60,1),(106,176,'Kaş Tasarımı',150.00,15,1),(107,176,'Fön ve Şekillendirme',100.00,10,1),(113,176,'Keratin Saç Düzleştirme',1750.00,90,1),(114,176,'Perma Kıvırcık | Dalgalandırma',2000.00,90,1),(115,176,'Damat Traşı',2500.00,90,1),(116,171,'Saç Kesim',350.00,45,1),(117,171,'Saç  Sakal Kesim',600.00,60,1),(118,171,'Sakal Kesim',150.00,15,1),(119,171,'Fön ve Şekillendirme',100.00,10,1),(120,171,'Damat Traşı',2000.00,90,1),(121,137,'Fön ve Şekillendirme',100.00,15,1),(122,137,'Damat Traşı',2500.00,120,1),(123,137,'Saç  Sakal Kesim',450.00,70,1),(124,137,'Kaş Tasarımı',250.00,30,1),(125,137,'Saç Boyama',3000.00,120,1),(126,10,'Sakal Kesim',150.00,15,1),(127,10,'Saç Yıkama',150.00,15,1),(128,10,'Maske',150.00,30,1),(129,10,'Kaş Tasarımı',250.00,15,1),(130,10,'Fön ve Şekillendirme',150.00,10,1),(131,10,'Damat Traşı',2500.00,120,1),(132,10,'Saç Boyama',3000.00,180,1),(133,10,'Keratin Bakımı',1750.00,145,1);
/*!40000 ALTER TABLE `hizmetler` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `islem_gunlukleri`
--

DROP TABLE IF EXISTS `islem_gunlukleri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `islem_gunlukleri` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `yonetici_id` int(11) DEFAULT NULL,
  `islem_turu` varchar(100) NOT NULL,
  `hedef_turu` varchar(50) DEFAULT NULL,
  `hedef_id` int(11) DEFAULT NULL,
  `detay` text DEFAULT NULL,
  `ip_adresi` varchar(45) DEFAULT NULL,
  `olusturulma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_islem_yonetici` (`yonetici_id`),
  KEY `idx_islem_tarih` (`olusturulma_tarihi`),
  KEY `idx_islem_turu` (`islem_turu`)
) ENGINE=InnoDB AUTO_INCREMENT=234 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `islem_gunlukleri`
--

LOCK TABLES `islem_gunlukleri` WRITE;
/*!40000 ALTER TABLE `islem_gunlukleri` DISABLE KEYS */;
INSERT INTO `islem_gunlukleri` VALUES (1,NULL,'cli_admin_create','yoneticiler',1,'CLI üzerinden admin hesabı oluşturuldu: admin@bitiras.com','0.0.0.0','2026-10-16 21:56:47'),(2,1,'admin_login','yoneticiler',1,'Admin paneline güvenli giriş yapıldı','::1','2026-10-16 22:21:59'),(3,1,'manager_create','yoneticiler',3,'Yeni yönetici oluşturuldu: yusuf furkan küçükgözlü (manager)','::1','2026-10-16 22:34:24'),(4,1,'admin_logout','yoneticiler',1,'Admin panelinden çıkış yapıldı','::1','2026-10-16 22:34:42'),(5,NULL,'unauthorized_admin_access_attempt','yoneticiler',3,'Admin paneline yetkisiz giriş engellendi (Kullanıcı Rolü: manager)','::1','2026-10-16 22:34:53'),(6,NULL,'unauthorized_admin_access_attempt','yoneticiler',3,'Admin paneline yetkisiz giriş engellendi (Kullanıcı Rolü: manager)','::1','2026-10-16 22:49:11'),(7,1,'admin_login','yoneticiler',1,'Admin paneline güvenli giriş yapıldı','::1','2026-10-16 22:49:14'),(8,1,'settings_update','site_ayarlari',NULL,'17 site ayarı güncellendi.','::1','2026-10-16 22:49:50'),(9,1,'settings_update','site_ayarlari',NULL,'17 site ayarı güncellendi.','::1','2026-10-16 22:50:30'),(10,3,'TEST_ISLEM','isletmeler',1,'Otomasyon test doğrulaması gerçekleştirildi','127.0.0.1','2026-10-16 23:05:57'),(11,3,'TEST_ISLEM','isletmeler',1,'Otomasyon test doğrulaması gerçekleştirildi','127.0.0.1','2026-10-16 23:13:57'),(12,1,'admin_logout','yoneticiler',1,'Admin panelinden çıkış yapıldı','::1','2026-10-16 23:16:37'),(13,3,'manager_login','yoneticiler',3,'Yönetici (Manager) paneline giriş yapıldı','::1','2026-10-16 23:16:46'),(14,3,'TEST_ISLEM','isletmeler',1,'Otomasyon test doğrulaması gerçekleştirildi','127.0.0.1','2026-10-16 23:23:02'),(15,3,'TEST_ISLEM','isletmeler',1,'Otomasyon test doğrulaması gerçekleştirildi','127.0.0.1','2026-10-16 23:35:26'),(16,3,'manager_login','yoneticiler',3,'Yönetici (Manager) paneline giriş yapıldı','::1','2026-10-16 23:36:48'),(17,1,'admin_login','yoneticiler',1,'Süper Admin paneline giriş yapıldı','::1','2026-10-16 23:37:27'),(27,1,'settings_update','site_ayarlari',NULL,'1 site ayarı güncellendi.','127.0.0.1','2026-10-16 23:45:29'),(28,3,'TEST_ISLEM','isletmeler',1,'Otomasyon test doğrulaması gerçekleştirildi','127.0.0.1','2026-10-16 23:46:08'),(29,1,'admin_login','yoneticiler',1,'Süper Admin paneline giriş yapıldı','::1','2026-10-16 23:48:52'),(30,1,'business_approve','isletmeler',5,'İşletme onaylandı: stil','127.0.0.1','2026-10-16 23:54:17'),(31,1,'business_approve','isletmeler',9,'İşletme onaylandı: stil','::1','2026-10-16 23:56:01'),(32,1,'business_suspend','isletmeler',5,'İşletme askıya alındı: stil','::1','2026-10-16 23:56:14'),(33,1,'business_reject','isletmeler',5,'İşletme reddedildi: stil. Gerekçe: Telefon Numarası Yanlış','::1','2026-10-16 23:56:40'),(34,4,'admin_login','yoneticiler',4,'Süper Admin paneline giriş yapıldı','::1','2026-10-17 15:20:33'),(35,4,'settings_update','site_ayarlari',NULL,'16 site ayarı güncellendi.','::1','2026-10-17 15:21:15'),(36,4,'admin_logout','yoneticiler',4,'Admin panelinden çıkış yapıldı','::1','2026-10-17 15:21:54'),(37,4,'admin_login','yoneticiler',4,'Süper Admin paneline giriş yapıldı','::1','2026-10-17 15:21:55'),(38,4,'admin_logout','yoneticiler',4,'Admin panelinden çıkış yapıldı','::1','2026-10-17 15:22:59'),(39,4,'admin_login','yoneticiler',4,'Süper Admin paneline giriş yapıldı','::1','2026-10-17 15:30:33'),(40,4,'settings_update','site_ayarlari',NULL,'16 site ayarı güncellendi.','::1','2026-10-17 15:30:39'),(41,4,'settings_update','site_ayarlari',NULL,'16 site ayarı güncellendi.','::1','2026-10-17 15:31:14'),(42,4,'smtp_test','site_ayarlari',NULL,'SMTP test e-postası tetiklendi: yusuf107838@gmail.com','::1','2026-10-17 15:31:32'),(43,4,'admin_logout','yoneticiler',4,'Admin panelinden çıkış yapıldı','::1','2026-10-17 15:52:00'),(44,4,'admin_login','yoneticiler',4,'Süper Admin paneline giriş yapıldı','::1','2026-10-17 15:52:02'),(45,4,'admin_login','yoneticiler',4,'Süper Admin paneline giriş yapıldı','127.0.0.1','2026-10-17 15:58:43'),(46,4,'admin_login','yoneticiler',4,'Süper Admin paneline giriş yapıldı','127.0.0.1','2026-10-17 15:59:10'),(47,4,'settings_update','site_ayarlari',NULL,'15 site ayarı güncellendi.','127.0.0.1','2026-10-17 16:00:06'),(48,4,'settings_update','site_ayarlari',NULL,'15 site ayarı güncellendi.','127.0.0.1','2026-10-17 16:00:18'),(49,4,'admin_login','yoneticiler',4,'Süper Admin paneline giriş yapıldı','127.0.0.1','2026-10-17 16:13:46'),(50,4,'business_approve','isletmeler',10,'İşletme onaylandı: Deneme berber salonu','127.0.0.1','2026-10-17 16:13:51'),(51,4,'admin_login','yoneticiler',4,'Süper Admin paneline giriş yapıldı','::1','2026-10-19 12:36:57'),(57,4,'settings_update','site_ayarlari',NULL,'1 site ayarı güncellendi.','127.0.0.1','2026-10-19 12:52:06'),(63,4,'settings_update','site_ayarlari',NULL,'1 site ayarı güncellendi.','127.0.0.1','2026-10-19 13:19:50'),(64,5,'TEST_ISLEM','isletmeler',1,'Otomasyon test doğrulaması gerçekleştirildi','127.0.0.1','2026-10-19 19:53:16'),(65,5,'TEST_ISLEM','isletmeler',1,'Otomasyon test doğrulaması gerçekleştirildi','127.0.0.1','2026-10-19 20:07:13'),(71,4,'settings_update','site_ayarlari',NULL,'1 site ayarı güncellendi.','127.0.0.1','2026-10-19 20:08:14'),(72,5,'TEST_ISLEM','isletmeler',1,'Otomasyon test doğrulaması gerçekleştirildi','127.0.0.1','2026-10-19 20:08:18'),(73,6,'admin_login','yoneticiler',6,'Süper Admin paneline giriş yapıldı','::1','2026-10-19 20:35:20'),(79,4,'settings_update','site_ayarlari',NULL,'1 site ayarı güncellendi.','127.0.0.1','2026-10-19 20:39:06'),(80,5,'TEST_ISLEM','isletmeler',1,'Otomasyon test doğrulaması gerçekleştirildi','127.0.0.1','2026-10-19 20:39:08'),(81,4,'admin_login','yoneticiler',4,'Süper Admin paneline giriş yapıldı','::1','2026-10-20 08:46:46'),(82,4,'business_status_change','isletmeler',10,'İşletme durumu değiştirildi: askıya alındı','::1','2026-10-20 08:49:26'),(83,4,'admin_login','yoneticiler',4,'Süper Admin paneline giriş yapıldı','::1','2026-10-21 23:12:12'),(84,4,'admin_logout','yoneticiler',4,'Admin panelinden çıkış yapıldı','::1','2026-10-21 23:12:30'),(85,4,'admin_login','yoneticiler',4,'Süper Admin paneline giriş yapıldı','::1','2026-10-25 16:06:53'),(86,4,'admin_logout','yoneticiler',4,'Admin panelinden çıkış yapıldı','::1','2026-10-25 16:25:48'),(87,4,'admin_login','yoneticiler',4,'Süper Admin paneline giriş yapıldı','::1','2026-10-25 16:25:59'),(88,4,'appointment_status_change','randevular',42,'Randevu durumu \'cancelled\' olarak güncellendi. (Müşteri: Mustafa Açıkgöz, İşletme: Deneme berber salonu)','::1','2026-10-25 16:44:32'),(89,4,'admin_login','yoneticiler',4,'Süper Admin paneline giriş yapıldı','::1','2026-10-25 16:57:28'),(90,4,'admin_login','yoneticiler',4,'Süper Admin paneline giriş yapıldı','::1','2026-10-26 22:04:47'),(91,4,'business_status_change','isletmeler',10,'İşletme durumu değiştirildi: aktifleştirildi','::1','2026-10-26 22:05:05'),(92,4,'admin_login','yoneticiler',4,'Süper Admin paneline giriş yapıldı','::1','2026-10-26 22:13:12'),(98,4,'settings_update','site_ayarlari',NULL,'1 site ayarı güncellendi.','127.0.0.1','2026-10-26 22:13:43'),(104,4,'settings_update','site_ayarlari',NULL,'1 site ayarı güncellendi.','127.0.0.1','2026-10-26 22:15:48'),(110,4,'settings_update','site_ayarlari',NULL,'1 site ayarı güncellendi.','127.0.0.1','2026-10-26 22:24:32'),(116,4,'settings_update','site_ayarlari',NULL,'1 site ayarı güncellendi.','127.0.0.1','2026-10-26 22:29:19'),(117,4,'admin_login','yoneticiler',4,'Süper Admin paneline giriş yapıldı','::1','2026-10-26 22:52:00'),(118,4,'business_status_change','isletmeler',10,'İşletme durumu değiştirildi: askıya alındı','::1','2026-10-26 22:52:18'),(119,4,'business_status_change','isletmeler',10,'İşletme durumu değiştirildi: aktifleştirildi','::1','2026-10-26 22:52:26'),(120,4,'business_status_change','isletmeler',10,'İşletme durumu değiştirildi: askıya alındı','::1','2026-10-26 22:52:31'),(121,4,'business_status_change','isletmeler',10,'İşletme durumu değiştirildi: aktifleştirildi','::1','2026-10-26 22:52:45'),(122,4,'business_status_change','isletmeler',10,'İşletme durumu değiştirildi: askıya alındı','::1','2026-10-26 22:52:48'),(123,4,'business_status_change','isletmeler',10,'İşletme durumu değiştirildi: aktifleştirildi','::1','2026-10-26 22:52:50'),(124,4,'business_status_change','isletmeler',10,'İşletme durumu değiştirildi: askıya alındı','::1','2026-10-26 22:52:52'),(125,4,'admin_logout','yoneticiler',4,'Admin panelinden çıkış yapıldı','::1','2026-10-26 22:53:23'),(126,4,'admin_login','yoneticiler',4,'Süper Admin paneline giriş yapıldı','::1','2026-10-26 22:53:32'),(127,4,'business_status_change','isletmeler',10,'İşletme durumu değiştirildi: aktifleştirildi','::1','2026-10-26 22:53:35'),(128,4,'admin_logout','yoneticiler',4,'Admin panelinden çıkış yapıldı','::1','2026-10-26 22:53:37'),(129,4,'admin_login','yoneticiler',4,'Süper Admin paneline giriş yapıldı','::1','2026-10-26 22:54:04'),(130,4,'admin_logout','yoneticiler',4,'Admin panelinden çıkış yapıldı','::1','2026-10-26 22:54:50'),(131,4,'admin_login','yoneticiler',4,'Süper Admin paneline giriş yapıldı','::1','2026-10-26 22:57:09'),(132,4,'admin_logout','yoneticiler',4,'Admin panelinden çıkış yapıldı','::1','2026-10-26 22:59:56'),(133,4,'admin_login','yoneticiler',4,'Süper Admin paneline giriş yapıldı','::1','2026-10-26 23:02:43'),(134,4,'admin_logout','yoneticiler',4,'Admin panelinden çıkış yapıldı','::1','2026-10-26 23:04:17'),(135,4,'admin_login','yoneticiler',4,'Süper Admin paneline giriş yapıldı','::1','2026-10-27 08:36:35'),(136,4,'manager_create','yoneticiler',7,'Yeni yönetici oluşturuldu: Furkan iğdde (manager)','::1','2026-10-27 08:38:29'),(137,4,'admin_logout','yoneticiler',4,'Admin panelinden çıkış yapıldı','::1','2026-10-27 08:38:33'),(138,7,'manager_login','yoneticiler',7,'Yönetici (Manager) paneline giriş yapıldı','::1','2026-10-27 08:38:36'),(139,7,'settings_update','site_ayarlari',NULL,'16 site ayarı güncellendi.','::1','2026-10-27 08:38:59'),(140,7,'settings_update','site_ayarlari',NULL,'16 site ayarı güncellendi.','::1','2026-10-27 08:39:09'),(141,4,'admin_login','yoneticiler',4,'Süper Admin paneline giriş yapıldı','::1','2026-10-27 08:39:20'),(142,4,'manager_update','yoneticiler',7,'Yönetici yetkileri ve kapsamı güncellendi: Furkan iğdde','::1','2026-10-27 08:39:32'),(143,4,'admin_logout','yoneticiler',4,'Admin panelinden çıkış yapıldı','::1','2026-10-27 08:39:41'),(144,7,'manager_login','yoneticiler',7,'Yönetici (Manager) paneline giriş yapıldı','::1','2026-10-27 08:39:45'),(145,4,'admin_login','yoneticiler',4,'Süper Admin paneline giriş yapıldı','::1','2026-10-27 08:40:07'),(146,4,'manager_update','yoneticiler',7,'Yönetici yetkileri ve kapsamı güncellendi: Furkan iğde','::1','2026-10-27 08:40:17'),(147,4,'admin_logout','yoneticiler',4,'Admin panelinden çıkış yapıldı','::1','2026-10-27 08:40:28'),(148,7,'manager_login','yoneticiler',7,'Yönetici (Manager) paneline giriş yapıldı','::1','2026-10-27 08:40:34'),(149,7,'manager_login','yoneticiler',7,'Yönetici (Manager) paneline giriş yapıldı','::1','2026-10-27 10:33:20'),(150,7,'manager_login','yoneticiler',7,'Yönetici (Manager) paneline giriş yapıldı','::1','2026-10-27 10:48:56'),(151,4,'admin_login','yoneticiler',4,'Süper Admin paneline giriş yapıldı','::1','2026-10-27 11:22:24'),(152,4,'admin_logout','yoneticiler',4,'Admin panelinden çıkış yapıldı','::1','2026-10-27 11:22:39'),(153,7,'manager_login','yoneticiler',7,'Yönetici (Manager) paneline giriş yapıldı','::1','2026-10-27 11:22:46'),(154,4,'admin_login','yoneticiler',4,'Süper Admin paneline giriş yapıldı','127.0.0.1','2026-10-27 22:42:31'),(155,4,'appointment_status_change','randevular',42,'Randevu durumu \'confirmed\' olarak güncellendi. (Müşteri: Mustafa Açıkgöz, İşletme: Keser Berber salonu)','127.0.0.1','2026-10-27 23:00:16'),(156,4,'appointment_status_change','randevular',42,'Randevu durumu \'completed\' olarak güncellendi. (Müşteri: Mustafa Açıkgöz, İşletme: Keser Berber salonu)','127.0.0.1','2026-10-27 23:00:18'),(157,4,'appointment_status_change','randevular',42,'Randevu durumu \'confirmed\' olarak güncellendi. (Müşteri: Mustafa Açıkgöz, İşletme: Keser Berber salonu)','127.0.0.1','2026-10-27 23:00:19'),(158,4,'appointment_status_change','randevular',42,'Randevu durumu \'completed\' olarak güncellendi. (Müşteri: Mustafa Açıkgöz, İşletme: Keser Berber salonu)','127.0.0.1','2026-10-27 23:00:21'),(159,4,'appointment_status_change','randevular',42,'Randevu durumu \'confirmed\' olarak güncellendi. (Müşteri: Mustafa Açıkgöz, İşletme: Keser Berber salonu)','127.0.0.1','2026-10-27 23:00:22'),(160,4,'appointment_status_change','randevular',42,'Randevu durumu \'completed\' olarak güncellendi. (Müşteri: Mustafa Açıkgöz, İşletme: Keser Berber salonu)','127.0.0.1','2026-10-27 23:00:25'),(161,4,'admin_login','yoneticiler',4,'Süper Admin paneline giriş yapıldı','127.0.0.1','2026-10-27 23:24:17'),(162,4,'admin_logout','yoneticiler',4,'Admin panelinden çıkış yapıldı','127.0.0.1','2026-10-27 23:24:38'),(163,4,'admin_login','yoneticiler',4,'Süper Admin paneline giriş yapıldı','127.0.0.1','2026-10-28 15:59:21'),(164,4,'settings_update','site_ayarlari',NULL,'9 site ayarı güncellendi.','127.0.0.1','2026-10-28 16:04:15'),(165,4,'admin_logout','yoneticiler',4,'Admin panelinden çıkış yapıldı','127.0.0.1','2026-10-28 16:30:44'),(166,4,'admin_login','yoneticiler',4,'Süper Admin paneline 2FA ile giriş yapıldı','127.0.0.1','2026-10-28 16:31:43'),(167,4,'business_reject','isletmeler',130,'İşletme reddedildi: Test Randevu Berber. Gerekçe: sdfsd','127.0.0.1','2026-10-28 19:14:19'),(168,4,'business_reject','isletmeler',129,'İşletme reddedildi: Test Randevu Berber. Gerekçe: vdfg','127.0.0.1','2026-10-28 19:14:25'),(169,4,'business_reject','isletmeler',128,'İşletme reddedildi: Test Randevu Berber. Gerekçe: fsdfsd','127.0.0.1','2026-10-28 19:14:29'),(170,4,'business_reject','isletmeler',126,'İşletme reddedildi: Test Randevu Berber. Gerekçe: fdsfsdf','127.0.0.1','2026-10-28 19:14:32'),(171,4,'appointment_status_change','randevular',218,'Randevu durumu \'cancelled\' olarak güncellendi. (Müşteri: Test Randevu Müşterisi, İşletme: Test Randevu Berber)','127.0.0.1','2026-10-28 19:14:47'),(172,4,'appointment_status_change','randevular',219,'Randevu durumu \'cancelled\' olarak güncellendi. (Müşteri: Test Randevu Müşterisi, İşletme: Test Randevu Berber)','127.0.0.1','2026-10-28 19:14:49'),(173,4,'appointment_status_change','randevular',220,'Randevu durumu \'cancelled\' olarak güncellendi. (Müşteri: Test Randevu Müşterisi, İşletme: Test Randevu Berber)','127.0.0.1','2026-10-28 19:14:51'),(174,4,'admin_login','yoneticiler',4,'Süper Admin paneline 2FA ile giriş yapıldı','127.0.0.1','2026-10-28 20:33:22'),(175,4,'manager_create','yoneticiler',13,'Yeni yönetici oluşturuldu: Mustafa Açıkgöz (admin)','127.0.0.1','2026-10-28 20:33:53'),(176,4,'admin_logout','yoneticiler',4,'Admin panelinden çıkış yapıldı','127.0.0.1','2026-10-28 20:34:03'),(177,13,'admin_login','yoneticiler',13,'Süper Admin paneline 2FA ile giriş yapıldı','127.0.0.1','2026-10-28 20:34:55'),(178,4,'admin_login','yoneticiler',4,'Süper Admin paneline 2FA ile giriş yapıldı','::1','2026-10-30 01:32:04'),(179,4,'business_status_change','isletmeler',10,'İşletme durumu değiştirildi: askıya alındı','::1','2026-10-30 01:32:38'),(180,4,'business_status_change','isletmeler',10,'İşletme durumu değiştirildi: aktifleştirildi','::1','2026-10-30 01:32:40'),(186,4,'settings_update','site_ayarlari',NULL,'1 site ayarı güncellendi.','127.0.0.1','2026-10-30 01:46:57'),(187,4,'admin_logout','yoneticiler',4,'Admin panelinden çıkış yapıldı','::1','2026-10-30 01:48:22'),(188,4,'admin_login','yoneticiler',4,'Süper Admin paneline 2FA ile giriş yapıldı','::1','2026-10-30 01:49:08'),(189,4,'business_approve','isletmeler',137,'İşletme onaylandı: Yılmaz Berber Salonu','::1','2026-10-30 01:49:21'),(190,4,'admin_logout','yoneticiler',4,'Admin panelinden çıkış yapıldı','::1','2026-10-30 01:49:25'),(196,4,'settings_update','site_ayarlari',NULL,'1 site ayarı güncellendi.','127.0.0.1','2026-10-30 09:38:12'),(197,4,'admin_login','yoneticiler',4,'Süper Admin paneline 2FA ile giriş yapıldı','::1','2026-10-30 11:44:52'),(198,4,'admin_login','yoneticiler',4,'Süper Admin paneline 2FA ile giriş yapıldı','::1','2026-10-30 11:49:43'),(199,4,'business_approve','isletmeler',171,'İşletme onaylandı: Çetinkaya Berber Salonu','::1','2026-10-30 11:49:51'),(200,4,'admin_logout','yoneticiler',4,'Admin panelinden çıkış yapıldı','::1','2026-10-30 11:50:52'),(201,13,'admin_login','yoneticiler',13,'Süper Admin paneline 2FA ile giriş yapıldı','127.0.0.1','2026-10-30 14:52:05'),(202,13,'admin_logout','yoneticiler',13,'Admin panelinden çıkış yapıldı','127.0.0.1','2026-10-30 14:53:00'),(203,13,'admin_login','yoneticiler',13,'Süper Admin paneline 2FA ile giriş yapıldı','127.0.0.1','2026-10-30 14:55:02'),(204,13,'admin_login','yoneticiler',13,'Süper Admin paneline 2FA ile giriş yapıldı','127.0.0.1','2026-10-30 15:33:25'),(205,13,'business_update','isletmeler',171,'İşletme bilgileri güncellendi: Çetinkaya Berber Salonu','127.0.0.1','2026-10-30 15:33:41'),(206,13,'admin_logout','yoneticiler',13,'Admin panelinden çıkış yapıldı','127.0.0.1','2026-10-30 15:40:43'),(207,13,'admin_login','yoneticiler',13,'Süper Admin paneline 2FA ile giriş yapıldı','127.0.0.1','2026-10-31 00:10:38'),(208,13,'admin_logout','yoneticiler',13,'Admin panelinden çıkış yapıldı','127.0.0.1','2026-10-31 00:23:34'),(209,13,'admin_login','yoneticiler',13,'Süper Admin paneline 2FA ile giriş yapıldı','127.0.0.1','2026-10-31 00:27:56'),(210,13,'settings_update','site_ayarlari',NULL,'9 site ayarı güncellendi.','127.0.0.1','2026-10-31 00:28:15'),(211,13,'settings_update','site_ayarlari',NULL,'10 site ayarı güncellendi.','127.0.0.1','2026-10-31 00:28:55'),(212,13,'settings_update','site_ayarlari',NULL,'9 site ayarı güncellendi.','127.0.0.1','2026-10-31 00:29:10'),(213,NULL,'kvkk_onay','isletme',176,'İşletme başvurusunda KVKK metni onaylandı. Pazarlama izni: Evet','127.0.0.1','2026-10-31 15:52:02'),(214,13,'business_approve','isletmeler',176,'İşletme onaylandı: Bayraktar Berber Salonu','127.0.0.1','2026-10-31 15:52:26'),(215,13,'admin_logout','yoneticiler',13,'Admin panelinden çıkış yapıldı','127.0.0.1','2026-10-31 15:52:34'),(216,13,'admin_login','yoneticiler',13,'Süper Admin paneline 2FA ile giriş yapıldı','127.0.0.1','2026-10-31 16:33:32'),(217,13,'appointment_status_change','randevular',217,'Randevu durumu \'confirmed\' olarak güncellendi. (Müşteri: Mustafa Açıkgöz, İşletme: Keser Berber salonu)','127.0.0.1','2026-10-31 16:35:31'),(218,13,'appointment_status_change','randevular',217,'Randevu durumu \'completed\' olarak güncellendi. (Müşteri: Mustafa Açıkgöz, İşletme: Keser Berber salonu)','127.0.0.1','2026-10-31 16:35:36'),(219,13,'business_status_change','isletmeler',176,'İşletme durumu değiştirildi: askıya alındı','127.0.0.1','2026-10-31 16:42:53'),(220,13,'business_status_change','isletmeler',176,'İşletme durumu değiştirildi: aktifleştirildi','127.0.0.1','2026-10-31 16:42:55'),(221,13,'admin_logout','yoneticiler',13,'Admin panelinden çıkış yapıldı','127.0.0.1','2026-10-31 16:43:11'),(222,4,'admin_login','yoneticiler',4,'Süper Admin paneline 2FA ile giriş yapıldı','::1','2026-11-02 18:54:10'),(223,4,'business_vip_toggle','isletmeler',176,'İşletme VIP durumu değiştirildi: VIP salon olarak işaretlendi','::1','2026-11-02 18:54:31'),(224,4,'admin_logout','yoneticiler',4,'Admin panelinden çıkış yapıldı','::1','2026-11-02 19:06:31'),(225,7,'manager_login','yoneticiler',7,'Yönetici paneline 2FA ile giriş yapıldı','::1','2026-11-02 19:08:23'),(226,7,'appointment_status_change','randevular',42,'Randevu durumu \'confirmed\' olarak güncellendi. (Müşteri: Mustafa Açıkgöz, İşletme: Keser Berber salonu)','::1','2026-11-02 19:10:16'),(227,7,'appointment_status_change','randevular',216,'Randevu durumu \'confirmed\' olarak güncellendi. (Müşteri: Mustafa Açıkgöz, İşletme: Keser Berber salonu)','::1','2026-11-02 19:10:19'),(228,7,'appointment_status_change','randevular',217,'Randevu durumu \'confirmed\' olarak güncellendi. (Müşteri: Mustafa Açıkgöz, İşletme: Keser Berber salonu)','::1','2026-11-02 19:10:20'),(229,7,'appointment_status_change','randevular',265,'Randevu durumu \'confirmed\' olarak güncellendi. (Müşteri: Mustafa Açıkgöz, İşletme: Bayraktar Berber Salonu)','::1','2026-11-02 19:10:21'),(230,7,'appointment_status_change','randevular',265,'Randevu durumu \'completed\' olarak güncellendi. (Müşteri: Mustafa Açıkgöz, İşletme: Bayraktar Berber Salonu)','::1','2026-11-02 19:10:21'),(231,7,'appointment_status_change','randevular',265,'Randevu durumu \'confirmed\' olarak güncellendi. (Müşteri: Mustafa Açıkgöz, İşletme: Bayraktar Berber Salonu)','::1','2026-11-02 19:10:22'),(232,7,'appointment_status_change','randevular',265,'Randevu durumu \'completed\' olarak güncellendi. (Müşteri: Mustafa Açıkgöz, İşletme: Bayraktar Berber Salonu)','::1','2026-11-02 19:10:24'),(233,7,'appointment_status_change','randevular',265,'Randevu durumu \'confirmed\' olarak güncellendi. (Müşteri: Mustafa Açıkgöz, İşletme: Bayraktar Berber Salonu)','::1','2026-11-02 19:10:25');
/*!40000 ALTER TABLE `islem_gunlukleri` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `isletme_bildirimleri`
--

DROP TABLE IF EXISTS `isletme_bildirimleri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `isletme_bildirimleri` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `isletme_id` int(11) NOT NULL,
  `baslik` varchar(255) NOT NULL,
  `mesaj` text NOT NULL,
  `tur` varchar(50) NOT NULL DEFAULT 'info',
  `link` varchar(255) DEFAULT NULL,
  `okundu` tinyint(1) NOT NULL DEFAULT 0,
  `olusturulma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_isletme_bildirim_okundu` (`isletme_id`,`okundu`,`olusturulma_tarihi`),
  CONSTRAINT `fk_isletme_bildirim_isletme` FOREIGN KEY (`isletme_id`) REFERENCES `isletmeler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=367 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `isletme_bildirimleri`
--

LOCK TABLES `isletme_bildirimleri` WRITE;
/*!40000 ALTER TABLE `isletme_bildirimleri` DISABLE KEYS */;
INSERT INTO `isletme_bildirimleri` VALUES (25,10,'Yeni randevu oluşturuldu','Mustafa Açıkgöz için yeni bir randevu alındı.','appointment','/isletme/randevular.php',1,'2026-10-17 17:16:51'),(99,10,'Yeni müşteri yorumu','Mustafa Açıkgöz işletmenizi değerlendirdi.','review','/isletme/yorumlar.php',1,'2026-10-17 18:00:12'),(230,10,'İşletme hesabı askıya alındı','Hesabınız askıya alındı; destek ekibiyle iletişime geçin.','business_status','/isletme/giris.php',1,'2026-10-20 08:49:26'),(231,10,'Randevu iptal edildi','Randevu #42 iptal edildi.','appointment_cancelled','/isletme/randevular.php',1,'2026-10-25 16:44:32'),(232,10,'İşletme başvurusu onaylandı','İşletme panelinizi kullanabilirsiniz.','business_status','/isletme/giris.php',1,'2026-10-26 22:05:05'),(279,10,'İşletme hesabı askıya alındı','Hesabınız askıya alındı; destek ekibiyle iletişime geçin.','business_status','/isletme/giris.php',1,'2026-10-26 22:52:18'),(280,10,'İşletme başvurusu onaylandı','İşletme panelinizi kullanabilirsiniz.','business_status','/isletme/giris.php',1,'2026-10-26 22:52:26'),(281,10,'İşletme hesabı askıya alındı','Hesabınız askıya alındı; destek ekibiyle iletişime geçin.','business_status','/isletme/giris.php',1,'2026-10-26 22:52:31'),(282,10,'İşletme başvurusu onaylandı','İşletme panelinizi kullanabilirsiniz.','business_status','/isletme/giris.php',1,'2026-10-26 22:52:45'),(283,10,'İşletme hesabı askıya alındı','Hesabınız askıya alındı; destek ekibiyle iletişime geçin.','business_status','/isletme/giris.php',1,'2026-10-26 22:52:48'),(284,10,'İşletme başvurusu onaylandı','İşletme panelinizi kullanabilirsiniz.','business_status','/isletme/giris.php',1,'2026-10-26 22:52:50'),(285,10,'İşletme hesabı askıya alındı','Hesabınız askıya alındı; destek ekibiyle iletişime geçin.','business_status','/isletme/giris.php',1,'2026-10-26 22:52:52'),(286,10,'İşletme başvurusu onaylandı','İşletme panelinizi kullanabilirsiniz.','business_status','/isletme/giris.php',1,'2026-10-26 22:53:35'),(287,10,'Yeni randevu oluşturuldu','Mustafa Açıkgöz için yeni bir randevu alındı.','appointment','/isletme/randevular.php',1,'2026-10-27 23:25:40'),(288,10,'Yeni randevu oluşturuldu','Mustafa Açıkgöz için yeni bir randevu alındı.','appointment','/isletme/randevular.php',1,'2026-10-27 23:28:19'),(303,10,'İşletme hesabı askıya alındı','Hesabınız askıya alındı; destek ekibiyle iletişime geçin.','business_status','/isletme/giris.php',1,'2026-10-30 01:32:38'),(304,10,'İşletme başvurusu onaylandı','İşletme panelinizi kullanabilirsiniz.','business_status','/isletme/giris.php',1,'2026-10-30 01:32:40'),(310,137,'İşletme başvurusu onaylandı','İşletme panelinizi kullanabilirsiniz.','business_status','/isletme/giris.php',0,'2026-10-30 01:49:21'),(311,10,'Yeni randevu oluşturuldu','Test Cust A için yeni bir randevu alındı.','appointment','/isletme/randevular.php',1,'2026-10-30 02:09:30'),(312,10,'Randevu iptal edildi','Randevu #226 iptal edildi.','appointment_cancelled','/isletme/randevular.php',1,'2026-10-30 02:09:30'),(338,171,'İşletme başvurusu onaylandı','İşletme panelinizi kullanabilirsiniz.','business_status','/isletme/giris.php',1,'2026-10-30 11:49:51'),(343,176,'İşletme başvurusu onaylandı','İşletme panelinizi kullanabilirsiniz.','business_status','/isletme/giris.php',1,'2026-10-31 15:52:26'),(344,176,'İşletme hesabı askıya alındı','Hesabınız askıya alındı; destek ekibiyle iletişime geçin.','business_status','/isletme/giris.php',1,'2026-10-31 16:42:53'),(345,176,'İşletme başvurusu onaylandı','İşletme panelinizi kullanabilirsiniz.','business_status','/isletme/giris.php',1,'2026-10-31 16:42:55'),(362,10,'Yeni müşteri yorumu','Mustafa Açıkgöz işletmenizi değerlendirdi.','review','/isletme/yorumlar.php',0,'2026-11-02 16:09:27'),(363,10,'Yeni müşteri yorumu','Mustafa Açıkgöz işletmenizi değerlendirdi.','review','/isletme/yorumlar.php',0,'2026-11-02 16:09:32'),(364,10,'Yeni müşteri yorumu','Mustafa Açıkgöz işletmenizi değerlendirdi.','review','/isletme/yorumlar.php',0,'2026-11-02 16:09:35'),(365,176,'Yeni randevu oluşturuldu','Mustafa Açıkgöz için yeni bir randevu alındı.','appointment','/isletme/randevular.php',0,'2026-11-02 18:36:29'),(366,176,'Yeni müşteri yorumu','Mustafa Açıkgöz işletmenizi değerlendirdi.','review','/isletme/yorumlar.php',0,'2026-11-02 18:48:14');
/*!40000 ALTER TABLE `isletme_bildirimleri` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `isletme_calisma_araliklari`
--

DROP TABLE IF EXISTS `isletme_calisma_araliklari`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `isletme_calisma_araliklari` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `isletme_id` int(11) NOT NULL,
  `haftanin_gunu` tinyint(3) unsigned NOT NULL,
  `baslangic` time NOT NULL,
  `bitis` time NOT NULL,
  `mola_baslangic` time DEFAULT NULL,
  `mola_bitis` time DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_isletme_calisma_gun` (`isletme_id`,`haftanin_gunu`),
  CONSTRAINT `fk_isletme_calisma_isletme` FOREIGN KEY (`isletme_id`) REFERENCES `isletmeler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `isletme_calisma_araliklari`
--

LOCK TABLES `isletme_calisma_araliklari` WRITE;
/*!40000 ALTER TABLE `isletme_calisma_araliklari` DISABLE KEYS */;
INSERT INTO `isletme_calisma_araliklari` VALUES (13,10,1,'09:00:00','20:00:00',NULL,NULL),(14,10,2,'09:00:00','20:00:00',NULL,NULL),(15,10,3,'09:00:00','20:00:00',NULL,NULL),(16,10,4,'09:00:00','20:00:00',NULL,NULL),(17,10,5,'09:00:00','20:00:00','12:00:00','13:00:00'),(18,10,6,'09:00:00','20:00:00',NULL,NULL),(19,10,7,'09:00:00','20:00:00',NULL,NULL),(28,176,1,'09:00:00','20:00:00',NULL,NULL),(29,176,2,'09:00:00','20:00:00',NULL,NULL),(30,176,3,'09:00:00','20:00:00',NULL,NULL),(31,176,4,'09:00:00','20:00:00',NULL,NULL),(32,176,5,'09:00:00','20:00:00',NULL,NULL);
/*!40000 ALTER TABLE `isletme_calisma_araliklari` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `isletme_fotograf_siralama`
--

DROP TABLE IF EXISTS `isletme_fotograf_siralama`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `isletme_fotograf_siralama` (
  `fotograf_id` int(11) NOT NULL,
  `isletme_id` int(11) NOT NULL,
  `sira` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`fotograf_id`),
  KEY `idx_fotograf_siralama_isletme` (`isletme_id`,`sira`),
  CONSTRAINT `fk_fotograf_siralama_fotograf` FOREIGN KEY (`fotograf_id`) REFERENCES `isletme_fotograflari` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fotograf_siralama_isletme` FOREIGN KEY (`isletme_id`) REFERENCES `isletmeler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `isletme_fotograf_siralama`
--

LOCK TABLES `isletme_fotograf_siralama` WRITE;
/*!40000 ALTER TABLE `isletme_fotograf_siralama` DISABLE KEYS */;
INSERT INTO `isletme_fotograf_siralama` VALUES (33,10,2),(55,10,3),(56,10,4),(57,10,5),(39,137,0),(40,137,1),(41,137,2),(42,137,3),(64,171,0),(65,171,1),(66,171,2),(67,171,3),(68,176,0),(69,176,1),(70,176,2);
/*!40000 ALTER TABLE `isletme_fotograf_siralama` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `isletme_fotograflari`
--

DROP TABLE IF EXISTS `isletme_fotograflari`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `isletme_fotograflari` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `isletme_id` int(11) NOT NULL,
  `fotograf_yolu` varchar(255) NOT NULL,
  `olusturulma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `business_id` (`isletme_id`),
  CONSTRAINT `isletme_fotograflari_ibfk_1` FOREIGN KEY (`isletme_id`) REFERENCES `isletmeler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=83 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `isletme_fotograflari`
--

LOCK TABLES `isletme_fotograflari` WRITE;
/*!40000 ALTER TABLE `isletme_fotograflari` DISABLE KEYS */;
INSERT INTO `isletme_fotograflari` VALUES (33,10,'uploads/isletme/10/gallery/abee9c353f048dd32e760d1c8cbe2a365792320f.png','2026-10-26 22:11:23'),(39,137,'uploads/isletme/137/gallery/cabf5e502d232828edf89ac6a86d6191c6d6f48d.png','2026-10-30 02:50:56'),(40,137,'uploads/isletme/137/gallery/80d74eb8a0ef6ac1f1e8d6a70b17b238dd6e018a.png','2026-10-30 02:51:09'),(41,137,'uploads/isletme/137/gallery/c3ffdadffd5da2818e70d3a98807d7e23637b50d.png','2026-10-30 02:51:51'),(42,137,'uploads/isletme/137/gallery/7b5dd2260ba2c5c78bd356f10c69d4fc091d7cee.png','2026-10-30 02:51:52'),(55,10,'uploads/isletme/10/gallery/e7a7bf53258053bfcb78537670870e92cbbd33b5.png','2026-10-30 11:24:29'),(56,10,'uploads/isletme/10/gallery/76a22f0a33f199f649ffbdf287a5ba01d177bf79.png','2026-10-30 11:24:29'),(57,10,'uploads/isletme/10/gallery/f1e75a3c64e7d410c5b59c3d4ef7b498d397e917.png','2026-10-30 11:24:29'),(64,171,'uploads/isletme/171/gallery/7000e52371b1f0e6b1933767b6b4299410c4436b.png','2026-10-30 11:52:01'),(65,171,'uploads/isletme/171/gallery/251e856cee4e1122ba991bc641e111ce5f67a4a3.png','2026-10-30 11:52:01'),(66,171,'uploads/isletme/171/gallery/31d93378b91406526080489bd431179fddfe80cb.png','2026-10-30 11:52:01'),(67,171,'uploads/isletme/171/gallery/3985435a5c13f71008cb25664eadbb6ccbc067f1.png','2026-10-30 11:52:02'),(68,176,'uploads/isletme/176/gallery/287e958c5ee3ea7a5317ca2be1302770b9c4953b.png','2026-10-31 15:59:05'),(69,176,'uploads/isletme/176/gallery/888820da1860cee7122e31a2f799a72ce3b2ee55.png','2026-10-31 15:59:19'),(70,176,'uploads/isletme/176/gallery/bcf191cf94147bc84ad7ccefaec36936bba8999c.png','2026-10-31 15:59:33');
/*!40000 ALTER TABLE `isletme_fotograflari` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `isletme_ozel_calisma_gunleri`
--

DROP TABLE IF EXISTS `isletme_ozel_calisma_gunleri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `isletme_ozel_calisma_gunleri` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `isletme_id` int(11) NOT NULL,
  `tarih` date NOT NULL,
  `kapali` tinyint(1) NOT NULL DEFAULT 1,
  `baslangic` time DEFAULT NULL,
  `bitis` time DEFAULT NULL,
  `not_metni` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_isletme_ozel_gun` (`isletme_id`,`tarih`),
  KEY `idx_isletme_ozel_tarih` (`tarih`),
  CONSTRAINT `fk_isletme_ozel_gun_isletme` FOREIGN KEY (`isletme_id`) REFERENCES `isletmeler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `isletme_ozel_calisma_gunleri`
--

LOCK TABLES `isletme_ozel_calisma_gunleri` WRITE;
/*!40000 ALTER TABLE `isletme_ozel_calisma_gunleri` DISABLE KEYS */;
/*!40000 ALTER TABLE `isletme_ozel_calisma_gunleri` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `isletme_panel_medya`
--

DROP TABLE IF EXISTS `isletme_panel_medya`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `isletme_panel_medya` (
  `isletme_id` int(11) NOT NULL,
  `profil_fotograf_id` int(11) DEFAULT NULL,
  `kapak_fotograf_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`isletme_id`),
  KEY `fk_panel_medya_profil` (`profil_fotograf_id`),
  KEY `fk_panel_medya_kapak` (`kapak_fotograf_id`),
  CONSTRAINT `fk_panel_medya_isletme` FOREIGN KEY (`isletme_id`) REFERENCES `isletmeler` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_panel_medya_kapak` FOREIGN KEY (`kapak_fotograf_id`) REFERENCES `isletme_fotograflari` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_panel_medya_profil` FOREIGN KEY (`profil_fotograf_id`) REFERENCES `isletme_fotograflari` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `isletme_panel_medya`
--

LOCK TABLES `isletme_panel_medya` WRITE;
/*!40000 ALTER TABLE `isletme_panel_medya` DISABLE KEYS */;
INSERT INTO `isletme_panel_medya` VALUES (10,55,33),(137,39,40),(171,66,65),(176,NULL,68);
/*!40000 ALTER TABLE `isletme_panel_medya` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `isletme_yorum_cevaplari`
--

DROP TABLE IF EXISTS `isletme_yorum_cevaplari`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `isletme_yorum_cevaplari` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `degerlendirme_id` int(11) NOT NULL,
  `isletme_id` int(11) NOT NULL,
  `cevap` text NOT NULL,
  `olusturulma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  `guncelleme_tarihi` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_isletme_yorum_cevap` (`degerlendirme_id`),
  KEY `idx_yorum_cevap_isletme` (`isletme_id`),
  CONSTRAINT `fk_yorum_cevap_degerlendirme` FOREIGN KEY (`degerlendirme_id`) REFERENCES `degerlendirmeler` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_yorum_cevap_isletme` FOREIGN KEY (`isletme_id`) REFERENCES `isletmeler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `isletme_yorum_cevaplari`
--

LOCK TABLES `isletme_yorum_cevaplari` WRITE;
/*!40000 ALTER TABLE `isletme_yorum_cevaplari` DISABLE KEYS */;
/*!40000 ALTER TABLE `isletme_yorum_cevaplari` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `isletme_yorum_sikayetleri`
--

DROP TABLE IF EXISTS `isletme_yorum_sikayetleri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `isletme_yorum_sikayetleri` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `degerlendirme_id` int(11) NOT NULL,
  `isletme_id` int(11) NOT NULL,
  `neden` varchar(500) NOT NULL,
  `durum` enum('pending','reviewed','dismissed') NOT NULL DEFAULT 'pending',
  `olusturulma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_isletme_yorum_sikayet` (`degerlendirme_id`,`isletme_id`),
  KEY `fk_yorum_sikayet_isletme` (`isletme_id`),
  KEY `idx_yorum_sikayet_durum` (`durum`),
  CONSTRAINT `fk_yorum_sikayet_degerlendirme` FOREIGN KEY (`degerlendirme_id`) REFERENCES `degerlendirmeler` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_yorum_sikayet_isletme` FOREIGN KEY (`isletme_id`) REFERENCES `isletmeler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `isletme_yorum_sikayetleri`
--

LOCK TABLES `isletme_yorum_sikayetleri` WRITE;
/*!40000 ALTER TABLE `isletme_yorum_sikayetleri` DISABLE KEYS */;
/*!40000 ALTER TABLE `isletme_yorum_sikayetleri` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `isletmeler`
--

DROP TABLE IF EXISTS `isletmeler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `isletmeler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `isletme_sahibi` varchar(150) NOT NULL,
  `isletme_adi` varchar(180) NOT NULL,
  `telefon` varchar(25) NOT NULL,
  `eposta` varchar(190) NOT NULL,
  `sehir` varchar(100) NOT NULL,
  `ilce` varchar(100) NOT NULL,
  `mahalle` varchar(150) DEFAULT NULL,
  `cadde_sokak` varchar(150) DEFAULT NULL,
  `bina_no` varchar(30) DEFAULT NULL,
  `daire_no` varchar(30) DEFAULT NULL,
  `posta_kodu` varchar(20) DEFAULT NULL,
  `tam_adres` text DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `place_id` varchar(255) DEFAULT NULL,
  `location_verified_at` datetime DEFAULT NULL,
  `aciklama` text DEFAULT NULL,
  `sifre_hash` varchar(255) NOT NULL,
  `kvkk_onay_tarihi` datetime DEFAULT NULL,
  `pazarlama_onay` tinyint(1) NOT NULL DEFAULT 0,
  `durum` enum('pending','active','suspended','rejected') NOT NULL DEFAULT 'pending',
  `is_vip` tinyint(1) NOT NULL DEFAULT 0,
  `gece_acik` tinyint(1) NOT NULL DEFAULT 0,
  `son_giris` datetime DEFAULT NULL,
  `eposta_2fa_kod_hash` varchar(255) DEFAULT NULL,
  `eposta_2fa_son_tarih` datetime DEFAULT NULL,
  `eposta_2fa_deneme` int(11) NOT NULL DEFAULT 0,
  `onaylayan_admin_id` int(11) DEFAULT NULL,
  `onay_tarihi` datetime DEFAULT NULL,
  `red_tarihi` datetime DEFAULT NULL,
  `red_gerekce` text DEFAULT NULL,
  `olusturulma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  `guncelleme_tarihi` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone` (`telefon`),
  UNIQUE KEY `email` (`eposta`),
  KEY `idx_isletme_coordinates` (`latitude`,`longitude`),
  KEY `idx_isletme_vip` (`is_vip`),
  KEY `idx_isletme_gece` (`gece_acik`)
) ENGINE=InnoDB AUTO_INCREMENT=189 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `isletmeler`
--

LOCK TABLES `isletmeler` WRITE;
/*!40000 ALTER TABLE `isletmeler` DISABLE KEYS */;
INSERT INTO `isletmeler` VALUES (10,'Mustafa Açıkgöz','Keser Berber salonu','05467209327','mustafaacz37@gmail.com','Aydın','Efeler','cumhuriyet mahallesi','tevfik fikret caddesi','42/4','D:3','09000','Aydın efeler cumhuriyet mahallesi tevfik fikret caddes 42/4',37.84275371,27.83511221,NULL,'2026-10-30 12:25:51','kesim','$2y$10$xPwrHq20vQHD37tF41gkr.2W6yFbkgvZULxOyJWMe3ruoZMxuntS2',NULL,0,'active',0,0,'2026-11-02 18:43:39',NULL,NULL,0,4,'2026-10-17 19:13:51',NULL,NULL,'2026-10-17 16:13:34','2026-11-02 15:43:39'),(137,'Yılmaz','Yılmaz Berber Salonu','0505 508 5158','36504125005@ogr.bozok.edu.tr','İstanbul','Çekmeköy','Taşdelen Mahallesi','Bulvar Sokak','1-3B',NULL,'34788','istanbul çekmeköy taşdelen mahallesi bulvar sokak 1-3B - YılmazBerber',41.03118500,29.23113060,NULL,'2026-10-30 11:40:27',NULL,'$2y$10$oPjDJcUpXLbclj8Wl8K.Su7IwIJTrfk.AyIQ12dmv3u2al5RFVmBm',NULL,0,'active',0,1,'2026-11-02 18:33:23',NULL,NULL,0,4,'2026-10-30 04:49:21',NULL,NULL,'2026-10-30 01:47:31','2026-11-02 15:33:23'),(171,'ömer çetinkaya','Çetinkaya Berber Salonu','05010590721','yusuffurkankucukgozlu@gmail.com','Yozgat','Sorgun','Ahmetefendi Mah',NULL,NULL,NULL,NULL,'Yozgat Sorgun Ahmetefendi mahallesi',39.80986750,35.18543050,NULL,'2026-10-30 14:53:13','','$2y$10$KcdVK6pUbPFCZzA5XVw3yOllmwx39FgOBMzOGPepnUYLY3R263c5u',NULL,0,'active',1,0,'2026-11-02 18:28:55',NULL,NULL,0,4,'2026-10-30 14:49:51',NULL,NULL,'2026-10-30 11:49:07','2026-11-02 15:28:55'),(176,'Gökhan Bayraktar','Bayraktar Berber Salonu','05384439834','mststorehesap@gmail.com','Sivas','Merkez','Sularbaşı','Haydarlı','10',NULL,'58800','Sularbaşı Mah., No: 10, 58800, Merkez, Sivas',39.75213926,37.01819122,NULL,'2026-10-31 18:58:33',NULL,'$2y$10$lr5JM8Icx35pcACinagjMucX3/4E6LFEXsEJ/NBAa5e75PnRMsMCO','2026-10-31 18:52:02',1,'active',1,1,'2026-11-02 21:37:54',NULL,NULL,0,13,'2026-10-31 18:52:26',NULL,NULL,'2026-10-31 15:52:02','2026-11-02 18:54:31');
/*!40000 ALTER TABLE `isletmeler` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_business_status_changed` AFTER UPDATE ON `isletmeler` FOR EACH ROW BEGIN
    IF NEW.durum <> OLD.durum AND NEW.durum IN ('active', 'rejected', 'suspended') THEN
        INSERT INTO isletme_bildirimleri (isletme_id, baslik, mesaj, tur, link)
        VALUES (
            NEW.id,
            CASE NEW.durum WHEN 'active' THEN 'İşletme başvurusu onaylandı' WHEN 'rejected' THEN 'İşletme başvurusu reddedildi' ELSE 'İşletme hesabı askıya alındı' END,
            CASE NEW.durum WHEN 'active' THEN 'İşletme panelinizi kullanabilirsiniz.' WHEN 'rejected' THEN COALESCE(NEW.red_gerekce, 'Başvurunuz reddedildi.') ELSE 'Hesabınız askıya alındı; destek ekibiyle iletişime geçin.' END,
            'business_status', '/isletme/giris.php'
        );
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `kullanici_bildirimleri`
--

DROP TABLE IF EXISTS `kullanici_bildirimleri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kullanici_bildirimleri` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kullanici_id` int(11) NOT NULL,
  `baslik` varchar(255) NOT NULL,
  `mesaj` text NOT NULL,
  `tur` varchar(50) NOT NULL DEFAULT 'info',
  `link` varchar(255) DEFAULT NULL,
  `okundu` tinyint(1) NOT NULL DEFAULT 0,
  `olusturulma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_kullanici_bildirim_okundu` (`kullanici_id`,`okundu`,`olusturulma_tarihi`),
  CONSTRAINT `fk_kullanici_bildirim_kullanici` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kullanici_bildirimleri`
--

LOCK TABLES `kullanici_bildirimleri` WRITE;
/*!40000 ALTER TABLE `kullanici_bildirimleri` DISABLE KEYS */;
/*!40000 ALTER TABLE `kullanici_bildirimleri` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kullanici_portal_profilleri`
--

DROP TABLE IF EXISTS `kullanici_portal_profilleri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kullanici_portal_profilleri` (
  `kullanici_id` int(11) NOT NULL,
  `fotograf_yolu` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`kullanici_id`),
  CONSTRAINT `fk_kullanici_portal_profil_kullanici` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kullanici_portal_profilleri`
--

LOCK TABLES `kullanici_portal_profilleri` WRITE;
/*!40000 ALTER TABLE `kullanici_portal_profilleri` DISABLE KEYS */;
INSERT INTO `kullanici_portal_profilleri` VALUES (6,'uploads/musteri/6/54569588072bf8229fe68140a0d51f8d9d9d252b.png');
/*!40000 ALTER TABLE `kullanici_portal_profilleri` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kullanicilar`
--

DROP TABLE IF EXISTS `kullanicilar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kullanicilar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ad_soyad` varchar(150) NOT NULL,
  `telefon` varchar(25) NOT NULL,
  `eposta` varchar(190) NOT NULL,
  `sehir` varchar(100) NOT NULL DEFAULT '',
  `sifre_hash` varchar(255) NOT NULL,
  `kvkk_onay_tarihi` datetime DEFAULT NULL,
  `pazarlama_onay` tinyint(1) NOT NULL DEFAULT 0,
  `eposta_dogrulandi` tinyint(1) NOT NULL DEFAULT 0,
  `eposta_dogrulama_kodu_hash` varchar(255) DEFAULT NULL,
  `eposta_dogrulama_son_tarih` datetime DEFAULT NULL,
  `eposta_dogrulama_deneme` int(11) NOT NULL DEFAULT 0,
  `sifre_sifirlama_token_hash` varchar(255) DEFAULT NULL,
  `sifre_sifirlama_son_tarih` datetime DEFAULT NULL,
  `sifre_sifirlama_deneme` int(11) NOT NULL DEFAULT 0,
  `yeni_eposta` varchar(190) DEFAULT NULL,
  `yeni_eposta_kodu_hash` varchar(255) DEFAULT NULL,
  `yeni_eposta_son_tarih` datetime DEFAULT NULL,
  `son_giris` datetime DEFAULT NULL,
  `olusturulma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone` (`telefon`),
  UNIQUE KEY `email` (`eposta`)
) ENGINE=InnoDB AUTO_INCREMENT=159 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kullanicilar`
--

LOCK TABLES `kullanicilar` WRITE;
/*!40000 ALTER TABLE `kullanicilar` DISABLE KEYS */;
INSERT INTO `kullanicilar` VALUES (6,'Mustafa Açıkgöz','05467209327','mustafaacz37@gmail.com','Gaziantep','$2y$10$iGV62LFLs5P06ckckbOFC.aAHdVNhLE9ql8KjOniEsID9klqa9G2m',NULL,0,1,NULL,NULL,0,NULL,NULL,0,NULL,NULL,NULL,NULL,'2026-10-17 16:12:35'),(118,'mehmet','05055085158','mehmetucar123q@gmail.com','Ağrı','$2y$10$AzMu.wjz6AzYDO7i3CwrJ./JEAukavKOQEE.qwwSwP0sR7a57tJDe',NULL,0,0,NULL,NULL,0,'e40cbe8b5f4a314a466620672955a53bcdd269ecf9a0d3316fea4b3bfde15ccb','2026-10-31 03:56:21',0,NULL,NULL,NULL,NULL,'2026-10-26 22:57:44'),(119,'Furkan İğde','05525723471','figde0118@gmail.com','Kırıkkale','$2y$10$x2Z7PQz0i9rI0cDWk6bb4uHLKdeoWFOe5yFZ6M4gaJ/ndf8NDNjQa',NULL,0,0,NULL,NULL,0,NULL,NULL,0,NULL,NULL,NULL,NULL,'2026-10-28 15:57:40');
/*!40000 ALTER TABLE `kullanicilar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `randevu_bildirim_kayitlari`
--

DROP TABLE IF EXISTS `randevu_bildirim_kayitlari`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `randevu_bildirim_kayitlari` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `randevu_id` int(11) NOT NULL,
  `bildirim_turu` enum('onay','degisiklik','iptal','hatirlatma_24h','hatirlatma_2h','isletme_yaklasan') NOT NULL,
  `alici_eposta` varchar(190) NOT NULL,
  `gonderim_zamani` datetime NOT NULL,
  `olusturulma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_randevu_bildirim` (`randevu_id`,`bildirim_turu`),
  KEY `idx_randevu` (`randevu_id`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `randevu_bildirim_kayitlari`
--

LOCK TABLES `randevu_bildirim_kayitlari` WRITE;
/*!40000 ALTER TABLE `randevu_bildirim_kayitlari` DISABLE KEYS */;
INSERT INTO `randevu_bildirim_kayitlari` VALUES (52,265,'onay','mustafaacz37@gmail.com','2026-11-02 21:36:29','2026-11-02 18:36:29');
/*!40000 ALTER TABLE `randevu_bildirim_kayitlari` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `randevu_ek_bilgileri`
--

DROP TABLE IF EXISTS `randevu_ek_bilgileri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `randevu_ek_bilgileri` (
  `randevu_id` int(11) NOT NULL,
  `fiyat_snapshot` decimal(10,2) DEFAULT NULL,
  `bitis_zamani` datetime DEFAULT NULL,
  `musteri_notu` text DEFAULT NULL,
  PRIMARY KEY (`randevu_id`),
  KEY `idx_randevu_ek_bilgi_bitis` (`bitis_zamani`),
  CONSTRAINT `fk_randevu_ek_bilgi_randevu` FOREIGN KEY (`randevu_id`) REFERENCES `randevular` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `randevu_ek_bilgileri`
--

LOCK TABLES `randevu_ek_bilgileri` WRITE;
/*!40000 ALTER TABLE `randevu_ek_bilgileri` DISABLE KEYS */;
INSERT INTO `randevu_ek_bilgileri` VALUES (42,300.00,'2026-10-18 15:46:00',NULL),(216,300.00,'2026-10-28 11:30:00','Deneme'),(217,300.00,'2026-10-28 12:00:00',NULL),(265,350.00,'2026-11-03 12:00:00',NULL);
/*!40000 ALTER TABLE `randevu_ek_bilgileri` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `randevular`
--

DROP TABLE IF EXISTS `randevular`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `randevular` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kullanici_id` int(11) NOT NULL,
  `isletme_id` int(11) NOT NULL,
  `calisan_id` int(11) DEFAULT NULL,
  `hizmet_id` int(11) DEFAULT NULL,
  `randevu_zamani` datetime NOT NULL,
  `durum` enum('pending','confirmed','completed','cancelled','rejected') NOT NULL DEFAULT 'pending',
  `hatirlatici_gonderildi` tinyint(1) NOT NULL DEFAULT 0,
  `olusturulma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `customer_id` (`kullanici_id`),
  KEY `business_id` (`isletme_id`),
  KEY `employee_id` (`calisan_id`),
  KEY `service_id` (`hizmet_id`),
  CONSTRAINT `randevular_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`),
  CONSTRAINT `randevular_ibfk_2` FOREIGN KEY (`isletme_id`) REFERENCES `isletmeler` (`id`),
  CONSTRAINT `randevular_ibfk_3` FOREIGN KEY (`calisan_id`) REFERENCES `calisanlar` (`id`) ON DELETE SET NULL,
  CONSTRAINT `randevular_ibfk_4` FOREIGN KEY (`hizmet_id`) REFERENCES `hizmetler` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=266 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `randevular`
--

LOCK TABLES `randevular` WRITE;
/*!40000 ALTER TABLE `randevular` DISABLE KEYS */;
INSERT INTO `randevular` VALUES (42,6,10,NULL,14,'2026-10-18 15:16:00','confirmed',0,'2026-10-17 17:16:51'),(216,6,10,NULL,14,'2026-10-28 11:00:00','confirmed',0,'2026-10-27 23:25:40'),(217,6,10,NULL,14,'2026-10-28 11:30:00','confirmed',0,'2026-10-27 23:28:19'),(265,6,176,NULL,104,'2026-11-03 11:30:00','confirmed',0,'2026-11-02 18:36:29');
/*!40000 ALTER TABLE `randevular` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_business_appointment_conflict_insert` BEFORE INSERT ON `randevular` FOR EACH ROW BEGIN
    DECLARE locked_employee_id INT DEFAULT NULL;
    DECLARE locked_business_id INT DEFAULT NULL;
    DECLARE appointment_end DATETIME;

    IF NEW.calisan_id IS NOT NULL AND NEW.durum IN ('pending', 'confirmed') THEN
        SELECT id INTO locked_employee_id
        FROM calisanlar
        WHERE id = NEW.calisan_id AND isletme_id = NEW.isletme_id
        FOR UPDATE;

        IF locked_employee_id IS NULL THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Appointment employee does not belong to business';
        END IF;

        SET appointment_end = DATE_ADD(
            NEW.randevu_zamani,
            INTERVAL COALESCE((SELECT sure_dakika FROM hizmetler WHERE id = NEW.hizmet_id AND isletme_id = NEW.isletme_id), 30) MINUTE
        );

        IF EXISTS (
            SELECT 1
            FROM randevular r
            LEFT JOIN randevu_ek_bilgileri x ON x.randevu_id = r.id
            LEFT JOIN hizmetler h ON h.id = r.hizmet_id
            WHERE r.calisan_id = NEW.calisan_id
              AND r.durum IN ('pending', 'confirmed')
              AND r.randevu_zamani < appointment_end
              AND COALESCE(x.bitis_zamani, DATE_ADD(r.randevu_zamani, INTERVAL COALESCE(h.sure_dakika, 30) MINUTE)) > NEW.randevu_zamani
        ) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Employee already has an overlapping appointment';
        END IF;
    ELSEIF NEW.calisan_id IS NULL AND NEW.durum IN ('pending', 'confirmed') THEN
        SELECT id INTO locked_business_id
        FROM isletmeler
        WHERE id = NEW.isletme_id
        FOR UPDATE;

        SET appointment_end = DATE_ADD(
            NEW.randevu_zamani,
            INTERVAL COALESCE((SELECT sure_dakika FROM hizmetler WHERE id = NEW.hizmet_id AND isletme_id = NEW.isletme_id), 30) MINUTE
        );

        IF EXISTS (
            SELECT 1
            FROM randevular r
            LEFT JOIN randevu_ek_bilgileri x ON x.randevu_id = r.id
            LEFT JOIN hizmetler h ON h.id = r.hizmet_id
            WHERE r.isletme_id = NEW.isletme_id
              AND r.calisan_id IS NULL
              AND r.durum IN ('pending', 'confirmed')
              AND r.randevu_zamani < appointment_end
              AND COALESCE(x.bitis_zamani, DATE_ADD(r.randevu_zamani, INTERVAL COALESCE(h.sure_dakika, 30) MINUTE)) > NEW.randevu_zamani
        ) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Business owner already has an overlapping appointment';
        END IF;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_business_appointment_created` AFTER INSERT ON `randevular` FOR EACH ROW BEGIN
    INSERT INTO randevu_ek_bilgileri (randevu_id, fiyat_snapshot, bitis_zamani)
    SELECT NEW.id, h.fiyat, DATE_ADD(NEW.randevu_zamani, INTERVAL h.sure_dakika MINUTE)
    FROM hizmetler h
    WHERE h.id = NEW.hizmet_id AND h.isletme_id = NEW.isletme_id;

    INSERT INTO isletme_bildirimleri (isletme_id, baslik, mesaj, tur, link)
    SELECT NEW.isletme_id, 'Yeni randevu oluşturuldu',
           CONCAT(COALESCE(k.ad_soyad, 'Müşteri'), ' için yeni bir randevu alındı.'),
           'appointment', '/isletme/randevular.php'
    FROM kullanicilar k
    WHERE k.id = NEW.kullanici_id;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_business_appointment_conflict_update` BEFORE UPDATE ON `randevular` FOR EACH ROW BEGIN
    DECLARE locked_employee_id INT DEFAULT NULL;
    DECLARE locked_business_id INT DEFAULT NULL;
    DECLARE appointment_end DATETIME;

    IF NEW.calisan_id IS NOT NULL AND NEW.durum IN ('pending', 'confirmed') THEN
        SELECT id INTO locked_employee_id
        FROM calisanlar
        WHERE id = NEW.calisan_id AND isletme_id = NEW.isletme_id
        FOR UPDATE;

        IF locked_employee_id IS NULL THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Appointment employee does not belong to business';
        END IF;

        SET appointment_end = DATE_ADD(
            NEW.randevu_zamani,
            INTERVAL COALESCE((SELECT sure_dakika FROM hizmetler WHERE id = NEW.hizmet_id AND isletme_id = NEW.isletme_id), 30) MINUTE
        );

        IF EXISTS (
            SELECT 1
            FROM randevular r
            LEFT JOIN randevu_ek_bilgileri x ON x.randevu_id = r.id
            LEFT JOIN hizmetler h ON h.id = r.hizmet_id
            WHERE r.calisan_id = NEW.calisan_id
              AND r.id <> NEW.id
              AND r.durum IN ('pending', 'confirmed')
              AND r.randevu_zamani < appointment_end
              AND COALESCE(x.bitis_zamani, DATE_ADD(r.randevu_zamani, INTERVAL COALESCE(h.sure_dakika, 30) MINUTE)) > NEW.randevu_zamani
        ) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Employee already has an overlapping appointment';
        END IF;
    ELSEIF NEW.calisan_id IS NULL AND NEW.durum IN ('pending', 'confirmed') THEN
        SELECT id INTO locked_business_id
        FROM isletmeler
        WHERE id = NEW.isletme_id
        FOR UPDATE;

        SET appointment_end = DATE_ADD(
            NEW.randevu_zamani,
            INTERVAL COALESCE((SELECT sure_dakika FROM hizmetler WHERE id = NEW.hizmet_id AND isletme_id = NEW.isletme_id), 30) MINUTE
        );

        IF EXISTS (
            SELECT 1
            FROM randevular r
            LEFT JOIN randevu_ek_bilgileri x ON x.randevu_id = r.id
            LEFT JOIN hizmetler h ON h.id = r.hizmet_id
            WHERE r.isletme_id = NEW.isletme_id
              AND r.calisan_id IS NULL
              AND r.id <> NEW.id
              AND r.durum IN ('pending', 'confirmed')
              AND r.randevu_zamani < appointment_end
              AND COALESCE(x.bitis_zamani, DATE_ADD(r.randevu_zamani, INTERVAL COALESCE(h.sure_dakika, 30) MINUTE)) > NEW.randevu_zamani
        ) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Business owner already has an overlapping appointment';
        END IF;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_business_appointment_cancelled` AFTER UPDATE ON `randevular` FOR EACH ROW BEGIN
    IF NEW.durum = 'cancelled' AND OLD.durum <> 'cancelled' THEN
        INSERT INTO isletme_bildirimleri (isletme_id, baslik, mesaj, tur, link)
        VALUES (NEW.isletme_id, 'Randevu iptal edildi', CONCAT('Randevu #', NEW.id, ' iptal edildi.'), 'appointment_cancelled', '/isletme/randevular.php');
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `site_ayarlari`
--

DROP TABLE IF EXISTS `site_ayarlari`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_ayarlari` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `anahtar` varchar(100) NOT NULL,
  `deger` longtext DEFAULT NULL,
  `tur` varchar(30) NOT NULL DEFAULT 'text',
  `aciklama` varchar(255) DEFAULT NULL,
  `guncelleme_tarihi` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_site_anahtar` (`anahtar`)
) ENGINE=InnoDB AUTO_INCREMENT=211 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_ayarlari`
--

LOCK TABLES `site_ayarlari` WRITE;
/*!40000 ALTER TABLE `site_ayarlari` DISABLE KEYS */;
INSERT INTO `site_ayarlari` VALUES (1,'site_baslik','BiTıraş.com','text','Site başlığı','2026-10-31 00:28:15'),(2,'site_aciklama','Türkiye\'nin en seçkin berber ve erkek kuaförlerinden anında online randevu alın.','text','Meta açıklaması',NULL),(3,'hero_baslik','Tarzını Keşfet, Sıra Beklemeden Randevunu Al','text','Hero bölümü ana başlık',NULL),(4,'hero_aciklama','Şehrinizdeki en iyi erkek kuaförlerini ve VIP berber salonlarını karşılaştırın, dakikalar içinde boş koltuğunuzu ayırtın.','textarea','Hero bölümü açıklama',NULL),(5,'duyuru_bandi','Sitemiz Yapım Aşamasındadır Yakında Görüşmek Üzere..','text','Üst duyuru bandı metni',NULL),(6,'duyuru_aktif','0','boolean','Duyuru bandı görünür mü?','2026-10-31 00:29:10'),(7,'iletisim_eposta','bitirasofficial@gmail.com','text','İletişim e-posta adresi','2026-10-28 16:04:15'),(8,'iletisim_telefon','0505 508 5158','text','İletişim telefon numarası','2026-10-17 15:31:14'),(9,'footer_metin','Sitemiz Yapım Aşamasındadır Yakında Görüşmek Üzere..','textarea','Footer açıklama metni',NULL),(10,'footer_telif','© 2026 BiTıraş.com - Tüm Hakları Saklıdır.','text','Footer telif metni','2026-10-28 16:04:15'),(11,'smtp_host','','text','SMTP sunucu adresi',NULL),(12,'smtp_port','587','text','SMTP port',NULL),(13,'smtp_kullanici','admin@bitiras.com','text','SMTP kullanıcı adı','2026-10-16 22:49:50'),(14,'smtp_sifre','Asdasd12.','password','SMTP şifre','2026-10-17 15:21:15'),(15,'smtp_gonderen_eposta','','text','Gönderen e-posta adresi',NULL),(16,'smtp_gonderen_ad','BiTıraş.com','text','Gönderen adı',NULL),(17,'smtp_sifreleme','tls','text','SMTP şifreleme (tls/ssl)',NULL),(181,'google_maps_api_key','','text','Google Maps JavaScript & Geocoding API Anahtarı',NULL);
/*!40000 ALTER TABLE `site_ayarlari` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yonetici_isletme_kapsami`
--

DROP TABLE IF EXISTS `yonetici_isletme_kapsami`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yonetici_isletme_kapsami` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `yonetici_id` int(11) NOT NULL,
  `isletme_id` int(11) NOT NULL,
  `olusturulma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_yonetici_isletme` (`yonetici_id`,`isletme_id`),
  KEY `idx_scope_yonetici` (`yonetici_id`),
  KEY `idx_scope_isletme` (`isletme_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yonetici_isletme_kapsami`
--

LOCK TABLES `yonetici_isletme_kapsami` WRITE;
/*!40000 ALTER TABLE `yonetici_isletme_kapsami` DISABLE KEYS */;
INSERT INTO `yonetici_isletme_kapsami` VALUES (6,3,1,'2026-10-16 23:46:08'),(10,5,1,'2026-10-19 20:39:08');
/*!40000 ALTER TABLE `yonetici_isletme_kapsami` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yonetici_izinleri`
--

DROP TABLE IF EXISTS `yonetici_izinleri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yonetici_izinleri` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `yonetici_id` int(11) NOT NULL,
  `izin_anahtari` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_yonetici_izin` (`yonetici_id`,`izin_anahtari`),
  KEY `fk_yizin_yonetici` (`yonetici_id`),
  CONSTRAINT `fk_yizin_yonetici` FOREIGN KEY (`yonetici_id`) REFERENCES `yoneticiler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=131 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yonetici_izinleri`
--

LOCK TABLES `yonetici_izinleri` WRITE;
/*!40000 ALTER TABLE `yonetici_izinleri` DISABLE KEYS */;
INSERT INTO `yonetici_izinleri` VALUES (124,7,'appointments.cancel'),(123,7,'appointments.edit'),(121,7,'appointments.view'),(122,7,'appointments.view_details'),(120,7,'business_photos.delete'),(119,7,'business_photos.upload'),(118,7,'business_photos.view'),(112,7,'businesses.approve'),(117,7,'businesses.archive'),(114,7,'businesses.edit'),(116,7,'businesses.reactivate'),(113,7,'businesses.reject'),(115,7,'businesses.suspend'),(110,7,'businesses.view'),(111,7,'businesses.view_details'),(130,7,'reviews.delete'),(129,7,'reviews.moderate'),(128,7,'reviews.view'),(126,7,'users.edit'),(127,7,'users.suspend'),(125,7,'users.view');
/*!40000 ALTER TABLE `yonetici_izinleri` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yoneticiler`
--

DROP TABLE IF EXISTS `yoneticiler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yoneticiler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ad_soyad` varchar(150) NOT NULL,
  `eposta` varchar(190) NOT NULL,
  `sifre_hash` varchar(255) NOT NULL,
  `rol` enum('admin','manager') NOT NULL DEFAULT 'manager',
  `kapsam_turu` enum('tum_isletmeler','belirli_isletmeler') NOT NULL DEFAULT 'tum_isletmeler',
  `yetki_versiyonu` int(11) NOT NULL DEFAULT 1,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `iki_adimli_tur` enum('none','totp','email') NOT NULL DEFAULT 'none',
  `totp_gizli_anahtar` varchar(255) DEFAULT NULL,
  `kurtarma_kodlari_hash` text DEFAULT NULL,
  `eposta_2fa_kod_hash` varchar(255) DEFAULT NULL,
  `eposta_2fa_son_tarih` datetime DEFAULT NULL,
  `eposta_2fa_deneme` int(11) NOT NULL DEFAULT 0,
  `son_giris` datetime DEFAULT NULL,
  `olusturan_admin_id` int(11) DEFAULT NULL,
  `davet_token` varchar(255) DEFAULT NULL,
  `davet_token_bitis` datetime DEFAULT NULL,
  `olusturulma_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  `guncelleme_tarihi` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `son_totp_zaman_dilimi` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_yoneticiler_eposta` (`eposta`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yoneticiler`
--

LOCK TABLES `yoneticiler` WRITE;
/*!40000 ALTER TABLE `yoneticiler` DISABLE KEYS */;
INSERT INTO `yoneticiler` VALUES (4,'Yusuf (Admin)','yusuf107838@gmail.com','$2y$10$vLkRfVsMNHxaKfhJMN35BuTz/aR5wuJeutRw0Tn2Q4yyrzgggeblS','admin','tum_isletmeler',1,1,'none',NULL,NULL,NULL,NULL,0,'2026-11-02 21:54:10',NULL,NULL,NULL,'2026-10-17 15:20:07','2026-11-02 18:54:10',NULL),(7,'Furkan iğde','figde0118@gmail.com','$2y$10$hoBx8Ri.YGeAiGW4dmQzIuXKiFQJaC2qBrkds.kXj8tLPl/mNBUFa','manager','tum_isletmeler',3,1,'none',NULL,NULL,NULL,NULL,0,'2026-11-02 22:08:23',4,NULL,NULL,'2026-10-27 08:38:29','2026-11-02 19:08:23',NULL),(13,'Mustafa Açıkgöz','mustafaacz37@gmail.com','$2y$10$XVYyjGKbF82sBOaFH6eJMexW38qf3ARQ3FAmXIsi4Bg3JAebfGNii','admin','tum_isletmeler',1,1,'none',NULL,NULL,NULL,NULL,0,'2026-10-31 19:33:32',4,NULL,NULL,'2026-10-28 20:33:53','2026-10-31 16:33:32',NULL);
/*!40000 ALTER TABLE `yoneticiler` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-02 22:45:02
