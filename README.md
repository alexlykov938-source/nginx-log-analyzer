# Nginx Log Analyzer

A **Yii2 Basic** web application for parsing, storing, and visualising nginx access logs.

![Dashboard screenshot](docs/screenshot.png)

---

## Features

| Feature | Details |
|---|---|
| **Log parsing** | Regex-based parser for nginx *combined* log format |
| **User-Agent analysis** | Extracts OS, CPU architecture (x86 / x64), and browser with version |
| **Charts** | Requests per day • Top-3 browsers share (%) per day |
| **Table** | Date · Requests · Top URL · Top Browser — fully sortable |
| **Filters** | Date range (≤ 1 year) · OS · Architecture |
| **Console command** | Bulk-imports a log file with configurable batch size |
| **Database** | MySQL — schema created via Yii2 migrations |

---

## Requirements

- PHP ≥ 7.4 (8.x recommended)
- Composer
- MySQL 5.7+ / MariaDB 10.3+
- nginx (or Apache with `mod_rewrite`)

---

## Installation

### 1 — Clone the repository

```bash
git clone https://github.com/<your-username>/nginx-log-analyzer.git
cd nginx-log-analyzer
```

### 2 — Install PHP dependencies

```bash
composer install
```

### 3 — Configure the database

Copy and edit the database config:

```bash
cp config/db.php.example config/db.php   # if applicable
```

Edit **`config/db.php`**:

```php
return [
    'class'    => 'yii\db\Connection',
    'dsn'      => 'mysql:host=localhost;dbname=nginx_logs',
    'username' => 'YOUR_DB_USER',
    'password' => 'YOUR_DB_PASS',
    'charset'  => 'utf8mb4',
];
```

Create the database:

```sql
CREATE DATABASE nginx_logs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4 — Run migrations

```bash
php yii migrate
```

This creates the `log_entries` table with all required indexes.

### 5 — Import a log file

```bash
# Basic import
php yii log/import /path/to/access.log

# Truncate the table first, use 500-row batches
php yii log/import /path/to/access.log --truncate=1 --batch-size=500

# Short aliases
php yii log/import /path/to/access.log -t -b 500
```

After import, verify with:

```bash
php yii log/stats
```

### 6 — Configure the web server

Copy `nginx.conf.example` to your nginx `sites-available` directory, adjust the `server_name` and `root` paths, then reload nginx.

Or use the built-in PHP development server for a quick demo:

```bash
php yii serve --port=8080
# Open http://localhost:8080
```

---

## Project Structure

```
nginx-log-analyzer/
├── commands/
│   └── LogController.php        # Console command  (php yii log/import …)
├── config/
│   ├── console.php              # Console application config
│   ├── db.php                   # Database connection
│   └── web.php                  # Web application config
├── controllers/
│   └── SiteController.php       # Dashboard controller
├── migrations/
│   └── m190321_000001_create_log_entries_table.php
├── models/
│   ├── LogEntry.php             # ActiveRecord + parsing logic
│   └── LogFilter.php            # Filter form model
├── views/
│   ├── layouts/main.php         # Base layout (Bootstrap 4 + Chart.js)
│   └── site/index.php           # Dashboard (charts + table)
├── web/
│   └── index.php                # Web entry point
├── yii                          # Console entry point
├── composer.json
└── nginx.conf.example
```

---

## Database Schema

```sql
CREATE TABLE log_entries (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip           VARCHAR(45)   NOT NULL,
    request_time DATETIME      NOT NULL,
    url          VARCHAR(2048) NOT NULL,
    method       VARCHAR(10)   NOT NULL DEFAULT 'GET',
    status_code  SMALLINT UNSIGNED NOT NULL DEFAULT 200,
    user_agent   TEXT,
    os           VARCHAR(100),
    architecture VARCHAR(10),          -- x86 | x64 | Unknown
    browser      VARCHAR(100),
    created_at   INT UNSIGNED  NOT NULL,

    INDEX idx_log_request_time (request_time),
    INDEX idx_log_os           (os),
    INDEX idx_log_architecture (architecture),
    INDEX idx_log_browser      (browser),
    INDEX idx_log_ip           (ip),
    INDEX idx_log_status_code  (status_code)
);
```

---

## Console Commands

| Command | Description |
|---|---|
| `php yii log/import <file>` | Parse and import a log file |
| `php yii log/import <file> --truncate=1` | Clear table then import |
| `php yii log/import <file> --batch-size=N` | Set bulk-insert batch size (default 1000) |
| `php yii log/stats` | Show row count and date range |
| `php yii migrate` | Apply all pending migrations |
| `php yii migrate/down` | Roll back last migration |

---

## Supported Log Format

Standard nginx **combined** log format:

```
$remote_addr - $remote_user [$time_local] "$request" $status $body_bytes_sent "$http_referer" "$http_user_agent"
```

Example line:

```
66.249.79.119 - - [21/Mar/2019:06:28:37 +0300] "GET /upload/img.jpg HTTP/1.1" 200 279894 "-" "Googlebot-Image/1.0"
```

### User-Agent Parsing

| Field | Examples |
|---|---|
| **OS** | Windows 10, Windows 7, Mac OS X, Linux, Android, iOS, ChromeOS … |
| **Architecture** | x64, x86, Unknown |
| **Browser** | Chrome 73.0, Firefox 65.0, Safari 12.0, IE 11, Opera 60.0, Yandex Browser, Bot / Crawler … |

---

## Git Workflow

```bash
git init
git add .
git commit -m "feat: initial Yii2 nginx log analyzer"
git remote add origin https://github.com/<your-username>/nginx-log-analyzer.git
git push -u origin main
```

---

## License

MIT
