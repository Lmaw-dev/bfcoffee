const API_BASE = import.meta.env.VITE_PHP_API_BASE || (import.meta.env.DEV ? '' : 'https://bfc-backend.onrender.com');

function assetUrl(path) {
  const normalizedBase = API_BASE.replace(/\/+$/, '');
  const normalizedPath = path.startsWith('/') ? path : `/${path}`;
  return `${normalizedBase}${normalizedPath}`;
}

export function resolveAssetPath(path, fallback = '/images/bfc.jpg') {
  if (!path) return assetUrl(fallback);

  // absolute URL or data URI
  if (/^(?:https?:)?\/\//i.test(path) || path.startsWith('data:')) {
    return path;
  }

  // already starts with slash — treat as relative to root of whichever host
  if (path.startsWith('/')) {
    return assetUrl(path);
  }

  // relative path like 'images/foo.jpg'
  return assetUrl(path.replace(/^\/+/, ''));
}