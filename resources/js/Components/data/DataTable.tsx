import React from 'react';

export interface Column { key: string; label: string; align?: 'left' | 'right'; /** Monospace accent ID column (unidad, caso) */ mono?: boolean; render?: (row: any) => React.ReactNode; }
export interface DataTableProps {
  columns: Column[];
  rows: any[];
  onRowClick?: (row: any) => void;
  /** Text shown when there are no rows */
  empty?: string;
}

export function DataTable({ columns, rows, onRowClick, empty = 'Sin registros' }: DataTableProps) {
  return <div className="blueprint" style={{ padding: '4px 12px 8px', overflowX: 'auto' }}>
    <i className="corner tl" /><i className="corner tr" /><i className="corner bl" /><i className="corner br" />
    <table className="table"><thead><tr>{columns.map(c => <th key={c.key} style={{ textAlign: c.align || 'left' }}>{c.label}</th>)}</tr></thead>
      <tbody>{rows.length === 0 && <tr><td colSpan={columns.length} style={{ color: 'var(--hc-muted)', textAlign: 'center', padding: 18 }}>{empty}</td></tr>}{rows.map((r, i) => <tr key={i} onClick={onRowClick ? () => onRowClick(r) : undefined} style={{ cursor: onRowClick ? 'pointer' : 'default' }}>
        {columns.map(c => <td key={c.key} style={{ textAlign: c.align || 'left', fontFamily: c.mono ? 'var(--hc-mono)' : undefined, fontSize: c.mono ? 13 : undefined, color: c.mono ? 'var(--color-accent-700)' : undefined, fontVariantNumeric: c.align === 'right' ? 'tabular-nums' : undefined }}>{c.render ? c.render(r) : r[c.key]}</td>)}
      </tr>)}</tbody></table>
  </div>;
}
