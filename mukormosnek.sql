-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Gép: mysql.omega:3306
-- Létrehozás ideje: 2026. Jún 08. 15:27
-- Kiszolgáló verziója: 10.11.14-MariaDB-0+deb12u2
-- PHP verzió: 7.2.34-63+0~20260514.120+debian12~1.gbp709ce5

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Adatbázis: `kozmetikusnak`
--

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `booking_ref` varchar(20) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `customer_phone` varchar(30) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `bookings`
--

INSERT INTO `bookings` (`id`, `booking_ref`, `staff_id`, `service_id`, `customer_name`, `customer_email`, `customer_phone`, `booking_date`, `start_time`, `end_time`, `status`, `notes`, `created_at`) VALUES
(5, 'BB-20260412-D3EE', 1, 5, 'fegsgds hjgdjgdjg', 'hello@sarizoltan.hu', '0611212112', '2026-04-14', '09:20:00', '09:40:00', 'confirmed', 'Foglalás összegzése\nKezelés\nExpressz arckezelés\nKozmetikus\nKiss Anna\nDátum\n2026. április 14., kedd\nIdőpont\n09:20 – 09:40\nIdőtartam\n20 perc', '2026-04-12 08:51:55'),
(6, 'BB-20260412-1291', 2, 6, 'sfaegshgrsgs', 'hello@sarizoltan.hu', '0611212112', '2026-04-20', '17:00:00', '18:00:00', 'pending', 'bsbs', '2026-04-12 09:03:29'),
(7, 'BB-20260412-F503', 3, 6, 'Sári Zoltán', 'hello@sarizoltan.hu', '0611212112', '2026-04-28', '13:00:00', '14:00:00', 'confirmed', 'hdrhdjtfjtfh zjsfjfjtf jrzjrsjthdrs', '2026-04-12 09:05:21'),
(8, 'BB-20260414-36F0', 1, 1, 'Sári Zoltán', 'weboldalajanlatok@gmail.com', '0611212112', '2026-04-16', '11:00:00', '11:30:00', 'pending', 'hdhfd', '2026-04-14 10:09:39'),
(9, 'BB-20260414-D6AA', 1, 1, 'Sári Zoltán', 'sari.zoltan@cukorbetegreceptek.hu', '0611212112', '2026-04-24', '15:00:00', '15:30:00', 'pending', 'hdhdfhfdhdf', '2026-04-14 10:46:33'),
(10, 'BB-20260414-03FB', 2, 2, 'Sári Zoltán', 'sari.zoltan@cukorbetegreceptek.hu', '0611212112', '2026-04-30', '12:00:00', '12:45:00', 'cancelled', '', '2026-04-14 11:06:58');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `phone`, `message`, `status`, `created_at`) VALUES
(1, 'Sári Zoltán', 'weboldalajanlatok@gmail.com', '06206267127', 'gsgsdgsdgsd', 'read', '2026-04-08 14:55:55'),
(2, 'vsvsbdb rhrhrh', 'hello@sarizoltan.hu', '06206267127', 'hdhdhd', 'new', '2026-04-12 08:44:53'),
(3, 'Sári Zoltán', 'sari.zoltan@cukorbetegreceptek.hu', '06 20 626 71 27', 'dncgndtnhdthd tddjdthgd', 'replied', '2026-04-14 10:29:43'),
(4, 'Sári Zoltán', 'sari.zoltan@cukorbetegreceptek.hu', 'fafadf', 'adfadgdsgsdgsgsd sgsrgrwgws', 'new', '2026-04-14 10:45:20');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `media`
--

CREATE TABLE `media` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `filepath` varchar(255) NOT NULL,
  `filetype` varchar(50) DEFAULT NULL,
  `filesize` int(11) DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `media`
--

INSERT INTO `media` (`id`, `filename`, `filepath`, `filetype`, `filesize`, `alt_text`, `uploaded_at`) VALUES
(16, 'tra-bg_1780924665_0.png', 'tra-bg_1780924665_0.png', 'image/png', 39610, '', '2026-06-08 13:17:45'),
(17, 'pexels-jose-antonio-otegui-auzmendi-2150489988-34930139_1780924819_0.jpg', 'pexels-jose-antonio-otegui-auzmendi-2150489988-34930139_1780924819_0.jpg', 'image/jpeg', 599939, '', '2026-06-08 13:20:19');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `menus`
--

CREATE TABLE `menus` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `location` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `menus`
--

INSERT INTO `menus` (`id`, `name`, `location`) VALUES
(1, 'Főmenü', 'header'),
(2, 'Lábléc menü', 'footer');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT 0,
  `label` varchar(100) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `page_id` int(11) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `target` varchar(20) DEFAULT '_self'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `menu_items`
--

INSERT INTO `menu_items` (`id`, `menu_id`, `parent_id`, `label`, `url`, `page_id`, `sort_order`, `target`) VALUES
(1, 1, 0, 'Főoldal', '/', NULL, 1, '_self'),
(8, 2, 0, 'Adatvédelem', '/adatvedelem', NULL, 1, '_self'),
(9, 2, 0, 'ÁSZF', '/aszf', NULL, 2, '_self'),
(16, 1, 0, 'Kapcsolat', '/contact.php', NULL, 17, '_self'),
(21, 1, 0, 'Időpontfoglalás', '/foglalas', NULL, 18, '_self'),
(23, 1, 0, 'Kozmetikusaink', '/mukormoseink', NULL, 15, '_self'),
(24, 1, 0, 'Kezelések', '/kezelesek', NULL, 14, '_self'),
(25, 1, 0, 'Rólunk', '/rolunk', NULL, 16, '_self');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `pages`
--

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `schema_type` varchar(50) DEFAULT 'WebPage',
  `status` enum('published','draft') DEFAULT 'draft',
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `pages`
--

INSERT INTO `pages` (`id`, `title`, `slug`, `content`, `meta_title`, `meta_description`, `schema_type`, `status`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Főoldal', 'fooldal', '<h1>Üdvözlünk a Kozmetikus Szalonban!</h1><p>Foglalj időpontot online, gyorsan és egyszerűen.</p>', 'Kozmetikus Szalon – Ragyogó arckezelések', 'Luxus kozmetikus szalon Budapest szívében. Foglalj időpontot online!', 'LocalBusiness', 'published', 0, '2026-04-08 11:31:19', '2026-04-08 11:31:19'),
(2, 'Rólunk', 'rolunk', '<h2>Rólunk</h2><p>Több mint 10 éve nyújtunk prémium kozmetikus kezeléseket.</p>', 'Rólunk – Kozmetikus Szalon', 'Ismerj meg minket! Tapasztalt kozmetikuseink várnak.', 'AboutPage', 'published', 0, '2026-04-08 11:31:19', '2026-04-08 11:31:19'),
(3, 'Kapcsolat', 'kapcsolat', '<h2>Kapcsolat</h2><p>Vedd fel velünk a kapcsolatot!</p>', 'Kapcsolat – Kozmetikus Szalon', 'Kapcsolatfelvétel a Kozmetikus Szalonnal.', 'ContactPage', 'published', 0, '2026-04-08 11:31:19', '2026-04-08 11:31:19'),
(4, 'Kezelések', 'kezelesek', '<p>[services]</p>\r\n<h1 style=\"text-align: center;\">Kozmetikusaink</h1>\r\n<p>[staff]</p>\r\n<p>&nbsp;</p>', '', '', 'ServicePage', 'published', 0, '2026-04-08 13:50:25', '2026-04-08 16:42:03'),
(5, 'Kozmetikusaink', 'mukormoseink', '<p>[staff]</p>', '', '', 'WebPage', 'published', 0, '2026-04-08 16:46:29', '2026-04-08 16:46:29');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `status` enum('published','draft') DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `posts`
--

INSERT INTO `posts` (`id`, `title`, `slug`, `excerpt`, `content`, `featured_image`, `author_id`, `meta_title`, `meta_description`, `status`, `published_at`, `created_at`) VALUES
(1, '2026 bőrtrendjei: elegáns nude és finom csillogás', '2026-koromtrendjei-elegans-nude-es-finom-csillogas', 'Mutatjuk az idei szezon legkedveltebb kozmetikus színeit és formáit.', '<p>Az idei szezonban a letisztult nude &aacute;rnyalatok, a finom kr&oacute;m effektek &eacute;s a visszafogott d&iacute;sz&iacute;t&eacute;sek h&oacute;d&iacute;tanak.</p>\r\n<p>Ha tart&oacute;s, m&eacute;gis eleg&aacute;ns megjelen&eacute;st szeretn&eacute;l, a r&ouml;videbb mandula forma &eacute;s a p&uacute;deres r&oacute;zsasz&iacute;n t&oacute;nus t&ouml;k&eacute;letes v&aacute;laszt&aacute;s.</p>', 'post_1780920748_6a26b1ac940a1.jpg', 1, '2026 bőrtrendjei: elegáns nude és finom csillogás', 'Mutatjuk az idei szezon legkedveltebb kozmetikus színeit és formáit.', 'published', '2026-04-08 16:20:43', '2026-04-08 16:20:43'),
(2, 'Hydra kezelés tartósság 5 lépésben', 'hydra-kezeles-tartossag-5-lepesben', 'Így marad makulátlan a hydra kezelésed akár 3-4 hétig.', '<p>A tart&oacute;s hydra kezel&eacute;s titka a megfelelő elők&eacute;sz&iacute;t&eacute;s, a professzion&aacute;lis alapanyag &eacute;s az ut&oacute;&aacute;pol&aacute;s.</p>\r\n<p>Mindig haszn&aacute;lj hidrat&aacute;l&oacute; sz&eacute;rumot, &eacute;s ker&uuml;ld az erős vegyszereket v&eacute;delem n&eacute;lk&uuml;l.</p>', 'post_1780920740_6a26b1a4aa45b.jpg', 1, 'Hydra kezelés tartósság 5 lépésben', 'Így marad makulátlan a hydra kezelésed akár 3-4 hétig.', 'published', '2026-04-08 16:23:44', '2026-04-08 16:23:44'),
(3, 'Mikor válassz bőrfiatalító kezelést?', 'mikor-valassz-borfiatalito-kezelest', 'Segítünk eldönteni, mikor ideális egy célzott bőrfiatalító kezelés.', '<p>A bőrfiatal&iacute;t&oacute; kezel&eacute;s akkor ide&aacute;lis, ha feszesebb, egys&eacute;gesebb bőrképet szeretn&eacute;l.</p>\r\n<p>Vend&eacute;geinkn&eacute;l k&uuml;l&ouml;n&ouml;sen n&eacute;pszerű alkalmak előtt, hiszen l&aacute;that&oacute;an jav&iacute;tja az arcbőr megjelen&eacute;s&eacute;t.</p>', 'post_1780920733_6a26b19db1715.jpg', 1, 'Mikor válassz bőrfiatalító kezelést?', 'Segítünk eldönteni, mikor ideális egy célzott bőrfiatalító kezelés.', 'published', '2026-04-08 16:26:04', '2026-04-08 16:26:04');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `post_categories`
--

CREATE TABLE `post_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `post_categories`
--

INSERT INTO `post_categories` (`id`, `name`, `slug`, `description`, `sort_order`) VALUES
(1, 'Manikűr', 'manikur', NULL, 1),
(2, 'Hydra kezelés', 'gellakk', NULL, 2),
(3, 'Tippek', 'tippek', NULL, 3),
(4, 'Hírek', 'hirek', NULL, 4);

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `post_category_pivot`
--

CREATE TABLE `post_category_pivot` (
  `post_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `post_category_pivot`
--

INSERT INTO `post_category_pivot` (`post_id`, `category_id`) VALUES
(1, 1),
(2, 2),
(2, 3),
(3, 3),
(3, 4);

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `post_tags`
--

CREATE TABLE `post_tags` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `post_tags`
--

INSERT INTO `post_tags` (`id`, `name`, `slug`) VALUES
(1, 'arckezelés', 'manikur'),
(2, 'hidra kezelés', 'gellakk'),
(3, 'nail-art', 'nail-art'),
(4, 'ápolás', 'apolas');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `post_tag_pivot`
--

CREATE TABLE `post_tag_pivot` (
  `post_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `post_tag_pivot`
--

INSERT INTO `post_tag_pivot` (`post_id`, `tag_id`) VALUES
(1, 1),
(1, 2),
(2, 3),
(2, 4),
(3, 2),
(3, 3);

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `schedule_exceptions`
--

CREATE TABLE `schedule_exceptions` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `exception_date` date NOT NULL,
  `is_closed` tinyint(1) DEFAULT 1,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `duration` int(11) NOT NULL DEFAULT 30,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `category` varchar(50) DEFAULT 'Hajvágás',
  `sort_order` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `services`
--

INSERT INTO `services` (`id`, `name`, `description`, `duration`, `price`, `category`, `sort_order`, `active`) VALUES
(1, 'Expressz arctisztítás', 'Gyors, frissítő arctisztítás ragyogó bőrért.', 30, 6500.00, 'Arckezelés', 1, 1),
(2, 'Hydra kezelés klasszikus', 'Mélyhidratáló kezelés az üde, sima arcbőrért.', 45, 8900.00, 'Arckezelés', 2, 1),
(3, 'Bőrfiatalító kezelés', 'Célzott anti-age kezelés feszesebb, üdébb bőrért.', 55, 9900.00, 'Bőrfiatalítás', 3, 1),
(4, 'Szemöldök formázás', 'Precíz szemöldökigazítás és festés harmonikus archoz.', 45, 7900.00, 'Szemöldök', 4, 1),
(5, 'Professzionális smink', 'Alkalmi vagy nappali smink személyre szabottan.', 30, 8500.00, 'Smink', 5, 1),
(6, 'Luxus arckezelés', 'Prémium bőrápoló rituálé masszázzsal és hidratáló pakolással.', 60, 11900.00, 'Prémium', 6, 1);

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('barbers_label', 'Szakértő csapatunk'),
('barbers_limit', '4'),
('barbers_show', '1'),
('barbers_subtitle', 'Kreatív, precíz szakemberek, akik minden alkalomra elegáns körmöket készítenek.'),
('barbers_title', 'Ismerd meg Kozmetikusainkat'),
('blog_label', 'Trendek & Tippek'),
('blog_limit', '3'),
('blog_show', '1'),
('blog_title', 'Legújabb Bejegyzéseink'),
('booking_advance_days', '30'),
('booking_close_time', '18:00'),
('booking_interval', '30'),
('booking_open_time', '09:00'),
('contact_label', 'Kapcsolat'),
('contact_show', '1'),
('contact_title', 'Kapcsolat & Nyitvatartás'),
('cta_bg_image', ''),
('cta_btn1_text', 'Időpontfoglalás'),
('cta_btn1_url', '/foglalas'),
('cta_btn2_text', 'Hívj minket'),
('cta_label', 'Ne várj tovább'),
('cta_show', '1'),
('cta_text', 'Válassz kezelést és kozmetikust, a többit pedig bízd ránk.'),
('cta_title', 'Foglalj luxus bőrkezelést még ma!'),
('facebook_url', ''),
('feature1_icon', 'fas fa-hand-sparkles'),
('feature1_text', 'Kozmetikusaink a legújabb technikákkal dolgoznak a tartós és elegáns végeredményért.'),
('feature1_title', 'Prémium szakértelem'),
('feature2_icon', 'fas fa-palette'),
('feature2_text', 'Foglalj pár kattintással kezelést és kozmetikust, amikor neked a legkényelmesebb.'),
('feature2_title', 'Online foglalás'),
('feature3_icon', 'fas fa-gem'),
('feature3_text', 'Minőségi hidra kezelésokkal és professzionális anyagokkal gondoskodunk körmeid szépségéről.'),
('feature3_title', 'Luxus alapanyagok'),
('feature4_icon', 'fas fa-wand-magic-sparkles'),
('feature4_text', 'Minden szettet a személyiségedhez és alkalmaidhoz igazítunk.'),
('feature4_title', 'Személyre szabott stílus'),
('features_label', 'Miért minket válassz'),
('features_show', '1'),
('features_title', 'A különbség amit érezni fogsz'),
('footer_text', '© 2026 Kozmetikus Szalon. Minden jog fenntartva.'),
('google_maps_embed', ''),
('hero_bg_image', 'hero_bg_image_1780920758.jpg'),
('hero_btn1_text', 'Időpontfoglalás'),
('hero_btn1_url', '/foglalas'),
('hero_btn2_text', 'Kezelések'),
('hero_btn2_url', '#services'),
('hero_label', 'Luxus Kozmetikus Szalon'),
('hero_stat1_label', 'Év tapasztalat'),
('hero_stat1_num', '10+'),
('hero_stat2_label', 'Kozmetikus'),
('hero_stat3_label', 'Elégedett ügyfél'),
('hero_stat3_num', '500+'),
('hero_subtitle', 'Tapasztalt kozmetikuseink gondoskodnak róla, hogy kezeid mindig ápoltak és elegánsak legyenek.'),
('hero_title_line1', 'Ragyogó arckezelések'),
('hero_title_line2', 'a kezeid ékszere.'),
('instagram_url', ''),
('map_type', 'openstreet'),
('meta_description', 'Prémium kozmetikus szalon időpontfoglalás online.'),
('osm_height', '400'),
('osm_lat', '47.499461'),
('osm_lng', '19.055271'),
('osm_marker_label', 'Kozmetikus Szalon'),
('osm_zoom', '16'),
('services_label', 'Kiemelt kezelések'),
('services_limit', '6'),
('services_show', '1'),
('services_subtitle', 'Minden kezelésünket prémium anyagokkal és kifinomult technikával végezzük.'),
('services_title', 'Elegáns Szalonkezelések'),
('site_address', '1061 Budapest, Andrássy út 1.'),
('site_email', 'hello@kozmetika.hu'),
('site_favicon', ''),
('site_logo', ''),
('site_name', 'Kozmetikus Szalon'),
('site_phone', '+36 1 234 5678'),
('site_tagline', 'Ragyogó arckezelések, hidra kezelés és műbőr kezelések');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `bio` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `staff`
--

INSERT INTO `staff` (`id`, `name`, `bio`, `photo`, `email`, `phone`, `sort_order`, `active`, `created_at`) VALUES
(1, 'Kiss Anna', 'Precíz arckezelés és modern hidra kezelés technikák szakértője.', 'staff_1780924743_6a26c147ade31.jpg', 'anna@kozmetika.hu', '', 1, 1, '2026-04-08 11:31:19'),
(2, 'Nagy Viktória', 'Elegáns épített műbőr és francia stílus mestere.', 'staff_1780924750_6a26c14e7697a.jpg', 'viktoria@kozmetika.hu', '', 2, 1, '2026-04-08 11:31:19'),
(3, 'Tóth Réka', 'Kreatív nail art díszítések specialistája.', 'staff_1780924757_6a26c155ce573.jpg', 'reka@kozmetika.hu', '', 3, 1, '2026-04-08 11:31:19');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `staff_services`
--

CREATE TABLE `staff_services` (
  `staff_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `staff_services`
--

INSERT INTO `staff_services` (`staff_id`, `service_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(2, 1),
(2, 2),
(2, 3),
(2, 4),
(2, 5),
(2, 6),
(3, 1),
(3, 2),
(3, 3),
(3, 4),
(3, 5),
(3, 6);

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('superadmin','admin','editor') DEFAULT 'admin',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `email`, `role`, `active`, `created_at`) VALUES
(1, 'sarizoltan', '$2y$10$0aQQ1zUALnvFGzobeb56j.jh7.17i3ZTUdVzsG.pizYh.HvHN7mkS', 'weboldalajanlatok@gmail.com', 'superadmin', 1, '2026-04-08 11:31:18');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `working_hours`
--

CREATE TABLE `working_hours` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `day_of_week` tinyint(4) NOT NULL,
  `start_time` time NOT NULL DEFAULT '09:00:00',
  `end_time` time NOT NULL DEFAULT '18:00:00',
  `is_day_off` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `working_hours`
--

INSERT INTO `working_hours` (`id`, `staff_id`, `day_of_week`, `start_time`, `end_time`, `is_day_off`) VALUES
(1, 1, 0, '09:00:00', '18:00:00', 0),
(2, 1, 1, '09:00:00', '18:00:00', 0),
(3, 1, 2, '09:00:00', '18:00:00', 0),
(4, 1, 3, '09:00:00', '18:00:00', 0),
(5, 1, 4, '09:00:00', '18:00:00', 0),
(6, 1, 5, '09:00:00', '16:00:00', 0),
(7, 1, 6, '00:00:00', '00:00:00', 1),
(8, 2, 0, '09:00:00', '18:00:00', 0),
(9, 2, 1, '09:00:00', '18:00:00', 0),
(10, 2, 2, '09:00:00', '18:00:00', 0),
(11, 2, 3, '09:00:00', '18:00:00', 0),
(12, 2, 4, '09:00:00', '18:00:00', 0),
(13, 2, 5, '09:00:00', '16:00:00', 0),
(14, 2, 6, '00:00:00', '00:00:00', 1),
(15, 3, 0, '09:00:00', '18:00:00', 0),
(16, 3, 1, '09:00:00', '18:00:00', 0),
(17, 3, 2, '09:00:00', '18:00:00', 0),
(18, 3, 3, '09:00:00', '18:00:00', 0),
(19, 3, 4, '09:00:00', '18:00:00', 0),
(20, 3, 5, '09:00:00', '16:00:00', 0),
(21, 3, 6, '00:00:00', '00:00:00', 1);

--
-- Indexek a kiírt táblákhoz
--

--
-- A tábla indexei `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_ref` (`booking_ref`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `service_id` (`service_id`);

--
-- A tábla indexei `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `menu_id` (`menu_id`);

--
-- A tábla indexei `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- A tábla indexei `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `author_id` (`author_id`);

--
-- A tábla indexei `post_categories`
--
ALTER TABLE `post_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- A tábla indexei `post_category_pivot`
--
ALTER TABLE `post_category_pivot`
  ADD PRIMARY KEY (`post_id`,`category_id`),
  ADD KEY `category_id` (`category_id`);

--
-- A tábla indexei `post_tags`
--
ALTER TABLE `post_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- A tábla indexei `post_tag_pivot`
--
ALTER TABLE `post_tag_pivot`
  ADD PRIMARY KEY (`post_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- A tábla indexei `schedule_exceptions`
--
ALTER TABLE `schedule_exceptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- A tábla indexei `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- A tábla indexei `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `staff_services`
--
ALTER TABLE `staff_services`
  ADD PRIMARY KEY (`staff_id`,`service_id`),
  ADD KEY `service_id` (`service_id`);

--
-- A tábla indexei `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- A tábla indexei `working_hours`
--
ALTER TABLE `working_hours`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- A kiírt táblák AUTO_INCREMENT értéke
--

--
-- AUTO_INCREMENT a táblához `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT a táblához `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT a táblához `media`
--
ALTER TABLE `media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT a táblához `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT a táblához `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT a táblához `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT a táblához `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT a táblához `post_categories`
--
ALTER TABLE `post_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT a táblához `post_tags`
--
ALTER TABLE `post_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT a táblához `schedule_exceptions`
--
ALTER TABLE `schedule_exceptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT a táblához `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT a táblához `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT a táblához `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT a táblához `working_hours`
--
ALTER TABLE `working_hours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Megkötések a kiírt táblákhoz
--

--
-- Megkötések a táblához `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`);

--
-- Megkötések a táblához `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Megkötések a táblához `post_category_pivot`
--
ALTER TABLE `post_category_pivot`
  ADD CONSTRAINT `post_category_pivot_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_category_pivot_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `post_categories` (`id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `post_tag_pivot`
--
ALTER TABLE `post_tag_pivot`
  ADD CONSTRAINT `post_tag_pivot_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_tag_pivot_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `post_tags` (`id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `schedule_exceptions`
--
ALTER TABLE `schedule_exceptions`
  ADD CONSTRAINT `schedule_exceptions_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `staff_services`
--
ALTER TABLE `staff_services`
  ADD CONSTRAINT `staff_services_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_services_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `working_hours`
--
ALTER TABLE `working_hours`
  ADD CONSTRAINT `working_hours_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
