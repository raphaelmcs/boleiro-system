<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public const GENDER_OPTIONS = [
        'masculino' => 'Masculino',
        'feminino' => 'Feminino',
        'unisex' => 'Unisex',
    ];

    public const VERSION_OPTIONS = [
        'jogador' => 'Jogador',
        'torcedor' => 'Torcedor',
        'retro' => 'Retrô',
        'kit_infantil' => 'Kit Infantil',
    ];

    protected $fillable = [
        'team_name',
        'season',
        'gender',
        'version',
        'description',
    ];

    public static function genderOptions(): array
    {
        return self::GENDER_OPTIONS;
    }

    public static function versionOptions(): array
    {
        return self::VERSION_OPTIONS;
    }

    public static function genderLabel(?string $gender): string
    {
        return self::GENDER_OPTIONS[$gender] ?? 'Não informado';
    }

    public static function versionLabel(?string $version): string
    {
        if ($version === null || $version === '') {
            return 'Não informado';
        }

        return self::VERSION_OPTIONS[$version] ?? ucfirst(str_replace('_', ' ', (string) $version));
    }

    public function getGenderLabelAttribute(): string
    {
        return self::genderLabel($this->gender);
    }

    public function getVersionLabelAttribute(): string
    {
        return self::versionLabel($this->version);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function salesOrderItems()
    {
        return $this->hasManyThrough(SalesOrderItem::class, ProductVariant::class);
    }
}
