import React from 'react';
import { Tag } from '../core/Tag';

export interface TimelineEntry { who: string; when: string; text: string; /** res = visible to resident, int = internal note, acc = action taken, omitted = system event */ kind?: 'res' | 'int' | 'acc'; }
export interface CaseTimelineProps { entries: TimelineEntry[]; }

const KIND = { res: ['Respuesta al residente', 'soft'], int: ['Nota interna', 'outline'], acc: ['Acción tomada', 'quiet'] };
export function CaseTimeline({ entries }: CaseTimelineProps) {
  return <div style={{ display: 'flex', flexDirection: 'column' }}>
    {entries.map((l, i) => { const k = KIND[l.kind]; return <div key={i} style={{ display: 'grid', gridTemplateColumns: '14px 1fr', gap: 14 }}>
      <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center' }}><span style={{ width: 9, height: 9, marginTop: 6, border: '1px solid var(--color-accent)', background: k ? 'var(--color-accent)' : 'transparent' }} /><span style={{ flex: 1, width: 1, background: 'var(--color-divider)' }} /></div>
      <div style={{ paddingBottom: 16, display: 'flex', flexDirection: 'column', gap: 3 }}>
        <div style={{ display: 'flex', gap: 8, alignItems: 'center', flexWrap: 'wrap' }}><span style={{ fontSize: 14, fontWeight: 500 }}>{l.who}</span><span style={{ fontSize: 12, color: 'var(--hc-muted)' }}>{l.when}</span>{k && <Tag tone={k[1]}>{k[0]}</Tag>}</div>
        <span style={{ fontSize: 14, lineHeight: 1.5, color: k ? 'var(--color-text)' : 'var(--hc-muted)' }}>{l.text}</span>
      </div>
    </div>; })}
  </div>;
}
