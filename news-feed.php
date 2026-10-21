<?php
declare(strict_types=1);

function downloadMusicFeed(string $url, string $name): ?string
{
    $maxBytes = 2 * 1024 * 1024;
    $curlFailure = '';
    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_MAXFILESIZE_LARGE => $maxBytes,
            CURLOPT_USERAGENT => 'SoundFusion music news reader/1.0',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
        ]);
        $response = curl_exec($curl);
        $httpStatus = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if (is_string($response) && $httpStatus >= 200 && $httpStatus < 300) {
            return $response;
        }
        $curlFailure = $curlError !== '' ? $curlError : 'HTTP ' . $httpStatus;
    }

    if (!filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
        $GLOBALS['soundfusionNewsFeedErrors'][] = $curlFailure !== ''
            ? $name . ': ошибка HTTPS. Проверьте доступ PHP к интернету и SSL-сертификаты.'
            : 'Для загрузки RSS включите расширение cURL или настройку allow_url_fopen в активной версии PHP.';
        return null;
    }

    $context = stream_context_create([
        'http' => [
            'timeout' => 8,
            'user_agent' => 'SoundFusion music news reader/1.0',
            'ignore_errors' => true,
            'follow_location' => 0,
            'max_redirects' => 0,
        ],
    ]);
    $stream = @fopen($url, 'rb', false, $context);
    $response = is_resource($stream) ? stream_get_contents($stream, $maxBytes + 1) : false;
    if (is_resource($stream)) {
        fclose($stream);
    }
    if (is_string($response) && strlen($response) <= $maxBytes && $response !== '') {
        return $response;
    }

    $GLOBALS['soundfusionNewsFeedErrors'][] = $name . ': PHP не смог загрузить RSS. Проверьте доступ в интернет, размер ленты и SSL-сертификаты.';
    return null;
}

function fetchMusicNews(): array
{
    $GLOBALS['soundfusionNewsFeedErrors'] = [];
    $cachePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'soundfusion-news-cache-v3.json';
    $cacheLifetime = 15 * 60;
    $cached = null;

    if (is_file($cachePath)) {
        $cacheContents = @file_get_contents($cachePath);
        $cacheData = is_string($cacheContents) ? json_decode($cacheContents, true) : null;
        if (is_array($cacheData) && isset($cacheData['items']) && is_array($cacheData['items'])) {
            $cached = $cacheData['items'];
            if (time() - (int) ($cacheData['saved_at'] ?? 0) < $cacheLifetime) {
                return $cached;
            }
        }
    }

    if (!function_exists('simplexml_load_string')) {
        $GLOBALS['soundfusionNewsFeedErrors'][] = 'В активной версии PHP не включено расширение SimpleXML.';
        return $cached ?? [];
    }

    $sources = require __DIR__ . '/news-feeds.php';
    $itemsByLanguage = ['ru' => [], 'en' => []];

    foreach ($sources as $source) {
        $feedUrl = (string) ($source['url'] ?? '');
        $sourceName = (string) ($source['name'] ?? 'RSS');
        if (parse_url($feedUrl, PHP_URL_SCHEME) !== 'https') {
            $GLOBALS['soundfusionNewsFeedErrors'][] = $sourceName . ': адрес источника должен начинаться с HTTPS.';
            continue;
        }

        $xmlText = downloadMusicFeed($feedUrl, $sourceName);
        if ($xmlText === null) {
            continue;
        }

        $previousSetting = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlText, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        libxml_clear_errors();
        libxml_use_internal_errors($previousSetting);
        if (!$xml) {
            $GLOBALS['soundfusionNewsFeedErrors'][] = $sourceName . ': полученный ответ не удалось разобрать как RSS/XML.';
            continue;
        }

        $entries = $xml->xpath('//*[local-name()="item"]');
        if (!$entries) {
            $entries = $xml->xpath('//*[local-name()="entry"]') ?: [];
        }
        if (!$entries) {
            $GLOBALS['soundfusionNewsFeedErrors'][] = $sourceName . ': в RSS не найдены записи новостей.';
            continue;
        }

        $language = ($source['language'] ?? 'en') === 'ru' ? 'ru' : 'en';
        $startingCount = count($itemsByLanguage[$language]);
        foreach ($entries as $entry) {
            $title = trim(html_entity_decode(strip_tags((string) $entry->title), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $url = trim((string) $entry->link);
            if ($url === '' && isset($entry->link['href'])) {
                $url = trim((string) $entry->link['href']);
            }
            if ($title === '' || parse_url($url, PHP_URL_SCHEME) !== 'https') {
                continue;
            }

            $path = (string) parse_url($url, PHP_URL_PATH);
            $isMusicCategory = strpos($path, '/music/') !== false;
            if (isset($entry->category)) {
                foreach ($entry->category as $category) {
                    $categoryName = (string) $category;
                    if ($categoryName === '' && isset($category['term'])) {
                        $categoryName = (string) $category['term'];
                    }
                    if (preg_match('/music|музык/iu', $categoryName)) {
                        $isMusicCategory = true;
                        break;
                    }
                }
            }
            $description = trim(html_entity_decode(strip_tags((string) ($entry->description ?? $entry->summary ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if (!empty($source['music_only']) && !$isMusicCategory && !preg_match('/музык|альбом|песн|сингл|трек|пев|рэп|рэпер|исполнител|артист|групп|концерт|турн|клип|хит|композиц|саундтрек|рок-|поп-|r\s*&\s*b|hip.?hop|rapper|singer|song|single|album|music|artist|band|concert|tour|track|release|soundtrack|producer|\bdj\b/iu', $title . ' ' . $description)) {
                continue;
            }
            $description = preg_replace('/\s+/u', ' ', $description) ?? '';
            if (preg_match_all('/./us', $description, $characters) !== false && count($characters[0]) > 240) {
                $description = rtrim(implode('', array_slice($characters[0], 0, 237))) . '…';
            }
            $publishedText = (string) ($entry->pubDate ?? $entry->published ?? $entry->updated ?? '');
            $itemsByLanguage[$language][] = [
                'title' => $title,
                'url' => $url,
                'description' => $description,
                'published_at' => strtotime($publishedText) ?: 0,
                'source' => $sourceName,
                'language' => $language,
            ];
        }
        if (count($itemsByLanguage[$language]) === $startingCount) {
            $message = !empty($source['music_only'])
                ? 'RSS получен, но в нём не нашлось ссылок из раздела музыки.'
                : 'RSS получен, но в нём не нашлось подходящих публикаций.';
            $GLOBALS['soundfusionNewsFeedErrors'][] = $sourceName . ': ' . $message;
        }
    }

    foreach ($itemsByLanguage as &$languageItems) {
        usort($languageItems, static fn (array $left, array $right): int => $right['published_at'] <=> $left['published_at']);
        $languageItems = array_slice($languageItems, 0, 6);
    }
    unset($languageItems);
    $items = array_merge($itemsByLanguage['ru'], $itemsByLanguage['en']);
    if ($items) {
        @file_put_contents($cachePath, json_encode(['saved_at' => time(), 'items' => $items], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        return $items;
    }

    return $cached ?? [];
}
