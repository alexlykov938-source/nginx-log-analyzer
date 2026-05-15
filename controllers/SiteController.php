<?php

namespace app\controllers;

use app\models\LogEntry;
use app\models\LogFilter;
use Yii;
use yii\web\Controller;

class SiteController extends Controller
{
    public function actionIndex()
    {
        $filter = new LogFilter();

        // Загружаем параметры фильтра из GET-запроса
        $filter->load(Yii::$app->request->get());
        if (!$filter->validate()) {
            $filter->clearErrors();      // сбрасываем невалидные значения, оставляем дефолты
        }
        $filter->loadDefaults();

        // Параметры сортировки таблицы
        $sort    = Yii::$app->request->get('sort', 'date');
        $dir     = Yii::$app->request->get('dir', 'desc');
        $allowed = ['date', 'requests', 'popular_url', 'popular_browser'];
        if (!in_array($sort, $allowed, true)) {
            $sort = 'date';
        }

        // Данные для графиков и таблицы
        $byDay         = LogEntry::countByDay($filter);
        $browsersByDay = LogEntry::topBrowsersByDay($filter);
        $summary       = LogEntry::dailySummary($filter, $sort, $dir);

        // Значения для выпадающих списков фильтра
        $osList   = LogEntry::getOsList();
        $archList = LogEntry::getArchList();

        // Данные для первого графика (запросы по дням)
        $chartDates         = array_column($byDay, 'date');
        $chartCounts        = array_map('intval', array_column($byDay, 'cnt'));

        // Данные для второго графика (доля браузеров по дням)
        $allDates    = array_unique(array_column($browsersByDay, 'date'));
        sort($allDates);

        // Группируем по браузеру
        $browserData = [];
        foreach ($browsersByDay as $row) {
            $browserData[$row['browser']][$row['date']] = (int) $row['cnt'];
        }

        // Суммарное кол-во запросов за каждую дату (для расчёта процента)
        $totalsPerDate = [];
        foreach ($byDay as $r) {
            $totalsPerDate[$r['date']] = (int) $r['cnt'];
        }

        $browserChartData = [];
        foreach ($browserData as $bName => $dateMap) {
            $seriesData = [];
            foreach ($allDates as $d) {
                $cnt   = $dateMap[$d] ?? 0;
                $total = $totalsPerDate[$d] ?? 1;
                $seriesData[] = round($cnt / $total * 100, 2);
            }
            $browserChartData[] = [
                'label' => $bName,
                'data'  => $seriesData,
            ];
        }

        return $this->render('index', [
            'filter'          => $filter,
            'osList'          => $osList,
            'archList'        => $archList,
            'summary'         => $summary,
            'sort'            => $sort,
            'dir'             => $dir,
            'chartDates'      => $chartDates,
            'chartCounts'     => $chartCounts,
            'browserDates'    => $allDates,
            'browserChartData'=> $browserChartData,
        ]);
    }

    public function actionError()
    {
        $exception = Yii::$app->errorHandler->exception;
        if ($exception !== null) {
            return $this->render('error', ['exception' => $exception]);
        }
        return $this->goHome();
    }
}
