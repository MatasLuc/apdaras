<!DOCTYPE html>
<html lang="lt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>apdaras.lt – Marškinėliai, džemperiai ir aksesuarai</title>
  <link rel="stylesheet" href="./assets/styles.css" />
</head>
<body>
  <header class="topbar">
    <div class="brand">apdaras.lt</div>
    <nav class="nav">
      <a href="#pagrindinis">Pagrindinis</a>
      <a href="#parduotuve">Parduotuvė</a>
      <a href="#prisijungti">Prisijungimas</a>
      <a href="#registracija">Registracija</a>
      <a href="#privalumai">Kodėl mes?</a>
      <a href="#kontaktai">Kontaktai</a>
    </nav>
    <div class="actions">
      <a class="btn btn--ghost" href="#parduotuve">Krepšelis</a>
      <a class="btn btn--primary" href="#prisijungti">Paskyra</a>
    </div>
  </header>

  <section id="pagrindinis" class="hero">
    <div class="hero__content">
      <p class="badge">Nauja kolekcija</p>
      <h1>apdaras.lt</h1>
      <p class="lead">Stilingi marškinėliai, džemperiai ir aksesuarai kasdienai. Užsisakyk internetu ir gauk pristatymą į namus.</p>
      <div class="cta">
        <a class="btn btn--primary" href="#parduotuve">Peržiūrėti prekes</a>
        <a class="btn btn--ghost" href="#privalumai">Kodėl apdaras.lt?</a>
      </div>
    </div>
    <div class="hero__visual">
      <div class="card">
        <p class="card__eyebrow">Top pasirinkimas</p>
        <p class="card__title">„Urban“ džemperis</p>
        <p class="card__price">€39.00</p>
      </div>
    </div>
  </section>

  <main>
    <section id="privalumai" class="section section--muted">
      <div class="section__header">
        <p class="badge">Vertė</p>
        <h2>Privalumai, kuriuos gaunate</h2>
      </div>
      <div class="grid grid--three">
        <article class="feature">
          <h3>Kokybiški audiniai</h3>
          <p>Naudojame sertifikuotas medžiagas, kad drabužiai būtų patvarūs ir malonūs dėvėti.</p>
        </article>
        <article class="feature">
          <h3>Lietuviškas klientų aptarnavimas</h3>
          <p>Padedame išsirinkti dydžius, priimame grąžinimus ir greitai atsakome į visus klausimus.</p>
        </article>
        <article class="feature">
          <h3>Greitas pristatymas</h3>
          <p>Siunčiame per 1–2 darbo dienas visoje Lietuvoje, o virš €70 pristatymas nemokamas.</p>
        </article>
      </div>
    </section>

    <section id="parduotuve" class="section">
      <div class="section__header">
        <p class="badge">Parduotuvė</p>
        <h2>Populiarios kategorijos</h2>
      </div>
      <div class="grid grid--three">
        <article class="card card--panel">
          <h3>Marškinėliai</h3>
          <p>Minimalistiniai, oversize ir sportiniai modeliai kiekvienam skoniui.</p>
          <a class="text-link" href="#">Peržiūrėti</a>
        </article>
        <article class="card card--panel">
          <h3>Džemperiai</h3>
          <p>Šilti ir patogūs džemperiai su užtrauktuku ir be jo.</p>
          <a class="text-link" href="#">Peržiūrėti</a>
        </article>
        <article class="card card--panel">
          <h3>Aksesuarai</h3>
          <p>Kepuraitės, kuprinės, kojinės ir kiti akcentai jūsų stiliui.</p>
          <a class="text-link" href="#">Peržiūrėti</a>
        </article>
      </div>
    </section>

    <section id="prisijungti" class="section section--muted">
      <div class="grid grid--two align-center">
        <div>
          <p class="badge">Klientai</p>
          <h2>Prisijunkite prie savo paskyros</h2>
          <p>Valdykite užsakymus, sekite pristatymą ir gaukite personalizuotas rekomendacijas prisijungę.</p>
        </div>
        <div class="cta">
          <a class="btn btn--primary" href="#">Prisijungti</a>
          <a class="btn btn--ghost" href="#registracija">Neturite paskyros?</a>
        </div>
      </div>
    </section>

    <section id="registracija" class="section">
      <div class="grid grid--two align-center">
        <div>
          <p class="badge">Nauji klientai</p>
          <h2>Sukurkite paskyrą per kelias sekundes</h2>
          <p>Registruokitės, kad išsaugotumėte krepšelį, adresus ir gautumėte nuolaidas pirmiesiems užsakymams.</p>
        </div>
        <div class="cta">
          <a class="btn btn--primary" href="#">Registruotis</a>
          <a class="btn btn--ghost" href="#parduotuve">Naršyti be registracijos</a>
        </div>
      </div>
    </section>

    <section class="section section--muted">
      <div class="section__header">
        <p class="badge">Vystymas</p>
        <h2>Kas jau veikia ir kas bus netrukus</h2>
      </div>
      <div class="grid grid--two">
        <div>
          <h3>Ši pradžios svetainė</h3>
          <p>Pradinis „index.php“ failas rodomas atidarius apdaras.lt, kad neliktų „Forbidden“ klaidos ir lankytojai matytų veikiančią vitriną.</p>
        </div>
        <div>
          <h3>Ryšys su backend</h3>
          <p>API jau paruošta MySQL duomenų bazei (katalogas „backend/“). Front-end bus sujungtas su parduotuvės ir vartotojų maršrutais, kai tik bus pridėtas pilnas UI.</p>
        </div>
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
            <li><a href="#pagrindinis">Pagrindinis</a></li>
            <li><a href="#parduotuve">Parduotuvė</a></li>
            <li><a href="#privalumai">Kodėl mes?</a></li>
            <li><a href="#registracija">Registracija</a></li>
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
