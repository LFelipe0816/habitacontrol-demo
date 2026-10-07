import React from 'react';
import { Button } from '../core/Button';

export interface PagerProps {
  meta: { current_page: number; last_page: number; total: number };
  onPage: (page: number) => void;
  /** e.g. "cobros" */
  noun?: string;
}

export function Pager({ meta, onPage, noun = 'registros' }: PagerProps) {
  if (meta.last_page <= 1) return <span style={{ fontSize: 12.5, color: 'var(--hc-muted)' }}>{meta.total} {noun}</span>;
  return <div style={{ display: 'flex', alignItems: 'center', gap: 12, fontSize: 13, color: 'var(--hc-muted)' }}>
    <Button variant="ghost" icon="chevron-left" title="Página anterior" disabled={meta.current_page <= 1} onClick={() => onPage(meta.current_page - 1)} />
    <span>Página {meta.current_page} de {meta.last_page} · {meta.total} {noun}</span>
    <Button variant="ghost" icon="chevron-right" title="Página siguiente" disabled={meta.current_page >= meta.last_page} onClick={() => onPage(meta.current_page + 1)} />
  </div>;
}
