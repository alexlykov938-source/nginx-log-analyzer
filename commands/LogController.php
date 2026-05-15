<?php

namespace app\commands;

use app\models\LogEntry;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Импортирует nginx access-лог в базу данных.
 *
 * Использование:
 *   php yii log/import /path/to/access.log
 *   php yii log/import /path/to/access.log --truncate=1
 *   php yii log/import /path/to/access.log --batch-size=500
 */
class LogController extends Controller
{
    /**
     * @var bool  Очистить таблицу перед импортом.
     */
    public bool $truncate = false;

    /**
     * @var int  Количество записей в одном bulk-INSERT запросе.
     */
    public int $batchSize = 1000;

    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), ['truncate', 'batchSize']);
    }

    public function optionAliases(): array
    {
        return array_merge(parent::optionAliases(), [
            't' => 'truncate',
            'b' => 'batchSize',
        ]);
    }

    // =========================================================================
    // ACTIONS
    // =========================================================================

    /**
     * Разобрать и импортировать один nginx access-лог файл.
     *
     * @param string $filePath  Полный путь к лог-файлу.
     * @return int  Код завершения.
     */
    public function actionImport(string $filePath): int
    {
        // --- Проверка файла -------------------------------------------------------
        if (!file_exists($filePath)) {
            $this->stderr("Error: file not found — {$filePath}\n", Console::FG_RED);
            return ExitCode::NOINPUT;
        }

        $this->stdout("Source: {$filePath}\n", Console::FG_CYAN);

        // --- Очистка таблицы (опционально) ---------------------------------------
        if ($this->truncate) {
            $this->stdout("Truncating log_entries table … ", Console::FG_YELLOW);
            Yii::$app->db->createCommand()->truncateTable('{{%log_entries}}')->execute();
            $this->stdout("done.\n", Console::FG_GREEN);
        }

        // --- Обработка файла построчно -------------------------------------------
        $fh = fopen($filePath, 'rb');
        if (!$fh) {
            $this->stderr("Error: cannot open file.\n", Console::FG_RED);
            return ExitCode::IOERR;
        }

        $total    = 0;
        $imported = 0;
        $skipped  = 0;
        $batch    = [];

        $this->stdout("Parsing …\n");

        while (($line = fgets($fh)) !== false) {
            $total++;
            $entry = LogEntry::fromLogLine($line);

            if ($entry === null) {
                $skipped++;
                continue;
            }

            $batch[] = [
                $entry->ip,
                $entry->request_time,
                $entry->url,
                $entry->method,
                $entry->status_code,
                $entry->user_agent,
                $entry->os,
                $entry->architecture,
                $entry->browser,
                $entry->created_at,
            ];

            if (count($batch) >= $this->batchSize) {
                $imported += $this->flushBatch($batch);
                $batch = [];
                $this->stdout("  … {$imported} rows inserted\r");
            }
        }

        // Flush remainder
        if ($batch) {
            $imported += $this->flushBatch($batch);
        }

        fclose($fh);

        $this->stdout("\n");
        $this->stdout("Done!\n", Console::FG_GREEN);
        $this->stdout("  Total lines : {$total}\n");
        $this->stdout("  Imported    : {$imported}\n", Console::FG_GREEN);
        $this->stdout("  Skipped     : {$skipped}\n", Console::FG_YELLOW);

        return ExitCode::OK;
    }

    /**
     * Показать статистику по импортированным данным (кол-во строк, диапазон дат).
     */
    public function actionStats(): int
    {
        $db    = Yii::$app->db;
        $count = (int) $db->createCommand('SELECT COUNT(*) FROM {{%log_entries}}')->queryScalar();
        $min   = $db->createCommand('SELECT MIN(request_time) FROM {{%log_entries}}')->queryScalar();
        $max   = $db->createCommand('SELECT MAX(request_time) FROM {{%log_entries}}')->queryScalar();

        $this->stdout("Log Entries Statistics\n", Console::FG_CYAN | Console::BOLD);
        $this->stdout("  Total rows : {$count}\n");
        $this->stdout("  Earliest   : {$min}\n");
        $this->stdout("  Latest     : {$max}\n");

        return ExitCode::OK;
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Записать накопленный батч в БД через один INSERT-запрос.
     * Возвращает количество реально вставленных строк.
     */
    private function flushBatch(array $batch): int
    {
        if (empty($batch)) {
            return 0;
        }

        $columns = [
            'ip', 'request_time', 'url', 'method', 'status_code',
            'user_agent', 'os', 'architecture', 'browser', 'created_at',
        ];

        try {
            Yii::$app->db->createCommand()
                ->batchInsert('{{%log_entries}}', $columns, $batch)
                ->execute();
            return count($batch);
        } catch (\Throwable $e) {
            $this->stderr("Batch insert error: " . $e->getMessage() . "\n", Console::FG_RED);
            return 0;
        }
    }
}
