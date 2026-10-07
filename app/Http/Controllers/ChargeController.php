<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateChargesRequest;
use App\Http\Requests\RecordPaymentRequest;
use App\Http\Requests\StoreChargeRequest;
use App\Models\Charge;
use App\Models\Community;
use App\Models\Payment;
use App\Models\Unit;
use App\Services\Ledger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChargeController extends Controller
{
    private const PER_PAGE = 20;

    private const TABS = ['cobros', 'pagos', 'unidades'];

    public function __construct(private Ledger $ledger) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Charge::class);
        $user = $request->user();

        $communities = $user->isResident() ? collect() : Community::visibleTo($user)->orderBy('name')->get(['id', 'name']);
        $communityId = $communities->firstWhere('id', $request->integer('residencial'))?->id;
        $inCommunity = fn ($q) => $q->when($communityId, fn ($w) => $w->whereHas('unit', fn ($u) => $u->where('community_id', $communityId)));

        $units = Unit::visibleTo($user)->when($communityId, fn ($q) => $q->where('community_id', $communityId));
        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'cobros';
        $search = trim((string) $request->query('q'));
        $byUnit = fn ($q) => $q->when($search, fn ($w) => $w->whereHas('unit', fn ($u) => $u->where('code', 'like', "%{$search}%")));

        return Inertia::render('Billing/Index', [
            'tab' => $tab,
            'filters' => $request->only('status', 'q', 'residencial'),
            'communities' => $communities,
            'stats' => [
                'receivable' => (float) (clone $units)->sum('balance'),
                'collected_month' => (float) Payment::visibleTo($user)->tap($inCommunity)->where('created_at', '>=', now()->startOfMonth())->sum('amount'),
                'on_time' => (clone $units)->where('balance', '<=', 0)->count(),
                'in_debt' => (clone $units)->where('balance', '>', 0)->count(),
                'legal' => Charge::visibleTo($user)->tap($inCommunity)->where('status', 'Legal')->count(),
            ],
            'charges' => $tab === 'cobros' ? $this->page(
                Charge::visibleTo($user)->tap($inCommunity)->tap($byUnit)->with('unit.residents')->withSum('payments', 'amount')
                    ->when($request->status, fn ($q, $s) => $q->where('status', $s))->orderBy('due_date')->orderBy('id')->paginate(self::PER_PAGE)->withQueryString(),
                fn (Charge $c) => [
                    'id' => $c->id, 'unit_id' => $c->unit_id, 'unit' => $c->unit->code, 'resident' => $c->unit->residents->first()?->name ?? '—', 'concept' => $c->concept,
                    'amount' => $c->amount, 'balance' => max(0, round($c->amount - (float) $c->payments_sum_amount, 2)), 'due_date' => $c->due_date->toDateString(), 'status' => $c->status,
                ]) : null,
            'payments' => $tab === 'pagos' ? $this->page(
                Payment::visibleTo($user)->tap($inCommunity)->tap($byUnit)->with(['unit:id,code', 'charge:id,concept'])->latest()->latest('id')->paginate(self::PER_PAGE)->withQueryString(),
                fn (Payment $p) => ['id' => $p->id, 'unit' => $p->unit?->code, 'concept' => $p->charge?->concept, 'amount' => $p->amount, 'method' => $p->method, 'reference' => $p->reference, 'date' => $p->created_at->toDateString()]) : null,
            'units' => $tab === 'unidades' ? $this->page(
                (clone $units)->with(['building:id,name', 'people' => fn ($q) => $q->wherePivot('relation', 'propietario'), 'charges' => fn ($q) => $q->where('status', '!=', 'Al día')->select('id', 'unit_id', 'status')])
                    ->when($search, fn ($q) => $q->where('code', 'like', "%{$search}%"))->orderByDesc('balance')->orderBy('code')->paginate(self::PER_PAGE)->withQueryString(),
                fn (Unit $u) => [
                    'id' => $u->id, 'code' => $u->code, 'building' => $u->building?->name, 'owner' => $u->people->first()?->name,
                    'maintenance_fee' => $u->maintenance_fee, 'balance' => $u->balance, 'status' => $this->worstStatus($u->charges->pluck('status')),
                ]) : null,
            'can' => ['manage' => $user->hasRole('admin')],
        ]);
    }

    /** Estado de una unidad = el más grave entre sus cobros abiertos. */
    private function worstStatus(Collection $statuses): string
    {
        foreach (['Legal', 'Moroso', 'Vencido', 'Por vencer'] as $status) {
            if ($statuses->contains($status)) {
                return $status;
            }
        }

        return 'Al día';
    }

    /** Envuelve un paginador con la forma { data, meta } que espera el componente Pager. */
    private function page(LengthAwarePaginator $paginator, callable $map): array
    {
        return ['data' => $paginator->getCollection()->map($map)->values(), 'meta' => ['current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'total' => $paginator->total()]];
    }

    public function store(StoreChargeRequest $request)
    {
        $unit = Unit::findOrFail($request->integer('unit_id'));
        Gate::authorize('manageFinances', $unit);
        $this->ledger->charge($request->user(), $unit, $request->string('concept')->toString(), (float) $request->input('amount'), Carbon::parse($request->input('due_date')));

        return back()->with('success', "Cargo creado · {$unit->code}");
    }

    public function generate(GenerateChargesRequest $request)
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        $community = Community::findOrFail($request->integer('community_id'));
        $count = $this->ledger->generateMonthly($request->user(), $community, $request->string('concept')->toString(), Carbon::parse($request->input('due_date')));

        return back()->with('success', $count ? "{$count} cuotas generadas" : 'No había cuotas nuevas por generar');
    }

    /** Pago aplicado a un cobro concreto. */
    public function pay(RecordPaymentRequest $request, Charge $charge)
    {
        Gate::authorize('manage', $charge);
        $this->ledger->pay($request->user(), $charge->unit, (float) $request->input('amount'), $request->input('method'), $request->input('reference'), $charge);

        return back()->with('success', "Pago registrado · {$charge->unit->code}");
    }

    /** Pago a la unidad: se reparte entre sus cobros abiertos, del más antiguo al más reciente. */
    public function payUnit(RecordPaymentRequest $request)
    {
        $unit = Unit::findOrFail($request->integer('unit_id'));
        Gate::authorize('manageFinances', $unit);
        $this->ledger->pay($request->user(), $unit, (float) $request->input('amount'), $request->input('method'), $request->input('reference'));

        return back()->with('success', "Pago registrado · {$unit->code}");
    }

    public function legal(Request $request, Charge $charge)
    {
        Gate::authorize('manage', $charge);
        $this->ledger->setLegal($charge, $request->user(), $request->boolean('legal'));

        return back()->with('success', $request->boolean('legal') ? 'Cobro enviado a proceso legal' : 'Cobro retirado de proceso legal');
    }

    /** CSV de cobros y pagos visibles (compatible con Excel: UTF-8 con BOM y celdas neutralizadas contra fórmulas). */
    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', Charge::class);
        $user = $request->user();
        $cell = fn ($v) => '"'.str_replace('"', '""', preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'".$v : (string) $v).'"';

        return response()->streamDownload(function () use ($user, $cell) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF".implode(',', array_map($cell, ['tipo', 'unidad', 'concepto', 'monto', 'estado', 'fecha', 'referencia']))."\n");
            Charge::visibleTo($user)->with('unit:id,code')->orderBy('due_date')->lazy()->each(fn ($c) => fwrite($out, implode(',', array_map($cell, ['cargo', $c->unit->code, $c->concept, $c->amount, $c->status, $c->due_date->toDateString(), '']))."\n"));
            Payment::visibleTo($user)->with(['unit:id,code', 'charge:id,concept'])->orderBy('created_at')->lazy()->each(fn ($p) => fwrite($out, implode(',', array_map($cell, ['pago', $p->unit?->code, $p->charge?->concept, $p->amount, 'registrado', $p->created_at->toDateString(), $p->reference]))."\n"));
            fclose($out);
        }, 'finanzas-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
