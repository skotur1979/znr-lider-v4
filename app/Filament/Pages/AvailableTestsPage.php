<?php

namespace App\Filament\Pages;

use App\Models\Test;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;
use BackedEnum;

class AvailableTestsPage extends Page
{
    protected string $view = 'filament.pages.available-tests-page';

    protected static string|UnitEnum|null $navigationGroup = 'Testiranje';
    protected static ?string $navigationLabel = 'Riješi testove';
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?int $navigationSort = 95;

    protected static ?string $title = 'Dostupni testovi';

    public static function getNavigationBadge(): ?string
    {
        abort_unless(Auth::check(), 401);

        $q = Test::query();

        if (! Auth::user()?->isSuperAdmin()) {
            $q->where(function (Builder $qq) {
                $qq->whereNull('user_id')
                    ->orWhere('user_id', Auth::id());
            });
        }

        return (string) $q->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    protected function getViewData(): array
    {
        abort_unless(Auth::check(), 401);

        $tests = $this->getTestsQuery()
            ->withCount('questions')
            ->orderBy('naziv')
            ->get();

        return compact('tests');
    }

    protected function getTestsQuery(): Builder
    {
        $q = Test::query();

        if (Auth::user()?->isSuperAdmin()) {
            return $q;
        }

        return $q->where(function (Builder $qq) {
            $qq->whereNull('user_id')
                ->orWhere('user_id', Auth::id());
        });
    }
}