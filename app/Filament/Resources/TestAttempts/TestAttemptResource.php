<?php

namespace App\Filament\Resources\TestAttempts;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\TestAttempts\Pages;
use App\Models\TestAttempt;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class TestAttemptResource extends BaseResource
{
    protected static ?string $model = TestAttempt::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';
    protected static \UnitEnum|string|null $navigationGroup = 'Testiranje';
    protected static ?string $navigationLabel = 'Rješeni testovi';
    protected static ?string $modelLabel = 'Rješeni test';
    protected static ?string $pluralModelLabel = 'Rješeni testovi';
    protected static ?int $navigationSort = 96;

    protected static function getModuleKey(): ?string
    {
        return 'test_attempts';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();

        return $user
            && ($user->isSuperAdmin() || (int) $record->user_id === (int) $user->ownerId());
    }

    public static function canDeleteAny(): bool
    {
        return (bool) Auth::user()?->isSuperAdmin();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['user', 'test']);

        if (Auth::user()?->isSuperAdmin()) {
            return $query;
        }

        return $query->where('user_id', Auth::user()?->ownerId());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Korisnik')
                    ->searchable(),

                TextColumn::make('test.naziv')
                    ->label('Naziv testa')
                    ->searchable(),

                TextColumn::make('ime_prezime')
                    ->label('Ime i prezime')
                    ->searchable(),

                TextColumn::make('radno_mjesto')
                    ->label('Radno mjesto')
                    ->searchable(),

                TextColumn::make('datum_rodjenja')
                    ->label('Datum rođenja')
                    ->date('d.m.Y.'),

                TextColumn::make('bodovi_osvojeni')
                    ->label('Bodovi')
                    ->sortable(),

                TextColumn::make('rezultat')
                    ->label('Rezultat (%)')
                    ->suffix('%')
                    ->sortable(),

                IconColumn::make('prolaz')
                    ->label('Prolaz')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Datum slanja')
                    ->dateTime('d.m.Y. H:i')
                    ->sortable(),
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
                    ->visible(fn (TestAttempt $record) => static::canDelete($record))
                    ->authorize(fn (TestAttempt $record) => static::canDelete($record))
                    ->requiresConfirmation()
                    ->modalHeading('Obriši pokušaj testa')
                    ->modalDescription('Jeste li sigurni? Ova akcija je trajna.')
                    ->successNotificationTitle('Pokušaj je obrisan.'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Obriši odabrane')
                    ->visible(fn () => Auth::user()?->isSuperAdmin()),
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::query();

        if (! Auth::user()?->isSuperAdmin()) {
            $query->where('user_id', Auth::user()?->ownerId());
        }

        return (string) $query->count();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTestAttempts::route('/'),
        ];
    }
}
