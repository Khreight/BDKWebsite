-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mer. 20 août 2025 à 20:52
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `bdk`
--

-- --------------------------------------------------------

--
-- Structure de la table `address`
--

DROP TABLE IF EXISTS `address`;
CREATE TABLE IF NOT EXISTS `address` (
  `addressId` int NOT NULL AUTO_INCREMENT,
  `city` int DEFAULT NULL,
  `street` text,
  `number` int DEFAULT NULL,
  PRIMARY KEY (`addressId`),
  KEY `city` (`city`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `address`
--

INSERT INTO `address` (`addressId`, `city`, `street`, `number`) VALUES
(1, 2, 'Rue de la glacerie', 1),
(2, 3, 'Hoogbuul', 47),
(3, 4, 'Rue du Karting', 13),
(4, 5, 'A. Vaucampslaan', 26);

-- --------------------------------------------------------

--
-- Structure de la table `circuit`
--

DROP TABLE IF EXISTS `circuit`;
CREATE TABLE IF NOT EXISTS `circuit` (
  `circuitId` int NOT NULL AUTO_INCREMENT,
  `nameCircuit` varchar(50) DEFAULT NULL,
  `address` int DEFAULT NULL,
  `picture` text,
  PRIMARY KEY (`circuitId`),
  KEY `address` (`address`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `circuit`
--

INSERT INTO `circuit` (`circuitId`, `nameCircuit`, `address`, `picture`) VALUES
(1, 'JMKarting', 1, 'https://jmkarting.com/namur/wp-content/uploads/sites/2/2023/05/20230419TL_Kingsize-JMKARTING_122.jpg'),
(2, 'GoodWill Karting', 2, 'https://static.wixstatic.com/media/d12e32_c12fb7d778a04a81b960b8a2927244e6~mv2.jpg/v1/fill/w_960,h_639,al_c,q_85,enc_avif,quality_auto/Foto%20Kombocht%20Goodwill%20Karting.jpg'),
(3, 'Karting des Fagnes', 3, 'https://www.speedactiontv.be/Getimage.aspx?m=M&d=imgnews&n=24H-MARIEMBOURG-24-05.jpg'),
(4, 'Factory Kart', 4, 'https://www.factorykart.be/uploads/crops/Desktop.4b4052df.dscf0431_2.jpeg');

-- --------------------------------------------------------

--
-- Structure de la table `city`
--

DROP TABLE IF EXISTS `city`;
CREATE TABLE IF NOT EXISTS `city` (
  `cityId` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `zip` int DEFAULT NULL,
  `country` int DEFAULT NULL,
  PRIMARY KEY (`cityId`),
  KEY `country` (`country`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `city`
--

INSERT INTO `city` (`cityId`, `name`, `zip`, `country`) VALUES
(1, 'Namur', NULL, 17),
(2, 'Floreffe', NULL, 17),
(3, 'Olen', NULL, 17),
(4, 'Couvin', NULL, 17),
(5, 'Beersel', NULL, 17);

-- --------------------------------------------------------

--
-- Structure de la table `country`
--

DROP TABLE IF EXISTS `country`;
CREATE TABLE IF NOT EXISTS `country` (
  `countryId` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `flag` text,
  PRIMARY KEY (`countryId`)
) ENGINE=MyISAM AUTO_INCREMENT=196 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `country`
--

INSERT INTO `country` (`countryId`, `name`, `flag`) VALUES
(1, 'Afghanistan', 'https://flagcdn.com/w320/af.png'),
(2, 'Albania', 'https://flagcdn.com/w320/al.png'),
(3, 'Algeria', 'https://flagcdn.com/w320/dz.png'),
(4, 'Andorra', 'https://flagcdn.com/w320/ad.png'),
(5, 'Angola', 'https://flagcdn.com/w320/ao.png'),
(6, 'Antigua and Barbuda', 'https://flagcdn.com/w320/ag.png'),
(7, 'Argentina', 'https://flagcdn.com/w320/ar.png'),
(8, 'Armenia', 'https://flagcdn.com/w320/am.png'),
(9, 'Australia', 'https://flagcdn.com/w320/au.png'),
(10, 'Austria', 'https://flagcdn.com/w320/at.png'),
(11, 'Azerbaijan', 'https://flagcdn.com/w320/az.png'),
(12, 'Bahamas', 'https://flagcdn.com/w320/bs.png'),
(13, 'Bahrain', 'https://flagcdn.com/w320/bh.png'),
(14, 'Bangladesh', 'https://flagcdn.com/w320/bd.png'),
(15, 'Barbados', 'https://flagcdn.com/w320/bb.png'),
(16, 'Belarus', 'https://flagcdn.com/w320/by.png'),
(17, 'Belgium', 'https://flagcdn.com/w320/be.png'),
(18, 'Belize', 'https://flagcdn.com/w320/bz.png'),
(19, 'Benin', 'https://flagcdn.com/w320/bj.png'),
(20, 'Bhutan', 'https://flagcdn.com/w320/bt.png'),
(21, 'Bolivia', 'https://flagcdn.com/w320/bo.png'),
(22, 'Bosnia and Herzegovina', 'https://flagcdn.com/w320/ba.png'),
(23, 'Botswana', 'https://flagcdn.com/w320/bw.png'),
(24, 'Brazil', 'https://flagcdn.com/w320/br.png'),
(25, 'Brunei', 'https://flagcdn.com/w320/bn.png'),
(26, 'Bulgaria', 'https://flagcdn.com/w320/bg.png'),
(27, 'Burkina Faso', 'https://flagcdn.com/w320/bf.png'),
(28, 'Burundi', 'https://flagcdn.com/w320/bi.png'),
(29, 'Cabo Verde', 'https://flagcdn.com/w320/cv.png'),
(30, 'Cambodia', 'https://flagcdn.com/w320/kh.png'),
(31, 'Cameroon', 'https://flagcdn.com/w320/cm.png'),
(32, 'Canada', 'https://flagcdn.com/w320/ca.png'),
(33, 'Central African Republic', 'https://flagcdn.com/w320/cf.png'),
(34, 'Chad', 'https://flagcdn.com/w320/td.png'),
(35, 'Chile', 'https://flagcdn.com/w320/cl.png'),
(36, 'China', 'https://flagcdn.com/w320/cn.png'),
(37, 'Colombia', 'https://flagcdn.com/w320/co.png'),
(38, 'Comoros', 'https://flagcdn.com/w320/km.png'),
(39, 'Congo (Republic)', 'https://flagcdn.com/w320/cg.png'),
(40, 'Congo (Democratic Republic)', 'https://flagcdn.com/w320/cd.png'),
(41, 'Costa Rica', 'https://flagcdn.com/w320/cr.png'),
(42, 'Côte d’Ivoire', 'https://flagcdn.com/w320/ci.png'),
(43, 'Croatia', 'https://flagcdn.com/w320/hr.png'),
(44, 'Cuba', 'https://flagcdn.com/w320/cu.png'),
(45, 'Cyprus', 'https://flagcdn.com/w320/cy.png'),
(46, 'Czechia', 'https://flagcdn.com/w320/cz.png'),
(47, 'Denmark', 'https://flagcdn.com/w320/dk.png'),
(48, 'Djibouti', 'https://flagcdn.com/w320/dj.png'),
(49, 'Dominica', 'https://flagcdn.com/w320/dm.png'),
(50, 'Dominican Republic', 'https://flagcdn.com/w320/do.png'),
(51, 'Ecuador', 'https://flagcdn.com/w320/ec.png'),
(52, 'Egypt', 'https://flagcdn.com/w320/eg.png'),
(53, 'El Salvador', 'https://flagcdn.com/w320/sv.png'),
(54, 'Equatorial Guinea', 'https://flagcdn.com/w320/gq.png'),
(55, 'Eritrea', 'https://flagcdn.com/w320/er.png'),
(56, 'Estonia', 'https://flagcdn.com/w320/ee.png'),
(57, 'Eswatini', 'https://flagcdn.com/w320/sz.png'),
(58, 'Ethiopia', 'https://flagcdn.com/w320/et.png'),
(59, 'Fiji', 'https://flagcdn.com/w320/fj.png'),
(60, 'Finland', 'https://flagcdn.com/w320/fi.png'),
(61, 'France', 'https://flagcdn.com/w320/fr.png'),
(62, 'Gabon', 'https://flagcdn.com/w320/ga.png'),
(63, 'Gambia', 'https://flagcdn.com/w320/gm.png'),
(64, 'Georgia', 'https://flagcdn.com/w320/ge.png'),
(65, 'Germany', 'https://flagcdn.com/w320/de.png'),
(66, 'Ghana', 'https://flagcdn.com/w320/gh.png'),
(67, 'Greece', 'https://flagcdn.com/w320/gr.png'),
(68, 'Grenada', 'https://flagcdn.com/w320/gd.png'),
(69, 'Guatemala', 'https://flagcdn.com/w320/gt.png'),
(70, 'Guinea', 'https://flagcdn.com/w320/gn.png'),
(71, 'Guinea-Bissau', 'https://flagcdn.com/w320/gw.png'),
(72, 'Guyana', 'https://flagcdn.com/w320/gy.png'),
(73, 'Haiti', 'https://flagcdn.com/w320/ht.png'),
(74, 'Honduras', 'https://flagcdn.com/w320/hn.png'),
(75, 'Hungary', 'https://flagcdn.com/w320/hu.png'),
(76, 'Iceland', 'https://flagcdn.com/w320/is.png'),
(77, 'India', 'https://flagcdn.com/w320/in.png'),
(78, 'Indonesia', 'https://flagcdn.com/w320/id.png'),
(79, 'Iran', 'https://flagcdn.com/w320/ir.png'),
(80, 'Iraq', 'https://flagcdn.com/w320/iq.png'),
(81, 'Ireland', 'https://flagcdn.com/w320/ie.png'),
(82, 'Israel', 'https://flagcdn.com/w320/il.png'),
(83, 'Italy', 'https://flagcdn.com/w320/it.png'),
(84, 'Jamaica', 'https://flagcdn.com/w320/jm.png'),
(85, 'Japan', 'https://flagcdn.com/w320/jp.png'),
(86, 'Jordan', 'https://flagcdn.com/w320/jo.png'),
(87, 'Kazakhstan', 'https://flagcdn.com/w320/kz.png'),
(88, 'Kenya', 'https://flagcdn.com/w320/ke.png'),
(89, 'Kiribati', 'https://flagcdn.com/w320/ki.png'),
(90, 'Kuwait', 'https://flagcdn.com/w320/kw.png'),
(91, 'Kyrgyzstan', 'https://flagcdn.com/w320/kg.png'),
(92, 'Laos', 'https://flagcdn.com/w320/la.png'),
(93, 'Latvia', 'https://flagcdn.com/w320/lv.png'),
(94, 'Lebanon', 'https://flagcdn.com/w320/lb.png'),
(95, 'Lesotho', 'https://flagcdn.com/w320/ls.png'),
(96, 'Liberia', 'https://flagcdn.com/w320/lr.png'),
(97, 'Libya', 'https://flagcdn.com/w320/ly.png'),
(98, 'Liechtenstein', 'https://flagcdn.com/w320/li.png'),
(99, 'Lithuania', 'https://flagcdn.com/w320/lt.png'),
(100, 'Luxembourg', 'https://flagcdn.com/w320/lu.png'),
(101, 'Madagascar', 'https://flagcdn.com/w320/mg.png'),
(102, 'Malawi', 'https://flagcdn.com/w320/mw.png'),
(103, 'Malaysia', 'https://flagcdn.com/w320/my.png'),
(104, 'Maldives', 'https://flagcdn.com/w320/mv.png'),
(105, 'Mali', 'https://flagcdn.com/w320/ml.png'),
(106, 'Malta', 'https://flagcdn.com/w320/mt.png'),
(107, 'Marshall Islands', 'https://flagcdn.com/w320/mh.png'),
(108, 'Mauritania', 'https://flagcdn.com/w320/mr.png'),
(109, 'Mauritius', 'https://flagcdn.com/w320/mu.png'),
(110, 'Mexico', 'https://flagcdn.com/w320/mx.png'),
(111, 'Micronesia', 'https://flagcdn.com/w320/fm.png'),
(112, 'Moldova', 'https://flagcdn.com/w320/md.png'),
(113, 'Monaco', 'https://flagcdn.com/w320/mc.png'),
(114, 'Mongolia', 'https://flagcdn.com/w320/mn.png'),
(115, 'Montenegro', 'https://flagcdn.com/w320/me.png'),
(116, 'Morocco', 'https://flagcdn.com/w320/ma.png'),
(117, 'Mozambique', 'https://flagcdn.com/w320/mz.png'),
(118, 'Myanmar', 'https://flagcdn.com/w320/mm.png'),
(119, 'Namibia', 'https://flagcdn.com/w320/na.png'),
(120, 'Nauru', 'https://flagcdn.com/w320/nr.png'),
(121, 'Nepal', 'https://flagcdn.com/w320/np.png'),
(122, 'Netherlands', 'https://flagcdn.com/w320/nl.png'),
(123, 'New Zealand', 'https://flagcdn.com/w320/nz.png'),
(124, 'Nicaragua', 'https://flagcdn.com/w320/ni.png'),
(125, 'Niger', 'https://flagcdn.com/w320/ne.png'),
(126, 'Nigeria', 'https://flagcdn.com/w320/ng.png'),
(127, 'North Korea', 'https://flagcdn.com/w320/kp.png'),
(128, 'North Macedonia', 'https://flagcdn.com/w320/mk.png'),
(129, 'Norway', 'https://flagcdn.com/w320/no.png'),
(130, 'Oman', 'https://flagcdn.com/w320/om.png'),
(131, 'Pakistan', 'https://flagcdn.com/w320/pk.png'),
(132, 'Palau', 'https://flagcdn.com/w320/pw.png'),
(133, 'Palestine', 'https://flagcdn.com/w320/ps.png'),
(134, 'Panama', 'https://flagcdn.com/w320/pa.png'),
(135, 'Papua New Guinea', 'https://flagcdn.com/w320/pg.png'),
(136, 'Paraguay', 'https://flagcdn.com/w320/py.png'),
(137, 'Peru', 'https://flagcdn.com/w320/pe.png'),
(138, 'Philippines', 'https://flagcdn.com/w320/ph.png'),
(139, 'Poland', 'https://flagcdn.com/w320/pl.png'),
(140, 'Portugal', 'https://flagcdn.com/w320/pt.png'),
(141, 'Qatar', 'https://flagcdn.com/w320/qa.png'),
(142, 'Romania', 'https://flagcdn.com/w320/ro.png'),
(143, 'Russia', 'https://flagcdn.com/w320/ru.png'),
(144, 'Rwanda', 'https://flagcdn.com/w320/rw.png'),
(145, 'Saint Kitts and Nevis', 'https://flagcdn.com/w320/kn.png'),
(146, 'Saint Lucia', 'https://flagcdn.com/w320/lc.png'),
(147, 'Saint Vincent and the Grenadines', 'https://flagcdn.com/w320/vc.png'),
(148, 'Samoa', 'https://flagcdn.com/w320/ws.png'),
(149, 'San Marino', 'https://flagcdn.com/w320/sm.png'),
(150, 'São Tomé and Príncipe', 'https://flagcdn.com/w320/st.png'),
(151, 'Saudi Arabia', 'https://flagcdn.com/w320/sa.png'),
(152, 'Senegal', 'https://flagcdn.com/w320/sn.png'),
(153, 'Serbia', 'https://flagcdn.com/w320/rs.png'),
(154, 'Seychelles', 'https://flagcdn.com/w320/sc.png'),
(155, 'Sierra Leone', 'https://flagcdn.com/w320/sl.png'),
(156, 'Singapore', 'https://flagcdn.com/w320/sg.png'),
(157, 'Slovakia', 'https://flagcdn.com/w320/sk.png'),
(158, 'Slovenia', 'https://flagcdn.com/w320/si.png'),
(159, 'Solomon Islands', 'https://flagcdn.com/w320/sb.png'),
(160, 'Somalia', 'https://flagcdn.com/w320/so.png'),
(161, 'South Africa', 'https://flagcdn.com/w320/za.png'),
(162, 'South Korea', 'https://flagcdn.com/w320/kr.png'),
(163, 'South Sudan', 'https://flagcdn.com/w320/ss.png'),
(164, 'Spain', 'https://flagcdn.com/w320/es.png'),
(165, 'Sri Lanka', 'https://flagcdn.com/w320/lk.png'),
(166, 'Sudan', 'https://flagcdn.com/w320/sd.png'),
(167, 'Suriname', 'https://flagcdn.com/w320/sr.png'),
(168, 'Sweden', 'https://flagcdn.com/w320/se.png'),
(169, 'Switzerland', 'https://flagcdn.com/w320/ch.png'),
(170, 'Syria', 'https://flagcdn.com/w320/sy.png'),
(171, 'Tajikistan', 'https://flagcdn.com/w320/tj.png'),
(172, 'Tanzania', 'https://flagcdn.com/w320/tz.png'),
(173, 'Thailand', 'https://flagcdn.com/w320/th.png'),
(174, 'Timor-Leste', 'https://flagcdn.com/w320/tl.png'),
(175, 'Togo', 'https://flagcdn.com/w320/tg.png'),
(176, 'Tonga', 'https://flagcdn.com/w320/to.png'),
(177, 'Trinidad and Tobago', 'https://flagcdn.com/w320/tt.png'),
(178, 'Tunisia', 'https://flagcdn.com/w320/tn.png'),
(179, 'Turkey', 'https://flagcdn.com/w320/tr.png'),
(180, 'Turkmenistan', 'https://flagcdn.com/w320/tm.png'),
(181, 'Tuvalu', 'https://flagcdn.com/w320/tv.png'),
(182, 'Uganda', 'https://flagcdn.com/w320/ug.png'),
(183, 'Ukraine', 'https://flagcdn.com/w320/ua.png'),
(184, 'United Arab Emirates', 'https://flagcdn.com/w320/ae.png'),
(185, 'United Kingdom', 'https://flagcdn.com/w320/gb.png'),
(186, 'United States', 'https://flagcdn.com/w320/us.png'),
(187, 'Uruguay', 'https://flagcdn.com/w320/uy.png'),
(188, 'Uzbekistan', 'https://flagcdn.com/w320/uz.png'),
(189, 'Vanuatu', 'https://flagcdn.com/w320/vu.png'),
(190, 'Holy See', 'https://flagcdn.com/w320/va.png'),
(191, 'Venezuela', 'https://flagcdn.com/w320/ve.png'),
(192, 'Vietnam', 'https://flagcdn.com/w320/vn.png'),
(193, 'Yemen', 'https://flagcdn.com/w320/ye.png'),
(194, 'Zambia', 'https://flagcdn.com/w320/zm.png'),
(195, 'Zimbabwe', 'https://flagcdn.com/w320/zw.png');

-- --------------------------------------------------------

--
-- Structure de la table `lap`
--

DROP TABLE IF EXISTS `lap`;
CREATE TABLE IF NOT EXISTS `lap` (
  `lapId` int NOT NULL AUTO_INCREMENT,
  `resultat` int DEFAULT NULL,
  `lapNumber` int DEFAULT NULL,
  `lapTime` float DEFAULT NULL,
  PRIMARY KEY (`lapId`),
  KEY `resultat` (`resultat`)
) ENGINE=MyISAM AUTO_INCREMENT=520 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `lap`
--

INSERT INTO `lap` (`lapId`, `resultat`, `lapNumber`, `lapTime`) VALUES
(1, 1, 1, 85.386),
(2, 1, 2, 64.041),
(3, 1, 3, 63.346),
(4, 1, 4, 63.245),
(5, 1, 5, 63.089),
(6, 1, 6, 62.979),
(7, 1, 7, 62.722),
(8, 1, 8, 62.874),
(9, 1, 9, 63.56),
(10, 1, 10, 63.095),
(11, 1, 11, 62.95),
(12, 1, 12, 63.103),
(13, 1, 13, 63.005),
(14, 1, 14, 63.76),
(15, 1, 15, 63.198),
(16, 1, 16, 62.773),
(17, 1, 17, 72.784),
(18, 1, 18, 63.064),
(19, 1, 19, 63.038),
(20, 1, 20, 63.136),
(21, 1, 21, 63.131),
(22, 1, 22, 62.869),
(23, 1, 23, 63.095),
(24, 1, 24, 63.169),
(25, 1, 25, 63.158),
(26, 1, 26, 63.102),
(27, 1, 27, 63.325),
(28, 1, 28, 63.066),
(29, 1, 29, 63.091),
(30, 1, 30, 63.597),
(31, 1, 31, 63.049),
(32, 1, 32, 63.993),
(33, 1, 33, 67.715),
(34, 2, 1, 85.525),
(35, 2, 2, 64.347),
(36, 2, 3, 63.564),
(37, 2, 4, 63.833),
(38, 2, 5, 63.568),
(39, 2, 6, 63.817),
(40, 2, 7, 64.072),
(41, 2, 8, 64.384),
(42, 2, 9, 64.126),
(43, 2, 10, 63.982),
(44, 2, 11, 63.96),
(45, 2, 12, 64.378),
(46, 2, 13, 64.661),
(47, 2, 14, 63.67),
(48, 2, 15, 63.848),
(49, 2, 16, 63.816),
(50, 2, 17, 71.419),
(51, 2, 18, 64.168),
(52, 2, 19, 64.725),
(53, 2, 20, 64.137),
(54, 2, 21, 63.485),
(55, 2, 22, 63.094),
(56, 2, 23, 63.336),
(57, 2, 24, 63.94),
(58, 2, 25, 64.061),
(59, 2, 26, 64.105),
(60, 2, 27, 63.981),
(61, 2, 28, 64.762),
(62, 2, 29, 64.548),
(63, 2, 30, 63.486),
(64, 2, 31, 63.348),
(65, 2, 32, 64.005),
(66, 3, 1, 85.341),
(67, 3, 2, 64.919),
(68, 3, 3, 63.873),
(69, 3, 4, 63.992),
(70, 3, 5, 64.25),
(71, 3, 6, 64.095),
(72, 3, 7, 64.745),
(73, 3, 8, 63.76),
(74, 3, 9, 64.266),
(75, 3, 10, 63.923),
(76, 3, 11, 65.092),
(77, 3, 12, 64.312),
(78, 3, 13, 63.761),
(79, 3, 14, 63.923),
(80, 3, 15, 63.733),
(81, 3, 16, 64.258),
(82, 3, 17, 71.606),
(83, 3, 18, 64.105),
(84, 3, 19, 64.672),
(85, 3, 20, 63.649),
(86, 3, 21, 63.81),
(87, 3, 22, 64.413),
(88, 3, 23, 63.018),
(89, 3, 24, 64.308),
(90, 3, 25, 64.731),
(91, 3, 26, 64.233),
(92, 3, 27, 63.349),
(93, 3, 28, 63.387),
(94, 3, 29, 64.507),
(95, 3, 30, 64.594),
(96, 3, 31, 63.967),
(97, 3, 32, 64.084),
(98, 4, 1, 84.779),
(99, 4, 2, 64.913),
(100, 4, 3, 64.07),
(101, 4, 4, 63.733),
(102, 4, 5, 63.954),
(103, 4, 6, 64.103),
(104, 4, 7, 64.072),
(105, 4, 8, 64.309),
(106, 4, 9, 63.816),
(107, 4, 10, 64.443),
(108, 4, 11, 64.147),
(109, 4, 12, 63.42),
(110, 4, 13, 64.113),
(111, 4, 14, 65.216),
(112, 4, 15, 63.426),
(113, 4, 16, 64.607),
(114, 4, 17, 64.249),
(115, 4, 18, 73.421),
(116, 4, 19, 65.34),
(117, 4, 20, 65.143),
(118, 4, 21, 65.598),
(119, 4, 22, 64.837),
(120, 4, 23, 63.848),
(121, 4, 24, 64.16),
(122, 4, 25, 63.861),
(123, 4, 26, 64.385),
(124, 4, 27, 65.188),
(125, 4, 28, 63.897),
(126, 4, 29, 64.877),
(127, 4, 30, 65.746),
(128, 4, 31, 64.818),
(129, 4, 32, 64.248),
(130, 5, 1, 84.984),
(131, 5, 2, 65.94),
(132, 5, 3, 64.416),
(133, 5, 4, 63.916),
(134, 5, 5, 63.949),
(135, 5, 6, 63.697),
(136, 5, 7, 64.162),
(137, 5, 8, 65.358),
(138, 5, 9, 64.146),
(139, 5, 10, 64.219),
(140, 5, 11, 63.865),
(141, 5, 12, 63.937),
(142, 5, 13, 64.765),
(143, 5, 14, 64.077),
(144, 5, 15, 64.791),
(145, 5, 16, 65.001),
(146, 5, 17, 71.578),
(147, 5, 18, 64.985),
(148, 5, 19, 64.894),
(149, 5, 20, 66.147),
(150, 5, 21, 65.42),
(151, 5, 22, 63.427),
(152, 5, 23, 63.6),
(153, 5, 24, 64.037),
(154, 5, 25, 63.834),
(155, 5, 26, 63.679),
(156, 5, 27, 64.364),
(157, 5, 28, 63.997),
(158, 5, 29, 65.494),
(159, 5, 30, 66.926),
(160, 5, 31, 65.334),
(161, 5, 32, 64),
(162, 6, 1, 86.712),
(163, 6, 2, 68.003),
(164, 6, 3, 64.648),
(165, 6, 4, 64.673),
(166, 6, 5, 64.881),
(167, 6, 6, 65.205),
(168, 6, 7, 64.839),
(169, 6, 8, 67.151),
(170, 6, 9, 65.586),
(171, 6, 10, 65.987),
(172, 6, 11, 65.921),
(173, 6, 12, 63.942),
(174, 6, 13, 65.327),
(175, 6, 14, 64.985),
(176, 6, 15, 64.92),
(177, 6, 16, 66.663),
(178, 6, 17, 72.01),
(179, 6, 18, 66.201),
(180, 6, 19, 65.243),
(181, 6, 20, 64.679),
(182, 6, 21, 64.608),
(183, 6, 22, 66.104),
(184, 6, 23, 65.301),
(185, 6, 24, 64.819),
(186, 6, 25, 64.951),
(187, 6, 26, 69.231),
(188, 6, 27, 64.901),
(189, 6, 28, 65.287),
(190, 6, 29, 65.196),
(191, 6, 30, 64.876),
(192, 6, 31, 65.477),
(193, 7, 1, 86.471),
(194, 7, 2, 67.297),
(195, 7, 3, 66.89),
(196, 7, 4, 66.083),
(197, 7, 5, 69.468),
(198, 7, 6, 67.289),
(199, 7, 7, 65.583),
(200, 7, 8, 65.904),
(201, 7, 9, 65.975),
(202, 7, 10, 65.085),
(203, 7, 11, 65.82),
(204, 7, 12, 65.437),
(205, 7, 13, 65.058),
(206, 7, 14, 65.339),
(207, 7, 15, 64.767),
(208, 7, 16, 72.193),
(209, 7, 17, 65.246),
(210, 7, 18, 65.113),
(211, 7, 19, 64.919),
(212, 7, 20, 65.128),
(213, 7, 21, 65.318),
(214, 7, 22, 65.509),
(215, 7, 23, 65.545),
(216, 7, 24, 64.722),
(217, 7, 25, 66.134),
(218, 7, 26, 66.714),
(219, 7, 27, 64.97),
(220, 7, 28, 65.957),
(221, 7, 29, 65.311),
(222, 7, 30, 64.828),
(223, 7, 31, 65.482),
(224, 8, 1, 86.079),
(225, 8, 2, 68.126),
(226, 8, 3, 67.475),
(227, 8, 4, 66.249),
(228, 8, 5, 67.734),
(229, 8, 6, 65.714),
(230, 8, 7, 66.243),
(231, 8, 8, 65.451),
(232, 8, 9, 65.559),
(233, 8, 10, 65.068),
(234, 8, 11, 66.478),
(235, 8, 12, 67.107),
(236, 8, 13, 65.159),
(237, 8, 14, 66.599),
(238, 8, 15, 64.812),
(239, 8, 16, 72.768),
(240, 8, 17, 65.331),
(241, 8, 18, 65.382),
(242, 8, 19, 66.088),
(243, 8, 20, 65.869),
(244, 8, 21, 66.601),
(245, 8, 22, 65.273),
(246, 8, 23, 66.225),
(247, 8, 24, 65.485),
(248, 8, 25, 65.954),
(249, 8, 26, 65.684),
(250, 8, 27, 65.772),
(251, 8, 28, 65.182),
(252, 8, 8, 64.583),
(253, 8, 30, 64.576),
(254, 8, 31, 64.931),
(255, 9, 1, 85.341),
(256, 9, 2, 64.571),
(257, 9, 3, 64.244),
(258, 9, 4, 64.095),
(259, 9, 5, 64.373),
(260, 9, 6, 63.804),
(261, 9, 7, 63.969),
(262, 9, 8, 65.363),
(263, 9, 9, 66.265),
(264, 9, 10, 65.349),
(265, 9, 11, 65.582),
(266, 9, 12, 65.305),
(267, 9, 13, 66.865),
(268, 9, 14, 66.389),
(269, 9, 15, 67.79),
(270, 9, 16, 73.837),
(271, 9, 17, 69.268),
(272, 9, 18, 65.36),
(273, 9, 19, 65.071),
(274, 9, 20, 65.94),
(275, 9, 21, 68.206),
(276, 9, 22, 64.902),
(277, 9, 23, 65.662),
(278, 9, 24, 66.74),
(279, 9, 25, 70.701),
(280, 9, 26, 66.699),
(281, 9, 27, 69.2),
(282, 9, 28, 66.263),
(283, 9, 29, 67.458),
(284, 9, 30, 67.861),
(285, 9, 31, 67.775),
(286, 10, 1, 85.501),
(287, 10, 2, 64.951),
(288, 10, 3, 64.037),
(289, 10, 4, 63.705),
(290, 10, 5, 64.227),
(291, 10, 6, 63.913),
(292, 10, 7, 64.231),
(293, 10, 8, 64.417),
(294, 10, 9, 64.091),
(295, 10, 10, 63.972),
(296, 10, 11, 65.427),
(297, 10, 12, 65.41),
(298, 10, 13, 65.215),
(299, 10, 14, 64.994),
(300, 10, 15, 64.32),
(301, 10, 16, 65.974),
(302, 10, 17, 123.175),
(303, 10, 18, 65.173),
(304, 10, 19, 64.306),
(305, 10, 20, 65.136),
(306, 10, 21, 64.421),
(307, 10, 22, 64.573),
(308, 10, 23, 64.998),
(309, 10, 24, 63.973),
(310, 10, 25, 65.138),
(311, 10, 26, 64.359),
(312, 10, 27, 65.261),
(313, 10, 28, 65.109),
(314, 10, 29, 65.667),
(315, 10, 30, 64.045),
(316, 10, 31, 65.043),
(317, 11, 1, 85.859),
(318, 11, 2, 69.446),
(319, 11, 3, 67.483),
(320, 11, 4, 66.404),
(321, 11, 5, 67.922),
(322, 11, 6, 66.641),
(323, 11, 7, 65.477),
(324, 11, 8, 65.231),
(325, 11, 9, 65.07),
(326, 11, 10, 65.317),
(327, 11, 11, 66.377),
(328, 11, 12, 67.542),
(329, 11, 13, 65.301),
(330, 11, 14, 65.997),
(331, 11, 15, 65.779),
(332, 11, 16, 74.447),
(333, 11, 17, 66.37),
(334, 11, 18, 67.207),
(335, 11, 19, 68.298),
(336, 11, 20, 67.036),
(337, 11, 21, 67.004),
(338, 11, 22, 66.73),
(339, 11, 23, 68.895),
(340, 11, 24, 74.433),
(341, 11, 25, 69.873),
(342, 11, 26, 68.364),
(343, 11, 27, 68.85),
(344, 11, 28, 67.188),
(345, 11, 29, 70.57),
(346, 11, 30, 71.561),
(347, 12, 1, 86.772),
(348, 12, 2, 68.452),
(349, 12, 3, 67.522),
(350, 12, 4, 67.422),
(351, 12, 5, 66.72),
(352, 12, 6, 67.504),
(353, 12, 7, 66.803),
(354, 12, 8, 67.782),
(355, 12, 9, 67.577),
(356, 12, 10, 67.74),
(357, 12, 11, 67.391),
(358, 12, 12, 67.323),
(359, 12, 13, 67.666),
(360, 12, 14, 67.329),
(361, 12, 15, 66.53),
(362, 12, 16, 92.226),
(363, 12, 17, 67.878),
(364, 12, 18, 68.967),
(365, 12, 19, 67.005),
(366, 12, 20, 66.263),
(367, 12, 21, 66.396),
(368, 12, 22, 68.499),
(369, 12, 23, 66.365),
(370, 12, 24, 66.134),
(371, 12, 25, 71.127),
(372, 12, 26, 67.527),
(373, 12, 27, 66.639),
(374, 12, 28, 66.809),
(375, 12, 29, 66.751),
(376, 12, 30, 67.567),
(377, 13, 1, 88.46),
(378, 13, 2, 69.914),
(379, 13, 3, 69.142),
(380, 13, 4, 68.229),
(381, 13, 5, 68.334),
(382, 13, 6, 67.755),
(383, 13, 7, 67.998),
(384, 13, 8, 67.793),
(385, 13, 9, 68.527),
(386, 13, 10, 67.781),
(387, 13, 11, 68.153),
(388, 13, 12, 66.745),
(389, 13, 13, 68.77),
(390, 13, 14, 68.898),
(391, 13, 15, 69.701),
(392, 13, 16, 68.72),
(393, 13, 17, 127.457),
(394, 13, 18, 69.777),
(395, 13, 19, 68.971),
(396, 13, 20, 67.113),
(397, 13, 21, 66.908),
(398, 13, 22, 67.618),
(399, 13, 23, 67.815),
(400, 13, 24, 67.037),
(401, 13, 25, 67.183),
(402, 13, 26, 67.189),
(403, 13, 27, 68.329),
(404, 13, 28, 68.093),
(405, 13, 29, 67.313),
(406, 14, 1, 88.083),
(407, 14, 2, 68.122),
(408, 14, 3, 68.705),
(409, 14, 4, 68.131),
(410, 14, 5, 68.43),
(411, 14, 6, 68.503),
(412, 14, 7, 68.352),
(413, 14, 8, 67.343),
(414, 14, 9, 67.163),
(415, 14, 10, 68.201),
(416, 14, 11, 67.257),
(417, 14, 12, 68.311),
(418, 14, 13, 67.37),
(419, 14, 14, 67.53),
(420, 14, 15, 67.552),
(421, 14, 16, 134.063),
(422, 14, 17, 71.295),
(423, 14, 18, 69.522),
(424, 14, 19, 67.036),
(425, 14, 20, 67.393),
(426, 14, 21, 70.598),
(427, 14, 22, 67.815),
(428, 14, 23, 68.195),
(429, 14, 24, 67.674),
(430, 14, 25, 69.064),
(431, 14, 26, 67.919),
(432, 14, 27, 67.023),
(433, 14, 28, 67.102),
(434, 14, 29, 66.721),
(435, 15, 1, 88.594),
(436, 15, 2, 70.091),
(437, 15, 3, 69.23),
(438, 15, 4, 70.819),
(439, 15, 5, 68.317),
(440, 15, 6, 69.437),
(441, 15, 7, 69.351),
(442, 15, 8, 70.162),
(443, 15, 9, 68.788),
(444, 15, 10, 68.852),
(445, 15, 11, 71.904),
(446, 15, 12, 69.675),
(447, 15, 13, 69.262),
(448, 15, 14, 69.323),
(449, 15, 15, 104.726),
(450, 15, 16, 69.626),
(451, 15, 17, 69.058),
(452, 15, 18, 68.183),
(453, 15, 19, 71.462),
(454, 15, 20, 69.445),
(455, 15, 21, 73.392),
(456, 15, 22, 68.664),
(457, 15, 23, 68.166),
(458, 15, 24, 72.415),
(459, 15, 25, 69.636),
(460, 15, 26, 70.462),
(461, 15, 27, 70.962),
(462, 15, 28, 69.751),
(463, 15, 29, 69.254),
(464, 16, 1, 88.01),
(465, 16, 2, 71.846),
(466, 16, 3, 71.193),
(467, 16, 4, 71.713),
(468, 16, 5, 70.057),
(469, 16, 6, 71.017),
(470, 16, 7, 70.747),
(471, 16, 8, 70.868),
(472, 16, 9, 72.591),
(473, 16, 10, 71.222),
(474, 16, 11, 72.38),
(475, 16, 12, 70.121),
(476, 16, 13, 71.012),
(477, 16, 14, 69.881),
(478, 16, 15, 82.407),
(479, 16, 16, 71.568),
(480, 16, 17, 73.217),
(481, 16, 18, 72.298),
(482, 16, 19, 69.721),
(483, 16, 20, 69.985),
(484, 16, 21, 69.785),
(485, 16, 22, 71.182),
(486, 16, 23, 70.903),
(487, 16, 24, 72.052),
(488, 16, 25, 72.106),
(489, 16, 26, 70.75),
(490, 16, 27, 70.67),
(491, 16, 28, 73.292),
(492, 16, 29, 73.155),
(493, 17, 1, 87.572),
(494, 17, 2, 72.641),
(495, 17, 3, 71.079),
(496, 17, 4, 72.469),
(497, 17, 5, 73.833),
(498, 17, 6, 71.43),
(499, 17, 7, 79.298),
(500, 17, 8, 74.245),
(501, 17, 9, 74.52),
(502, 17, 10, 74.298),
(503, 17, 11, 74.597),
(504, 17, 12, 75.105),
(505, 17, 13, 74.91),
(506, 17, 14, 74.825),
(507, 17, 15, 135.438),
(508, 17, 16, 71.514),
(509, 17, 17, 70.706),
(510, 17, 18, 72.391),
(511, 17, 19, 72.89),
(512, 17, 20, 71.639),
(513, 17, 21, 71.667),
(514, 17, 22, 72.706),
(515, 17, 23, 70.972),
(516, 17, 24, 70.328),
(517, 17, 25, 70.061),
(518, 17, 26, 72.558),
(519, 17, 27, 70.964);

-- --------------------------------------------------------

--
-- Structure de la table `poll`
--

DROP TABLE IF EXISTS `poll`;
CREATE TABLE IF NOT EXISTS `poll` (
  `pollId` int NOT NULL AUTO_INCREMENT,
  `titlePoll` varchar(30) DEFAULT NULL,
  `description` text,
  `pollType` enum('date','circuit','text','picture') DEFAULT NULL,
  `startDate` datetime DEFAULT NULL,
  `endDate` datetime DEFAULT NULL,
  `video` text,
  `pollDate` datetime DEFAULT NULL,
  `isManyChoice` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`pollId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `polloptions`
--

DROP TABLE IF EXISTS `polloptions`;
CREATE TABLE IF NOT EXISTS `polloptions` (
  `pollOptionsId` int NOT NULL AUTO_INCREMENT,
  `poll` int DEFAULT NULL,
  `proposedDate` datetime DEFAULT NULL,
  `proposedCircuit` int DEFAULT NULL,
  `proposedText` text,
  `proposedPicture` text,
  PRIMARY KEY (`pollOptionsId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `pollvote`
--

DROP TABLE IF EXISTS `pollvote`;
CREATE TABLE IF NOT EXISTS `pollvote` (
  `pollVoteId` int NOT NULL AUTO_INCREMENT,
  `poll` int DEFAULT NULL,
  `optionChose` int DEFAULT NULL,
  `driver` int DEFAULT NULL,
  PRIMARY KEY (`pollVoteId`),
  KEY `poll` (`poll`),
  KEY `optionChose` (`optionChose`),
  KEY `driver` (`driver`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `race`
--

DROP TABLE IF EXISTS `race`;
CREATE TABLE IF NOT EXISTS `race` (
  `raceId` int NOT NULL AUTO_INCREMENT,
  `circuit` int DEFAULT NULL,
  `description` text,
  `date` datetime DEFAULT NULL,
  `season` int DEFAULT NULL,
  `video` text,
  `price_cents` int DEFAULT NULL,
  `capacity_min` int DEFAULT NULL,
  `capacity_max` int DEFAULT NULL,
  `registration_open` datetime DEFAULT NULL,
  `registration_close` datetime DEFAULT NULL,
  `fastDriver` int DEFAULT NULL,
  PRIMARY KEY (`raceId`),
  KEY `circuit` (`circuit`),
  KEY `season` (`season`),
  KEY `fastDriver` (`fastDriver`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `race`
--

INSERT INTO `race` (`raceId`, `circuit`, `description`, `date`, `season`, `video`, `price_cents`, `capacity_min`, `capacity_max`, `registration_open`, `registration_close`, `fastDriver`) VALUES
(1, 1, 'Première édition de la saison de Karting 2025 !', '2025-01-26 14:00:00', 1, NULL, 6000, 15, 20, '2025-01-01 22:12:00', '2025-01-06 22:12:00', 4);

-- --------------------------------------------------------

--
-- Structure de la table `ranking`
--

DROP TABLE IF EXISTS `ranking`;
CREATE TABLE IF NOT EXISTS `ranking` (
  `rankingId` int NOT NULL AUTO_INCREMENT,
  `pilot` int DEFAULT NULL,
  `season` int DEFAULT NULL,
  `points` float DEFAULT NULL,
  PRIMARY KEY (`rankingId`),
  KEY `pilot` (`pilot`),
  KEY `season` (`season`)
) ENGINE=MyISAM AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `ranking`
--

INSERT INTO `ranking` (`rankingId`, `pilot`, `season`, `points`) VALUES
(1, 1, 1, 0),
(2, 19, 1, 0),
(3, 4, 1, 0),
(4, 3, 1, 0),
(5, 8, 1, 0),
(6, 20, 1, 0),
(7, 2, 1, 0),
(8, 13, 1, 0),
(9, 17, 1, 0),
(10, 16, 1, 0),
(11, 15, 1, 0),
(12, 5, 1, 0),
(13, 10, 1, 0),
(14, 7, 1, 0),
(15, 6, 1, 0),
(16, 14, 1, 0),
(17, 12, 1, 0),
(18, 9, 1, 0),
(19, 11, 1, 0),
(20, 18, 1, 0);

-- --------------------------------------------------------

--
-- Structure de la table `registration`
--

DROP TABLE IF EXISTS `registration`;
CREATE TABLE IF NOT EXISTS `registration` (
  `registrationId` int NOT NULL AUTO_INCREMENT,
  `user` int DEFAULT NULL,
  `race` int DEFAULT NULL,
  `status` enum('no-valide','waited','valide') DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  PRIMARY KEY (`registrationId`),
  KEY `user` (`user`),
  KEY `race` (`race`)
) ENGINE=MyISAM AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `registration`
--

INSERT INTO `registration` (`registrationId`, `user`, `race`, `status`, `date`) VALUES
(2, 4, 1, 'valide', '2025-08-20 22:13:10'),
(3, 3, 1, 'valide', '2025-08-20 22:13:12'),
(4, 8, 1, 'valide', '2025-08-20 22:13:13'),
(5, 20, 1, 'valide', '2025-08-20 22:13:14'),
(6, 2, 1, 'valide', '2025-08-20 22:13:15'),
(7, 13, 1, 'valide', '2025-08-20 22:13:16'),
(8, 17, 1, 'valide', '2025-08-20 22:13:17'),
(10, 16, 1, 'valide', '2025-08-20 22:13:20'),
(11, 15, 1, 'valide', '2025-08-20 22:13:21'),
(12, 5, 1, 'valide', '2025-08-20 22:13:22'),
(13, 10, 1, 'valide', '2025-08-20 22:13:24'),
(14, 7, 1, 'valide', '2025-08-20 22:13:25'),
(15, 6, 1, 'valide', '2025-08-20 22:13:27'),
(16, 14, 1, 'valide', '2025-08-20 22:13:28'),
(17, 12, 1, 'valide', '2025-08-20 22:13:29'),
(19, 11, 1, 'valide', '2025-08-20 22:13:32'),
(20, 18, 1, 'valide', '2025-08-20 22:13:34');

-- --------------------------------------------------------

--
-- Structure de la table `resultat`
--

DROP TABLE IF EXISTS `resultat`;
CREATE TABLE IF NOT EXISTS `resultat` (
  `resultatId` int NOT NULL AUTO_INCREMENT,
  `pilot` int DEFAULT NULL,
  `race` int DEFAULT NULL,
  `position` int DEFAULT NULL,
  `averageSpeed` float DEFAULT NULL,
  `points` float DEFAULT NULL,
  `gapWithFront` float DEFAULT NULL,
  PRIMARY KEY (`resultatId`),
  KEY `pilot` (`pilot`),
  KEY `race` (`race`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `resultat`
--

INSERT INTO `resultat` (`resultatId`, `pilot`, `race`, `position`, `averageSpeed`, `points`, `gapWithFront`) VALUES
(1, 4, 1, 1, 56, 22.5, 0),
(2, 15, 1, 2, 55.47, 21, 1),
(3, 2, 1, 3, 55.25, 20, 7.953),
(4, 3, 1, 4, 55.04, 19, 16.085),
(5, 11, 1, 5, 55, 18, 17.68),
(6, 7, 1, 6, 54.08, 17, 2),
(7, 12, 1, 7, 53.86, 16, 8.773),
(8, 6, 1, 8, 53.76, 15, 12.289),
(9, 5, 1, 9, 53.57, 14, 19.972),
(10, 18, 1, 10, 53.44, 13, 24.957),
(11, 20, 1, 11, 52.47, 12, 3),
(12, 14, 1, 12, 52.07, 11, 15.884),
(13, 13, 1, 13, 50.52, 10, 4),
(14, 10, 1, 14, 50.44, 9, 3.213),
(15, 8, 1, 15, 49.97, 8, 22.467),
(16, 16, 1, 16, 49.48, 7, 43.108),
(17, 17, 1, 17, 47.25, 6, 6);

-- --------------------------------------------------------

--
-- Structure de la table `role`
--

DROP TABLE IF EXISTS `role`;
CREATE TABLE IF NOT EXISTS `role` (
  `roleId` int NOT NULL AUTO_INCREMENT,
  `nameRole` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`roleId`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `role`
--

INSERT INTO `role` (`roleId`, `nameRole`) VALUES
(1, 'Organisateur'),
(2, 'Pilote'),
(3, 'Demandeur'),
(4, 'Visiteur');

-- --------------------------------------------------------

--
-- Structure de la table `season`
--

DROP TABLE IF EXISTS `season`;
CREATE TABLE IF NOT EXISTS `season` (
  `seasonId` int NOT NULL AUTO_INCREMENT,
  `year` int DEFAULT NULL,
  PRIMARY KEY (`seasonId`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `season`
--

INSERT INTO `season` (`seasonId`, `year`) VALUES
(1, 2025);

-- --------------------------------------------------------

--
-- Structure de la table `tokenuser`
--

DROP TABLE IF EXISTS `tokenuser`;
CREATE TABLE IF NOT EXISTS `tokenuser` (
  `tokenUserId` int NOT NULL AUTO_INCREMENT,
  `typeToken` enum('passwordForget','cookieToken','verificationEmail') DEFAULT NULL,
  `user` int DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `dateToken` datetime DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`tokenUserId`),
  KEY `user` (`user`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `tokenuser`
--

INSERT INTO `tokenuser` (`tokenUserId`, `typeToken`, `user`, `token`, `dateToken`, `status`) VALUES
(1, 'verificationEmail', 1, 'ca5059fb9e61439fe980a8d4737bef4837e0b9c91ac714db695115290c6983d1', '2025-08-20 20:37:06', 0),
(2, 'cookieToken', 1, 'b9ec4c1bfb9301a6379943803e26346d0b473b7895e8994c2d568f14339485a4', '2025-08-20 20:38:31', 1);

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `userId` int NOT NULL AUTO_INCREMENT,
  `firstName` varchar(50) DEFAULT NULL,
  `lastName` varchar(50) DEFAULT NULL,
  `birthday` datetime DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `picture` text,
  `phone` varchar(30) DEFAULT NULL,
  `poids` int DEFAULT NULL,
  `taille` int DEFAULT NULL,
  `role` int DEFAULT NULL,
  `numero` int DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `city` int DEFAULT NULL,
  `nationality` int DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `dateRequestMember` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `emailVerified` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`userId`)
) ENGINE=MyISAM AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `user`
--

INSERT INTO `user` (`userId`, `firstName`, `lastName`, `birthday`, `email`, `picture`, `phone`, `poids`, `taille`, `role`, `numero`, `description`, `city`, `nationality`, `password`, `dateRequestMember`, `created_at`, `emailVerified`) VALUES
(1, 'Louis', 'Francken', '2005-09-11 00:00:00', 'louisthedeaf@gmail.com', 'default.png', '+32467053812', NULL, NULL, 1, NULL, NULL, 1, 17, '$2y$10$0442qX0W5UIs1AxNTydcJOfBKhdn3lkC/po9yk44xQuDsZZ1HGhFS', NULL, '2025-08-20 20:37:06', 1),
(2, 'Calogero', 'Campanella', '2000-01-01 00:00:00', 'adam.campanella68@gmail.com', 'default.png', '+32467117629', 80, 180, 2, 55, 'J\'adore le karting et j\'aime la compétition organisé par EBISU', 1, 17, 'password', NULL, '2025-08-20 22:06:32', 1),
(3, 'Simon', 'Beaucarne', '2000-01-01 00:00:00', 'beaucarne.simon28@gmail.com', 'default.png', '+32462750755', 80, 180, 2, 96, 'J\'adore le karting et j\'aime la compétition organisé par EBISU', 1, 17, 'password', NULL, '2025-08-20 22:06:33', 1),
(4, 'Remy', 'Adnet', '2000-01-01 00:00:00', 'remy.adnet74@gmail.com', 'default.png', '+32469035103', 80, 180, 2, 22, 'J\'adore le karting et j\'aime la compétition organisé par EBISU', 1, 17, 'password', NULL, '2025-08-20 22:06:33', 1),
(5, 'Kevin', 'Kopp', '2000-01-01 00:00:00', 'kevin.kopp21@gmail.com', 'default.png', '+32467834894', 80, 180, 2, 63, 'J\'adore le karting et j\'aime la compétition organisé par EBISU', 1, 17, 'password', NULL, '2025-08-20 22:06:33', 1),
(6, 'Bastien', 'Lobert', '2000-01-01 00:00:00', 'bastien.lobert17@gmail.com', 'default.png', '+32468131036', 80, 180, 2, 38, 'J\'adore le karting et j\'aime la compétition organisé par EBISU', 1, 17, 'password', NULL, '2025-08-20 22:06:33', 1),
(7, 'Damien', 'Lhoest', '2000-01-01 00:00:00', 'pierre.lhoest33@gmail.com', 'default.png', '+32464522731', 80, 180, 2, 12, 'J\'adore le karting et j\'aime la compétition organisé par EBISU', 1, 17, 'password', NULL, '2025-08-20 22:06:33', 1),
(8, 'Denis', 'Belot', '2000-01-01 00:00:00', 'denis.belot48@gmail.com', 'default.png', '+32466798412', 80, 180, 2, 78, 'J\'adore le karting et j\'aime la compétition organisé par EBISU', 1, 17, 'password', NULL, '2025-08-20 22:06:33', 1),
(9, 'Gilles', 'Poncelet', '2000-01-01 00:00:00', 'denis.poncelet91@gmail.com', 'default.png', '+32465314877', 80, 180, 2, 23, 'J\'adore le karting et j\'aime la compétition organisé par EBISU', 1, 17, 'password', NULL, '2025-08-20 22:06:33', 1),
(10, 'Kylian', 'Lenoir', '2000-01-01 00:00:00', 'kylian.lenoir64@gmail.com', 'default.png', '+32461839456', 80, 180, 2, 88, 'J\'adore le karting et j\'aime la compétition organisé par EBISU', 1, 17, 'password', NULL, '2025-08-20 22:06:33', 1),
(11, 'Jérôme', 'Trouillet', '2000-01-01 00:00:00', 'jerome.trouillet43@gmail.com', 'default.png', '+32463487192', 80, 180, 2, 34, 'J\'adore le karting et j\'aime la compétition organisé par EBISU', 1, 17, 'password', NULL, '2025-08-20 22:06:33', 1),
(12, 'Segre', 'Pellergrino', '2000-01-01 00:00:00', 'segriio.pellergrino56@gmail.com', 'default.png', '+32467539281', 80, 180, 2, 11, 'J\'adore le karting et j\'aime la compétition organisé par EBISU', 1, 17, 'password', NULL, '2025-08-20 22:06:33', 1),
(13, 'Yannick', 'Claesen', '2000-01-01 00:00:00', 'yannick.claesen22@gmail.com', 'default.png', '+32465478920', 80, 180, 2, 70, 'J\'adore le karting et j\'aime la compétition organisé par EBISU', 1, 17, 'password', NULL, '2025-08-20 22:06:33', 1),
(14, 'Sebastien', 'Lulian', '2000-01-01 00:00:00', 'sebastien.lulian84@gmail.com', 'default.png', '+32468894561', 80, 180, 2, 6, 'J\'adore le karting et j\'aime la compétition organisé par EBISU', 1, 17, 'password', NULL, '2025-08-20 22:06:33', 1),
(15, 'Nikos', 'Kaszubowski', '2000-01-01 00:00:00', 'nikos.kaszubowski39@gmail.com', 'default.png', '+32461237895', 80, 180, 2, 45, 'J\'adore le karting et j\'aime la compétition organisé par EBISU', 1, 17, 'password', NULL, '2025-08-20 22:06:33', 1),
(16, 'Jonathan', 'Huet', '2000-01-01 00:00:00', 'jonathan.huet31@gmail.com', 'default.png', '+32463379921', 80, 180, 2, 32, 'J\'adore le karting et j\'aime la compétition organisé par EBISU', 1, 17, 'password', NULL, '2025-08-20 22:06:33', 1),
(17, 'Grégory', 'Collignon', '2000-01-01 00:00:00', 'gregory.collignon76@gmail.com', 'default.png', '+32466841092', 80, 180, 2, 53, 'J\'adore le karting et j\'aime la compétition organisé par EBISU', 1, 17, 'password', NULL, '2025-08-20 22:06:33', 1),
(18, 'Olivia', 'Voelker', '2000-01-01 00:00:00', 'jacob.voelker29@gmail.com', 'default.png', '+32462789315', 80, 180, 2, 99, 'J\'adore le karting et j\'aime la compétition organisé par EBISU', 1, 17, 'password', NULL, '2025-08-20 22:06:33', 1),
(19, 'Kevin', 'Adnet', '2000-01-01 00:00:00', 'kevin.adnet12@gmail.com', 'default.png', '+32463597184', 80, 180, 2, 8, 'J\'adore le karting et j\'aime la compétition organisé par EBISU', 1, 17, 'password', NULL, '2025-08-20 22:06:33', 1),
(20, 'Salvatore', 'Bruno', '2000-01-01 00:00:00', 'michel.bruno19@gmail.com', 'default.png', '+32467418239', 80, 180, 2, 61, 'J\'adore le karting et j\'aime la compétition organisé par EBISU', 1, 17, 'password', NULL, '2025-08-20 22:06:33', 1);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
