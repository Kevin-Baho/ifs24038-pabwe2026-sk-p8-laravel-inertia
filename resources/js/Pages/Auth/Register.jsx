import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Button from '../../Components/UI/Button';

const input = 'block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30';

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({
        name: '', username: '', email: '', password: '', password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/register');
    };

    const fields = [
        { key: 'name', label: 'Nama lengkap', type: 'text', auto: 'name' },
        { key: 'username', label: 'Username', type: 'text', auto: 'username', hint: 'Huruf, angka, strip (-), dan garis bawah (_).' },
        { key: 'email', label: 'Email', type: 'email', auto: 'email' },
        { key: 'password', label: 'Password', type: 'password', auto: 'new-password', hint: 'Minimal 8 karakter.' },
        { key: 'password_confirmation', label: 'Ulangi password', type: 'password', auto: 'new-password' },
    ];

    return (
        <AppLayout variant="public">
            <Head title="Daftar" />

            <div className="mx-auto max-w-md px-4 py-14 sm:py-20">
                <h1 className="font-serif text-4xl font-semibold tracking-tight text-ink">Daftar sebagai penulis</h1>
                <p className="mt-2 text-slate-600">Gratis. Mulai menulis dalam hitungan menit.</p>

                <form onSubmit={submit} className="mt-8 space-y-5 rounded-2xl bg-white border border-slate-200 p-6 sm:p-8 shadow-sm">
                    {fields.map((f) => (
                        <div key={f.key}>
                            <label htmlFor={`register-${f.key}`} className="block text-sm font-semibold text-slate-700 mb-1.5">{f.label}</label>
                            <input
                                id={`register-${f.key}`} name={f.key} type={f.type} autoComplete={f.auto} required
                                value={data[f.key]} onChange={(e) => setData(f.key, e.target.value)} className={input}
                            />
                            {errors[f.key] ? <p className="mt-1.5 text-sm text-rose-600">{errors[f.key]}</p> : f.hint && <p className="mt-1.5 text-xs text-slate-500">{f.hint}</p>}
                        </div>
                    ))}

                    <Button id="register-submit" type="submit" loading={processing} className="w-full">
                        {processing ? 'Memproses...' : 'Buat akun'}
                    </Button>
                </form>

                <p className="mt-6 text-sm text-slate-600">
                    Sudah punya akun? <Link href="/login" className="font-semibold text-primary-700 hover:underline">Masuk</Link>
                </p>
            </div>
        </AppLayout>
    );
}
