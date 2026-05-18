<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                        Forms\Components\Select::make('version')
                            ->label('Versão')
                            ->options([
                                'jogador' => 'Jogador',
                                'torcedor' => 'Torcedor',
                                'retro' => 'Retrô',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->columnSpanFull(),
                    ])->columns(3),
                
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
                Tables\Columns\TextColumn::make('version')
                    ->label('Versão')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'jogador' => 'warning',
                        'torcedor' => 'success',
                        'retro' => 'danger',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('variants_sum_stock_quantity')
                    ->label('Estoque Total')
                    ->sum('variants', 'stock_quantity')
                    ->sortable(),
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
                //
            ])
            ->actions([
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
