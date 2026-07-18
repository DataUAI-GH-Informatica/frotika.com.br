<?php

declare(strict_types=1);

return [
    /*
     * Conta usada como administradora da plataforma (dono do sistema Frotika).
     * O PlatformAdminSeeder cria essa conta com is_platform_admin = true e um
     * Group com type = 'platform'. Ajuste via .env para apontar para a sua conta.
     */
    'admin_email' => env('PLATFORM_ADMIN_EMAIL', 'admin@frotika.com.br'),
    'admin_name' => env('PLATFORM_ADMIN_NAME', 'Administrador Frotika'),
    'admin_password' => env('PLATFORM_ADMIN_PASSWORD', 'secret-1234'),
    'group_name' => env('PLATFORM_GROUP_NAME', 'Plataforma Frotika'),
];
