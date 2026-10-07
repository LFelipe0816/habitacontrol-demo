import React from 'react';

export interface SegmentedProps {
  options: string[];
  value: string;
  onChange?: (v: string) => void;
  /** Stretch options to fill the row */
  fill?: boolean;
}

export function Segmented({ options, value, onChange, fill }: SegmentedProps) {
  return <div className="seg" style={fill ? { display: 'flex' } : undefined}>
    {options.map(o => {
      const on = o === value;
      return <span key={o} className="seg-opt" onClick={() => onChange && onChange(o)}
        style={{ flex: fill ? 1 : undefined, justifyContent: 'center', background: on ? 'var(--color-accent)' : 'transparent', color: on ? 'var(--color-bg)' : 'var(--color-text)' }}>{o}</span>;
    })}
  </div>;
}
