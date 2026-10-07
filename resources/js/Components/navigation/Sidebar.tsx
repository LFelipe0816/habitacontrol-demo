import React from 'react';
import { Icon } from '../core/Icon';

export interface NavItem { id: string; label: string; /** Lucide icon */ icon: string; badge?: number | string; disabled?: boolean; }
export interface SidebarProps {
  community?: string;
  communityMeta?: string;
  sections: { title: string; items: NavItem[] }[];
  active?: string;
  onNavigate?: (id: string) => void;
  user?: { initials: string; name: string; role: string };
}

export function Sidebar({ community = 'Residencial Los Robles', communityMeta = '3 torres · 180 unidades', sections, active, onNavigate, user = { initials: 'MR', name: 'María Rosario', role: 'Administradora' } }: SidebarProps) {
  return <aside style={{ width: 'var(--hc-sidebar-w)', borderRight: '1px solid var(--color-divider)', display: 'flex', flexDirection: 'column', height: '100%' }}>
    <div style={{ height: 'var(--hc-topbar-h)', display: 'flex', alignItems: 'center', gap: 10, padding: '0 18px', borderBottom: '1px solid var(--color-divider)' }}>
      <div className="blueprint" style={{ width: 22, height: 22, display: 'flex', alignItems: 'center', justifyContent: 'center' }}><i className="corner tl" /><i className="corner tr" /><i className="corner bl" /><i className="corner br" /><div style={{ width: 8, height: 8, background: 'var(--color-accent)' }} /></div>
      <span style={{ fontFamily: 'var(--font-heading)', fontWeight: 600, fontSize: 19, letterSpacing: '.04em', textTransform: 'uppercase' }}>Habita<span style={{ color: 'var(--color-accent)' }}>Control</span></span>
    </div>
    <div style={{ padding: '14px 18px', borderBottom: '1px solid var(--color-divider)', display: 'flex', flexDirection: 'column', gap: 2 }}>
      <span style={{ fontSize: 10, letterSpacing: '.1em', textTransform: 'uppercase', color: 'var(--color-accent-700)' }}>Comunidad</span>
      <span style={{ fontSize: 14, fontWeight: 500 }}>{community}</span>
      <span style={{ fontSize: 12, color: 'var(--hc-muted)' }}>{communityMeta}</span>
    </div>
    <nav style={{ flex: 1, overflow: 'auto', padding: '10px 0' }}>
      {sections.map(sec => <div key={sec.title}>
        <div style={{ padding: '14px 18px 6px', fontSize: 10, letterSpacing: '.1em', textTransform: 'uppercase', color: 'var(--hc-muted)' }}>{sec.title}</div>
        {sec.items.map(it => { const on = it.id === active; return <div key={it.id} onClick={it.disabled ? undefined : () => onNavigate && onNavigate(it.id)}
          style={{ display: 'flex', alignItems: 'center', gap: 10, padding: '7px 18px', fontSize: 14, cursor: it.disabled ? 'default' : 'pointer', opacity: it.disabled ? .45 : 1, color: on ? 'var(--color-accent-700)' : 'var(--color-text)', background: on ? 'var(--hc-faint)' : 'transparent', borderLeft: '2px solid ' + (on ? 'var(--color-accent)' : 'transparent') }}>
          <Icon name={it.icon} /><span style={{ flex: 1 }}>{it.label}</span>{it.badge != null && <span style={{ fontFamily: 'var(--hc-mono)', fontSize: 11, color: 'var(--color-accent-700)' }}>{it.badge}</span>}
        </div>; })}
      </div>)}
    </nav>
    <div style={{ padding: '12px 18px', borderTop: '1px solid var(--color-divider)', display: 'flex', alignItems: 'center', gap: 10 }}>
      <div style={{ width: 30, height: 30, border: '1px solid var(--color-divider)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontFamily: 'var(--font-heading)', fontWeight: 600, fontSize: 13 }}>{user.initials}</div>
      <div style={{ display: 'flex', flexDirection: 'column', lineHeight: 1.25 }}><span style={{ fontSize: 13, fontWeight: 500 }}>{user.name}</span><span style={{ fontSize: 11.5, color: 'var(--hc-muted)' }}>{user.role}</span></div>
    </div>
  </aside>;
}
