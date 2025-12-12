<?php
require_once __DIR__ . '/../cart.php';
ensure_cart_initialized();

$isLoggedIn = isset($_SESSION['user_id']);
$userName = trim($_SESSION['user_name'] ?? '') ?: 'svečias';
$userRole = $_SESSION['user_role'] ?? 'customer';
$cartCount = cart_count();
$accountUrl = $isLoggedIn ? 'paskyra.php' : 'prisijungimas.php';
$accountLabel = $isLoggedIn ? 'Labas, ' . $userName : 'Paskyra';
?>
<header class="topbar">
  <div class="container topbar__inner">
    <div class="brand">apdaras.lt</div>
    <nav class="nav" aria-label="Pagrindinė navigacija">
      <a href="index.php#pagrindinis">Pagrindinis</a>
      <a href="parduotuve.php">Parduotuvė</a>
    </nav>
    <div class="actions">
      <?php if ($isLoggedIn): ?>
        <div class="account">
          <button class="account__toggle" type="button">
            <span class="account__label">Labas,</span>
            <span class="account__name"><?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php if ($cartCount > 0): ?>
              <span class="account__badge" aria-label="Krepšelyje yra prekių"><?php echo $cartCount > 9 ? '9+' : $cartCount; ?></span>
            <?php endif; ?>
          </button>
          <div class="account__menu">
            <a class="account__menu-row" href="krepselis.php">
              <span>Krepšelis</span>
              <?php if ($cartCount > 0): ?>
                <span class="pill pill--alert"><?php echo $cartCount > 9 ? '9+' : $cartCount; ?></span>
              <?php else: ?>
                <span class="pill">Tuščias</span>
              <?php endif; ?>
            </a>
            <a href="paskyra.php">Paskyros redagavimas</a>
            <?php if ($userRole === 'admin'): ?>
              <a href="administravimas.php">Administravimas</a>
            <?php endif; ?>
            <a href="logout.php">Atsijungti</a>
          </div>
        </div>
      <?php else: ?>
        <a class="btn btn--ghost" href="krepselis.php">Krepšelis</a>
        <a class="btn btn--primary" href="<?php echo $accountUrl; ?>"><?php echo htmlspecialchars($accountLabel, ENT_QUOTES, 'UTF-8'); ?></a>
      <?php endif; ?>
    </div>
  </div>
</header>
