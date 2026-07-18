<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Billing\Enums\GroupLicenseStatus;
use App\Domain\Billing\Models\GroupLicense;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureGroupLicenseAllowsWrite
{
    /**
     * @var list<string>
     */
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    /**
     * @var list<string>
     */
    private const EXEMPT_ROUTE_PATTERNS = [
        'logout',
        'tenancy.switch-company',
        'platform.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->getMethod(), self::SAFE_METHODS, true)) {
            return $next($request);
        }

        if ($request->routeIs(...self::EXEMPT_ROUTE_PATTERNS)) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user instanceof User || $user->current_group_id === null) {
            return $next($request);
        }

        $license = GroupLicense::query()
            ->where('group_id', $user->current_group_id)
            ->first();

        if ($license === null) {
            return $next($request);
        }

        $status = $license->status;

        if (in_array($status, [GroupLicenseStatus::Active, GroupLicenseStatus::Trialing], true)) {
            return $next($request);
        }

        $message = 'Operações de escrita estão bloqueadas para o grupo até a quitação do boleto da licença.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'status' => 'group_license_blocked',
                'license_status' => $status->value,
            ], 423);
        }

        return redirect()
            ->route('dashboard')
            ->with('warning', $message);
    }
}
