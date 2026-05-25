export default function LandingPage({ navigate }) {
  return (
    <main className="landing-page">
      <div className="bg-chevron" aria-hidden="true">&gt;</div>
      <div className="grain" aria-hidden="true" />

      <section className="hero">
        <nav className="nav">
          <div className="brand">
            <img src="/images/bfc.jpg" alt="But First Coffee logo" />
            <span>BUT FIRST, COFFEE</span>
          </div>
          <div className="nav-actions">
            <button className="btn dark" type="button" onClick={() => navigate('/login')}>Admin Login</button>
            <button className="btn gold" type="button" onClick={() => navigate('/menu')}>Order Now</button>
          </div>
        </nav>

        <div className="hero-content">
          <div className="hero-copy">
            <div className="eyebrow-row">
              <span className="chip">Fresh brewed daily</span>
              <span className="chip">Bakery pairs & quick checkout</span>
            </div>
            <div>
              <span className="kicker">Coffee made for momentum</span>
              <h1 className="headline"><span>But First,</span> Coffee</h1>
            </div>
            <p className="sub">
              From iced classics to warm bakery favorites, every cup is made to keep your
              momentum going. Step in, slow down, and sip something worth remembering.
            </p>
            <div className="hero-actions">
              <button className="btn gold" type="button" onClick={() => navigate('/menu')}>Order Now</button>
              <button className="btn dark" type="button" onClick={() => navigate('/menu')}>See Signature Picks</button>
            </div>
          </div>

          <div className="visual">
            <div className="cup-stack">
              <img className="cup center" src="/images/bfc1.jpg" alt="Signature coffee cup" onError={(event) => { event.currentTarget.src = '/images/bc.png'; }} />
              <div className="shape" />
              <div className="visual-note">
                <div className="caption">
                  <strong>House blend</strong>
                  Bright roast, creamy finish, and a finish that stays present without feeling heavy.
                </div>
                <div className="caption">
                  <strong>Best paired with</strong>
                  Pandesal, espresso, or a chilled latte for the first hour of the day.
                </div>
              </div>
            </div>
          </div>
        </div>

        <section className="story">
          <article className="panel">
            <img src="/images/bfc.jpg" alt="But First Coffee interior" onError={(event) => { event.currentTarget.src = '/images/bfc.jpg'; }} />
            <div className="panel-copy">
              <h3>Signature Experience</h3>
              <p>Designed for students, workers, and early risers. Quick service, rich coffee aromas, and a warm brand personality in every visit.</p>
            </div>
          </article>
          <article className="panel">
            <img src="/images/pandesal.jpg" alt="Fresh bakery item" />
            <div className="panel-copy">
              <h3>Pair It Perfectly</h3>
              <p>Pair your drink with fresh breads and pastries. Build your own comfort combo in minutes from menu to checkout.</p>
            </div>
          </article>
          <article className="panel">
            <img src="/images/latte.jpg" alt="Creamy latte on a table" />
            <div className="panel-copy">
              <h3>Designed for the Slow Sip</h3>
              <p>Balanced textures, clean plating, and a calmer finish for customers who want the moment to feel a little more deliberate.</p>
            </div>
          </article>
        </section>

        <section className="menu-strip">
          <article className="menu-card">
            <img src="/images/espresso.jpg" alt="Espresso drink" />
            <div>
              <span className="tag">Bold</span>
              <h4>Espresso Shot</h4>
              <p>A focused, bright start for customers who want speed without losing depth.</p>
            </div>
          </article>
          <article className="menu-card">
            <img src="/images/cappuccino.jpg" alt="Cappuccino drink" />
            <div>
              <span className="tag">Classic</span>
              <h4>Cappuccino</h4>
              <p>Foam, balance, and a soft finish that makes the middle of the day feel smoother.</p>
            </div>
          </article>
          <article className="menu-card">
            <img src="/images/pandesal.jpg" alt="Fresh pandesal bread" />
            <div>
              <span className="tag">Warm</span>
              <h4>Fresh Pandesal</h4>
              <p>Simple, comforting, and ready to anchor a drink into a full breakfast ritual.</p>
            </div>
          </article>
        </section>

        <p className="footer-note">Open daily • Crafted by Roble - Dumanayos</p>
      </section>
    </main>
  );
}
