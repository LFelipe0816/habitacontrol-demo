import React from 'react';

export interface MeterProps {
  label: string;
  /** 0–100 */
  value: number;
  /** Fill with the strong accent when true (low reading / needs action) */
  warn?: boolean;
}

/** Labelled horizontal gauge. Fill weight, not hue, signals concern (mono palette). */
export function Meter({ label, value, warn }: MeterProps) {
  const v = Math.max(0, Math.min(100, value));
  return <div style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
    <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 12.5, color: 'var(--hc-muted)' }}>
      <span>{label}</span><span style={{ fontFamily: 'var(--hc-mono)', color: 'var(--color-text)' }}>{v}%</span>
    </div>
    <div role="meter" aria-label={label} aria-valuenow={v} aria-valuemin={0} aria-valuemax={100} style={{ height: 8, background: 'var(--hc-faint)', border: '1px solid var(--color-divider)' }}>
      <div style={{ width: `${v}%`, height: '100%', background: warn ? 'var(--color-accent-700)' : 'var(--color-accent)', opacity: warn ? 1 : .8 }} />
    </div>
  </div>;
}
