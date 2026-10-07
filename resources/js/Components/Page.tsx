import React from 'react';

export function PageHeader({ kicker, title, actions }: { kicker?: React.ReactNode; title: string; actions?: React.ReactNode }) {
  return <div style={{ display: 'flex', alignItems: 'flex-end', gap: 16, flexWrap: 'wrap', marginBottom: 24 }}>
    <div style={{ display: 'flex', flexDirection: 'column', gap: 2, flex: 1, minWidth: 0 }}>
      {kicker && <span style={{ fontSize: 10.5, letterSpacing: '.1em', textTransform: 'uppercase', color: 'var(--color-accent-700)' }}>{kicker}</span>}
      <h1 style={{ margin: 0, fontSize: 'var(--hc-display)', lineHeight: 1.05, textTransform: 'uppercase', letterSpacing: '.02em' }}>{title}</h1>
    </div>
    {actions && <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap', justifyContent: 'flex-end' }}>{actions}</div>}
  </div>;
}

export function Section({ title, children, aside }: { title: string; children: React.ReactNode; aside?: React.ReactNode }) {
  return <section style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
    <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
      <h2 style={{ margin: 0, fontSize: 18, textTransform: 'uppercase', letterSpacing: '.04em', flex: 1 }}>{title}</h2>{aside}
    </div>
    {children}
  </section>;
}

export function FieldError({ message }: { message?: string }) {
  return message ? <span style={{ fontSize: 12, color: 'var(--color-accent-700)' }}>{message}</span> : null;
}
