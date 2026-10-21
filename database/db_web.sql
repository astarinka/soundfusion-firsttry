-- SoundFusion starter schema for MySQL / MariaDB.
-- Import into a database named db_web. Existing catalog rows are left intact.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `main` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(80) NOT NULL,
    `link` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `menu` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(80) NOT NULL,
    `alt` VARCHAR(160) NOT NULL DEFAULT '',
    `link` VARCHAR(255) NOT NULL,
    `enable` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `industry` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `description` VARCHAR(120) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `content` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `companyname` VARCHAR(160) NOT NULL,
    `about` TEXT NOT NULL,
    `logo` VARCHAR(255) NOT NULL DEFAULT '',
    `industry` INT UNSIGNED NOT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `featured` TINYINT(1) NOT NULL DEFAULT 0,
    `link` VARCHAR(500) NOT NULL DEFAULT '',
    `description` VARCHAR(255) NOT NULL DEFAULT '',
    `album` TEXT NOT NULL,
    PRIMARY KEY (`id`),
    KEY `content_industry_idx` (`industry`),
    CONSTRAINT `content_industry_fk` FOREIGN KEY (`industry`) REFERENCES `industry` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM `main` WHERE `link` = 'events.php';
DELETE FROM `menu` WHERE `link` = 'events.php';

INSERT IGNORE INTO `main` (`id`, `name`, `link`) VALUES
(1, 'Главная', 'index.php'),
(2, 'Музыка', 'music.php'),
(4, 'Новости', 'news.php'),
(5, 'О проекте', 'about.php');

INSERT IGNORE INTO `menu` (`id`, `name`, `alt`, `link`, `enable`) VALUES
(1, 'Главная', 'Главная страница', 'index.php', 1),
(2, 'Музыка', 'Музыкальный каталог', 'music.php', 1),
(4, 'Новости', 'Музыкальные новости', 'news.php', 1),
(5, 'О проекте', 'Информация о проекте', 'about.php', 1);

INSERT IGNORE INTO `industry` (`id`, `description`) VALUES
(1, 'Поп и R&B'),
(2, 'Хип-хоп'),
(3, 'Продюсеры'),
(4, 'Альтернативная музыка');

INSERT IGNORE INTO `content` (`id`, `companyname`, `about`, `logo`, `industry`, `active`, `link`, `description`, `album`) VALUES
(1, 'The Weeknd', '', 'theweeknd.jpg', 1, 1, '', 'Поп и R&B', ''),
(2, 'Playboi Carti', '', 'playboicarti.jpg', 2, 1, '', 'Хип-хоп', ''),
(3, 'Kendrick Lamar', '', 'kendrick.jpg', 2, 1, '', 'Хип-хоп', ''),
(4, 'Future', '', 'Future.jpg', 2, 1, '', 'Хип-хоп', ''),
(5, 'Metro Boomin', '', 'metroboomin.jpg', 3, 1, '', 'Продюсеры', ''),
(6, 'Drake', '', 'drake.jpg', 1, 1, '', 'Поп и R&B', ''),
(7, 'Yeat', '', 'yeat.jpg', 2, 1, '', 'Хип-хоп', ''),
(8, 'Don Toliver', '', 'don.jpg', 1, 1, '', 'Поп и R&B', ''),
(9, 'Tyler, The Creator', '', 'tyler.jpg', 2, 1, '', 'Хип-хоп', ''),
(10, 'Ye', '', 'ye.jpg', 2, 1, '', 'Хип-хоп', ''),
(11, 'A$AP Rocky', '', 'rocky.jpg', 2, 1, '', 'Хип-хоп', ''),
(12, 'Radiohead', '', 'radiohead.jpg', 4, 1, '', 'Альтернативная музыка', '');
