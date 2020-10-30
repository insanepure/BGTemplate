-- phpMyAdmin SQL Dump
-- version 4.6.5.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Erstellungszeit: 18. Feb 2019 um 21:14
-- Server-Version: 10.1.21-MariaDB
-- PHP-Version: 5.6.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `bgdb`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `attacks`
--

CREATE TABLE `attacks` (
  `id` int(11) NOT NULL COMMENT 'Die ID an der man die Attacke finden kann.',
  `name` varchar(90) NOT NULL COMMENT 'Der Name der Attacke.',
  `type` tinyint(1) NOT NULL COMMENT 'Der Typ der Attacke. 1 = Damage, 2 = Heal, 3 = VW, 4 = Defend',
  `ki` int(11) NOT NULL,
  `atk` int(11) NOT NULL COMMENT 'Der Wert der Attacke.',
  `def` int(11) NOT NULL,
  `lp` int(11) NOT NULL,
  `kp` int(11) NOT NULL,
  `rounds` int(11) NOT NULL,
  `hitchance` int(100) NOT NULL,
  `kpcost` int(11) NOT NULL COMMENT 'Kosten der Attacke.',
  `isprocentualkp` tinyint(1) NOT NULL,
  `bonusatk` int(11) NOT NULL COMMENT 'Bonusangriff der Attacke.',
  `text` longtext NOT NULL COMMENT 'Text der Attacke.',
  `misstext` longtext NOT NULL,
  `transformid` int(2) NOT NULL COMMENT 'Wenn zwei VWs die selbe transformID haben, dann kann man sie zusammen benutzen.',
  `image` varchar(255) NOT NULL COMMENT 'Bild der Attacke',
  `stealkp` int(11) NOT NULL,
  `paralyze` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Daten für Tabelle `attacks`
--

INSERT INTO `attacks` (`id`, `name`, `type`, `ki`, `atk`, `def`, `lp`, `kp`, `rounds`, `hitchance`, `kpcost`, `isprocentualkp`, `bonusatk`, `text`, `misstext`, `transformid`, `image`, `stealkp`, `paralyze`) VALUES
(1, 'Schlag', 1, 0, 10, 0, 0, 0, 0, 95, 0, 0, 0, '!user holt zu einen Schlag aus und zielt auf !target.', '!user holt zu einen Schlag aus und zielt auf !target, verfehlt !target jedoch.', 0, 'https://orig00.deviantart.net/a248/f/2007/357/2/0/sakura_hit_naruto_x3_by_ssj5gogeta13.jpg', 0, 0),
(2, 'Heilung', 2, 0, 10, 0, 0, 0, 0, 100, 50, 0, 0, '!user beugt sich über !target und !target beginnt zu leuchten.', '', 0, 'https://vignette.wikia.nocookie.net/dragonball/images/7/7f/DendeHealingPiccolo.png', 0, 0),
(3, 'Powerup', 3, 0, 1, 0, 0, 0, 0, 100, 20, 0, 0, '!user sammelt Energie und steigert so die Kraft.', '', 0, 'https://vignette.wikia.nocookie.net/rwby/images/5/59/Super_saiyan_vegeta.gif', 0, 0),
(4, 'Verteidigung', 4, 0, 2, 0, 0, 0, 0, 100, 0, 0, 0, '!user geht in Verteidigungsposition.', '', 0, 'https://i.gifer.com/HM4o.gif', 0, 0),
(5, 'Starker Schlag', 1, 0, 10, 0, 0, 0, 0, 90, 5, 0, 10, '!user holt zu einen starken Schlag aus und zielt auf !target.', '!user holt zu einen starken Schlag aus und zielt auf !target. !user verfehlt jedoch.', 0, 'https://pm1.narvii.com/6282/fdb0eadb51db4879525261374256bd3ab1aaaeca_hq.jpg', 0, 0),
(6, 'Powerup2', 3, 0, 1, 0, 0, 0, 0, 100, 50, 0, 0, '!user sammelt Energie und steigert so die Kraft.', '', 0, 'https://i.gifer.com/7nWZ.gif', 0, 0),
(7, 'Powerup3', 3, 0, 2, 0, 0, 0, 0, 100, 50, 0, 0, '!user sammelt Energie und steigert so die Kraft.', '', 2, 'https://data.whicdn.com/images/203899788/original.gif', 0, 0),
(8, 'NPC erschaffen', 5, 0, 15, 0, 0, 0, 0, 100, 40, 1, 0, '!user erschafft einen Klon der genauso aussieht wie er.', '', 0, 'https://vignette.wikia.nocookie.net/naruto/images/f/fc/Clone_technique.png/revision/latest?cb=20150504141733', 0, 0),
(9, 'Fusion', 6, 0, 50, 0, 0, 0, 0, 100, 0, 0, 0, '!user fusioniert mit !target.', '!user konnte nicht mit !target fusionieren.', 0, 'https://thumbs.gfycat.com/JaggedPeskyFrog-max-1mb.gif', 0, 0),
(10, 'Energiesauger', 1, 0, 10, 0, 0, 0, 0, 90, 0, 0, 0, '!user greift !target und saugt Energie.', '!user versucht !target zu greifen, !target weicht jedoch aus.', 0, 'https://vignette.wikia.nocookie.net/naruto/images/a/a5/Chakra_Absorption.png/revision/latest?cb=20150215230823', 10, 0),
(11, 'Versteinern', 7, 0, 0, 0, 0, 0, 3, 90, 10, 0, 0, '!user spuckt !target an und !target wird versteinert.', '!user verfehlt !target mit der Spucke.', 0, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQl8Rqq_kUii1eMVnPz8S4YozFRNF7IelLaR4Kiq87oOePKi90j', 0, 12),
(12, 'Paralyze', 8, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '!user ist versteinert.', '!user ist versteinert.', 0, 'https://i.ytimg.com/vi/kkPeUlEij0g/maxresdefault.jpg', 0, 0),
(13, 'Debuff', 9, -10, -250, -10, -10, -10, 3, 95, 5, 0, 0, '!user belegt !target mit einen Fluch.', '!user versucht !target mit einen Fluch zu belegen, scheitert jedoch.', 0, 'https://thumbs.gfycat.com/QuarterlyAllGibbon-size_restricted.gif', 0, 0),
(14, 'Buff', 10, 10, 10, 10, 10, 10, 3, 100, 10, 0, 0, '!user bufft !target.', '!user hat !target beim buffen verfehlt.', 0, 'https://thumbs.gfycat.com/SorrowfulHarshGardensnake-size_restricted.gif', 0, 0),
(15, 'Heal', 11, 100, 0, 0, 10, 10, 3, 100, 10, 0, 0, '!user belegt !target mit einer Heilaura.', '!user verfehlt !target mit einer Heilaura.', 0, 'http://66.media.tumblr.com/tumblr_mafj5jpXJJ1qa3bqpo4_500.gif', 0, 0),
(16, 'Poison', 12, 0, 0, 0, -10, -10, 3, 90, 10, 0, 0, '!user vergiftet !target.', '!user konnte !target nicht vergiften.', 0, 'https://thumbs.gfycat.com/PotableDisfiguredAmericanbadger-max-1mb.gif', 0, 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `fighterattacks`
--

CREATE TABLE `fighterattacks` (
  `fighter` int(11) NOT NULL COMMENT 'ID des Kämpfers',
  `attack` int(11) NOT NULL COMMENT 'Id der Attacke'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `fighters`
--

CREATE TABLE `fighters` (
  `id` int(11) NOT NULL COMMENT 'Die ID an der man den Kämpfer finden kann.',
  `user` int(11) NOT NULL COMMENT 'ID des Spielers, 0 falls NPC',
  `name` varchar(90) NOT NULL COMMENT 'Name des Kämpfers',
  `image` varchar(255) NOT NULL,
  `fight` int(11) NOT NULL COMMENT 'ID des Kampfes',
  `team` tinyint(1) NOT NULL COMMENT 'Team des Kämpfers, startet bei 0',
  `ki` int(11) NOT NULL COMMENT 'KI des Kämpfers',
  `mki` int(11) NOT NULL COMMENT 'Maximale KI des Kämpfers',
  `atk` int(11) NOT NULL COMMENT 'Angriff des Kämpfers',
  `matk` int(11) NOT NULL COMMENT 'Maximaler Angriff des Kämpfers',
  `def` int(11) NOT NULL COMMENT 'Verteidigung des Kämpfers',
  `mdef` int(11) NOT NULL COMMENT 'Maximale Verteidigung des Kämpfers',
  `lp` int(11) NOT NULL COMMENT 'Leben des Kämpfers.',
  `mlp` int(11) NOT NULL COMMENT 'Maximaler Leben des Kämpfers.',
  `ilp` int(11) NOT NULL COMMENT 'Erhöhter maximaler Leben des Kämpfers durch Verwandlungen.',
  `kp` int(11) NOT NULL COMMENT 'Kp des Kämpfers',
  `mkp` int(11) NOT NULL COMMENT 'Maximale KP des Kämpfers',
  `ikp` int(11) NOT NULL COMMENT 'Erhöhte maximale KP des Kämpfers durch Verwandlungen.',
  `attack` int(11) NOT NULL COMMENT 'Ausgewählter Angriff, ist eine ID',
  `target` int(11) NOT NULL COMMENT 'Ausgewähltes Ziel, ist eine ID',
  `isnpc` tinyint(4) NOT NULL COMMENT 'ob er ein NPC ist oder nicht',
  `transform` longtext NOT NULL COMMENT 'Transformationen die der Kämpfer aktiv hat.',
  `lastactiontime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `iscreated` tinyint(1) NOT NULL,
  `fuseid` int(11) NOT NULL,
  `paralyzed` int(23) NOT NULL,
  `buffs` longtext NOT NULL,
  `heals` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `fights`
--

CREATE TABLE `fights` (
  `id` int(11) NOT NULL COMMENT 'ID des Kampfes',
  `name` varchar(90) NOT NULL COMMENT 'Name des Kampfes',
  `state` tinyint(1) NOT NULL COMMENT 'Status des Kampfes, 1 = offen, 2 = läuft, 3 = beendet',
  `round` int(11) NOT NULL COMMENT 'Runde des Kampfes',
  `mode` varchar(30) NOT NULL COMMENT 'Modus des Kampfes, 1vs1, 2vs1, 5vs5',
  `text` longtext NOT NULL COMMENT 'Text des Kampfes',
  `money` int(11) NOT NULL COMMENT 'Geld der Gewinner kriegt',
  `items` longtext NOT NULL,
  `keepstats` tinyint(1) NOT NULL,
  `levelup` tinyint(1) NOT NULL,
  `tournament` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `name` varchar(90) NOT NULL,
  `image` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` int(11) NOT NULL,
  `type` int(2) NOT NULL,
  `ki` int(11) NOT NULL,
  `lp` int(11) NOT NULL,
  `kp` int(11) NOT NULL,
  `atk` int(11) NOT NULL,
  `def` int(11) NOT NULL,
  `slot` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Daten für Tabelle `items`
--

INSERT INTO `items` (`id`, `name`, `image`, `description`, `price`, `type`, `ki`, `lp`, `kp`, `atk`, `def`, `slot`) VALUES
(1, 'Heilitem', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT_BXA96Df3ErBwVW09_AtrtbQUsIc1c1ncFzUzF-NYak6su2ND', 'Ein Heilitem', 10, 1, 0, 100, 0, 0, 0, 0),
(2, 'Manaitem', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQzjy4QkQp2GKj0OQkIDz9wmk3_f7yJgb7IMvzvfEtL-xmCTyM4', 'Ein Manaitem', 10, 1, 0, 0, 100, 0, 0, 0),
(3, 'Schwert', 'https://d1nhio0ox7pgb.cloudfront.net/_img/g_collection_png/standard/256x256/sword.png', 'ein Schwert', 50, 2, 0, 0, 0, 10, 0, 1),
(4, 'Schwert 2', 'https://d1nhio0ox7pgb.cloudfront.net/_img/g_collection_png/standard/256x256/sword.png', 'ein Schwert', 100, 2, 0, 0, 0, 20, 10, 1),
(5, 'Dragonball', 'https://dumielauxepices.net/sites/default/files/dragon-ball-clipart-stardragon-507762-4292480.png', 'Ein Dragonball', 0, 3, 0, 0, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `market`
--

CREATE TABLE `market` (
  `id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `username` varchar(90) NOT NULL,
  `item` int(11) NOT NULL,
  `itemname` varchar(25590) NOT NULL,
  `itemimage` varchar(255) NOT NULL,
  `amount` int(11) NOT NULL,
  `price` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `senderid` int(11) NOT NULL,
  `sendername` varchar(50) NOT NULL,
  `receiver` int(11) NOT NULL,
  `topic` varchar(255) NOT NULL,
  `text` text NOT NULL,
  `hasread` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `npcs`
--

CREATE TABLE `npcs` (
  `id` int(11) NOT NULL COMMENT 'ID an der man einen User identifizieren kann',
  `name` varchar(50) NOT NULL COMMENT 'Charaktername',
  `image` varchar(255) NOT NULL,
  `ki` int(11) NOT NULL COMMENT 'KI des Spielers',
  `lp` int(11) NOT NULL COMMENT 'LP des Spielers',
  `kp` int(11) NOT NULL COMMENT 'KP des Spelers',
  `atk` int(11) NOT NULL COMMENT 'Angriffstärke',
  `def` int(11) NOT NULL COMMENT 'Verteidigungswert',
  `attacks` longtext NOT NULL,
  `money` int(11) NOT NULL,
  `items` longtext NOT NULL COMMENT 'Format: ID@Amount@Type;ID@Amount@Type',
  `levelup` tinyint(1) NOT NULL,
  `keepstats` tinyint(4) NOT NULL,
  `levelfilter` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

--
-- Daten für Tabelle `npcs`
--

INSERT INTO `npcs` (`id`, `name`, `image`, `ki`, `lp`, `kp`, `atk`, `def`, `attacks`, `money`, `items`, `levelup`, `keepstats`, `levelfilter`) VALUES
(1, 'Gegner', 'https://media.giphy.com/media/muY2DjApRIP3G/giphy.gif', 10, 100, 100, 10, 10, '1;2;13', 20, '1@2@1', 1, 1, 0),
(2, 'Test', 'https://i.kym-cdn.com/photos/images/newsfeed/001/060/067/cd3.gif', 10, 100, 100, 10, 10, '1;2;4', 0, '', 0, 0, 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tournamentbrackets`
--

CREATE TABLE `tournamentbrackets` (
  `id` int(11) NOT NULL,
  `tournament` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `username` varchar(1190) NOT NULL,
  `round` int(2) NOT NULL,
  `col` int(23) NOT NULL,
  `defeated` tinyint(1) NOT NULL,
  `isnpc` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Daten für Tabelle `tournamentbrackets`
--



-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tournaments`
--

CREATE TABLE `tournaments` (
  `id` int(11) NOT NULL,
  `name` varchar(90) NOT NULL,
  `start` datetime NOT NULL,
  `round` int(2) NOT NULL,
  `end` tinyint(4) NOT NULL,
  `minstart` int(3) NOT NULL,
  `itemid` int(11) NOT NULL,
  `itemamount` int(11) NOT NULL,
  `itemtype` int(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

--
-- Daten für Tabelle `tournaments`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `userattacks`
--

CREATE TABLE `userattacks` (
  `user` int(11) NOT NULL COMMENT 'ID des Users',
  `attack` int(11) NOT NULL COMMENT 'ID der Attacke'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Daten für Tabelle `userattacks`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `useritems`
--

CREATE TABLE `useritems` (
  `id` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `item` int(11) NOT NULL,
  `amount` int(11) NOT NULL,
  `slot` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Daten für Tabelle `useritems`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL COMMENT 'ID an der man einen User identifizieren kann',
  `login` varchar(50) NOT NULL COMMENT 'Loginname des Spielers',
  `password` varchar(255) NOT NULL COMMENT 'Password des Uses',
  `session` varchar(40) NOT NULL COMMENT 'session des users damit er eingeloggt bleibt',
  `name` varchar(50) NOT NULL COMMENT 'Charaktername',
  `level` int(11) NOT NULL DEFAULT '1',
  `ki` int(11) NOT NULL COMMENT 'KI des Spielers',
  `lp` int(11) NOT NULL COMMENT 'LP des Spielers',
  `mlp` int(11) NOT NULL,
  `kp` int(11) NOT NULL COMMENT 'KP des Spelers',
  `mkp` int(11) NOT NULL,
  `atk` int(11) NOT NULL COMMENT 'Angriffstärke',
  `def` int(11) NOT NULL COMMENT 'Verteidigungswert',
  `itemlp` int(11) NOT NULL,
  `itemkp` int(11) NOT NULL,
  `itematk` int(11) NOT NULL,
  `itemdef` int(11) NOT NULL,
  `money` int(11) NOT NULL COMMENT 'Geld der Spieler hat',
  `profileimage` varchar(255) NOT NULL,
  `profiledescription` text NOT NULL,
  `challenger` int(11) NOT NULL,
  `challengeitem` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

--
-- Daten für Tabelle `users`
--
--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `attacks`
--
ALTER TABLE `attacks`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `fighters`
--
ALTER TABLE `fighters`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `fights`
--
ALTER TABLE `fights`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `market`
--
ALTER TABLE `market`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `npcs`
--
ALTER TABLE `npcs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indizes für die Tabelle `tournamentbrackets`
--
ALTER TABLE `tournamentbrackets`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `tournaments`
--
ALTER TABLE `tournaments`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `useritems`
--
ALTER TABLE `useritems`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `attacks`
--
ALTER TABLE `attacks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Die ID an der man die Attacke finden kann.', AUTO_INCREMENT=17;
--
-- AUTO_INCREMENT für Tabelle `fighters`
--
ALTER TABLE `fighters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Die ID an der man den Kämpfer finden kann.', AUTO_INCREMENT=13;
--
-- AUTO_INCREMENT für Tabelle `fights`
--
ALTER TABLE `fights`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID des Kampfes', AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT für Tabelle `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT für Tabelle `market`
--
ALTER TABLE `market`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT für Tabelle `npcs`
--
ALTER TABLE `npcs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID an der man einen User identifizieren kann', AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT für Tabelle `tournamentbrackets`
--
ALTER TABLE `tournamentbrackets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;
--
-- AUTO_INCREMENT für Tabelle `tournaments`
--
ALTER TABLE `tournaments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT für Tabelle `useritems`
--
ALTER TABLE `useritems`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;
--
-- AUTO_INCREMENT für Tabelle `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID an der man einen User identifizieren kann', AUTO_INCREMENT=6;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
