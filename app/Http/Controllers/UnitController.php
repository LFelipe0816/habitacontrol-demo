<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Payment;
use App\Models\ResidentRequest;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class UnitController extends Controller
{
    /** Ficha administrativa del apartamento: personas, contrato, estado de cuenta e historial. */
    public function show(Request $request, Unit $unit)
    {
        Gate::authorize('view', $unit);
        $internal = Gate::allows('viewInternal', $unit);

        $unit->load(['community:id,name', 'block:id,name', 'building:id,name', 'people', 'leases' => fn ($q) => $q->latest('ends_on')->limit(1)]);
        $peopleIds = $unit->people->pluck('id');

        $charges = $unit->charges()->withSum('payments', 'amount')->orderByDesc('due_date')->limit(6)->get();
        $payments = Payment::where('unit_id', $unit->id)->orWhereHas('charge', fn ($q) => $q->where('unit_id', $unit->id))
            ->latest()->limit(5)->get();

        return Inertia::render('Units/Show', [
            'unit' => [
                'id' => $unit->id, 'code' => $unit->code, 'number' => $unit->apartment_number ?? $unit->code, 'floor' => $unit->floor,
                'community' => ['id' => $unit->community->id, 'name' => $unit->community->name],
                'block' => $unit->block?->name, 'building' => $unit->building?->name,
                'occupancy' => $unit->occupancy, 'balance' => $unit->balance, 'maintenance_fee' => $unit->maintenance_fee,
                'parking' => $unit->parking, 'move_in_date' => $unit->move_in_date?->toDateString(),
                'emergency_contact' => $unit->emergency_contact, 'vehicles' => $unit->vehicles ?? [],
                'notes' => $internal ? $unit->notes : null,
            ],
            'people' => $unit->people->map(fn ($p) => [
                'id' => $p->id, 'name' => $p->name, 'relation' => $p->pivot->relation, 'document_id' => $p->document_id, 'phone' => $p->phone,
            ]),
            'lease' => $unit->leases->first()?->only(['starts_on', 'ends_on', 'monthly_rent', 'deposit', 'authorized_by']),
            'charges' => $charges->map(fn ($c) => [
                'id' => $c->id, 'concept' => $c->concept, 'amount' => $c->amount, 'due_date' => $c->due_date->toDateString(), 'status' => $c->status,
                'balance' => max(0, $c->amount - (float) $c->payments_sum_amount),
            ]),
            'payments' => $payments->map(fn ($p) => ['id' => $p->id, 'amount' => $p->amount, 'method' => $p->method, 'reference' => $p->reference, 'date' => $p->created_at->toDateString()]),
            // Quejas contra la unidad y reportes que hicieron sus habitantes.
            'complaints' => $this->incidents(Incident::where('unit_id', $unit->id)),
            'reports' => $this->incidents(Incident::whereIn('reporter_id', $peopleIds)->where(fn ($q) => $q->whereNull('unit_id')->orWhere('unit_id', '!=', $unit->id))),
            'requests' => ResidentRequest::where('unit_id', $unit->id)->latest()->limit(4)->get()
                ->map(fn ($r) => ['id' => $r->id, 'title' => $r->title, 'status' => $r->status, 'response' => $r->response]),
            'can' => ['internal' => $internal],
        ]);
    }

    private function incidents($query)
    {
        return $query->latest()->limit(5)->get(['id', 'code', 'title', 'status', 'priority'])
            ->map(fn ($i) => ['id' => $i->code, 'title' => $i->title, 'status' => $i->status, 'priority' => $i->priority]);
    }
}
