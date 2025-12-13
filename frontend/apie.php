<?php require_once __DIR__ . '/auth.php'; ensure_session(); ?>
<!DOCTYPE html>
<html lang="lt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Apie mus – apdaras.lt</title>
  <link rel="stylesheet" href="./assets/styles.css" />
</head>
<body>
  <?php include __DIR__ . '/partials/nav.php'; ?>

  <section class="hero">
    <div class="container hero__shell">
      <div class="hero__grid">
        <div class="hero__content">
          <div class="badge">Apie apdaras.lt</div>
          <h1>Drabužiai, kuriuos kuriame kaip produktą</h1>
          <p class="lead">Kuriame minimalistinius miesto drabužius su tokia pat dėmesio koncentracija, kaip ir skaitmeninius produktus: aiškiai, skaidriai ir be nereikalingų žingsnių.</p>
        </div>
      </div>
    </div>
  </section>

  <main class="section">
    <div class="container">
      <div class="section__inner">
        <div class="section__header">
          <p class="badge">Mūsų pažadas</p>
          <h2>Patikimas partneris kiekviename etape</h2>
        </div>
        <div class="grid grid--three">
          <article class="tile">
            <p class="tile__label">1</p>
            <h3>Sąžininga kokybė</h3>
            <p class="muted">Renkamės audinius, kurie tarnautų ilgai, o kiekviena siūlė būtų tokia pat tvarkinga kaip ir mūsų kodas.</p>
          </article>
          <article class="tile">
            <p class="tile__label">2</p>
            <h3>Greitas reagavimas</h3>
            <p class="muted">Klausimus sprendžiame per 1–2 darbo dienas ir informuojame, kaip juda užsakymas ar grąžinimas.</p>
          </article>
          <article class="tile">
            <p class="tile__label">3</p>
            <h3>Atsakingas dizainas</h3>
            <p class="muted">Kolekcijas planuojame mažomis partijomis, kad sumažintume atliekas ir išlaikytume šviežią pasiūlą.</p>
          </article>
        </div>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
