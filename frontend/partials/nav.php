<?php
require_once __DIR__ . '/../auth.php';
ensure_session();

$isLoggedIn = isset($_SESSION['user_id']);
$userName = trim($_SESSION['user_name'] ?? '') ?: 'svečias';
$userRole = $_SESSION['user_role'] ?? 'customer';
$accountUrl = $isLoggedIn ? 'paskyra.php' : 'prisijungimas.php';
$accountLabel = $isLoggedIn ? 'Labas, ' . $userName : 'Paskyra';
?>
<header class="topbar">
  <div class="brand">apdaras.lt</div>
  <nav class="nav">
    <a href="index.php#pagrindinis">Pagrindinis</a>
    <a href="parduotuve.php">Parduotuvė</a>
  </nav>
  <div class="actions">
    <a class="btn btn--ghost" href="parduotuve.php">Krepšelis</a>
    <?php if ($isLoggedIn): ?>
      <div class="account">
        <button class="account__toggle" type="button">
          <span class="account__label">Labas,</span>
          <span class="account__name"><?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></span>
        </button>
        <div class="account__menu">
          <a href="paskyra.php">Paskyros redagavimas</a>
          <?php if ($userRole === 'admin'): ?>
            <a href="administravimas.php">Administravimas</a>
          <?php endif; ?>
        </div>
      </div>
      <a class="btn btn--ghost" href="logout.php">Atsijungti</a>
    <?php else: ?>
      <a class="btn btn--primary" href="<?php echo $accountUrl; ?>"><?php echo htmlspecialchars($accountLabel, ENT_QUOTES, 'UTF-8'); ?></a>
    <?php endif; ?>
  </div>
</header>
