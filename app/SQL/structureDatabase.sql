DROP database bdk;
CREATE database bdk;
USE bdk;


CREATE TABLE role (
    roleId INT PRIMARY KEY AUTO_INCREMENT,
    nameRole VARCHAR(30)
);

INSERT INTO role (nameRole) VALUES
    ('Organisateur'),
    ('Pilote'),
    ('Demandeur'),
    ('Visiteur');

CREATE TABLE season (
    seasonId INT PRIMARY KEY AUTO_INCREMENT,
    year INT
);

CREATE TABLE country (
    countryId INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50),
    flag TEXT
);

CREATE TABLE city (
    cityId INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50),
    zip INT,
    country INT,

    FOREIGN KEY (country) REFERENCES country(countryId)
);

CREATE TABLE address (
    addressId INT PRIMARY KEY AUTO_INCREMENT,
    city INT,
    street TEXT,
    number INT,

    FOREIGN KEY (city) REFERENCES city(cityId)
);

CREATE TABLE circuit (
    circuitId INT PRIMARY KEY AUTO_INCREMENT,
    nameCircuit VARCHAR(50),
    address INT,
    picture TEXT,

    FOREIGN KEY (address) REFERENCES address(addressId)
);

CREATE TABLE user (
    userId INT AUTO_INCREMENT PRIMARY KEY,
    firstName VARCHAR(50),
    lastName VARCHAR(50),
    birthday DATETIME,
    email VARCHAR(255),
    picture TEXT,
    phone VARCHAR(30),
    poids INT,
    taille INT,
    role INT,
    numero INT,
    description VARCHAR(255),
    city INT,
    nationality INT,
    password VARCHAR(255),
    dateRequestMember DATETIME,
    created_at DATETIME,
    emailVerified BOOLEAN
);

CREATE TABLE tokenUser (
    tokenUserId INT AUTO_INCREMENT PRIMARY KEY,
    typeToken ENUM('passwordForget', 'cookieToken','verificationEmail'),
    user INT,
    token VARCHAR(255),
    dateToken DATETIME,
    status BOOLEAN,

    FOREIGN KEY (user) REFERENCES user(userId)
);

CREATE TABLE ranking (
    rankingId INT PRIMARY KEY AUTO_INCREMENT,
    pilot INT,
    season INT,
    points FLOAT,

    FOREIGN KEY (pilot) REFERENCES user(userId),
    FOREIGN KEY (season) REFERENCES season(seasonId)
);

CREATE TABLE race (
    raceId INT PRIMARY KEY AUTO_INCREMENT,
    circuit INT,
    description TEXT,
    date DATETIME,
    season INT,
    video TEXT,
    price_cents INT,
    capacity_min INT,
    capacity_max INT,
    registration_open DATETIME,
    registration_close DATETIME,
    fastDriver INT,

    FOREIGN KEY (circuit) REFERENCES circuit(circuitId),
    FOREIGN KEY (season) REFERENCES season(seasonId),
    FOREIGN KEY (fastDriver) REFERENCES user(userId)
);

CREATE TABLE registration (
    registrationId INT PRIMARY KEY AUTO_INCREMENT,
    user INT,
    race INT,
    status ENUM('no-valide', 'waited', 'valide'),
    date DATETIME,

    FOREIGN KEY (user) REFERENCES user(userId),
    FOREIGN KEY (race) REFERENCES race(raceId)
);

CREATE TABLE resultat (
    resultatId INT PRIMARY KEY AUTO_INCREMENT,
    pilot INT,
    race INT,
    position INT,
    averageSpeed FLOAT,
    points FLOAT,
    gapWithFront FLOAT,

    FOREIGN KEY (pilot) REFERENCES user(userId),
    FOREIGN KEY (race) REFERENCES race(raceId)
);

CREATE TABLE lap (
    lapId INT PRIMARY KEY AUTO_INCREMENT,
    resultat INT,
    lapNumber INT,
    lapTime FLOAT,

    FOREIGN KEY (resultat) REFERENCES resultat(resultatId)
);

CREATE TABLE poll (
    pollId INT PRIMARY KEY AUTO_INCREMENT,
    titlePoll VARCHAR(30),
    description TEXT,
    pollType ENUM('date', 'circuit', 'text', 'picture'),
    startDate DATETIME,
    endDate DATETIME,
    video TEXT,
    pollDate DATETIME,
    isManyChoice BOOL
);

CREATE TABLE pollOptions (
    pollOptionsId INT PRIMARY KEY AUTO_INCREMENT,
    poll INT,
    proposedDate DATETIME,
    proposedCircuit INT,
    proposedText TEXT,
    proposedPicture TEXT
);

CREATE TABLE pollVote (
    pollVoteId INT PRIMARY KEY AUTO_INCREMENT,
    poll INT,
    optionChose INT,
    driver INT,

    FOREIGN KEY (poll) REFERENCES poll(pollId),
    FOREIGN KEY (optionChose) REFERENCES pollOptions(pollOptionsId),
    FOREIGN KEY (driver) REFERENCES user(userId)
);


INSERT INTO country (countryId, `name`, `flag`) VALUES
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







