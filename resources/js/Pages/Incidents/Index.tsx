import { useEffect, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Blueprint, Button, IncidentCard, KpiGrid, Segmented } from '@/Components';
import { PageHeader, Section } from '@/Components/Page';
import { ago, initials } from '@/lib/format';
import { INCIDENT_STATES, type Incident, type IncidentStats } from '@/types';

type Props = { incidents: Incident[]; filters: { view?: string; search?: string }; stats: IncidentStats };
const VIEWS = { Abiertas: 'abiertas', 'Alta prioridad': 'alta', Todas: 'todas' } as const;

const hours = (min: number | null) => min == null ? '—' : min < 60 ? `${Math.round(min)} min` : `${(min / 60).toFixed(1)} h`;

export default function Index({ incidents, filters, stats }: Props) {
  const view = (Object.keys(VIEWS) as (keyof typeof VIEWS)[]).find(k => VIEWS[k] === filters.view) ?? 'Abiertas';
  const [search, setSearch] = useState(filters.search ?? '');

  // El filtrado ocurre en Laravel; aquí solo se actualiza la query string.
  const apply = (q: Record<string, string>) => router.get('/incidencias', { ...filters, ...q }, { preserveState: true, replace: true });
  useEffect(() => {
    if (search === (filters.search ?? '')) return;
    const t = setTimeout(() => apply({ search }), 300);
    return () => clearTimeout(t);
  }, [search]);

  const states = view === 'Abiertas' ? INCIDENT_STATES.slice(0, 4) : INCIDENT_STATES;
  const types = Object.entries(stats.by_type);
  const top = Math.max(1, ...types.map(([, n]) => n));

  return <div style={{ display: 'flex', flexDirection: 'column', gap: 28 }}>
    <Head title="Incidencias" />
    <PageHeader kicker="Inciden 360" title="Incidencias" actions={<Link href="/incidencias/crear"><Button variant="primary" icon="plus">Reportar</Button></Link>} />
    <KpiGrid items={[
      { label: 'Casos abiertos', value: String(stats.open), hero: true, span: 2, sub: `${stats.total} en total` },
      { label: 'Con evidencia inicial', value: String(stats.with_evidence), sub: 'fotos o videos' },
      { label: 'Cierre documentado', value: String(stats.closed_documented), sub: 'resueltos con soporte' },
      { label: 'Primera respuesta', value: hours(stats.avg_response_minutes), sub: 'promedio' },
    ]} />

    {types.length > 0 && <Section title="Reportes por tipo">
      <Blueprint style={{ padding: 18, display: 'grid', gridTemplateColumns: 'repeat(auto-fit,minmax(260px,1fr))', gap: '10px 32px' }}>
        {types.map(([type, n]) => <div key={type} style={{ display: 'grid', gridTemplateColumns: '150px 1fr 24px', alignItems: 'center', gap: 10, fontSize: 13.5 }}>
          <span style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{type}</span>
          <span style={{ height: 8, background: 'var(--hc-faint)' }}><span style={{ display: 'block', height: '100%', width: `${(n / top) * 100}%`, background: 'var(--color-accent)' }} /></span>
          <span style={{ fontFamily: 'var(--hc-mono)', fontSize: 12, textAlign: 'right' }}>{n}</span>
        </div>)}
      </Blueprint>
    </Section>}

    <div>
      <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap', alignItems: 'center', marginBottom: 20 }}>
        <Segmented options={Object.keys(VIEWS)} value={view} onChange={v => apply({ view: VIEWS[v as keyof typeof VIEWS] })} />
        <input className="input" placeholder="Buscar por caso, asunto o ubicación" value={search} onChange={e => setSearch(e.target.value)} style={{ maxWidth: 320 }} />
      </div>
      <div style={{ display: 'grid', gridTemplateColumns: `repeat(${states.length},minmax(240px,1fr))`, gap: 16, overflowX: 'auto', paddingBottom: 8 }}>
        {states.map(st => {
          const col = incidents.filter(i => i.status === st);
          return <div key={st} style={{ display: 'flex', flexDirection: 'column', gap: 12, minWidth: 0 }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 11, letterSpacing: '.1em', textTransform: 'uppercase', color: 'var(--hc-muted)', borderBottom: '1px solid var(--color-divider)', paddingBottom: 8 }}>
              <span>{st}</span><span style={{ fontFamily: 'var(--hc-mono)' }}>{col.length}</span>
            </div>
            {col.length === 0 && <span style={{ fontSize: 13, color: 'var(--hc-muted)' }}>Sin casos.</span>}
            {col.map(i => <IncidentCard key={i.id} id={i.id} title={i.title} type={i.type} location={i.location} priority={i.priority}
              evidence={`${i.evidence.length} foto${i.evidence.length === 1 ? '' : 's'}`} image={i.evidence.find(e => e.mime?.startsWith('image/'))?.url} age={ago(i.created_at)} assignee={i.assignee ? initials(i.assignee) : undefined}
              recurring={i.recurring} onClick={() => router.visit('/incidencias/' + i.id)} />)}
          </div>;
        })}
      </div>
    </div>
  </div>;
}
