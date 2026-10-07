import React from 'react';

export interface IconProps {
  /** Lucide icon name, e.g. "siren", "wallet" */
  name: string;
  size?: number;
  style?: React.CSSProperties;
}

const CDN='https://unpkg.com/lucide-static@0.460.0/icons/';
export function Icon({ name, size = 17, style }: IconProps) {
  const m = `url(${CDN}${name}.svg) center/contain no-repeat`;
  return <span aria-hidden="true" style={{ display: 'inline-block', flex: 'none', width: size, height: size, background: 'currentColor', WebkitMask: m, mask: m, ...style }} />;
}
