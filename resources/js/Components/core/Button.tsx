import React from 'react';
import { Icon } from './Icon';

export interface ButtonProps {
  /** primary = the one solid object on screen (registration marks included) */
  variant?: 'primary' | 'secondary' | 'ghost';
  /** Lucide icon name; with no children renders a 36px icon button */
  icon?: string;
  block?: boolean;
  disabled?: boolean;
  onClick?: () => void;
  title?: string;
  type?: 'button' | 'submit' | 'reset';
  children?: React.ReactNode;
}

export function Button({ variant = 'secondary', icon, block, children, ...rest }: ButtonProps) {
  const cls = ['btn', 'btn-' + variant, !children && icon ? 'btn-icon' : '', block ? 'btn-block' : '', variant === 'primary' ? 'blueprint' : ''].filter(Boolean).join(' ');
  return <button className={cls} {...rest}>
    {variant === 'primary' && <><i className="corner tl" /><i className="corner tr" /><i className="corner bl" /><i className="corner br" /></>}
    {icon && <Icon name={icon} size={16} />}
    {children}
  </button>;
}
