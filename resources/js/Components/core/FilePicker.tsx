import React, { useRef } from 'react';
import { Button } from './Button';
import { Icon } from './Icon';

export interface FilePickerProps {
  files: File[];
  onChange: (files: File[]) => void;
  accept?: string;
  max?: number;
  /** Button label */
  label?: string;
  hint?: string;
}

const size = (b: number) => b > 1e6 ? (b / 1e6).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1e3)) + ' KB';

/** File input with a removable list of the chosen files (Inertia sends them as multipart). */
export function FilePicker({ files, onChange, accept = 'image/*,video/*', max = 8, label = 'Agregar fotos o videos', hint }: FilePickerProps) {
  const ref = useRef<HTMLInputElement>(null);
  const add = (list: FileList | null) => { if (list) onChange([...files, ...Array.from(list)].slice(0, max)); if (ref.current) ref.current.value = ''; };
  return <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
    <input ref={ref} type="file" accept={accept} multiple hidden onChange={e => add(e.target.files)} />
    <div style={{ display: 'flex', alignItems: 'center', gap: 10, flexWrap: 'wrap' }}>
      <Button type="button" icon="paperclip" disabled={files.length >= max} onClick={() => ref.current?.click()}>{label}</Button>
      <span style={{ fontSize: 12, color: 'var(--hc-muted)' }}>{hint ?? `Hasta ${max} archivos · 20 MB cada uno`}</span>
    </div>
    {files.map((f, i) => <div key={i} style={{ display: 'flex', alignItems: 'center', gap: 8, padding: '5px 10px', border: '1px solid var(--color-divider)', fontSize: 13 }}>
      <Icon name={f.type.startsWith('video/') ? 'video' : 'image'} size={15} />
      <span style={{ flex: 1, minWidth: 0, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{f.name}</span>
      <span style={{ fontFamily: 'var(--hc-mono)', fontSize: 11, color: 'var(--hc-muted)' }}>{size(f.size)}</span>
      <button type="button" aria-label={`Quitar ${f.name}`} onClick={() => onChange(files.filter((_, j) => j !== i))} style={{ all: 'unset', cursor: 'pointer', display: 'flex' }}><Icon name="x" size={15} /></button>
    </div>)}
  </div>;
}
