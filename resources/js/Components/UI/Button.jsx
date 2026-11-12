import Spinner from './Spinner';

const VARIANTS = {
    primary: 'bg-primary-600 text-white hover:bg-primary-700 shadow-sm',
    secondary: 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50',
    danger: 'bg-rose-600 text-white hover:bg-rose-700 shadow-sm',
    ghost: 'text-slate-600 hover:bg-slate-100',
};

/** Tombol dengan spinner otomatis saat `loading`. */
export default function Button({ variant = 'primary', loading = false, disabled, className = '', children, type = 'button', ...props }) {
    return (
        <button
            type={type}
            disabled={disabled || loading}
            className={`inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition disabled:opacity-60 ${VARIANTS[variant]} ${className}`}
            {...props}
        >
            {loading && <Spinner />}
            {children}
        </button>
    );
}
