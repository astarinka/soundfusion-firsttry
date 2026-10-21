-- Импортируйте один раз в существующую базу db_web через phpMyAdmin.
-- Создаёт комнаты артистов, членство пользователей и постоянную историю сообщений.
SET NAMES utf8mb4;
USE `db_web`;

CREATE TABLE IF NOT EXISTS `artist_chat_members` (
    `user_id` BIGINT UNSIGNED NOT NULL,
    `artist_id` INT UNSIGNED NOT NULL,
    `joined_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `artist_id`),
    KEY `artist_chat_members_artist_idx` (`artist_id`, `joined_at`),
    CONSTRAINT `artist_chat_members_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `artist_chat_members_artist_fk` FOREIGN KEY (`artist_id`) REFERENCES `content` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `artist_chat_messages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `artist_id` INT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `message` VARCHAR(1000) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `artist_chat_messages_room_idx` (`artist_id`, `id`),
    KEY `artist_chat_messages_user_idx` (`user_id`),
    CONSTRAINT `artist_chat_messages_artist_fk` FOREIGN KEY (`artist_id`) REFERENCES `content` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `artist_chat_messages_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `artist_private_messages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `artist_id` INT UNSIGNED NOT NULL,
    `sender_id` BIGINT UNSIGNED NOT NULL,
    `recipient_id` BIGINT UNSIGNED NOT NULL,
    `message` VARCHAR(1000) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `artist_private_conversation_idx` (`artist_id`, `sender_id`, `recipient_id`, `id`),
    KEY `artist_private_recipient_idx` (`recipient_id`, `id`),
    CONSTRAINT `artist_private_artist_fk` FOREIGN KEY (`artist_id`) REFERENCES `content` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `artist_private_sender_fk` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `artist_private_recipient_fk` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
