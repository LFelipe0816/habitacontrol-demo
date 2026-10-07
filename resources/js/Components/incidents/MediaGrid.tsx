import React from 'react';

export interface MediaItem { id?: number; url?: string; label: string; mime?: string | null; }
export interface MediaGridProps { items: MediaItem[]; empty?: string; }

/** Thumbnails for case evidence: photos open full size, videos play inline. */
export function MediaGrid({ items, empty = 'Sin archivos.' }: MediaGridProps) {
  if (items.length === 0) return <span style={{ fontSize: 13, color: 'var(--hc-muted)' }}>{empty}</span>;
  return <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill,minmax(150px,1fr))', gap: 10 }}>
    {items.map((m, i) => {
      const box: React.CSSProperties = { aspectRatio: '4/3', border: '1px solid var(--color-divider)', overflow: 'hidden', display: 'block', background: 'var(--hc-faint)' };
      return <figure key={m.id ?? i} style={{ margin: 0, display: 'flex', flexDirection: 'column', gap: 4 }}>
        {m.mime?.startsWith('video/')
          ? <video src={m.url} controls preload="metadata" style={{ ...box, objectFit: 'cover', width: '100%' }} />
          : <a href={m.url} target="_blank" rel="noreferrer" style={box}><img src={m.url} alt={m.label} style={{ width: '100%', height: '100%', objectFit: 'cover' }} /></a>}
        <figcaption style={{ fontFamily: 'var(--hc-mono)', fontSize: 10.5, color: 'var(--hc-muted)', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{m.label}</figcaption>
      </figure>;
    })}
  </div>;
}
