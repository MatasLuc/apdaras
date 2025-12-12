<!DOCTYPE html>
<html lang="lt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Parduotuvė – apdaras.lt</title>
  <link rel="stylesheet" href="./assets/styles.css" />
</head>
<body>
  <header class="topbar">
    <div class="brand">apdaras.lt</div>
    <nav class="nav">
      <a href="index.php">Pagrindinis</a>
      <a href="parduotuve.php">Parduotuvė</a>
      <a href="prisijungimas.php">Prisijungimas</a>
      <a href="registracija.php">Registracija</a>
      <a href="index.php#privalumai">Kodėl mes?</a>
      <a href="index.php#kontaktai">Kontaktai</a>
    </nav>
    <div class="actions">
      <a class="btn btn--ghost" href="#katalogas">Krepšelis</a>
      <a class="btn btn--primary" href="prisijungimas.php">Paskyra</a>
    </div>
  </header>

  <section class="hero">
    <div class="hero__content">
      <p class="badge">Parduotuvė</p>
      <h1>Naršykite mūsų kolekcijas</h1>
      <p class="lead">Marškinėliai, džemperiai ir aksesuarai – atrinkti kasdieniam patogumui ir stiliui. Pasirinkite kategoriją ir užbaikite pirkimą internetu.</p>
      <div class="cta">
        <a class="btn btn--primary" href="#katalogas">Peržiūrėti katalogą</a>
        <a class="btn btn--ghost" href="prisijungimas.php">Prisijungti prie paskyros</a>
      </div>
    </div>
    <div class="hero__visual">
      <div class="card">
        <p class="card__eyebrow">Akcija</p>
        <p class="card__title">„Urban“ džemperis</p>
        <p class="card__price">€39.00</p>
        <p class="card__price">-15% su kodu URBAN15</p>
      </div>
    </div>
  </section>

  <main>
    <section id="katalogas" class="section">
      <div class="section__header">
        <p class="badge">Populiaru</p>
        <h2>Prekių kategorijos</h2>
      </div>
      <div class="grid grid--three">
        <article class="card card--panel">
          <h3>Marškinėliai</h3>
          <p>Minimalistiniai, oversize ir sportiniai modeliai kiekvienam skoniui.</p>
          <a class="text-link" href="#">Į krepšelį</a>
        </article>
        <article class="card card--panel">
          <h3>Džemperiai</h3>
          <p>Šilti ir patogūs džemperiai su užtrauktuku ir be jo.</p>
          <a class="text-link" href="#">Į krepšelį</a>
        </article>
        <article class="card card--panel">
          <h3>Aksesuarai</h3>
          <p>Kepuraitės, kuprinės, kojinės ir kiti akcentai jūsų stiliui.</p>
          <a class="text-link" href="#">Į krepšelį</a>
        </article>
      </div>
    </section>

    <section class="section section--muted">
      <div class="section__header">
        <p class="badge">Paslaugos</p>
        <h2>Pristatymas ir grąžinimai</h2>
      </div>
      <div class="grid grid--two">
        <article class="feature">
          <h3>Greitas pristatymas</h3>
          <p>Siunčiame per 1–2 d. d. visoje Lietuvoje. Virš €70 – pristatymas nemokamas.</p>
        </article>
        <article class="feature">
          <h3>Lanksti grąžinimo politika</h3>
          <p>Netiko dydis? Galite grąžinti arba pakeisti per 30 dienų.</p>
        </article>
      </div>
    </section>
  </main>

  <footer id="kontaktai" class="footer">
    <div class="footer__inner">
      <div class="footer__grid">
        <div class="footer__brand">
          <div class="footer__logo">apdaras.lt</div>
          <p>Kasdien padedame rasti stilių, kuris tinka ir darbui, ir poilsiui. Viskas sukurta ir supakuota Lietuvoje.</p>
          <div class="footer__chip">Made in LT</div>
        </div>

        <div>
          <p class="footer__title">Navigacija</p>
          <ul class="footer__list">
            <li><a href="index.php#pagrindinis">Pagrindinis</a></li>
            <li><a href="parduotuve.php">Parduotuvė</a></li>
            <li><a href="prisijungimas.php">Prisijungimas</a></li>
            <li><a href="registracija.php">Registracija</a></li>
          </ul>
        </div>

        <div>
          <p class="footer__title">Pagalba</p>
          <ul class="footer__list">
            <li>+370 600 00000</li>
            <li><a href="mailto:laba@apdaras.lt">laba@apdaras.lt</a></li>
            <li>Pristatymas per 1–2 d. d.</li>
            <li>Grąžinimas per 30 dienų</li>
          </ul>
        </div>

        <div>
          <p class="footer__title">Socialiniai kanalai</p>
          <ul class="footer__list">
            <li><a href="#">Instagram</a></li>
            <li><a href="#">Facebook</a></li>
            <li><a href="#">TikTok</a></li>
            <li><a href="#">Naujienlaiškis</a></li>
          </ul>
        </div>
      </div>

      <div class="footer__bar">
        <p>apdaras.lt · Drabužiai ir aksesuarai internetu</p>
        <p>2024 © Visos teisės saugomos</p>
      </div>
    </div>
  </footer>
</body>
</html>
