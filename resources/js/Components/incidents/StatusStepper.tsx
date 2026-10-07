import React from 'react';

export interface StatusStepperProps {
  /** Defaults to the 6 Inciden 360 states */
  steps?: string[];
  current: string;
  onSelect?: (state: string) => void;
}

export const INCIDENT_STATES = ['Recibida', 'En revisión', 'Asignada', 'En gestión', 'Resuelta', 'Cerrada'];
export function StatusStepper({ steps = INCIDENT_STATES, current, onSelect }: StatusStepperProps) {
  const si = steps.indexOf(current);
  return <div style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
    {steps.map((label, i) => <div key={label} onClick={onSelect ? () => onSelect(label) : undefined} style={{ display: 'flex', alignItems: 'center', gap: 10, padding: '6px 8px', cursor: onSelect ? 'pointer' : 'default', background: i === si ? 'var(--hc-faint)' : 'transparent', fontSize: 14, color: i <= si ? 'var(--color-text)' : 'var(--hc-muted)' }}>
      <span style={{ width: 12, height: 12, border: '1px solid ' + (i <= si ? 'var(--color-accent)' : 'var(--color-divider)'), background: i < si ? 'var(--color-accent)' : i === si ? 'color-mix(in srgb,var(--color-accent) 40%,transparent)' : 'transparent' }} />
      <span style={{ flex: 1, fontWeight: i === si ? 600 : 400 }}>{label}</span>
      <span style={{ fontFamily: 'var(--hc-mono)', fontSize: 11, color: 'var(--hc-muted)' }}>{String(i + 1).padStart(2, '0')}</span>
    </div>)}
  </div>;
}
