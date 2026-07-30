<?php

declare(strict_types=1);

namespace App\Domain\Attachments\Enums;

use App\Domain\Fuelings\Models\Fueling;
use App\Domain\Trips\Models\CteDocument;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Registro único do que aceita anexo. Adicionar manutenção ou lançamento
 * financeiro à lista é um case novo aqui — rota, caminho de arquivo e rótulo
 * saem daqui, não de string espalhada pelos controllers.
 */
enum AttachableType: string
{
    case Fueling = 'fueling';
    case CteDocument = 'cte_document';

    public function label(): string
    {
        return match ($this) {
            self::Fueling => 'Abastecimento',
            self::CteDocument => 'CT-e',
        };
    }

    /**
     * Segmento pt-BR usado na URL e no caminho dentro do disco.
     */
    public function slug(): string
    {
        return match ($this) {
            self::Fueling => 'abastecimentos',
            self::CteDocument => 'ct-e',
        };
    }

    /**
     * @return class-string<Model>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::Fueling => Fueling::class,
            self::CteDocument => CteDocument::class,
        };
    }

    public static function fromSlug(string $slug): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->slug() === $slug) {
                return $case;
            }
        }

        return null;
    }

    public static function forModel(Model $model): self
    {
        foreach (self::cases() as $case) {
            if ($model::class === $case->modelClass()) {
                return $case;
            }
        }

        throw new InvalidArgumentException($model::class.' não aceita anexos.');
    }
}
