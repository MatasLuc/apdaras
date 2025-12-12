<?php
require_once __DIR__ . '/cart.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update';
    $productId = $_POST['product_id'] ?? '';
    $quantity = (int) ($_POST['qty'] ?? 1);

    switch ($action) {
        case 'remove':
            remove_cart_item($productId);
            $_SESSION['cart_alert'] = ['type' => 'success', 'text' => 'Prekė pašalinta iš krepšelio.'];
            break;
        case 'clear':
            clear_cart();
            $_SESSION['cart_alert'] = ['type' => 'success', 'text' => 'Krepšelis išvalytas.'];
            break;
        case 'update':
        default:
            update_cart_item($productId, $quantity);
            $_SESSION['cart_alert'] = ['type' => 'success', 'text' => 'Krepšelio kiekiai atnaujinti.'];
            break;
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
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
          <p class="lead">Peržiūrėkite prekes, atnaujinkite kiekius ir tęskite atsiskaitymą tada, kai būsite pasirengę. Viską išsaugome 7 dienas.</p>
          <div class="cta">
            <a class="btn btn--primary" href="parduotuve.php">Tęsti apsipirkimą</a>
            <?php if ($items): ?>
              <form method="post" class="inline-form">
                <input type="hidden" name="action" value="clear">
                <button class="btn btn--ghost" type="submit">Išvalyti krepšelį</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
        <div class="hero__visual">
          <div class="hero__panel">
            <p class="card__eyebrow">Krepšelio būsena</p>
            <p class="card__title"><?php echo cart_count() ? cart_count() . ' prek.' : 'Tuščias'; ?></p>
            <p class="muted">Prekių kiekiai sinchronizuojami tarp visų puslapių šios sesijos metu.</p>
          </div>
          <div class="hero__panel">
            <p class="card__eyebrow">Bendra suma</p>
            <p class="card__price">€<?php echo number_format(cart_total(), 2, '.', ''); ?></p>
            <p class="muted">Į kainą įtrauktas pristatymas. Apmokėjimas bus įjungtas vėliau.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <main>
    <section class="section">
      <div class="container">
        <div class="section__inner cart-shell">
          <?php if ($alert): ?>
            <div class="alert alert--<?php echo htmlspecialchars($alert['type'], ENT_QUOTES, 'UTF-8'); ?>">
              <?php echo htmlspecialchars($alert['text'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
          <?php endif; ?>

          <?php if ($items): ?>
            <div class="cart-grid">
              <div class="cart-list">
                <?php foreach ($items as $item): ?>
                  <article class="cart-row">
                    <div class="cart-row__main">
                      <div class="pill pill--ghost"><?php echo htmlspecialchars($item['category'] ?? 'Kategorija', ENT_QUOTES, 'UTF-8'); ?></div>
                      <div>
                        <h3><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p class="muted">€<?php echo number_format($item['price'], 2, '.', ''); ?> / vnt.</p>
                      </div>
                    </div>
                    <div class="cart-row__controls">
                      <form method="post" class="quantity quantity--stacked">
                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="number" name="qty" min="0" max="20" value="<?php echo (int) $item['qty']; ?>">
                        <div class="quantity__actions">
                          <button class="btn btn--ghost" type="submit" name="action" value="update">Atnaujinti</button>
                          <button class="btn btn--ghost btn--danger" type="submit" name="action" value="remove">Pašalinti</button>
                        </div>
                      </form>
                      <div class="cart-row__total">€<?php echo number_format($item['price'] * $item['qty'], 2, '.', ''); ?></div>
                    </div>
                  </article>
                <?php endforeach; ?>
              </div>

              <aside class="cart-summary">
                <div class="card card--panel">
                  <div class="summary-row">
                    <span>Tarpinė suma</span>
                    <strong>€<?php echo number_format(cart_subtotal(), 2, '.', ''); ?></strong>
                  </div>
                  <div class="summary-row">
                    <span>Pristatymas</span>
                    <strong>€<?php echo number_format(cart_shipping_fee(), 2, '.', ''); ?></strong>
                  </div>
                  <div class="summary-row summary-row--total">
                    <span>Viso</span>
                    <strong>€<?php echo number_format(cart_total(), 2, '.', ''); ?></strong>
                  </div>
                  <div class="summary-actions">
                    <button class="btn btn--primary btn--block" type="button">Apmokėti</button>
                    <a class="btn btn--ghost btn--block" href="parduotuve.php">Pridėti daugiau prekių</a>
                  </div>
                </div>
              </aside>
            </div>
          <?php else: ?>
            <div class="empty-state">
              <p class="badge">Tuščias krepšelis</p>
              <h3>Pradėkite apsipirkimą</h3>
              <p class="muted">Kol kas nieko nepridėjote. Peržvelkite mūsų katalogą ir įtraukite savo favoritus.</p>
              <a class="btn btn--primary" href="parduotuve.php">Eiti į parduotuvę</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
