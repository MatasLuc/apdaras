<?php
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/db.php';

$pdo = get_db_connection();
$id = (int) ($_GET['id'] ?? 0);

// Jei ID nenurodytas, grąžiname į parduotuvę
if (!$id) {
    header('Location: parduotuve.php');
    exit;
}

// 1. Apdorojame įdėjimą į krepšelį
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['product_id'] ?? '';
    $quantity = (int) ($_POST['qty'] ?? 1);

    $added = add_cart_item($productId, $quantity);

    $_SESSION['cart_alert'] = $added
        ? ['type' => 'success', 'text' => $added['name'] . ' pridėta į krepšelį.']
        : ['type' => 'error', 'text' => 'Nepavyko pridėti prekės.'];

    // Perkrauname tą patį puslapį, kad išvengtume formos pakartotinio siuntimo
    header('Location: produktas.php?id=' . $id);
    exit;
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
    // Jei prekė nerasta
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="lt"><head><meta charset="UTF-8"><title>Nerasta</title></head><body><h1>Prekė nerasta</h1><a href="parduotuve.php">Grįžti į parduotuvę</a></body></html>';
    exit;
}

// 3. Gauname nuotraukas
$stmtImg = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC");
$stmtImg->execute([$id]);
$images = $stmtImg->fetchAll();

// Jei nėra nuotraukų, įdedame placeholderį, kad kodas nelūžtų
if (empty($images)) {
    $images[] = ['image_url' => '', 'is_primary' => 1];
}

// 4. Gauname variacijas (pvz. Dydžius, Spalvas)
$stmtVar = $pdo->prepare("
    SELECT va.name as attribute, vv.value
    FROM product_variations pv
    JOIN variation_values vv ON pv.variation_value_id = vv.id
    JOIN variation_attributes va ON vv.variation_attribute_id = va.id
    WHERE pv.product_id = ?
    ORDER BY va.name, vv.value
");
$stmtVar->execute([$id]);
$variations = $stmtVar->fetchAll();

// Sugrupuojame variacijas pagal atributą (pvz. Dydis: M, L)
$groupedVariations = [];
foreach ($variations as $v) {
    $groupedVariations[$v['attribute']][] = $v['value'];
}

// 5. Gauname kelias susijusias prekes (atsitiktines, išskyrus dabartinę)
// PAKEITIMAS: LIMIT 3 -> LIMIT 4
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($product['title'], ENT_QUOTES, 'UTF-8'); ?> – apdaras.lt</title>
  <link rel="stylesheet" href="./assets/styles.css" />
  <style>
    /* Papildomi stiliai specifiniai produkto puslapiui */
    .product-layout {
        display: grid;
        grid-template-columns: 1.2fr 1fr; /* Kairėje nuotraukos, dešinėje info */
        gap: 40px;
        align-items: start;
    }
    
    .gallery {
        display: grid;
        gap: 16px;
    }
    
    .gallery__main {
        width: 100%;
        aspect-ratio: 1 / 1.1; /* Šiek tiek aukštesnė nei kvadratas */
        background: #f4f4f5;
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid var(--stroke);
    }
    
    .gallery__main img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        cursor: zoom-in;
    }

    .gallery__thumbs {
        display: flex;
        gap: 10px;
        overflow-x: auto;
        padding-bottom: 4px;
    }

    .gallery__thumb {
        width: 80px;
        height: 80px;
        flex-shrink: 0;
        border-radius: 10px;
        overflow: hidden;
        cursor: pointer;
        border: 2px solid transparent;
        transition: border-color 0.2s;
    }
    
    .gallery__thumb.is-active {
        border-color: var(--accent);
    }

    .gallery__thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .product-info {
        display: flex;
        flex-direction: column;
        gap: 24px;
        position: sticky;
        top: 100px; /* Kad skrolinant informacija liktų matoma */
    }

    .product-header h1 {
        margin: 0 0 8px 0;
        font-size: clamp(28px, 3vw, 40px);
        line-height: 1.1;
    }

    .product-price-row {
        display: flex;
        align-items: baseline;
        gap: 12px;
        margin-top: 12px;
    }

    .big-price {
        font-size: 32px;
        font-weight: 800;
        color: var(--accent-2);
    }
    
    .big-old-price {
        font-size: 20px;
        text-decoration: line-through;
        color: var(--muted);
    }

    .product-description {
        line-height: 1.8;
        color: var(--muted);
    }

    .attrs-list {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
    }
    
    .attr-group {
        display: grid;
        gap: 6px;
    }
    
    .attr-label {
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--muted);
        letter-spacing: 0.5px;
    }

    .add-to-cart-box {
        background: var(--surface);
        border: 1px solid var(--stroke);
        border-radius: 16px;
        padding: 20px;
        display: grid;
        gap: 16px;
    }

    @media (max-width: 900px) {
        .product-layout {
            grid-template-columns: 1fr;
            gap: 24px;
        }
        .product-info {
            position: static;
        }
    }
  </style>
</head>
<body>
  <?php include __DIR__ . '/partials/nav.php'; ?>

  <main class="section">
    <div class="container">
      
      <?php if ($alert): ?>
        <div class="alert alert--<?php echo htmlspecialchars($alert['type'], ENT_QUOTES, 'UTF-8'); ?>" style="margin-bottom: 24px;">
          <?php echo htmlspecialchars($alert['text'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <nav aria-label="Breadcrumb" style="margin-bottom: 24px; color: var(--muted); font-size: 14px;">
        <a href="parduotuve.php" class="text-link">Parduotuvė</a> 
        <span style="margin: 0 6px;">/</span> 
        <span><?php echo htmlspecialchars($product['category_names'] ?: 'Kategorija', ENT_QUOTES, 'UTF-8'); ?></span>
      </nav>

      <div class="product-layout">
        
        <div class="gallery">
          <div class="gallery__main">
            <?php 
              $mainSrc = !empty($images[0]['image_url']) ? $images[0]['image_url'] : ''; 
            ?>
            <?php if($mainSrc): ?>
                <img id="mainImage" src="<?php echo htmlspecialchars($mainSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($product['title'], ENT_QUOTES, 'UTF-8'); ?>">
            <?php else: ?>
                <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:var(--muted);">Nėra nuotraukos</div>
            <?php endif; ?>
          </div>
          <?php if (count($images) > 1): ?>
            <div class="gallery__thumbs">
              <?php foreach ($images as $index => $img): ?>
                <button class="gallery__thumb <?php echo $index === 0 ? 'is-active' : ''; ?>" 
                        onclick="changeImage('<?php echo htmlspecialchars($img['image_url'], ENT_QUOTES, 'UTF-8'); ?>', this)">
                  <img src="<?php echo htmlspecialchars($img['image_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="">
                </button>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="product-info">
          <div class="product-header">
            <?php if ($product['ribbon']): ?>
                <span class="badge" style="margin-bottom: 12px; display:inline-flex;"><?php echo htmlspecialchars($product['ribbon'], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
            <h1><?php echo htmlspecialchars($product['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="lead" style="margin:0;"><?php echo htmlspecialchars($product['subtitle'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
            
            <div class="product-price-row">
              <span class="big-price">€<?php echo number_format($product['discount_price'] ?: $product['price'], 2); ?></span>
              <?php if ($product['discount_price']): ?>
                <span class="big-old-price">€<?php echo number_format($product['price'], 2); ?></span>
              <?php endif; ?>
            </div>
          </div>

          <?php if ($groupedVariations): ?>
            <div class="attrs-list">
                <?php foreach ($groupedVariations as $attrName => $values): ?>
                    <div class="attr-group">
                        <span class="attr-label"><?php echo htmlspecialchars($attrName, ENT_QUOTES, 'UTF-8'); ?></span>
                        <div class="pill-row">
                            <?php foreach ($values as $val): ?>
                                <span class="pill"><?php echo htmlspecialchars($val, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
          <?php endif; ?>

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
                    <div class="form__field">
                        <label for="qty" class="sr-only">Kiekis</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="number" id="qty" name="qty" value="1" min="1" max="<?php echo min(20, $product['stock']); ?>" style="width: 80px;">
                            <button type="submit" class="btn btn--primary btn--block">Į krepšelį</button>
                        </div>
                    </div>
                 </form>
             <?php else: ?>
                 <button disabled class="btn btn--ghost btn--block">Laikinai nėra</button>
             <?php endif; ?>
             
             <div class="meta-row" style="font-size: 13px; justify-content: center;">
                <span>🚚 Pristatymas 1-2 d.d.</span>
                <span>🛡️ 30 d. grąžinimas</span>
             </div>
          </div>

          <div class="product-description">
            <h3>Apie produktą</h3>
            <div class="stack">
                <?php 
                  // Paprastas nl2br aprašymui
                  echo nl2br(htmlspecialchars($product['description'] ?: $product['summary'], ENT_QUOTES, 'UTF-8')); 
                ?>
            </div>
            <?php if($product['weight_kg']): ?>
                <p class="muted" style="margin-top: 12px; font-size: 14px;">Svoris: <?php echo $product['weight_kg']; ?> kg</p>
            <?php endif; ?>
          </div>

        </div>
      </div>
      
      <?php if ($relatedProducts): ?>
      <div style="margin-top: 80px;">
        <div class="section__header">
            <p class="badge">Taip pat siūlome</p>
            <h2>Jums gali patikti</h2>
        </div>
        <div class="grid grid--four" style="margin-top: 24px;">
            <?php foreach ($relatedProducts as $rel): ?>
                <a href="produktas.php?id=<?php echo $rel['id']; ?>" class="card card--product" style="text-decoration: none; color: inherit;">
                    <div class="card__image-container">
                        <?php if($rel['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($rel['image_url'], ENT_QUOTES, 'UTF-8'); ?>" class="card__image" alt="">
                        <?php else: ?>
                            <span class="muted">Nėra foto</span>
                        <?php endif; ?>
                    </div>
                    <h3><?php echo htmlspecialchars($rel['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <div class="card__meta">
                        <span class="card__price">€<?php echo number_format($rel['discount_price'] ?: $rel['price'], 2); ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </main>

  <script>
    function changeImage(src, thumbElement) {
        if (!src) return;
        document.getElementById('mainImage').src = src;
        
        // Atnaujiname aktyvų thumbnail
        document.querySelectorAll('.gallery__thumb').forEach(el => el.classList.remove('is-active'));
        thumbElement.classList.add('is-active');
    }
  </script>

  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
