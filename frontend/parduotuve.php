<?php
require_once __DIR__ . '/cart.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['product_id'] ?? '';
    $quantity = (int) ($_POST['qty'] ?? 1);

    $added = add_cart_item($productId, $quantity);

    $_SESSION['cart_alert'] = $added
        ? ['type' => 'success', 'text' => $added['name'] . ' pridėta į krepšelį.']
        : ['type' => 'error', 'text' => 'Nepavyko pridėti prekės.'];

    header('Location: parduotuve.php');
    exit;
}

$catalog = cart_catalog();
$alert = $_SESSION['cart_alert'] ?? null;
unset($_SESSION['cart_alert']);
?>
<!DOCTYPE html>
<html lang="lt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Parduotuvė – apdaras.lt</title>
  <link rel="stylesheet" href="./assets/styles.css" />
</head>
<body>
  <?php include __DIR__ . '/partials/nav.php'; ?>

  <section class="hero">
    <div class="container hero__shell">
      <div class="hero__grid">
        <div class="hero__content">
          <div class="badge">Parduotuvė</div>
          <h1>Kolekcijos, sukurtos modernaus miesto ritmui</h1>
          <p class="lead">Kiekviena prekių kortelė – kaip SaaS modulis: aiški, greita ir pasiruošusi veikti. Lengvai įsidėkite kiekius, patikrinkite sumas ir tęskite.</p>
          <div class="cta">
            <a class="btn btn--primary" href="#katalogas">Peržiūrėti katalogą</a>
            <a class="btn btn--ghost" href="krepselis.php">Eiti į krepšelį</a>
          </div>
        </div>
        <div class="hero__visual">
          <div class="hero__panel">
            <p class="card__eyebrow">Akcija</p>
            <p class="card__title">„Signal“ džemperis</p>
            <p class="card__price">€39.00</p>
            <p class="muted">-15% su kodu SIGNAL15 · Atnaujinta kapsulė</p>
          </div>
          <div class="hero__panel">
            <p class="card__eyebrow">Pristatymas</p>
            <p class="muted">1–2 d. d. visoje Lietuvoje. Virš €70 – nemokamai.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <main>
    <section id="katalogas" class="section">
      <div class="container">
        <div class="section__inner">
          <div class="section__header">
            <p class="badge">Populiaru</p>
            <h2>Prekių kategorijos</h2>
          </div>
          <?php if ($alert): ?>
            <div class="alert alert--<?php echo htmlspecialchars($alert['type'], ENT_QUOTES, 'UTF-8'); ?>">
              <?php echo htmlspecialchars($alert['text'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
          <?php endif; ?>

          <?php if (empty($catalog)): ?>
            <div class="empty-state">
              <p class="badge">Katalogas tuščias</p>
              <h3>Dar neįkelta prekių</h3>
              <p class="muted">Pabandykite pridėti produktų administravimo skydelyje arba atnaujinkite puslapį.</p>
            </div>
          <?php else: ?>
            <div class="grid grid--three">
              <?php foreach ($catalog as $product): ?>
                <article class="card card--product">
                  <div class="card__header">
                    <span class="pill"><?php echo htmlspecialchars($product['category'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="pill pill--ghost"><?php echo htmlspecialchars($product['tag'], ENT_QUOTES, 'UTF-8'); ?></span>
                  </div>
                  <h3><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                  <p class="muted">
                    <?php echo htmlspecialchars($product['summary'] ?: 'Atraskite naujausias kolekcijas ir variacijas.', ENT_QUOTES, 'UTF-8'); ?>
                  </p>
                  <div class="card__meta">
                    <div class="price-stack">
                      <span class="card__price">€<?php echo number_format($product['price'], 2, '.', ''); ?></span>
                      <?php if (!empty($product['discount_price'])): ?>
                        <span class="card__old-price">€<?php echo number_format($product['full_price'], 2, '.', ''); ?></span>
                      <?php endif; ?>
                    </div>
                    <form method="post" class="product-form">
                      <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id'], ENT_QUOTES, 'UTF-8'); ?>">
                      <label class="quantity">
                        <span class="sr-only">Kiekis</span>
                        <input type="number" name="qty" min="1" max="20" value="1">
                      </label>
                      <button class="btn btn--primary" type="submit">Į krepšelį</button>
                    </form>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section class="section">
      <div class="container">
        <div class="section__inner">
          <div class="section__header">
            <p class="badge">Paslaugos</p>
            <h2>Pristatymas ir grąžinimai</h2>
          </div>
          <div class="stack stack--spacious">
            <article class="tile">
              <p class="tile__label">Pristatymas</p>
              <h3>Greita logistika</h3>
              <p class="muted">Siunčiame per 1–2 d. d. visoje Lietuvoje. Virš €70 – pristatymas nemokamas.</p>
            </article>
            <article class="tile">
              <p class="tile__label">Grąžinimas</p>
              <h3>Lanksti politika</h3>
              <p class="muted">Netiko dydis? Galite grąžinti arba pakeisti per 30 dienų be papildomų klausimų.</p>
            </article>
          </div>
        </div>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
