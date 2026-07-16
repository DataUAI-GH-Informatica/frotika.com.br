<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Tenancy\Models\Company;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetTenantContext
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $this->tenantContext->runWithoutTenant(static fn (): Response => $next($request));
        }

        $sessionCompanyId = $request->hasSession() ? $request->session()->get('current_company_id') : null;
        $companyId = $sessionCompanyId ?? $user->getAttribute('current_company_id');

        if ($companyId === null) {
            return $this->tenantContext->runWithoutTenant(static fn (): Response => $next($request));
        }

        $company = Company::query()->find($companyId);

        if ($company === null) {
            abort(403, 'Empresa selecionada não encontrada.');
        }

        $hasAccess = $user->companies()->whereKey($company->getKey())->exists();

        if (! $hasAccess) {
            abort(403, 'Usuário sem acesso à empresa selecionada.');
        }

        return $this->tenantContext->runFor($company, static fn (): Response => $next($request));
    }
}
