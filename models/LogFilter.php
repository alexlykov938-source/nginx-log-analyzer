<?php

namespace app\models;

use yii\base\Model;

/**
 * Модель формы для фильтрации данных лога.
 */
class LogFilter extends Model
{
    /** @var string|null  Дата начала периода (YYYY-MM-DD) */
    public ?string $date_from = null;

    /** @var string|null  Дата конца периода (YYYY-MM-DD) */
    public ?string $date_to = null;

    /** @var string|null  Фильтр по операционной системе */
    public ?string $os = null;

    /** @var string|null  Фильтр по архитектуре: x86 | x64 | Unknown */
    public ?string $architecture = null;

    // -------------------------------------------------------------------------
    public function rules(): array
    {
        return [
            [['date_from', 'date_to', 'os', 'architecture'], 'safe'],
            [['date_from', 'date_to'], 'date', 'format' => 'php:Y-m-d'],
            ['date_to', 'validateDateRange'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'date_from'    => 'Date From',
            'date_to'      => 'Date To',
            'os'           => 'Operating System',
            'architecture' => 'Architecture',
        ];
    }

    /**
     * Проверяет, что date_from <= date_to и диапазон не превышает 1 год.
     */
    public function validateDateRange(): void
    {
        if (!$this->date_from || !$this->date_to) {
            return;
        }

        $from = strtotime($this->date_from);
        $to   = strtotime($this->date_to);

        if ($from > $to) {
            $this->addError('date_to', '"Date To" must be greater than or equal to "Date From".');
            return;
        }

        if (($to - $from) > 365 * 24 * 3600) {
            $this->addError('date_to', 'Date range must not exceed 1 year.');
        }
    }

    /**
     * Если фильтр пустой — подставить последние 30 дней по умолчанию.
     */
    public function loadDefaults(): void
    {
        if (!$this->date_from && !$this->date_to) {
            $this->date_to   = date('Y-m-d');
            $this->date_from = date('Y-m-d', strtotime('-30 days'));
        }
    }
}
