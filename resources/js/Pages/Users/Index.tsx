import { useEffect, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { Button, DataTable, Dialog, Field, Pager, Segmented, Tag, UnitPicker } from '@/Components';
import type { UnitOption } from '@/Components';
import { FieldError, PageHeader } from '@/Components/Page';
import type { ManagedUser, Page } from '@/types';

type Props = { users: Page<ManagedUser>; filters: { q?: string; role?: string; estado?: string }; roles: Record<string, string>; allRoles: Record<string, string> };
type Link = { unit_id: number; code: string; relation: 'propietario' | 'residente' | 'inquilino' };
const RELATIONS = ['propietario', 'residente', 'inquilino'] as const;
const suggest = () => Array.from(crypto.getRandomValues(new Uint8Array(10)), b => 'abcdefghjkmnpqrstuvwxyz23456789'[b % 31]).join('');
const keep = { preserveScroll: true };

export default function Index({ users, filters, roles, allRoles }: Props) {
  const [editing, setEditing] = useState<ManagedUser | 'new' | null>(null);
  const [search, setSearch] = useState(filters.q ?? '');
  const [pick, setPick] = useState<UnitOption | null>(null);
  const form = useForm({ name: '', email: '', password: '', role: 'residente', phone: '', document_id: '', active: true, units: [] as Link[] });

  const go = (q: Record<string, string | number | undefined>) => router.get('/usuarios', Object.fromEntries(Object.entries({ ...filters, ...q }).filter(([, v]) => v)) as Record<string, string>, { preserveState: true, replace: true });
  useEffect(() => {
    if (search === (filters.q ?? '')) return;
    const t = setTimeout(() => go({ q: search, page: undefined }), 300);
    return () => clearTimeout(t);
  }, [search]);

  const open = (u: ManagedUser | 'new') => {
    form.clearErrors(); setPick(null);
    form.setData(u === 'new' ? { name: '', email: '', password: suggest(), role: 'residente', phone: '', document_id: '', active: true, units: [] }
      : { name: u.name, email: u.email, password: '', role: u.role, phone: u.phone ?? '', document_id: u.document_id ?? '', active: u.active, units: u.units });
    setEditing(u);
  };
  const close = () => setEditing(null);
  const save = () => editing === 'new' ? form.post('/usuarios', { ...keep, onSuccess: close }) : form.patch(`/usuarios/${(editing as ManagedUser).id}`, { ...keep, onSuccess: close });
  const addUnit = (u: UnitOption | null) => { if (u && !form.data.units.some(l => l.unit_id === u.id)) form.setData('units', [...form.data.units, { unit_id: u.id, code: u.code, relation: form.data.role === 'propietario' ? 'propietario' : 'residente' }]); setPick(null); };
  const err = (k: string) => (form.errors as Record<string, string | undefined>)[k];
  const unitError = Object.entries(form.errors).find(([k]) => k.startsWith('units'))?.[1];

  return <div style={{ display: 'flex', flexDirection: 'column', gap: 24 }}>
    <Head title="Usuarios" />
    <PageHeader kicker="Administración" title="Usuarios y accesos" actions={<Button variant="primary" icon="user-plus" onClick={() => open('new')}>Nuevo usuario</Button>} />
    <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap', alignItems: 'center' }}>
      <input className="input" placeholder="Buscar por nombre o correo" aria-label="Buscar usuarios" value={search} onChange={e => setSearch(e.target.value)} style={{ maxWidth: 300 }} />
      <select className="input" aria-label="Rol" value={filters.role ?? ''} onChange={e => go({ role: e.target.value, page: undefined })} style={{ maxWidth: 200 }}>
        <option value="">Todos los roles</option>{Object.entries(allRoles).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
      </select>
      <Segmented options={['Todos', 'Activos', 'Inactivos']} value={filters.estado ? filters.estado[0].toUpperCase() + filters.estado.slice(1) : 'Todos'} onChange={v => go({ estado: v === 'Todos' ? undefined : v.toLowerCase(), page: undefined })} />
    </div>
    <DataTable empty="Sin usuarios con esos filtros." onRowClick={(r: ManagedUser) => open(r)} rows={users.data} columns={[
      { key: 'name', label: 'Usuario', render: (r: ManagedUser) => <><strong style={{ fontWeight: 500 }}>{r.name}</strong><br /><span style={{ fontSize: 12.5, color: 'var(--hc-muted)' }}>{r.email}</span></> },
      { key: 'role_label', label: 'Rol' },
      { key: 'units', label: 'Apartamentos', render: (r: ManagedUser) => r.units.length ? r.units.map(u => u.code).join(', ') : 'No aplica' },
      { key: 'active', label: 'Acceso', render: (r: ManagedUser) => <Tag tone={r.active ? 'quiet' : 'outline'}>{r.active ? 'Activo' : 'Desactivado'}</Tag> },
    ]} />
    <Pager meta={users.meta} noun="usuarios" onPage={p => go({ page: p })} />

    <Dialog open={editing !== null} title={editing === 'new' ? 'Nuevo usuario' : 'Editar usuario'} onClose={close}
      actions={<><Button variant="ghost" onClick={close}>Cancelar</Button><Button variant="primary" disabled={form.processing} onClick={save}>{editing === 'new' ? 'Crear usuario' : 'Guardar'}</Button></>}>
      <div style={{ display: 'flex', flexDirection: 'column', gap: 12, maxHeight: '65vh', overflow: 'auto', paddingRight: 4 }}>
        <Field label="Nombre" value={form.data.name} onChange={e => form.setData('name', e.target.value)} required /><FieldError message={form.errors.name} />
        <Field label="Correo" type="email" value={form.data.email} onChange={e => form.setData('email', e.target.value)} required /><FieldError message={form.errors.email} />
        <Field label={editing === 'new' ? 'Contraseña inicial' : 'Nueva contraseña (opcional)'} value={form.data.password} onChange={e => form.setData('password', e.target.value)} placeholder={editing === 'new' ? '' : 'Déjala vacía para no cambiarla'} />
        <FieldError message={form.errors.password} />
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
          <Field label="Rol"><select className="input" value={form.data.role} onChange={e => form.setData('role', e.target.value)}>{Object.entries(roles).map(([k, v]) => <option key={k} value={k}>{v}</option>)}</select></Field>
          <Field label="Cédula" value={form.data.document_id} onChange={e => form.setData('document_id', e.target.value)} />
        </div>
        <Field label="Teléfono" value={form.data.phone} onChange={e => form.setData('phone', e.target.value)} />
        <FieldError message={form.errors.role || err('phone')} />

        <Field label="Apartamentos vinculados"><UnitPicker value={pick} onChange={addUnit} placeholder="Agregar apartamento…" /></Field>
        {form.data.units.map((l, i) => <div key={l.unit_id} style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
          <span style={{ fontFamily: 'var(--hc-mono)', fontSize: 13, minWidth: 80 }}>{l.code}</span>
          <select className="input" aria-label={`Relación con ${l.code}`} value={l.relation} onChange={e => form.setData('units', form.data.units.map((x, j) => j === i ? { ...x, relation: e.target.value as Link['relation'] } : x))}>{RELATIONS.map(r => <option key={r}>{r}</option>)}</select>
          <Button variant="ghost" icon="x" title="Quitar" onClick={() => form.setData('units', form.data.units.filter((_, j) => j !== i))} />
        </div>)}
        <FieldError message={unitError} />

        {editing !== 'new' && <label className="radio"><input type="checkbox" checked={form.data.active} onChange={e => form.setData('active', e.target.checked)} /><span className="dot" />Acceso activo</label>}
      </div>
    </Dialog>
  </div>;
}
