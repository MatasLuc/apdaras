<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$pdo = get_db_connection();
$user = require_login($pdo);

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if ($name === '') {
        $errors[] = 'Vardas yra privalomas.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Įveskite galiojantį el. pašto adresą.';
    }

    if ($password !== '' && strlen($password) < 8) {
        $errors[] = 'Naujas slaptažodis turi būti bent 8 simbolių.';
    }

    if ($password !== '' && $password !== $passwordConfirm) {
        $errors[] = 'Slaptažodžiai turi sutapti.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $existing = find_user_by_email($pdo, $email);
            if ($existing && (int) $existing['id'] !== (int) $user['id']) {
                throw new RuntimeException('Toks el. pašto adresas jau naudojamas.');
            }

            update_user_profile($pdo, (int) $user['id'], $name, $email);

            if ($password !== '') {
                update_user_password($pdo, (int) $user['id'], $password);
            }

            $pdo->commit();

            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $user = find_user_by_id($pdo, (int) $user['id']);

            $success = 'Paskyros duomenys atnaujinti.';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Paskyros atnaujinimo klaida: ' . $e->getMessage());
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
  <title>Mano paskyra – apdaras.lt</title>
  <link rel="stylesheet" href="./assets/styles.css" />
</head>
<body>
  <?php include __DIR__ . '/partials/nav.php'; ?>

  <section class="hero">
    <div class="hero__content">
      <p class="badge">Paskyra</p>
      <h1>Sveiki, <?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?>!</h1>
      <p class="lead">Atnaujinkite savo duomenis ir valdykite paskyrą.</p>
    </div>
  </section>

  <main class="section">
    <div class="auth">
      <div>
        <h2>Paskyros nustatymai</h2>
        <p class="muted">Keiskite vardą, el. paštą ar slaptažodį.</p>
      </div>
      <?php if ($errors): ?>
        <div class="alert alert--error">
          <strong>Nepavyko atnaujinti:</strong>
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
          <span>Vardas</span>
          <input type="text" name="name" value="<?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?>" required />
        </label>
        <label class="form__field">
          <span>El. paštas</span>
          <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>" required />
        </label>
        <div class="form__row">
          <label class="form__field">
            <span>Naujas slaptažodis (neprivaloma)</span>
            <input type="password" name="password" placeholder="********" />
          </label>
          <label class="form__field">
            <span>Pakartokite slaptažodį</span>
            <input type="password" name="password_confirm" placeholder="********" />
          </label>
        </div>
        <div class="form__actions">
          <button class="btn btn--primary" type="submit">Išsaugoti</button>
          <a class="btn btn--ghost" href="logout.php">Atsijungti</a>
        </div>
      </form>
    </div>
  </main>

  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
