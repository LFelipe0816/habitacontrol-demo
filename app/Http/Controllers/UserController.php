<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\SaveUserRequest;
use App\Models\ActivityLog;
use App\Models\Community;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class UserController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request)
    {
        Gate::authorize('viewAny', User::class);
        $actor = $request->user();
        $search = trim((string) $request->query('q'));

        $users = User::query()
            ->when(! $actor->hasRole('superadmin'), fn ($q) => $q->whereIn('community_id', Community::visibleTo($actor)->select('id')))
            ->when($request->query('role'), fn ($q, $r) => $q->where('role', $r))
            ->when($request->query('estado'), fn ($q, $e) => $q->where('active', $e === 'activos'))
            ->when($search, fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->with('units:id,code')->orderBy('name')->paginate(self::PER_PAGE)->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => [
                'data' => $users->getCollection()->map(fn (User $u) => $this->present($u))->values(),
                'meta' => ['current_page' => $users->currentPage(), 'last_page' => $users->lastPage(), 'total' => $users->total()],
            ],
            'filters' => $request->only('q', 'role', 'estado'),
            'roles' => collect(Role::cases())->filter(fn (Role $r) => in_array($r->value, $actor->assignableRoles(), true))
                ->mapWithKeys(fn (Role $r) => [$r->value => $r->label()]),
            'allRoles' => collect(Role::cases())->mapWithKeys(fn (Role $r) => [$r->value => $r->label()]),
        ]);
    }

    public function store(SaveUserRequest $request)
    {
        Gate::authorize('viewAny', User::class);
        $actor = $request->user();
        $data = $request->validated();

        $user = User::create(collect($data)->only(['name', 'email', 'password', 'role', 'phone', 'document_id'])->all() + ['community_id' => $actor->community_id, 'active' => $data['active'] ?? true]);
        $this->syncUnits($user, $data['units'] ?? []);
        $this->log($actor, 'Usuario creado', "{$user->name} fue agregado como ".Role::from($user->role)->label().'.');

        return back()->with('success', "Usuario creado · {$user->email}");
    }

    public function update(SaveUserRequest $request, User $user)
    {
        Gate::authorize('manage', $user);
        $actor = $request->user();
        $data = $request->validated();

        // Evita quedarse sin acceso o sin administración: no se puede desactivar ni cambiar el rol de uno mismo.
        abort_if($actor->is($user) && (($data['active'] ?? true) === false || (isset($data['role']) && $data['role'] !== $user->role)), 422, 'No puedes desactivarte ni cambiar tu propio rol.');

        $user->fill(collect($data)->only(['name', 'email', 'role', 'phone', 'document_id', 'active'])->all());
        if (filled($data['password'] ?? null)) {
            $user->password = $data['password'];
        }
        $changed = array_keys($user->getDirty());
        $user->save();
        if (array_key_exists('units', $data)) {
            $this->syncUnits($user, $data['units']);
        }
        $this->log($actor, 'Usuario actualizado', "{$user->name}: ".implode(', ', $changed ?: ['vínculos con unidades']).'.');

        return back()->with('success', 'Usuario actualizado');
    }

    /** Búsqueda de apartamentos para vincularlos (autocompletado). */
    public function searchUnits(Request $request)
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        $q = trim((string) $request->query('q'));

        return Unit::visibleTo($request->user())->with('building:id,name')
            ->when($q, fn ($w) => $w->where('code', 'like', "%{$q}%"))->orderBy('code')->limit(10)->get()
            ->map(fn (Unit $u) => ['id' => $u->id, 'code' => $u->code, 'label' => trim($u->code.' · '.($u->building?->name ?? ''), ' ·'), 'balance' => $u->balance]);
    }

    private function present(User $u): array
    {
        return [
            'id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'role' => $u->role, 'role_label' => Role::tryFrom($u->role)?->label() ?? $u->role,
            'phone' => $u->phone, 'document_id' => $u->document_id, 'active' => $u->active,
            'units' => $u->units->map(fn ($unit) => ['unit_id' => $unit->id, 'code' => $unit->code, 'relation' => $unit->pivot->relation])->values(),
        ];
    }

    /** Reemplaza los vínculos; la primera unidad pasa a ser la unidad principal (`users.unit_id`). */
    private function syncUnits(User $user, array $links): void
    {
        $user->units()->detach();
        foreach ($links as $link) {
            $user->units()->attach($link['unit_id'], ['relation' => $link['relation']]);
        }
        $user->update(['unit_id' => $links[0]['unit_id'] ?? null]);
    }

    private function log(User $actor, string $action, string $detail): void
    {
        ActivityLog::create(['community_id' => $actor->community_id, 'user_id' => $actor->id, 'action' => $action, 'detail' => $detail]);
    }
}
