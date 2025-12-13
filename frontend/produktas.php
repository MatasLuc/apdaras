<?php
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/db.php';

$pdo = get_db_connection();
$id = (int) ($_GET['id'] ?? 0);

if (!$id) {
    header('Location: parduotuve.php');
    exit;
}

// -- Gauname duomenis (variacijas ir pan) prieš POST apdorojimą --
$stmtVar = $pdo->prepare("
    SELECT va.name as attribute, vv.value, vv.id as val_id
    FROM product_variations pv
    JOIN variation_values vv ON pv.variation_value_id = vv.id
    JOIN variation_attributes va ON vv.variation_attribute_id = va.id
    WHERE pv.product_id = ?
    ORDER BY va.name, vv.value
");
$stmtVar->execute([$id]);
$variations = $stmtVar->fetchAll();

$hasVariations = count($variations) > 0;
$groupedVariations = [];
foreach ($variations as $v) {
    $groupedVariations[$v['attribute']][] = ['id' => $v['val_id'], 'value' => $v['value']];
}

// -- POST Apdorojimas --
$errorMsg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['product_id'] ?? '';
    $quantity = (int) ($_POST['qty'] ?? 1);
    $variationId = !empty($_POST['variation_id']) ? (int)$_POST['variation_id'] : null;

    // VALIDACIJA: Jei prekė turi variacijų, bet niekas nepasirinkta
    if ($hasVariations && !$variationId) {
        $errorMsg = "Prašome pasirinkti savybę (dydį ar spalvą).";
    } else {
        $added = add_cart_item($productId, $quantity, $variationId);
        
        $_SESSION['cart_alert'] = $added
            ? ['type' => 'success', 'text' => 'Prekė pridėta į krepšelį.']
            : ['type' => 'error', 'text' => 'Nepavyko pridėti prekės.'];

        if ($added) {
            header('Location: produktas.php?id=' . $id);
            exit;
        }
    }
}

// 2. Gauname produkto informaciją
$stmt = $pdo->prepare("
    SELECT p.*, 
           GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') as category_names
    FROM products p
    LEFT JOIN product_categories pc ON pc.product_id = p.id
    LEFT JOIN categories c ON pc.category_id = c.id
    WHERE p.id = ?
    GROUP BY p.id
    LIMIT 1
");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    echo 'Prekė nerasta.'; exit;
}

// 3. Nuotraukos
$stmtImg = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC");
$stmtImg->execute([$id]);
$images = $stmtImg->fetchAll();
if (empty($images)) $images[] = ['image_url' => '', 'is_primary' => 1];

// 4. Susijusios prekės
$stmtRelated = $pdo->prepare("
    SELECT p.id, p.title, p.price, p.discount_price,
           (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) as image_url
    FROM products p
    WHERE p.id != ? AND p.stock > 0
    ORDER BY RAND()
    LIMIT 4
");
$stmtRelated->execute([$id]);
$relatedProducts = $stmtRelated->fetchAll();

$alert = $_SESSION['cart_alert'] ?? null;
unset($_SESSION['cart_alert']);
?>
<!DOCTYPE html>
<html lang="lt">
<head>
  <meta charset="UTF-8" />
  <title><?php echo htmlspecialchars($product['title']); ?> – apdaras.lt</title>
  <link rel="stylesheet" href="./assets/styles.css" />
  <style>
    /* ... (Stiliai lieka tie patys kaip anksčiau) ... */
    .product-layout { display: grid; grid-template-columns: 1.2fr 1fr; gap: 40px; align-items: start; }
    .gallery { display: grid; gap: 16px; }
    .gallery__main { width: 100%; aspect-ratio: 1/1.1; background: #f4f4f5; border-radius: 16px; overflow: hidden; border: 1px solid var(--stroke); }
    .gallery__main img { width: 100%; height: 100%; object-fit: cover; }
    .gallery__thumbs { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 4px; }
    .gallery__thumb { width: 80px; height: 80px; flex-shrink: 0; border-radius: 10px; overflow: hidden; cursor: pointer; border: 2px solid transparent; }
    .gallery__thumb.is-active { border-color: var(--accent); }
    .gallery__thumb img { width: 100%; height: 100%; object-fit: cover; }
    .product-info { display: flex; flex-direction: column; gap: 24px; position: sticky; top: 100px; }
    .big-price { font-size: 32px; font-weight: 800; color: var(--accent-2); }
    .big-old-price { font-size: 20px; text-decoration: line-through; color: var(--muted); }
    .add-to-cart-box { background: var(--surface); border: 1px solid var(--stroke); border-radius: 16px; padding: 20px; display: grid; gap: 16px; }
    .variation-select {
        display: flex; flex-wrap: wrap; gap: 8px;
    }
    .variation-radio { display: none; }
    .variation-label {
        border: 1px solid var(--stroke);
        background: #fff;
        padding: 8px 14px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 700;
        transition: 0.2s;
    }
    .variation-radio:checked + .variation-label {
        background: var(--text);
        color: #fff;
        border-color: var(--text);
    }
    @media (max-width: 900px) { .product-layout { grid-template-columns: 1fr; } .product-info { position: static; } }
  </style>
</head>
<body>
  <?php include __DIR__ . '/partials/nav.php'; ?>

  <main class="section">
    <div class="container">
      <?php if ($alert): ?>
        <div class="alert alert--<?php echo $alert['type']; ?>" style="margin-bottom: 24px;">
          <?php echo htmlspecialchars($alert['text']); ?>
        </div>
      <?php endif; ?>
      
      <?php if ($errorMsg): ?>
        <div class="alert alert--error" style="margin-bottom: 24px;">
          <?php echo htmlspecialchars($errorMsg); ?>
        </div>
      <?php endif; ?>

      <nav aria-label="Breadcrumb" style="margin-bottom: 24px; color: var(--muted); font-size: 14px;">
        <a href="parduotuve.php" class="text-link">Parduotuvė</a> / <span><?php echo htmlspecialchars($product['category_names'] ?: 'Kategorija'); ?></span>
      </nav>

      <div class="product-layout">
        <div class="gallery">
          <div class="gallery__main">
            <?php $mainSrc = !empty($images[0]['image_url']) ? $images[0]['image_url'] : ''; ?>
            <?php if($mainSrc): ?>
                <img id="mainImage" src="<?php echo htmlspecialchars($mainSrc); ?>" alt="">
            <?php else: ?>
                <div style="display:flex;align-items:center;justify-content:center;height:100%;">Nėra foto</div>
            <?php endif; ?>
          </div>
          <?php if (count($images) > 1): ?>
            <div class="gallery__thumbs">
              <?php foreach ($images as $index => $img): ?>
                <button class="gallery__thumb <?php echo $index === 0 ? 'is-active' : ''; ?>" onclick="changeImage('<?php echo $img['image_url']; ?>', this)">
                  <img src="<?php echo htmlspecialchars($img['image_url']); ?>" alt="">
                </button>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="product-info">
          <div>
            <?php if ($product['ribbon']): ?><span class="badge"><?php echo htmlspecialchars($product['ribbon']); ?></span><?php endif; ?>
            <h1 style="margin: 8px 0;"><?php echo htmlspecialchars($product['title']); ?></h1>
            <p class="lead" style="margin:0;"><?php echo htmlspecialchars($product['subtitle'] ?? ''); ?></p>
            <div style="display:flex; gap:12px; align-items:baseline; margin-top:12px;">
              <span class="big-price">€<?php echo number_format($product['discount_price'] ?: $product['price'], 2); ?></span>
              <?php if ($product['discount_price']): ?>
                <span class="big-old-price">€<?php echo number_format($product['price'], 2); ?></span>
              <?php endif; ?>
            </div>
          </div>

          <div class="add-to-cart-box">
             <div style="display: flex; justify-content: space-between; align-items: center;">
                 <span class="strong">Likutis:</span>
                 <span class="<?php echo $product['stock'] > 0 ? 'text-success' : 'text-danger'; ?>">
                    <?php echo $product['stock'] > 0 ? $product['stock'] . ' vnt.' : 'Išparduota'; ?>
                 </span>
             </div>
             
             <?php if ($product['stock'] > 0): ?>
                 <form method="post" class="stack">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    
                    <?php if ($groupedVariations): ?>
                        <?php foreach ($groupedVariations as $attrName => $values): ?>
                            <div>
                                <p class="card__eyebrow" style="margin-bottom: 8px;"><?php echo htmlspecialchars($attrName); ?></p>
                                <div class="variation-select">
                                    <?php foreach ($values as $val): ?>
                                        <label>
                                            <input type="radio" name="variation_id" value="<?php echo $val['id']; ?>" class="variation-radio" required>
                                            <span class="variation-label"><?php echo htmlspecialchars($val['value']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <div class="form__field" style="margin-top: 12px;">
                        <div style="display: flex; gap: 10px;">
                            <input type="number" name="qty" value="1" min="1" max="20" style="width: 80px;">
                            <button type="submit" class="btn btn--primary btn--block">Į krepšelį</button>
                        </div>
                    </div>
                 </form>
             <?php else: ?>
                 <button disabled class="btn btn--ghost btn--block">Laikinai nėra</button>
             <?php endif; ?>
          </div>
          
          <div class="product-description">
            <h3>Apie produktą</h3>
            <div><?php echo nl2br(htmlspecialchars($product['description'] ?: $product['summary'])); ?></div>
          </div>
        </div>
      </div>
      
      <?php if ($relatedProducts): ?>
        <div style="margin-top: 80px;">
            <h3>Jums gali patikti</h3>
            <div class="grid grid--four" style="margin-top: 24px;">
                <?php foreach ($relatedProducts as $rel): ?>
                    <a href="produktas.php?id=<?php echo $rel['id']; ?>" class="card card--product" style="text-decoration:none;color:inherit;">
                        <div class="card__image-container">
                            <?php if($rel['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($rel['image_url']); ?>" class="card__image" alt="">
                            <?php else: ?>
                                <span class="muted">Nėra foto</span>
                            <?php endif; ?>
                        </div>
                        <h3><?php echo htmlspecialchars($rel['title']); ?></h3>
                        <span class="card__price">€<?php echo number_format($rel['discount_price'] ?: $rel['price'], 2); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
      <?php endif; ?>
    </div>
  </main>
  <script>
    function changeImage(src, thumb) {
        document.getElementById('mainImage').src = src;
        document.querySelectorAll('.gallery__thumb').forEach(el => el.classList.remove('is-active'));
        thumb.classList.add('is-active');
    }
  </script>
  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
