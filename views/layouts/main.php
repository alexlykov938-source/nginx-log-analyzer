<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= \yii\helpers\Html::encode($this->title) ?> — Nginx Log Analyzer</title>

    <!-- Bootstrap 4 -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        :root {
            --brand: #2563eb;
            --brand-dark: #1d4ed8;
        }

        body {
            background: #f1f5f9;
            font-family: 'Segoe UI', system-ui, sans-serif;
            font-size: .9rem;
        }

        /* ── Navbar ── */
        .navbar {
            background: var(--brand);
            box-shadow: 0 2px 8px rgba(0,0,0,.2);
        }
        .navbar-brand { font-weight: 700; font-size: 1.15rem; letter-spacing: .5px; }

        /* ── Cards ── */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 1px 6px rgba(0,0,0,.08);
        }
        .card-header {
            border-radius: 10px 10px 0 0 !important;
            background: #fff;
            border-bottom: 1px solid #e8edf2;
            font-weight: 600;
            font-size: .95rem;
            color: #374151;
        }

        /* ── Filter panel ── */
        #filter-panel { background: #fff; }
        .form-control, .custom-select {
            border-radius: 6px;
            font-size: .87rem;
        }
        .btn-filter {
            background: var(--brand);
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: .42rem 1.1rem;
        }
        .btn-filter:hover { background: var(--brand-dark); color: #fff; }
        .btn-reset {
            border-radius: 6px;
            padding: .42rem 1.1rem;
        }

        /* ── Table ── */
        .table thead th {
            background: #f8fafc;
            border-top: none;
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .5px;
            white-space: nowrap;
        }
        .table td { vertical-align: middle; }
        .sort-link {
            color: inherit;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .sort-link:hover { color: var(--brand); }
        .sort-icon { font-size: .7rem; color: #9ca3af; }
        .sort-icon.active { color: var(--brand); }

        /* ── URL cell truncate ── */
        .url-cell {
            max-width: 260px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* ── Stat badges ── */
        .badge-os {
            background: #dbeafe;
            color: #1d4ed8;
            font-size: .75rem;
            font-weight: 500;
            padding: .25rem .55rem;
            border-radius: 999px;
        }

        /* ── Chart container ── */
        .chart-wrapper { position: relative; height: 260px; }

        /* ── Empty state ── */
        .empty-state { padding: 3rem; text-align: center; color: #94a3b8; }
        .empty-state i { font-size: 2.5rem; margin-bottom: .75rem; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark mb-4">
    <span class="navbar-brand">
        <i class="fas fa-chart-bar mr-2"></i>Nginx Log Analyzer
    </span>
    <span class="text-white-50 small">Visualise &amp; explore your access logs</span>
</nav>

<div class="container-fluid px-4">
    <?= $content ?>
</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
</body>
</html>
