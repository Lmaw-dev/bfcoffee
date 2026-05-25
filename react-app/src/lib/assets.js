const API_BASE = import.meta.env.VITE_PHP_API_BASE || '';

export function resolveAssetPath(path, fallback = '/images/bfc.jpg') {
  if (!path) return (API_BASE ? `${API_BASE}${fallback.startsWith('/') ? '' : '/'}${fallback}` : fallback);

  // absolute URL or data URI
  if (/^(?:https?:)?\/\//i.test(path) || path.startsWith('data:')) {
    return path;
  }

  // already starts with slash — treat as relative to root of whichever host
  if (path.startsWith('/')) {
    return API_BASE ? `${API_BASE}${path}` : path;
  }

  // relative path like 'images/foo.jpg'
  return API_BASE ? `${API_BASE}/${path.replace(/^\/+/, '')}` : `/${path.replace(/^\/+/, '')}`;
}