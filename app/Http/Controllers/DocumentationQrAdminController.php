<?php

namespace App\Http\Controllers;

use App\Filament\Resources\DocumentationItems\DocumentationItemResource;
use App\Models\DocumentationItem;
use App\Services\DocumentationQrService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;

class DocumentationQrAdminController extends Controller
{
    public function show(
        DocumentationItem $documentationItem,
        DocumentationQrService $qrService
    ) {
        $this->authorizeDocumentation(
            $documentationItem,
            'view'
        );

        $qrCode =
            $qrService->getOrCreate(
                $documentationItem,
                auth()->user()
            );

        $svg =
            $qrService->svg(
                $qrCode,
                500
            );

        $publicUrl =
            $qrService->publicUrl(
                $qrCode
            );

        return view(
            'qr.documentation-admin',
            compact(
                'documentationItem',
                'qrCode',
                'svg',
                'publicUrl'
            )
        );
    }

    public function regenerate(
        DocumentationItem $documentationItem,
        DocumentationQrService $qrService
    ): RedirectResponse {
        $this->authorizeDocumentation(
            $documentationItem,
            'update'
        );

        $qrCode =
            $qrService->getOrCreate(
                $documentationItem,
                auth()->user()
            );

        $qrService->regenerate(
            $qrCode
        );

        return redirect()
            ->route(
                'documentation.qr.admin',
                [
                    'documentationItem' =>
                        $documentationItem,
                ]
            )
            ->with(
                'qr_success',
                'QR kod je regeneriran. Stari QR kod više nije važeći.'
            );
    }

    public function deactivate(
        DocumentationItem $documentationItem,
        DocumentationQrService $qrService
    ): RedirectResponse {
        $this->authorizeDocumentation(
            $documentationItem,
            'update'
        );

        $qrCode =
            $qrService->getOrCreate(
                $documentationItem,
                auth()->user()
            );

        $qrService->deactivate(
            $qrCode
        );

        return redirect()
            ->route(
                'documentation.qr.admin',
                [
                    'documentationItem' =>
                        $documentationItem,
                ]
            )
            ->with(
                'qr_success',
                'QR kod je deaktiviran.'
            );
    }

    public function activate(
        DocumentationItem $documentationItem,
        DocumentationQrService $qrService
    ): RedirectResponse {
        $this->authorizeDocumentation(
            $documentationItem,
            'update'
        );

        $qrCode =
            $qrService->getOrCreate(
                $documentationItem,
                auth()->user()
            );

        $qrService->activate(
            $qrCode
        );

        return redirect()
            ->route(
                'documentation.qr.admin',
                [
                    'documentationItem' =>
                        $documentationItem,
                ]
            )
            ->with(
                'qr_success',
                'QR kod je ponovno aktiviran.'
            );
    }

    public function downloadPdf(
        DocumentationItem $documentationItem,
        DocumentationQrService $qrService
    ) {
        $this->authorizeDocumentation(
            $documentationItem,
            'view'
        );

        $qrCode =
            $qrService->getOrCreate(
                $documentationItem,
                auth()->user()
            );

        $svg =
            $qrService->svg(
                $qrCode,
                1000
            );

        $qrDataUri =
            'data:image/svg+xml;base64,'
            . base64_encode($svg);

        $pdf = Pdf::loadView(
            'pdf.documentation-qr-label',
            [
                'documentationItem' =>
                    $documentationItem,

                'qrCode' =>
                    $qrCode,

                'qrDataUri' =>
                    $qrDataUri,
            ]
        )
            ->setPaper(
                'a4',
                'portrait'
            )
            ->setOptions([
                'isHtml5ParserEnabled' =>
                    true,

                'isRemoteEnabled' =>
                    false,

                'defaultFont' =>
                    'DejaVu Sans',

                'dpi' =>
                    150,
            ]);

        return $pdf->download(
            'qr-dokumentacija-'
            . $documentationItem->id
            . '.pdf'
        );
    }

    protected function authorizeDocumentation(
        DocumentationItem $documentationItem,
        string $permission
    ): void {
        $user = auth()->user();

        abort_unless(
            $user,
            403
        );

        if (
            ! $user->isSuperAdmin()
        ) {
            abort_unless(
                (int) $documentationItem->user_id
                    ===
                (int) $user->ownerId(),
                404
            );
        }

        abort_unless(
            DocumentationItemResource
                ::ensureModulePermission(
                    $permission
                ),
            403
        );
    }
}