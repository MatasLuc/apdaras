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
      <div class="section__inner">
        <div class="section__header">
          <p class="badge">Produktai</p>
          <h3>Pridėti naują produktą</h3>
          <p class="muted">Sukurkite kortelę su pilnu atributų rinkiniu: nuo nuotraukų iki variacijų ir susijusių prekių.</p>
        </div>

        <div class="grid grid--two">
          <form id="product-form" class="card card--surface">
            <div class="card__header">
              <div>
                <p class="card__eyebrow">Pagrindinė informacija</p>
                <h4>Produkto detalės</h4>
              </div>
              <button class="btn btn--primary" type="submit">Išsaugoti produktą</button>
            </div>

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
                <input type="text" name="token" placeholder="Įveskite jei siunčiate į API" />
              </label>
            </div>

            <div class="divider"></div>

            <div class="form-section">
              <div class="form-section__header">
                <div>
                  <p class="card__eyebrow">Nuotraukos</p>
                  <h4>Pagrindinė ir papildomos</h4>
                </div>
                <div class="actions">
                  <input type="url" id="image-url" placeholder="https://..." />
                  <label class="checkbox">
                    <input type="checkbox" id="image-primary" />
                    <span>Pagrindinė</span>
                  </label>
                  <button class="btn" type="button" id="add-image">Pridėti</button>
                </div>
              </div>
              <div id="image-list" class="chip-list" aria-live="polite"></div>
            </div>

            <div class="form-section">
              <div class="form-section__header">
                <div>
                  <p class="card__eyebrow">Kategorijos</p>
                  <h4>Pagrindinės ir subkategorijos</h4>
                </div>
              </div>
              <div id="category-list" class="grid grid--two"></div>
            </div>

            <div class="form-section">
              <div class="form-section__header">
                <div>
                  <p class="card__eyebrow">Variacijos</p>
                  <h4>Spalvos, dydžiai ir kiti pasirinkimai</h4>
                </div>
                <div class="actions">
                  <input type="text" id="new-variation-value" placeholder="Nauja variacijos reikšmė" />
                  <select id="new-variation-attribute"></select>
                  <button class="btn" type="button" id="add-variation-value">Pridėti reikšmę</button>
                </div>
              </div>
              <div id="variation-list" class="grid grid--two"></div>
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
          </form>

          <aside class="card card--surface">
            <div class="card__header">
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
  </main>

  <script>
    const categories = [
      { id: 1, name: 'Marškinėliai', subcategories: [{ id: 11, name: 'Oversize' }, { id: 12, name: 'Klasikiniai' }] },
      { id: 2, name: 'Džemperiai', subcategories: [{ id: 21, name: 'Su gobtuvu' }, { id: 22, name: 'Be gobtuvo' }] },
      { id: 3, name: 'Aksesuarai', subcategories: [{ id: 31, name: 'Kepurės' }, { id: 32, name: 'Rankinės' }] }
    ];

    const variationAttributes = [
      {
        id: 1,
        name: 'Spalva',
        values: [
          { id: 101, value: 'Juoda' },
          { id: 102, value: 'Balta' },
          { id: 103, value: 'Žalia' }
        ]
      },
      {
        id: 2,
        name: 'Dydis',
        values: [
          { id: 201, value: 'XS' },
          { id: 202, value: 'S' },
          { id: 203, value: 'M' },
          { id: 204, value: 'L' },
          { id: 205, value: 'XL' }
        ]
      }
    ];

    const relatedCatalog = [
      { id: 201, name: 'Urban marškinėliai' },
      { id: 202, name: 'Core džemperis' },
      { id: 203, name: 'Minimalistinė kepuraitė' },
      { id: 204, name: 'City kuprinė' },
      { id: 205, name: 'Signal džemperis' }
    ];

    const imageState = [];
    const categoryState = new Set();
    const subcategoryState = new Set();
    const variationState = new Set();
    const relatedState = new Set();

    const imageListEl = document.getElementById('image-list');
    const categoryListEl = document.getElementById('category-list');
    const variationListEl = document.getElementById('variation-list');
    const variationSelectEl = document.getElementById('new-variation-attribute');
    const relatedResultsEl = document.getElementById('related-results');
    const relatedSelectedEl = document.getElementById('related-selected');
    const previewEl = document.getElementById('product-preview');

    function renderImages() {
      imageListEl.innerHTML = '';
      imageState.forEach((img, idx) => {
        const chip = document.createElement('div');
        chip.className = 'chip chip--interactive';
        chip.innerHTML = `
          <span>${img.is_primary ? '⭐️' : '🖼️'} ${img.image_url}</span>
          <div class="chip__actions">
            <button type="button" data-index="${idx}" class="link set-primary">Pagrindinė</button>
            <button type="button" data-index="${idx}" class="link text-danger remove">Šalinti</button>
          </div>
        `;
        imageListEl.appendChild(chip);
      });
    }

    function renderCategories() {
      categoryListEl.innerHTML = '';
      categories.forEach((cat) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'card card--ghost';
        wrapper.innerHTML = `<p class="card__eyebrow">${cat.name}</p>`;

        const catLabel = document.createElement('label');
        catLabel.className = 'checkbox';
        catLabel.innerHTML = `
          <input type="checkbox" data-type="category" value="${cat.id}" />
          <span>Priskirti kategoriją</span>
        `;
        wrapper.appendChild(catLabel);

        const scList = document.createElement('div');
        scList.className = 'stack';
        cat.subcategories.forEach((sub) => {
          const scLabel = document.createElement('label');
          scLabel.className = 'checkbox';
          scLabel.innerHTML = `
            <input type="checkbox" data-type="subcategory" value="${sub.id}" />
            <span>${sub.name}</span>
          `;
          scList.appendChild(scLabel);
        });
        wrapper.appendChild(scList);
        categoryListEl.appendChild(wrapper);
      });
    }

    function renderVariationSelect() {
      variationSelectEl.innerHTML = '';
      variationAttributes.forEach((attr) => {
        const option = document.createElement('option');
        option.value = attr.id;
        option.textContent = attr.name;
        variationSelectEl.appendChild(option);
      });
    }

    function renderVariations() {
      variationListEl.innerHTML = '';
      variationAttributes.forEach((attr) => {
        const card = document.createElement('div');
        card.className = 'card card--ghost';
        card.innerHTML = `<p class="card__eyebrow">${attr.name}</p>`;
        const list = document.createElement('div');
        list.className = 'chip-list';
        attr.values.forEach((val) => {
          const key = val.id;
          const chip = document.createElement('label');
          chip.className = 'chip chip--interactive';
          chip.innerHTML = `
            <input type="checkbox" value="${key}" ${variationState.has(key) ? 'checked' : ''} />
            <span>${val.value}</span>
          `;
          list.appendChild(chip);
        });
        card.appendChild(list);
        variationListEl.appendChild(card);
      });
    }

    function renderRelated(searchTerm = '') {
      relatedResultsEl.innerHTML = '';
      const normalized = searchTerm.toLowerCase();
      relatedCatalog
        .filter((item) => item.name.toLowerCase().includes(normalized))
        .forEach((item) => {
          const row = document.createElement('div');
          row.className = 'list__item';
          row.innerHTML = `
            <span>${item.name}</span>
            <button class="btn btn--ghost" type="button" data-id="${item.id}">Pridėti</button>
          `;
          relatedResultsEl.appendChild(row);
        });

      relatedSelectedEl.innerHTML = '';
      Array.from(relatedState).forEach((id) => {
        const product = relatedCatalog.find((item) => item.id === Number(id));
        if (!product) return;
        const chip = document.createElement('div');
        chip.className = 'chip chip--interactive';
        chip.innerHTML = `
          <span>${product.name}</span>
          <button class="link text-danger" type="button" data-id="${id}">Šalinti</button>
        `;
        relatedSelectedEl.appendChild(chip);
      });
    }

    function updatePreview(payload) {
      previewEl.innerHTML = '';
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

      previewEl.append(meta, lists, json);
    }

    document.getElementById('add-image').addEventListener('click', () => {
      const url = document.getElementById('image-url').value.trim();
      if (!url) return;
      const isPrimary = document.getElementById('image-primary').checked;
      if (isPrimary) {
        imageState.forEach((img) => (img.is_primary = false));
      }
      imageState.push({ image_url: url, is_primary: isPrimary });
      document.getElementById('image-url').value = '';
      document.getElementById('image-primary').checked = false;
      renderImages();
    });

    imageListEl.addEventListener('click', (event) => {
      const index = event.target.getAttribute('data-index');
      if (index === null) return;
      if (event.target.classList.contains('remove')) {
        imageState.splice(Number(index), 1);
      }
      if (event.target.classList.contains('set-primary')) {
        imageState.forEach((img, idx) => {
          img.is_primary = idx === Number(index);
        });
      }
      renderImages();
    });

    categoryListEl.addEventListener('change', (event) => {
      const { type, value } = event.target.dataset;
      if (!type) return;
      const targetSet = type === 'category' ? categoryState : subcategoryState;
      if (event.target.checked) {
        targetSet.add(Number(value));
      } else {
        targetSet.delete(Number(value));
      }
    });

    function syncVariationCheckboxes() {
      variationListEl.querySelectorAll('input[type="checkbox"]').forEach((input) => {
        input.checked = variationState.has(Number(input.value));
      });
    }

    variationListEl.addEventListener('change', (event) => {
      if (event.target.type !== 'checkbox') return;
      const value = Number(event.target.value);
      if (event.target.checked) {
        variationState.add(value);
      } else {
        variationState.delete(value);
      }
    });

    document.getElementById('add-variation-value').addEventListener('click', () => {
      const value = document.getElementById('new-variation-value').value.trim();
      const attributeId = Number(variationSelectEl.value);
      if (!value) return;
      const attribute = variationAttributes.find((attr) => attr.id === attributeId);
      const nextId = Date.now();
      if (!attribute.values.some((item) => item.value === value)) {
        attribute.values.push({ id: nextId, value });
      }
      document.getElementById('new-variation-value').value = '';
      renderVariations();
      syncVariationCheckboxes();
    });

    document.getElementById('related-search').addEventListener('input', (event) => {
      renderRelated(event.target.value);
    });

    relatedResultsEl.addEventListener('click', (event) => {
      const id = event.target.getAttribute('data-id');
      if (!id) return;
      relatedState.add(Number(id));
      renderRelated(document.getElementById('related-search').value);
    });

    relatedSelectedEl.addEventListener('click', (event) => {
      const id = event.target.getAttribute('data-id');
      if (!id) return;
      relatedState.delete(Number(id));
      renderRelated(document.getElementById('related-search').value);
    });

    document.getElementById('product-form').addEventListener('submit', async (event) => {
      event.preventDefault();
      const formData = new FormData(event.target);
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
        categories: Array.from(categoryState),
        subcategories: Array.from(subcategoryState),
        variation_value_ids: Array.from(variationState),
        related_product_ids: Array.from(relatedState),
        images: imageState.map((img, index) => ({ image_url: img.image_url, is_primary: img.is_primary || index === 0 }))
      };

      updatePreview(payload);

      const token = formData.get('token');
      if (!token) return;

      try {
        const response = await fetch('http://localhost:4000/products', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${token}`
          },
          body: JSON.stringify(payload)
        });

        const result = await response.json();
        const note = document.createElement('div');
        note.className = `alert ${response.ok ? 'alert--success' : 'alert--error'}`;
        note.textContent = response.ok
          ? `Sukurta. Naujo produkto ID: ${result.id}`
          : result.message || 'Nepavyko sukurti produkto';
        previewEl.prepend(note);
      } catch (error) {
        const note = document.createElement('div');
        note.className = 'alert alert--error';
        note.textContent = 'API užklausos klaida. Patikrinkite serverį arba žetoną.';
        previewEl.prepend(note);
      }
    });

    renderImages();
    renderCategories();
    renderVariationSelect();
    renderVariations();
    syncVariationCheckboxes();
    renderRelated();
  </script>

  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
