import { Head, router } from '@inertiajs/react';
import { Blueprint, Icon, KpiGrid, Tag } from '@/Components';
import { PageHeader } from '@/Components/Page';
import { money, shortMoney } from '@/lib/format';
import type { Community } from '@/types';

type Props = { communities: Community[]; totals: { communities: number; contracted_units: number; balance: number; open_incidents: number } };

const Stat = ({ label, value }: { label: string; value: string | number }) => <div style={{ display: 'flex', flexDirection: 'column', gap: 1 }}>
  <span style={{ fontFamily: 'var(--font-heading)', fontWeight: 600, fontSize: 22, lineHeight: 1.1 }}>{value}</span>
  <span style={{ fontSize: 10.5, letterSpacing: '.08em', textTransform: 'uppercase', color: 'var(--hc-muted)' }}>{label}</span>
</div>;

export default function Index({ communities, totals }: Props) {
  return <div style={{ display: 'flex', flexDirection: 'column', gap: 28 }}>
    <Head title="Residenciales" />
    <PageHeader kicker="Portafolio" title="Residenciales" />
    <KpiGrid items={[
      { label: 'Viviendas administradas', value: totals.contracted_units.toLocaleString('es-DO'), hero: true, span: 2, sub: `${totals.communities} residenciales` },
      { label: 'Cuentas por cobrar', value: shortMoney(totals.balance) },
      { label: 'Casos abiertos', value: String(totals.open_incidents), onClick: () => router.visit('/incidencias') },
    ]} />
    {communities.length === 0
      ? <Blueprint style={{ padding: 32, textAlign: 'center', color: 'var(--hc-muted)' }}>Aún no hay residenciales registrados.</Blueprint>
      : <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill,minmax(min(360px,100%),1fr))', gap: 24 }}>
        {communities.map(c => <Blueprint key={c.id} onClick={() => router.visit('/residenciales/' + c.id)} style={{ padding: 20, display: 'flex', flexDirection: 'column', gap: 16, cursor: 'pointer' }}>
          <div style={{ display: 'flex', alignItems: 'flex-start', gap: 10 }}>
            <div style={{ flex: 1, minWidth: 0 }}>
              <h3 style={{ margin: 0, fontSize: 24, textTransform: 'uppercase', letterSpacing: '.02em' }}>{c.name}</h3>
              <span style={{ fontSize: 13, color: 'var(--hc-muted)' }}>{c.address ?? 'Dirección pendiente'}</span>
            </div>
            <Tag tone={c.status === 'activo' ? 'strong' : 'outline'}>{c.status}</Tag>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4,1fr)', gap: 12 }}>
            <Stat label="Viviendas" value={c.units_count.toLocaleString('es-DO')} />
            <Stat label="Ocupadas" value={c.occupied_units} />
            <Stat label="Con deuda" value={c.units_with_balance} />
            <Stat label="Casos" value={c.open_incidents} />
          </div>
          <div style={{ display: 'flex', alignItems: 'center', gap: 8, borderTop: '1px solid var(--color-divider)', paddingTop: 12, fontSize: 13, color: 'var(--hc-muted)' }}>
            <Icon name="layers" size={15} /><span style={{ flex: 1 }}>{c.plan ?? 'Sin plan'} · cuota {money(c.maintenance_fee)}</span>
            <span style={{ color: 'var(--color-accent-700)' }}>Ver estructura →</span>
          </div>
        </Blueprint>)}
      </div>}
  </div>;
}
