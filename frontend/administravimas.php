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

  <section class="hero">
    <div class="container hero__shell">
      <div class="hero__grid">
        <div class="hero__content">
          <div class="badge">Administravimas</div>
          <h1>Valdymo skydelis</h1>
          <p class="lead">Čia galėsite tvarkyti katalogą ir naudotojus. Šiuo metu tai informacinis puslapis.</p>
          <div class="meta-row">
            <span>🛠️ Paruošta plėtrai</span>
            <span>🔐 Tik administratoriams</span>
          </div>
        </div>
        <div class="hero__visual">
          <div class="hero__panel">
            <p class="card__eyebrow">Prisijungęs</p>
            <p class="muted"><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></p>
          </div>
        </div>
      </div>
    </div>
  </section>

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
            <p class="muted">Tvarkykite prekes, kategorijas ir nuotraukas vienoje vietoje. Naujos prekės forma atsidaro atskiram lange, kaip ir redagavimas.</p>
          </div>

          <div class="collapsible card card--surface is-open" data-section="catalog-summary">
            <div class="card__header collapsible__header">
              <div>
                <p class="card__eyebrow">Katalogas</p>
                <h4>Greita suvestinė</h4>
              </div>
              <div class="actions">
                <button class="btn btn--soft" type="button" id="refresh-data">Atnaujinti duomenis</button>
                <button class="btn btn--primary" type="button" id="open-new-product">Pridėti produktą</button>
                <button class="btn btn--ghost collapsible__toggle" type="button">Suskleisti</button>
              </div>
            </div>
            <div class="collapsible__body">
              <div class="table" id="product-table" aria-live="polite">
                <div class="table__row table__row--head">
                  <span>Pavadinimas</span>
                  <span>Kategorijos</span>
                  <span>Kaina</span>
                  <span>Veiksmai</span>
                </div>
              </div>
            </div>
          </div>

          <div class="collapsible card card--surface is-open" data-section="category-management">
            <div class="card__header collapsible__header">
              <div>
                <p class="card__eyebrow">Kategorijų valdymas</p>
                <h4>Kategorijos ir subkategorijos</h4>
              </div>
              <button class="btn btn--ghost collapsible__toggle" type="button">Suskleisti</button>
            </div>
            <div class="collapsible__body">
              <div class="stack" id="category-manager">
                <form id="category-form" class="form-grid">
                  <label class="form-field">
                    <span>Kategorijos pavadinimas</span>
                    <input type="text" name="category_name" placeholder="Pvz., Aksesuarai" required />
                  </label>
                  <label class="form-field">
                    <span>Slug</span>
                    <input type="text" name="category_slug" placeholder="aksesuarai" required />
                  </label>
                  <button class="btn btn--primary" type="submit">Pridėti kategoriją</button>
                </form>

                <form id="subcategory-form" class="form-grid">
                  <label class="form-field">
                    <span>Pagrindinė kategorija</span>
                    <select name="parent_category" id="subcategory-parent" required></select>
                  </label>
                  <label class="form-field">
                    <span>Subkategorijos pavadinimas</span>
                    <input type="text" name="subcategory_name" placeholder="Oversize" required />
                  </label>
                  <label class="form-field">
                    <span>Slug</span>
                    <input type="text" name="subcategory_slug" placeholder="oversize" required />
                  </label>
                  <button class="btn" type="submit">Pridėti subkategoriją</button>
                </form>

                <div id="category-list" class="stack stack--spacious"></div>
              </div>
            </div>
          </div>

          <div class="collapsible card card--surface is-open" data-section="product-editor">
            <div class="card__header collapsible__header">
              <div>
                <p class="card__eyebrow">Produktų forma</p>
                <h4 id="form-title">Pridėti naują produktą</h4>
              </div>
              <div class="actions">
                <button class="btn btn--primary" type="submit" form="product-form">Išsaugoti produktą</button>
                <button class="btn btn--ghost collapsible__toggle" type="button">Suskleisti</button>
              </div>
            </div>
            <div class="collapsible__body">
              <div class="form-panels">
                <form id="product-form" class="panel panel--form">
                  <div class="collapsible form-section is-open">
                    <div class="form-section__header collapsible__header">
                      <div>
                        <p class="card__eyebrow">Pagrindinė informacija</p>
                        <h4>Turinys ir kaina</h4>
                      </div>
                      <button class="btn btn--ghost collapsible__toggle" type="button">Suskleisti</button>
                    </div>
                    <div class="form-section__body collapsible__body">
                      <div class="form-grid">
                        <label class="form-field">
                          <span>Pavadinimas</span>
                          <input type="text" name="title" required />
                        </label>
                        <label class="form-field">
                          <span>Paantraštė</span>
                          <input type="text" name="subtitle" placeholder="Trumpa žinutė po pavadinimu" />
                        </label>
                        <label class="form-field">
                          <span>Juostelė</span>
                          <input type="text" name="ribbon" placeholder="Pvz., Nauja, Top pasirinkimas" />
                        </label>
                        <label class="form-field">
                          <span>Žymės</span>
                          <input type="text" name="tags" placeholder="Žymos, atskirtos kableliais" />
                        </label>
                        <label class="form-field form-field--wide">
                          <span>Paantraštė (santrauka)</span>
                          <textarea name="summary" rows="2"></textarea>
                        </label>
                        <label class="form-field form-field--wide">
                          <span>Aprašymas</span>
                          <textarea name="description" rows="4"></textarea>
                        </label>
                      </div>

                      <div class="form-grid">
                        <label class="form-field">
                          <span>Kaina (€)</span>
                          <input type="number" name="price" min="0" step="0.01" required />
                        </label>
                        <label class="form-field">
                          <span>Kaina su nuolaida (€)</span>
                          <input type="number" name="discount_price" min="0" step="0.01" />
                        </label>
                        <label class="form-field">
                          <span>Svoris (kg)</span>
                          <input type="number" name="weight_kg" min="0" step="0.001" />
                        </label>
                        <label class="form-field">
                          <span>Likutis</span>
                          <input type="number" name="stock" min="0" step="1" value="0" />
                        </label>
                      </div>

                      <div class="form-grid">
                        <label class="form-field">
                          <span>Galimybė personalizuoti</span>
                          <select name="allow_personalization">
                            <option value="0">Ne</option>
                            <option value="1">Taip</option>
                          </select>
                        </label>
                        <label class="form-field">
                          <span>API Bearer žetonas</span>
                          <input type="text" name="token" id="form-token" placeholder="Įveskite jei siunčiate į API" />
                        </label>
                      </div>
                    </div>
                  </div>

                  <div class="collapsible form-section is-open">
                    <div class="form-section__header collapsible__header">
                      <div>
                        <p class="card__eyebrow">Nuotraukos</p>
                        <h4>Pagrindinė ir papildomos</h4>
                      </div>
                      <div class="actions">
                        <button class="btn btn--ghost collapsible__toggle" type="button">Suskleisti</button>
                      </div>
                    </div>
                    <div class="form-section__body collapsible__body">
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
                            <p>Nutempkite arba pasirinkite kelias nuotraukas – jos bus patalpintos į „upload“ katalogą.</p>
                          </div>
                        </label>
                      </div>
                      <div id="image-list" class="chip-list" aria-live="polite"></div>
                    </div>
                  </div>

                  <div class="collapsible form-section is-open">
                    <div class="form-section__header collapsible__header">
                      <div>
                        <p class="card__eyebrow">Kategorijos</p>
                        <h4>Pagrindinės ir subkategorijos</h4>
                      </div>
                      <button class="btn btn--ghost collapsible__toggle" type="button">Suskleisti</button>
                    </div>
                    <div class="form-section__body collapsible__body">
                      <div id="category-select" class="grid grid--two"></div>
                    </div>
                  </div>

                  <div class="collapsible form-section is-open">
                    <div class="form-section__header collapsible__header">
                      <div>
                        <p class="card__eyebrow">Variacijos</p>
                        <h4>Spalvos, dydžiai ir kiti pasirinkimai</h4>
                      </div>
                      <div class="actions">
                        <input type="text" id="new-variation-value" placeholder="Nauja variacijos reikšmė" />
                        <select id="new-variation-attribute"></select>
                        <button class="btn" type="button" id="add-variation-value">Pridėti reikšmę</button>
                        <button class="btn btn--ghost collapsible__toggle" type="button">Suskleisti</button>
                      </div>
                    </div>
                    <div class="form-section__body collapsible__body">
                      <div id="variation-list" class="grid grid--two"></div>
                    </div>
                  </div>

                  <div class="collapsible form-section is-open">
                    <div class="form-section__header collapsible__header">
                      <div>
                        <p class="card__eyebrow">Susiję produktai</p>
                        <h4>Pasirinkite iš katalogo</h4>
                      </div>
                      <div class="actions">
                        <input type="search" id="related-search" placeholder="Paieška pagal pavadinimą" />
                        <button class="btn btn--ghost collapsible__toggle" type="button">Suskleisti</button>
                      </div>
                    </div>
                    <div class="form-section__body collapsible__body">
                      <div class="grid grid--two">
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

                <aside class="panel panel--preview">
                  <div class="form-section__header">
                    <div>
                      <p class="card__eyebrow">Peržiūra</p>
                      <h4>Paruošta siuntimui</h4>
                    </div>
                  </div>
                  <div id="product-preview" class="stack" aria-live="polite">
                    <p class="muted">Užpildykite formą kairėje, kad pamatytumėte suformuotą objekto santrauką.</p>
                  </div>
                </aside>
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
          <p class="muted">Šis tabas rezervuotas naudotojų ir teisių valdymui. Šiuo metu visas dėmesys – katalogo kūrimui.</p>
        </div>
      </div>
    </div>
  </main>

  <script>
    const apiBaseUrl = "<?php echo getenv('API_BASE_URL') ?: 'http://localhost:4000'; ?>";
    const params = new URLSearchParams(window.location.search);
    const editingProductId = params.get('productId');

    const state = {
      products: [],
      categories: [],
      variations: [],
      images: [],
      categoryIds: new Set(),
      subcategoryIds: new Set(),
      variationIds: new Set(),
      relatedIds: new Set()
    };

    const elements = {
      tabs: document.querySelectorAll('.tab'),
      panels: document.querySelectorAll('.tab-panel'),
      productTable: document.getElementById('product-table'),
      categoryManager: document.getElementById('category-list'),
      categorySelect: document.getElementById('category-select'),
      subcategoryParent: document.getElementById('subcategory-parent'),
      variationList: document.getElementById('variation-list'),
      variationSelect: document.getElementById('new-variation-attribute'),
      imageList: document.getElementById('image-list'),
      relatedResults: document.getElementById('related-results'),
      relatedSelected: document.getElementById('related-selected'),
      preview: document.getElementById('product-preview'),
      form: document.getElementById('product-form'),
      formTitle: document.getElementById('form-title'),
      formToken: document.getElementById('form-token')
    };

    function getToken() {
      return (elements.formToken.value || '').trim();
    }

    function setupCollapsibles() {
      document.querySelectorAll('.collapsible').forEach((section) => {
        const body = section.querySelector('.collapsible__body');
        const toggle = section.querySelector('.collapsible__toggle');
        if (!body || !toggle) return;
        const sync = () => {
          const open = section.classList.contains('is-open');
          body.style.display = open ? '' : 'none';
          toggle.textContent = open ? 'Suskleisti' : 'Išskleisti';
        };
        sync();
        toggle.addEventListener('click', () => {
          section.classList.toggle('is-open');
          sync();
        });
      });
    }

    function formatCurrency(value) {
      const num = Number(value || 0);
      return `${num.toFixed(2)} €`;
    }

    async function fetchJson(path, options = {}) {
      const response = await fetch(`${apiBaseUrl}${path}`, options);
      if (!response.ok) {
        throw new Error(`Klaida ${response.status}`);
      }
      return response.json();
    }

    function renderTabs() {
      elements.tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
          const target = tab.dataset.tab;
          elements.tabs.forEach((btn) => btn.classList.toggle('is-active', btn === tab));
          elements.panels.forEach((panel) => panel.classList.toggle('is-active', panel.id === `tab-${target}`));
        });
      });
    }

    function renderProductTable() {
      elements.productTable.querySelectorAll('.table__row:not(.table__row--head)').forEach((row) => row.remove());
      if (!state.products.length) {
        const empty = document.createElement('div');
        empty.className = 'table__row';
        empty.innerHTML = '<span class="muted">Nėra produktų</span><span></span><span></span><span></span>';
        elements.productTable.appendChild(empty);
        return;
      }

      state.products.forEach((product) => {
        const row = document.createElement('div');
        row.className = 'table__row';
        const categories = (product.categories_list || []).map((c) => c.name).join(', ');
        row.innerHTML = `
          <span class="strong">${product.title}</span>
          <span>${categories || 'Nepaskirta'}</span>
          <span>${formatCurrency(product.discount_price || product.price)}</span>
          <span class="actions">
            <a class="btn btn--ghost" target="_blank" href="administravimas.php?productId=${product.id}">Redaguoti</a>
          </span>
        `;
        elements.productTable.appendChild(row);
      });
    }

    function renderCategoryManager() {
      elements.categoryManager.innerHTML = '';
      if (!state.categories.length) {
        elements.categoryManager.innerHTML = '<p class="muted">Dar nėra kategorijų. Pridėkite viršuje.</p>';
        return;
      }

      state.categories.forEach((cat) => {
        const card = document.createElement('div');
        card.className = 'card card--ghost';
        const subs = (cat.subcategories || []).map((s) => `<span class="pill">${s.name}</span>`).join(' ');
        card.innerHTML = `
          <div class="card__row">
            <div>
              <p class="card__eyebrow">${cat.slug}</p>
              <h4>${cat.name}</h4>
              <div class="pill-row">${subs || '<span class="muted">Nėra subkategorijų</span>'}</div>
            </div>
            <div class="actions">
              <button class="btn btn--ghost" data-action="edit" data-id="${cat.id}">Redaguoti</button>
              <button class="btn btn--danger" data-action="delete" data-id="${cat.id}">Trinti</button>
            </div>
          </div>
        `;
        if (cat.subcategories?.length) {
          const subList = document.createElement('div');
          subList.className = 'list list--compact';
          cat.subcategories.forEach((sub) => {
            const item = document.createElement('div');
            item.className = 'list__item';
            item.innerHTML = `
              <span>${sub.name} <span class="muted">(${sub.slug})</span></span>
              <span class="actions">
                <button class="btn btn--ghost" data-sub-action="edit" data-id="${sub.id}" data-cat="${cat.id}">Redaguoti</button>
                <button class="btn btn--danger" data-sub-action="delete" data-id="${sub.id}" data-cat="${cat.id}">Trinti</button>
              </span>
            `;
            subList.appendChild(item);
          });
          card.appendChild(subList);
        }
        elements.categoryManager.appendChild(card);
      });
    }

    function renderCategorySelect() {
      elements.categorySelect.innerHTML = '';
      elements.subcategoryParent.innerHTML = '<option value="">Pasirinkite kategoriją</option>';
      state.categories.forEach((cat) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'card card--ghost';
        wrapper.innerHTML = `<p class="card__eyebrow">${cat.name}</p>`;
        const catLabel = document.createElement('label');
        catLabel.className = 'checkbox';
        catLabel.innerHTML = `
          <input type="checkbox" data-type="category" value="${cat.id}" ${state.categoryIds.has(cat.id) ? 'checked' : ''}/>
          <span>Priskirti kategoriją</span>
        `;
        wrapper.appendChild(catLabel);

        const scList = document.createElement('div');
        scList.className = 'stack';
        (cat.subcategories || []).forEach((sub) => {
          const scLabel = document.createElement('label');
          scLabel.className = 'checkbox';
          scLabel.innerHTML = `
            <input type="checkbox" data-type="subcategory" value="${sub.id}" ${state.subcategoryIds.has(sub.id) ? 'checked' : ''}/>
            <span>${sub.name}</span>
          `;
          scList.appendChild(scLabel);
        });
        wrapper.appendChild(scList);
        elements.categorySelect.appendChild(wrapper);

        const option = document.createElement('option');
        option.value = cat.id;
        option.textContent = cat.name;
        elements.subcategoryParent.appendChild(option);
      });
    }

    function renderVariations() {
      elements.variationList.innerHTML = '';
      elements.variationSelect.innerHTML = '';
      state.variations.forEach((attr) => {
        const option = document.createElement('option');
        option.value = attr.id;
        option.textContent = attr.name;
        elements.variationSelect.appendChild(option);

        const card = document.createElement('div');
        card.className = 'card card--ghost';
        card.innerHTML = `<p class="card__eyebrow">${attr.name}</p>`;
        const list = document.createElement('div');
        list.className = 'chip-list';
        attr.values.forEach((val) => {
          const chip = document.createElement('label');
          chip.className = 'chip chip--interactive';
          chip.innerHTML = `
            <input type="checkbox" value="${val.id}" ${state.variationIds.has(val.id) ? 'checked' : ''}/>
            <span>${val.value}</span>
          `;
          list.appendChild(chip);
        });
        card.appendChild(list);
        elements.variationList.appendChild(card);
      });
    }

    function renderRelated(searchTerm = '') {
      const normalized = searchTerm.toLowerCase();
      elements.relatedResults.innerHTML = '';
      state.products
        .filter((item) => item.title.toLowerCase().includes(normalized) && item.id !== Number(editingProductId))
        .forEach((item) => {
          const row = document.createElement('div');
          row.className = 'list__item';
          row.innerHTML = `
            <span>${item.title}</span>
            <button class="btn btn--ghost" type="button" data-id="${item.id}">Pridėti</button>
          `;
          elements.relatedResults.appendChild(row);
        });

      elements.relatedSelected.innerHTML = '';
      Array.from(state.relatedIds).forEach((id) => {
        const product = state.products.find((item) => item.id === Number(id));
        if (!product) return;
        const chip = document.createElement('div');
        chip.className = 'chip chip--interactive';
        chip.innerHTML = `
          <span>${product.title}</span>
          <button class="link text-danger" type="button" data-id="${id}">Šalinti</button>
        `;
        elements.relatedSelected.appendChild(chip);
      });
    }

    function renderImages() {
      elements.imageList.innerHTML = '';
      state.images.forEach((img, idx) => {
        const chip = document.createElement('div');
        chip.className = 'chip chip--interactive';
        chip.innerHTML = `
          <span>${img.is_primary ? '⭐️' : '🖼️'} ${img.image_url}</span>
          <div class="chip__actions">
            <button type="button" data-index="${idx}" class="link set-primary">Pagrindinė</button>
            <button type="button" data-index="${idx}" class="link text-danger remove">Šalinti</button>
          </div>
        `;
        elements.imageList.appendChild(chip);
      });
    }

    function updatePreview(payload) {
      elements.preview.innerHTML = '';
      const meta = document.createElement('div');
      meta.className = 'stack';
      meta.innerHTML = `
        <p class="muted">Slug: <strong>${payload.slug || 'nenurodytas'}</strong></p>
        <p class="muted">Kaina: <strong>${payload.price || 0} €</strong> · Nuolaida: <strong>${
        payload.discount_price || '–'
      }</strong></p>
        <p class="muted">Likutis: <strong>${payload.stock ?? 0} vnt.</strong> · Svoris: <strong>${
        payload.weight_kg || '–'
      } kg</strong></p>
        <p class="muted">Personalizacija: <strong>${payload.allow_personalization ? 'Taip' : 'Ne'}</strong></p>
      `;

      const lists = document.createElement('div');
      lists.className = 'stack';
      lists.innerHTML = `
        <p class="muted">Kategorijos: ${payload.categories.join(', ') || 'nepasirinkta'}</p>
        <p class="muted">Subkategorijos: ${payload.subcategories.join(', ') || 'nepasirinkta'}</p>
        <p class="muted">Variacijos: ${payload.variation_value_ids.length || 0} pasirinkimai</p>
        <p class="muted">Susiję produktai: ${payload.related_product_ids.length || 0} įrašai</p>
        <p class="muted">Nuotraukos: ${payload.images.length || 0} vnt.</p>
      `;

      const json = document.createElement('pre');
      json.className = 'code';
      json.textContent = JSON.stringify(payload, null, 2);

      elements.preview.append(meta, lists, json);
    }

    async function loadCollections() {
      try {
        const [products, categories, variations] = await Promise.all([
          fetchJson('/products'),
          fetchJson('/categories'),
          fetchJson('/variations')
        ]);

        state.products = products;
        state.categories = categories.reduce((acc, row) => {
          let entry = acc.find((item) => item.id === row.id);
          if (!entry) {
            entry = { id: row.id, name: row.name, slug: row.slug, subcategories: [] };
            acc.push(entry);
          }
          if (row.subcategory_id) {
            entry.subcategories.push({ id: row.subcategory_id, name: row.subcategory_name, slug: row.subcategory_slug });
          }
          return acc;
        }, []);
        state.variations = variations;

        renderProductTable();
        renderCategoryManager();
        renderCategorySelect();
        renderVariations();
        renderRelated();
      } catch (error) {
        elements.productTable.insertAdjacentHTML('beforeend', `<div class="table__row"><span class="text-danger">${error.message}</span></div>`);
      }
    }

    async function loadProduct(productId) {
      if (!productId) return;
      const data = await fetchJson(`/products/${productId}`);
      elements.formTitle.textContent = `Redaguojama prekė #${productId}`;
      elements.form.querySelector('[name="title"]').value = data.title || '';
      elements.form.querySelector('[name="subtitle"]').value = data.subtitle || '';
      elements.form.querySelector('[name="ribbon"]').value = data.ribbon || '';
      elements.form.querySelector('[name="tags"]').value = data.tags || '';
      elements.form.querySelector('[name="summary"]').value = data.summary || '';
      elements.form.querySelector('[name="description"]').value = data.description || '';
      elements.form.querySelector('[name="price"]').value = data.price || '';
      elements.form.querySelector('[name="discount_price"]').value = data.discount_price || '';
      elements.form.querySelector('[name="stock"]').value = data.stock || 0;
      elements.form.querySelector('[name="weight_kg"]').value = data.weight_kg || '';
      elements.form.querySelector('[name="allow_personalization"]').value = data.allow_personalization ? '1' : '0';

      state.categoryIds = new Set((data.categories_list || []).map((c) => c.id));
      state.subcategoryIds = new Set((data.subcategories_list || []).map((c) => c.id));
      state.variationIds = new Set(data.variation_value_ids || []);
      state.relatedIds = new Set(data.related_product_ids || []);
      state.images = (data.images_list || []).map((img, index) => ({
        image_url: img.image_url,
        is_primary: img.is_primary || index === 0
      }));

      renderCategorySelect();
      renderVariations();
      renderImages();
      renderRelated();
    }

    async function upsertCategory(formData, isSubcategory = false) {
      const token = getToken();
      if (!token) {
        alert('Įveskite Bearer žetoną produkto formoje.');
        return;
      }
      const payload = Object.fromEntries(formData.entries());
      const endpoint = isSubcategory
        ? `/categories/${payload.parent_category}/subcategories`
        : '/categories';
      await fetchJson(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
        body: JSON.stringify(
          isSubcategory
            ? { name: payload.subcategory_name, slug: payload.subcategory_slug }
            : { name: payload.category_name, slug: payload.category_slug }
        )
      });
      await loadCollections();
    }

    async function editCategory(id) {
      const target = state.categories.find((c) => c.id === Number(id));
      if (!target) return;
      const name = prompt('Naujas pavadinimas', target.name);
      const slug = prompt('Naujas slug', target.slug);
      if (!name || !slug) return;
      const token = getToken();
      await fetchJson(`/categories/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
        body: JSON.stringify({ name, slug })
      });
      await loadCollections();
    }

    async function deleteCategory(id) {
      const token = getToken();
      await fetchJson(`/categories/${id}`, {
        method: 'DELETE',
        headers: { Authorization: `Bearer ${token}` }
      });
      await loadCollections();
    }

    async function editSubcategory(catId, subId) {
      const cat = state.categories.find((c) => c.id === Number(catId));
      const target = cat?.subcategories?.find((s) => s.id === Number(subId));
      if (!target) return;
      const name = prompt('Naujas subkategorijos pavadinimas', target.name);
      const slug = prompt('Naujas slug', target.slug);
      if (!name || !slug) return;
      const token = getToken();
      await fetchJson(`/categories/${catId}/subcategories/${subId}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
        body: JSON.stringify({ name, slug })
      });
      await loadCollections();
    }

    async function deleteSubcategory(catId, subId) {
      const token = getToken();
      await fetchJson(`/categories/${catId}/subcategories/${subId}`, {
        method: 'DELETE',
        headers: { Authorization: `Bearer ${token}` }
      });
      await loadCollections();
    }

    async function uploadFiles(files) {
      if (!files.length) return;
      const token = getToken();
      if (!token) {
        alert('Įveskite Bearer žetoną, kad įkeltumėte nuotraukas.');
        return;
      }

      for (const file of files) {
        const reader = new FileReader();
        reader.onload = async (ev) => {
          try {
            const response = await fetchJson('/upload', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
              body: JSON.stringify({ fileName: file.name, dataUrl: ev.target.result })
            });
            state.images.push({ image_url: response.url, is_primary: !state.images.length });
            renderImages();
          } catch (error) {
            alert(`Nepavyko įkelti failo ${file.name}`);
          }
        };
        reader.readAsDataURL(file);
      }
    }

    function buildPayload(formData) {
      const payload = {
        title: formData.get('title') || '',
        slug: (formData.get('title') || '').toLowerCase().replace(/\s+/g, '-'),
        subtitle: formData.get('subtitle') || '',
        ribbon: formData.get('ribbon') || '',
        summary: formData.get('summary') || '',
        description: formData.get('description') || '',
        price: Number(formData.get('price')),
        discount_price: formData.get('discount_price') ? Number(formData.get('discount_price')) : null,
        stock: Number(formData.get('stock') || 0),
        tags: formData.get('tags') || '',
        weight_kg: formData.get('weight_kg') ? Number(formData.get('weight_kg')) : null,
        allow_personalization: formData.get('allow_personalization') === '1',
        categories: Array.from(state.categoryIds),
        subcategories: Array.from(state.subcategoryIds),
        variation_value_ids: Array.from(state.variationIds),
        related_product_ids: Array.from(state.relatedIds),
        images: state.images.map((img, index) => ({ image_url: img.image_url, is_primary: img.is_primary || index === 0 }))
      };

      updatePreview({
        ...payload,
        categories: Array.from(state.categoryIds).map((id) => state.categories.find((c) => c.id === id)?.name || ''),
        subcategories: Array.from(state.subcategoryIds).map(
          (id) => state.categories.flatMap((c) => c.subcategories || []).find((s) => s.id === id)?.name || ''
        ),
        images: payload.images
      });

      return payload;
    }

    elements.categoryManager.addEventListener('click', (event) => {
      const id = event.target.getAttribute('data-id');
      if (event.target.dataset.action === 'edit') {
        editCategory(id);
      }
      if (event.target.dataset.action === 'delete') {
        deleteCategory(id);
      }

      const subId = event.target.getAttribute('data-id');
      const catId = event.target.getAttribute('data-cat');
      if (event.target.dataset.subAction === 'edit') {
        editSubcategory(catId, subId);
      }
      if (event.target.dataset.subAction === 'delete') {
        deleteSubcategory(catId, subId);
      }
    });

    elements.categorySelect.addEventListener('change', (event) => {
      const { type, value } = event.target.dataset;
      if (!type || !value) return;
      const set = type === 'category' ? state.categoryIds : state.subcategoryIds;
      if (event.target.checked) {
        set.add(Number(value));
      } else {
        set.delete(Number(value));
      }
    });

    elements.variationList.addEventListener('change', (event) => {
      if (event.target.type !== 'checkbox') return;
      const value = Number(event.target.value);
      if (event.target.checked) {
        state.variationIds.add(value);
      } else {
        state.variationIds.delete(value);
      }
    });

    document.getElementById('add-variation-value').addEventListener('click', async () => {
      const value = document.getElementById('new-variation-value').value.trim();
      const attributeId = Number(elements.variationSelect.value);
      if (!value || !attributeId) return;
      const token = getToken();
      await fetchJson(`/variations/attributes/${attributeId}/values`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
        body: JSON.stringify({ value })
      });
      document.getElementById('new-variation-value').value = '';
      await loadCollections();
    });

    document.getElementById('related-search').addEventListener('input', (event) => {
      renderRelated(event.target.value);
    });

    elements.relatedResults.addEventListener('click', (event) => {
      const id = event.target.getAttribute('data-id');
      if (!id) return;
      state.relatedIds.add(Number(id));
      renderRelated(document.getElementById('related-search').value);
    });

    elements.relatedSelected.addEventListener('click', (event) => {
      const id = event.target.getAttribute('data-id');
      if (!id) return;
      state.relatedIds.delete(Number(id));
      renderRelated(document.getElementById('related-search').value);
    });

    document.getElementById('add-image').addEventListener('click', () => {
      const url = document.getElementById('image-url').value.trim();
      if (!url) return;
      const isPrimary = document.getElementById('image-primary').checked;
      if (isPrimary) {
        state.images.forEach((img) => (img.is_primary = false));
      }
      state.images.push({ image_url: url, is_primary: isPrimary });
      document.getElementById('image-url').value = '';
      document.getElementById('image-primary').checked = false;
      renderImages();
    });

    elements.imageList.addEventListener('click', (event) => {
      const index = event.target.getAttribute('data-index');
      if (index === null) return;
      if (event.target.classList.contains('remove')) {
        state.images.splice(Number(index), 1);
      }
      if (event.target.classList.contains('set-primary')) {
        state.images.forEach((img, idx) => {
          img.is_primary = idx === Number(index);
        });
      }
      renderImages();
    });

    document.getElementById('image-upload').addEventListener('change', (event) => {
      uploadFiles(Array.from(event.target.files || []));
      event.target.value = '';
    });

    document.getElementById('category-form').addEventListener('submit', async (event) => {
      event.preventDefault();
      await upsertCategory(new FormData(event.target));
      event.target.reset();
    });

    document.getElementById('subcategory-form').addEventListener('submit', async (event) => {
      event.preventDefault();
      await upsertCategory(new FormData(event.target), true);
      event.target.reset();
    });

    elements.form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const formData = new FormData(event.target);
      const payload = buildPayload(formData);

      const token = getToken();
      if (!token) return;

      try {
        const endpoint = editingProductId ? `/products/${editingProductId}` : '/products';
        const method = editingProductId ? 'PUT' : 'POST';
        const response = await fetchJson(endpoint, {
          method,
          headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${token}`
          },
          body: JSON.stringify(payload)
        });

        const note = document.createElement('div');
        note.className = 'alert alert--success';
        note.textContent = editingProductId
          ? 'Produktas atnaujintas'
          : `Sukurta. Naujo produkto ID: ${response.id}`;
        elements.preview.prepend(note);
        await loadCollections();
      } catch (error) {
        const note = document.createElement('div');
        note.className = 'alert alert--error';
        note.textContent = 'Nepavyko išsaugoti produkto';
        elements.preview.prepend(note);
      }
    });

    document.getElementById('refresh-data').addEventListener('click', loadCollections);
    document.getElementById('open-new-product').addEventListener('click', () => {
      window.open('administravimas.php#product-editor', '_blank');
    });

    renderTabs();
    setupCollapsibles();
    renderImages();
    loadCollections().then(() => {
      if (editingProductId) {
        loadProduct(editingProductId);
      }
    });
  </script>

  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
