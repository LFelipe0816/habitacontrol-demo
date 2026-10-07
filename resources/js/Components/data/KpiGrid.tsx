import React from 'react';

export interface KpiItem { label: string; value: string; sub?: string; /** Column span */ span?: number; /** Larger accent value — use once per grid */ hero?: boolean; onClick?: () => void; }
export interface KpiGridProps {
  items: KpiItem[];
  /** Min cell width in px. Default 210 */
  min?: number;
}

export function KpiGrid({ items, min = 210 }: KpiGridProps) {
  return <div className="blueprint" style={{ display: 'grid', gridTemplateColumns: `repeat(auto-fit,minmax(${min}px,1fr))`, gap: 1, background: 'var(--color-divider)' }}>
    <i className="corner tl" /><i className="corner tr" /><i className="corner bl" /><i className="corner br" />
    {items.map((k, i) => <div key={i} onClick={k.onClick} style={{ gridColumn: k.span ? 'span ' + k.span : undefined, background: 'var(--color-bg)', padding: '18px 20px', display: 'flex', flexDirection: 'column', gap: 4, cursor: k.onClick ? 'pointer' : 'default' }}>
      <span style={{ fontSize: 10.5, letterSpacing: '.1em', textTransform: 'uppercase', color: 'var(--hc-muted)' }}>{k.label}</span>
      <span style={{ fontFamily: 'var(--font-heading)', fontWeight: 600, fontSize: k.hero ? 'clamp(32px,3.4vw,46px)' : 'clamp(22px,2.2vw,30px)', lineHeight: 1.05, whiteSpace: 'nowrap', color: k.hero ? 'var(--color-accent-700)' : 'var(--color-text)' }}>{k.value}</span>
      {k.sub && <span style={{ fontSize: 12.5, color: 'var(--hc-muted)' }}>{k.sub}</span>}
    </div>)}
  </div>;
}
