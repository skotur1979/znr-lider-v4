<?php

namespace App\Exports;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class UsersExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    protected $users;

    public function __construct()
    {
        $this->users = UserResource::getEloquentQuery()
            ->with('parentUser')
            ->orderBy('name')
            ->get();
    }

    public function collection()
    {
        return $this->users;
    }

    public function headings(): array
    {
        return [
            'Ime i prezime',
            'Organizacija',
            'E-mail',
            'Uloga',
            'Glavni korisnik organizacije',
            'Može dodavati podkorisnike',
            'Aktivan',
            'Dnevni izvještaj',
            'Tjedni izvještaj',
            'Uključeni moduli',
        ];
    }

    public function map($user): array
    {
        /** @var User $user */

        return [
            $user->name,
            $user->organization_name,
            $user->email,
            $this->roleLabel($user),
            $user->parentUser?->name ?? '',
            $user->can_manage_subusers ? 'Da' : 'Ne',
            $user->is_active ? 'Da' : 'Ne',
            $user->daily_status_email_enabled ? 'Da' : 'Ne',
            $user->weekly_status_email_enabled ? 'Da' : 'Ne',
            implode(', ', $this->moduleLabels($user->quick_actions)),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastRow = $this->users->count() + 1;

                $sheet->getStyle("A1:J{$lastRow}")
                    ->getFont()
                    ->setName('DejaVu Sans')
                    ->setSize(10);

                $sheet->getStyle('A1:J1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'name' => 'DejaVu Sans',
                        'size' => 10,
                    ],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => '1F2937'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                $sheet->getStyle("A2:J{$lastRow}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                $sheet->getStyle("A2:J{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT);

                $sheet->getStyle("F2:I{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getColumnDimension('A')->setWidth(28);
                $sheet->getColumnDimension('B')->setWidth(30);
                $sheet->getColumnDimension('C')->setWidth(34);
                $sheet->getColumnDimension('D')->setWidth(20);
                $sheet->getColumnDimension('E')->setWidth(28);
                $sheet->getColumnDimension('F')->setWidth(18);
                $sheet->getColumnDimension('G')->setWidth(12);
                $sheet->getColumnDimension('H')->setWidth(18);
                $sheet->getColumnDimension('I')->setWidth(18);
                $sheet->getColumnDimension('J')->setWidth(70);

                $sheet->getRowDimension(1)->setRowHeight(28);

                for ($row = 2; $row <= $lastRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(34);
                }

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:J{$lastRow}");
            },
        ];
    }

    private function roleLabel(User $user): string
    {
        return match ($user->role) {
            'super_admin', 'admin' => 'Super admin',
            'org_admin' => 'Glavni korisnik',
            'org_user' => 'Podkorisnik',
            default => $user->is_admin ? 'Super admin' : 'Korisnik',
        };
    }

    private function moduleLabels($modules): array
    {
        if (! is_array($modules)) {
            return [];
        }

        $labels = [
            'risk_assessments' => 'Procjene rizika',
            'documentation' => 'Dokumentacija',
            'chemicals' => 'Kemikalije',
            'observations' => 'Zapažanja',
            'incidents' => 'Incidenti',
            'expenses' => 'Troškovi',
            'budgets' => 'Budžet',
            'work_permits' => 'Dozvole za rad',
            'inspections' => 'Nadzori',
            'kpis' => 'KPI',

            'employees' => 'Zaposlenici',
            'medical_referrals_ra1' => 'RA-1 uputnice',
            'medical_referrals_nr1' => 'NR-1 uputnice',
            'ppe_logs' => 'Upisnik OZO',

            'machines' => 'Radna oprema',
            'fires' => 'Vatrogasni aparati',
            'first_aid' => 'Prva pomoć - ormarići',
            'miscellaneous' => 'Ostala ispitivanja',
            'categories' => 'Kategorije ispitivanja',

            'waste_organizations' => 'Organizacije otpada',
            'waste_types' => 'Vrste otpada',
            'onto_records' => 'ONTO obrasci',
            'waste_tracking_forms' => 'Prateći listovi',
            'monthly_reports' => 'Mjesečni izvještaj',

            'tests' => 'Testovi',
            'questions' => 'Pitanja',
            'answers' => 'Odgovori',
            'test_attempts' => 'Riješeni testovi',

            'work_tasks' => 'Radni zadaci',
        ];

        return collect($modules)
            ->map(fn ($module) => $labels[$module] ?? $module)
            ->values()
            ->all();
    }
}