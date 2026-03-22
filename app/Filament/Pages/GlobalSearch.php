<?php

namespace App\Filament\Pages;

use App\Services\GlobalSearchService;
use Filament\Pages\Page;

class GlobalSearch extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-magnifying-glass';
    protected static ?string $navigationLabel = 'Globalna pretraga';
    protected static ?string $title = 'Globalna pretraga';
    protected static string|\UnitEnum|null $navigationGroup = 'Upravljanje';
    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.global-search';

    public string $query = '';

    public array $results = [
        'employees' => [],
        'machines' => [],
        'fires' => [],
        'miscellaneous' => [],
        'chemicals' => [],
    ];

    public function mount(): void
    {
        $this->query = (string) request()->query('q', '');
        $this->runSearch();
    }

    public function updatedQuery(): void
    {
        $this->runSearch();
    }

    protected function runSearch(): void
    {
        $this->results = app(GlobalSearchService::class)->search($this->query);
    }

    public function getTotalResultsProperty(): int
    {
        return collect($this->results)->flatten(1)->count();
    }

    public function highlight(string $text): string
    {
        $query = trim($this->query);

        if ($query === '') {
            return e($text);
        }

        return preg_replace(
            '/' . preg_quote($query, '/') . '/iu',
            '<mark class="znr-mark">$0</mark>',
            e($text)
        );
    }
}