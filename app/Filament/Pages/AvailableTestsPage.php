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

    /**
     * ✅ Badge u navigaciji (broj dostupnih testova za usera)
     */
    public static function getNavigationBadge(): ?string
    {
        $q = Test::query();

        if (! Auth::user()?->isAdmin()) {
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

    /**
     * ✅ Filament view data
     */
    protected function getViewData(): array
    {
        abort_unless(Auth::check(), 401);

        $tests = $this->getTestsQuery()
            ->withCount('questions') // ✅ questions_count
            ->orderBy('naziv')
            ->get();

        return compact('tests');
    }

    protected function getTestsQuery(): Builder
    {
        $q = Test::query();

        // Admin vidi sve
        if (Auth::user()?->isAdmin()) {
            return $q;
        }

        // User vidi:
        // - globalne testove (user_id NULL)
        // - svoje privatne testove (user_id = Auth::id())
        return $q->where(function (Builder $qq) {
            $qq->whereNull('user_id')
                ->orWhere('user_id', Auth::id());
        });
    }
}