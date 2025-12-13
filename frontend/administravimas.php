<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$pdo = get_db_connection();
$user = require_login($pdo);

if (($user['role'] ?? 'customer') !== 'admin') {
    header('Location: paskyra.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="lt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Administravimas – apdaras.lt</title>
  <link rel="stylesheet" href="./assets/styles.css" />
</head>
<body>
  <?php include __DIR__ . '/partials/nav.php'; ?>

  <main class="section">
    <div class="container">
      <div class="tabs">
        <button class="tab is-active" data-tab="catalog">Katalogas</button>
        <button class="tab" data-tab="account">Naudotojai</button>
      </div>

      <div class="tab-panel is-active" id="tab-catalog">
        <div class="section__inner">
          <div class="section__header">
            <p class="badge">Katalogas</p>
            <h3>Produkto ir kategorijų valdymas</h3>
            <p class="muted">Tvarkykite prekes, kategorijas ir nuotraukas vienoje vietoje.</p>
          </div>

          <div class="admin-shell">
            <aside class="admin-sidebar">
              <div class="admin-sidebar__title">Parduotuvės nustatymai</div>
              <nav class="admin-nav">
                <button class="admin-nav__item is-active" type="button" data-target="catalog-summary">Produktai</button>
                <button class="admin-nav__item" type="button" data-target="category-management">Kategorijos</button>
                <button class="admin-nav__item" type="button" data-target="variation-management">Variacijos</button>
                <button class="admin-nav__item" type="button" data-target="product-editor">Nauja prekė</button>
              </nav>
            </aside>

            <div class="admin-main">
              <div class="card card--surface admin-card" id="catalog-summary" data-admin-section>
                <div class="card__header">
                  <div>
                    <p class="card__eyebrow">Katalogas</p>
                    <h4>Greita suvestinė</h4>
                  </div>
                  <div class="actions">
                    <button class="btn btn--primary" type="button" id="open-new-product">Pridėti produktą</button>
                  </div>
                </div>
                <div class="table" id="product-table" aria-live="polite">
                  <div class="table__row table__row--head">
                    <span>Pavadinimas</span>
                    <span>Kategorijos</span>
                    <span>Kaina</span>
                    <span>Veiksmai</span>
                  </div>
                </div>
              </div>

              <div class="card card--surface admin-card" id="category-management" data-admin-section>
                <div class="card__header">
                  <div>
                    <p class="card__eyebrow">Kategorijų valdymas</p>
                    <h4>Kategorijos ir subkategorijos</h4>
                  </div>
                </div>
                <div class="stack stack--spacious">
                  <form id="category-form" class="stack stack--spacious">
                    <label class="form__field">
                      <span>Kategorijos pavadinimas</span>
                      <input type="text" name="category_name" placeholder="Pvz., Aksesuarai" required />
                    </label>
                    <label class="form__field">
                      <span>Slug</span>
                      <input type="text" name="category_slug" placeholder="aksesuarai" required />
                    </label>
                    <button class="btn btn--primary" type="submit">Pridėti kategoriją</button>
                  </form>

                  <form id="subcategory-form" class="stack stack--spacious">
                    <label class="form__field">
                      <span>Pagrindinė kategorija</span>
                      <select name="parent_category" id="subcategory-parent" required></select>
                    </label>
                    <label class="form__field">
                      <span>Subkategorijos pavadinimas</span>
                      <input type="text" name="subcategory_name" placeholder="Oversize" required />
                    </label>
                    <label class="form__field">
                      <span>Slug</span>
                      <input type="text" name="subcategory_slug" placeholder="oversize" required />
                    </label>
                    <button class="btn" type="submit">Pridėti subkategoriją</button>
                  </form>

                  <div id="category-list" class="stack stack--spacious"></div>
                </div>
              </div>

              <div class="card card--surface admin-card" id="variation-management" data-admin-section>
                <div class="card__header">
                  <div>
                    <p class="card__eyebrow">Variacijų valdymas</p>
                    <h4>Spalvos, dydžiai ir kiti pasirinkimai</h4>
                  </div>
                </div>
                <div class="stack stack--spacious">
                  <form id="variation-attribute-form" class="stack stack--spacious">
                    <label class="form__field">
                      <span>Naujas variacijos atributas</span>
                      <input type="text" id="variation-attribute-name" placeholder="Pvz., Spalva, Dydis" required />
                    </label>
                    <button class="btn btn--primary" type="submit">Pridėti atributą</button>
                  </form>
                  <div class="stack stack--spacious">
                    <label class="form__field">
                      <span>Nauja reikšmė</span>
                      <input type="text" id="new-variation-value" placeholder="Nauja variacijos reikšmė" />
                    </label>
                    <label class="form__field">
                      <span>Pasirinkite atributą</span>
                      <select id="new-variation-attribute"></select>
                    </label>
                    <div class="form-actions form-actions--inline">
                      <button class="btn" type="button" id="add-variation-value">Pridėti reikšmę</button>
                    </div>
                  </div>
                  <div id="variation-library" class="stack stack--spacious"></div>
                </div>
              </div>

              <div class="card card--surface admin-card" id="product-editor" data-admin-section>
                <div class="card__header">
                  <div>
                    <p class="card__eyebrow">Produkto forma</p>
                    <h4 id="form-title">Pridėti naują produktą</h4>
                  </div>
                  <div class="actions">
                    <button class="btn btn--ghost" type="button" id="cancel-edit">Atšaukti</button>
                    <button class="btn btn--primary" type="submit" form="product-form">Išsaugoti produktą</button>
                  </div>
                </div>

                <div class="product-quick">
                   <button class="tile" type="button" id="trigger-upload">
                    <span class="tile__title">Įkelti paveikslėlius</span>
                    <span class="tile__hint">Pasirinkite kelis failus ir nustatykite pagrindinį</span>
                  </button>
                </div>

                <form id="product-form" class="panel panel--form">
                  <div id="form-messages" class="stack"></div>

                  <div class="form-section">
                    <div class="form-section__header">
                      <div>
                        <p class="card__eyebrow">Pagrindinė informacija</p>
                        <h4>Turinys</h4>
                      </div>
                    </div>
                  <div class="form-section__body">
                      <div class="stack stack--spacious">
                        <label class="form__field">
                          <span>Pavadinimas</span>
                          <input type="text" name="title" required />
                        </label>
                        <label class="form__field">
                          <span>Paantraštė</span>
                          <input type="text" name="subtitle" placeholder="Trumpa žinutė po pavadinimu" />
                        </label>
                        <label class="form__field">
                          <span>Juostelė</span>
                          <input type="text" name="ribbon" placeholder="Pvz., Nauja, Top pasirinkimas" />
                        </label>
                        <label class="form__field">
                          <span>Žymės</span>
                          <input type="text" name="tags" placeholder="Žymos, atskirtos kableliais" />
                        </label>
                        <label class="form__field">
                          <span>Paantraštė (santrauka)</span>
                          <textarea name="summary" rows="2"></textarea>
                        </label>
                        <label class="form__field">
                          <span>Aprašymas</span>
                          <textarea name="description" rows="4"></textarea>
                        </label>
                      </div>
                    </div>
                  </div>

                  <div class="form-section">
                    <div class="form-section__header">
                      <div>
                        <p class="card__eyebrow">Kaina</p>
                        <h4>Kainodara ir atsargos</h4>
                      </div>
                    </div>
                  <div class="form-section__body">
                      <div class="stack stack--spacious">
                        <label class="form__field">
                          <span>Kaina (€)</span>
                          <input type="number" name="price" min="0" step="0.01" required />
                        </label>
                        <label class="form__field">
                          <span>Kaina su nuolaida (€)</span>
                          <input type="number" name="discount_price" min="0" step="0.01" />
                        </label>
                        <label class="form__field">
                          <span>Svoris (kg)</span>
                          <input type="number" name="weight_kg" min="0" step="0.001" />
                        </label>
                        <label class="form__field">
                          <span>Likutis</span>
                          <input type="number" name="stock" min="0" step="1" value="0" />
                        </label>
                        <label class="form__field">
                          <span>Galimybė personalizuoti</span>
                          <select name="allow_personalization">
                            <option value="0">Ne</option>
                            <option value="1">Taip</option>
                          </select>
                        </label>
                      </div>
                    </div>
                  </div>

                  <div class="form-section">
                    <div class="form-section__header">
                      <div>
                        <p class="card__eyebrow">Nuotraukos</p>
                        <h4>Pagrindinė ir papildomos</h4>
                      </div>
                    </div>
                    <div class="form-section__body">
                      <div class="actions actions--wrap">
                        <input type="url" id="image-url" placeholder="https://..." />
                        <label class="checkbox">
                          <input type="checkbox" id="image-primary" />
                          <span>Pagrindinė</span>
                        </label>
                        <button class="btn" type="button" id="add-image">Pridėti nuorodą</button>
                      </div>
                      <div class="upload">
                        <label class="upload__drop">
                          <input type="file" id="image-upload" multiple accept="image/*" />
                          <div>
                            <p class="card__eyebrow">Įkelti failus</p>
                            <p>Nutempkite arba pasirinkite kelias nuotraukas.</p>
                          </div>
                        </label>
                      </div>
                      <div id="image-list" class="chip-list" aria-live="polite"></div>
                    </div>
                  </div>

                  <div class="form-section">
                    <div class="form-section__header">
                      <div>
                        <p class="card__eyebrow">Kategorijos</p>
                        <h4>Pagrindinės ir subkategorijos</h4>
                      </div>
                    </div>
                    <div class="form-section__body">
                      <div id="category-select" class="stack stack--spacious"></div>
                    </div>
                  </div>

                  <div class="form-section">
                    <div class="form-section__header">
                      <div>
                        <p class="card__eyebrow">Variacijos</p>
                        <h4>Pasirinkite reikšmes</h4>
                      </div>
                    </div>
                    <div class="form-section__body">
                  <div id="variation-picker" class="stack stack--spacious"></div>
                    </div>
                  </div>

                  <div class="form-section">
                    <div class="form-section__header">
                      <div>
                        <p class="card__eyebrow">Susiję produktai</p>
                        <h4>Pasirinkite iš katalogo</h4>
                      </div>
                      <div class="actions">
                        <input type="search" id="related-search" placeholder="Paieška pagal pavadinimą" />
                      </div>
                    </div>
                    <div class="form-section__body">
                      <div class="stack stack--spacious">
                        <div>
                          <p class="muted">Rezultatai</p>
                          <div id="related-results" class="list"></div>
                        </div>
                        <div>
                          <p class="muted">Pasirinkta</p>
                          <div id="related-selected" class="chip-list"></div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="form-actions">
                    <button class="btn btn--primary" type="submit">Išsaugoti produktą</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="tab-panel" id="tab-account">
        <div class="card card--surface">
          <div class="card__header">
            <div>
              <p class="card__eyebrow">Paskyros</p>
              <h4>Naudotojų valdymas</h4>
            </div>
          </div>
          <p class="muted">Ateityje čia bus vartotojų valdymas.</p>
        </div>
      </div>
    </div>
  </main>

  <script>
    window.ADMIN_CONFIG = {
        userRole: "<?php echo htmlspecialchars($user['role'] ?? '', ENT_QUOTES, 'UTF-8'); ?>",
        initialProductId: "<?php echo htmlspecialchars($_GET['productId'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
    };
  </script>
  <script src="./assets/admin.js" defer></script>

  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
