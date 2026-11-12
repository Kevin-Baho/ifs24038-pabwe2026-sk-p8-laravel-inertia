import { Link } from '@inertiajs/react';

export default function Brand({ href = '/', compact = false }) {
    return (
        <Link href={href} className="inline-flex items-center gap-2.5">
            <span className="w-8 h-8 rounded-lg bg-primary-700 text-white grid place-items-center font-serif font-bold text-lg leading-none">B</span>
            {!compact && (
                <span className="font-serif text-xl font-bold tracking-tight text-ink">
                    BlogVerse <span className="text-primary-700">Pro</span>
                </span>
            )}
        </Link>
    );
}
