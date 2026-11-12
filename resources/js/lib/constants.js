export const CATEGORY_NAMES = ['Teknologi', 'Pendidikan', 'Gaya Hidup', 'Bisnis', 'Opini', 'Umum'];

export const initialOf = (name = '') => (name?.trim()?.charAt(0) || '?').toUpperCase();

export const storageUrl = (path) => {
    if (!path) return null;
    if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('//')) {
        return path;
    }
    return `/storage/${path}`;
};
