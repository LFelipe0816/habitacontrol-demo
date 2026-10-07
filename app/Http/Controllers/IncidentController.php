<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncidentEvidenceRequest;
use App\Http\Requests\StoreIncidentRequest;
use App\Http\Requests\UpdateIncidentDetailRequest;
use App\Http\Resources\IncidentDetailResource;
use App\Http\Resources\IncidentResource;
use App\Models\Community;
use App\Models\Incident;
use App\Models\Unit;
use App\Models\User;
use App\Services\IncidentWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class IncidentController extends Controller
{
    public function __construct(private IncidentWorkflow $workflow) {}

    public function index(Request $request)
    {
        $view = $request->string('view', 'abiertas')->toString();
        $base = Incident::visibleTo($request->user());

        $incidents = (clone $base)->with(['assignee', 'reporter', 'unit', 'attachments'])
            ->when($view === 'abiertas', fn ($q) => $q->whereNotIn('status', Incident::CLOSED_STATES))
            ->when($view === 'alta', fn ($q) => $q->where('priority', 'Alta'))
            ->when($request->search, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%$s%")->orWhere('code', 'like', "%$s%")->orWhere('location', 'like', "%$s%")))
            ->latest()->get();

        return Inertia::render('Incidents/Index', [
            'incidents' => IncidentResource::collection($incidents),
            'filters' => $request->only('view', 'search'),
            'stats' => $this->stats($base),
        ]);
    }

    /** Indicadores de Inciden 360 sobre todos los casos visibles, sin importar el filtro de la lista. */
    private function stats($base): array
    {
        $closed = fn ($q) => $q->whereIn('status', Incident::CLOSED_STATES);
        $withFile = fn (string $collection) => fn ($q) => $q->where('collection', $collection);

        return [
            'total' => (clone $base)->count(),
            'open' => (clone $base)->whereNotIn('status', Incident::CLOSED_STATES)->count(),
            'with_evidence' => (clone $base)->whereHas('attachments', $withFile('evidence'))->count(),
            'closed_documented' => (clone $base)->where($closed)->where(fn ($q) => $q->whereHas('attachments', $withFile('solution'))->orWhereNotNull('resolution'))->count(),
            'avg_response_minutes' => (clone $base)->whereNotNull('first_response_at')->get(['created_at', 'first_response_at'])
                ->avg(fn ($i) => $i->created_at->diffInMinutes($i->first_response_at)),
            'by_type' => (clone $base)->reorder()->selectRaw('type, count(*) as total')->groupBy('type')->orderByDesc('total')->pluck('total', 'type'),
        ];
    }

    /** Formulario de reporte. Las opciones de ubicación dependen del residencial y, para apartamentos, del edificio elegido. */
    public function create(Request $request)
    {
        $user = $request->user();
        $communities = Community::visibleTo($user)->orderBy('name')->get(['id', 'name']);
        $community = $user->isResident() ? $user->community : ($communities->firstWhere('id', $request->integer('residencial')) ?? $communities->firstWhere('id', $user->community_id) ?? $communities->first());
        abort_unless($community, 403);

        $units = $user->isResident()
            ? Unit::whereIn('id', $user->units()->pluck('units.id')->push($user->unit_id)->filter())->get()
            : Unit::where('building_id', $request->integer('edificio'))->where('community_id', $community->id)->get();

        return Inertia::render('Incidents/Create', [
            // Valores iniciales (p. ej. "Reportar avería" desde servicios generales); el tipo y la categoría se validan contra las listas.
            'prefill' => [
                'title' => $request->string('asunto')->limit(120)->toString(),
                'reference' => $request->string('referencia')->limit(160)->toString(),
                'type' => in_array($request->query('tipo'), Incident::TYPES, true) ? $request->query('tipo') : null,
                'scope' => in_array($request->query('categoria'), Incident::SCOPES, true) ? $request->query('categoria') : null,
            ],
            'types' => Incident::TYPES,
            'scopes' => Incident::SCOPES,
            'locationTypes' => Incident::LOCATION_TYPES,
            'communities' => $user->isResident() ? [] : $communities,
            'communityId' => $community->id,
            'blocks' => $community->blocks()->get(['id', 'name']),
            'buildings' => $community->buildings()->get(['id', 'block_id', 'name']),
            'streets' => $community->streets()->orderBy('name')->get(['id', 'name']),
            'units' => $units->sortBy('apartment_number', SORT_NATURAL)->values()->map(fn ($u) => ['id' => $u->id, 'number' => $u->apartment_number ?? $u->code, 'building_id' => $u->building_id]),
        ]);
    }

    public function store(StoreIncidentRequest $request)
    {
        $incident = $this->workflow->open($request->user(), $request->communityId(), $request->safe()->except('evidence'), $request->file('evidence', []));

        return redirect("/incidencias/{$incident->code}")->with('success', "{$incident->code} creada");
    }

    public function show(Request $request, Incident $incident)
    {
        Gate::authorize('view', $incident);
        $incident->load(['community', 'assignee', 'reporter', 'unit', 'entries.user', 'attachments']);
        $user = $request->user();

        return Inertia::render('Incidents/Show', [
            'incident' => new IncidentDetailResource($incident),
            'staff' => User::where('community_id', $incident->community_id)->whereIn('role', ['admin', 'mantenimiento', 'seguridad'])->orderBy('name')->get(['id', 'name', 'role']),
            'states' => Incident::STATES,
            'can' => [
                'manage' => $user->can('manage', $incident),
                'assign' => $user->can('assign', $incident),
                'comment' => $user->can('comment', $incident),
                'internal' => $user->can('viewInternal', $incident),
                'confirm' => $user->can('confirm', $incident),
            ],
        ]);
    }

    public function status(Request $request, Incident $incident)
    {
        Gate::authorize('manage', $incident);
        $data = $request->validate(['status' => ['required', Rule::in(Incident::STATES)]]);
        $this->workflow->changeStatus($incident, $request->user(), $data['status']);

        return back()->with('success', "{$incident->code} → {$incident->status}");
    }

    public function assign(Request $request, Incident $incident)
    {
        Gate::authorize('assign', $incident);
        $data = $request->validate([
            'assignee_id' => ['nullable', Rule::exists('users', 'id')->where('community_id', $incident->community_id)],
            'assignee_role' => ['nullable', Rule::in(['mantenimiento', 'seguridad', 'admin'])],
        ]);
        abort_unless($data['assignee_id'] ?? $data['assignee_role'] ?? null, 422, 'Indica una persona o un rol.');
        $assignee = isset($data['assignee_id']) ? User::find($data['assignee_id']) : null;
        $this->workflow->assign($incident, $request->user(), $assignee, $data['assignee_role'] ?? null);

        return back()->with('success', 'Responsable actualizado');
    }

    public function detail(UpdateIncidentDetailRequest $request, Incident $incident)
    {
        Gate::authorize('manage', $incident);
        $this->workflow->updateDetail($incident, $request->user(), $request->validated());

        return back()->with('success', 'Caso actualizado');
    }

    public function evidence(StoreIncidentEvidenceRequest $request, Incident $incident)
    {
        Gate::authorize('manage', $incident);
        $this->workflow->attach($incident, $request->validated('collection'), $request->file('files'), $request->user());

        return back()->with('success', 'Evidencia agregada');
    }

    public function comment(Request $request, Incident $incident)
    {
        Gate::authorize('comment', $incident);
        $data = $request->validate(['kind' => ['required', Rule::in(['res', 'int', 'acc'])], 'text' => 'required|string|max:2000']);
        // Quien reportó solo puede responder al equipo; las notas internas y acciones son del personal.
        abort_if($request->user()->isResident() && $data['kind'] !== 'res', 403);
        $incident->entries()->create($data + ['user_id' => $request->user()->id]);

        return back()->with('success', 'Registrado en el caso');
    }

    public function confirm(Request $request, Incident $incident)
    {
        Gate::authorize('confirm', $incident);
        $data = $request->validate(['resolved' => ['required', 'boolean'], 'feedback' => ['nullable', 'string', 'max:1000']]);
        $this->workflow->confirm($incident, $request->user(), $data['resolved'], $data['feedback'] ?? null);

        return back()->with('success', $data['resolved'] ? 'Gracias, caso cerrado' : 'Caso reabierto');
    }
}
