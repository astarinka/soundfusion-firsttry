-- Select the four artists shown on the home page. Edit the names to change your favorites.
UPDATE `content`
SET `featured` = CASE
    WHEN `companyname` IN ('The Weeknd', 'Kendrick Lamar', 'Future', 'Drake') THEN 1
    ELSE 0
END;
