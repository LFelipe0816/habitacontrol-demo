import { Head, router } from '@inertiajs/react';
import { Blueprint, Tag } from '@/Components';
import { PageHeader } from '@/Components/Page';
import type { Notice } from '@/types';

export default function Index({ notices }: { notices: Notice[] }) {
  const read = (n: Notice) => !n.read && router.post(`/avisos/${n.id}/leido`, {}, { preserveScroll: true, only: ['notices'] });
  return <div style={{ maxWidth: 760 }}>
    <Head title="Avisos" />
    <PageHeader kicker="Comunidad" title="Avisos" />
    <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
      {notices.map(n => <Blueprint key={n.id} onClick={() => read(n)} style={{ padding: 18, display: 'flex', flexDirection: 'column', gap: 6, cursor: n.read ? 'default' : 'pointer' }}>
        <div style={{ display: 'flex', gap: 10, alignItems: 'center' }}>
          <Tag tone={n.read ? 'quiet' : 'strong'}>{n.category}</Tag>
          <span style={{ fontSize: 12, color: 'var(--hc-muted)', marginLeft: 'auto' }}>{n.date}</span>
        </div>
        <span style={{ fontSize: 16, fontWeight: n.read ? 500 : 700 }}>{n.title}</span>
        <span style={{ fontSize: 14, color: 'var(--hc-muted)' }}>{n.body}</span>
      </Blueprint>)}
    </div>
  </div>;
}
