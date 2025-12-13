<?php
require_once __DIR__ . '/cart.php';

// Apdorojame veiksmus
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update';
    
    if ($action === 'checkout') {
        // --- UŽSAKYMO KŪRIMAS ---
        // Jei vartotojas svečias, čia galėtume paimti duomenis iš formos
        $guestInfo = [
            'name' => 'Svečias', // Vėliau čia bus tikra forma
            'email' => 'guest@example.com',
            'address' => 'Nenurodyta'
        ];
        
        $orderId = create_order_from_cart($guestInfo);
        
        if ($orderId) {
            // Sėkmė! Nukreipiam į padėkos puslapį ar parodom pranešimą
            // Čia supaprastintai tiesiog išvalom alertą ir parodom sėkmę
            $_SESSION['cart_alert'] = ['type' => 'success', 'text' => "Užsakymas #{$orderId} priimtas sėkmingai!"];
        } else {
            $_SESSION['cart_alert'] = ['type' => 'error', 'text' => 'Nepavyko sukurti užsakymo. Bandykite vėliau.'];
        }
    } elseif ($action === 'remove') {
        $itemId = (int)$_POST['item_id'];
        remove_cart_item($itemId);
        $_SESSION['cart_alert'] = ['type' => 'success', 'text' => 'Prekė pašalinta.'];
    } elseif ($action === 'clear') {
        clear_cart();
        $_SESSION['cart_alert'] = ['type' => 'success', 'text' => 'Krepšelis išvalytas.'];
    } else {
        // Update
        $itemId = (int)$_POST['item_id'];
        $qty = (int)$_POST['qty'];
        update_cart_item($itemId, $qty);
        $_SESSION['cart_alert'] = ['type' => 'success', 'text' => 'Kiekiai atnaujinti.'];
    }

    header('Location: krepselis.php');
    exit;
}

$items = cart_items();
$alert = $_SESSION['cart_alert'] ?? null;
unset($_SESSION['cart_alert']);
?>
<!DOCTYPE html>
<html lang="lt">
<head>
  <meta charset="UTF-8" />
  <title>Krepšelis – apdaras.lt</title>
  <link rel="stylesheet" href="./assets/styles.css" />
</head>
<body>
  <?php include __DIR__ . '/partials/nav.php'; ?>

  <section class="hero">
    <div class="container hero__shell">
      <div class="hero__grid">
        <div class="hero__content">
          <div class="badge">Krepšelis</div>
          <h1>Jūsų pasirinkimai</h1>
          <p class="lead">Peržiūrėkite prekes ir tęskite atsiskaitymą.</p>
          <div class="cta">
            <a class="btn btn--ghost" href="parduotuve.php">Grįžti į parduotuvę</a>
            <?php if ($items): ?>
              <form method="post" class="inline-form">
                <input type="hidden" name="action" value="clear">
                <button class="btn btn--danger" type="submit">Išvalyti viską</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
        <div class="hero__visual">
          <div class="hero__panel">
            <p class="card__eyebrow">Bendra suma</p>
            <p class="card__price">€<?php echo number_format(cart_total(), 2); ?></p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <main class="section">
    <div class="container">
      <?php if ($alert): ?>
        <div class="alert alert--<?php echo $alert['type']; ?>" style="margin-bottom: 20px;">
          <?php echo htmlspecialchars($alert['text']); ?>
        </div>
      <?php endif; ?>

      <?php if ($items): ?>
        <div class="cart-grid">
          <div class="cart-list">
            <?php foreach ($items as $item): ?>
              <article class="cart-row">
                <div class="cart-row__main">
                  <?php if($item['image_url']): ?>
                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                  <?php endif; ?>
                  <div>
                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                    <?php if($item['variation_text']): ?>
                        <p class="muted" style="font-size: 13px;"><?php echo htmlspecialchars($item['variation_text']); ?></p>
                    <?php endif; ?>
                    <p class="muted">€<?php echo number_format($item['price'], 2); ?> / vnt.</p>
                  </div>
                </div>
                <div class="cart-row__controls">
                  <form method="post" class="quantity quantity--stacked">
                    <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                    <input type="number" name="qty" min="1" max="20" value="<?php echo $item['qty']; ?>">
                    <div class="quantity__actions">
                      <button class="btn btn--ghost" type="submit" name="action" value="update">Atnaujinti</button>
                      <button class="btn btn--ghost btn--danger" type="submit" name="action" value="remove">Pašalinti</button>
                    </div>
                  </form>
                  <div class="cart-row__total">€<?php echo number_format($item['price'] * $item['qty'], 2); ?></div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>

          <aside class="cart-summary">
            <div class="card card--panel">
              <div class="summary-row"><span>Tarpinė suma</span><strong>€<?php echo number_format(cart_subtotal(), 2); ?></strong></div>
              <div class="summary-row"><span>Pristatymas</span><strong>€<?php echo number_format(cart_shipping_fee(), 2); ?></strong></div>
              <div class="summary-row summary-row--total"><span>Viso</span><strong>€<?php echo number_format(cart_total(), 2); ?></strong></div>
              
              <div class="summary-actions">
                <form method="post">
                    <input type="hidden" name="action" value="checkout">
                    <button class="btn btn--primary btn--block" type="submit">Apmokėti (Formuoti užsakymą)</button>
                </form>
              </div>
            </div>
          </aside>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <h3>Krepšelis tuščias</h3>
          <a class="btn btn--primary" href="parduotuve.php">Pradėti pirkimą</a>
        </div>
      <?php endif; ?>
    </div>
  </main>
  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
