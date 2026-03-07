<x-filament-widgets::widget>
    <x-filament::section>
        <div style="margin-bottom: 18px;">
            <h2 style="font-size: 24px; font-weight: 700; color: #fff; margin: 0 0 6px 0;">
                Pregled zapažanja po mjesecima
            </h2>
            <p style="font-size: 14px; color: #9ca3af; margin: 0;">
                Odabrana godina: {{ $year }}
            </p>
        </div>

        <div style="overflow-x: auto; border: 1px solid rgba(255,255,255,.08); border-radius: 16px; background: rgba(255,255,255,.02);">
            <table style="width: 100%; min-width: 1200px; border-collapse: collapse; font-size: 14px;">
                <thead>
                    <tr style="background: rgba(255,255,255,.04);">
                        <th style="padding: 12px 14px; text-align: left; color: #e5e7eb; border-bottom: 1px solid rgba(255,255,255,.08);">Mjesec</th>
                        <th style="padding: 12px 14px; text-align: center; color: #e5e7eb; border-bottom: 1px solid rgba(255,255,255,.08);">Ukupno</th>
                        <th style="padding: 12px 14px; text-align: center; color: #38bdf8; border-bottom: 1px solid rgba(255,255,255,.08);">NM</th>
                        <th style="padding: 12px 14px; text-align: center; color: #fb7185; border-bottom: 1px solid rgba(255,255,255,.08);">Negativna</th>
                        <th style="padding: 12px 14px; text-align: center; color: #34d399; border-bottom: 1px solid rgba(255,255,255,.08);">Pozitivna</th>
                        <th style="padding: 12px 14px; text-align: center; color: #f87171; border-bottom: 1px solid rgba(255,255,255,.08);">Nije započeto</th>
                        <th style="padding: 12px 14px; text-align: center; color: #fbbf24; border-bottom: 1px solid rgba(255,255,255,.08);">U tijeku</th>
                        <th style="padding: 12px 14px; text-align: center; color: #4ade80; border-bottom: 1px solid rgba(255,255,255,.08);">Završeno</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($rows as $index => $row)
                        <tr style="background: {{ $index % 2 === 0 ? 'transparent' : 'rgba(255,255,255,.03)' }};">
                            <td style="padding: 11px 14px; color: #fff; font-weight: 600; font-size: 15px; border-bottom: 1px solid rgba(255,255,255,.06); white-space: nowrap;">
                                {{ $row['month'] }}
                            </td>

                            <td style="padding: 11px 14px; text-align: center; color: #fff; font-weight: 700; font-size: 16px; letter-spacing: 0.3px; border-bottom: 1px solid rgba(255,255,255,.06);">
                                {{ $row['total'] }}
                            </td>

                            <td style="padding: 11px 14px; text-align: center; color: #38bdf8; font-weight: 700; font-size: 16px; letter-spacing: 0.3px; border-bottom: 1px solid rgba(255,255,255,.06);">
                                {{ $row['nm_total'] }}
                            </td>

                            <td style="padding: 11px 14px; text-align: center; color: #fb7185; font-weight: 700; font-size: 16px; letter-spacing: 0.3px; border-bottom: 1px solid rgba(255,255,255,.06);">
                                {{ $row['negative_total'] }}
                            </td>

                            <td style="padding: 11px 14px; text-align: center; color: #34d399; font-weight: 700; font-size: 16px; letter-spacing: 0.3px; border-bottom: 1px solid rgba(255,255,255,.06);">
                                {{ $row['positive_total'] }}
                            </td>

                            <td style="padding: 11px 14px; text-align: center; color: #f87171; font-weight: 700; font-size: 16px; letter-spacing: 0.3px; border-bottom: 1px solid rgba(255,255,255,.06);">
                                {{ $row['not_started_total'] }}
                            </td>

                            <td style="padding: 11px 14px; text-align: center; color: #fbbf24; font-weight: 700; font-size: 16px; letter-spacing: 0.3px; border-bottom: 1px solid rgba(255,255,255,.06);">
                                {{ $row['in_progress_total'] }}
                            </td>

                            <td style="padding: 11px 14px; text-align: center; color: #4ade80; font-weight: 700; font-size: 16px; letter-spacing: 0.3px; border-bottom: 1px solid rgba(255,255,255,.06);">
                                {{ $row['complete_total'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot>
                    <tr style="background: rgba(255,255,255,.08);">
                        <td style="padding: 12px 14px; color: #fff; font-weight: 700; font-size: 15px;">UKUPNO</td>

                        <td style="padding: 12px 14px; text-align: center; color: #fff; font-weight: 800; font-size: 17px; letter-spacing: 0.3px;">
                            {{ collect($rows)->sum('total') }}
                        </td>

                        <td style="padding: 12px 14px; text-align: center; color: #38bdf8; font-weight: 800; font-size: 17px; letter-spacing: 0.3px;">
                            {{ collect($rows)->sum('nm_total') }}
                        </td>

                        <td style="padding: 12px 14px; text-align: center; color: #fb7185; font-weight: 800; font-size: 17px; letter-spacing: 0.3px;">
                            {{ collect($rows)->sum('negative_total') }}
                        </td>

                        <td style="padding: 12px 14px; text-align: center; color: #34d399; font-weight: 800; font-size: 17px; letter-spacing: 0.3px;">
                            {{ collect($rows)->sum('positive_total') }}
                        </td>

                        <td style="padding: 12px 14px; text-align: center; color: #f87171; font-weight: 800; font-size: 17px; letter-spacing: 0.3px;">
                            {{ collect($rows)->sum('not_started_total') }}
                        </td>

                        <td style="padding: 12px 14px; text-align: center; color: #fbbf24; font-weight: 800; font-size: 17px; letter-spacing: 0.3px;">
                            {{ collect($rows)->sum('in_progress_total') }}
                        </td>

                        <td style="padding: 12px 14px; text-align: center; color: #4ade80; font-weight: 800; font-size: 17px; letter-spacing: 0.3px;">
                            {{ collect($rows)->sum('complete_total') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>