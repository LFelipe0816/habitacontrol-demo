<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Block;
use App\Models\Building;
use App\Models\CommonArea;
use App\Models\Community;
use App\Models\Company;
use App\Models\Document;
use App\Models\Lease;
use App\Models\Message;
use App\Models\Notice;
use App\Models\Plan;
use App\Models\Poll;
use App\Models\Reservation;
use App\Models\ResidentRequest;
use App\Models\ServiceAsset;
use App\Models\Street;
use App\Models\Unit;
use App\Models\User;
use App\Models\Visitor;
use App\Services\Ledger;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Datos de demostración 100% ficticios (residenciales, personas, cobros, incidencias).
 * La fuente es database/seeders/data/demo.json; cada `ref` solo sirve para enlazar registros durante la carga.
 * Todas las cuentas usan la contraseña "password": úsalas únicamente en local.
 */
class DemoSeeder extends Seeder
{
    private const EVIDENCE_PATH = 'incidents/evidencia-ejemplo.png';

    /** @var array<string, array<string, int>> ids reales indexados por tipo y ref original */
    private array $ids = [];

    private array $data;

    public function run(): void
    {
        $this->data = json_decode(file_get_contents(__DIR__.'/data/demo.json'), true, flags: JSON_THROW_ON_ERROR);

        $this->catalog();
        $this->structure();
        $this->people();
        $this->units();
        $this->incidents();
        $this->billing();
        $this->communityLife();
        $this->services();
    }

    private function id(string $type, ?string $ref): ?int
    {
        return $ref === null ? null : ($this->ids[$type][$ref] ?? null);
    }

    private function remember(string $type, string $ref, int $id): void
    {
        $this->ids[$type][$ref] = $id;
    }

    /** La community principal es la primera del archivo. */
    private function main(): int
    {
        return $this->id('community', $this->data['communities'][0]['ref']);
    }

    private function catalog(): void
    {
        $plans = collect($this->data['plans'])->mapWithKeys(fn ($p) => [$p['key'] => Plan::create($p)->id]);
        $company = Company::create($this->data['company']);

        foreach ($this->data['communities'] as $c) {
            $community = Community::create(['company_id' => $company->id, 'plan_id' => $plans[$c['plan']]] + collect($c)->except(['ref', 'plan'])->all());
            $this->remember('community', $c['ref'], $community->id);
        }
    }

    private function structure(): void
    {
        foreach ($this->data['blocks'] as $b) {
            $block = Block::create(['community_id' => $this->id('community', $b['community'])] + collect($b)->except(['ref', 'community'])->all());
            $this->remember('block', $b['ref'], $block->id);
        }
        foreach ($this->data['buildings'] as $b) {
            $building = Building::create([
                'community_id' => $this->id('community', $b['community']),
                'block_id' => $this->id('block', $b['block']),
            ] + collect($b)->except(['ref', 'community', 'block'])->all());
            $this->remember('building', $b['ref'], $building->id);
        }
        foreach ($this->data['streets'] as $s) {
            $street = Street::create([
                'community_id' => $this->id('community', $s['community']),
                'block_id' => $this->id('block', $s['block']),
            ] + collect($s)->except(['ref', 'community', 'block'])->all());
            $this->remember('street', $s['ref'], $street->id);
        }
    }

    private function people(): void
    {
        foreach ($this->data['users'] as $u) {
            $user = User::create([
                'community_id' => $this->main(),
                'password' => 'password',
            ] + collect($u)->except(['ref', 'units'])->all());
            $this->remember('user', $u['ref'], $user->id);
        }
    }

    private function units(): void
    {
        foreach ($this->data['units'] as $u) {
            $unit = Unit::create([
                'community_id' => $this->id('community', $u['community']),
                'block_id' => $this->id('block', $u['block']),
                'building_id' => $this->id('building', $u['building']),
            ] + collect($u)->except(['ref', 'community', 'block', 'building', 'owner', 'residents', 'lease'])->all());
            $this->remember('unit', $u['ref'], $unit->id);

            $this->linkPeople($unit, $u);
        }
    }

    /** Vincula propietario y ocupantes; la primera unidad de cada usuario pasa a ser su unidad principal. */
    private function linkPeople(Unit $unit, array $u): void
    {
        $ownerId = $this->id('user', $u['owner']);
        $occupantRelation = $u['occupancy'] === 'alquilado' ? 'inquilino' : 'residente';
        $links = collect($u['residents'])->map(fn ($ref) => $this->id('user', $ref))->filter()
            ->reject(fn ($id) => $id === $ownerId)->mapWithKeys(fn ($id) => [$id => $occupantRelation]);
        if ($ownerId) {
            $links->put($ownerId, 'propietario');
        }
        // Un propietario que también vive en la unidad conserva la relación de propietario.
        $everyone = collect($u['residents'])->map(fn ($ref) => $this->id('user', $ref))->filter()->push($ownerId)->filter()->unique();

        foreach ($links as $userId => $relation) {
            $unit->people()->attach($userId, ['relation' => $relation]);
        }
        User::whereIn('id', $everyone)->whereNull('unit_id')->update(['unit_id' => $unit->id]);

        if ($u['lease']) {
            Lease::create([
                'unit_id' => $unit->id,
                'tenant_id' => $links->search('inquilino') ?: null,
                'starts_on' => $u['lease']['startDate'],
                'ends_on' => $u['lease']['endDate'],
                'monthly_rent' => $u['lease']['monthlyRent'],
                'deposit' => $u['lease']['deposit'],
                'authorized_by' => $u['lease']['authorizedBy'],
            ]);
        }
    }

    private function incidents(): void
    {
        Storage::disk('public')->put(self::EVIDENCE_PATH, file_get_contents(__DIR__.'/data/evidencia-ejemplo.png'));

        foreach ($this->data['incidents'] as $i) {
            $reporter = User::findOrFail($this->id('user', $i['reporter']));
            $incident = $reporter->reportedIncidents()->create([
                'community_id' => $this->id('community', $i['community']),
                'block_id' => $this->id('block', $i['block']),
                'building_id' => $this->id('building', $i['building']),
                'street_id' => $this->id('street', $i['street']),
                'unit_id' => $this->id('unit', $i['unit']),
                'assignee_id' => $this->id('user', $i['assignee']),
            ] + collect($i)->except(['community', 'block', 'building', 'street', 'unit', 'reporter', 'assignee', 'created_at', 'timeline', 'evidence', 'solution'])->all());

            $incident->forceFill(['created_at' => $i['created_at']])->saveQuietly();

            foreach ($i['timeline'] as $t) {
                $incident->entries()->forceCreate(['user_id' => $reporter->id, 'kind' => 'acc', 'text' => $t['text'], 'created_at' => $t['at'], 'updated_at' => $t['at']]);
            }
            foreach (['evidence' => $i['evidence'], 'solution' => $i['solution']] as $collection => $files) {
                foreach ($files as $f) {
                    $incident->attachments()->create(['collection' => $collection, 'path' => self::EVIDENCE_PATH] + $f);
                }
            }

            ActivityLog::create(['community_id' => $incident->community_id, 'user_id' => $reporter->id, 'action' => 'Incidencia registrada', 'detail' => "{$incident->code} · {$incident->title}"]);
        }
    }

    /**
     * Los cobros se cargan pendientes y los pagos originales se aplican a través del libro de cuentas,
     * de modo que saldos y estados salgan del mismo cálculo que usa la aplicación.
     */
    private function billing(): void
    {
        $ledger = app(Ledger::class);
        $admin = User::where('role', 'admin')->firstOrFail();

        foreach ($this->data['charges'] as $c) {
            $ledger->charge($admin, Unit::findOrFail($this->id('unit', $c['unit'])), $c['concept'], $c['amount'], Carbon::parse($c['due_date']));
        }
        foreach (collect($this->data['payments'])->sortBy('at') as $p) {
            $ledger->pay($admin, Unit::findOrFail($this->id('unit', $p['unit'])), $p['amount'], $p['method'], $p['reference'], at: Carbon::parse($p['at']));
        }
    }

    private function communityLife(): void
    {
        $community = $this->main();

        foreach ($this->data['visitors'] as $v) {
            Visitor::create([
                'unit_id' => $this->id('unit', $v['unit']),
                'name' => $v['name'],
                'document' => 'Pendiente',
                'host' => User::find($this->id('user', $v['user']))->name,
                'reason' => $v['reason'],
                'status' => $v['status'],
                'entered_at' => now(),
            ]);
        }

        $areas = collect($this->data['reservations'])->pluck('area')->unique()->mapWithKeys(fn ($name) => [$name => CommonArea::create(['community_id' => $community, 'name' => $name])->id]);
        foreach ($this->data['reservations'] as $r) {
            Reservation::create([
                'common_area_id' => $areas[$r['area']],
                'unit_id' => $this->id('unit', $r['unit']),
                'user_id' => $this->id('user', $r['user']),
                'starts_at' => $r['time'],
            ] + collect($r)->only(['date', 'status', 'notes'])->all());
        }

        foreach ($this->data['documents'] as $d) {
            Document::create(['community_id' => $community, 'uploaded_by' => $this->id('user', $d['user'])] + collect($d)->only(['title', 'category', 'visibility'])->all());
        }
        foreach ($this->data['requests'] as $r) {
            ResidentRequest::create([
                'unit_id' => $this->id('unit', $r['unit']),
                'requester_id' => $this->id('user', $r['requester']),
                'assignee_id' => $this->id('user', $r['assignee']),
            ] + collect($r)->only(['title', 'description', 'status', 'response'])->all());
        }
        foreach ($this->data['messages'] as $m) {
            Message::create([
                'from_user_id' => $this->id('user', $m['from_user']),
                'to_user_id' => $this->id('user', $m['to_user']),
            ] + collect($m)->only(['subject', 'body'])->all());
        }
        foreach ($this->data['notices'] as $n) {
            Notice::create(['community_id' => $community, 'author_id' => $this->id('user', $n['user']), 'category' => 'General'] + collect($n)->only(['title', 'body', 'audience'])->all());
        }
        $this->polls($community);
    }

    /** Los conteos originales eran cifras sueltas; aquí cada residente vota una vez para que el conteo salga de `poll_votes`. */
    private function polls(int $community): void
    {
        $voters = User::whereIn('role', ['residente', 'propietario'])->pluck('id');

        foreach ($this->data['polls'] as $p) {
            $poll = Poll::create(['community_id' => $community, 'created_by' => $this->id('user', $p['user'])] + collect($p)->only(['title', 'status'])->all());
            $options = collect($p['options'])->map(fn ($o) => $poll->options()->create(['label' => $o['label']]));
            foreach ($voters as $n => $userId) {
                $poll->votes()->create(['user_id' => $userId, 'poll_option_id' => $options[$n % $options->count()]->id]);
            }
        }
    }

    private function services(): void
    {
        foreach ($this->data['services'] as $s) {
            $asset = ServiceAsset::create(['community_id' => $this->main()] + collect($s)->except(['last', 'next'])->all());
            $asset->maintenances()->create(['scheduled_for' => $s['last'], 'performed_on' => $s['last']]);
            $asset->activities()->create(['community_id' => $asset->community_id, 'user_id' => $this->id('user', 'user_maintenance'), 'action' => 'Mantenimiento completado', 'detail' => "{$asset->name}: servicio preventivo realizado el ".date('d/m/Y', strtotime($s['last'])).'.'])
                ->forceFill(['created_at' => $s['last']])->saveQuietly();
            $asset->maintenances()->create(['scheduled_for' => $s['next']]);
        }
    }
}
