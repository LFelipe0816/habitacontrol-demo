/** RD$1.184.300,00 */
export const money = (n: number) => 'RD$' + Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ',00';
export const shortMoney = (n: number) => n >= 1e6 ? 'RD$' + (n / 1e6).toFixed(2).replace('.', ',') + 'M' : money(n);
/** "2026-03-30" se lee como fecha local; sin esto el navegador la toma como UTC y muestra el día anterior. */
const parse = (iso: string) => new Date(/^\d{4}-\d{2}-\d{2}$/.test(iso) ? iso + 'T00:00' : iso);
export const date = (iso: string) => parse(iso).toLocaleDateString('es-DO', { day: 'numeric', month: 'short' });
export const time = (iso: string) => new Date(iso).toLocaleTimeString('es-DO', { hour: 'numeric', minute: '2-digit' });
export const initials = (name?: string | null) => name ? name.split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase() : '—';
export const ago = (iso: string) => {
  const h = Math.round((Date.now() - new Date(iso).getTime()) / 36e5);
  return h < 24 ? h + ' h' : Math.round(h / 24) + ' d';
};
