import { useEffect, useMemo, useState } from 'react';
import {
  addAdminProduct,
  addAdminStaff,
  clearOrders,
  deleteAdminProduct,
  deleteAdminStaff,
  getAdminOrders,
  getAdminProducts,
  getAdminStaff,
  getAdminStats,
  updateAdminProduct,
  updateAdminStaff
} from '../lib/api';
import { resolveAssetPath } from '../lib/assets';

const money = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });

const emptyProduct = { id: '', name: '', category: '', price: '', image: '', available: true };
const emptyStaff = { id: '', name: '', role: '', username: '', password: '', active: true };

function assetSrc(path) {
  return resolveAssetPath(path, '/images/bfc.jpg');
}

export default function AdminPage({ navigate }) {
  const [tab, setTab] = useState('dashboard');
  const [stats, setStats] = useState(null);
  const [products, setProducts] = useState([]);
  const [orders, setOrders] = useState([]);
  const [staff, setStaff] = useState([]);
  const [productForm, setProductForm] = useState(emptyProduct);
  const [staffForm, setStaffForm] = useState(emptyStaff);
  const [productModalOpen, setProductModalOpen] = useState(false);
  const [staffModalOpen, setStaffModalOpen] = useState(false);
  const [busy, setBusy] = useState('');
  const [notice, setNotice] = useState('');

  async function loadAll() {
    setBusy('Loading dashboard...');
    try {
      const [statsData, productsData, ordersData, staffData] = await Promise.all([
        getAdminStats(),
        getAdminProducts(),
        getAdminOrders(50),
        getAdminStaff()
      ]);

      setStats(statsData);
      setProducts(Array.isArray(productsData) ? productsData : []);
      setOrders(Array.isArray(ordersData) ? ordersData : []);
      setStaff(Array.isArray(staffData) ? staffData : []);
    } catch (err) {
      setNotice(err.message || 'Failed to load admin data');
    } finally {
      setBusy('');
    }
  }

  useEffect(() => {
    loadAll();
  }, []);

  const dashboardCards = useMemo(() => {
    if (!stats) {
      return [];
    }

    return [
      { label: 'Products', value: stats.products_count },
      { label: 'Available', value: stats.available_count },
      { label: 'Orders', value: stats.orders_count },
      { label: 'Revenue', value: money.format(stats.revenue || 0) },
      { label: 'Active staff', value: stats.staff_active },
      { label: 'Today revenue', value: money.format(stats.today_revenue || 0) }
    ];
  }, [stats]);

  function beginEditProduct(product) {
    setProductForm({
      id: product.id,
      name: product.name || '',
      category: product.category || '',
      price: product.price ?? '',
      image: product.image || '',
      available: Boolean(product.available)
    });
    setTab('products');
    setProductModalOpen(true);
  }

  function beginEditStaff(member) {
    setStaffForm({
      id: member.id,
      name: member.name || '',
      role: member.role || '',
      username: member.username || '',
      password: '',
      active: Boolean(member.active)
    });
    setTab('staff');
    setStaffModalOpen(true);
  }

  function openProductModal() {
    setProductForm(emptyProduct);
    setProductModalOpen(true);
  }

  function openStaffModal() {
    setStaffForm(emptyStaff);
    setStaffModalOpen(true);
  }

  function closeProductModal() {
    setProductModalOpen(false);
  }

  function closeStaffModal() {
    setStaffModalOpen(false);
  }

  async function saveProduct(event) {
    event.preventDefault();
    setNotice('');

    try {
      const payload = {
        id: productForm.id ? Number(productForm.id) : undefined,
        name: productForm.name,
        category: productForm.category,
        price: Number(productForm.price),
        image: productForm.image,
        available: productForm.available
      };

      if (productForm.id) {
        await updateAdminProduct(payload);
      } else {
        await addAdminProduct(payload);
      }

      setProductForm(emptyProduct);
      setProductModalOpen(false);
      await loadAll();
      setNotice('Product saved.');
    } catch (err) {
      setNotice(err.message || 'Failed to save product');
    }
  }

  async function saveStaff(event) {
    event.preventDefault();
    setNotice('');

    try {
      const payload = {
        id: staffForm.id ? Number(staffForm.id) : undefined,
        name: staffForm.name,
        role: staffForm.role,
        username: staffForm.username,
        password: staffForm.password,
        active: staffForm.active
      };

      if (staffForm.id) {
        await updateAdminStaff(payload);
      } else {
        await addAdminStaff(payload);
      }

      setStaffForm(emptyStaff);
      setStaffModalOpen(false);
      await loadAll();
      setNotice('Staff record saved.');
    } catch (err) {
      setNotice(err.message || 'Failed to save staff member');
    }
  }

  async function removeProduct(id) {
    if (!window.confirm('Delete this product?')) {
      return;
    }
    await deleteAdminProduct(id);
    await loadAll();
  }

  async function removeStaff(id) {
    if (!window.confirm('Delete this staff member?')) {
      return;
    }
    await deleteAdminStaff(id);
    await loadAll();
  }

  async function removeAllOrders() {
    if (!window.confirm('Clear all orders?')) {
      return;
    }
    await clearOrders();
    await loadAll();
  }

  return (
    <main className="admin-page">
      <canvas id="canvas1" aria-hidden="true" />
      <div className="grain" aria-hidden="true" />

      <div className="admin-wrap">
        <aside className="sidebar">
          <div className="sidebar-brand">
            <img className="brand-logo" src="/images/bfc.jpg" alt="But First Coffee logo" width="28" height="28" />
            <div className="brand-text">
              <span className="brand-name">BFC Admin</span>
            </div>
          </div>
          <nav className="sidebar-nav">
            <button className={tab === 'dashboard' ? 'nav-item active' : 'nav-item'} type="button" onClick={() => setTab('dashboard')}><span className="nav-icon">📊</span> Dashboard</button>
            <button className={tab === 'products' ? 'nav-item active' : 'nav-item'} type="button" onClick={() => setTab('products')}><span className="nav-icon">🟤</span> Products</button>
            <button className={tab === 'orders' ? 'nav-item active' : 'nav-item'} type="button" onClick={() => setTab('orders')}><span className="nav-icon">📋</span> Orders</button>
            <button className={tab === 'staff' ? 'nav-item active' : 'nav-item'} type="button" onClick={() => setTab('staff')}><span className="nav-icon">👥</span> Staff</button>
          </nav>
          <div className="sidebar-footer">
            <span id="loggedInAs" className="logged-as">{busy || 'Admin Session'}</span>
            <button className="btn-logout" type="button" onClick={() => navigate('/')}>Logout</button>
          </div>
        </aside>

        <main className="main-content">
          <section id="tab-dashboard" className={tab === 'dashboard' ? 'tab-section active' : 'tab-section'}>
            <h1>Dashboard</h1>
            <div className="stats-grid" id="statsGrid">
              {dashboardCards.map((card) => (
                <article className="stat-card" key={card.label}>
                  <div className="stat-val">{card.value}</div>
                  <div className="stat-label">{card.label}</div>
                </article>
              ))}
            </div>
            <h2>Recent Orders</h2>
            <div className="table-wrap">
              <table id="recentOrdersTable">
                <thead>
                  <tr><th>#</th><th>Date</th><th>Items</th><th>Total</th></tr>
                </thead>
                <tbody id="recentOrdersBody">
                  {orders.slice(0, 10).map((order, index) => (
                    <tr key={order.id}>
                      <td>{index + 1}</td>
                      <td>{order.order_date}</td>
                      <td>{Array.isArray(order.items) ? order.items.map((item) => `${item.quantity}x ${item.name}`).join(', ') : '—'}</td>
                      <td>{money.format(Number(order.total || 0))}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </section>

          <section id="tab-products" className={tab === 'products' ? 'tab-section active' : 'tab-section'}>
            <div className="section-header">
              <h1>Products</h1>
              <button className="btn-primary" type="button" onClick={openProductModal}>+ Add Product</button>
            </div>
            <div className="table-wrap">
              <table id="productsTable">
                <thead>
                  <tr>
                    <th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Status</th><th>Actions</th>
                  </tr>
                </thead>
                <tbody id="productsBody">
                  {products.map((product) => (
                    <tr key={product.id}>
                      <td><img className="thumb" src={assetSrc(product.image)} alt={product.name} onError={(event) => { event.currentTarget.src = '/images/bfc.jpg'; }} /></td>
                      <td>{product.name}</td>
                      <td>{product.category}</td>
                      <td>{money.format(Number(product.price || 0))}</td>
                      <td><span className={product.available ? 'badge badge-green' : 'badge badge-red'}>{product.available ? 'Available' : 'Hidden'}</span></td>
                      <td className="action-cell">
                        <button className="btn-sm btn-edit" type="button" onClick={() => beginEditProduct(product)}>Edit</button>
                        <button className="btn-sm btn-del" type="button" onClick={() => removeProduct(product.id)}>Delete</button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </section>

          <section id="tab-orders" className={tab === 'orders' ? 'tab-section active' : 'tab-section'}>
            <div className="section-header">
              <h1>Order History</h1>
              <button className="btn-danger" type="button" onClick={removeAllOrders}>🗑 Clear History</button>
            </div>
            <div className="table-wrap">
              <table id="ordersTable">
                <thead>
                  <tr><th>#</th><th>Date &amp; Time</th><th>Items</th><th>Total</th><th>Paid</th><th>Change</th></tr>
                </thead>
                <tbody id="ordersBody">
                  {orders.map((order, index) => (
                    <tr key={order.id}>
                      <td>{index + 1}</td>
                      <td>{order.order_date}</td>
                      <td>{Array.isArray(order.items) ? order.items.map((item) => `${item.quantity}x ${item.name}`).join(', ') : '—'}</td>
                      <td>{money.format(Number(order.total || 0))}</td>
                      <td>{money.format(Number(order.paid || 0))}</td>
                      <td>{money.format(Number(order.change_amount || 0))}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </section>

          <section id="tab-staff" className={tab === 'staff' ? 'tab-section active' : 'tab-section'}>
            <div className="section-header">
              <h1>Staff Management</h1>
              <button className="btn-primary" type="button" onClick={openStaffModal}>+ Add Staff</button>
            </div>
            <div className="table-wrap">
              <table id="staffTable">
                <thead>
                  <tr><th>Name</th><th>Role</th><th>Username</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody id="staffBody">
                  {staff.map((member) => (
                    <tr key={member.id}>
                      <td>{member.name}</td>
                      <td>{member.role}</td>
                      <td>{member.username}</td>
                      <td>{member.active ? 'Active' : 'Inactive'}</td>
                      <td className="action-cell">
                        <button className="btn-sm btn-edit" type="button" onClick={() => beginEditStaff(member)}>Edit</button>
                        <button className="btn-sm btn-del" type="button" onClick={() => removeStaff(member.id)}>Delete</button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </section>
        </main>
      </div>

      <div className={productModalOpen ? 'modal-overlay open' : 'modal-overlay'} id="productModal">
        <div className="modal">
          <div className="modal-header">
            <h3>{productForm.id ? 'Edit Product' : 'Add Product'}</h3>
            <button className="modal-close" type="button" onClick={closeProductModal}>×</button>
          </div>
          <form onSubmit={saveProduct}>
            <div className="modal-body">
              <input type="hidden" id="productId" value={productForm.id} readOnly />
              <div className="form-row">
                <div className="form-group">
                  <label>Product Name *</label>
                  <input type="text" id="productName" value={productForm.name} onChange={(event) => setProductForm({ ...productForm, name: event.target.value })} placeholder="e.g. Caramel Latte" />
                </div>
                <div className="form-group">
                  <label>Category *</label>
                  <input type="text" id="productCategory" value={productForm.category} onChange={(event) => setProductForm({ ...productForm, category: event.target.value })} placeholder="e.g. Coffees" />
                </div>
              </div>
              <div className="form-row">
                <div className="form-group">
                  <label>Price (₱) *</label>
                  <input type="number" id="productPrice" value={productForm.price} onChange={(event) => setProductForm({ ...productForm, price: event.target.value })} placeholder="0.00" min="0" step="0.01" />
                </div>
                <div className="form-group">
                  <label>Image URL</label>
                  <input type="text" id="productImage" value={productForm.image} onChange={(event) => setProductForm({ ...productForm, image: event.target.value })} placeholder="images/filename.jpg" />
                </div>
              </div>
              <div className="form-group">
                <label><input type="checkbox" id="productAvailable" checked={productForm.available} onChange={(event) => setProductForm({ ...productForm, available: event.target.checked })} /> Available for order</label>
              </div>
            </div>
            <div className="modal-footer">
              <button className="btn-secondary" type="button" onClick={closeProductModal}>Cancel</button>
              <button className="btn-primary" type="submit">Save Product</button>
            </div>
          </form>
        </div>
      </div>

      <div className={staffModalOpen ? 'modal-overlay open' : 'modal-overlay'} id="staffModal">
        <div className="modal">
          <div className="modal-header">
            <h3>{staffForm.id ? 'Edit Staff' : 'Add Staff'}</h3>
            <button className="modal-close" type="button" onClick={closeStaffModal}>×</button>
          </div>
          <form onSubmit={saveStaff}>
            <div className="modal-body">
              <input type="hidden" id="staffId" value={staffForm.id} readOnly />
              <div className="form-row">
                <div className="form-group">
                  <label>Full Name *</label>
                  <input type="text" id="staffName" value={staffForm.name} onChange={(event) => setStaffForm({ ...staffForm, name: event.target.value })} placeholder="e.g. Maria Santos" />
                </div>
                <div className="form-group">
                  <label>Role *</label>
                  <input type="text" id="staffRole" value={staffForm.role} onChange={(event) => setStaffForm({ ...staffForm, role: event.target.value })} placeholder="Manager" />
                </div>
              </div>
              <div className="form-row">
                <div className="form-group">
                  <label>Username *</label>
                  <input type="text" id="staffUsername" value={staffForm.username} onChange={(event) => setStaffForm({ ...staffForm, username: event.target.value })} placeholder="e.g. maria_s" />
                </div>
                <div className="form-group">
                  <label id="staffPassLabel">Password *</label>
                  <input type="password" id="staffPassword" value={staffForm.password} onChange={(event) => setStaffForm({ ...staffForm, password: event.target.value })} placeholder="Min 6 characters" />
                </div>
              </div>
              <div className="form-group">
                <label><input type="checkbox" id="staffActive" checked={staffForm.active} onChange={(event) => setStaffForm({ ...staffForm, active: event.target.checked })} /> Active (can log in)</label>
              </div>
            </div>
            <div className="modal-footer">
              <button className="btn-secondary" type="button" onClick={closeStaffModal}>Cancel</button>
              <button className="btn-primary" type="submit">Save Staff</button>
            </div>
          </form>
        </div>
      </div>
    </main>
  );
}
