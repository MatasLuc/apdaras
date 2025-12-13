<?php require_once __DIR__ . '/auth.php'; ensure_session(); ?>
<!DOCTYPE html>
<html lang="lt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>apdaras.lt – Marškinėliai, džemperiai ir aksesuarai</title>
  <link rel="stylesheet" href="./assets/styles.css" />
</head>
<body>
  <?php include __DIR__ . '/partials/nav.php'; ?>

  <section id="pagrindinis" class="hero">
    <div class="container hero__shell">
      <div class="hero__grid">
        <div class="hero__content">
          <div class="badge">Naujos kartos kolekcija</div>
          <h1>Premium urban wear, sukurta rytojaus miestui</h1>
          <p class="lead">apdaras.lt jungia minimalistinį siluetą su išmaniomis detalėmis. Greiti pristatymai, skaidri patirtis ir aiški navigacija – lyg šiuolaikinė SaaS platforma, tik drabužiams.</p>
          <div class="meta-row">
            <span>⚡ Greita, sklandi sąsaja</span>
            <span>🛡️ Saugios paskyros</span>
            <span>🚚 1–2 d. pristatymas</span>
          </div>
          <div class="cta">
            <a class="btn btn--primary" href="parduotuve.php">Peržiūrėti prekes</a>
            <a class="btn btn--ghost" href="#patirtys">Patirti dizainą</a>
          </div>
        </div>
        <div class="hero__visual">
          <div class="hero__panel">
            <p class="card__eyebrow">Ateities kapsulė</p>
            <p class="card__title">Džemperis „Signal“</p>
            <p class="card__price">€39.00</p>
            <p class="muted">Tech kilpinis audinys, lazeriu pjautos detalės, trys spalvos.</p>
          </div>
          <div class="hero__panel">
            <p class="card__eyebrow">Patirties indikatoriai</p>
            <div class="meta-row">
              <span>98% patenkintų klientų</span>
              <span>30 d. grąžinimo langas</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <main>
    <section id="patirtys" class="section">
      <div class="container">
        <div class="section__inner">
          <div class="section__header">
            <p class="badge">Patirtys</p>
            <h2>Ką reiškia premium apdaras.lt patirtis</h2>
          </div>
          <div class="grid grid--three">
            <article class="tile">
              <p class="tile__label">01</p>
              <h3>Sensorinis komfortas</h3>
              <p class="muted">Kruopščiai atrinkti audiniai su subtilia faktūra, kad jaustumėtės lengvai dirbdami ar keliaudami.</p>
            </article>
            <article class="tile">
              <p class="tile__label">02</p>
              <h3>Aiškus naršymas</h3>
              <p class="muted">Minimalistinė sąsaja, mažai paspaudimų, stipri tipografija ir premium kortelės su „glass“ akcentais.</p>
            </article>
            <article class="tile">
              <p class="tile__label">03</p>
              <h3>Greiti sprendimai</h3>
              <p class="muted">1–2 d. pristatymas, 30 d. grąžinimas ir skaidri kainodara be paslėptų žingsnių.</p>
            </article>
          </div>
        </div>
      </div>
    </section>

    <section class="section">
      <div class="container">
        <div class="section__inner">
          <div class="section__header">
            <p class="badge">Greiti scenarijai</p>
            <h2>Pasirinkite kelionę</h2>
          </div>
          <div class="grid grid--three">
            <article class="card card--panel">
              <h3>Parduotuvė</h3>
              <p>Kolekcijos su tvarkingais filtravimais, kiekiai realiu laiku ir krepšelio žinutės be trikdžių.</p>
              <a class="text-link" href="parduotuve.php">Eiti į parduotuvę</a>
            </article>
            <article class="card card--panel">
              <h3>Prisijungimas</h3>
              <p>Greitas autentifikavimas su aiškiais pranešimais ir automatišku nukreipimu į paskyrą.</p>
              <a class="text-link" href="prisijungimas.php">Prisijungti</a>
            </article>
            <article class="card card--panel">
              <h3>Registracija</h3>
              <p>Minimalūs laukai, aiški klaidų komunikacija ir patvari sesija – paruošta kasdieniam naudojimui.</p>
              <a class="text-link" href="registracija.php">Sukurti paskyrą</a>
            </article>
          </div>
        </div>
      </div>
    </section>

    <section class="section">
      <div class="container">
        <div class="section__inner">
          <div class="section__header">
            <p class="badge">Architektūra</p>
            <h2>Kas jau veikia ir kuo dar papildysime</h2>
          </div>
          <div class="stack stack--spacious">
            <div class="tile">
              <p class="tile__label">Front-end</p>
              <h3>Glotnus vartotojo kelias</h3>
              <p class="muted">Aiški hierarchija, mažesni tarpai, klijuojama antraštė ir prieinama tipografija, kad kiekvienas veiksmas būtų užtikrintas.</p>
            </div>
            <div class="tile">
              <p class="tile__label">Ryšiai</p>
              <h3>MySQL + saugus prisijungimas</h3>
              <p class="muted">Paskyros, krepšelis ir duomenys valdomi per MySQL su nuosekliomis formomis ir patvaria sesija.</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
