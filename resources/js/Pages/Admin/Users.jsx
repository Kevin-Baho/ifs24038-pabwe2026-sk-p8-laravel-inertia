import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Avatar from '../../Components/Layout/Avatar';
import StatCards from '../../Components/UI/StatCards';
import Pagination from '../../Components/UI/Pagination';
import ConfirmDialog from '../../Components/UI/ConfirmDialog';
import { DownloadIcon, EyeIcon, SearchIcon, TrashIcon } from '../../Components/UI/Icons';
import useFilters from '../../lib/useFilters';

export default function AdminUsers({ users, filters, stats }) {
    const { auth } = usePage().props;
    const [values, set] = useFilters('/admin/users', { search: filters.search ?? '' });
    const [target, setTarget] = useState(null);
    const [deleting, setDeleting] = useState(false);

    const changeRole = (user, role) => router.patch(`/admin/users/${user.id}/role`, { role }, { preserveScroll: true });

    const remove = () => {
        setDeleting(true);
        router.delete(`/admin/users/${target.id}`, {
            preserveScroll: true,
            onFinish: () => { setDeleting(false); setTarget(null); },
        });
    };

    return (
        <AppLayout>
            <Head title="Kelola Pengguna" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900">Kelola Pengguna</h1>
                        <p className="text-sm text-slate-500">Atur peran pengguna dan pantau jumlah artikel mereka.</p>
                    </div>
                    <a href="/admin/posts/export" className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 shadow-sm transition">
                        <DownloadIcon className="w-4 h-4" /> Ekspor Semua Artikel (Excel)
                    </a>
                </div>

                <StatCards
                    items={[
                        { label: 'Total Pengguna', value: stats.users.total, accent: 'bg-violet-500' },
                        { label: 'Total Artikel', value: stats.posts.total, accent: 'bg-primary-600' },
                        { label: 'Artikel Terbit', value: stats.posts.published, accent: 'bg-emerald-500' },
                    ]}
                />

                <section className="rounded-2xl bg-white border border-slate-200">
                    <div className="p-4 border-b border-slate-100">
                        <div className="relative">
                            <SearchIcon className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <input
                                type="search" value={values.search} onChange={(e) => set('search', e.target.value)}
                                placeholder="Cari nama, username, atau email..." aria-label="Cari pengguna"
                                className="w-full rounded-xl border border-slate-300 pl-10 pr-3.5 py-2.5 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30"
                            />
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-slate-600">
                                <tr>
                                    <th className="px-5 py-3 font-semibold">Pengguna</th>
                                    <th className="px-5 py-3 font-semibold">Peran</th>
                                    <th className="px-5 py-3 font-semibold text-right">Artikel</th>
                                    <th className="px-5 py-3 font-semibold text-right">Terbit</th>
                                    <th className="px-5 py-3 font-semibold text-right">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {users.data.map((u) => {
                                    const isSelf = u.id === auth.user.id;
                                    return (
                                        <tr key={u.id} className="hover:bg-slate-50/60">
                                            <td className="px-5 py-3.5">
                                                <div className="flex items-center gap-3">
                                                    <Avatar name={u.name} url={u.avatar_url} path={u.profile_photo_path} className="w-9 h-9 text-sm" />
                                                    <div className="min-w-0">
                                                        <p className="font-semibold text-slate-900 truncate">{u.name}{isSelf && <span className="ml-2 text-xs font-medium text-slate-500">(Anda)</span>}</p>
                                                        <p className="text-xs text-slate-500 truncate">@{u.username} - {u.email}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-5 py-3.5">
                                                <select
                                                    value={u.role} disabled={isSelf}
                                                    onChange={(e) => changeRole(u, e.target.value)}
                                                    aria-label={`Peran ${u.name}`}
                                                    className="rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-sm disabled:bg-slate-100 disabled:text-slate-500"
                                                >
                                                    <option value="member">Member</option>
                                                    <option value="admin">Admin</option>
                                                </select>
                                            </td>
                                            <td className="px-5 py-3.5 text-right tabular-nums">{u.posts_count}</td>
                                            <td className="px-5 py-3.5 text-right tabular-nums">{u.published_posts_count}</td>
                                            <td className="px-5 py-3.5">
                                                <div className="flex items-center justify-end gap-1">
                                                    <Link href={`/admin/users/${u.id}/posts`} aria-label={`Lihat artikel ${u.name}`} title="Lihat artikel" className="p-2 rounded-lg text-primary-700 hover:bg-primary-50">
                                                        <EyeIcon className="w-5 h-5" />
                                                    </Link>
                                                    <a href={`/admin/users/${u.id}/posts/export`} aria-label={`Ekspor artikel ${u.name}`} title="Ekspor Excel" className="p-2 rounded-lg text-primary-700 hover:bg-primary-50">
                                                        <DownloadIcon className="w-5 h-5" />
                                                    </a>
                                                    <button type="button" disabled={isSelf} onClick={() => setTarget(u)} aria-label={`Hapus ${u.name}`} title="Hapus pengguna" className="p-2 rounded-lg text-rose-600 hover:bg-rose-50 disabled:opacity-30 disabled:hover:bg-transparent">
                                                        <TrashIcon className="w-5 h-5" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                        {users.data.length === 0 && <p className="px-6 py-14 text-center text-slate-500">Pengguna tidak ditemukan.</p>}
                    </div>

                    <div className="px-5 py-4 border-t border-slate-100"><Pagination links={users.links} /></div>
                </section>
            </div>

            <ConfirmDialog
                open={Boolean(target)}
                title="Hapus pengguna?"
                message={target ? `Akun ${target.name} (@${target.username}) dan seluruh ${target.posts_count} artikelnya akan dihapus permanen.` : ''}
                loading={deleting}
                onConfirm={remove}
                onCancel={() => setTarget(null)}
            />
        </AppLayout>
    );
}
