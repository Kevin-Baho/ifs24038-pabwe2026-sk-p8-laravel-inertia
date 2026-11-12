import { Head, Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import AdminPostTable from '../../Components/Post/AdminPostTable';
import PostFilters from '../../Components/Post/PostFilters';
import Avatar from '../../Components/Layout/Avatar';
import StatCards from '../../Components/UI/StatCards';
import Pagination from '../../Components/UI/Pagination';
import { ArrowLeftIcon, DownloadIcon } from '../../Components/UI/Icons';

export default function AdminUserPosts({ targetUser, posts, filters, stats, categories }) {
    return (
        <AppLayout>
            <Head title={`Artikel ${targetUser.name}`} />

            <div className="mx-auto max-w-6xl space-y-6">
                <Link href="/admin/users" className="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-primary-700">
                    <ArrowLeftIcon className="w-4 h-4" /> Kembali ke pengguna
                </Link>

                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div className="flex items-center gap-4">
                        <Avatar name={targetUser.name} className="w-14 h-14 text-xl" />
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-slate-900">{targetUser.name}</h1>
                            <p className="text-sm text-slate-500">@{targetUser.username} - {targetUser.email} - {targetUser.role}</p>
                        </div>
                    </div>
                    <a href={`/admin/users/${targetUser.id}/posts/export`} className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary-600 text-white hover:bg-primary-700 shadow-sm transition">
                        <DownloadIcon className="w-4 h-4" /> Ekspor Artikel Pengguna
                    </a>
                </div>

                <StatCards
                    items={[
                        { label: 'Total Artikel', value: stats.total, accent: 'bg-primary-600' },
                        { label: 'Terbit', value: stats.published, accent: 'bg-emerald-500' },
                        { label: 'Draf', value: stats.draft, accent: 'bg-amber-500' },
                    ]}
                />

                <section className="rounded-2xl bg-white border border-slate-200">
                    <div className="p-4 border-b border-slate-100">
                        <PostFilters url={`/admin/users/${targetUser.id}/posts`} filters={filters} categories={categories} />
                    </div>
                    <AdminPostTable posts={posts.data} showAuthor={false} />
                    <div className="px-5 py-4 border-t border-slate-100"><Pagination links={posts.links} /></div>
                </section>
            </div>
        </AppLayout>
    );
}
