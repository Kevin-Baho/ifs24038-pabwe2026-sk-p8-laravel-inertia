import { Link, usePage } from '@inertiajs/react';

/** Kartu ajakan komunitas: mengarah ke Login/Register untuk guest. */
export default function CommunityCta() {
    const { auth } = usePage().props;
    const user = auth?.user;

    return (
        <section aria-labelledby="cta-title" className="rounded-3xl bg-primary-800 text-white px-6 py-10 sm:px-12 sm:py-14 grid gap-8 md:grid-cols-[1.4fr_1fr] md:items-center">
            <div>
                <h2 id="cta-title" className="font-serif text-3xl sm:text-4xl font-semibold tracking-tight leading-tight">
                    Punya gagasan yang ingin dibagikan?
                </h2>
                <p className="mt-3 max-w-xl text-primary-100">
                    Bergabunglah dengan komunitas penulis BlogVerse. Tulis dengan Markdown, minta bantuan AI untuk draf awal, dan terbitkan saat siap.
                </p>
            </div>
            <div className="flex flex-col sm:flex-row md:flex-col gap-3">
                {user ? (
                    <Link href={user.role === 'admin' ? '/admin/users' : '/dashboard/posts'} className="text-center px-6 py-3 rounded-xl bg-white text-primary-800 font-semibold hover:bg-primary-50 transition">
                        Buka dashboard
                    </Link>
                ) : (
                    <>
                        <Link href="/register" className="text-center px-6 py-3 rounded-xl bg-white text-primary-800 font-semibold hover:bg-primary-50 transition">
                            Daftar sebagai penulis
                        </Link>
                        <Link href="/login" className="text-center px-6 py-3 rounded-xl border border-white/40 text-white font-semibold hover:bg-white/10 transition">
                            Saya sudah punya akun
                        </Link>
                    </>
                )}
            </div>
        </section>
    );
}
