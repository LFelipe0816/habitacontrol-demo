import React, { useEffect, useRef, useState } from 'react';
import { Icon } from '../core/Icon';

export interface UnitOption { id: number; code: string; label: string; balance?: number; }
export interface UnitPickerProps {
  value: UnitOption | null;
  onChange: (unit: UnitOption | null) => void;
  placeholder?: string;
}

/** Search-as-you-type apartment selector backed by GET /unidades/buscar (a community can have 1,000+ units). */
export function UnitPicker({ value, onChange, placeholder = 'Buscar apartamento (A01-204)…' }: UnitPickerProps) {
  const [q, setQ] = useState('');
  const [options, setOptions] = useState<UnitOption[]>([]);
  const [open, setOpen] = useState(false);
  const seq = useRef(0);

  useEffect(() => {
    if (!open) return;
    const mine = ++seq.current;
    const t = setTimeout(async () => {
      const res = await fetch('/unidades/buscar?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
      if (res.ok && mine === seq.current) setOptions(await res.json());   // descarta respuestas viejas
    }, 200);
    return () => clearTimeout(t);
  }, [q, open]);

  if (value) return <div className="input" style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
    <span style={{ flex: 1 }}>{value.label}</span>
    <button type="button" aria-label="Quitar apartamento" onClick={() => onChange(null)} style={{ all: 'unset', cursor: 'pointer', display: 'flex' }}><Icon name="x" size={15} /></button>
  </div>;

  return <div style={{ position: 'relative' }}>
    <input className="input" role="combobox" aria-expanded={open} value={q} placeholder={placeholder} onFocus={() => setOpen(true)} onChange={e => { setQ(e.target.value); setOpen(true); }} onBlur={() => setTimeout(() => setOpen(false), 150)} />
    {open && options.length > 0 && <ul role="listbox" style={{ position: 'absolute', zIndex: 5, left: 0, right: 0, margin: 0, padding: 0, listStyle: 'none', background: 'var(--color-bg)', border: '1px solid var(--color-divider)', boxShadow: 'var(--shadow-md)', maxHeight: 220, overflow: 'auto' }}>
      {options.map(o => <li key={o.id} role="option" aria-selected={false} onMouseDown={() => { onChange(o); setQ(''); setOpen(false); }} style={{ padding: '8px 12px', cursor: 'pointer', fontSize: 14, borderBottom: '1px solid var(--hc-line)' }}>{o.label}</li>)}
    </ul>}
  </div>;
}
