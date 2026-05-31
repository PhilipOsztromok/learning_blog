# Anime Vault — Deployment Guide

A complete self-hosted anime database for **osztromok.com/anime**.  
Dark anime-aesthetic UI · PHP 8+ · MySQL/MariaDB · No external API key required.

---

## Directory Layout (on your server)

```
/var/www/html/anime/
├── .htaccess
├── 403.php
├── 404.php
├── browse.php
├── index.php
├── login.php
├── logout.php
├── register.php
├── show.php
├── watchlist.php
├── schema.sql
├── admin/
│   ├── index.php          ← Dashboard
│   ├── sidebar.php
│   ├── anime.php          ← List all anime
│   ├── edit_anime.php     ← Add / edit anime + Jikan auto-fill
│   ├── delete_anime.php
│   ├── studios.php
│   ├── people.php         ← Voice actors, directors; assign cast/staff
│   ├── genres.php
│   ├── users.php
│   └── reviews.php
├── includes/
│   ├── auth.php           ← Session, login, register, CSRF helpers
│   ├── db.php             ← PDO connection (edit credentials here)
│   ├── header.php
│   └── footer.php
├── js/
│   └── main.js
└── styles/
    ├── main.css
    └── favicon.svg
```

---

## 1. Database Setup

```bash
# Log in to MySQL
mysql -u root -p

# Create the database and user
CREATE DATABASE anime_vault CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'anime_user'@'localhost' IDENTIFIED BY 'choose_a_strong_password';
GRANT ALL PRIVILEGES ON anime_vault.* TO 'anime_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import the schema (creates all tables + seeds genres)
mysql -u anime_user -p anime_vault < /var/www/html/anime/schema.sql
```

---

## 2. Configure Database Credentials

Edit `/var/www/html/anime/includes/db.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'anime_vault');
define('DB_USER', 'anime_user');       // ← your DB username
define('DB_PASS', 'choose_a_strong_password');  // ← your DB password
```

---

## 3. Create the Admin User

The schema seeds a placeholder admin row.  
**Replace it with a real bcrypt-hashed password before going live:**

```bash
php -r "echo password_hash('YourSecurePassword123!', PASSWORD_BCRYPT);"
```

Then update the database:

```sql
USE anime_vault;
UPDATE users
SET username = 'admin',
    email    = 'you@osztromok.com',
    password = '$2y$12$...(your hash)...'
WHERE role = 'admin'
LIMIT 1;
```

Or simply register through the site at `/anime/register.php` and then promote yourself:

```sql
UPDATE users SET role = 'admin' WHERE email = 'you@osztromok.com';
```

---

## 4. File Permissions

```bash
# Web server needs to read all files
chown -R www-data:www-data /var/www/html/anime/
find /var/www/html/anime -type f -exec chmod 644 {} \;
find /var/www/html/anime -type d -exec chmod 755 {} \;
```

---

## 5. Apache / Virtual Host

Ensure `mod_rewrite` is enabled:

```bash
a2enmod rewrite headers expires deflate
systemctl reload apache2
```

Make sure `AllowOverride All` is set for `/var/www/html` in your Apache config (so `.htaccess` rules take effect):

```apache
<Directory /var/www/html>
    AllowOverride All
</Directory>
```

---

## 6. PHP Requirements

- PHP **8.0+**
- Extensions: `pdo_mysql`, `json`, `session`, `mbstring`
- `allow_url_fopen = On` (for the Jikan API scraper — usually enabled by default)

Check: `php -m | grep -E 'pdo|json|mbstring'`

---

## 7. Integrating with osztromok.com

On the main site, add a link to `/anime/login.php` under your Projects subtopic.  
The anime site uses **its own CSS** (`/anime/styles/main.css`) and is completely independent — it will not inherit or conflict with the main site's styles.

---

## 8. Auto-fill Feature (Jikan / MyAnimeList API)

On the **Admin → Edit Anime** page, the **"Auto-fill from MyAnimeList"** panel uses the free [Jikan API v4](https://jikan.moe/) — no registration or API key needed.

It fetches:
- Title (Japanese + English)
- Synopsis
- Type, status, episodes, duration
- Premiered season/year
- Source material, age rating
- Poster image URL
- Trailer URL
- Genres (auto-checks matching checkboxes)
- Score from MAL

Review the pre-filled data, then click **"Apply to Form"** and **"Save Anime"**.  
You can override any field before saving.

---

## 9. Site Features Summary

| Feature | Details |
|---|---|
| **Browse & Search** | Full-text search, filter by genre/type/status/year, sort by rating/newest/A-Z |
| **Show Detail Page** | Poster, synopsis, cast, staff, reviews, watchlist widget, trailer link |
| **User Accounts** | Register, login, sessions, CSRF protection |
| **Watchlist** | Per-user: watching / completed / plan to watch / dropped / on hold |
| **Reviews** | Rating (1–10), title, body, spoiler flag; auto-updates site average |
| **Admin Dashboard** | Stats, recent additions |
| **Admin: Anime** | Add/edit/delete anime; auto-fill from Jikan API |
| **Admin: Studios** | CRUD for animation studios |
| **Admin: People** | CRUD for voice actors/directors; assign to cast & staff |
| **Admin: Genres** | CRUD; auto-slug generation |
| **Admin: Users** | Promote/demote admin, disable accounts, reset passwords |
| **Admin: Reviews** | View and delete any review |

---

## 10. Security Notes

- All user input is parameterised (PDO prepared statements — no SQL injection)
- Passwords hashed with `bcrypt` (cost 12)
- CSRF tokens on every form
- `session_regenerate_id()` on login
- `.htaccess` blocks direct access to `/includes/` and sensitive file types
- Security headers set via `.htaccess` (X-Frame-Options, CSP, etc.)
- **Change the default admin password immediately after first deploy**

---

## 11. Optional Enhancements (future ideas)

- Image upload for posters (store in `/anime/uploads/`)
- Email verification on register
- Forgot password / reset email flow
- RSS feed of recently added shows
- Public API endpoint (`/anime/api/anime.php`) for embedding on other pages
- Discord webhook notifications for new reviews
