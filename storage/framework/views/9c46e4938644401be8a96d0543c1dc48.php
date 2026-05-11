<?php if (isset($component)) { $__componentOriginalb525200bfa976483b4eaa0b7685c6e24 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb525200bfa976483b4eaa0b7685c6e24 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-widgets::components.widget','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-widgets::widget'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <div id="today-mini-block" class="today-line-wrap">
        <div class="today-line-left">
            <div class="today-line-icon">📅</div>

            <div class="today-line-title-wrap">
                <span class="today-line-title"><?php echo e($dayLabel); ?></span>
                <span class="today-line-date">(<?php echo e($dayDateLabel); ?>)</span>
            </div>
        </div>

        <div class="today-line-center">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasAnything): ?>
                <a href="<?php echo e($tasksUrl); ?>" class="today-line-link">
                    <span class="today-line-label">zadaci:</span>
                    <span class="today-line-number"><?php echo e($taskCount); ?></span>
                </a>

                <span class="today-line-sep">•</span>

                <a href="<?php echo e($calendarUrl); ?>" class="today-line-link">
                    <span class="today-line-label">rokovi danas:</span>
                    <span class="today-line-number"><?php echo e($deadlineCount); ?></span>
                </a>
            <?php else: ?>
                <span class="today-line-empty">nema zadataka ni rokova za <?php echo e($dayDateLabel); ?></span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($deadlines)): ?>
            <div class="today-line-right">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $deadlines; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $count): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <span class="today-line-pill"><?php echo e($label); ?>: <?php echo e($count); ?></span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    <style>
        .today-line-wrap{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:14px;
            padding:10px 14px;
            border-radius:14px;
            border:1px solid #dbe3f0;
            background:linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            box-shadow:0 8px 18px rgba(15, 23, 42, 0.06);
            margin-bottom:1px;
            scroll-margin-top:90px;
        }

        .dark .today-line-wrap{
            border:1px solid rgba(148,163,184,.14);
            background:linear-gradient(180deg, rgba(8,18,40,.94), rgba(5,12,28,.94));
            box-shadow:0 8px 18px rgba(0,0,0,.12);
        }

        .today-line-left{
            display:flex;
            align-items:center;
            gap:10px;
            flex-shrink:0;
        }

        .today-line-icon{
            width:32px;
            height:32px;
            border-radius:10px;
            display:flex;
            align-items:center;
            justify-content:center;
            background:rgba(59,130,246,.08);
            border:1px solid rgba(59,130,246,.16);
            font-size:14px;
            flex-shrink:0;
        }

        .dark .today-line-icon{
            background:rgba(59,130,246,.10);
            border:1px solid rgba(59,130,246,.18);
        }

        .today-line-title-wrap{
            display:flex;
            align-items:center;
            gap:6px;
            flex-wrap:wrap;
        }

        .today-line-title{
            font-size:.92rem;
            font-weight:900;
            color:#0f172a;
            line-height:1;
        }

        .dark .today-line-title{
            color:#ffffff;
        }

        .today-line-date{
            font-size:.90rem;
            font-weight:900;
            color:#2563eb;
            line-height:1;
        }

        .dark .today-line-date{
            color:#60a5fa;
        }

        .today-line-center{
            display:flex;
            align-items:center;
            justify-content:center;
            gap:10px;
            flex:1;
            min-width:0;
            flex-wrap:wrap;
        }

        .today-line-link{
            display:inline-flex;
            align-items:center;
            gap:5px;
            text-decoration:none;
            color:#1e293b;
            transition:opacity .15s ease, transform .15s ease;
            white-space:nowrap;
        }

        .today-line-link:hover{
            opacity:.92;
            transform:translateY(-1px);
            text-decoration:none;
        }

        .today-line-link:focus{
            outline:none;
            text-decoration:none;
        }

        .dark .today-line-link{
            color:#e2e8f0;
        }

        .today-line-number{
            color:#0f172a;
            font-size:.96rem;
            font-weight:900;
            line-height:1;
        }

        .dark .today-line-number{
            color:#ffffff;
        }

        .today-line-label{
            color:#475569;
            font-size:.82rem;
            font-weight:800;
            line-height:1;
        }

        .dark .today-line-label{
            color:#cbd5e1;
        }

        .today-line-sep{
            color:#94a3b8;
            font-weight:700;
            line-height:1;
        }

        .dark .today-line-sep{
            color:#64748b;
        }

        .today-line-empty{
            color:#64748b;
            font-size:.84rem;
            font-weight:700;
        }

        .dark .today-line-empty{
            color:#94a3b8;
        }

        .today-line-right{
            display:flex;
            align-items:center;
            gap:6px;
            flex-wrap:wrap;
            justify-content:flex-end;
            max-width:46%;
        }

        .today-line-pill{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            padding:5px 8px;
            border-radius:999px;
            background:rgba(245,158,11,.10);
            border:1px solid rgba(245,158,11,.18);
            color:#b45309;
            font-size:.70rem;
            font-weight:800;
            line-height:1;
            white-space:nowrap;
        }

        .dark .today-line-pill{
            background:rgba(245,158,11,.10);
            border:1px solid rgba(245,158,11,.16);
            color:#fbbf24;
        }

        @media (max-width: 1200px){
            .today-line-wrap{
                flex-wrap:wrap;
                align-items:flex-start;
            }

            .today-line-center{
                justify-content:flex-start;
                width:100%;
            }

            .today-line-right{
                max-width:100%;
                justify-content:flex-start;
                width:100%;
            }
        }

        @media (max-width: 640px){
            .today-line-wrap{
                padding:10px 12px;
            }

            .today-line-title{
                font-size:.88rem;
            }

            .today-line-number{
                font-size:.90rem;
            }

            .today-line-label{
                font-size:.78rem;
            }

            .today-line-date{
                font-size:.84rem;
            }
        }
    </style>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb525200bfa976483b4eaa0b7685c6e24)): ?>
<?php $attributes = $__attributesOriginalb525200bfa976483b4eaa0b7685c6e24; ?>
<?php unset($__attributesOriginalb525200bfa976483b4eaa0b7685c6e24); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb525200bfa976483b4eaa0b7685c6e24)): ?>
<?php $component = $__componentOriginalb525200bfa976483b4eaa0b7685c6e24; ?>
<?php unset($__componentOriginalb525200bfa976483b4eaa0b7685c6e24); ?>
<?php endif; ?><?php /**PATH C:\Users\Korisnik\znr-lider-v4\resources\views/filament/widgets/today-mini-block-widget.blade.php ENDPATH**/ ?>