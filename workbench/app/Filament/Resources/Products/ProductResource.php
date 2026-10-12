<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Products;

use Asignua\FilamentToggleIconColumn\Columns\ToggleIconColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Workbench\App\Filament\Resources\Products\Pages\ListProducts;
use Workbench\App\Models\Product;

/**
 * The catalogue shown in the README screenshots.
 */
class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
            TextInput::make('sku')->required(),
            Toggle::make('is_published'),
            Toggle::make('is_featured'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->weight('medium'),
                TextColumn::make('sku')->fontFamily('mono')->color('gray'),
                TextColumn::make('price_cents')
                    ->label('Price')
                    ->formatStateUsing(fn (int $state): string => '$'.number_format($state / 100, 2))
                    ->alignEnd(),
                ToggleIconColumn::make('is_published')
                    ->label('Published')
                    ->onIcon('heroicon-s-check-circle')
                    ->offIcon('heroicon-o-minus-circle')
                    ->onColor('success')
                    ->offColor('gray')
                    ->size(IconSize::Large)
                    ->stateTooltip()
                    ->disabled(fn (Product $record): bool => $record->is_archived),
                ToggleIconColumn::make('is_featured')
                    ->label('Featured')
                    ->onIcon('heroicon-s-star')
                    ->offIcon('heroicon-o-star')
                    ->onColor('warning')
                    ->offColor('gray')
                    ->size(IconSize::Large)
                    ->hoverColor('warning')
                    ->stateTooltip()
                    ->disabled(fn (Product $record): bool => $record->is_archived),
                ToggleIconColumn::make('is_archived')
                    ->label('Archived')
                    ->onIcon('heroicon-s-archive-box')
                    ->offIcon('heroicon-o-archive-box')
                    ->onColor('danger')
                    ->offColor('gray')
                    ->size(IconSize::Large)
                    ->stateTooltip(),
            ])
            ->defaultSort('id');
    }

    public static function getPages(): array
    {
        return ['index' => ListProducts::route('/')];
    }
}
