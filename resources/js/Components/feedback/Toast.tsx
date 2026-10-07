import React from 'react';

export interface ToastProps {
  children: React.ReactNode;
  /** Pin bottom-right. Default true */
  fixed?: boolean;
}

export function Toast({ children, fixed = true }: ToastProps) {
  return <div className="blueprint" style={{ position: fixed ? 'fixed' : 'relative', bottom: fixed ? 24 : undefined, right: fixed ? 24 : undefined, zIndex: 30, background: 'var(--color-bg)', boxShadow: 'var(--shadow-lg)', padding: '12px 16px', fontSize: 14, display: 'inline-flex', alignItems: 'center', gap: 10 }}>
    <i className="corner tl" /><i className="corner tr" /><i className="corner bl" /><i className="corner br" />
    <span style={{ width: 8, height: 8, background: 'var(--color-accent)' }} />{children}
  </div>;
}
