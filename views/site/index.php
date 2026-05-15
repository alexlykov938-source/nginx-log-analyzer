<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/* @var $this       yii\web\View */
/* @var $filter     app\models\LogFilter */
/* @var $osList     array */
/* @var $archList   array */
/* @var $summary    array */
/* @var $sort       string */
/* @var $dir        string */
/* @var $chartDates array */
/* @var $chartCounts array */
/* @var $browserDates array */
/* @var $browserChartData array */

$this->title = 'Dashboard';

// Palette for browser chart
$palette = [
    ['border' => '#2563eb', 'bg' => 'rgba(37,99,235,.15)'],
    ['border' => '#f59e0b', 'bg' => 'rgba(245,158,11,.15)'],
    ['border' => '#10b981', 'bg' => 'rgba(16,185,129,.15)'],
];

/**
 * Helper: render sortable column header link.
 */
$sortLink = function(string $col, string $label) use ($sort, $dir, $filter): string {
    $nextDir = ($sort === $col && $dir === 'asc') ? 'desc' : 'asc';
    $url     = Url::current([
        'sort'         => $col,
        'dir'          => $nextDir,
        'LogFilter[date_from]'    => $filter->date_from,
        'LogFilter[date_to]'      => $filter->date_to,
        'LogFilter[os]'           => $filter->os,
        'LogFilter[architecture]' => $filter->architecture,
    ]);
    $icon = 'fas fa-sort';
    $cls  = '';
    if ($sort === $col) {
        $icon = $dir === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
        $cls  = 'active';
    }
    return Html::a(
        $label . ' <i class="' . $icon . ' sort-icon ' . $cls . '"></i>',
        $url,
        ['class' => 'sort-link']
    );
};
?>

<!-- ═══════════════════════════════════════════════════════════════════
     FILTER PANEL
════════════════════════════════════════════════════════════════════════ -->
<div class="card mb-4" id="filter-panel">
    <div class="card-header d-flex align-items-center">
        <i class="fas fa-filter text-primary mr-2"></i> Filters
        <button class="btn btn-sm btn-link ml-auto text-secondary"
                data-toggle="collapse" data-target="#filterBody">
            <i class="fas fa-chevron-down"></i>
        </button>
    </div>
    <div class="collapse show" id="filterBody">
        <div class="card-body">
            <?php $form = ActiveForm::begin([
                'method' => 'get',
                'action' => Url::to(['site/index']),
                'options' => ['id' => 'filter-form'],
            ]); ?>

            <div class="form-row align-items-end">

                <!-- Date From -->
                <div class="form-group col-md-2 mb-2">
                    <?= $form->field($filter, 'date_from')->textInput([
                        'type'  => 'date',
                        'class' => 'form-control',
                        'max'   => date('Y-m-d'),
                    ])->label('Date From') ?>
                </div>

                <!-- Date To -->
                <div class="form-group col-md-2 mb-2">
                    <?= $form->field($filter, 'date_to')->textInput([
                        'type'  => 'date',
                        'class' => 'form-control',
                        'max'   => date('Y-m-d'),
                    ])->label('Date To') ?>
                </div>

                <!-- OS -->
                <div class="form-group col-md-3 mb-2">
                    <?= $form->field($filter, 'os')->dropDownList(
                        array_merge(['' => '— All OS —'], array_combine($osList, $osList)),
                        ['class' => 'custom-select']
                    )->label('Operating System') ?>
                </div>

                <!-- Architecture -->
                <div class="form-group col-md-2 mb-2">
                    <?= $form->field($filter, 'architecture')->dropDownList(
                        array_merge(['' => '— All —'], array_combine($archList, $archList)),
                        ['class' => 'custom-select']
                    )->label('Architecture') ?>
                </div>

                <!-- Buttons -->
                <div class="form-group col-md-3 mb-2 d-flex align-items-end">
                    <?= Html::submitButton('<i class="fas fa-search mr-1"></i> Apply', [
                        'class' => 'btn btn-filter mr-2',
                    ]) ?>
                    <?= Html::a('<i class="fas fa-times mr-1"></i> Reset', ['site/index'], [
                        'class' => 'btn btn-outline-secondary btn-reset',
                    ]) ?>
                </div>

            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
     CHARTS ROW
════════════════════════════════════════════════════════════════════════ -->
<div class="row mb-4">

    <!-- Chart 1: Requests per day -->
    <div class="col-lg-6 mb-3">
        <div class="card h-100">
            <div class="card-header">
                <i class="fas fa-chart-area text-primary mr-1"></i>
                Requests per Day
            </div>
            <div class="card-body">
                <?php if (empty($chartDates)): ?>
                    <div class="empty-state">
                        <i class="fas fa-database d-block"></i>
                        No data for selected period
                    </div>
                <?php else: ?>
                    <div class="chart-wrapper">
                        <canvas id="requestsChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Chart 2: Top-3 browsers share per day -->
    <div class="col-lg-6 mb-3">
        <div class="card h-100">
            <div class="card-header">
                <i class="fas fa-globe text-warning mr-1"></i>
                Top-3 Browsers Share (%)
            </div>
            <div class="card-body">
                <?php if (empty($browserDates)): ?>
                    <div class="empty-state">
                        <i class="fas fa-database d-block"></i>
                        No data for selected period
                    </div>
                <?php else: ?>
                    <div class="chart-wrapper">
                        <canvas id="browserChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- ═══════════════════════════════════════════════════════════════════
     DAILY SUMMARY TABLE
════════════════════════════════════════════════════════════════════════ -->
<div class="card mb-5">
    <div class="card-header d-flex align-items-center">
        <i class="fas fa-table text-success mr-1"></i>
        Daily Summary
        <span class="badge badge-secondary ml-2"><?= count($summary) ?> days</span>
    </div>
    <div class="card-body p-0">

        <?php if (empty($summary)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox d-block"></i>
                No records match the current filters.<br>
                <small>Try a different date range or reset the filters.</small>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead>
                        <tr>
                            <th><?= $sortLink('date', 'Date') ?></th>
                            <th><?= $sortLink('requests', 'Requests') ?></th>
                            <th><?= $sortLink('popular_url', 'Top URL') ?></th>
                            <th><?= $sortLink('popular_browser', 'Top Browser') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($summary as $row): ?>
                            <tr>
                                <td><strong><?= Html::encode($row['date']) ?></strong></td>
                                <td>
                                    <span class="badge badge-primary badge-pill px-3 py-1">
                                        <?= number_format((int) $row['requests']) ?>
                                    </span>
                                </td>
                                <td class="url-cell" title="<?= Html::encode($row['popular_url'] ?? '—') ?>">
                                    <?= Html::encode($row['popular_url'] ?? '—') ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['popular_browser'])): ?>
                                        <span class="badge-os"><?= Html::encode($row['popular_browser']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
     JAVASCRIPT — Chart.js
════════════════════════════════════════════════════════════════════════ -->
<?php if (!empty($chartDates)): ?>
<script>
(function () {
    // ── Chart 1: Requests per day ───────────────────────────────────────────
    var ctx1 = document.getElementById('requestsChart').getContext('2d');
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: <?= json_encode($chartDates) ?>,
            datasets: [{
                label: 'Requests',
                data: <?= json_encode($chartCounts) ?>,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37,99,235,.12)',
                borderWidth: 2,
                pointRadius: <?= count($chartDates) > 60 ? 0 : 3 ?>,
                fill: true,
                tension: 0.3,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return ' ' + ctx.parsed.y.toLocaleString() + ' requests';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        maxTicksLimit: 10,
                        font: { size: 11 }
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: { font: { size: 11 } }
                }
            }
        }
    });
})();
</script>
<?php endif; ?>

<?php if (!empty($browserDates)): ?>
<script>
(function () {
    var palette = <?= json_encode($palette) ?>;
    var datasets = <?= json_encode($browserChartData) ?>.map(function(s, i) {
        var c = palette[i] || { border: '#6b7280', bg: 'rgba(107,114,128,.15)' };
        return {
            label: s.label,
            data: s.data,
            borderColor: c.border,
            backgroundColor: c.bg,
            borderWidth: 2,
            pointRadius: <?= count($browserDates) > 60 ? 0 : 3 ?>,
            fill: true,
            tension: 0.3,
        };
    });

    var ctx2 = document.getElementById('browserChart').getContext('2d');
    new Chart(ctx2, {
        type: 'line',
        data: {
            labels: <?= json_encode($browserDates) ?>,
            datasets: datasets,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: { font: { size: 11 }, boxWidth: 14 }
                },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return ' ' + ctx.dataset.label + ': ' + ctx.parsed.y + '%';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { maxTicksLimit: 10, font: { size: 11 } }
                },
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        font: { size: 11 },
                        callback: function(v) { return v + '%'; }
                    }
                }
            }
        }
    });
})();
</script>
<?php endif; ?>
