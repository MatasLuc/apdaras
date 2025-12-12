<?php
require_once __DIR__ . '/../auth.php';
ensure_session();

$isLoggedIn = isset($_SESSION['user_id']);
$accountUrl = $isLoggedIn ? 'paskyra.php' : 'prisijungimas.php';
$accountLabel = $isLoggedIn ? 'Mano paskyra' : 'Paskyra';
?>
<header class="topbar">
  <div class="brand">apdaras.lt</div>
  <nav class="nav">
    <a href="index.php#pagrindinis">Pagrindinis</a>
    <a href="parduotuve.php">Parduotuvė</a>
    <a href="prisijungimas.php">Prisijungimas</a>
    <a href="registracija.php">Registracija</a>
    <a href="index.php#privalumai">Kodėl mes?</a>
    <a href="index.php#kontaktai">Kontaktai</a>
  </nav>
  <div class="actions">
    <a class="btn btn--ghost" href="parduotuve.php">Krepšelis</a>
    <a class="btn btn--primary" href="<?php echo $accountUrl; ?>"><?php echo htmlspecialchars($accountLabel, ENT_QUOTES, 'UTF-8'); ?></a>
    <?php if ($isLoggedIn): ?>
      <a class="btn btn--ghost" href="logout.php">Atsijungti</a>
    <?php endif; ?>
  </div>
</header>
