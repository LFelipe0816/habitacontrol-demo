import { Head, Link, router } from '@inertiajs/react';
import { Blueprint, DataTable, KpiGrid, SelectorStrip, Tag, UnitTile } from '@/Components';
import { PageHeader, Section } from '@/Components/Page';
import { shortMoney } from '@/lib/format';
import type { Block, BuildingSummary, Community, Incident, Street, UnitSummary } from '@/types';

type Props = {
  residential: Community; blocks: Block[]; buildings: BuildingSummary[]; units: UnitSummary[]; streets: Street[]; incidents: Incident[];
  selected: { block: number | null; building: number | null };
};

export default function Show({ residential: c, blocks, buildings, units, streets, incidents, selected }: Props) {
  // La selección vive en la URL (?manzana=&edificio=) para poder compartir o recargar la vista.
  const go = (q: Record<string, number | string>) => router.get(`/residenciales/${c.id}`, q, { preserveScroll: true, preserveState: true, only: ['blocks', 'buildings', 'units', 'selected'] });
  const block = blocks.find(b => b.id === selected.block);
  const building = buildings.find(b => b.id === selected.building);

  return <div style={{ display: 'flex', flexDirection: 'column', gap: 32 }}>
    <Head title={c.name} />
    <PageHeader kicker={<Link href="/residenciales" style={{ color: 'inherit' }}>← Portafolio</Link>} title={c.name}
      actions={<Tag tone={c.status === 'activo' ? 'strong' : 'outline'}>{c.status}</Tag>} />
    <KpiGrid items={[
      { label: 'Viviendas contratadas', value: c.units_count.toLocaleString('es-DO'), hero: true, span: 2, sub: `${c.loaded_units} cargadas en el sistema` },
      { label: 'Ocupadas', value: String(c.occupied_units) },
      { label: 'Con deuda', value: String(c.units_with_balance), sub: shortMoney(c.balance) },
      { label: 'Casos abiertos', value: String(c.open_incidents) },
    ]} />

    <Section title="Estructura">
      {blocks.length === 0 ? <Blueprint style={{ padding: 24, color: 'var(--hc-muted)' }}>Este residencial aún no tiene manzanas registradas.</Blueprint> : <>
        <SelectorStrip label="Manzanas" value={selected.block} onChange={id => go({ manzana: id })}
          items={blocks.map(b => ({ id: b.id, label: b.name, sub: `${b.buildings} edif. · ${b.units} aptos · ${b.incidents} casos` }))} />
        <Blueprint style={{ padding: 20, display: 'flex', flexDirection: 'column', gap: 18 }}>
          <span style={{ fontSize: 10.5, letterSpacing: '.1em', textTransform: 'uppercase', color: 'var(--color-accent-700)' }}>Edificios de {block?.name}</span>
          {buildings.length === 0 ? <span style={{ color: 'var(--hc-muted)' }}>Sin edificios registrados.</span> :
            <SelectorStrip label="Edificios" value={selected.building} onChange={id => go({ manzana: selected.block!, edificio: id })}
              items={buildings.map(b => ({ id: b.id, label: b.code, sub: `${b.units} aptos` }))} />}
          {building && <>
            <h3 style={{ margin: 0, fontSize: 20 }}>{building.name} · {units.length} apartamentos</h3>
            {units.length === 0 ? <span style={{ color: 'var(--hc-muted)' }}>No hay apartamentos cargados en este edificio.</span> :
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill,minmax(104px,1fr))', gap: 10 }}>
                {units.map(u => <UnitTile key={u.id} number={u.number} occupancy={u.occupancy} balance={u.balance} onClick={() => router.visit('/unidades/' + u.id)} />)}
              </div>}
            <span style={{ fontSize: 12, color: 'var(--hc-muted)' }}>Relleno = con deuda · borde punteado = vacante</span>
          </>}
        </Blueprint>
      </>}
    </Section>

    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit,minmax(min(420px,100%),1fr))', gap: 28 }}>
      <Section title="Calles y puntos comunes">
        <DataTable
          columns={[
            { key: 'code', label: 'Cód.', mono: true },
            { key: 'name', label: 'Calle', render: r => <><strong style={{ fontWeight: 500 }}>{r.name}</strong><br /><span style={{ fontSize: 12.5, color: 'var(--hc-muted)' }}>{r.reference}</span></> },
            { key: 'block', label: 'Manzana' },
            { key: 'lighting_points', label: 'Luces', align: 'right' },
          ]}
          rows={streets}
        />
      </Section>
      <Section title="Incidencias vinculadas">
        <DataTable
          onRowClick={r => router.visit('/incidencias/' + r.id)}
          columns={[
            { key: 'id', label: 'Caso', mono: true },
            { key: 'title', label: 'Asunto', render: r => <>{r.title}<br /><span style={{ fontSize: 12.5, color: 'var(--hc-muted)' }}>{r.location}</span></> },
            { key: 'priority', label: 'Prioridad', render: r => <Tag status={r.priority} /> },
            { key: 'status', label: 'Estado' },
          ]}
          rows={incidents}
        />
      </Section>
    </div>
  </div>;
}
