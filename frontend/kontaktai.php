<?php require_once __DIR__ . '/auth.php'; ensure_session(); ?>
<!DOCTYPE html>
<html lang="lt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Kontaktai – apdaras.lt</title>
  <link rel="stylesheet" href="./assets/styles.css" />
</head>
<body>
  <?php include __DIR__ . '/partials/nav.php'; ?>

  <section class="hero">
    <div class="container hero__shell">
      <div class="hero__grid">
        <div class="hero__content">
          <div class="badge">Kontaktai</div>
          <h1>Parašykite mums</h1>
          <p class="lead">Esame pasiekiami el. paštu ir telefonu darbo dienomis 9–18 val. – atsakysime per vieną darbo dieną.</p>
        </div>
      </div>
    </div>
  </section>

  <main class="section">
    <div class="container">
      <div class="section__inner grid grid--two align-start">
        <div class="card card--panel">
          <h2>Kontaktinė informacija</h2>
          <p class="muted">Pasirinkite patogiausią būdą susisiekti su mumis dėl užsakymų, grąžinimų ar bendradarbiavimo.</p>
          <ul class="list">
            <li><strong>El. paštas:</strong> hello@apdaras.lt</li>
            <li><strong>Telefonas:</strong> +370 600 00000</li>
            <li><strong>Adresas:</strong> Modernioji g. 10, Vilnius</li>
          </ul>
        </div>
        <div class="card card--panel">
          <h2>Greitas užklausos šablonas</h2>
          <p class="muted">Įrašykite temą ir trumpai aprašykite situaciją – galėsite šį tekstą nukopijuoti į el. laišką.</p>
          <form class="form" aria-label="Greita užklausa">
            <label class="form__field">
              <span>Tema</span>
              <input type="text" name="subject" placeholder="Pvz., Užsakymo #1234 pristatymas" />
            </label>
            <label class="form__field">
              <span>Žinutė</span>
              <textarea name="message" rows="4" placeholder="Trumpai aprašykite klausimą ar problemą"></textarea>
            </label>
            <div class="form__actions">
              <button class="btn btn--primary" type="button">Kopijuoti tekstą</button>
              <a class="btn btn--ghost" href="mailto:hello@apdaras.lt">Rašyti el. laišką</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
