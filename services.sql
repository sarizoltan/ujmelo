-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Gép: mysql.omega:3306
-- Létrehozás ideje: 2026. Jún 08. 12:37
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
-- Adatbázis: `barbercuccnak`
--

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
(1, 'Expressz manikűr', 'Gyors, ápolt és elegáns manikűr mindennapokra.', 30, 6500.00, 'Manikűr', 1, 1),
(2, 'Géllakk klasszikus', 'Tartós géllakk fényes, letisztult végeredménnyel.', 45, 8900.00, 'Géllakk', 2, 1),
(3, 'Francia géllakk', 'Időtálló francia stílus modern kivitelben.', 55, 9900.00, 'Géllakk', 3, 1),
(4, 'Épített műköröm', 'Tartós épített technika egyedi formára igazítva.', 90, 14900.00, 'Műköröm', 4, 1),
(5, 'Nail art díszítés', 'Kézzel festett vagy csillámos díszítések egyedi stílusban.', 30, 4500.00, 'Nail Art', 5, 1),
(6, 'Luxus spa kézápolás', 'Bőrápoló rituálé manikűrrel és hidratáló pakolással.', 60, 11900.00, 'Prémium', 6, 1);

--
-- Indexek a kiírt táblákhoz
--

--
-- A tábla indexei `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- A kiírt táblák AUTO_INCREMENT értéke
--

--
-- AUTO_INCREMENT a táblához `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
