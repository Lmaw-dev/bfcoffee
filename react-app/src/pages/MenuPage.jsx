import { useEffect, useMemo, useState } from 'react';
import { addOrder, getProducts } from '../lib/api';
import { resolveAssetPath } from '../lib/assets';

const money = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });

export default function MenuPage({ navigate }) {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [category, setCategory] = useState('All');
  const [cart, setCart] = useState([]);
  const [cash, setCash] = useState('');
  const [checkoutState, setCheckoutState] = useState('idle');

  useEffect(() => {
    let mounted = true;

    async function load() {
      try {
        const data = await getProducts();
        if (mounted) {
          setProducts(Array.isArray(data) ? data : []);
        }
      } catch (err) {
        if (mounted) setError(err.message || 'Failed to load products');
      } finally {
        if (mounted) setLoading(false);
      }
    }

    load();
    return () => {
      mounted = false;
    };
  }, []);

  const availableProducts = useMemo(() => products.filter((product) => product.available !== false), [products]);
  const categories = useMemo(() => ['All', ...new Set(availableProducts.map((product) => product.category))], [availableProducts]);
  const visibleProducts = useMemo(() => availableProducts.filter((product) => category === 'All' || product.category === category), [availableProducts, category]);

  const groupedProducts = useMemo(() => {
    const groups = new Map();
    for (const product of visibleProducts) {
      const list = groups.get(product.category) || [];
      list.push(product);
      groups.set(product.category, list);
    }
    return Array.from(groups.entries()).map(([groupName, groupItems]) => ({ groupName, groupItems }));
  }, [visibleProducts]);

  const featuredProducts = availableProducts.slice(0, 3);
  const total = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
  const tendered = Number(cash || 0);
  const change = Math.max(tendered - total, 0);

  function productImage(product) {
    return resolveAssetPath(product.image, '/images/bfc.jpg');
  }

  function addToCart(product) {
    setCart((current) => {
      const existing = current.find((item) => item.id === product.id);
      if (existing) {
        return current.map((item) => (item.id === product.id ? { ...item, quantity: item.quantity + 1 } : item));
      }
      return [...current, { id: product.id, name: product.name, price: Number(product.price), quantity: 1 }];
    });
  }

  function updateQuantity(id, delta) {
    setCart((current) => current.map((item) => (item.id === id ? { ...item, quantity: item.quantity + delta } : item)).filter((item) => item.quantity > 0));
  }

  async function checkout() {
    if (!cart.length) {
      setCheckoutState('Add items first.');
      return;
    }

    if (tendered < total) {
      setCheckoutState('Cash received must be at least the order total.');
      return;
    }

    setCheckoutState('Processing order...');

    try {
      await addOrder({ items: cart, total, paid: tendered, change_amount: change });
      setCart([]);
      setCash('');
      setCheckoutState('Order saved successfully.');
    } catch (err) {
      setCheckoutState(err.message || 'Failed to save order');
    }
  }

  return (
    <main className="menu-page">
      <header className="header">
        <div className="header-brand">
          <img src="/images/bfc.jpg" alt="But First Coffee logo" />
          <span>BUT FIRST, COFFEE</span>
        </div>
        <nav className="header-nav">
          <button className="btn-nav" type="button" onClick={() => navigate('/')}>Home</button>
          <button className="btn-nav" type="button" onClick={() => navigate('/login')}>Admin Login</button>
        </nav>
      </header>

      <div className="container menu-layout-shell">
        <section className="menu-title">
          <h1>Our Menu</h1>
          <p>Crafted coffee & quality food</p>
        </section>

        <div className="featured-section">
          <span className="featured-badge">Featured Picks</span>
          <h2 className="featured-title">Pair Your Drink</h2>
          <p className="featured-desc">From espresso to pastries, every item is curated for the original cafe look and the React checkout flow.</p>

          <div className="featured-strip">
            {featuredProducts.map((product) => (
              <article className="featured-card" key={product.id}>
                <img src={productImage(product)} alt={product.name} onError={(event) => { event.currentTarget.src = '/images/bfc.jpg'; }} />
                <div>
                  <h4>{product.name}</h4>
                  <p>{product.category}</p>
                  <div className="price-row">
                    <span className="featured-price">{money.format(Number(product.price || 0))}</span>
                    <button type="button" className="featured-add" onClick={() => addToCart(product)}>Add</button>
                  </div>
                </div>
              </article>
            ))}
          </div>
        </div>

        <div className="menu-content-grid">
          <div className="menu-main">
            <div className="category-tabs">
              {categories.map((item) => (
                <button key={item} type="button" className={item === category ? 'cat-tab active' : 'cat-tab'} onClick={() => setCategory(item)}>{item}</button>
              ))}
            </div>

            {loading ? <div className="notice">Loading products...</div> : null}
            {error ? <div className="notice error">{error}</div> : null}
            {groupedProducts.length === 0 && !loading ? <div className="notice">No products found.</div> : null}

            <div className="menu-grid dynamic-menu-grid">
              {groupedProducts.map(({ groupName, groupItems }) => (
                <section className="menu-section" key={groupName}>
                  <div className="section-header">
                    <span className="section-icon">☕</span>
                    <div>
                      <h2 className="section-title">{groupName}</h2>
                      <p className="section-subtitle">Live products from the PHP API</p>
                    </div>
                  </div>

                  {groupItems.map((product) => (
                    <div className="menu-item" key={product.id}>
                      <img className="menu-item-thumb" src={productImage(product)} alt={product.name} onError={(event) => { event.currentTarget.src = '/images/bfc.jpg'; }} />
                      <div className="menu-item-copy">
                        <h3 className="item-name">
                          {product.name}
                          {product.available === false ? <span className="coming-soon-badge">Unavailable</span> : null}
                        </h3>
                        <p className="item-desc">Freshly prepared and ready for checkout.</p>
                        <div className="item-prices">
                          <div className="price-tag">
                            <span className="price-size">Single</span>
                            <span className="price-amount">{money.format(Number(product.price || 0))}</span>
                          </div>
                          <button type="button" className="mini-add" onClick={() => addToCart(product)}>Add to order</button>
                        </div>
                      </div>
                    </div>
                  ))}
                </section>
              ))}
            </div>
          </div>

          <aside className="order-summary">
            <div className="featured-section order-snapshot-panel">
              <span className="featured-badge">Current Order</span>
              <h2 className="featured-title">Checkout</h2>
              <div className="order-snapshot">
                <div className="snapshot-chip">
                  <span>Items</span>
                  <strong>{cart.reduce((sum, item) => sum + item.quantity, 0)}</strong>
                </div>
                <div className="snapshot-chip">
                  <span>Total</span>
                  <strong>{money.format(total)}</strong>
                </div>
              </div>

              <div className="cart-list">
                {cart.length === 0 ? <p className="muted">Add items from the menu to start an order.</p> : null}
                {cart.map((item) => (
                  <div className="cart-row" key={item.id}>
                    <div>
                      <strong>{item.name}</strong>
                      <span>{money.format(item.price)}</span>
                    </div>
                    <div className="quantity-controls">
                      <button type="button" onClick={() => updateQuantity(item.id, -1)}>-</button>
                      <span>{item.quantity}</span>
                      <button type="button" onClick={() => updateQuantity(item.id, 1)}>+</button>
                    </div>
                  </div>
                ))}
              </div>

              <div className="checkout-note">Products are loaded from the existing PHP API and payment is posted back to the MySQL-backed store endpoint.</div>

              <div className="totals">
                <label>
                  Cash received
                  <input type="number" min="0" step="0.01" value={cash} onChange={(event) => setCash(event.target.value)} placeholder="0.00" />
                </label>
                <div><span>Change</span><strong>{money.format(change)}</strong></div>
              </div>

              {checkoutState && checkoutState !== 'idle' ? <div className={checkoutState.includes('success') ? 'notice success' : 'notice'}>{checkoutState}</div> : null}

              <div className="order-actions">
                <button className="btn gold checkout-btn" type="button" onClick={checkout}>Place order</button>
              </div>
            </div>
          </aside>
        </div>

        <p className="footer-note">Open daily • Orders available at cafe.html</p>
      </div>
    </main>
  );
}
