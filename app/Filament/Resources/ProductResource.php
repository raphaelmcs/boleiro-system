<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $modelLabel = 'Camisa';
    protected static ?string $pluralModelLabel = 'Camisas (Catálogo)';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalhes do Produto')
                    ->schema([
                        Forms\Components\TextInput::make('team_name')
                            ->label('Time/Seleção')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('season')
                            ->label('Temporada')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('gender')
                            ->label('Gênero')
                            ->options(Product::genderOptions())
                            ->required(),
                        Forms\Components\Select::make('version')
                            ->label('Versão')
                            ->options(Product::versionOptions())
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->columnSpanFull(),
                    ])->columns(4),
                
                Forms\Components\Section::make('Variações e Estoque')
                    ->schema([
                        Forms\Components\Repeater::make('variants')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('size')
                                    ->label('Tamanho')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('stock_quantity')
                                    ->label('Quantidade em Estoque')
                                    ->required()
                                    ->numeric()
                                    ->default(0),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel('Adicionar Tamanho'),
                    ])
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('variants');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('team_name')
                    ->label('Time')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('season')
                    ->label('Temporada')
                    ->searchable(),
                Tables\Columns\TextColumn::make('gender')
                    ->label('Gênero')
                    ->formatStateUsing(fn (?string $state): string => Product::genderLabel($state))
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'masculino' => 'info',
                        'feminino' => 'danger',
                        'unisex' => 'success',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('version')
                    ->label('Versão')
                    ->formatStateUsing(fn (?string $state): string => Product::versionLabel($state))
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'jogador' => 'warning',
                        'torcedor' => 'success',
                        'retro' => 'danger',
                        'kit_infantil' => 'info',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('variants_sum_stock_quantity')
                    ->label('Estoque Total')
                    ->sum('variants', 'stock_quantity')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state <= 0 ? 'danger' : ($state <= 10 ? 'warning' : 'success')),
                Tables\Columns\TextColumn::make('stock_breakdown')
                    ->label('Estoque por Tamanho')
                    ->getStateUsing(function (Product $record): string {
                        return $record->variants->map(function ($variant) {
                            $indicator = $variant->stock_quantity <= 0 ? '🔴' : ($variant->stock_quantity <= 3 ? '🟡' : '🟢');
                            return "{$indicator} {$variant->size}: {$variant->stock_quantity}";
                        })->implode(' | ');
                    })
                    ->wrap(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('gender')
                    ->label('Gênero')
                    ->options(Product::genderOptions()),
                Tables\Filters\SelectFilter::make('version')
                    ->label('Versão')
                    ->options([
                        'kit_infantil' => Product::versionLabel('kit_infantil'),
                        'torcedor' => Product::versionLabel('torcedor'),
                        'jogador' => Product::versionLabel('jogador'),
                    ]),
                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Tamanho Esgotado (0)')
                    ->query(fn (Builder $query): Builder => $query->whereHas('variants', fn (Builder $q) => $q->where('stock_quantity', '<=', 0))),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Tamanho Acabando (1 a 3)')
                    ->query(fn (Builder $query): Builder => $query->whereHas('variants', fn (Builder $q) => $q->whereBetween('stock_quantity', [1, 3]))),
            ])
            ->actions([
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicar')
                    ->icon('heroicon-m-document-duplicate')
                    ->color('gray')
                    ->modalHeading('Duplicar camisa')
                    ->modalSubmitActionLabel('Duplicar camisa')
                    ->form([
                        Forms\Components\TextInput::make('team_name')
                            ->label('Nome da nova camisa no estoque')
                            ->required()
                            ->maxLength(255)
                            ->rules([
                                fn (Product $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                                    if (trim((string) $value) === trim($record->team_name)) {
                                        $fail('Informe um nome diferente da camisa original.');
                                    }
                                },
                            ]),
                    ])
                    ->action(function (array $data, Product $record): void {
                        DB::transaction(function () use ($data, $record): void {
                            $newProduct = Product::create([
                                'team_name' => trim($data['team_name']),
                                'season' => $record->season,
                                'gender' => $record->gender,
                                'version' => $record->version,
                                'description' => $record->description,
                            ]);

                            foreach ($record->variants as $variant) {
                                $newProduct->variants()->create([
                                    'size' => $variant->size,
                                    'stock_quantity' => $variant->stock_quantity,
                                ]);
                            }
                        });
                    })
                    ->successNotificationTitle('Camisa duplicada'),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
