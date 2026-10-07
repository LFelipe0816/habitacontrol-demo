import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { Button, DataTable, Dialog, Field, Tag } from '@/Components';
import { FieldError, PageHeader } from '@/Components/Page';
import { time } from '@/lib/format';
import type { Visitor } from '@/types';

export default function Index({ visitors }: { visitors: Visitor[] }) {
  const [open, setOpen] = useState(false);
  const form = useForm({ name: '', document: '', unit: '', host: '', plate: '' });
  const save = () => form.post('/seguridad/visitas', { preserveScroll: true, onSuccess: () => { setOpen(false); form.reset(); } });
  const out = (id: number) => router.patch(`/seguridad/visitas/${id}/salida`, {}, { preserveScroll: true });
  const f = (k: keyof typeof form.data, label: string, ph?: string) => <div style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
    <Field label={label} value={form.data[k]} placeholder={ph} onChange={e => form.setData(k, e.target.value)} /><FieldError message={form.errors[k]} />
  </div>;

  return <div style={{ display: 'flex', flexDirection: 'column', gap: 20 }}>
    <Head title="Seguridad" />
    <PageHeader kicker="Operación" title="Seguridad" actions={<Button variant="primary" icon="user-plus" onClick={() => setOpen(true)}>Registrar visita</Button>} />
    <DataTable
      columns={[
        { key: 'name', label: 'Visitante' },
        { key: 'document', label: 'Documento', mono: true },
        { key: 'unit', label: 'Unidad', mono: true },
        { key: 'host', label: 'Anfitrión' },
        { key: 'entered_at', label: 'Entrada', render: r => time(r.entered_at) },
        { key: 'left_at', label: 'Salida', render: r => r.left_at ? time(r.left_at) : <Tag tone="outline">Dentro</Tag> },
        { key: 'act', label: '', align: 'right', render: r => !r.left_at ? <Button variant="ghost" onClick={() => out(r.id)}>Marcar salida</Button> : null },
      ]}
      rows={visitors}
    />
    <Dialog open={open} title="Registrar visita" onClose={() => setOpen(false)}
      actions={<><Button variant="ghost" onClick={() => setOpen(false)}>Cancelar</Button><Button variant="primary" disabled={form.processing} onClick={save}>Registrar entrada</Button></>}>
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
        {f('name', 'Nombre')}{f('document', 'Documento')}{f('unit', 'Unidad', 'B-402')}{f('host', 'Anfitrión')}{f('plate', 'Placa')}
      </div>
    </Dialog>
  </div>;
}
