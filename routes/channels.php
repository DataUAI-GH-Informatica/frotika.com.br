<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
 * Canal privado por usuário — usado pelas notificações broadcast do Laravel
 * (App\Models\User recebe em App.Models.User.{id}). Cada usuário só escuta o
 * próprio canal. É por aqui que avisamos "seu processamento terminou".
 */
Broadcast::channel('App.Models.User.{id}', static function (User $user, int|string $id): bool {
    return $user->getKey() === (int) $id;
});

/*
 * Canal privado por empresa — para eventos que interessam a todos os usuários
 * de uma empresa (ex.: importação de CT-e concluída). A autorização respeita o
 * isolamento multi-tenant: só entra quem tem acesso àquela Company.
 */
Broadcast::channel('company.{companyId}', static function (User $user, int|string $companyId): bool {
    return $user->companies()->whereKey((int) $companyId)->exists();
});
