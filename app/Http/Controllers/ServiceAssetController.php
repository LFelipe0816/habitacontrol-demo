<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompleteMaintenanceRequest;
use App\Http\Requests\ScheduleMaintenanceRequest;
use App\Http\Requests\ServiceAssetRequest;
use App\Http\Resources\ServiceAssetResource;
use App\Models\ActivityLog;
use App\Models\Community;
use App\Models\ServiceAsset;
use App\Models\ServiceMaintenance;
use App\Services\ServiceAssetWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ServiceAssetController extends Controller
{
    /** Campos que el personal de mantenimiento puede tocar; el resto es configuración del administrador. */
    private const OPERATIONAL = ['status', 'health', 'availability', 'reading', 'risk'];

    public function __construct(private ServiceAssetWorkflow $workflow) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', ServiceAsset::class);
        $user = $request->user();

        $communities = Community::visibleTo($user)->orderBy('name')->get(['id', 'name']);
        $community = $communities->firstWhere('id', $request->integer('residencial')) ?? $communities->firstWhere('id', $user->community_id) ?? $communities->first();
        abort_unless($community, 403);

        $assets = ServiceAsset::where('community_id', $community->id)->with('maintenances')->orderBy('id')->get();
        $resources = ServiceAssetResource::collection($assets)->resolve();
        $selected = $assets->firstWhere('id', $request->integer('activo')) ?? $assets->firstWhere('status', 'alerta') ?? $assets->first();

        return Inertia::render('Services/Index', [
            'assets' => $resources,
            'selected' => $selected?->id,
            'stats' => [
                'total' => $assets->count(),
                'attention' => $assets->whereIn('status', ServiceAsset::NEEDS_ATTENTION)->count(),
                'avg_health' => $assets->isEmpty() ? null : (int) round($assets->avg('health')),
                'water' => $assets->whereIn('category', ServiceAsset::WATER)->count(),
            ],
            // Mantenimientos pendientes, del más próximo al más lejano.
            'upcoming' => ServiceMaintenance::whereNull('performed_on')->whereIn('service_asset_id', $assets->pluck('id'))
                ->with('asset:id,name,location,category,responsible,provider,status')->orderBy('scheduled_for')->limit(6)->get()
                ->map(fn ($m) => [
                    'id' => $m->id, 'asset_id' => $m->service_asset_id, 'asset' => $m->asset->name, 'location' => $m->asset->location, 'category' => $m->asset->category,
                    'responsible' => $m->asset->responsible, 'date' => $m->scheduled_for->toDateString(), 'type' => $m->type, 'overdue' => $m->scheduled_for->isBefore(today()),
                ]),
            'log' => ActivityLog::where('subject_type', ServiceAsset::class)->whereIn('subject_id', $assets->pluck('id'))
                ->with('user:id,name')->latest('created_at')->limit(6)->get()
                ->map(fn ($l) => ['id' => $l->id, 'action' => $l->action, 'detail' => $l->detail, 'who' => $l->user?->name, 'at' => $l->created_at->toIso8601String()]),
            'communities' => $communities,
            'communityId' => $community->id,
            'statuses' => ServiceAsset::STATUSES,
            'can' => ['operate' => $user->hasRole('admin', 'mantenimiento'), 'configure' => $user->hasRole('admin')],
        ]);
    }

    public function store(ServiceAssetRequest $request)
    {
        $communityId = $request->integer('community_id') ?: $request->user()->community_id;
        abort_unless(Community::visibleTo($request->user())->whereKey($communityId)->exists() && $request->user()->hasRole('admin'), 403);

        $asset = $this->workflow->create($request->user(), $communityId, $request->validated());

        return redirect("/servicios?residencial={$communityId}&activo={$asset->id}")->with('success', "{$asset->name} registrado");
    }

    public function update(ServiceAssetRequest $request, ServiceAsset $asset)
    {
        Gate::authorize('operate', $asset);
        $data = Gate::allows('configure', $asset) ? $request->validated() : Arr::only($request->validated(), self::OPERATIONAL);
        $this->workflow->update($asset, $request->user(), $data);

        return back()->with('success', 'Activo actualizado');
    }

    public function schedule(ScheduleMaintenanceRequest $request, ServiceAsset $asset)
    {
        Gate::authorize('operate', $asset);
        $this->workflow->schedule($asset, $request->user(), $request->validated());

        return back()->with('success', 'Mantenimiento programado');
    }

    public function complete(CompleteMaintenanceRequest $request, ServiceMaintenance $maintenance)
    {
        Gate::authorize('operate', $maintenance->asset);
        abort_if($maintenance->performed_on, 422, 'Este mantenimiento ya se completó.');
        $this->workflow->complete($maintenance, $request->user(), $request->validated());

        return back()->with('success', 'Mantenimiento completado');
    }
}
