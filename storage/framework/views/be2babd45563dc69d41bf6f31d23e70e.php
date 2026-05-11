<?php
    use Illuminate\Support\Carbon;

    $items = collect($getRecord()->items ?? [])->sort(function ($a, $b) {
        $aHasEnd = ! blank($a->end_date);
        $bHasEnd = ! blank($b->end_date);

        if ($aHasEnd && ! $bHasEnd) {
            return -1;
        }

        if (! $aHasEnd && $bHasEnd) {
            return 1;
        }

        if (! $aHasEnd && ! $bHasEnd) {
            $aIssue = $a->issue_date ? Carbon::parse($a->issue_date)->timestamp : 0;
            $bIssue = $b->issue_date ? Carbon::parse($b->issue_date)->timestamp : 0;

            return $bIssue <=> $aIssue;
        }

        $aEnd = Carbon::parse($a->end_date)->timestamp;
        $bEnd = Carbon::parse($b->end_date)->timestamp;

        if ($aEnd !== $bEnd) {
            return $bEnd <=> $aEnd;
        }

        $aIssue = $a->issue_date ? Carbon::parse($a->issue_date)->timestamp : 0;
        $bIssue = $b->issue_date ? Carbon::parse($b->issue_date)->timestamp : 0;

        if ($aIssue !== $bIssue) {
            return $bIssue <=> $aIssue;
        }

        $aDuration = (int) ($a->duration_months ?? 0);
        $bDuration = (int) ($b->duration_months ?? 0);

        return $bDuration <=> $aDuration;
    })->values();
?>

<div style="display:flex; flex-direction:column; gap:6px;">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div style="min-height:30px; display:flex; align-items:center; white-space:nowrap;">
            <?php echo e($item->issue_date ? Carbon::parse($item->issue_date)->format('d.m.Y.') : '—'); ?>

        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div style="min-height:30px; display:flex; align-items:center; color:#9ca3af;">
            —
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div><?php /**PATH C:\Users\Korisnik\znr-lider-v4\resources\views/filament/columns/ozo-izdano.blade.php ENDPATH**/ ?>