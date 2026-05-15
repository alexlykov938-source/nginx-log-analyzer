<?php

use yii\db\Migration;

/**
 * Создаёт таблицу {{%log_entries}} для хранения разобранных записей nginx access-лога.
 */
class m190321_000001_create_log_entries_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%log_entries}}', [
            'id'           => $this->primaryKey()->unsigned(),
            'ip'           => $this->string(45)->notNull()->comment('IP-адрес клиента'),
            'request_time' => $this->dateTime()->notNull()->comment('Дата и время запроса'),
            'url'          => $this->string(2048)->notNull()->comment('URL запроса'),
            'method'       => $this->string(10)->notNull()->defaultValue('GET')->comment('HTTP-метод'),
            'status_code'  => $this->smallInteger()->unsigned()->notNull()->defaultValue(200)->comment('HTTP-код ответа'),
            'user_agent'   => $this->text()->null()->comment('Полная строка User-Agent'),
            'os'           => $this->string(100)->null()->comment('Операционная система (Windows, Linux, Mac OS, Android, iOS …)'),
            'architecture' => $this->string(10)->null()->comment('Архитектура процессора: x86 или x64'),
            'browser'      => $this->string(100)->null()->comment('Название и версия браузера'),
            'created_at'   => $this->integer()->unsigned()->notNull()->comment('Unix-время момента импорта'),
        ]);

        // Индексы для часто используемых в фильтрах и сортировках колонок
        $this->createIndex('idx_log_request_time', '{{%log_entries}}', 'request_time');
        $this->createIndex('idx_log_os',           '{{%log_entries}}', 'os');
        $this->createIndex('idx_log_architecture', '{{%log_entries}}', 'architecture');
        $this->createIndex('idx_log_browser',      '{{%log_entries}}', 'browser');
        $this->createIndex('idx_log_ip',           '{{%log_entries}}', 'ip');
        $this->createIndex('idx_log_status_code',  '{{%log_entries}}', 'status_code');
    }

    public function safeDown()
    {
        $this->dropTable('{{%log_entries}}');
    }
}
