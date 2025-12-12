<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/db.php';

$errors = [];
$success = null;
$emailValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  $emailValue = $email;

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Įveskite galiojantį el. pašto adresą.';
  }

  if ($password === '') {
    $errors[] = 'Slaptažodis yra privalomas.';
  }

    if (!$errors) {
      try {
        $db = get_db_connection();
        $user = find_user_by_email($db, $email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
          $errors[] = 'Neteisingi prisijungimo duomenys.';
        } else {
          $_SESSION['user_id'] = $user['id'];
          $_SESSION['user_name'] = $user['name'];
          $_SESSION['user_email'] = $user['email'];
          $success = 'Prisijungimas sėkmingas. Malonu matyti sugrįžus!';
        }
      } catch (PDOException $e) {
        error_log('Prisijungimo klaida: ' . $e->getMessage());
        $errors[] = $e->getMessage();
      }
    }
  }
  ?>
<!DOCTYPE html>
<html lang="lt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Prisijungimas – apdaras.lt</title>
  <link rel="stylesheet" href="./assets/styles.css" />
</head>
<body>
  <header class="topbar">
    <div class="brand">apdaras.lt</div>
    <nav class="nav">
      <a href="index.php">Pagrindinis</a>
      <a href="parduotuve.php">Parduotuvė</a>
      <a href="prisijungimas.php">Prisijungimas</a>
      <a href="registracija.php">Registracija</a>
      <a href="index.php#privalumai">Kodėl mes?</a>
      <a href="index.php#kontaktai">Kontaktai</a>
    </nav>
    <div class="actions">
      <a class="btn btn--ghost" href="parduotuve.php">Krepšelis</a>
      <a class="btn btn--primary" href="prisijungimas.php">Paskyra</a>
    </div>
  </header>

  <section class="hero">
    <div class="hero__content">
      <p class="badge">Klientai</p>
      <h1>Prisijunkite prie savo paskyros</h1>
      <p class="lead">Valdykite užsakymus, sekite pristatymą ir gaukite personalizuotas rekomendacijas prisijungę.</p>
    </div>
  </section>

  <main class="section">
    <div class="auth">
      <div>
        <h2>Prisijungimo duomenys</h2>
        <p class="muted">Įveskite el. paštą ir slaptažodį, kad prisijungtumėte.</p>
      </div>
      <?php if ($errors): ?>
        <div class="alert alert--error">
          <strong>Patikrinkite įvestus duomenis:</strong>
          <ul>
            <?php foreach ($errors as $error): ?>
              <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php elseif ($success): ?>
        <div class="alert alert--success">
          <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>
      <form class="form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" novalidate>
        <label class="form__field">
          <span>El. paštas</span>
          <input type="email" name="email" placeholder="jusu@pastas.lt" value="<?php echo htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8'); ?>" required />
        </label>
        <label class="form__field">
          <span>Slaptažodis</span>
          <input type="password" name="password" placeholder="********" required />
        </label>
        <div class="form__actions">
          <button class="btn btn--primary" type="submit">Prisijungti</button>
          <a class="btn btn--ghost" href="registracija.php">Neturite paskyros?</a>
        </div>
      </form>
    </div>
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
            <li><a href="index.php#pagrindinis">Pagrindinis</a></li>
            <li><a href="parduotuve.php">Parduotuvė</a></li>
            <li><a href="prisijungimas.php">Prisijungimas</a></li>
            <li><a href="registracija.php">Registracija</a></li>
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
