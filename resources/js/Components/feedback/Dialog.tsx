import React from 'react';

export interface DialogProps {
  open?: boolean;
  title?: string;
  children?: React.ReactNode;
  /** Buttons, right-aligned; primary last */
  actions?: React.ReactNode;
  onClose?: () => void;
}

export function Dialog({ open = true, title, children, actions, onClose }: DialogProps) {
  if (!open) return null;
  return <div className="dialog-backdrop" onClick={onClose} style={{ zIndex: 20 }}>
    <div className="dialog blueprint" onClick={e => e.stopPropagation()} style={{ background: 'var(--color-bg)' }}>
      <i className="corner tl" /><i className="corner tr" /><i className="corner bl" /><i className="corner br" />
      {title && <span className="dialog-title">{title}</span>}
      {children}
      {actions && <div className="dialog-actions">{actions}</div>}
    </div>
  </div>;
}
