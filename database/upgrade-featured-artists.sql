-- Run this once on an existing database that was created before the featured column was added.
ALTER TABLE `content`
    ADD COLUMN `featured` TINYINT(1) NOT NULL DEFAULT 0 AFTER `active`;

UPDATE `content`
SET `featured` = CASE
    WHEN `companyname` IN ('The Weeknd', 'Kendrick Lamar', 'Future', 'Drake') THEN 1
    ELSE 0
END;
