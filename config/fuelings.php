<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Disco da planilha de importação
    |--------------------------------------------------------------------------
    |
    | A planilha enviada fica na área privada do grupo até o job terminar de
    | processá-la, e é apagada em seguida — o dado que importa já virou
    | abastecimento. Caminho: grupos/{group_uuid}/abastecimentos-import/{lote}.
    |
    */

    'import_storage_disk' => env('FUELING_IMPORT_STORAGE_DISK', 'local'),
];
