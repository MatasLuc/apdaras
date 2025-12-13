<?php require_once __DIR__ . '/auth.php'; ensure_session(); ?>
<!DOCTYPE html>
<html lang="lt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mokėjimas ir pristatymas – apdaras.lt</title>
  <link rel="stylesheet" href="./assets/styles.css" />
</head>
<body>
  <?php include __DIR__ . '/partials/nav.php'; ?>

  <section class="hero">
    <div class="container hero__shell">
      <div class="hero__grid">
        <div class="hero__content">
          <div class="badge">Mokėjimas ir pristatymas</div>
          <h1>Aiškios sąlygos nuo apmokėjimo iki gavimo</h1>
          <p class="lead">Palaikome saugius atsiskaitymo būdus ir lankstų pristatymą, kad užsakymas jus pasiektų greitai ir be rūpesčių.</p>
        </div>
      </div>
    </div>
  </section>

  <main class="section">
    <div class="container">
      <div class="section__inner grid grid--two align-start">
        <div class="card card--panel">
          <h2>Mokėjimo būdai</h2>
          <ul class="list">
            <li>Banko kortelės (Visa, MasterCard) per saugų mokėjimų apdorojimą.</li>
            <li>Atsiskaitymas per el. bankininkystę (Swedbank, SEB, Luminor ir kt.).</li>
            <li>Sąskaita faktūra įmonėms – parašykite mums po užsakymo.</li>
          </ul>
        </div>
        <div class="card card--panel">
          <h2>Pristatymo pasirinkimai</h2>
          <ul class="list">
            <li>Paštomatai ir kurjeriai visoje Lietuvoje per 1–2 d. d.</li>
            <li>Nemokamas pristatymas užsakymams nuo 70 €.</li>
            <li>Užsienio siuntos – suderinus atskirai el. paštu hello@apdaras.lt.</li>
          </ul>
        </div>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
