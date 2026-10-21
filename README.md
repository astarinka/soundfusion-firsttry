# SoundFusion — First Version

SoundFusion is a small PHP and MySQL music catalog for discovering artists and finding their music on streaming services. It includes a featured artist selection on the home page, full artist and category browsing, and a balanced Russian and English music news feed loaded from RSS.

## Features

- Responsive, dark navy interface with a featured home page, artist cards, and detail pages.
- Up to four author-selected artists on the home page; the Music page lists the full catalog.
- Spotify and SoundCloud search links for each artist.
- Artist and category content loaded from MySQL.
- Six Russian and six English music headlines loaded from RSS, cached for 15 minutes, and linked to their original publishers.
- Prepared statements for URL-based database lookups and escaped dynamic HTML output.
- User accounts with unique email and nickname, profile editing, avatar uploads, and favorite artists.
- News matching a signed-in user's favorite artists is shown first in both language feeds.
- Shared navigation and footer; the Events page has been removed.

## Technologies

PHP 8+ with MySQLi and SimpleXML, MySQL or MariaDB, HTML, and CSS. No framework or package installation is required. The News page needs outbound internet access, PHP's SimpleXML extension, and either cURL or `allow_url_fopen`; the rest of the site works without the news feed.

## Run locally with Laragon

1. Start Apache and MySQL in Laragon.
2. Place this repository in `C:\laragon\www\SoundFusion-firsttry` (or your Laragon document root).
3. In HeidiSQL or phpMyAdmin, create a database named `db_web` using `utf8mb4`.
4. Import [`database/db_web.sql`](database/db_web.sql) into `db_web`. It creates the `main`, `menu`, `industry`, and `content` tables and inserts starter catalog data. It also removes the old Events navigation entry.
5. Run [`database/set-featured-artists.sql`](database/set-featured-artists.sql) once to choose the four artists featured on the home page.
6. Copy `config.example.php` to `local.config.php` and enter your local host, port, database, username, and password. `local.config.php` is ignored by Git. The legacy `config.local.php` filename remains supported.
7. Open `http://localhost/SoundFusion-firsttry/`.

If you already have the earlier database, run [`database/upgrade-featured-artists.sql`](database/upgrade-featured-artists.sql) once. It adds the `featured` column and selects the initial four favorites. Do not run this one-time migration again.

To add the example artists and fill in biographies that are blank, run [`database/add-more-artists.sql`](database/add-more-artists.sql). It skips artists already in the database. To choose a different home page selection, edit the names in `database/set-featured-artists.sql` and run it again.

### Accounts and favorite artists

To enable registration and moderator access, import [`database/user-accounts.sql`](database/user-accounts.sql) into the existing `db_web` database using phpMyAdmin. The migration creates account and role tables and promotes the account with the unique nickname `astarinka`; change that nickname in the SQL if needed. It is safe to re-import. Do not re-import `database/db_web.sql` for an existing site. Registration requires a valid email and a password of at least eight characters with an uppercase letter and a digit. Users can edit their display name and unique nickname, upload a JPG/PNG/WebP avatar (up to 3 MB), and manage favorite artists in their profile. Matching artist news is moved to the top of each language feed. The moderator page shows registered users and their favorite artists, so keep moderator access limited to trusted people.

## Manage the News feed

The feed list is in [`news-feeds.php`](news-feeds.php). The page shows up to six Russian and six English music stories in separate sections. To change or add sources, edit its returned array, keeping each entry in this format:

```php
[
    'name' => 'Publication name',
    'language' => 'en', // or 'ru'
    'url' => 'https://example.com/feed.xml',
],
```

Use RSS feeds you are permitted to link to. Set `language` to `ru` or `en`; for a broad Russian feed, set `music_only` to `true` so links in music sections and articles whose titles or summaries mention music-related topics are included. The site displays headlines and short descriptions with links back to the publisher; it does not copy full articles. Feed results are cached for 15 minutes. If a source is unavailable, expand “Why did some news fail to load?” on the News page for the specific error, then check the URL, server internet access, and PHP's SimpleXML plus cURL or `allow_url_fopen` settings.

## Database tables

- `main` — primary site navigation.
- `menu` — retained legacy navigation table.
- `industry` — categories used by artist records.
- `content` — artist names, biographies, image filenames, categories, optional release notes, and the `featured` flag.

Artist image filenames in `content.logo` refer to files in the project root. The card links search for the artist on Spotify and SoundCloud; users can add album or release notes in `content.album`.

## Notes

SoundFusion is an educational project and is not affiliated with the artists, streaming services, publishers, or image rights holders.

---

# SoundFusion — Первая версия

SoundFusion — небольшой музыкальный каталог на PHP и MySQL, который помогает знакомиться с исполнителями и находить их музыку на стриминговых платформах. На главной отображаются до четырёх избранных артистов, а страница «Музыка» показывает весь каталог. Есть страницы артистов, категории и сбалансированная лента музыкальных новостей на русском и английском.

## Возможности

- Адаптивный интерфейс в тёмно-синих тонах, карточки артистов и страницы с описанием.
- До четырёх выбранных автором артистов на главной; полный каталог находится в разделе «Музыка».
- Ссылки для поиска музыки каждого исполнителя в Spotify и SoundCloud.
- Каталог артистов и категорий загружается из MySQL.
- До шести русских и шести английских музыкальных заголовков загружаются из RSS, кэшируются на 15 минут и ведут к первоисточникам.
- Подготовленные запросы для параметров URL и экранирование динамического HTML.
- Аккаунты с уникальной почтой и никнеймом, редактированием профиля, загрузкой аватара и списком любимых артистов.
- Новости о любимых исполнителях поднимаются выше в русской и английской лентах.
- Общие навигация и подвал; страница «События» удалена.

## Технологии

PHP 8+ с MySQLi и SimpleXML, MySQL или MariaDB, HTML и CSS. Фреймворки и пакеты не нужны. Для ленты новостей серверу необходим доступ в интернет, расширение PHP SimpleXML и cURL либо настройка `allow_url_fopen`; остальные страницы работают и без RSS.

## Локальный запуск в Laragon

1. Запустите Apache и MySQL в Laragon.
2. Поместите репозиторий в `C:\laragon\www\SoundFusion-firsttry` (или в папку документов Laragon).
3. В HeidiSQL или phpMyAdmin создайте базу `db_web` с кодировкой `utf8mb4`.
4. Импортируйте в неё [`database/db_web.sql`](database/db_web.sql). Скрипт создаёт таблицы `main`, `menu`, `industry`, `content`, добавляет начальные данные и удаляет старую ссылку на события.
5. Один раз выполните [`database/set-featured-artists.sql`](database/set-featured-artists.sql), чтобы выбрать четырёх артистов для главной страницы.
6. Скопируйте `config.example.php` в `local.config.php` и укажите локальный адрес, порт, базу данных, имя пользователя и пароль. `local.config.php` исключён из Git. Старое имя `config.local.php` тоже поддерживается.
7. Откройте `http://localhost/SoundFusion-firsttry/`.

Если база уже была создана для предыдущей версии, один раз запустите [`database/upgrade-featured-artists.sql`](database/upgrade-featured-artists.sql). Этот скрипт добавит поле избранного и отметит первоначальных четырёх артистов. Повторно его выполнять не нужно.

Чтобы добавить примеры новых артистов и заполнить пустые биографии, запустите [`database/add-more-artists.sql`](database/add-more-artists.sql). Уже существующие артисты с теми же именами не дублируются. Чтобы поменять избранное, измените имена в `database/set-featured-artists.sql` и выполните запрос повторно.

### Регистрация и любимые артисты

Чтобы включить регистрацию и права модератора, импортируйте [`database/user-accounts.sql`](database/user-accounts.sql) в существующую базу `db_web` через phpMyAdmin. Скрипт создаёт таблицы аккаунтов и ролей и назначает модератором аккаунт с уникальным никнеймом `astarinka`; при необходимости замените никнейм в SQL. Скрипт можно импортировать повторно. Для уже работающего сайта не импортируйте повторно `database/db_web.sql`. При регистрации нужна корректная почта и пароль не короче 8 символов, содержащий заглавную букву и цифру. В профиле можно поменять имя и уникальный никнейм, загрузить аватар JPG/PNG/WebP (до 3 МБ) и управлять списком любимых артистов. Новости о них будут подниматься выше внутри каждой языковой ленты. В меню модератора отображаются зарегистрированные пользователи и их любимые артисты; выдавайте эту роль только доверенным людям.

## Как изменить RSS-ленту новостей

Список источников находится в файле [`news-feeds.php`](news-feeds.php). На странице показывается до шести русских и шести английских музыкальных публикаций в отдельных блоках. Чтобы заменить или добавить источник, отредактируйте массив, укажите язык (`ru` или `en`) и HTTPS-адрес RSS в таком формате:

```php
[
    'name' => 'Название издания',
    'language' => 'ru',
    'url' => 'https://example.com/feed.xml',
],
```

Для общей русской ленты добавьте `'music_only' => true`: будут учитываться ссылки из музыкального раздела и статьи, в заголовках или описаниях которых есть музыкальные темы. Добавляйте RSS, которые разрешают показывать заголовки со ссылками на статьи. Сайт показывает короткие описания и ведёт на издание, но не копирует полные статьи. Результаты кэшируются на 15 минут. Если лента не загружается, разверните сообщение «Почему часть новостей не загрузилась?» на странице «Новости»: там появится причина. Также проверьте адрес RSS, доступ сервера в интернет и наличие SimpleXML плюс cURL либо `allow_url_fopen` в активной версии PHP.

## Таблицы базы данных

- `main` — основная навигация сайта.
- `menu` — сохранённая таблица старого меню.
- `industry` — категории артистов.
- `content` — имена артистов, биографии, имена файлов изображений, категории, заметки о релизах и флаг `featured`.

В `content.logo` указываются имена файлов в корне проекта. Карточки ведут на поиск артиста в Spotify и SoundCloud. Названия альбомов или релизов можно добавить в поле `content.album`.

## Примечание

SoundFusion — учебный проект, не связанный с артистами, стриминговыми сервисами, издателями или правообладателями изображений.

## Безопасность

Настройки MySQL загружаются из игнорируемого Git файла `local.config.php`; в коде нет встроенных имени пользователя или пароля. Не добавляйте локальные настройки или production-секреты в Git. На публичном сервере используйте отдельного MySQL-пользователя только для базы проекта, включите HTTPS и храните секреты вне папки сайта, если хостинг это позволяет. Правила Apache закрывают прямой доступ к локальным настройкам, SQL-дампам, журналам и PHP-файлам в папках загрузок. В модераторском разделе видны почты пользователей и их любимые артисты — назначайте модераторами только доверенных людей.
