import { FormEvent } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Blueprint, Button, Field } from '@/Components';
import { FieldError } from '@/Components/Page';

function Login() {
  const { data, setData, post, processing, errors } = useForm({ email: '', password: '', remember: false });
  const submit = (e: FormEvent) => { e.preventDefault(); post('/login'); };

  return <div style={{ minHeight: '100%', display: 'grid', placeItems: 'center', padding: 24 }}>
    <Head title="Entrar" />
    <Blueprint as="form" onSubmit={submit} style={{ width: 'min(380px,100%)', padding: 28, display: 'flex', flexDirection: 'column', gap: 16 }}>
      <span style={{ fontFamily: 'var(--font-heading)', fontWeight: 600, fontSize: 24, letterSpacing: '.04em', textTransform: 'uppercase' }}>Habita<span style={{ color: 'var(--color-accent)' }}>Control</span></span>
      <Field label="Correo" type="email" value={data.email} onChange={e => setData('email', e.target.value)} required />
      <FieldError message={errors.email} />
      <Field label="Contraseña" type="password" value={data.password} onChange={e => setData('password', e.target.value)} required />
      <Button variant="primary" type="submit" block disabled={processing}>{processing ? 'Entrando…' : 'Entrar'}</Button>
    </Blueprint>
  </div>;
}
Login.layout = null;
export default Login;
