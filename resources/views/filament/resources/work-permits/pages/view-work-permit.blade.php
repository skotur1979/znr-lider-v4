<x-filament-panels::page>
    @php
        $record = $this->record;

        $has = fn ($arr, $key) => in_array($key, is_array($arr) ? $arr : [], true);

        $fmtDate = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d.m.Y.') : '';
        $fmtDateTime = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d.m.Y. H:i') : '';
        $fmtTime = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('H:i') : '';

        $measureLabels = \App\Models\WorkPermit::requiredMeasuresOptions();
        $hazardLabels = \App\Models\WorkPermit::hazardOptions();
        $ppeLabels = \App\Models\WorkPermit::ppeOptions();
    @endphp

    <style>
        .wp-page {
            max-width: 980px;
            margin: 0 auto;
        }

        .wp-sheet {
            background: #ffffff;
            color: #000000;
            border: 1px solid #111827;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .18);
            overflow: hidden;
        }

        .dark .wp-sheet {
            background: #ffffff;
            color: #000000;
        }

        .wp-title {
            background: #0b2c6b;
            color: #ffffff;
            text-align: center;
            font-weight: 800;
            font-size: 22px;
            line-height: 1.1;
            padding: 8px 10px;
            border-bottom: 1px solid #111827;
            text-transform: uppercase;
        }

        .wp-subtitle {
            background: #1da9df;
            color: #000000;
            text-align: center;
            font-weight: 800;
            font-size: 18px;
            line-height: 1.1;
            padding: 4px 10px;
            border-bottom: 1px solid #111827;
            text-transform: uppercase;
        }

        .wp-subtitle-green {
            background: #93d150;
        }

        .wp-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .wp-table td,
        .wp-table th {
            border: 1px solid #111827;
            padding: 3px 6px;
            vertical-align: top;
            font-size: 11px;
            line-height: 1.15;
            color: #000000;
        }

        .wp-green {
            background: #93d150;
        }

        .wp-blue {
            background: #1da9df;
        }

        .wp-center {
            text-align: center;
        }

        .wp-bold {
            font-weight: 700;
        }

        .wp-label {
            font-weight: 700;
            width: 140px;
        }

        .wp-small-label {
            font-weight: 700;
            width: 120px;
        }

        .wp-box {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #111827;
            text-align: center;
            line-height: 11px;
            font-size: 10px;
            font-weight: 800;
            vertical-align: middle;
            margin-right: 4px;
        }

        .wp-check-inline {
            display: inline-block;
            margin-right: 18px;
            margin-bottom: 2px;
            white-space: nowrap;
            font-weight: 700;
        }

        .wp-check-col {
            display: block;
            margin-bottom: 3px;
            white-space: nowrap;
            font-size: 11px;
        }

        .wp-content-area {
            min-height: 64px;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .wp-content-area-sm {
            min-height: 22px;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .wp-workers td {
            height: 22px;
        }

        .wp-two-col {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .wp-two-col td {
            border: 1px solid #111827;
            vertical-align: top;
            padding: 4px 8px;
            width: 50%;
        }

        .wp-three-col {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .wp-three-col td {
            border: 1px solid #111827;
            vertical-align: top;
            padding: 4px 8px;
            width: 33.3333%;
        }

        .wp-four-col {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .wp-four-col td {
            border: 1px solid #111827;
            vertical-align: top;
            padding: 4px 8px;
            width: 25%;
        }

        .wp-foot-note {
            background: #93d150;
            text-align: center;
            font-style: italic;
            font-weight: 700;
            padding: 8px 10px;
            border-top: 1px solid #111827;
            font-size: 11px;
            color: #000000;
        }

        @media (max-width: 900px) {
            .wp-page {
                max-width: 100%;
            }

            .wp-title {
                font-size: 18px;
            }

            .wp-subtitle {
                font-size: 15px;
            }

            .wp-table td,
            .wp-table th,
            .wp-two-col td,
            .wp-three-col td,
            .wp-four-col td {
                font-size: 10px;
            }

            .wp-check-inline,
            .wp-check-col {
                white-space: normal;
            }
        }
    </style>

    <div class="wp-page">
        <div class="wp-sheet">
            <div class="wp-title">DOZVOLA ZA RAD</div>
            <div class="wp-subtitle">ZAHTJEV</div>

            <table class="wp-table">
                <tr class="wp-green wp-bold wp-center">
                    <td style="width: 14%;">Broj:</td>
                    <td style="width: 11%;">{{ $record->permit_number ?: '' }}</td>
                    <td style="width: 8%;">Datum<br>:</td>
                    <td style="width: 12%;">{{ $fmtDate($record->issue_date) }}</td>
                    <td style="width: 11%;">Vrijedi od:</td>
                    <td style="width: 18%;">{{ $fmtDateTime($record->valid_from) }}</td>
                    <td style="width: 10%;">Vrijedi do:</td>
                    <td style="width: 16%;">{{ $fmtDateTime($record->valid_until) }}</td>
                </tr>

                <tr>
                    <td class="wp-label">Za poslove:</td>
                    <td colspan="7">
                        <div class="wp-check-inline">
                            <span class="wp-box">{{ $has($record->work_types, 'hot_work') ? 'X' : '' }}</span> VRUĆI RADOVI
                        </div>
                        <div class="wp-check-inline">
                            <span class="wp-box">{{ $has($record->work_types, 'work_at_height') ? 'X' : '' }}</span> RADOVI NA VISINI
                        </div>
                        <div class="wp-check-inline">
                            <span class="wp-box">{{ $has($record->work_types, 'electrical_work') ? 'X' : '' }}</span> ELEKTRIČARSKI RADOVI
                        </div>
                        <br>
                        <div class="wp-check-inline">
                            <span class="wp-box">{{ $has($record->work_types, 'hazardous_chemicals') ? 'X' : '' }}</span> RAD S OPASNIM KEMIKALIJAMA
                        </div>
                        <div class="wp-check-inline">
                            <span class="wp-box">{{ $has($record->work_types, 'other') ? 'X' : '' }}</span> OSTALO:
                            {{ $record->other_work_type ?: '' }}
                        </div>
                    </td>
                </tr>

                <tr>
                    <td class="wp-label">Zahtjev/propis:</td>
                    <td colspan="7">{{ $record->request_or_regulation ?: '' }}</td>
                </tr>

                <tr>
                    <td class="wp-label">Radove izvode:</td>
                    <td colspan="7">
                        <div class="wp-check-inline">
                            <span class="wp-box">{{ $has($record->executor_types, 'company_employees') ? 'X' : '' }}</span> Zaposlenici tvrtke
                        </div>
                        <div class="wp-check-inline">
                            <span class="wp-box">{{ $has($record->executor_types, 'external_contractors') ? 'X' : '' }}</span> Vanjski izvođači
                        </div>
                    </td>
                </tr>

                <tr class="wp-workers">
                    <td class="wp-label" rowspan="3" style="vertical-align: middle;">Popis radnika:</td>
                    <td colspan="2">1. {{ $record->worker_1 ?: '' }}</td>
                    <td colspan="2">2. {{ $record->worker_2 ?: '' }}</td>
                    <td colspan="3">3. {{ $record->worker_3 ?: '' }}</td>
                </tr>
                <tr class="wp-workers">
                    <td colspan="2">4. {{ $record->worker_4 ?: '' }}</td>
                    <td colspan="2">5. {{ $record->worker_5 ?: '' }}</td>
                    <td colspan="3">6. {{ $record->worker_6 ?: '' }}</td>
                </tr>
                <tr class="wp-workers">
                    <td colspan="2">7. {{ $record->worker_7 ?: '' }}</td>
                    <td colspan="2">8. {{ $record->worker_8 ?: '' }}</td>
                    <td colspan="3">9. {{ $record->worker_9 ?: '' }}</td>
                </tr>

                <tr>
                    <td class="wp-label">Opis poslova - radova:</td>
                    <td colspan="7">
                        <div class="wp-content-area">{{ $record->work_description ?: '' }}</div>
                    </td>
                </tr>

                <tr>
                    <td class="wp-label">Kontakt osoba:</td>
                    <td colspan="4">{{ $record->contact_person ?: '' }}</td>
                    <td class="wp-label">Telefonski broj:</td>
                    <td colspan="2">{{ $record->phone ?: '' }}</td>
                </tr>
            </table>

            <div class="wp-subtitle" style="font-size: 15px;">MJERE KOJE JE POTREBNO PODUZETI U CILJU OSIGURANJA IZVOĐENJA RADOVA</div>

            <table class="wp-two-col">
                <tr>
                    <td>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_measures, 'remove_flammable_material') ? 'X' : '' }}</span> {{ $measureLabels['remove_flammable_material'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_measures, 'place_fire_extinguishers') ? 'X' : '' }}</span> {{ $measureLabels['place_fire_extinguishers'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_measures, 'check_welding_bottles') ? 'X' : '' }}</span> {{ $measureLabels['check_welding_bottles'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_measures, 'check_welding_hoses') ? 'X' : '' }}</span> {{ $measureLabels['check_welding_hoses'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_measures, 'cover_openings') ? 'X' : '' }}</span> {{ $measureLabels['cover_openings'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_measures, 'check_grounding_cable') ? 'X' : '' }}</span> {{ $measureLabels['check_grounding_cable'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_measures, 'fire_blankets') ? 'X' : '' }}</span> {{ $measureLabels['fire_blankets'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_measures, 'additional_lighting') ? 'X' : '' }}</span> {{ $measureLabels['additional_lighting'] }}</div>
                    </td>
                    <td>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_measures, 'check_dangerous_gases') ? 'X' : '' }}</span> {{ $measureLabels['check_dangerous_gases'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_measures, 'mark_work_area') ? 'X' : '' }}</span> {{ $measureLabels['mark_work_area'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_measures, 'lototo') ? 'X' : '' }}</span> {{ $measureLabels['lototo'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_measures, 'additional_risk_assessment') ? 'X' : '' }}</span> {{ $measureLabels['additional_risk_assessment'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_measures, 'mandatory_scaffold') ? 'X' : '' }}</span> {{ $measureLabels['mandatory_scaffold'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_measures, 'safety_rope') ? 'X' : '' }}</span> {{ $measureLabels['safety_rope'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_measures, 'additional_access_exit') ? 'X' : '' }}</span> {{ $measureLabels['additional_access_exit'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_measures, 'five_rules_electrical') ? 'X' : '' }}</span> {{ $measureLabels['five_rules_electrical'] }}</div>
                    </td>
                </tr>
            </table>

            <table class="wp-table">
                <tr>
                    <td class="wp-label">Dodatne mjere:</td>
                    <td><div class="wp-content-area-sm">{{ $record->additional_measures ?: '' }}</div></td>
                </tr>
            </table>

            <div class="wp-subtitle" style="font-size: 15px;">POTREBNA OPREMA (BROJ I VRSTA VATROGASNIH APARATA – AKO JE POTREBNO)</div>

            <table class="wp-table">
                <tr>
                    <td><div class="wp-content-area-sm">{{ $record->required_equipment ?: '' }}</div></td>
                </tr>
            </table>

            <div class="wp-subtitle" style="font-size: 15px;">OPASNOSTI RADA</div>

            <table class="wp-three-col">
                <tr>
                    <td>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->work_hazards, 'fall_from_height') ? 'X' : '' }}</span> {{ $hazardLabels['fall_from_height'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->work_hazards, 'sharp_objects') ? 'X' : '' }}</span> {{ $hazardLabels['sharp_objects'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->work_hazards, 'mechanical_lifting') ? 'X' : '' }}</span> {{ $hazardLabels['mechanical_lifting'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->work_hazards, 'stored_energy') ? 'X' : '' }}</span> {{ $hazardLabels['stored_energy'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->work_hazards, 'hot_cold_surfaces') ? 'X' : '' }}</span> {{ $hazardLabels['hot_cold_surfaces'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->work_hazards, 'acids_alkalis') ? 'X' : '' }}</span> {{ $hazardLabels['acids_alkalis'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->work_hazards, 'heavy_loads') ? 'X' : '' }}</span> {{ $hazardLabels['heavy_loads'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->work_hazards, 'electrical_hazard') ? 'X' : '' }}</span> {{ $hazardLabels['electrical_hazard'] }}</div>
                    </td>

                    <td>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->work_hazards, 'lack_of_oxygen') ? 'X' : '' }}</span> {{ $hazardLabels['lack_of_oxygen'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->work_hazards, 'crushing') ? 'X' : '' }}</span> {{ $hazardLabels['crushing'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->work_hazards, 'vehicle_impact') ? 'X' : '' }}</span> {{ $hazardLabels['vehicle_impact'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->work_hazards, 'bad_weather') ? 'X' : '' }}</span> {{ $hazardLabels['bad_weather'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->work_hazards, 'noise') ? 'X' : '' }}</span> {{ $hazardLabels['noise'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->work_hazards, 'explosive_flammable') ? 'X' : '' }}</span> {{ $hazardLabels['explosive_flammable'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->work_hazards, 'outdoor_work') ? 'X' : '' }}</span> {{ $hazardLabels['outdoor_work'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->work_hazards, 'poor_lighting') ? 'X' : '' }}</span> {{ $hazardLabels['poor_lighting'] }}</div>
                    </td>

                    <td>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->work_hazards, 'confined_space') ? 'X' : '' }}</span> {{ $hazardLabels['confined_space'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->work_hazards, 'repetitive_movements') ? 'X' : '' }}</span> {{ $hazardLabels['repetitive_movements'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->work_hazards, 'walking_surfaces') ? 'X' : '' }}</span> {{ $hazardLabels['walking_surfaces'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->work_hazards, 'high_pressure') ? 'X' : '' }}</span> {{ $hazardLabels['high_pressure'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->work_hazards, 'prolonged_work') ? 'X' : '' }}</span> {{ $hazardLabels['prolonged_work'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->work_hazards, 'eye_strain') ? 'X' : '' }}</span> {{ $hazardLabels['eye_strain'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->work_hazards, 'dangerous_vapors_gases') ? 'X' : '' }}</span> {{ $hazardLabels['dangerous_vapors_gases'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->work_hazards, 'other') ? 'X' : '' }}</span> OSTALO: {{ $record->other_hazard ?: '' }}</div>
                    </td>
                </tr>
            </table>

            <div class="wp-subtitle" style="font-size: 15px;">OSOBNA ZAŠTITNA OPREMA KOJA SE MORA KORISTITI TIJEKOM IZVOĐENJA RADOVA</div>

            <table class="wp-four-col">
                <tr>
                    <td>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_ppe, 'safety_shoes') ? 'X' : '' }}</span> {{ $ppeLabels['safety_shoes'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_ppe, 'work_clothes') ? 'X' : '' }}</span> {{ $ppeLabels['work_clothes'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_ppe, 'rubber_boots') ? 'X' : '' }}</span> {{ $ppeLabels['rubber_boots'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_ppe, 'protective_gloves') ? 'X' : '' }}</span> {{ $ppeLabels['protective_gloves'] }}</div>
                    </td>

                    <td>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_ppe, 'safety_glasses') ? 'X' : '' }}</span> {{ $ppeLabels['safety_glasses'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_ppe, 'protective_mask') ? 'X' : '' }}</span> {{ $ppeLabels['protective_mask'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_ppe, 'half_mask') ? 'X' : '' }}</span> {{ $ppeLabels['half_mask'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_ppe, 'face_shield') ? 'X' : '' }}</span> {{ $ppeLabels['face_shield'] }}</div>
                    </td>

                    <td>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_ppe, 'hearing_protection') ? 'X' : '' }}</span> {{ $ppeLabels['hearing_protection'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_ppe, 'respirator') ? 'X' : '' }}</span> {{ $ppeLabels['respirator'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_ppe, 'reflective_vest') ? 'X' : '' }}</span> {{ $ppeLabels['reflective_vest'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_ppe, 'fall_protection_belt') ? 'X' : '' }}</span> {{ $ppeLabels['fall_protection_belt'] }}</div>
                    </td>

                    <td>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_ppe, 'helmet') ? 'X' : '' }}</span> {{ $ppeLabels['helmet'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_ppe, 'welding_mask') ? 'X' : '' }}</span> {{ $ppeLabels['welding_mask'] }}</div>
                        <div class="wp-check-col"><span class="wp-box">{{ $has($record->required_ppe, 'cap_with_protection') ? 'X' : '' }}</span> {{ $ppeLabels['cap_with_protection'] }}</div>
                    </td>
                </tr>
            </table>

            <div class="wp-subtitle" style="font-size: 15px;">ODOBRENJE – DOZVOLA NIJE VAŽEĆA DOK NIJE POTPISANA</div>

            <table class="wp-table">
                <tr class="wp-blue wp-center wp-bold">
                    <td style="width: 30%;"></td>
                    <td>Ime i prezime:</td>
                    <td>Potpis:</td>
                </tr>
                <tr>
                    <td class="wp-bold">Osoba koja zahtjeva dozvolu:</td>
                    <td>{{ $record->requester_name ?: '' }}</td>
                    <td>{{ $record->requester_signature ?: '' }}</td>
                </tr>
                <tr>
                    <td class="wp-bold">Osoba koja odobrava dozvolu:</td>
                    <td>{{ $record->approver_name ?: '' }}</td>
                    <td>{{ $record->approver_signature ?: '' }}</td>
                </tr>
            </table>

            <div class="wp-subtitle" style="font-size: 15px;">PRODUŽENJE VALJANOSTI DOZVOLE ZA RAD (AKO JE POTREBNO I PRIMJENJIVO)</div>

            <table class="wp-table">
                <tr>
                    <td class="wp-small-label">Vrijedi od:</td>
                    <td>{{ $fmtDateTime($record->extension_valid_from) ?: '' }}</td>
                    <td class="wp-small-label">Vrijedi do:</td>
                    <td>{{ $fmtDateTime($record->extension_valid_until) ?: '' }}</td>
                </tr>
                <tr class="wp-blue wp-center wp-bold">
                    <td></td>
                    <td>Ime i prezime:</td>
                    <td></td>
                    <td>Potpis:</td>
                </tr>
                <tr>
                    <td class="wp-bold">Osoba koja odobrava produženje:</td>
                    <td>{{ $record->extension_approver_name ?: '' }}</td>
                    <td></td>
                    <td>{{ $record->extension_approver_signature ?: '' }}</td>
                </tr>
            </table>

            <div class="wp-subtitle wp-subtitle-green" style="font-size: 15px;">PROVJERA IZVRŠENIH RADOVA</div>

            <table class="wp-table">
                <tr>
                    <td class="wp-small-label">Radovi su završeni:</td>
                    <td style="width: 17%;">
                        <span class="wp-check-inline"><span class="wp-box">{{ $record->works_finished === true ? 'X' : '' }}</span> DA</span>
                        <span class="wp-check-inline"><span class="wp-box">{{ $record->works_finished === false ? 'X' : '' }}</span> NE</span>
                    </td>
                    <td class="wp-small-label">Ako nisu završeni navesti razlog:</td>
                    <td>{{ $record->unfinished_reason ?: '' }}</td>
                </tr>
                <tr>
                    <td class="wp-small-label">Provjera provedena nakon:</td>
                    <td>
                        <span class="wp-check-inline"><span class="wp-box">{{ $record->checked_after === '1h' ? 'X' : '' }}</span> 1 sata</span>
                        <span class="wp-check-inline"><span class="wp-box">{{ $record->checked_after === '3h' ? 'X' : '' }}</span> 3 sata</span>
                    </td>
                    <td></td>
                    <td></td>
                </tr>
                <tr class="wp-green wp-center wp-bold">
                    <td>Ime i prezime:</td>
                    <td>Potpis:</td>
                    <td>Datum:</td>
                    <td>Vrijeme:</td>
                </tr>
                <tr>
                    <td>{{ $record->verification_name ?: '' }}</td>
                    <td>{{ $record->verification_signature ?: '' }}</td>
                    <td>{{ $fmtDate($record->verification_date) ?: '' }}</td>
                    <td>{{ $fmtTime($record->verification_time) ?: '' }}</td>
                </tr>
            </table>

            <div class="wp-foot-note">
                Napomena: Nositelj radova upoznat je sa sigurnosnim mjerama i preuzima odgovornost potpisom preuzetog odobrenja.
            </div>
        </div>
    </div>
</x-filament-panels::page>