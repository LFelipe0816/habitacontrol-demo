<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommunityResource;
use App\Http\Resources\IncidentResource;
use App\Http\Resources\UnitResource;
use App\Models\Community;
use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CommunityController extends Controller
{
    private const CLOSED = ['Resuelta', 'Cerrada'];

    private const LOCATION_TYPES = [
        'apartment' => 'Apartamento', 'building' => 'Edificio', 'block' => 'Manzana', 'street' => 'Calle', 'common_area' => 'Área común',
    ];

    /** Portafolio: todos los residenciales que administra la empresa del usuario. */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Community::class);

        $communities = Community::visibleTo($request->user())
            ->with('plan')
            ->withCount(['units as loaded_units_count', 'occupiedUnits', 'unitsWithBalance', 'openIncidents'])
            ->withSum('units', 'balance')
            ->orderBy('name')->get();

        return Inertia::render('Communities/Index', [
            'communities' => CommunityResource::collection($communities),
            'totals' => [
                'communities' => $communities->count(),
                'contracted_units' => $communities->sum('units_count'),
                'balance' => (float) $communities->sum('units_sum_balance'),
                'open_incidents' => $communities->sum('open_incidents_count'),
            ],
        ]);
    }

    /** Estructura de un residencial: manzana → edificio → apartamentos, más calles e incidencias. */
    public function show(Request $request, Community $community)
    {
        Gate::authorize('view', $community);

        $community->loadMissing('plan')->loadCount(['units as loaded_units_count', 'occupiedUnits', 'unitsWithBalance', 'openIncidents'])->loadSum('units', 'balance');
        // `units_count` es la cifra contratada (columna); `loaded_units_count` son las unidades realmente cargadas.
        $blocks = $community->blocks()->withCount(['buildings', 'units', 'incidents'])->get();
        $block = $blocks->firstWhere('id', $request->integer('manzana')) ?? $blocks->first();

        $buildings = $block ? $block->buildings()->withCount('units')->get() : collect();
        // Si no se elige edificio, se abre el primero que ya tenga apartamentos cargados.
        $building = $buildings->firstWhere('id', $request->integer('edificio')) ?? $buildings->firstWhere('units_count', '>', 0) ?? $buildings->first();
        $units = $building ? $building->units()->get()->sortBy('apartment_number', SORT_NATURAL)->values() : collect();

        return Inertia::render('Communities/Show', [
            // No usar la clave `community`: la comparte HandleInertiaRequests para la barra lateral.
            'residential' => new CommunityResource($community),
            'blocks' => $blocks->map(fn ($b) => [
                'id' => $b->id, 'code' => $b->code, 'name' => $b->name,
                'buildings' => $b->buildings_count, 'units' => $b->units_count, 'incidents' => $b->incidents_count,
            ]),
            'buildings' => $buildings->map(fn ($b) => ['id' => $b->id, 'code' => $b->code, 'name' => $b->name, 'units' => $b->units_count]),
            'selected' => ['block' => $block?->id, 'building' => $building?->id],
            'units' => UnitResource::collection($units),
            'streets' => $community->streets()->with('block:id,name')->orderBy('name')->get()->map(fn ($s) => [
                'id' => $s->id, 'code' => $s->code, 'name' => $s->name, 'reference' => $s->reference,
                'block' => $s->block?->name ?? 'General', 'lighting_points' => $s->lighting_points,
            ]),
            'incidents' => IncidentResource::collection(
                Incident::where('community_id', $community->id)->with(['assignee', 'reporter', 'unit'])->latest()->limit(8)->get()
            ),
            'locationTypes' => self::LOCATION_TYPES,
        ]);
    }
}
