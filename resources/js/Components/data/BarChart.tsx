import React from 'react';

export interface BarChartProps {
  /** a = outlined series (e.g. facturado), b = solid series (e.g. cobrado) */
  data: { label: string; a: number; b: number }[];
  max?: number;
  height?: number;
  legend?: [string, string];
}

export function BarChart({ data, max, height = 220, legend = ['Facturado', 'Cobrado'] }: BarChartProps) {
  const m = max || Math.max(...data.flatMap(d => [d.a, d.b])) * 1.05;
  const h = v => Math.round(v / m * (height - 2)) + 'px';
  return <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
    <div style={{ display: 'flex', gap: 14, fontSize: 12, color: 'var(--hc-muted)' }}>
      <span style={{ display: 'flex', alignItems: 'center', gap: 6 }}><span style={{ width: 10, height: 10, background: 'color-mix(in srgb,var(--color-accent) 30%,transparent)', border: '1px solid var(--color-accent)' }} />{legend[0]}</span>
      <span style={{ display: 'flex', alignItems: 'center', gap: 6 }}><span style={{ width: 10, height: 10, background: 'var(--color-accent)' }} />{legend[1]}</span>
    </div>
    <div style={{ height, display: 'grid', gridTemplateColumns: `repeat(${data.length},1fr)`, alignItems: 'end', borderBottom: '1px solid var(--color-divider)', backgroundImage: 'linear-gradient(var(--hc-line) 1px,transparent 1px)', backgroundSize: '100% ' + height / 4 + 'px' }}>
      {data.map(d => <div key={d.label} style={{ display: 'flex', justifyContent: 'center', alignItems: 'flex-end', gap: 5, height: '100%' }}>
        <div style={{ width: 20, height: h(d.a), background: 'color-mix(in srgb,var(--color-accent) 30%,transparent)', border: '1px solid var(--color-accent)', borderBottom: 0 }} />
        <div style={{ width: 20, height: h(d.b), background: 'var(--color-accent)' }} />
      </div>)}
    </div>
    <div style={{ display: 'grid', gridTemplateColumns: `repeat(${data.length},1fr)`, fontSize: 12, color: 'var(--hc-muted)', textAlign: 'center' }}>{data.map(d => <span key={d.label}>{d.label}</span>)}</div>
  </div>;
}
