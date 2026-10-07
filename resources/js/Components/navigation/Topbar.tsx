import React from 'react';
import { Icon } from '../core/Icon';

export interface TopbarProps {
  /** Breadcrumb trail; last item is current */
  crumbs?: string[];
  searchPlaceholder?: string;
  /** Right-side actions (icon buttons) */
  children?: React.ReactNode;
}

export function Topbar({ crumbs = [], searchPlaceholder = 'Buscar unidad, residente, caso…', children }: TopbarProps) {
  return <header style={{ height: 'var(--hc-topbar-h)', borderBottom: '1px solid var(--color-divider)', display: 'flex', alignItems: 'center', gap: 12, padding: '0 28px', background: 'var(--color-bg)' }}>
    <div style={{ display: 'flex', gap: 8, fontSize: 13, color: 'var(--hc-muted)', whiteSpace: 'nowrap', overflow: 'hidden', minWidth: 0 }}>
      {crumbs.map((c, i) => <React.Fragment key={i}>{i > 0 && <span>/</span>}<span style={{ color: i === crumbs.length - 1 ? 'var(--color-text)' : undefined }}>{c}</span></React.Fragment>)}
    </div>
    <div style={{ flex: 1 }} />
    <div style={{ width: 260, display: 'flex', alignItems: 'center', gap: 8, height: 34, padding: '0 10px', border: '1px solid var(--color-divider)', fontSize: 13, color: 'var(--hc-muted)' }}><Icon name="search" size={15} />{searchPlaceholder}</div>
    {children}
  </header>;
}
