const API_BASE = import.meta.env.VITE_PHP_API_BASE || (import.meta.env.DEV ? '' : 'https://bfc-backend.onrender.com');
const SUPABASE_URL = import.meta.env.VITE_SUPABASE_URL || '';
const SUPABASE_ANON_KEY = import.meta.env.VITE_SUPABASE_ANON_KEY || '';

function apiUrl(path) {
  if (!API_BASE) {
    return path;
  }

  const normalizedBase = API_BASE.replace(/\/+$/, '');
  return `${normalizedBase}${path.startsWith('/') ? '' : '/'}${path}`;
}

async function request(path, { method = 'GET', body, credentials = 'include', headers = {} } = {}) {
  const response = await fetch(apiUrl(path), {
    method,
    credentials,
    headers: {
      ...headers
    },
    body
  });

  const contentType = response.headers.get('content-type') || '';
  const isJson = contentType.includes('application/json');
  const data = isJson ? await response.json().catch(() => null) : await response.text();

  if (!response.ok) {
    const message = isJson && data && typeof data === 'object' && data.error ? data.error : 'Request failed';
    throw new Error(message);
  }

  return data;
}

async function fetchSupabaseProducts() {
  if (!SUPABASE_URL || !SUPABASE_ANON_KEY) {
    throw new Error('Supabase is not configured');
  }

  const endpoint = new URL('/rest/v1/products', SUPABASE_URL);
  endpoint.searchParams.set('select', 'id,name,category,price,image,available');
  endpoint.searchParams.set('available', 'eq.true');
  endpoint.searchParams.set('order', 'category.asc,name.asc');

  const response = await fetch(endpoint.toString(), {
    headers: {
      apikey: SUPABASE_ANON_KEY,
      Authorization: `Bearer ${SUPABASE_ANON_KEY}`,
      Accept: 'application/json'
    }
  });

  const data = await response.json().catch(() => null);

  if (!response.ok) {
    const message = Array.isArray(data) ? 'Failed to load Supabase products' : data?.message || data?.error || 'Failed to load Supabase products';
    throw new Error(message);
  }

  return Array.isArray(data) ? data : [];
}

export function getProducts() {
  if (SUPABASE_URL && SUPABASE_ANON_KEY) {
    return fetchSupabaseProducts();
  }

  return request('/store-api.php?action=get_products');
}

export function addOrder(order) {
  return request('/store-api.php?action=add_order', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(order)
  });
}

export function loginStaff({ login_id, password }) {
  const body = new URLSearchParams({ login_id, password });
  return request('/log-in.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'X-Requested-With': 'XMLHttpRequest',
      Accept: 'application/json'
    },
    body
  });
}

export function getAdminStats() {
  return request('/admin-api.php?action=get_stats');
}

export function getAdminProducts() {
  return request('/admin-api.php?action=get_products');
}

export function addAdminProduct(payload) {
  return request('/admin-api.php?action=add_product', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(payload)
  });
}

export function updateAdminProduct(payload) {
  return request('/admin-api.php?action=update_product', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(payload)
  });
}

export function deleteAdminProduct(id) {
  return request('/admin-api.php?action=delete_product', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ id })
  });
}

export function getAdminOrders(limit = 100) {
  return request(`/admin-api.php?action=get_orders&limit=${encodeURIComponent(limit)}`);
}

export function updateOrderStatus(id, status) {
  return request('/admin-api.php?action=update_order_status', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ id, status })
  });
}

export function clearOrders() {
  return request('/admin-api.php?action=clear_orders', {
    method: 'POST'
  });
}

export function getAdminStaff() {
  return request('/admin-api.php?action=get_staff');
}

export function addAdminStaff(payload) {
  return request('/admin-api.php?action=add_staff', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(payload)
  });
}

export function updateAdminStaff(payload) {
  return request('/admin-api.php?action=update_staff', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(payload)
  });
}

export function deleteAdminStaff(id) {
  return request('/admin-api.php?action=delete_staff', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ id })
  });
}