<x-filament-panels::page>
    <style>
        .edu-wrap { width: 100%; }

        .edu-hero {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 22px;
            padding: 18px;
            margin-bottom: 18px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
        }

        .dark .edu-hero {
            background: linear-gradient(135deg, #111827, #1f2937);
            border: 1px solid rgba(245, 158, 11, .35);
            box-shadow: 0 16px 36px rgba(0, 0, 0, .22);
        }

        .edu-hero-title {
            color: #111827;
            font-size: 21px;
            font-weight: 900;
            margin-bottom: 5px;
        }

        .dark .edu-hero-title { color: #ffffff; }

        .edu-hero-text {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 16px;
        }

        .dark .edu-hero-text { color: #cbd5e1; }

        .edu-filter-grid {
            display: grid;
            grid-template-columns: 2fr 1.1fr 1.1fr auto;
            gap: 10px;
            align-items: end;
        }

        .edu-field label {
            display: block;
            color: #475569;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 5px;
        }

        .dark .edu-field label { color: #e5e7eb; }

        .edu-field input,
        .edu-field select {
            width: 100%;
            min-height: 40px;
            border-radius: 12px;
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #111827;
            font-size: 13px;
            padding: 8px 11px;
        }

        .dark .edu-field input,
        .dark .edu-field select {
            border: 1px solid rgba(148, 163, 184, .35);
            background: #ffffff;
            color: #0f172a;
        }

        .edu-reset {
            min-height: 40px;
            padding: 8px 15px;
            border-radius: 12px;
            border: 1px solid #d1d5db;
            background: #f8fafc;
            color: #111827;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
        }

        .dark .edu-reset {
            border: 1px solid rgba(255, 255, 255, .22);
            background: rgba(255, 255, 255, .12);
            color: #ffffff;
        }

        .edu-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            width: 100%;
            align-items: stretch;
        }

                .edu-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 22px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, .06);
            overflow: hidden;
            height: 390px;
            display: grid;
            grid-template-rows: 112px 70px 1fr;
        }

        .dark .edu-card {
            background: #111827;
            border: 1px solid rgba(245, 158, 11, .55);
            box-shadow: 0 14px 32px rgba(0, 0, 0, .28);
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
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .dark .edu-icon { background: rgba(255, 255, 255, .09); }

        .edu-title-row {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            margin-bottom: 8px;
        }

        .edu-title {
            color: #111827;
            font-size: 16px;
            font-weight: 900;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dark .edu-title { color: #ffffff; }

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
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            color: #334155;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 0;
        }

        .dark .edu-badge {
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .15);
            color: #e5e7eb;
        }

        .edu-badge-main {
            background: #dbeafe;
            border-color: #bfdbfe;
            color: #1d4ed8;
        }

        .dark .edu-badge-main {
            background: rgba(59, 130, 246, .16);
            border-color: rgba(59, 130, 246, .35);
            color: #bfdbfe;
        }

        .edu-badge-type {
            background: #dcfce7;
            border-color: #bbf7d0;
            color: #047857;
        }

        .dark .edu-badge-type {
            background: rgba(16, 185, 129, .12);
            border-color: rgba(16, 185, 129, .30);
            color: #bbf7d0;
        }

        .edu-badge-meta {
            background: #fef3c7;
            border-color: #fde68a;
            color: #92400e;
        }

        .dark .edu-badge-meta {
            background: rgba(245, 158, 11, .13);
            border-color: rgba(245, 158, 11, .33);
            color: #fde68a;
        }

        .edu-badge-new {
            background: #fee2e2;
            border-color: #fecaca;
            color: #b91c1c;
        }

        .dark .edu-badge-new {
            background: rgba(239, 68, 68, .16);
            border-color: rgba(239, 68, 68, .35);
            color: #fecaca;
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
            background: #f59e0b;
            color: #111827;
            white-space: nowrap;
        }

        .dark .edu-edit-top {
            background: rgba(255, 255, 255, .10);
            color: #ffffff;
        }

        .edu-card-top-actions {
            display: flex;
            align-items: center;
            gap: 7px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .edu-qr-top {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 32px;
            border-radius: 11px;
            padding: 7px 11px;
            font-size: 12px;
            font-weight: 900;
            text-decoration: none;
            background: #16a34a;
            color: #ffffff;
            white-space: nowrap;
        }

        .dark .edu-qr-top {
            background: rgba(34, 197, 94, .18);
            color: #bbf7d0;
            border: 1px solid rgba(34, 197, 94, .35);
        }

        .edu-body {
            padding: 0 17px 12px;
            color: #475569;
            font-size: 13px;
            line-height: 1.45;
            overflow: hidden;
        }

        .dark .edu-body { color: #cbd5e1; }

        .edu-actions {
            border-top: 1px solid #e5e7eb;
            padding: 12px 17px 15px;
            display: grid;
            gap: 8px;
            align-content: start;
            overflow: hidden;
        }

        .dark .edu-actions {
            border-top: 1px solid rgba(255, 255, 255, .10);
        }

        .edu-action-group {
            display: grid;
            grid-template-columns: 86px minmax(0, 1fr);
            gap: 8px;
            align-items: start;
        }

        .edu-action-label {
            color: #2563eb;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .04em;
            padding-top: 8px;
        }

        .dark .edu-action-label { color: #93c5fd; }

        .edu-action-list {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
        min-width: 0;
        max-height: 118px;
        overflow-y: auto;
        padding-right: 4px;
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
            color: #94a3b8;
            font-size: 12px;
            padding-top: 8px;
            font-style: italic;
        }

        .dark .edu-empty-source { color: #64748b; }

        .edu-empty {
            border-radius: 22px;
            border: 1px dashed #cbd5e1;
            background: #ffffff;
            padding: 34px;
            text-align: center;
            color: #475569;
        }

        .dark .edu-empty {
            border: 1px dashed rgba(148, 163, 184, .35);
            background: #111827;
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
            grid-template-rows: auto auto 1fr;
        }

        .edu-card-head {
            grid-template-columns: 44px minmax(0, 1fr);
            overflow: visible;
            padding-bottom: 14px;
        }

        .edu-title {
            white-space: normal;
            overflow: visible;
            text-overflow: unset;
            line-height: 1.25;
        }

        .edu-badge-panel {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            max-height: none;
            overflow: visible;
            margin-top: 6px;
        }

        .edu-badge {
            min-height: 26px;
            height: auto;
            padding: 5px 8px;
            white-space: normal;
            text-align: center;
            line-height: 1.15;
        }

        .edu-card-top-actions {
            grid-column: 2;
            justify-content: flex-start;
            margin-top: 8px;
        }

        .edu-qr-top,
        .edu-edit-top {
            width: fit-content;
        }

        .edu-edit-top {
            display: inline-flex;
            position: relative;
            z-index: 3;
        }

        .edu-body {
            padding-top: 10px;
            overflow: visible;
        }

        .edu-action-group {
            grid-template-columns: 1fr;
        }

        .edu-action-list {
            max-height: none;
        }

        .edu-actions {
            overflow: visible;
        }
    }
    </style>

    <div class="edu-wrap">
        <div class="edu-hero">
            <div class="edu-hero-title">Edukacijski centar</div>

            <div class="edu-hero-text">
                Upute za korištenje ZNR LIDER sustava, Excel predlošci, PDF obrasci, korisni dokumenti, video linkovi i pomoć za korisnike.
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
                        @foreach ($this->categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="edu-field">
                    <label>Vrsta</label>
                    <select wire:model.live="type">
                        <option value="">Sve vrste</option>
                        <option value="manual">Upute</option>
                        <option value="excel_template">Excel predložak</option>
                        <option value="pdf_form">PDF obrazac</option>
                        <option value="faq">FAQ / pomoć</option>
                        <option value="example">Primjer</option>
                        <option value="video">Video link</option>
                        <option value="website">Korisni link</option>
                        <option value="document">Dokument</option>
                        <option value="other">Ostalo</option>
                    </select>
                </div>

                <button type="button" wire:click="resetFilters" class="edu-reset">
                    Reset
                </button>
            </div>
        </div>

        @if ($this->materials->count())
            <div class="edu-grid">
                @foreach ($this->materials as $material)
                    @php
                        $links = $this->materialLinks($material);
                        $files = $this->materialFiles($material);

                        $contentTypes = $material->content_types;

                        if (! is_array($contentTypes) || count($contentTypes) === 0) {
                            $contentTypes = [$material->type ?: 'document'];
                        }
                    @endphp

                    <div class="edu-card">
                        <div class="edu-card-head">
                            <div class="edu-icon">
                                {{ $this->iconFor($contentTypes[0] ?? $material->type) }}
                            </div>

                            <div style="min-width: 0;">
                                <div class="edu-title-row">
                                    <div class="edu-title" title="{{ $material->title }}">
                                        {{ $material->title }}
                                    </div>
                                </div>

                                <div class="edu-badge-panel">
                                    @if ($material->category)
                                        <span class="edu-badge edu-badge-main" title="{{ $material->category->name }}">
                                            {{ $material->category->name }}
                                        </span>
                                    @endif

                                    @foreach ($contentTypes as $contentType)
                                        <span class="edu-badge edu-badge-type" title="{{ $this->typeLabel($contentType) }}">
                                            {{ $this->typeLabel($contentType) }}
                                        </span>
                                    @endforeach

                                    @if (count($links) > 0)
                                        <span class="edu-badge edu-badge-meta">
                                            {{ count($links) }} link
                                        </span>
                                    @endif

                                    @if (count($files) > 0)
                                        <span class="edu-badge edu-badge-meta">
                                            {{ count($files) }} dok.
                                        </span>
                                    @endif

                                    @if ($material->created_at && $material->created_at->gt(now()->subDays(7)))
                                        <span class="edu-badge edu-badge-new">
                                            Novo
                                        </span>
                                    @endif

                                    <span class="edu-badge" title="{{ $material->is_global ? 'Globalno dostupno svim korisnicima' : 'Dostupno organizaciji' }}">
                                        {{ $material->is_global ? 'Globalno' : 'Organizacija' }}
                                    </span>
                                </div>
                            </div>

                            @if (
                                $this->canManageQr($material)
                                || $this->canEditMaterial($material)
                            )
                                <div class="edu-card-top-actions">

                                    @if ($this->canManageQr($material))
                                        <a
                                            href="{{ $this->qrAdminUrl($material) }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="edu-qr-top"
                                        >
                                            QR kod
                                        </a>
                                    @endif

                                    @if ($this->canEditMaterial($material))
                                        <a
                                            href="{{ \App\Filament\Resources\LearningMaterials\LearningMaterialResource::getUrl('edit', ['record' => $material]) }}"
                                            class="edu-edit-top"
                                        >
                                            Uredi
                                        </a>
                                    @endif

                                </div>
                            @endif
                        </div>

                        <div class="edu-body">
                            {{ $material->description ? \Illuminate\Support\Str::limit($material->description, 170) : 'Nema dodatnog opisa za ovaj edukacijski materijal.' }}
                        </div>

                        <div class="edu-actions">
                            <div class="edu-action-group">
                                <div class="edu-action-label">Linkovi</div>

                                <div class="edu-action-list">
                                    @forelse ($links as $link)
                                        <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" class="edu-link" title="{{ $link['label'] }}">
                                            🔗 <span>{{ \Illuminate\Support\Str::limit($link['label'], 26) }}</span>
                                        </a>
                                    @empty
                                        <div class="edu-empty-source">Nema linkova</div>
                                    @endforelse
                                </div>
                            </div>

                            <div class="edu-action-group">
                                <div class="edu-action-label">Dokumenti</div>

                                <div class="edu-action-list">
                                    @forelse ($files as $file)
                                        <a href="{{ $file['url'] }}" target="_blank" rel="noopener noreferrer" class="edu-file" title="{{ $file['label'] }}">
                                            📄 <span>{{ \Illuminate\Support\Str::limit($file['label'], 26) }}</span>
                                        </a>
                                    @empty
                                        <div class="edu-empty-source">Nema dokumenata</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="edu-pagination">
                {{ $this->materials->links() }}
            </div>
        @else
            <div class="edu-empty">
                <strong>Nema pronađenih edukacijskih materijala.</strong><br>
                Promijeni filtere ili dodaj novi materijal.
            </div>
        @endif
    </div>
</x-filament-panels::page>