import React from 'react';

export interface TagProps {
  /** Visual weight. Mono palette: severity is expressed by fill weight, never hue. */
  tone?: 'strong' | 'outline' | 'soft' | 'quiet' | 'ink';
  /** Shortcut: maps known statuses/priorities to a tone (Al día, Por vencer, Vencido, Moroso, Legal, Alta, Media, Baja) */
  status?: string;
  children?: React.ReactNode;
}

const TONES = {
  strong: { background: 'var(--hc-status-strong-bg)', color: 'var(--hc-status-strong-fg)', border: '1px solid var(--color-accent)' },
  outline: { background: 'transparent', color: 'var(--hc-status-mid-fg)', border: '1px solid var(--color-accent)' },
  soft: { background: 'var(--hc-status-soft-bg)', color: 'var(--hc-status-soft-fg)', border: '1px solid transparent' },
  quiet: { background: 'var(--hc-status-quiet-bg)', color: 'var(--hc-status-quiet-fg)', border: '1px solid transparent' },
  ink: { background: 'var(--color-text)', color: 'var(--color-bg)', border: '1px solid var(--color-text)' },
};
export const STATUS_TONE = { 'Al día': 'quiet', 'Por vencer': 'outline', 'Vencido': 'soft', 'Moroso': 'strong', 'Legal': 'ink', Alta: 'strong', Operativo: 'quiet', Mantenimiento: 'outline', 'En revisión': 'soft', 'Atención requerida': 'strong', 'Fuera de servicio': 'ink', Media: 'outline', Baja: 'quiet' };
export function Tag({ tone, status, children }: TagProps) {
  const t = TONES[tone || STATUS_TONE[status] || 'quiet'];
  return <span className="tag" style={t}>{children || status}</span>;
}
