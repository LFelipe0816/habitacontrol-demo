import { FormEvent } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { Blueprint, Button, Field, FilePicker, LocationPicker, Segmented } from '@/Components';
import type { LocationValue } from '@/Components';
import { FieldError, PageHeader } from '@/Components/Page';
import type { Priority } from '@/types';

type Props = {
  types: string[]; scopes: string[]; locationTypes: Record<string, string>; communities: { id: number; name: string }[]; communityId: number;
  blocks: { id: number; name: string }[]; buildings: { id: number; block_id: number | null; name: string }[]; streets: { id: number; name: string }[];
  units: { id: number; number: string; building_id: number | null }[];
  prefill: { title: string; reference: string; type: string | null; scope: string | null };
};

export default function Create({ types, scopes, locationTypes, communities, communityId, blocks, buildings, streets, units, prefill }: Props) {
  // Residentes reciben solo sus apartamentos (sin lista de residenciales): eligen la unidad directamente.
  const ownUnitsOnly = communities.length === 0;
  const { data, setData, post, processing, errors, progress } = useForm({
    community_id: String(communityId), title: prefill.title, type: prefill.type ?? types[0] ?? '', scope: prefill.scope ?? scopes[0] ?? '', priority: 'Media' as Priority, description: '',
    location_type: ownUnitsOnly ? 'apartment' : 'common_area', block_id: '', building_id: '', unit_id: ownUnitsOnly && units.length === 1 ? String(units[0].id) : '', street_id: '', reference: prefill.reference, evidence: [] as File[],
  });
  const submit = (e: FormEvent) => { e.preventDefault(); post('/incidencias'); };

  // El personal puede reportar en cualquiera de sus residenciales: al cambiar, se recargan las opciones de ubicación.
  const changeCommunity = (id: string) => router.get('/incidencias/crear', { residencial: id }, { preserveState: false });
  // Los apartamentos de un edificio se piden bajo demanda (un residencial puede tener cientos).
  const patchLocation = (patch: Partial<LocationValue>) => {
    setData(d => ({ ...d, ...patch }));
    if (patch.building_id) router.reload({ data: { residencial: data.community_id, edificio: patch.building_id }, only: ['units'] });
  };
  const err = (k: string) => (errors as Record<string, string | undefined>)[k];

  return <div style={{ maxWidth: 760, display: 'flex', flexDirection: 'column', gap: 4 }}>
    <Head title="Reportar incidencia" />
    <PageHeader kicker="Inciden 360" title="Reportar incidencia" />
    <Blueprint as="form" onSubmit={submit} style={{ padding: 24, display: 'flex', flexDirection: 'column', gap: 20 }}>
      {communities.length > 1 && <Field label="Residencial">
        <select className="input" value={data.community_id} onChange={e => changeCommunity(e.target.value)}>{communities.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}</select>
      </Field>}

      <Field label="Asunto" value={data.title} onChange={e => setData('title', e.target.value)} placeholder="Bombilla dañada en calle" required />
      <FieldError message={errors.title} />
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit,minmax(200px,1fr))', gap: 14 }}>
        <Field label="Tipo"><select className="input" value={data.type} onChange={e => setData('type', e.target.value)}>{types.map(t => <option key={t}>{t}</option>)}</select></Field>
        <Field label="Categoría"><select className="input" value={data.scope} onChange={e => setData('scope', e.target.value)}>{scopes.map(t => <option key={t}>{t}</option>)}</select></Field>
      </div>

      <LocationPicker value={data} onChange={patchLocation} types={locationTypes} blocks={blocks} buildings={buildings} streets={streets} units={units} ownUnitsOnly={ownUnitsOnly} errors={errors} />

      <Field label="Prioridad"><Segmented options={['Baja', 'Media', 'Alta']} value={data.priority} onChange={v => setData('priority', v as Priority)} /></Field>
      <Field label="Detalle" multiline rows={4} value={data.description} onChange={e => setData('description', e.target.value)} placeholder="Qué ocurre, dónde se ve y qué impacto tiene." />
      <FieldError message={errors.description} />

      <Field label="Fotos o videos del problema"><FilePicker files={data.evidence} onChange={f => setData('evidence', f)} /></Field>
      <FieldError message={err('evidence') || Object.entries(errors).find(([k]) => k.startsWith('evidence.'))?.[1]} />
      {progress && <span style={{ fontSize: 12, color: 'var(--hc-muted)' }}>Subiendo {progress.percentage}%</span>}

      <div style={{ display: 'flex', gap: 8 }}>
        <Button variant="primary" type="submit" disabled={processing}>{processing ? 'Enviando…' : 'Enviar reporte'}</Button>
        <Button variant="ghost" type="button" onClick={() => history.back()}>Cancelar</Button>
      </div>
    </Blueprint>
  </div>;
}
