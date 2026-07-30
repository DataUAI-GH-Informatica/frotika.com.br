<?php

declare(strict_types=1);

namespace App\Domain\Attachments\Support;

use App\Support\Format;

/**
 * Fonte única das regras de arquivo: FormRequest, Action e o texto de ajuda no
 * formulário leem daqui. Mudar o limite em `config/attachments.php` muda os
 * três de uma vez.
 */
final class AttachmentRules
{
    /**
     * @return list<string>
     */
    public static function allowedExtensions(): array
    {
        $extensions = config('attachments.allowed_extensions', ['pdf']);

        if (! is_array($extensions) || $extensions === []) {
            $extensions = ['pdf'];
        }

        return array_values(array_map(
            static fn (mixed $extension): string => mb_strtolower((string) $extension),
            $extensions,
        ));
    }

    public static function maxKilobytes(): int
    {
        return (int) config('attachments.max_size_kb', 10240);
    }

    public static function maxFiles(): int
    {
        return (int) config('attachments.max_files_per_upload', 10);
    }

    /**
     * @return list<string>
     */
    public static function fileRules(): array
    {
        return [
            'file',
            'mimes:'.implode(',', self::allowedExtensions()),
            'max:'.self::maxKilobytes(),
        ];
    }

    /**
     * @return list<string>
     */
    public static function collectionRules(): array
    {
        return ['array', 'max:'.self::maxFiles()];
    }

    /**
     * "PDF, JPG, PNG, WEBP ou XML" — para o texto de ajuda e a mensagem de erro.
     */
    public static function humanExtensions(): string
    {
        $extensions = array_map(mb_strtoupper(...), self::allowedExtensions());
        $last = array_pop($extensions);

        return $extensions === [] ? $last : implode(', ', $extensions).' ou '.$last;
    }

    public static function humanMaxSize(): string
    {
        return Format::fileSize(self::maxKilobytes() * 1024);
    }
}
