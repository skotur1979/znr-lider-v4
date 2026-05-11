<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <title>ZNR LIDER - Izvještaj o stanju sustava</title>

    <style>
        @page { margin: 14px; }

        * { font-family: "DejaVu Sans", sans-serif; box-sizing: border-box; }

        body {
            margin: 0;
            color: #0f172a;
            font-size: 10.5px;
            line-height: 1.35;
        }

        .container {
            width: 100%;
            background: #ffffff;
            border: 1px solid #dbe2ea;
            border-radius: 14px;
            overflow: hidden;
        }

        .header {
            background: #111827;
            color: #ffffff;
            padding: 18px 24px 16px;
        }

        .brand {
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 1.3px;
            color: #f59e0b;
            text-transform: uppercase;
        }

        .title {
            margin-top: 6px;
            font-size: 23px;
            line-height: 28px;
            font-weight: 900;
        }

        .meta {
            margin-top: 5px;
            font-size: 11px;
            color: #cbd5e1;
        }

        .content { padding: 16px 24px 18px; }

        .intro {
            font-size: 11.5px;
            line-height: 17px;
            color: #334155;
            margin-bottom: 9px;
        }

        .smart-box {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 12px;
            padding: 10px 12px;
            margin-bottom: 10px;
        }

        .label {
            font-size: 10px;
            font-weight: 900;
            color: #92400e;
            text-transform: uppercase;
            letter-spacing: .7px;
            margin-bottom: 5px;
        }

        .status-box {
            border-radius: 13px;
            padding: 12px 14px;
            margin: 10px 0 11px;
            text-align: center;
            page-break-inside: avoid;
        }

        .status-critical { background: #dc2626; color: #ffffff; border: 2px solid #991b1b; }
        .status-warning { background: #f97316; color: #ffffff; border: 2px solid #c2410c; }
        .status-ok { background: #16a34a; color: #ffffff; border: 2px solid #15803d; }

        .status-small {
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .status-main {
            font-size: 22px;
            font-weight: 900;
            margin-top: 5px;
        }

        .status-text {
            margin-top: 5px;
            font-size: 11.5px;
            font-weight: 700;
        }

        .stats {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px;
            margin: 0 -8px 8px -8px;
        }

        .stat {
            width: 33.33%;
            border-radius: 12px;
            padding: 9px 10px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            page-break-inside: avoid;
        }

        .stat-red { background: #fff1f2; border-color: #fecdd3; }
        .stat-yellow { background: #fffbeb; border-color: #fde68a; }
        .stat-blue { background: #eff6ff; border-color: #bfdbfe; }
        .stat-green { background: #ecfdf5; border-color: #bbf7d0; }
        .stat-purple { background: #f5f3ff; border-color: #ddd6fe; }
        .stat-slate { background: #f8fafc; border-color: #cbd5e1; }

        .stat-label {
            font-size: 9.5px;
            font-weight: 900;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .stat-value {
            font-size: 22px;
            line-height: 24px;
            font-weight: 900;
        }

        .stat-note {
            margin-top: 3px;
            font-size: 9.5px;
            color: #64748b;
            font-weight: 700;
        }

        .red { color: #dc2626; }
        .yellow { color: #d97706; }
        .blue { color: #2563eb; }
        .green { color: #16a34a; }
        .purple { color: #7c3aed; }
        .slate { color: #334155; }

        .section-title {
            font-size: 15px;
            font-weight: 900;
            margin: 13px 0 7px;
            page-break-after: avoid;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            page-break-inside: auto;
        }

        table.data th {
            background: #f1f5f9;
            padding: 7px 9px;
            font-size: 10px;
            font-weight: 900;
            color: #334155;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }

        table.data td {
            padding: 6px 9px;
            font-size: 10.5px;
            color: #0f172a;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        table.data tr { page-break-inside: avoid; }

        table.data tr:nth-child(even) td { background: #f8fafc; }

        .center { text-align: center; }

        .badge {
            display: inline-block;
            min-width: 34px;
            border-radius: 999px;
            padding: 3px 8px;
            font-weight: 900;
            text-align: center;
        }

        .badge-red { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        .badge-yellow { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
        .badge-green { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-blue { background: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe; }

        .actions-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 11px;
            padding: 9px 12px;
            page-break-inside: avoid;
        }

        .actions { margin: 0; padding-left: 16px; }

        .actions li {
            margin-bottom: 4px;
            font-size: 10.5px;
            line-height: 16px;
        }

        .conclusion {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-left: 5px solid #2563eb;
            border-radius: 11px;
            padding: 10px 12px;
            color: #1e3a8a;
            font-size: 10.5px;
            line-height: 16px;
            page-break-inside: avoid;
        }
        .page-break-before {
    page-break-before: always;
}

.avoid-break {
    page-break-inside: avoid;
    break-inside: avoid;
}

table.data {
    page-break-inside: auto;
}

table.data tr {
    page-break-inside: avoid;
    break-inside: avoid;
}

table.data thead {
    display: table-header-group;
}

table.data tfoot {
    display: table-row-group;
}

        .footer {
            margin-top: 12px;
            padding-top: 9px;
            border-top: 1px solid #e2e8f0;
            font-size: 9.5px;
            line-height: 14px;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>

<body>
<?php
    $statusClass = $totalExpired > 0
        ? 'status-critical'
        : ($totalSoon > 0 ? 'status-warning' : 'status-ok');

    $statusIcon = $totalExpired > 0
        ? '!'
        : ($totalSoon > 0 ? '!' : 'OK');

    $statusMessage = $totalExpired > 0
        ? 'Sustav zahtijeva hitnu reakciju zbog isteklih stavki.'
        : ($totalSoon > 0
            ? 'Sustav zahtijeva planiranje aktivnosti koje uskoro istječu.'
            : 'Trenutno nema kritičnih isteklih stavki.');

    $daysWithoutLta = $daysWithoutLta ?? 0;
    $ltaRecordDays = $ltaRecordDays ?? 0;
    $ltaCount = $ltaCount ?? 0;
    $mtaCount = $mtaCount ?? 0;
    $faaCount = $faaCount ?? 0;
    $openObservations = $openObservations ?? 0;
    $openWorkTasks = $openWorkTasks ?? 0;
    $closedWorkTasks = $closedWorkTasks ?? 0;
?>

<div class="container">
    <div class="header">
        <div class="brand">ZNR LIDER</div>
        <div class="title">Izvještaj o stanju sustava</div>
        <div class="meta">Datum izvještaja: <?php echo e($reportDate); ?> · Automatski PDF izvještaj</div>
    </div>

    <div class="content">
        <div class="intro">
            Profesionalni pregled stanja ZNR sustava, rokova, operativnih aktivnosti i ključnih pokazatelja sigurnosti.
        </div>

        <div class="smart-box">
            <div class="label">Pametni sažetak</div>
            Sustav je trenutno u statusu <strong><?php echo e($systemStatus); ?></strong>.
            Evidentirano je <strong><?php echo e($totalExpired); ?></strong> isteklih stavki i
            <strong><?php echo e($totalSoon); ?></strong> stavki koje uskoro istječu.
            Trenutno je <strong><?php echo e($daysWithoutLta); ?></strong> dana bez LTA.
        </div>

        <div class="status-box <?php echo e($statusClass); ?>">
            <div class="status-small">Status sustava</div>
            <div class="status-main"><?php echo e($statusIcon); ?> <?php echo e(mb_strtoupper($systemStatus)); ?></div>
            <div class="status-text"><?php echo e($statusMessage); ?></div>
        </div>

        <table class="stats">
            <tr>
                <td class="stat stat-red">
                    <div class="stat-label">Isteklo</div>
                    <div class="stat-value red"><?php echo e($totalExpired); ?></div>
                    <div class="stat-note">hitno postupanje</div>
                </td>
                <td class="stat stat-yellow">
                    <div class="stat-label">U 30 dana</div>
                    <div class="stat-value yellow"><?php echo e($totalSoon); ?></div>
                    <div class="stat-note">planirati</div>
                </td>
                <td class="stat stat-green">
                    <div class="stat-label">Bez LTA</div>
                    <div class="stat-value green"><?php echo e($daysWithoutLta); ?></div>
                    <div class="stat-note">dana</div>
                </td>
            </tr>
        </table>

        <table class="stats">
            <tr>
                <td class="stat stat-purple">
                    <div class="stat-label">Rekord bez LTA</div>
                    <div class="stat-value purple"><?php echo e($ltaRecordDays); ?></div>
                    <div class="stat-note">dana</div>
                </td>
                <td class="stat stat-blue">
                    <div class="stat-label">Kategorije</div>
                    <div class="stat-value blue"><?php echo e(count($rows)); ?></div>
                    <div class="stat-note">praćenih područja</div>
                </td>
                <td class="stat stat-slate">
                    <div class="stat-label">Otvorene aktivnosti</div>
                    <div class="stat-value slate"><?php echo e($openObservations + $openWorkTasks); ?></div>
                    <div class="stat-note">zapažanja i zadaci</div>
                </td>
            </tr>
        </table>

        <div class="section-title">Sigurnosni pokazatelji</div>

        <table class="data">
            <thead>
            <tr>
                <th>Pokazatelj</th>
                <th class="center" style="width:90px;">Vrijednost</th>
                <th>Napomena</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><strong>LTA ozljede</strong></td>
                <td class="center"><span class="badge badge-red"><?php echo e($ltaCount); ?></span></td>
                <td>Ozljede na radu s izgubljenim radnim danima.</td>
            </tr>
            <tr>
                <td><strong>MTA događaji</strong></td>
                <td class="center"><span class="badge badge-yellow"><?php echo e($mtaCount); ?></span></td>
                <td>Pružanje prve pomoći / medicinska obrada izvan tvrtke.</td>
            </tr>
            <tr>
                <td><strong>FAA događaji</strong></td>
                <td class="center"><span class="badge badge-blue"><?php echo e($faaCount); ?></span></td>
                <td>Pružanje prve pomoći unutar tvrtke.</td>
            </tr>
            <tr>
                <td><strong>Otvorena zapažanja</strong></td>
                <td class="center"><span class="badge badge-yellow"><?php echo e($openObservations); ?></span></td>
                <td>Zapažanja u statusu nije započeto ili u tijeku.</td>
            </tr>
            <tr>
                <td><strong>Otvoreni radni zadaci</strong></td>
                <td class="center"><span class="badge badge-yellow"><?php echo e($openWorkTasks); ?></span></td>
                <td>Dodijeljene aktivnosti koje još nisu zatvorene.</td>
            </tr>
            <tr>
                <td><strong>Zatvoreni radni zadaci</strong></td>
                <td class="center"><span class="badge badge-green"><?php echo e($closedWorkTasks); ?></span></td>
                <td>Završene aktivnosti i provedene mjere.</td>
            </tr>
            </tbody>
        </table>

        <div class="page-break-before avoid-break">
    <div class="section-title">Rokovi i valjanosti</div>

    <table class="data">
            <thead>
            <tr>
                <th>Stavka</th>
                <th class="center" style="width:90px;">Isteklo</th>
                <th class="center" style="width:90px;">U 30 dana</th>
            </tr>
            </thead>
            <tbody>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><strong><?php echo e($row['label']); ?></strong></td>
                    <td class="center"><span class="badge badge-red"><?php echo e($row['expired']); ?></span></td>
                    <td class="center"><span class="badge badge-yellow"><?php echo e($row['soon']); ?></span></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </tbody>
        </table>

        <div class="section-title">Preporučene akcije</div>

        <div class="actions-box">
            <ol class="actions">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $actions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $action): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($action); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </ol>
        </div>

        <div class="section-title">Zaključak</div>

        <div class="conclusion">
            <?php echo e($summary); ?>

            <br>
            Preporučuje se prioritetno rješavanje isteklih stavki, zatim planiranje aktivnosti koje istječu unutar 30 dana te redovito zatvaranje otvorenih zapažanja i radnih zadataka.
        </div>

        <div class="footer">
            Ovaj izvještaj je automatski generiran iz sustava <strong>ZNR LIDER</strong>.<br>
            Ako podaci odstupaju od očekivanih, provjerite evidencije i statuse unutar aplikacije.
        </div>
    </div>
</div>
</body>
</html><?php /**PATH C:\Users\Korisnik\znr-lider-v4\resources\views/pdf/znr-general-report.blade.php ENDPATH**/ ?>