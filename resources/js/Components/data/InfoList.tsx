import React from 'react';

export interface InfoListProps { items: { label: string; value?: React.ReactNode }[]; }

/** Label/value pairs for record sheets (ficha de apartamento). Empty values render an em dash. */
export function InfoList({ items }: InfoListProps) {
  return <dl style={{ margin: 0, display: 'grid', gridTemplateColumns: 'repeat(auto-fill,minmax(180px,1fr))', gap: '14px 20px' }}>
    {items.map(i => <div key={i.label} style={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
      <dt style={{ fontSize: 10.5, letterSpacing: '.1em', textTransform: 'uppercase', color: 'var(--hc-muted)' }}>{i.label}</dt>
      <dd style={{ margin: 0, fontSize: 14.5 }}>{i.value || '—'}</dd>
    </div>)}
  </dl>;
}
