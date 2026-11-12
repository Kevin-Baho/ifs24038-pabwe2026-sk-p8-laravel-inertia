import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Button from '../../Components/UI/Button';

const input = 'block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({ username: '', password: '', remember: false });

    const submit = (e) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <AppLayout variant="public">
            <Head title="Masuk" />

            <div className="mx-auto max-w-md px-4 py-14 sm:py-20">
                <h1 className="font-serif text-4xl font-semibold tracking-tight text-ink">Masuk</h1>
                <p className="mt-2 text-slate-600">Lanjutkan menulis di BlogVerse Pro.</p>

                <form onSubmit={submit} className="mt-8 space-y-5 rounded-2xl bg-white border border-slate-200 p-6 sm:p-8 shadow-sm">
                    {errors.username && (
                        <div role="alert" className="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700">{errors.username}</div>
                    )}

                    <div>
                        <label htmlFor="login-username" className="block text-sm font-semibold text-slate-700 mb-1.5">Username atau email</label>
                        <input
                            id="login-username" name="username" type="text" autoComplete="username" required autoFocus
                            value={data.username} onChange={(e) => setData('username', e.target.value)}
                            className={input} placeholder="mis. ifs24038"
                        />
                    </div>

                    <div>
                        <label htmlFor="login-password" className="block text-sm font-semibold text-slate-700 mb-1.5">Password</label>
                        <input
                            id="login-password" name="password" type="password" autoComplete="current-password" required
                            value={data.password} onChange={(e) => setData('password', e.target.value)}
                            className={input}
                        />
                        {errors.password && <p className="mt-1.5 text-sm text-rose-600">{errors.password}</p>}
                    </div>

                    <label className="flex items-center gap-2.5 text-sm text-slate-600">
                        <input type="checkbox" checked={data.remember} onChange={(e) => setData('remember', e.target.checked)} className="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500" />
                        Ingat saya
                    </label>

                    <Button id="login-submit" type="submit" loading={processing} className="w-full">
                        {processing ? 'Memproses...' : 'Masuk'}
                    </Button>
                </form>

                <p className="mt-6 text-sm text-slate-600">
                    Belum punya akun? <Link href="/register" className="font-semibold text-primary-700 hover:underline">Daftar gratis</Link>
                </p>
            </div>
        </AppLayout>
    );
}
