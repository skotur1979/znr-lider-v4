<?php

namespace App\Filament\Resources\TestAttempts;

use App\Filament\Resources\TestAttempts\Pages;
use App\Models\TestAttempt;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;

class TestAttemptResource extends Resource
{
    protected static ?string $model = TestAttempt::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';
    protected static string|UnitEnum|null $navigationGroup = 'Testiranje';
    protected static ?string $navigationLabel = 'Rješeni testovi';
    protected static ?string $modelLabel = 'Rješeni test';
    protected static ?string $pluralModelLabel = 'Rješeni testovi';
    protected static ?int $navigationSort = 96;

    public static function canCreate(): bool { return false; }
    public static function canEdit(Model $record): bool { return false; }

    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();
        return $user && ($user->isAdmin() || (int) $record->user_id === (int) $user->id);
    }

    public static function canDeleteAny(): bool
    {
        return (bool) Auth::user()?->isAdmin();
    }

    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery()->with(['user', 'test']);

        if (! Auth::user()?->isAdmin()) {
            $q->where('user_id', Auth::id());
        }

        return $q;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('Korisnik'),
                TextColumn::make('test.naziv')->label('Naziv testa'),
                TextColumn::make('ime_prezime')->label('Ime i prezime'),
                TextColumn::make('radno_mjesto')->label('Radno mjesto'),
                TextColumn::make('datum_rodjenja')->label('Datum rođenja')->date('d.m.Y.'),
                TextColumn::make('bodovi_osvojeni')->label('Bodovi'),
                TextColumn::make('rezultat')->label('Rezultat (%)')->suffix('%'),
                IconColumn::make('prolaz')->label('Prolaz')->boolean(),
                TextColumn::make('created_at')->label('Datum slanja')->dateTime('d.m.Y H:i'),
                TextColumn::make('show_link')
    ->label('Prikaži')
    ->state(fn (TestAttempt $record) => 'Prikaži')
    ->url(fn (TestAttempt $record) => route('test-attempts.show', $record))
    ->openUrlInNewTab()
    ->color('primary'),

TextColumn::make('pdf_link')
    ->label('PDF')
    ->state(fn (TestAttempt $record) => 'PDF')
    ->url(fn (TestAttempt $record) => route('test-attempts.download', $record))
    ->openUrlInNewTab()
    ->color('warning'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
    Action::make('show')
        ->label('Prikaži')
        ->icon('heroicon-o-eye')
        ->url(fn (TestAttempt $record) => route('test-attempts.show', $record))
        ->openUrlInNewTab(),

    Action::make('pdf')
        ->label('PDF')
        ->icon('heroicon-o-document-text')
        ->url(fn (TestAttempt $record) => route('test-attempts.download', $record))
        ->openUrlInNewTab(),

    DeleteAction::make()
        ->label('Obriši')
        ->visible(fn (TestAttempt $record) =>
            auth()->user()?->isAdmin() || (int) $record->user_id === (int) auth()->id()
        )
        ->authorize(fn (TestAttempt $record) =>
            auth()->user()?->isAdmin() || (int) $record->user_id === (int) auth()->id()
        )
        ->requiresConfirmation()
        ->modalHeading('Obriši pokušaj testa')
        ->modalSubheading('Jeste li sigurni? Ova akcija je trajna.')
        ->successNotificationTitle('Pokušaj je obrisan.'),
])
            ->bulkActions([
    DeleteBulkAction::make()
        ->label('Obriši odabrane')
        ->visible(fn () => Auth::user()?->isAdmin()),
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        $q = static::getModel()::query();

        if (! Auth::user()?->isAdmin()) {
            $q->where('user_id', Auth::id());
        }

        return (string) $q->count();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTestAttempts::route('/'),
        ];
    }
}