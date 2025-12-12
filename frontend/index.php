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
    <div class="hero__content">
      <p class="badge">Nauja kolekcija</p>
      <h1>apdaras.lt</h1>
      <p class="lead">Stilingi marškinėliai, džemperiai ir aksesuarai kasdienai. Užsisakyk internetu ir gauk pristatymą į namus.</p>
      <div class="cta">
        <a class="btn btn--primary" href="parduotuve.php">Peržiūrėti prekes</a>
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

    <section class="section">
      <div class="section__header">
        <p class="badge">Pagrindiniai puslapiai</p>
        <h2>Kur norite tęsti?</h2>
      </div>
      <div class="grid grid--three">
        <article class="card card--panel">
          <h3>Parduotuvė</h3>
          <p>Peržiūrėkite populiariausias kategorijas ir įsidėkite prekes į krepšelį.</p>
          <a class="text-link" href="parduotuve.php">Eiti į parduotuvę</a>
        </article>
        <article class="card card--panel">
          <h3>Prisijungimas</h3>
          <p>Valdykite užsakymus ir sekite pristatymą prisijungę prie paskyros.</p>
          <a class="text-link" href="prisijungimas.php">Prisijungti</a>
        </article>
        <article class="card card--panel">
          <h3>Registracija</h3>
          <p>Sukurkite paskyrą, išsaugokite adresus ir gaukite nuolaidų kodus.</p>
          <a class="text-link" href="registracija.php">Registruotis</a>
        </article>
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

  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
