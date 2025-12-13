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
    $birthdate = trim($_POST['birthdate'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $gender = $_POST['gender'] ?? 'unspecified';

    $profileImage = $user['profile_image'] ?? null;

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

    $allowedGenders = ['male', 'female', 'unspecified'];
    if (!in_array($gender, $allowedGenders, true)) {
        $errors[] = 'Pasirinkite galiojančią lytį.';
    }

    if ($birthdate !== '') {
        try {
            $date = new DateTime($birthdate);
            if ($date > new DateTime('today')) {
                $errors[] = 'Gimimo data negali būti ateityje.';
            }
            $birthdate = $date->format('Y-m-d');
        } catch (Throwable $e) {
            $errors[] = 'Netinkamas gimimo datos formatas.';
        }
    } else {
        $birthdate = null;
    }

    if (isset($_FILES['avatar']) && is_array($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Nepavyko įkelti nuotraukos. Bandykite dar kartą.';
        } else {
            $tmpPath = $_FILES['avatar']['tmp_name'];
            $mime = mime_content_type($tmpPath);
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                $errors[] = 'Leidžiami tik JPG, PNG arba WEBP formatai.';
            }

            if (!$errors) {
                $uploadDir = __DIR__ . '/upload';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $filename = 'profile_' . (int) $user['id'] . '_' . time() . '.' . strtolower($ext ?: 'jpg');
                $destination = $uploadDir . '/' . $filename;

                if (move_uploaded_file($tmpPath, $destination)) {
                    $profileImage = 'upload/' . $filename;
                } else {
                    $errors[] = 'Nepavyko išsaugoti nuotraukos.';
                }
            }
        }
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $existing = find_user_by_email($pdo, $email);
            if ($existing && (int) $existing['id'] !== (int) $user['id']) {
                throw new RuntimeException('Toks el. pašto adresas jau naudojamas.');
            }

            update_user_profile($pdo, (int) $user['id'], $name, $email, $birthdate, $address !== '' ? $address : null, $gender, $profileImage);

            if ($password !== '') {
                update_user_password($pdo, (int) $user['id'], $password);
            }

            $pdo->commit();

            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $user = find_user_by_id($pdo, (int) $user['id']);
            $_SESSION['user_role'] = $user['role'] ?? 'customer';

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
    <div class="container hero__shell">
      <div class="hero__grid">
        <div class="hero__content">
          <div class="badge">Paskyra</div>
          <h1>Labas, <?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?>!</h1>
          <p class="lead">Atnaujinkite savo paskyros informaciją ir papildykite profilio duomenis viename lange.</p>
        </div>
      </div>
    </div>
  </section>

  <main class="section">
    <div class="container">
      <div class="auth-shell auth-shell--full">
        <div class="auth-card auth-card--profile">
          <h2>Paskyros nustatymai</h2>
          <p class="muted">Keiskite vardą, el. paštą, slaptažodį ar papildykite profilio informaciją.</p>
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
          <form class="form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data" novalidate>
            <label class="form__field">
              <span>Vardas</span>
              <input type="text" name="name" value="<?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?>" required />
            </label>
            <label class="form__field">
              <span>El. paštas</span>
              <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>" required />
            </label>
            <div class="form__row profile-row">
              <label class="form__field">
                <span>Gimimo data</span>
                <input type="date" name="birthdate" value="<?php echo htmlspecialchars($user['birthdate'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
              </label>
              <label class="form__field">
                <span>Lytis</span>
                <select name="gender">
                  <?php $genderValue = $user['gender'] ?? 'unspecified'; ?>
                  <option value="unspecified" <?php echo $genderValue === 'unspecified' ? 'selected' : ''; ?>>Nenurodyta</option>
                  <option value="female" <?php echo $genderValue === 'female' ? 'selected' : ''; ?>>Moteris</option>
                  <option value="male" <?php echo $genderValue === 'male' ? 'selected' : ''; ?>>Vyras</option>
                </select>
              </label>
            </div>
            <label class="form__field">
              <span>Adresas</span>
              <textarea name="address" rows="3" placeholder="Pilnas adresas, gatvė, miestas, pašto kodas ir pan."><?php echo htmlspecialchars($user['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
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
            <div class="form__row profile-row">
              <div class="form__field">
                <span>Profilio nuotrauka</span>
                <label class="upload-tile">
                  <input type="file" name="avatar" accept="image/png,image/jpeg,image/webp" />
                  <div class="upload-tile__content">
                    <strong>Pasirinkite nuotrauką</strong>
                    <p class="muted">JPG, PNG arba WEBP</p>
                  </div>
                </label>
              </div>
              <?php if (!empty($user['profile_image'])): ?>
                <div class="avatar-preview">
                  <img src="<?php echo htmlspecialchars($user['profile_image'], ENT_QUOTES, 'UTF-8'); ?>" alt="Profilio nuotrauka" />
                  <p class="muted">Dabartinė nuotrauka</p>
                </div>
              <?php endif; ?>
            </div>
            <div class="form__actions">
              <button class="btn btn--primary" type="submit">Išsaugoti</button>
              <a class="btn btn--ghost" href="logout.php">Atsijungti</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
