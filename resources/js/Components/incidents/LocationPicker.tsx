import React from 'react';
import { Field } from '../core/Field';
import { Segmented } from '../core/Segmented';

export interface LocationValue { location_type: string; block_id: string; building_id: string; unit_id: string; street_id: string; reference: string; }
export interface LocationPickerProps {
  value: LocationValue;
  onChange: (patch: Partial<LocationValue>) => void;
  /** key → label, e.g. apartment → Apartamento */
  types: Record<string, string>;
  blocks: { id: number; name: string }[];
  buildings: { id: number; block_id: number | null; name: string }[];
  streets: { id: number; name: string }[];
  units: { id: number; number: string; building_id: number | null }[];
  /** Residents pick among their own apartments: no block/building drill-down */
  ownUnitsOnly?: boolean;
  errors?: Partial<Record<keyof LocationValue, string>>;
}

const Select = ({ value, onChange, children, label, error }: { value: string; onChange: (v: string) => void; children: React.ReactNode; label: string; error?: string }) =>
  <Field label={label}>
    <select className="input" value={value} onChange={e => onChange(e.target.value)} aria-invalid={!!error}>{children}</select>
    {error && <span style={{ fontSize: 12, color: 'var(--color-accent-700)' }}>{error}</span>}
  </Field>;

/** Cascading where-is-it selector: type → block → building → apartment | street | free reference. */
export function LocationPicker({ value: v, onChange, types, blocks, buildings, streets, units, ownUnitsOnly, errors = {} }: LocationPickerProps) {
  const label = types[v.location_type];
  const shownBuildings = buildings.filter(b => !v.block_id || String(b.block_id) === v.block_id);
  const t = v.location_type;
  return <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
    <Field label="¿Dónde ocurre?">
      <Segmented options={Object.values(types)} value={label} onChange={l => onChange({ location_type: Object.keys(types).find(k => types[k] === l)!, block_id: '', building_id: '', unit_id: '', street_id: '' })} />
    </Field>
    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit,minmax(200px,1fr))', gap: 14 }}>
      {(t === 'block' || (t === 'apartment' && !ownUnitsOnly)) && <Select label="Manzana" value={v.block_id} error={errors.block_id} onChange={id => onChange({ block_id: id, building_id: '', unit_id: '' })}>
        <option value="">Selecciona…</option>{blocks.map(b => <option key={b.id} value={b.id}>{b.name}</option>)}</Select>}
      {(t === 'building' || (t === 'apartment' && !ownUnitsOnly)) && <Select label="Edificio" value={v.building_id} error={errors.building_id} onChange={id => onChange({ building_id: id, unit_id: '' })}>
        <option value="">Selecciona…</option>{shownBuildings.map(b => <option key={b.id} value={b.id}>{b.name}</option>)}</Select>}
      {t === 'apartment' && <Select label="Apartamento" value={v.unit_id} error={errors.unit_id} onChange={id => onChange({ unit_id: id })}>
        <option value="">{ownUnitsOnly || v.building_id ? 'Selecciona…' : 'Elige un edificio primero'}</option>
        {units.filter(u => ownUnitsOnly || String(u.building_id) === v.building_id).map(u => <option key={u.id} value={u.id}>Apto {u.number}</option>)}</Select>}
      {t === 'street' && <Select label="Calle" value={v.street_id} error={errors.street_id} onChange={id => onChange({ street_id: id })}>
        <option value="">Selecciona…</option>{streets.map(s => <option key={s.id} value={s.id}>{s.name}</option>)}</Select>}
    </div>
    <Field label={t === 'common_area' ? 'Área o referencia' : 'Referencia exacta (opcional)'} value={v.reference}
      onChange={e => onChange({ reference: e.target.value })} placeholder={t === 'common_area' ? 'Salón multiuso, cisterna, garita…' : 'Frente al edificio A07'} />
    {errors.reference && <span style={{ fontSize: 12, color: 'var(--color-accent-700)' }}>{errors.reference}</span>}
  </div>;
}
