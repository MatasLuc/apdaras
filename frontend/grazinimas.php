<?php require_once __DIR__ . '/auth.php'; ensure_session(); ?>
<!DOCTYPE html>
<html lang="lt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Grąžinimas – apdaras.lt</title>
  <link rel="stylesheet" href="./assets/styles.css" />
</head>
<body>
  <?php include __DIR__ . '/partials/nav.php'; ?>

  <section class="hero">
    <div class="container hero__shell">
      <div class="hero__grid">
        <div class="hero__content">
          <div class="badge">Grąžinimo taisyklės</div>
          <h1>30 dienų apsisprendimui</h1>
          <p class="lead">Leidžiame pasimatuoti namuose ir grąžinti per 30 dienų nuo gavimo – svarbu, kad prekė būtų švari ir nenešiota.</p>
        </div>
      </div>
    </div>
  </section>

  <main class="section">
    <div class="container">
      <div class="section__inner">
        <div class="section__header">
          <p class="badge">Procesas</p>
          <h2>Kaip atlikti grąžinimą</h2>
        </div>
        <div class="grid grid--two align-start">
          <div class="card card--panel">
            <h3>Žingsniai</h3>
            <ol class="list">
              <li>Parašykite el. paštu hello@apdaras.lt per 30 d. nuo pristatymo.</li>
              <li>Pateikite užsakymo numerį ir grąžinamas prekes.</li>
              <li>Supakuokite prekę švariai ir pateikite siuntos numerį.</li>
            </ol>
          </div>
          <div class="card card--panel">
            <h3>Sąlygos</h3>
            <ul class="list">
              <li>Priimame tik nedėvėtas, švarias ir su etiketėmis prekes.</li>
              <li>Grąžinimo išlaidas apmoka pirkėjas, nebent prekė brokuota.</li>
              <li>Pinigus grąžiname per 5–7 d. nuo prekės gavimo.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
