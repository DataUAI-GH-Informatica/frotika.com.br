<?php

declare(strict_types=1);

namespace App\Domain\Fuelings\Actions;

use App\Domain\Fuelings\Models\Fueling;

/**
 * Cálculo de consumo (km/l) — regra 8 do projeto, implementada exatamente como
 * a seção 5.4 do blueprint manda:
 *
 * - só fecha entre dois abastecimentos full_tank do MESMO tanque;
 * - Arla 32 e óleo NUNCA entram (nem nos litros, nem abrem/fecham intervalo);
 * - litros do intervalo = soma dos combustíveis posteriores ao último tanque
 *   cheio, até o atual (inclusive);
 * - sem tanque cheio anterior → km_per_liter = null (nunca zero).
 *
 * Recalcula todos os abastecimentos do veículo: inserir/editar um lançamento no
 * meio muda o intervalo do próximo, então recomputar a série inteira é a forma
 * mais simples de nunca deixar um número velho na tela. Grava com saveQuietly
 * para não reentrar no observer.
 *
 * Assume o contexto de tenant já aberto (chamado de dentro das Actions).
 */
final class FuelConsumptionCalculator
{
    public function recalculateForVehicle(int $vehicleId): void
    {
        $fuelings = Fueling::query()
            ->where('vehicle_id', $vehicleId)
            ->orderBy('fueled_at')
            ->orderBy('id')
            ->get();

        /** @var array<string, array{odometer: int|null, liters: float}> $tankState */
        $tankState = [];

        foreach ($fuelings as $fueling) {
            $product = $fueling->product;
            $tankKey = $fueling->tank->value;

            $kmSinceLast = null;
            $kmPerLiter = null;

            // Arla 32 e óleo não participam: consumo nulo e intervalo intacto.
            if ($product->isFuel()) {
                $state = $tankState[$tankKey] ?? ['odometer' => null, 'liters' => 0.0];

                $state['liters'] += (float) $fueling->liters;

                if ($fueling->full_tank) {
                    if ($state['odometer'] !== null) {
                        $km = (int) $fueling->odometer - $state['odometer'];

                        if ($km > 0 && $state['liters'] > 0.0) {
                            $kmSinceLast = $km;
                            $kmPerLiter = round($km / $state['liters'], 3);
                        }
                    }

                    // Tanque cheio fecha o intervalo e abre o próximo.
                    $state['odometer'] = (int) $fueling->odometer;
                    $state['liters'] = 0.0;
                }

                $tankState[$tankKey] = $state;
            }

            $fueling->forceFill([
                'km_since_last' => $kmSinceLast,
                'km_per_liter' => $kmPerLiter,
            ])->saveQuietly();
        }
    }
}
