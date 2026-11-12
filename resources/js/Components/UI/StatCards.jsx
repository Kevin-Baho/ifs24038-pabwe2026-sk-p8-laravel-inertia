export default function StatCards({ items }) {
    return (
        <dl className="grid grid-cols-1 sm:grid-cols-3 gap-3">
            {items.map((item) => (
                <div key={item.label} className="rounded-2xl bg-white border border-slate-200 px-5 py-4 flex items-center gap-4">
                    <span className={`w-1.5 self-stretch rounded-full ${item.accent}`} />
                    <div>
                        <dt className="text-sm text-slate-500">{item.label}</dt>
                        <dd className="text-3xl font-bold text-slate-900 tabular-nums tracking-tight">{item.value}</dd>
                    </div>
                </div>
            ))}
        </dl>
    );
}
