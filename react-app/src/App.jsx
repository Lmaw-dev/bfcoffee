import { useEffect, useState } from 'react';
import AdminPage from './pages/AdminPage';
import LandingPage from './pages/LandingPage';
import LoginPage from './pages/LoginPage';
import MenuPage from './pages/MenuPage';

function getPath() {
  const path = window.location.pathname.replace(/\/+$/, '') || '/';
  if (path === '/index.html') {
    return '/';
  }

  return path;
}

export default function App() {
  const [path, setPath] = useState(getPath());

  useEffect(() => {
    const onPopState = () => setPath(getPath());
    window.addEventListener('popstate', onPopState);
    return () => window.removeEventListener('popstate', onPopState);
  }, []);

  function navigate(nextPath) {
    if (nextPath === path) {
      return;
    }

    window.history.pushState({}, '', nextPath);
    setPath(nextPath);
  }

  if (path === '/menu') {
    return <MenuPage navigate={navigate} />;
  }

  if (path === '/login' || path === '/log-in') {
    return <LoginPage navigate={navigate} />;
  }

  if (path === '/admin' || path === '/cafe-admin') {
    return <AdminPage navigate={navigate} />;
  }

  return <LandingPage navigate={navigate} />;
}