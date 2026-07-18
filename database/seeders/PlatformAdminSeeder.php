<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Billing\Enums\GroupLicenseStatus;
use App\Domain\Billing\Models\GroupLicense;
use App\Domain\Tenancy\Enums\GroupType;
use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class PlatformAdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $email = (string) config('platform.admin_email');

            $user = User::query()->firstOrNew([
                'email' => $email,
            ]);

            $user->forceFill([
                'name' => (string) config('platform.admin_name'),
                'email' => $email,
                'email_verified_at' => now(),
                'password' => Hash::make((string) config('platform.admin_password')),
                'is_platform_admin' => true,
            ])->save();

            $group = Group::query()->firstOrNew([
                'type' => GroupType::Platform->value,
                'owner_user_id' => $user->getKey(),
            ]);

            $group->forceFill([
                'uuid' => $group->uuid ?: Str::uuid()->toString(),
                'name' => (string) config('platform.group_name'),
                'type' => GroupType::Platform->value,
                'status' => 'active',
            ])->save();

            $company = Company::query()->firstOrNew([
                'cnpj' => '00000000000191',
            ]);

            $company->forceFill([
                'group_id' => $group->getKey(),
                'uuid' => $company->uuid ?: Str::uuid()->toString(),
                'legal_name' => 'FROTIKA PLATAFORMA',
                'trade_name' => 'Frotika',
                'tax_regime' => 'simples',
            ])->save();

            $group->users()->syncWithoutDetaching([
                $user->getKey() => [
                    'role' => 'owner',
                    'invited_by' => null,
                    'joined_at' => now(),
                ],
            ]);

            $company->users()->syncWithoutDetaching([$user->getKey()]);

            $group->forceFill([
                'primary_company_id' => $company->getKey(),
            ])->save();

            $user->forceFill([
                'current_group_id' => $group->getKey(),
                'current_company_id' => $company->getKey(),
            ])->save();

            $license = GroupLicense::query()->firstOrNew([
                'group_id' => $group->getKey(),
            ]);

            $license->forceFill([
                'group_id' => $group->getKey(),
                'status' => GroupLicenseStatus::Active,
                'trial_starts_at' => now(),
                'trial_ends_at' => null,
                'activated_at' => now(),
                'suspended_at' => null,
                'monthly_price_cents' => 0,
                'notes' => 'Licença interna da plataforma Frotika.',
            ])->save();
        });
    }
}
