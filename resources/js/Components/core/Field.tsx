import React from 'react';

export interface FieldProps extends Omit<React.InputHTMLAttributes<HTMLInputElement>, 'onChange' | 'value' | 'children'> {
  rows?: number;
  label?: string;
  /** Render a textarea */
  multiline?: boolean;
  value?: string;
  placeholder?: string;
  onChange?: (e: any) => void;
  /** Custom control (select, Segmented…) instead of the default input */
  children?: React.ReactNode;
}

export function Field({ label, multiline, children, ...input }: FieldProps) {
  return <div className="field">
    {label && <label>{label}</label>}
    {children || (multiline ? <textarea className="input" {...input} /> : <input className="input" {...input} />)}
  </div>;
}
