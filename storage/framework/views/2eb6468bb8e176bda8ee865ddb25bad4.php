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
    <div class="sr-wrap">
        <div class="sr-head">
            <h3 class="sr-title"><?php echo e($title); ?></h3>
        </div>

        <div class="sr-grid">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $cards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e($card['url']); ?>" class="sr-card sr-card--<?php echo e($card['tone']); ?>">
                    <div class="sr-card-top">
                        <div class="sr-icon-wrap">
                            <?php if (isset($component)) { $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.icon','data' => ['icon' => $card['icon'],'class' => 'sr-icon']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($card['icon']),'class' => 'sr-icon']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $attributes = $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $component = $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?>
                        </div>

                        <?php if (isset($component)) { $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.icon','data' => ['icon' => 'heroicon-o-chevron-right','class' => 'sr-arrow-icon']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'heroicon-o-chevron-right','class' => 'sr-arrow-icon']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $attributes = $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $component = $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?>
                    </div>

                    <div class="sr-card-middle">
                        <div class="sr-text">
                            <div class="sr-label"><?php echo e($card['label']); ?></div>
                            <div class="sr-hint"><?php echo e($card['hint']); ?></div>
                        </div>

                        <div class="sr-count-wrap">
                            <span class="sr-count"><?php echo e($card['count']); ?></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    <style>
        .sr-wrap{
            border-radius:18px;
            padding:14px 16px;
            border:1px solid #dbe3f0;
            background:#ffffff;
            box-shadow:0 1px 2px rgba(15, 23, 42, 0.04);
            margin-bottom:16px;
            display:flex;
            flex-direction:column;
            gap:12px;
        }

        .dark .sr-wrap{
            border:1px solid rgba(148,163,184,.14);
            background:linear-gradient(180deg, rgba(10, 24, 52, .95), rgba(4, 10, 24, .95));
            box-shadow:0 10px 22px rgba(0, 0, 0, .14);
        }

        .sr-title{
            margin:0;
            font-size:1.02rem;
            font-weight:800;
            color:#111827;
            letter-spacing:-0.02em;
        }

        .dark .sr-title{
            color:#ffffff;
        }

        .sr-grid{
            display:grid;
            grid-template-columns:repeat(4, minmax(0, 1fr));
            gap:12px;
        }

        @media (max-width: 1280px){
            .sr-grid{
                grid-template-columns:repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px){
            .sr-grid{
                grid-template-columns:1fr;
            }
        }

        .sr-card{
            display:flex;
            flex-direction:column;
            gap:10px;
            min-height:92px;
            padding:12px 14px;
            border-radius:16px;
            border:1px solid #dbe3f0;
            background:#ffffff;
            text-decoration:none;
            transition:all .16s ease;
            box-shadow:0 1px 2px rgba(15, 23, 42, 0.04);
        }

        .sr-card:hover{
            transform:translateY(-2px);
            border-color:#c7d5ea;
            box-shadow:0 10px 22px rgba(15, 23, 42, 0.08);
        }

        .dark .sr-card{
            border:1px solid rgba(148,163,184,.14);
            background:rgba(7,18,40,.80);
            box-shadow:none;
        }

        .dark .sr-card:hover{
            box-shadow:0 10px 18px rgba(0,0,0,.14);
        }

        .sr-card-top{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:10px;
        }

        .sr-icon-wrap{
            width:32px;
            height:32px;
            border-radius:10px;
            display:flex;
            align-items:center;
            justify-content:center;
            background:#f3f6fb;
            flex-shrink:0;
        }

        .dark .sr-icon-wrap{
            background:rgba(255,255,255,.06);
        }

        .sr-icon{
            width:17px;
            height:17px;
        }

        .sr-arrow-icon{
            width:15px;
            height:15px;
            color:#94a3b8;
            flex-shrink:0;
        }

        .dark .sr-arrow-icon{
            color:#93c5fd;
        }

        .sr-card-middle{
            display:flex;
            align-items:flex-end;
            justify-content:space-between;
            gap:12px;
            min-width:0;
            flex:1;
        }

        .sr-text{
            min-width:0;
            flex:1;
        }

        .sr-label{
            font-size:.96rem;
            font-weight:800;
            line-height:1.2;
            color:#0f172a;
            margin-bottom:3px;
        }

        .dark .sr-label{
            color:#ffffff;
        }

        .sr-hint{
            font-size:.81rem;
            line-height:1.2;
            color:#64748b;
            display:-webkit-box;
            -webkit-line-clamp:2;
            -webkit-box-orient:vertical;
            overflow:hidden;
        }

        .dark .sr-hint{
            color:#a5b4fc;
        }

        .sr-count-wrap{
            display:flex;
            align-items:flex-end;
            justify-content:flex-end;
            flex-shrink:0;
        }

        .sr-count{
            min-width:36px;
            height:36px;
            padding:0 10px;
            border-radius:999px;
            display:flex;
            align-items:center;
            justify-content:center;
            background:#eef3f9;
            color:#0f172a;
            font-size:1rem;
            font-weight:900;
        }

        .dark .sr-count{
            background:rgba(255,255,255,.08);
            color:#ffffff;
        }

        .sr-card--danger .sr-icon-wrap{
            color:#ef4444;
            background:rgba(239,68,68,.08);
        }

        .dark .sr-card--danger .sr-icon-wrap{
            color:#f43f5e;
            background:rgba(244,63,94,.15);
            border:1px solid rgba(244,63,94,.25);
        }

        .sr-card--warning .sr-icon-wrap{
            color:#d97706;
            background:rgba(245,158,11,.10);
        }

        .dark .sr-card--warning .sr-icon-wrap{
            color:#f59e0b;
            background:rgba(245,158,11,.15);
            border:1px solid rgba(245,158,11,.25);
        }

        .sr-card--gray .sr-icon-wrap{
            color:#64748b;
            background:#eef2f7;
        }

        .dark .sr-card--gray .sr-icon-wrap{
            color:#cbd5e1;
            background:rgba(148,163,184,.12);
            border:1px solid rgba(148,163,184,.20);
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
<?php endif; ?><?php /**PATH C:\Users\Korisnik\znr-lider-v4\resources\views/filament/widgets/system-status-widget.blade.php ENDPATH**/ ?>