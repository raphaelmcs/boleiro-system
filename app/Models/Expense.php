<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    public const CATEGORY_OPTIONS = [
        'taxas' => 'Taxas',
        'correios' => 'Correios',
        'emprestimos' => 'Empréstimos',
        'material_loja' => 'Material para loja',
        'outras' => 'Outras',
    ];

    protected $fillable = [
        'expense_date',
        'category',
        'description',
        'amount',
        'notes',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public static function categoryOptions(): array
    {
        return self::CATEGORY_OPTIONS;
    }

    public static function categoryLabel(?string $category): string
    {
        return self::CATEGORY_OPTIONS[$category] ?? 'Não informado';
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::categoryLabel($this->category);
    }
}
