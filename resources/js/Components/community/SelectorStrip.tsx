import React from 'react';

export interface SelectorItem { id: number | string; label: string; /** Second line, e.g. "16 aptos" */ sub?: string; }
export interface SelectorStripProps {
  items: SelectorItem[];
  value?: number | string | null;
  onChange?: (id: number | string) => void;
  /** Accessible name of the group */
  label: string;
}

/** Row of selectable blocks/buildings: wireframe cells, the active one filled with the accent. */
export function SelectorStrip({ items, value, onChange, label }: SelectorStripProps) {
  return <div role="group" aria-label={label} style={{ display: 'flex', flexWrap: 'wrap', gap: 8 }}>
    {items.map(it => {
      const on = it.id === value;
      return <button key={it.id} type="button" aria-pressed={on} onClick={() => onChange?.(it.id)}
        style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-start', gap: 1, minWidth: 84, padding: '7px 12px', cursor: 'pointer', font: 'inherit', textAlign: 'left',
          background: on ? 'var(--color-accent)' : 'transparent', color: on ? 'var(--color-bg)' : 'var(--color-text)', border: '1px solid ' + (on ? 'var(--color-accent)' : 'var(--color-divider)') }}>
        <span style={{ fontFamily: 'var(--font-heading)', fontWeight: 600, fontSize: 15, letterSpacing: '.03em' }}>{it.label}</span>
        {it.sub && <span style={{ fontSize: 11.5, opacity: .75 }}>{it.sub}</span>}
      </button>;
    })}
  </div>;
}
