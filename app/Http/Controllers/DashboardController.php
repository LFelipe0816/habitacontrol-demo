<?php

namespace App\Http\Controllers;

use App\Http\Resources\IncidentResource;
use App\Models\Charge;
use App\Models\Incident;
use App\Models\Payment;
use App\Models\Visitor;
use Inertia\Inertia;

class DashboardController extends Controller
{
    private const OVERDUE = ['Moroso', 'Legal'];

    public function __invoke()
    {
        $now = now();
        $billed = (float) Charge::whereBetween('due_date', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])->sum('amount');
        $collected = (float) Payment::where('created_at', '>=', $now->copy()->startOfMonth())->sum('amount');

        $openIncidents = Incident::with(['assignee', 'reporter', 'unit'])
            ->whereNotIn('status', ['Resuelta', 'Cerrada'])->latest()->limit(8)->get();

        return Inertia::render('Dashboard', [
            'summary' => [
                'billed_month' => $billed,
                'collected_month' => $collected,
                'collection_rate' => $billed > 0 ? (int) min(100, round($collected / $billed * 100)) : 0,
                'open_incidents' => Incident::whereNotIn('status', ['Resuelta', 'Cerrada'])->count(),
                'overdue_units' => Charge::whereIn('status', self::OVERDUE)->distinct('unit_id')->count('unit_id'),
                'visitors_today' => Visitor::whereDate('entered_at', $now->toDateString())->count(),
                'monthly' => $this->monthly(),
            ],
            'openIncidents' => IncidentResource::collection($openIncidents),
        ]);
    }

    /** Últimos 6 meses, en miles de RD$ (lo que rotula el gráfico). */
    private function monthly(): array
    {
        return collect(range(5, 0))->map(function ($ago) {
            $month = now()->startOfMonth()->subMonths($ago);
            $range = [$month->copy(), $month->copy()->endOfMonth()];

            return [
                'label' => ucfirst($month->translatedFormat('M')),
                'a' => (int) round(Charge::whereBetween('due_date', $range)->sum('amount') / 1000),
                'b' => (int) round(Payment::whereBetween('created_at', $range)->sum('amount') / 1000),
            ];
        })->all();
    }
}
