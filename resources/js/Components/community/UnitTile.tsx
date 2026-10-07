import React from 'react';

export interface UnitTileProps {
  number: string;
  occupancy: string;
  /** Deuda vigente; > 0 fills the tile (severity = fill weight, never hue) */
  balance?: number;
  onClick?: () => void;
}

export function UnitTile({ number, occupancy, balance = 0, onClick }: UnitTileProps) {
  const vacant = occupancy === 'vacante';
  const owing = balance > 0;
  return <button type="button" onClick={onClick} title={`Apto ${number} · ${vacant ? 'Vacante' : occupancy === 'alquilado' ? 'Alquilado' : 'Ocupado'}${owing ? ' · con deuda' : ''}`}
    style={{ display: 'flex', flexDirection: 'column', gap: 4, alignItems: 'flex-start', padding: '10px 12px', cursor: 'pointer', font: 'inherit',
      background: owing ? 'var(--hc-status-strong-bg)' : 'transparent', color: owing ? 'var(--hc-status-strong-fg)' : 'var(--color-text)',
      border: `1px ${vacant ? 'dashed' : 'solid'} ${owing ? 'var(--color-accent)' : 'var(--color-divider)'}` }}>
    <span style={{ fontFamily: 'var(--font-heading)', fontWeight: 600, fontSize: 20, lineHeight: 1 }}>{number}</span>
    <span style={{ fontSize: 11, letterSpacing: '.04em', textTransform: 'uppercase', opacity: .75 }}>{vacant ? 'Vacante' : occupancy === 'alquilado' ? 'Alquilado' : 'Ocupado'}</span>
  </button>;
}
