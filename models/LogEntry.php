<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * ActiveRecord model for table {{%log_entries}}.
 *
 * @property int    $id
 * @property string $ip
 * @property string $request_time  datetime
 * @property string $url
 * @property string $method
 * @property int    $status_code
 * @property string $user_agent
 * @property string $os
 * @property string $architecture
 * @property string $browser
 * @property int    $created_at
 */
class LogEntry extends ActiveRecord
{
    // -------------------------------------------------------------------------
    // Регулярное выражение для парсинга строки nginx combined-формата
    // -------------------------------------------------------------------------
    const LOG_PATTERN = '/^(\S+) \S+ \S+ \[([^\]]+)\] "(\S+) (\S+) [^"]+" (\d+) \d+ "[^"]*" "([^"]*)"$/';

    // -------------------------------------------------------------------------
    // Имя таблицы в БД
    // -------------------------------------------------------------------------
    public static function tableName(): string
    {
        return '{{%log_entries}}';
    }

    // -------------------------------------------------------------------------
    // Правила валидации
    // -------------------------------------------------------------------------
    public function rules(): array
    {
        return [
            [['ip', 'request_time', 'url'], 'required'],
            ['ip',           'string', 'max' => 45],
            ['request_time', 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            ['url',          'string', 'max' => 2048],
            ['method',       'string', 'max' => 10],
            ['status_code',  'integer'],
            ['user_agent',   'string'],
            ['os',           'string', 'max' => 100],
            ['architecture', 'string', 'max' => 10],
            ['browser',      'string', 'max' => 100],
            ['created_at',   'integer'],
        ];
    }

    // -------------------------------------------------------------------------
    // Человекочитаемые названия атрибутов
    // -------------------------------------------------------------------------
    public function attributeLabels(): array
    {
        return [
            'id'           => 'ID',
            'ip'           => 'IP Address',
            'request_time' => 'Date / Time',
            'url'          => 'URL',
            'method'       => 'Method',
            'status_code'  => 'Status',
            'user_agent'   => 'User-Agent',
            'os'           => 'OS',
            'architecture' => 'Architecture',
            'browser'      => 'Browser',
            'created_at'   => 'Imported At',
        ];
    }

    // =========================================================================
    // ПАРСИНГ
    // =========================================================================

    /**
     * Разобрать одну строку nginx-лога.
     * Возвращает заполненный (но ещё не сохранённый) объект LogEntry,
     * либо null если строка не соответствует формату.
     */
    public static function fromLogLine(string $line): ?self
    {
        $line = trim($line);
        if ($line === '') {
            return null;
        }

        if (!preg_match(self::LOG_PATTERN, $line, $m)) {
            return null;
        }

        [, $ip, $rawTime, $method, $url, $statusCode, $userAgent] = $m;

        $entry                = new self();
        $entry->ip            = $ip;
        $entry->request_time  = self::parseDateTime($rawTime);
        $entry->method        = strtoupper($method);
        $entry->url           = $url;
        $entry->status_code   = (int) $statusCode;
        $entry->user_agent    = $userAgent;
        $entry->created_at    = time();

        [$os, $arch, $browser] = self::parseUserAgent($userAgent);
        $entry->os            = $os;
        $entry->architecture  = $arch;
        $entry->browser       = $browser;

        return $entry;
    }

    /**
     * Преобразует строку даты nginx в формат MySQL DATETIME.
     * Вход:  "21/Mar/2019:00:20:06 +0300"
     * Выход: "2019-03-21 00:20:06"
     */
    private static function parseDateTime(string $raw): string
    {
        $months = [
            'Jan' => '01','Feb' => '02','Mar' => '03','Apr' => '04',
            'May' => '05','Jun' => '06','Jul' => '07','Aug' => '08',
            'Sep' => '09','Oct' => '10','Nov' => '11','Dec' => '12',
        ];

        // 21/Mar/2019:00:20:06 +0300
        if (preg_match('/(\d{2})\/(\w{3})\/(\d{4}):(\d{2}:\d{2}:\d{2})/', $raw, $m)) {
            $day   = $m[1];
            $month = $months[$m[2]] ?? '01';
            $year  = $m[3];
            $time  = $m[4];
            return "{$year}-{$month}-{$day} {$time}";
        }

        return date('Y-m-d H:i:s');
    }

    /**
     * Разобрать строку User-Agent на [ОС, архитектура, браузер].
     * Все три значения могут быть 'Unknown' если определить не удалось.
     */
    public static function parseUserAgent(string $ua): array
    {
        $os   = self::detectOS($ua);
        $arch = self::detectArchitecture($ua);
        $browser = self::detectBrowser($ua);
        return [$os, $arch, $browser];
    }

    // -------------------------------------------------------------------------
    // Определение операционной системы
    // -------------------------------------------------------------------------
    private static function detectOS(string $ua): string
    {
        $ua = strtolower($ua);

        if (str_contains($ua, 'android'))                return 'Android';
        if (str_contains($ua, 'iphone'))                 return 'iOS';
        if (str_contains($ua, 'ipad'))                   return 'iOS';
        if (str_contains($ua, 'ipod'))                   return 'iOS';
        if (str_contains($ua, 'windows phone'))          return 'Windows Phone';
        if (preg_match('/windows nt (\d+\.\d+)/i', $ua, $m)) {
            return 'Windows ' . self::windowsNtVersion($m[1]);
        }
        if (str_contains($ua, 'macintosh') || str_contains($ua, 'mac os x')) {
            return 'Mac OS X';
        }
        if (str_contains($ua, 'linux'))                  return 'Linux';
        if (str_contains($ua, 'cros'))                   return 'ChromeOS';
        if (str_contains($ua, 'freebsd'))                return 'FreeBSD';
        if (str_contains($ua, 'openbsd'))                return 'OpenBSD';

        return 'Unknown';
    }

    private static function windowsNtVersion(string $ntVer): string
    {
        $map = [
            '10.0' => '10',
            '6.3'  => '8.1',
            '6.2'  => '8',
            '6.1'  => '7',
            '6.0'  => 'Vista',
            '5.2'  => 'XP x64',
            '5.1'  => 'XP',
            '5.0'  => '2000',
        ];
        return $map[$ntVer] ?? $ntVer;
    }

    // -------------------------------------------------------------------------
    // Определение архитектуры процессора
    // -------------------------------------------------------------------------
    private static function detectArchitecture(string $ua): string
    {
        if (preg_match('/x86_64|x86-64|win64|wow64|amd64|x64/i', $ua)) {
            return 'x64';
        }
        if (preg_match('/i[3-6]86|i86pc|x86/i', $ua)) {
            return 'x86';
        }
        // 64-bit ARM (Apple Silicon, Android arm64)
        if (preg_match('/arm64|aarch64/i', $ua)) {
            return 'x64';
        }
        return 'Unknown';
    }

    // -------------------------------------------------------------------------
    // Определение браузера (порядок важен: сначала частные случаи)
    // -------------------------------------------------------------------------
    private static function detectBrowser(string $ua): string
    {
        // Bots
        if (preg_match('/googlebot|bingbot|yandexbot|baiduspider|duckduckbot|slurp|facebookexternalhit|twitterbot|bot|crawler|spider/i', $ua)) {
            return 'Bot / Crawler';
        }

        // Edge (new Chromium-based, must come before Chrome)
        if (preg_match('/Edg\/(\d+[\.\d]*)/i', $ua, $m))            return 'Microsoft Edge ' . $m[1];
        // Legacy Edge
        if (preg_match('/Edge\/(\d+[\.\d]*)/i', $ua, $m))           return 'Microsoft Edge ' . $m[1];
        // Internet Explorer
        if (preg_match('/MSIE (\d+[\.\d]*)/i', $ua, $m))            return 'IE ' . $m[1];
        if (preg_match('/Trident\/.*rv:(\d+[\.\d]*)/i', $ua, $m))   return 'IE ' . $m[1];
        // Opera (new)
        if (preg_match('/OPR\/(\d+[\.\d]*)/i', $ua, $m))            return 'Opera ' . $m[1];
        // Opera (old)
        if (preg_match('/Opera\/(\d+[\.\d]*)/i', $ua, $m))          return 'Opera ' . $m[1];
        // Yandex Browser
        if (preg_match('/YaBrowser\/(\d+[\.\d]*)/i', $ua, $m))      return 'Yandex Browser ' . $m[1];
        // Samsung Browser
        if (preg_match('/SamsungBrowser\/(\d+[\.\d]*)/i', $ua, $m)) return 'Samsung Browser ' . $m[1];
        // UC Browser
        if (preg_match('/UCBrowser\/(\d+[\.\d]*)/i', $ua, $m))      return 'UC Browser ' . $m[1];
        // Firefox
        if (preg_match('/Firefox\/(\d+[\.\d]*)/i', $ua, $m))        return 'Firefox ' . $m[1];
        // Chrome (must come before Safari)
        if (preg_match('/Chrome\/(\d+[\.\d]*)/i', $ua, $m))         return 'Chrome ' . $m[1];
        // Safari
        if (preg_match('/Version\/(\d+[\.\d]*).*Safari/i', $ua, $m)) return 'Safari ' . $m[1];
        if (preg_match('/Safari\/(\d+[\.\d]*)/i', $ua, $m))         return 'Safari';

        return 'Unknown';
    }

    // =========================================================================
    // ЗАПРОСЫ ДЛЯ СТАТИСТИКИ
    // =========================================================================

    /**
     * Собрать базовый запрос с применёнными фильтрами.
     */
    public static function filteredQuery(LogFilter $filter): \yii\db\ActiveQuery
    {
        $query = self::find();

        if ($filter->date_from) {
            $query->andWhere(['>=', 'request_time', $filter->date_from . ' 00:00:00']);
        }
        if ($filter->date_to) {
            $query->andWhere(['<=', 'request_time', $filter->date_to . ' 23:59:59']);
        }
        if ($filter->os) {
            $query->andWhere(['os' => $filter->os]);
        }
        if ($filter->architecture) {
            $query->andWhere(['architecture' => $filter->architecture]);
        }

        return $query;
    }

    /**
     * Количество запросов по дням для первого графика.
     * Возвращает: [ ['date' => 'YYYY-MM-DD', 'cnt' => N], … ]
     */
    public static function countByDay(LogFilter $filter): array
    {
        return self::filteredQuery($filter)
            ->select(['DATE(request_time) AS date', 'COUNT(*) AS cnt'])
            ->groupBy(['DATE(request_time)'])
            ->orderBy(['date' => SORT_ASC])
            ->asArray()
            ->all();
    }

    /**
     * Доля топ-3 браузеров по дням для второго графика.
     * Возвращает: [ ['date' => 'YYYY-MM-DD', 'browser' => '…', 'cnt' => N], … ]
     */
    public static function topBrowsersByDay(LogFilter $filter): array
    {
        // First find the 3 most popular browsers overall
        $topBrowsers = self::filteredQuery($filter)
            ->select(['LEFT(browser, 20) AS b', 'COUNT(*) AS cnt'])
            ->groupBy(['b'])
            ->orderBy(['cnt' => SORT_DESC])
            ->limit(3)
            ->asArray()
            ->all();

        if (empty($topBrowsers)) {
            return [];
        }

        $browserNames = array_column($topBrowsers, 'b');

        return self::filteredQuery($filter)
            ->select([
                'DATE(request_time) AS date',
                'LEFT(browser, 20) AS browser',
                'COUNT(*) AS cnt',
            ])
            ->andWhere(['LEFT(browser, 20)' => $browserNames])
            ->groupBy(['date', 'browser'])
            ->orderBy(['date' => SORT_ASC])
            ->asArray()
            ->all();
    }

    /**
     * Сводка по дням для таблицы на дашборде.
     * Колонки: дата, кол-во запросов, самый популярный URL, самый популярный браузер.
     */
    public static function dailySummary(LogFilter $filter, string $sort = 'date', string $dir = 'desc'): array
    {
        $db = Yii::$app->db;

        // Allowed sort columns mapping to SQL expressions
        $sortMap = [
            'date'             => 'DATE(le.request_time)',
            'requests'         => 'requests',
            'popular_url'      => 'popular_url',
            'popular_browser'  => 'popular_browser',
        ];

        $sortCol = $sortMap[$sort] ?? 'DATE(le.request_time)';
        $sortDir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        // Build WHERE from filter
        $where  = [];
        $params = [];
        if ($filter->date_from) {
            $where[]             = 'le.request_time >= :date_from';
            $params[':date_from'] = $filter->date_from . ' 00:00:00';
        }
        if ($filter->date_to) {
            $where[]           = 'le.request_time <= :date_to';
            $params[':date_to'] = $filter->date_to . ' 23:59:59';
        }
        if ($filter->os) {
            $where[]    = 'le.os = :os';
            $params[':os'] = $filter->os;
        }
        if ($filter->architecture) {
            $where[]           = 'le.architecture = :arch';
            $params[':arch']   = $filter->architecture;
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "
            SELECT
                DATE(le.request_time)              AS date,
                COUNT(*)                           AS requests,
                (
                    SELECT url FROM log_entries li
                    WHERE DATE(li.request_time) = DATE(le.request_time)
                    " . ($filter->os ? "AND li.os = :os" : "") . "
                    " . ($filter->architecture ? "AND li.architecture = :arch" : "") . "
                    GROUP BY url ORDER BY COUNT(*) DESC LIMIT 1
                )                                  AS popular_url,
                (
                    SELECT LEFT(browser, 30) FROM log_entries li2
                    WHERE DATE(li2.request_time) = DATE(le.request_time)
                    " . ($filter->os ? "AND li2.os = :os" : "") . "
                    " . ($filter->architecture ? "AND li2.architecture = :arch" : "") . "
                    GROUP BY browser ORDER BY COUNT(*) DESC LIMIT 1
                )                                  AS popular_browser
            FROM log_entries le
            {$whereSql}
            GROUP BY DATE(le.request_time)
            ORDER BY {$sortCol} {$sortDir}
        ";

        return $db->createCommand($sql, $params)->queryAll();
    }

    /**
     * Список уникальных значений ОС для выпадающего списка фильтра.
     */
    public static function getOsList(): array
    {
        return self::find()
            ->select('os')
            ->distinct()
            ->andWhere(['not', ['os' => null]])
            ->andWhere(['<>', 'os', ''])
            ->orderBy('os')
            ->column();
    }

    /**
     * Список уникальных значений архитектуры для выпадающего списка фильтра.
     */
    public static function getArchList(): array
    {
        return self::find()
            ->select('architecture')
            ->distinct()
            ->andWhere(['not', ['architecture' => null]])
            ->andWhere(['<>', 'architecture', ''])
            ->orderBy('architecture')
            ->column();
    }
}
