<!DOCTYPE html>
<html lang="lt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Registracija – apdaras.lt</title>
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
      <a class="btn btn--ghost" href="parduotuve.php">Krepšelis</a>
      <a class="btn btn--primary" href="prisijungimas.php">Paskyra</a>
    </div>
  </header>

  <section class="hero">
    <div class="hero__content">
      <p class="badge">Nauji klientai</p>
      <h1>Sukurkite paskyrą per kelias sekundes</h1>
      <p class="lead">Registruokitės, kad išsaugotumėte krepšelį, adresus ir gautumėte nuolaidas pirmiesiems užsakymams.</p>
    </div>
  </section>

  <main class="section">
    <div class="auth">
      <div>
        <h2>Registracijos forma</h2>
        <p class="muted">Užpildykite savo duomenis ir spustelėkite „Registruotis“.</p>
      </div>
      <form class="form" action="#" method="post">
        <label class="form__field">
          <span>Vardas</span>
          <input type="text" name="name" placeholder="Jūsų vardas" required />
        </label>
        <label class="form__field">
          <span>El. paštas</span>
          <input type="email" name="email" placeholder="jusu@pastas.lt" required />
        </label>
        <label class="form__field">
          <span>Slaptažodis</span>
          <input type="password" name="password" placeholder="Sukurkite slaptažodį" required />
        </label>
        <label class="form__field">
          <span>Patvirtinkite slaptažodį</span>
          <input type="password" name="password_confirm" placeholder="Pakartokite slaptažodį" required />
        </label>
        <div class="form__actions">
          <button class="btn btn--primary" type="submit">Registruotis</button>
          <a class="btn btn--ghost" href="prisijungimas.php">Jau turite paskyrą?</a>
        </div>
      </form>
    </div>
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
