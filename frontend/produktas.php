<?php
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/db.php';

$pdo = get_db_connection();
$id = (int) ($_GET['id'] ?? 0);

if (!$id) {
    header('Location: parduotuve.php');
    exit;
}

// -- 1. Gauname produkto informaciją --
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

// -- 2. Variacijos --
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

// -- 3. Nuotraukos --
$stmtImg = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC");
$stmtImg->execute([$id]);
$images = $stmtImg->fetchAll();
if (empty($images)) $images[] = ['image_url' => '', 'is_primary' => 1];

// -- POST Apdorojimas --
$errorMsg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['product_id'] ?? '';
    $quantity = (int) ($_POST['qty'] ?? 1);
    $variationId = !empty($_POST['variation_id']) ? (int)$_POST['variation_id'] : null;
    $persDataRaw = $_POST['personalization_data'] ?? null;

    if ($hasVariations && !$variationId) {
        $errorMsg = "Būtina pasirinkti variaciją (dydį ar spalvą).";
    }

    if (!$errorMsg) {
        if ($persDataRaw) {
            $decoded = json_decode($persDataRaw, true);
            if (empty($decoded['text'])) {
                $persDataRaw = null;
            }
        }

        $added = add_cart_item($productId, $quantity, $variationId, $persDataRaw);
        $_SESSION['cart_alert'] = $added
            ? ['type' => 'success', 'text' => 'Prekė pridėta į krepšelį.']
            : ['type' => 'error', 'text' => 'Nepavyko pridėti prekės.'];

        if ($added) {
            header('Location: produktas.php?id=' . $id);
            exit;
        }
    }
}

// -- Susijusios prekės --
$stmtRelated = $pdo->prepare("SELECT p.id, p.title, p.price, p.discount_price, (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) as image_url FROM products p WHERE p.id != ? AND p.stock > 0 ORDER BY RAND() LIMIT 4");
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
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@700&family=Playfair+Display:wght@700&family=Montserrat:wght@900&display=swap" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>
<body>
  <?php include __DIR__ . '/partials/nav.php'; ?>

  <main class="section">
    <div class="container">
      <?php if ($alert): ?>
        <div class="alert alert--<?php echo $alert['type']; ?>" style="margin-bottom: 24px;"><?php echo htmlspecialchars($alert['text']); ?></div>
      <?php endif; ?>
      <?php if ($errorMsg): ?>
        <div class="alert alert--error" style="margin-bottom: 24px;"><strong>Klaida:</strong> <?php echo htmlspecialchars($errorMsg); ?></div>
      <?php endif; ?>

      <nav aria-label="Breadcrumb" style="margin-bottom: 24px; color: var(--muted); font-size: 14px;">
        <a href="parduotuve.php" class="text-link">Parduotuvė</a> / <span><?php echo htmlspecialchars($product['category_names'] ?: 'Kategorija'); ?></span>
      </nav>

      <?php if ($product['allow_personalization']): ?>
        <form method="post" id="productForm">
            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
            <input type="hidden" name="personalization_data" id="personalizationInput">
            <input type="hidden" name="is_personalized_intent" value="1">

            <div class="editor-layout">
                <div class="editor-thumbs">
                    <?php foreach ($images as $idx => $img): ?>
                        <div class="editor-thumb <?php echo $idx === 0 ? 'is-active' : ''; ?>" onclick="setEditorImage('<?php echo $img['image_url']; ?>', this)">
                            <img src="<?php echo htmlspecialchars($img['image_url']); ?>" alt="" crossorigin="anonymous">
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="editor-stage">
                    <div class="editor-canvas-wrap" id="canvasWrap">
                        <img id="editorBg" src="<?php echo htmlspecialchars($images[0]['image_url']); ?>" class="editor-bg" alt="" crossorigin="anonymous">
                        
                        <div class="editor-overlay" id="editorOverlay">
                            <div id="transformBox" class="transform-box" style="display:none;">
                                <p id="textElement" style="font-family: 'Roboto', sans-serif; font-size: 24px; color: #000000;"></p>
                                <div class="resize-handle" id="resizeHandle"></div>
                                <div class="rotate-handle" id="rotateHandle"></div>
                            </div>
                        </div>
                    </div>
                    <div id="loadingOverlay" style="display:none; position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.9); z-index:10; flex-direction:column; justify-content:center; align-items:center; text-align:center;">
                        <span class="strong" style="font-size:18px;">Ruošiamas užsakymas...</span>
                        <span class="muted" style="font-size:14px; margin-top:4px;">Generuojamas spaudos failas ir peržiūra</span>
                    </div>
                </div>

                <div class="editor-controls">
                    <div class="control-group">
                        <p class="card__eyebrow">Redagavimas</p>
                        <input type="text" id="TextInput" placeholder="Jūsų tekstas..." class="form__field" style="width: 100%; padding: 10px;">
                        
                        <label class="form__field">
                            <span>Šriftas</span>
                            <select id="FontSelect">
                                <option value="'Roboto', sans-serif">Roboto</option>
                                <option value="'Playfair Display', serif">Playfair</option>
                                <option value="'Montserrat', sans-serif">Montserrat</option>
                                <option value="monospace">Monospace</option>
                            </select>
                        </label>

                        <div>
                            <span style="font-size: 13px; font-weight: 700; display:block; margin-bottom: 6px;">Spalva</span>
                            <div class="color-options" id="ColorPicker">
                                <button type="button" class="color-btn is-active" style="background: #000000;" data-color="#000000"></button>
                                <button type="button" class="color-btn" style="background: #ffffff;" data-color="#ffffff"></button>
                                <button type="button" class="color-btn" style="background: #ef4444;" data-color="#ef4444"></button>
                                <button type="button" class="color-btn" style="background: #22c55e;" data-color="#22c55e"></button>
                                <button type="button" class="color-btn" style="background: #3b82f6;" data-color="#3b82f6"></button>
                                <button type="button" class="color-btn" style="background: #fbbf24;" data-color="#fbbf24"></button>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h1 style="margin: 0; font-size: 24px;"><?php echo htmlspecialchars($product['title']); ?></h1>
                        <p class="big-price">€<?php echo number_format($product['discount_price'] ?: $product['price'], 2); ?></p>
                    </div>

                    <?php if ($groupedVariations): ?>
                        <div class="control-group">
                            <?php foreach ($groupedVariations as $attrName => $values): ?>
                                <div>
                                    <p class="card__eyebrow" style="margin-bottom: 8px;"><?php echo htmlspecialchars($attrName); ?></p>
                                    <div class="variation-select">
                                        <?php foreach ($values as $val): ?>
                                            <label>
                                                <input type="radio" name="variation_id" value="<?php echo $val['id']; ?>" class="variation-radio">
                                                <span class="variation-label"><?php echo htmlspecialchars($val['value']); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div style="display: flex; gap: 10px; margin-top: auto;">
                        <input type="number" name="qty" value="1" min="1" max="20" style="width: 70px; text-align: center; border-radius: 12px; border: 1px solid var(--stroke);">
                        <button type="submit" id="submitBtn" class="btn btn--primary btn--block">Į krepšelį</button>
                    </div>
                </div>
            </div>
        </form>

        <script>
            const textInput = document.getElementById('TextInput');
            const transformBox = document.getElementById('transformBox');
            const textElement = document.getElementById('textElement');
            const fontSelect = document.getElementById('FontSelect');
            const colorPicker = document.getElementById('ColorPicker');
            const persInput = document.getElementById('personalizationInput');
            const canvasWrap = document.getElementById('canvasWrap');
            const editorBg = document.getElementById('editorBg');

            let state = {
                text: '',
                color: '#000000',
                fontFamily: "'Roboto', sans-serif",
                x: 50,
                y: 50,
                scale: 1,
                rotation: 0
            };

            function updateView() {
                textElement.innerText = state.text;
                transformBox.style.display = state.text ? 'block' : 'none';
                textElement.style.color = state.color;
                textElement.style.fontFamily = state.fontFamily;
                transformBox.style.left = state.x + '%';
                transformBox.style.top = state.y + '%';
                transformBox.style.transform = `translate(-50%, -50%) rotate(${state.rotation}deg) scale(${state.scale})`;
            }

            textInput.addEventListener('input', (e) => { state.text = e.target.value; updateView(); });
            fontSelect.addEventListener('change', (e) => { state.fontFamily = e.target.value; updateView(); });

            colorPicker.addEventListener('click', (e) => {
                if(e.target.classList.contains('color-btn')) {
                    document.querySelectorAll('.color-btn').forEach(b => b.classList.remove('is-active'));
                    e.target.classList.add('is-active');
                    state.color = e.target.dataset.color;
                    updateView();
                }
            });

            window.setEditorImage = function(src, el) {
                editorBg.src = src;
                document.querySelectorAll('.editor-thumb').forEach(t => t.classList.remove('is-active'));
                el.classList.add('is-active');
            }

            // GIZMO LOGIKA
            let isDragging = false, isRotating = false, isResizing = false;
            let startAngle = 0, startScale = 1;

            function getMousePos(e) {
                const rect = canvasWrap.getBoundingClientRect();
                return { x: e.clientX - rect.left, y: e.clientY - rect.top, rect };
            }

            transformBox.addEventListener('mousedown', (e) => {
                if(e.target.classList.contains('rotate-handle') || e.target.classList.contains('resize-handle')) return;
                isDragging = true;
                transformBox.classList.add('is-selected');
                e.preventDefault();
            });

            document.getElementById('rotateHandle').addEventListener('mousedown', (e) => {
                isRotating = true;
                const boxRect = transformBox.getBoundingClientRect();
                const centerX = boxRect.left + boxRect.width / 2;
                const centerY = boxRect.top + boxRect.height / 2;
                startAngle = Math.atan2(e.clientY - centerY, e.clientX - centerX) - (state.rotation * Math.PI / 180);
                e.stopPropagation(); e.preventDefault();
            });

            document.getElementById('resizeHandle').addEventListener('mousedown', (e) => {
                isResizing = true;
                const rect = transformBox.getBoundingClientRect();
                const centerX = rect.left + rect.width / 2;
                const centerY = rect.top + rect.height / 2;
                const dist = Math.hypot(e.clientX - centerX, e.clientY - centerY);
                startScale = state.scale / dist;
                e.stopPropagation(); e.preventDefault();
            });

            document.addEventListener('mousemove', (e) => {
                if (!isDragging && !isRotating && !isResizing) return;
                const pos = getMousePos(e);

                if (isDragging) {
                    state.x = (pos.x / pos.rect.width) * 100;
                    state.y = (pos.y / pos.rect.height) * 100;
                } else if (isRotating) {
                    const boxRect = transformBox.getBoundingClientRect();
                    const centerX = boxRect.left + boxRect.width / 2;
                    const centerY = boxRect.top + boxRect.height / 2;
                    state.rotation = (Math.atan2(e.clientY - centerY, e.clientX - centerX) - startAngle) * (180 / Math.PI);
                } else if (isResizing) {
                    const boxRect = transformBox.getBoundingClientRect();
                    const centerX = boxRect.left + boxRect.width / 2;
                    const centerY = boxRect.top + boxRect.height / 2;
                    const dist = Math.hypot(e.clientX - centerX, e.clientY - centerY);
                    state.scale = Math.max(0.2, dist * startScale);
                }
                updateView();
            });

            document.addEventListener('mouseup', () => {
                isDragging = false; isRotating = false; isResizing = false;
            });

            // --- SUBMIT LOGIKA (PDF ir PNG) ---
            const form = document.getElementById('productForm');
            const loadingOverlay = document.getElementById('loadingOverlay');

            async function uploadDataUrl(filename, dataUrl) {
                const res = await fetch('api/upload.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ fileName: filename, dataUrl: dataUrl })
                });
                if(!res.ok) throw new Error('Upload failed');
                const data = await res.json();
                return data.url;
            }

            form.addEventListener('submit', async (e) => {
                if (!state.text) { persInput.value = ''; return; }
                
                e.preventDefault();
                loadingOverlay.style.display = 'flex';
                transformBox.classList.remove('is-selected');

                try {
                    const { jsPDF } = window.jspdf;

                    // 1. Sugeneruojame SPAUDOS FAILĄ (be fono, tik tekstas, aukšta kokybė)
                    editorBg.style.visibility = 'hidden'; 
                    
                    // TAISYMAS: Pridėti scroll nustatymai, kad išvengti nukirpimo
                    const printCanvas = await html2canvas(canvasWrap, {
                        useCORS: true,
                        scale: 4, 
                        backgroundColor: null,
                        scrollX: 0,
                        scrollY: 0
                    });
                    
                    // Konvertuojame pikselius į taškus (points) PDF'ui: 1px = 0.75pt
                    const widthPt = printCanvas.width * 0.75;
                    const heightPt = printCanvas.height * 0.75;

                    const pdf = new jsPDF({ 
                        orientation: printCanvas.width > printCanvas.height ? 'l' : 'p', 
                        unit: 'pt', 
                        format: [widthPt, heightPt] 
                    });
                    
                    pdf.addImage(printCanvas.toDataURL('image/png'), 'PNG', 0, 0, widthPt, heightPt);
                    const pdfData = pdf.output('datauristring');
                    
                    const pdfUrl = await uploadDataUrl('print-file.pdf', pdfData);
                    state.printPdfUrl = pdfUrl;

                    // 2. Sugeneruojame PERŽIŪROS FAILĄ (su fonu)
                    editorBg.style.visibility = 'visible'; 
                    const previewCanvas = await html2canvas(canvasWrap, {
                        useCORS: true,
                        scale: 1,
                        scrollX: 0,
                        scrollY: 0
                    });
                    const previewUrl = await uploadDataUrl('preview-snapshot.png', previewCanvas.toDataURL('image/png'));
                    state.snapshotUrl = previewUrl;

                    persInput.value = JSON.stringify(state);
                    form.submit();

                } catch (err) {
                    console.error(err);
                    alert('Klaida kuriant dizainą. Bandykite dar kartą.');
                    loadingOverlay.style.display = 'none';
                    editorBg.style.visibility = 'visible';
                    transformBox.classList.add('is-selected');
                }
            });
        </script>

      <?php else: ?>
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
               <form method="post" class="stack">
                  <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                  <?php if ($groupedVariations): ?>
                      <?php foreach ($groupedVariations as $attrName => $values): ?>
                          <div>
                              <p class="card__eyebrow" style="margin-bottom: 8px;"><?php echo htmlspecialchars($attrName); ?></p>
                              <div class="variation-select">
                                  <?php foreach ($values as $val): ?>
                                      <label>
                                          <input type="radio" name="variation_id" value="<?php echo $val['id']; ?>" class="variation-radio">
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
            </div>
            
            <div class="product-description">
              <h3>Apie produktą</h3>
              <div><?php echo nl2br(htmlspecialchars($product['description'] ?: $product['summary'])); ?></div>
            </div>
          </div>
        </div>
        <script>
            function changeImage(src, thumb) {
                document.getElementById('mainImage').src = src;
                document.querySelectorAll('.gallery__thumb').forEach(el => el.classList.remove('is-active'));
                thumb.classList.add('is-active');
            }
        </script>
      <?php endif; ?>
      
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
  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
