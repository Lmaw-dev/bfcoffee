import { useState } from 'react';
import { loginStaff } from '../lib/api';

export default function LoginPage({ navigate, onLoginSuccess }) {
  const [loginId, setLoginId] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  async function handleSubmit(event) {
    event.preventDefault();
    setLoading(true);
    setError('');

    try {
      await loginStaff({ login_id: loginId, password });
      onLoginSuccess?.();
      navigate('/admin');
    } catch (err) {
      setError(err.message || 'Login failed');
    } finally {
      setLoading(false);
    }
  }

  return (
    <main className="login-page">
      <div className="grain" aria-hidden="true" />
      <section className="login-shell">
        <div className="card login-card">
          <div className="brand">
            <img src="/images/bfc.jpg" alt="But First Coffee logo" />
            <span>BUT FIRST, COFFEE</span>
          </div>
          <h1>Admin / Staff Login</h1>
          <p>Use admin email or staff username from the database.</p>

          {error ? <div className="error-banner">{error}</div> : null}

          <form onSubmit={handleSubmit}>
            <label htmlFor="login_id">Admin Email or Staff Username</label>
            <input id="login_id" value={loginId} onChange={(event) => setLoginId(event.target.value)} type="text" placeholder="admin@gmail.com or staff username" required />

            <label htmlFor="password">Password</label>
            <input id="password" value={password} onChange={(event) => setPassword(event.target.value)} type="password" placeholder="Enter password" required />

            <button className="btn-login" type="submit" disabled={loading}>
              {loading ? 'Logging in...' : 'Log In'}
            </button>
          </form>

          <div className="row">
            <button type="button" className="link" onClick={() => navigate('/')}>Back to Landing</button>
            <button type="button" className="link" onClick={() => navigate('/menu')}>Go to Menu</button>
          </div>
        </div>
      </section>
    </main>
  );
}
