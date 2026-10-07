import React from 'react';
import { Tag } from '../core/Tag';

export interface IncidentCardProps {
  id: string;
  title: string;
  type: string;
  location: string;
  priority?: 'Alta' | 'Media' | 'Baja';
  /** e.g. "3 archivos + 2" */
  evidence?: string;
  age?: string;
  /** Assignee initials */
  assignee?: string;
  recurring?: boolean;
  /** Show evidence thumbnail placeholder */
  thumbnail?: boolean;
  /** URL of the first evidence photo, shown as the card cover */
  image?: string;
  onClick?: () => void;
}

export function IncidentCard({ id, title, type, location, priority = 'Media', evidence, age, assignee, recurring, thumbnail, image, onClick }: IncidentCardProps) {
  return <div className="blueprint" onClick={onClick} style={{ padding: 12, display: 'flex', flexDirection: 'column', gap: 8, cursor: onClick ? 'pointer' : 'default' }}>
    <i className="corner tl" /><i className="corner tr" /><i className="corner bl" /><i className="corner br" />
    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}><span style={{ fontFamily: 'var(--hc-mono)', fontSize: 11, color: 'var(--hc-muted)' }}>{id}</span><Tag status={priority} /></div>
    {image && <img src={image} alt="" loading="lazy" style={{ height: 84, width: '100%', objectFit: 'cover', border: '1px solid var(--color-divider)' }} />}
    {thumbnail && !image && <div style={{ height: 64, border: '1px solid var(--color-divider)', background: 'repeating-linear-gradient(135deg,var(--hc-line) 0 6px,transparent 6px 12px)', display: 'flex', alignItems: 'flex-end', padding: '4px 6px', fontFamily: 'var(--hc-mono)', fontSize: 10, color: 'var(--hc-muted)' }}>evidencia · {evidence}</div>}
    <span style={{ fontSize: 14.5, fontWeight: 500, lineHeight: 1.3 }}>{title}</span>
    <span style={{ fontSize: 12.5, color: 'var(--hc-muted)' }}>{type} · {location}</span>
    <div style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 11.5, color: 'var(--hc-muted)', borderTop: '1px solid var(--hc-line)', paddingTop: 7 }}>
      <span>{evidence}</span>{recurring && <span style={{ color: 'var(--color-accent-700)', fontWeight: 500 }}>↻ reincide</span>}
      <span style={{ marginLeft: 'auto' }}>{age}</span>
      <span style={{ width: 22, height: 22, border: '1px solid var(--color-divider)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontFamily: 'var(--font-heading)', fontWeight: 600, fontSize: 10.5, color: 'var(--color-text)' }}>{assignee || '—'}</span>
    </div>
  </div>;
}
