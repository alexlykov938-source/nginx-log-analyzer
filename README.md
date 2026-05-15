# Nginx Log Analyzer

Веб-приложение на Yii2 для парсинга и анализа nginx access-логов.

## Что делает

- Парсит nginx combined-формат лога
- Определяет из User-Agent: ОС, архитектуру (x86/x64), браузер
- Хранит данные в MySQL
- Показывает два графика и таблицу с фильтрами и сортировкой

## Требования

- PHP 7.4+
- Composer
- MySQL 5.7+

## Установка

```bash
# 1. Поставить зависимости
composer install

# 2. Создать базу данных
mysql -u root -p -e "CREATE DATABASE nginx_logs CHARACTER SET utf8mb4;"

# 3. Прописать настройки БД в config/db.php

# 4. Создать таблицу
php yii migrate

# 5. Загрузить лог
php yii log/import /path/to/access.log

# 6. Запустить
php yii serve
# Открыть http://localhost:8080
```

## Консольные команды

```bash
# Импорт лога
php yii log/import /path/to/access.log

# Импорт с очисткой таблицы перед загрузкой
php yii log/import /path/to/access.log --truncate=1

# Посмотреть сколько записей в БД
php yii log/stats
```

## Структура проекта

```
├── commands/LogController.php        — консольная команда импорта
├── config/{db,web,console}.php       — конфиги
├── controllers/SiteController.php    — единственная страница
├── migrations/                       — создание таблицы в БД
├── models/LogEntry.php               — парсинг + запросы к БД
├── models/LogFilter.php              — фильтры
├── views/site/index.php              — дашборд (графики + таблица)
└── yii                               — точка входа консоли
```

## Лицензия

MIT