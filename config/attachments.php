<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Disco dos anexos
    |--------------------------------------------------------------------------
    |
    | Anexo é documento de cliente: nota fiscal, cupom do posto, foto do
    | painel. Fica em disco privado e só sai por rota autenticada.
    | Caminho relativo: grupos/{group_uuid}/anexos/{tipo}/{id}/{uuid}.{ext}
    |
    */

    'storage_disk' => env('ATTACHMENT_STORAGE_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Extensões aceitas e limite de tamanho
    |--------------------------------------------------------------------------
    |
    | A validação usa a regra `mimes`, que confere o conteúdo do arquivo e não
    | só o nome. O limite é por arquivo, em kilobytes.
    |
    */

    'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'xml'],

    'max_size_kb' => (int) env('ATTACHMENT_MAX_SIZE_KB', 10240),

    /*
    |--------------------------------------------------------------------------
    | Máximo de arquivos por envio
    |--------------------------------------------------------------------------
    */

    'max_files_per_upload' => (int) env('ATTACHMENT_MAX_FILES_PER_UPLOAD', 10),
];
