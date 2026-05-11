<?php if (isset($component)) { $__componentOriginal166a02a7c5ef5a9331faf66fa665c256 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal166a02a7c5ef5a9331faf66fa665c256 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-panels::components.page.index','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-panels::page'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <style>
        .edu-wrap { width: 100%; }

        .edu-hero {
            background: linear-gradient(135deg, #111827, #1f2937);
            border: 1px solid rgba(245, 158, 11, .35);
            border-radius: 22px;
            padding: 18px;
            margin-bottom: 18px;
            box-shadow: 0 16px 36px rgba(0, 0, 0, .22);
        }

        .edu-hero-title {
            color: #ffffff;
            font-size: 21px;
            font-weight: 900;
            margin-bottom: 5px;
        }

        .edu-hero-text {
            color: #cbd5e1;
            font-size: 13px;
            margin-bottom: 16px;
        }

        .edu-filter-grid {
            display: grid;
            grid-template-columns: 2fr 1.1fr 1.1fr auto;
            gap: 10px;
            align-items: end;
        }

        .edu-field label {
            display: block;
            color: #e5e7eb;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 5px;
        }

        .edu-field input,
        .edu-field select {
            width: 100%;
            min-height: 38px;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .35);
            background: #ffffff;
            color: #0f172a;
            font-size: 13px;
            padding: 8px 11px;
        }

        .edu-reset {
            min-height: 38px;
            padding: 8px 15px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, .22);
            background: rgba(255, 255, 255, .12);
            color: #ffffff;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
        }

        .edu-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            width: 100%;
            align-items: stretch;
        }

        .edu-card {
            background: #111827;
            border: 1px solid rgba(245, 158, 11, .55);
            border-radius: 22px;
            box-shadow: 0 14px 32px rgba(0, 0, 0, .28);
            overflow: hidden;
            height: 330px;
            display: grid;
            grid-template-rows: 112px 74px 1fr;
        }

        .edu-card-head {
            padding: 16px 17px 10px;
            display: grid;
            grid-template-columns: 44px minmax(0, 1fr) auto;
            gap: 12px;
            align-items: start;
            overflow: hidden;
        }

        .edu-icon {
            width: 44px;
            height: 44px;
            border-radius: 15px;
            background: rgba(255, 255, 255, .09);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .edu-title-row {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            margin-bottom: 8px;
        }

        .edu-title {
            color: #ffffff;
            font-size: 16px;
            font-weight: 900;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .edu-badge-panel {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 5px;
            max-width: 100%;
            max-height: 54px;
            overflow: hidden;
        }

        .edu-badge {
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 0 8px;
            font-size: 11px;
            font-weight: 800;
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .15);
            color: #e5e7eb;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 0;
        }

        .edu-badge-main {
            background: rgba(59, 130, 246, .16);
            border-color: rgba(59, 130, 246, .35);
            color: #bfdbfe;
        }

        .edu-badge-type {
            background: rgba(16, 185, 129, .12);
            border-color: rgba(16, 185, 129, .30);
            color: #bbf7d0;
        }

        .edu-badge-meta {
            background: rgba(245, 158, 11, .13);
            border-color: rgba(245, 158, 11, .33);
            color: #fde68a;
        }

        .edu-edit-top {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 32px;
            border-radius: 11px;
            padding: 7px 11px;
            font-size: 12px;
            font-weight: 900;
            text-decoration: none;
            background: rgba(255, 255, 255, .10);
            color: #ffffff;
            white-space: nowrap;
        }

        .edu-body {
            padding: 0 17px 12px;
            color: #cbd5e1;
            font-size: 13px;
            line-height: 1.45;
            overflow: hidden;
        }

        .edu-actions {
            border-top: 1px solid rgba(255, 255, 255, .10);
            padding: 12px 17px 15px;
            display: grid;
            gap: 8px;
            align-content: start;
            overflow: hidden;
        }

        .edu-action-group {
            display: grid;
            grid-template-columns: 86px minmax(0, 1fr);
            gap: 8px;
            align-items: start;
        }

        .edu-action-label {
            color: #93c5fd;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .04em;
            padding-top: 8px;
        }

        .edu-action-list {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            min-width: 0;
            max-height: 72px;
            overflow: hidden;
        }

        .edu-link,
        .edu-file {
            display: inline-flex;
            align-items: center;
            max-width: 185px;
            min-height: 30px;
            border-radius: 11px;
            padding: 7px 10px;
            font-size: 12px;
            font-weight: 900;
            text-decoration: none;
            line-height: 1;
        }

        .edu-link {
            background: #f59e0b;
            color: #111827;
        }

        .edu-file {
            background: #10b981;
            color: #ffffff;
        }

        .edu-link span,
        .edu-file span {
            display: inline-block;
            min-width: 0;
            max-width: 145px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .edu-empty-source {
            color: #64748b;
            font-size: 12px;
            padding-top: 8px;
            font-style: italic;
        }

        .edu-empty {
            border-radius: 22px;
            border: 1px dashed rgba(148, 163, 184, .35);
            background: #111827;
            padding: 34px;
            text-align: center;
            color: #cbd5e1;
        }

        .edu-pagination {
            margin-top: 18px;
        }

        @media (max-width: 1450px) {
            .edu-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 900px) {
            .edu-grid,
            .edu-filter-grid {
                grid-template-columns: 1fr;
            }

            .edu-card {
                height: auto;
                min-height: 330px;
            }

            .edu-card-head {
                grid-template-columns: 44px minmax(0, 1fr);
            }

            .edu-edit-top {
                grid-column: 2;
                width: fit-content;
            }

            .edu-action-group {
                grid-template-columns: 1fr;
            }

            .edu-action-list {
                max-height: none;
            }

            .edu-badge-panel {
                max-height: none;
            }
        }
    </style>

    <div class="edu-wrap">
        <div class="edu-hero">
            <div class="edu-hero-title">Edukacijski centar</div>
            <div class="edu-hero-text">
                Edukacijski materijali, stručni linkovi, video upute, Napo filmovi i interni dokumenti.
            </div>

            <div class="edu-filter-grid">
                <div class="edu-field">
                    <label>Pretraživanje</label>
                    <input
                        type="text"
                        wire:model.live.debounce.350ms="search"
                        placeholder="Pretraži po nazivu, opisu ili linku..."
                    >
                </div>

                <div class="edu-field">
                    <label>Kategorija</label>
                    <select wire:model.live="category">
                        <option value="">Sve kategorije</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $this->categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($cat->id); ?>"><?php echo e($cat->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </select>
                </div>

                <div class="edu-field">
                    <label>Vrsta</label>
                    <select wire:model.live="type">
                        <option value="">Sve vrste</option>
                        <option value="document">Dokument</option>
                        <option value="video">Video</option>
                        <option value="website">Stručna stranica</option>
                        <option value="instruction">Uputa</option>
                        <option value="other">Ostalo</option>
                    </select>
                </div>

                <button type="button" wire:click="resetFilters" class="edu-reset">
                    Reset
                </button>
            </div>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->materials->count()): ?>
            <div class="edu-grid">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $this->materials; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $material): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $links = $this->materialLinks($material);
                        $files = $this->materialFiles($material);

                        $contentTypes = $material->content_types;

                        if (! is_array($contentTypes) || count($contentTypes) === 0) {
                            $contentTypes = [$material->type ?: 'document'];
                        }
                    ?>

                    <div class="edu-card">
                        <div class="edu-card-head">
                            <div class="edu-icon">
                                <?php echo e($this->iconFor($contentTypes[0] ?? $material->type)); ?>

                            </div>

                            <div style="min-width: 0;">
                                <div class="edu-title-row">
                                    <div class="edu-title" title="<?php echo e($material->title); ?>">
                                        <?php echo e($material->title); ?>

                                    </div>
                                </div>

                                <div class="edu-badge-panel">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($material->category): ?>
                                        <span class="edu-badge edu-badge-main" title="<?php echo e($material->category->name); ?>">
                                            <?php echo e($material->category->name); ?>

                                        </span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $contentTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contentType): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <span class="edu-badge edu-badge-type" title="<?php echo e($this->typeLabel($contentType)); ?>">
                                            <?php echo e($this->typeLabel($contentType)); ?>

                                        </span>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($links) > 0): ?>
                                        <span class="edu-badge edu-badge-meta">
                                            <?php echo e(count($links)); ?> link
                                        </span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($files) > 0): ?>
                                        <span class="edu-badge edu-badge-meta">
                                            <?php echo e(count($files)); ?> dok.
                                        </span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                    <span class="edu-badge" title="<?php echo e($material->is_global ? 'Globalno' : 'Organizacija'); ?>">
                                        <?php echo e($material->is_global ? 'Globalno' : 'Organizacija'); ?>

                                    </span>
                                </div>
                            </div>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->canEditMaterial($material)): ?>
                                <a
                                    href="<?php echo e(\App\Filament\Resources\LearningMaterials\LearningMaterialResource::getUrl('edit', ['record' => $material])); ?>"
                                    class="edu-edit-top"
                                >
                                    Uredi
                                </a>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>

                        <div class="edu-body">
                            <?php echo e($material->description ? \Illuminate\Support\Str::limit($material->description, 170) : 'Nema dodatnog opisa za ovaj edukacijski materijal.'); ?>

                        </div>

                        <div class="edu-actions">
                            <div class="edu-action-group">
                                <div class="edu-action-label">Linkovi</div>

                                <div class="edu-action-list">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $links; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <a href="<?php echo e($link['url']); ?>" target="_blank" rel="noopener noreferrer" class="edu-link" title="<?php echo e($link['label']); ?>">
                                            🔗 <span><?php echo e(\Illuminate\Support\Str::limit($link['label'], 26)); ?></span>
                                        </a>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <div class="edu-empty-source">Nema linkova</div>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </div>

                            <div class="edu-action-group">
                                <div class="edu-action-label">Dokumenti</div>

                                <div class="edu-action-list">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $files; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $file): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <a href="<?php echo e($file['url']); ?>" target="_blank" rel="noopener noreferrer" class="edu-file" title="<?php echo e($file['label']); ?>">
                                            📄 <span><?php echo e(\Illuminate\Support\Str::limit($file['label'], 26)); ?></span>
                                        </a>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <div class="edu-empty-source">Nema dokumenata</div>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <div class="edu-pagination">
                <?php echo e($this->materials->links()); ?>

            </div>
        <?php else: ?>
            <div class="edu-empty">
                <strong>Nema pronađenih edukacijskih materijala.</strong><br>
                Promijeni filtere ili dodaj novi materijal.
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $attributes = $__attributesOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $component = $__componentOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?><?php /**PATH C:\Users\Korisnik\znr-lider-v4\resources\views/filament/resources/learning-materials/pages/list-learning-materials.blade.php ENDPATH**/ ?>