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
          <div class="badge">Nauja kolekcija 2024</div>
          <h1>Urban stilius kiekvienai dienai</h1>
          <p class="lead">apdaras.lt siūlo minimalistinius marškinėlius, patogius džemperius ir modernius aksesuarus. Viskas pritaikyta kasdieniam ritmui – nuo darbų iki savaitgalio kelionių.</p>
          <div class="meta-row">
            <span>✔️ Greitas pristatymas LT</span>
            <span>✔️ Nemokamas grąžinimas 30 d.</span>
            <span>✔️ Pagalba lietuvių k.</span>
          </div>
          <div class="cta">
            <a class="btn btn--primary" href="parduotuve.php">Peržiūrėti prekes</a>
            <a class="btn btn--ghost" href="#privalumai">Pažinti prekės ženklą</a>
          </div>
        </div>
        <div class="hero__visual">
          <div class="hero__panel">
            <p class="card__eyebrow">Top pasirinkimas</p>
            <p class="card__title">„Urban“ džemperis</p>
            <p class="card__price">€39.00</p>
            <p class="muted">Minkštas kilpinis audinys, trys spalvos.</p>
          </div>
          <div class="hero__panel">
            <p class="card__eyebrow">Krepšelio idėja</p>
            <p class="muted">3 x marškinėliai, 1 x džemperis, 2 x aksesuarai</p>
            <p class="card__price">€124.00</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <main>
    <section id="privalumai" class="section">
      <div class="container">
        <div class="section__inner">
          <div class="section__header">
            <p class="badge">Vertė</p>
            <h2>Ką gaunate rinkdamiesi apdaras.lt</h2>
          </div>
          <div class="grid grid--three">
            <article class="tile">
              <p class="tile__label">01</p>
              <h3>Kokybiški audiniai</h3>
              <p class="muted">Naudojame sertifikuotas medžiagas, kad drabužiai būtų patvarūs, kvėpuojantys ir malonūs dėvėti.</p>
            </article>
            <article class="tile">
              <p class="tile__label">02</p>
              <h3>Greitas aptarnavimas</h3>
              <p class="muted">Atsakome lietuviškai, padedame su dydžiais ir užtikriname sklandų grąžinimą be papildomų klausimų.</p>
            </article>
            <article class="tile">
              <p class="tile__label">03</p>
              <h3>Aiški logistika</h3>
              <p class="muted">Pristatome per 1–2 darbo dienas, o virš €70 užsakymams taikome nemokamą pristatymą.</p>
            </article>
          </div>
        </div>
      </div>
    </section>

    <section class="section">
      <div class="container">
        <div class="section__inner">
          <div class="section__header">
            <p class="badge">Greitos nuorodos</p>
            <h2>Pasirinkite, kur tęsti</h2>
          </div>
          <div class="grid grid--three">
            <article class="card card--panel">
              <h3>Parduotuvė</h3>
              <p>Pasiruošę apsipirkti? Peržiūrėkite naujausius marškinėlių, džemperių ir aksesuarų leidimus.</p>
              <a class="text-link" href="parduotuve.php">Eiti į parduotuvę</a>
            </article>
            <article class="card card--panel">
              <h3>Prisijungimas</h3>
              <p>Valdykite užsakymus, sekite pristatymą ir gaukite personalizuotas rekomendacijas.</p>
              <a class="text-link" href="prisijungimas.php">Prisijungti</a>
            </article>
            <article class="card card--panel">
              <h3>Registracija</h3>
              <p>Susikurkite paskyrą, išsaugokite adresus ir gaukite nuolaidų kodus.</p>
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
            <p class="badge">Vystymas</p>
            <h2>Kas veikia šiandien ir kas laukia</h2>
          </div>
          <div class="grid grid--two align-center">
            <div class="tile">
              <p class="tile__label">Pradinis puslapis</p>
              <h3>Vitri̇na be klaidų</h3>
              <p class="muted">Index.php rodo veikiančią vitriną, kad lankytojai matytų turinį ir rastų svarbiausias nuorodas be „Forbidden“ klaidų.</p>
            </div>
            <div class="tile">
              <p class="tile__label">Backend ryšys</p>
              <h3>Paruošta MySQL</h3>
              <p class="muted">API dalis jau paruošta. Front-end bus prijungtas prie prekių ir vartotojų maršrutų, kai tik bus pridėtas pilnas UI.</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
