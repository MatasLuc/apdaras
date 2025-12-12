<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$pdo = get_db_connection();
$user = require_login($pdo);

if (($user['role'] ?? 'customer') !== 'admin') {
    header('Location: paskyra.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="lt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Administravimas – apdaras.lt</title>
  <link rel="stylesheet" href="./assets/styles.css" />
</head>
<body>
  <?php include __DIR__ . '/partials/nav.php'; ?>

  <section class="hero">
    <div class="container hero__shell">
      <div class="hero__grid">
        <div class="hero__content">
          <div class="badge">Administravimas</div>
          <h1>Valdymo skydelis</h1>
          <p class="lead">Čia galėsite tvarkyti katalogą ir naudotojus. Šiuo metu tai informacinis puslapis.</p>
          <div class="meta-row">
            <span>🛠️ Paruošta plėtrai</span>
            <span>🔐 Tik administratoriams</span>
          </div>
        </div>
        <div class="hero__visual">
          <div class="hero__panel">
            <p class="card__eyebrow">Prisijungęs</p>
            <p class="muted"><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <main class="section">
    <div class="container">
      <div class="section__inner">
        <h3>Administratoriaus priėjimas patvirtintas</h3>
        <p class="muted">Galite tęsti kuriant tikrą valdymo UI arba prisijungti prie backend.</p>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
