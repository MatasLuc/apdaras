<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
ensure_session();

if (isset($_SESSION['user_id'])) {
  header('Location: paskyra.php');
  exit;
}

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
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        header('Location: paskyra.php');
        exit;
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
  <?php include __DIR__ . '/partials/nav.php'; ?>

  <section class="hero">
    <div class="container hero__shell">
      <div class="hero__grid">
        <div class="hero__content">
          <div class="badge">Klientai</div>
          <h1>Prisijunkite prie paskyros</h1>
          <p class="lead">Valdykite užsakymus, sekite pristatymą ir gaukite personalizuotas rekomendacijas prisijungę.</p>
          <div class="meta-row">
            <span>🔒 Saugus prisijungimas</span>
            <span>🧾 Užsakymų istorija</span>
          </div>
        </div>
        <div class="hero__visual">
          <div class="hero__panel">
            <p class="card__eyebrow">Patarimas</p>
            <p class="muted">Prisijunkę matysite išsaugotus adresus ir greitesnį apmokėjimą.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <main class="section">
    <div class="container">
      <div class="auth-shell">
        <div class="auth-card">
          <h2>Prisijungimo duomenys</h2>
          <p class="muted">Įveskite el. paštą ir slaptažodį, kad prisijungtumėte.</p>
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
        <div class="auth-card">
          <h3>Naujas klientas?</h3>
          <p class="muted">Sukurkite paskyrą ir gaukite nuolaidų kodus bei greitesnį apmokėjimą.</p>
          <a class="btn btn--primary" href="registracija.php">Registruotis</a>
        </div>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
