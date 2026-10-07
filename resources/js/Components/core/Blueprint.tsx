import React from 'react';

export interface BlueprintProps {
  /** Element to render. Default "div" */
  as?: string;
  children?: React.ReactNode;
  style?: React.CSSProperties;
  className?: string;
  onClick?: () => void;
  onSubmit?: (e: React.FormEvent) => void;
}

export function Blueprint({ as = 'div', children, style, className = '', ...rest }: BlueprintProps) {
  const Tag = as as any;
  return <Tag className={('blueprint ' + className).trim()} style={style} {...rest}>
    <i className="corner tl" /><i className="corner tr" /><i className="corner bl" /><i className="corner br" />
    {children}
  </Tag>;
}
