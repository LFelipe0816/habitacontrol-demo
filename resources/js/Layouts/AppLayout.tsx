import React, { useEffect } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Button, Sidebar, Topbar } from '@/Components';
import { useUI } from '@/context/UIContext';
import { initials } from '@/lib/format';
import type { PageProps, Role } from '@/types';

const NAV: Record<string, { route: string; crumb: string }> = {
  dashboard: { route: '/', crumb: 'Panel' },
  residenciales: { route: '/residenciales', crumb: 'Residenciales' },
  unidad: { route: '/unidades', crumb: 'Apartamento' },
  incidencias: { route: '/incidencias', crumb: 'Incidencias' },
  servicios: { route: '/servicios', crumb: 'Servicios generales' },
  cobros: { route: '/cobros', crumb: 'Cobros' },
  usuarios: { route: '/usuarios', crumb: 'Usuarios' },
  seguridad: { route: '/seguridad', crumb: 'Seguridad' },
  avisos: { route: '/avisos', crumb: 'Avisos' },
};
const ROLE_LABEL: Record<Role, string> = { superadmin: 'Superadministrador', admin: 'Administración', propietario: 'Propietario', residente: 'Residente', seguridad: 'Seguridad', mantenimiento: 'Mantenimiento', junta: 'Junta directiva' };

export default function AppLayout({ children }: { children: React.ReactNode }) {
  const { auth, flash, community } = usePage<PageProps>().props;
  const { url } = usePage();
  const { theme, toggleTheme, notify } = useUI();
  const isResident = auth.user.role === 'propietario' || auth.user.role === 'residente';
  const active = Object.keys(NAV).find(k => k !== 'dashboard' && url.startsWith(NAV[k].route)) || 'dashboard';

  // Mensajes flash de Laravel: return back()->with('success', '…')
  useEffect(() => { if (flash?.success) notify(flash.success); if (flash?.error) notify(flash.error); }, [flash]);

  return <div style={{ display: 'flex', height: '100%' }}>
    <Sidebar
      community={community?.name}
      communityMeta={community?.meta}
      active={active}
      onNavigate={id => router.visit(id === 'unidad' ? `${NAV.unidad.route}/${auth.user.unit_id}` : NAV[id].route)}
      user={{ initials: initials(auth.user.name), name: auth.user.name, role: ROLE_LABEL[auth.user.role] }}
      sections={[
        { title: 'General', items: [
          { id: 'dashboard', label: 'Panel', icon: 'layout-grid' },
          // Personal: portafolio de residenciales. Propietarios/residentes: la ficha de su apartamento.
          ...(isResident
            ? (auth.user.unit_id ? [{ id: 'unidad', label: 'Mi apartamento', icon: 'home' }] : [])
            : [{ id: 'residenciales', label: 'Residenciales', icon: 'building-2' }]),
        ] },
        { title: 'Operación', items: [
          { id: 'incidencias', label: 'Incidencias', icon: 'wrench' },
          ...(['superadmin', 'admin', 'mantenimiento', 'junta'].includes(auth.user.role) ? [{ id: 'servicios', label: 'Servicios', icon: 'cog' }] : []),
          ...(['superadmin', 'admin', 'junta', 'propietario', 'residente'].includes(auth.user.role) ? [{ id: 'cobros', label: 'Cobros', icon: 'receipt' }] : []),
          ...(['superadmin', 'admin', 'seguridad'].includes(auth.user.role) ? [{ id: 'seguridad', label: 'Seguridad', icon: 'shield' }] : []),
        ] },
        { title: 'Comunidad', items: [{ id: 'avisos', label: 'Avisos', icon: 'megaphone' }] },
        ...(['superadmin', 'admin'].includes(auth.user.role) ? [{ title: 'Administración', items: [{ id: 'usuarios', label: 'Usuarios', icon: 'users' }] }] : []),
      ]}
    />
    <div style={{ flex: 1, minWidth: 0, display: 'flex', flexDirection: 'column' }}>
      <Topbar crumbs={[community?.name ?? '', NAV[active].crumb]}>
        <Button variant="ghost" icon={theme === 'dia' ? 'moon' : 'sun'} title="Cambiar tema" onClick={toggleTheme} />
        <Button variant="ghost" icon="log-out" title="Cerrar sesión" onClick={() => router.post('/logout')} />
      </Topbar>
      <main style={{ flex: 1, overflow: 'auto', padding: '28px 28px 48px' }}>{children}</main>
    </div>
  </div>;
}
