import React, { createContext, useCallback, useContext, useEffect, useState } from 'react';
import { Toast } from '@/Components';

type Theme = 'dia' | 'noche';
type Ctx = { theme: Theme; toggleTheme: () => void; notify: (msg: string) => void };
const UICtx = createContext<Ctx | null>(null);

export function UIProvider({ children }: { children: React.ReactNode }) {
  const [theme, setTheme] = useState<Theme>(() => (localStorage.getItem('hc_theme') as Theme) || 'dia');
  const [toast, setToast] = useState<string | null>(null);
  useEffect(() => {
    theme === 'noche' ? document.documentElement.setAttribute('data-theme', 'noche') : document.documentElement.removeAttribute('data-theme');
    localStorage.setItem('hc_theme', theme);
  }, [theme]);
  const notify = useCallback((m: string) => { setToast(m); setTimeout(() => setToast(null), 3200); }, []);
  return <UICtx.Provider value={{ theme, toggleTheme: () => setTheme(t => (t === 'dia' ? 'noche' : 'dia')), notify }}>
    {children}
    {toast && <Toast>{toast}</Toast>}
  </UICtx.Provider>;
}

export function useUI() {
  const v = useContext(UICtx);
  if (!v) throw new Error('useUI fuera de UIProvider');
  return v;
}
