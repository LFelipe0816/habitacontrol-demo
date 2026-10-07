import React from 'react';
import { Icon } from '../core/Icon';
import { Meter } from '../core/Meter';
import { Tag } from '../core/Tag';

export interface ServiceAssetCardProps {
  category: string;
  name: string;
  location?: string | null;
  statusLabel: string;
  status: string;
  health: number;
  readingLabel?: string | null;
  reading?: number | null;
  capacity?: string | null;
  availability?: number | null;
  /** ISO date of the next pending service */
  nextMaintenance?: string | null;
  overdue?: boolean;
  responsible?: string | null;
  selected?: boolean;
  onClick?: () => void;
}

/** Lucide icon per service category. */
export const SERVICE_ICON: Record<string, string> = { 'Energía': 'zap', Agua: 'droplets', 'Calidad del agua': 'flask-conical', Almacenamiento: 'warehouse' };
const fmt = (iso: string) => new Date(iso + 'T00:00').toLocaleDateString('es-DO', { day: 'numeric', month: 'short' });

export function ServiceAssetCard(p: ServiceAssetCardProps) {
  const needsAttention = p.status !== 'operativo';
  return <div className="blueprint" onClick={p.onClick} tabIndex={p.onClick ? 0 : undefined} onKeyDown={e => e.key === 'Enter' && p.onClick?.()}
    style={{ padding: 18, display: 'flex', flexDirection: 'column', gap: 14, cursor: p.onClick ? 'pointer' : 'default', outline: p.selected ? '2px solid var(--color-accent)' : undefined, outlineOffset: 3 }}>
    <i className="corner tl" /><i className="corner tr" /><i className="corner bl" /><i className="corner br" />
    <div style={{ display: 'flex', gap: 12, alignItems: 'flex-start' }}>
      <div style={{ width: 38, height: 38, flex: 'none', border: '1px solid var(--color-divider)', display: 'flex', alignItems: 'center', justifyContent: 'center', color: 'var(--color-accent-700)' }}><Icon name={SERVICE_ICON[p.category] ?? 'cog'} size={20} /></div>
      <div style={{ flex: 1, minWidth: 0 }}>
        <div style={{ fontSize: 10.5, letterSpacing: '.1em', textTransform: 'uppercase', color: 'var(--hc-muted)' }}>{p.category}</div>
        <div style={{ fontFamily: 'var(--font-heading)', fontWeight: 600, fontSize: 19, lineHeight: 1.15 }}>{p.name}</div>
        {p.location && <div style={{ fontSize: 12.5, color: 'var(--hc-muted)' }}>{p.location}</div>}
      </div>
      <Tag status={p.statusLabel} />
    </div>
    <Meter label="Salud operativa" value={p.health} warn={p.health < 70} />
    {p.reading != null && <Meter label={p.readingLabel ?? 'Lectura'} value={p.reading} warn={needsAttention && p.reading < 60} />}
    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3,1fr)', gap: 10, fontSize: 13, borderTop: '1px solid var(--hc-line)', paddingTop: 12 }}>
      <Cell label="Capacidad" value={p.capacity} />
      <Cell label="Disponib." value={p.availability != null ? p.availability + '%' : null} />
      <Cell label={p.overdue ? 'Vencido' : 'Próximo'} value={p.nextMaintenance ? fmt(p.nextMaintenance) : 'Sin fecha'} strong={p.overdue} />
    </div>
    {p.responsible && <div style={{ fontSize: 12.5, color: 'var(--hc-muted)' }}>Responsable: <span style={{ color: 'var(--color-text)' }}>{p.responsible}</span></div>}
  </div>;
}

const Cell = ({ label, value, strong }: { label: string; value?: string | null; strong?: boolean }) => <div style={{ display: 'flex', flexDirection: 'column', gap: 1, minWidth: 0 }}>
  <span style={{ fontWeight: 500, fontVariantNumeric: 'tabular-nums', color: strong ? 'var(--color-accent-700)' : undefined, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{value || '—'}</span>
  <span style={{ fontSize: 10.5, letterSpacing: '.08em', textTransform: 'uppercase', color: 'var(--hc-muted)' }}>{label}</span>
</div>;
