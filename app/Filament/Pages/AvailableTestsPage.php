<?php

namespace App\Filament\Pages;

use App\Models\Test;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use BackedEnum;

class AvailableTestsPage extends Page
{
    // ✅ Filament v4: view je NON-static
    protected string $view = 'filament.pages.available-tests-page';

    protected static string|UnitEnum|null $navigationGroup = 'Testiranje';
    protected static ?string $navigationLabel = 'Riješi testove';
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?int $navigationSort = 95;

    // ✅ Naslov stranice
    protected static ?string $title = 'Dostupni testovi';

    public $tests = [];

    public function mount(): void
    {
        abort_unless(Auth::check(), 401);

        $this->tests = $this->getTestsQuery()
            ->orderBy('naziv')
            ->get();
    }

    protected function getTestsQuery(): Builder
    {
        $q = Test::query();

        // Admin vidi sve
        if (Auth::user()?->isAdmin()) {
            return $q;
        }

        // ✅ User vidi:
        // - globalne testove (user_id NULL)
        // - svoje privatne testove (user_id = Auth::id())
        return $q->where(function (Builder $qq) {
            $qq->whereNull('user_id')
               ->orWhere('user_id', Auth::id());
        });
    }
}