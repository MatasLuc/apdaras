<?php require_once __DIR__ . '/auth.php'; ensure_session(); ?>
<!DOCTYPE html>
<html lang="lt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Parduotuvė – apdaras.lt</title>
  <link rel="stylesheet" href="./assets/styles.css" />
</head>
<body>
  <?php include __DIR__ . '/partials/nav.php'; ?>

  <section class="hero">
    <div class="container hero__shell">
      <div class="hero__grid">
        <div class="hero__content">
          <div class="badge">Parduotuvė</div>
          <h1>Kolekcijos, paruoštos iškart</h1>
          <p class="lead">Marškinėliai, džemperiai ir aksesuarai – atrinkti kasdieniam patogumui ir stiliui. Užbaikite krepšelį per kelias minutes, o mes pasirūpinsime greitu pristatymu.</p>
          <div class="cta">
            <a class="btn btn--primary" href="#katalogas">Peržiūrėti katalogą</a>
            <a class="btn btn--ghost" href="prisijungimas.php">Prisijungti</a>
          </div>
        </div>
        <div class="hero__visual">
          <div class="hero__panel">
            <p class="card__eyebrow">Akcija</p>
            <p class="card__title">„Urban“ džemperis</p>
            <p class="card__price">€39.00</p>
            <p class="muted">-15% su kodu URBAN15</p>
          </div>
          <div class="hero__panel">
            <p class="card__eyebrow">Pristatymas</p>
            <p class="muted">1–2 d. d. visoje Lietuvoje. Virš €70 – nemokamai.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <main>
    <section id="katalogas" class="section">
      <div class="container">
        <div class="section__inner">
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
        </div>
      </div>
    </section>

    <section class="section">
      <div class="container">
        <div class="section__inner">
          <div class="section__header">
            <p class="badge">Paslaugos</p>
            <h2>Pristatymas ir grąžinimai</h2>
          </div>
          <div class="grid grid--two">
            <article class="tile">
              <p class="tile__label">Pristatymas</p>
              <h3>Greita logistika</h3>
              <p class="muted">Siunčiame per 1–2 d. d. visoje Lietuvoje. Virš €70 – pristatymas nemokamas.</p>
            </article>
            <article class="tile">
              <p class="tile__label">Grąžinimas</p>
              <h3>Lanksti politika</h3>
              <p class="muted">Netiko dydis? Galite grąžinti arba pakeisti per 30 dienų be papildomų klausimų.</p>
            </article>
          </div>
        </div>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
