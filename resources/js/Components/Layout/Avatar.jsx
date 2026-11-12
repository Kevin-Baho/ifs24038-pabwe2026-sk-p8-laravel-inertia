import { useEffect, useState } from 'react';
import { initialOf, storageUrl } from '../../lib/constants';

export default function Avatar({ name, path, url, className = 'w-9 h-9' }) {
    const [broken, setBroken] = useState(false);
    const src = url || (path ? storageUrl(path) : null);

    useEffect(() => setBroken(false), [src]);

    return (
        <span className={`rounded-full overflow-hidden bg-primary-600 text-white font-semibold grid place-items-center shrink-0 ${className}`}>
            {src && !broken ? (
                <img src={src} alt={name} className="w-full h-full object-cover" onError={() => setBroken(true)} />
            ) : (
                <span>{initialOf(name)}</span>
            )}
        </span>
    );
}
