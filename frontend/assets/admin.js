document.addEventListener('DOMContentLoaded', () => {
    const config = window.ADMIN_CONFIG || {};
    const apiBaseUrl = "api/";
    
    // Būsena
    const state = {
        products: [],
        categories: [],
        variations: [],
        images: [],
        categoryIds: new Set(),
        subcategoryIds: new Set(),
        variationIds: new Set(),
        relatedIds: new Set(),
        editingId: null,
        orders: []
    };

    const elements = {
        tabs: document.querySelectorAll('.tab'),
        panels: document.querySelectorAll('.tab-panel'),
        productTable: document.getElementById('product-table'),
        categoryManager: document.getElementById('category-list'),
        categorySelect: document.getElementById('category-select'),
        subcategoryParent: document.getElementById('subcategory-parent'),
        variationLibrary: document.getElementById('variation-library'),
        variationPicker: document.getElementById('variation-picker'),
        variationSelect: document.getElementById('new-variation-attribute'),
        imageList: document.getElementById('image-list'),
        relatedResults: document.getElementById('related-results'),
        relatedSelected: document.getElementById('related-selected'),
        messages: document.getElementById('form-messages'),
        form: document.getElementById('product-form'),
        formTitle: document.getElementById('form-title'),
        adminNav: document.querySelectorAll('.admin-nav__item'),
        adminSections: document.querySelectorAll('[data-admin-section]'),
        cancelEditBtn: document.getElementById('cancel-edit'),
        openNewProductBtn: document.getElementById('open-new-product'),
        ordersTable: document.getElementById('orders-table'),
        refreshOrdersBtn: document.getElementById('refresh-orders')
    };

    function formatCurrency(value) {
        const num = Number(value || 0);
        return `${num.toFixed(2)} €`;
    }

    function buildApiUrl(path) {
        const [rawPath, query] = `${path}`.split('?');
        const segments = rawPath.replace(/^\/+/g, '').split('/').filter(Boolean);
        const [resource, ...rest] = segments;
        if (!resource) return apiBaseUrl;
        let url = `${apiBaseUrl}${resource}.php`;
        if (rest.length) url += `/${rest.join('/')}`;
        if (typeof query === 'string') url += `?${query}`;
        return url;
    }

    async function fetchJson(path, options = {}) {
        const headers = { 'X-Admin-Role': config.userRole, ...(options.headers || {}) };
        const response = await fetch(buildApiUrl(path), { credentials: 'include', ...options, headers });
        let payload;
        try {
            const text = await response.text();
            payload = text ? JSON.parse(text) : null;
        } catch (err) { payload = null; }
        if (!response.ok) {
            const detail = payload?.detail ? `: ${payload.detail}` : '';
            throw new Error(`${payload?.message || response.status}${detail}`);
        }
        return payload;
    }

    function pushMessage(text, tone = 'info') {
        if (!elements.messages) return;
        const note = document.createElement('div');
        note.className = `alert alert--${tone}`;
        note.textContent = text;
        elements.messages.prepend(note);
        setTimeout(() => note.remove(), 5000);
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
            toggle.addEventListener('click', () => section.classList.toggle('is-open') && sync());
        });
    }

    function renderTabs() {
        elements.tabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                const target = tab.dataset.tab;
                elements.tabs.forEach((btn) => btn.classList.toggle('is-active', btn === tab));
                elements.panels.forEach((panel) => panel.classList.toggle('is-active', panel.id === `tab-${target}`));
                
                if (target === 'orders') {
                    loadOrders();
                }
            });
        });
    }

    function showSection(targetId) {
        elements.adminNav.forEach((item) => item.classList.toggle('is-active', item.dataset.target === targetId));
        elements.adminSections.forEach((card) => card.classList.toggle('is-active', card.id === targetId));
        if (targetId === 'catalog-summary') resetForm();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function setupAdminNavigation() {
        elements.adminNav.forEach((item) => item.addEventListener('click', () => showSection(item.dataset.target)));
        config.initialProductId ? startEditProduct(config.initialProductId) : showSection('catalog-summary');
    }
    
    function resetForm() {
        state.editingId = null;
        elements.form.reset();
        elements.formTitle.textContent = 'Pridėti naują produktą';
        elements.messages.innerHTML = '';
        state.images = [];
        state.categoryIds.clear();
        state.subcategoryIds.clear();
        state.variationIds.clear();
        state.relatedIds.clear();
        renderCategorySelect();
        renderVariations();
        renderImages();
        renderRelated();
    }

    // --- UŽSAKYMAI ---
    async function loadOrders() {
        if (!elements.ordersTable) return;
        try {
            const orders = await fetchJson('/orders');
            state.orders = orders;
            renderOrders();
        } catch (e) {
            console.error(e);
        }
    }

    // Funkcija statuso atnaujinimui (prieinama globaliai, nes kviečiama iš onchange)
    window.updateOrderStatus = async function(selectElement, orderId) {
        const newStatus = selectElement.value;
        const originalStatus = selectElement.getAttribute('data-original');

        if (!confirm(`Ar tikrai norite pakeisti užsakymo #${orderId} būseną į "${newStatus}"?`)) {
            selectElement.value = originalStatus; // Atstatome, jei atšaukė
            return;
        }

        try {
            await fetchJson(`/orders/${orderId}`, {
                method: 'PUT',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ status: newStatus })
            });
            
            selectElement.setAttribute('data-original', newStatus);
            pushMessage(`Užsakymas #${orderId} atnaujintas`, 'success');
            loadOrders(); // Perkrauname duomenis
        } catch (e) {
            alert('Nepavyko atnaujinti: ' + e.message);
            selectElement.value = originalStatus;
        }
    };

    function renderOrders() {
        elements.ordersTable.querySelectorAll('.table__row:not(.table__row--head)').forEach(r => r.remove());
        
        if (!state.orders.length) {
            elements.ordersTable.insertAdjacentHTML('beforeend', '<div class="table__row"><span class="muted">Nėra užsakymų</span></div>');
            return;
        }

        const statusMap = { 'new': 'Naujas', 'paid': 'Apmokėtas', 'shipped': 'Išsiųstas', 'completed': 'Įvykdytas', 'cancelled': 'Atšauktas' };

        state.orders.forEach(order => {
            const date = new Date(order.created_at).toLocaleString('lt-LT');
            const itemsSummary = (order.items || []).map(i => `${i.product_name} (${i.quantity} vnt.)`).join(', ');

            // Generuojame select'ą
            let statusOptions = '';
            for (const [key, label] of Object.entries(statusMap)) {
                const selected = order.status === key ? 'selected' : '';
                statusOptions += `<option value="${key}" ${selected}>${label}</option>`;
            }

            const row = document.createElement('div');
            row.className = 'table__row';
            row.title = itemsSummary;
            row.innerHTML = `
                <div>
                    <strong class="text-link">#${order.id}</strong>
                    <div class="muted" style="font-size:12px">${date}</div>
                </div>
                <div>
                    <div>${order.contact_name || 'Svečias'}</div>
                    <div class="muted" style="font-size:12px">${order.contact_email}</div>
                </div>
                <div class="strong">${formatCurrency(order.total_price)}</div>
                <div>
                    <select onchange="updateOrderStatus(this, ${order.id})" data-original="${order.status}" style="padding: 4px; border-radius: 6px; border: 1px solid #ccc;">
                        ${statusOptions}
                    </select>
                </div>
            `;
            elements.ordersTable.appendChild(row);
        });
    }

    // --- PRODUKTAI ---
    window.startEditProduct = async function(id) {
        try {
            await loadProduct(id);
            state.editingId = id;
            showSection('product-editor');
            elements.formTitle.textContent = `Redaguojama prekė #${id}`;
        } catch (e) {
            alert('Klaida: ' + e.message);
        }
    };

    async function loadProduct(productId) {
        if (!productId) return;
        const data = await fetchJson(`/products/${productId}`);
        const f = elements.form;
        f.querySelector('[name="title"]').value = data.title || '';
        f.querySelector('[name="subtitle"]').value = data.subtitle || '';
        f.querySelector('[name="ribbon"]').value = data.ribbon || '';
        f.querySelector('[name="tags"]').value = data.tags || '';
        f.querySelector('[name="summary"]').value = data.summary || '';
        f.querySelector('[name="description"]').value = data.description || '';
        f.querySelector('[name="price"]').value = data.price || '';
        f.querySelector('[name="discount_price"]').value = data.discount_price || '';
        f.querySelector('[name="stock"]').value = data.stock || 0;
        f.querySelector('[name="weight_kg"]').value = data.weight_kg || '';
        f.querySelector('[name="allow_personalization"]').value = data.allow_personalization ? '1' : '0';

        state.categoryIds = new Set((data.categories_list || []).map(c => Number(c.id)));
        state.subcategoryIds = new Set((data.subcategories_list || []).map(c => Number(c.id)));
        state.variationIds = new Set((data.variation_value_ids || []).map(Number));
        state.relatedIds = new Set((data.related_product_ids || []).map(Number));
        state.images = (data.images_list || []).map((img, index) => ({
            image_url: img.image_url,
            is_primary: img.is_primary || index === 0
        }));

        renderCategorySelect();
        renderVariations();
        renderImages();
        renderRelated();
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
            const mainImage = (product.images_list || []).find(img => img.is_primary) || (product.images_list || [])[0];
            const imgHtml = mainImage 
                ? `<img src="${mainImage.image_url}" class="table-thumb" alt="" />`
                : `<div class="table-thumb" style="background: #e2e8f0;"></div>`;

            row.innerHTML = `
              <div class="table-row-content">
                ${imgHtml}
                <span class="strong">${product.title}</span>
              </div>
              <span>${categories || 'Nepaskirta'}</span>
              <span>${formatCurrency(product.discount_price || product.price)}</span>
              <span class="actions">
                <button class="btn btn--ghost" type="button" onclick="startEditProduct(${product.id})">Redaguoti</button>
              </span>
            `;
            elements.productTable.appendChild(row);
        });
    }

    function renderCategoryManager() {
        elements.categoryManager.innerHTML = '';
        if (!state.categories.length) {
            elements.categoryManager.innerHTML = '<p class="muted">Dar nėra kategorijų.</p>';
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
            const isChecked = state.categoryIds.has(Number(cat.id));
            catLabel.innerHTML = `
              <input type="checkbox" data-type="category" value="${cat.id}" ${isChecked ? 'checked' : ''}/>
              <span>Priskirti kategoriją</span>
            `;
            wrapper.appendChild(catLabel);

            const scList = document.createElement('div');
            scList.className = 'stack';
            (cat.subcategories || []).forEach((sub) => {
                const scLabel = document.createElement('label');
                scLabel.className = 'checkbox';
                const isSubChecked = state.subcategoryIds.has(Number(sub.id));
                scLabel.innerHTML = `
                  <input type="checkbox" data-type="subcategory" value="${sub.id}" ${isSubChecked ? 'checked' : ''}/>
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
        elements.variationLibrary.innerHTML = '';
        elements.variationPicker.innerHTML = '';
        elements.variationSelect.innerHTML = '';
        state.variations.forEach((attr) => {
            const option = document.createElement('option');
            option.value = attr.id;
            option.textContent = attr.name;
            elements.variationSelect.appendChild(option);

            const libraryCard = document.createElement('div');
            libraryCard.className = 'card card--ghost';
            libraryCard.innerHTML = `<p class="card__eyebrow">${attr.name}</p>`;
            const libraryList = document.createElement('div');
            libraryList.className = 'pill-row';
            attr.values.forEach((val) => {
                const pill = document.createElement('span');
                pill.className = 'pill';
                pill.textContent = val.value;
                libraryList.appendChild(pill);
            });
            libraryCard.appendChild(libraryList);
            elements.variationLibrary.appendChild(libraryCard);

            const pickerCard = document.createElement('div');
            pickerCard.className = 'card card--ghost';
            pickerCard.innerHTML = `<p class="card__eyebrow">${attr.name}</p>`;
            const pickerList = document.createElement('div');
            pickerList.className = 'chip-list';
            attr.values.forEach((val) => {
                const chip = document.createElement('label');
                chip.className = 'chip chip--interactive';
                const isChecked = state.variationIds.has(Number(val.id));
                chip.innerHTML = `
                  <input type="checkbox" value="${val.id}" ${isChecked ? 'checked' : ''}/>
                  <span>${val.value}</span>
                `;
                pickerList.appendChild(chip);
            });
            pickerCard.appendChild(pickerList);
            elements.variationPicker.appendChild(pickerCard);
        });
    }

    function renderRelated(searchTerm = '') {
        const normalized = searchTerm.toLowerCase();
        elements.relatedResults.innerHTML = '';
        state.products
            .filter((item) => item.title.toLowerCase().includes(normalized) && item.id !== Number(state.editingId))
            .forEach((item) => {
                const row = document.createElement('div');
                row.className = 'list__item';
                row.innerHTML = `<span>${item.title}</span><button class="btn btn--ghost" type="button" data-id="${item.id}">Pridėti</button>`;
                elements.relatedResults.appendChild(row);
            });

        elements.relatedSelected.innerHTML = '';
        Array.from(state.relatedIds).forEach((id) => {
            const product = state.products.find((item) => item.id === Number(id));
            if (!product) return;
            const chip = document.createElement('div');
            chip.className = 'chip chip--interactive';
            chip.innerHTML = `<span>${product.title}</span><button class="link text-danger" type="button" data-id="${id}">Šalinti</button>`;
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

    async function loadCollections() {
        try {
            const [products, categories, variations] = await Promise.all([
                fetchJson('/products'),
                fetchJson('/categories'),
                fetchJson('/variations')
            ]);
            state.products = products.map(p => ({...p, id: Number(p.id)}));
            state.categories = categories.reduce((acc, row) => {
                const id = Number(row.id);
                let entry = acc.find((item) => item.id === id);
                if (!entry) {
                    entry = { id, name: row.name, slug: row.slug, subcategories: [] };
                    acc.push(entry);
                }
                if (row.subcategory_id) {
                    entry.subcategories.push({ id: Number(row.subcategory_id), name: row.subcategory_name, slug: row.subcategory_slug });
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
            pushMessage(`Nepavyko įkelti duomenų: ${error.message}`, 'error');
        }
    }

    async function upsertCategory(formData, isSubcategory = false) {
        const payload = Object.fromEntries(formData.entries());
        const endpoint = isSubcategory
            ? `/categories/${payload.parent_category}/subcategories`
            : '/categories';
        try {
            await fetchJson(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(
                    isSubcategory
                        ? { name: payload.subcategory_name, slug: payload.subcategory_slug }
                        : { name: payload.category_name, slug: payload.category_slug }
                )
            });
            await loadCollections();
            pushMessage('Išsaugota', 'success');
        } catch (error) { pushMessage(error.message, 'error'); }
    }

    async function editCategory(id) {
        const target = state.categories.find((c) => c.id === Number(id));
        if (!target) return;
        const name = prompt('Naujas pavadinimas', target.name);
        const slug = prompt('Naujas slug', target.slug);
        if (!name) return;
        await fetchJson(`/categories/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, slug })
        });
        await loadCollections();
    }

    async function deleteCategory(id) {
        if(!confirm('Ar tikrai norite trinti?')) return;
        await fetchJson(`/categories/${id}`, { method: 'DELETE' });
        await loadCollections();
    }
    
    async function uploadFiles(files) {
        if (!files.length) return;
        for (const file of files) {
            const reader = new FileReader();
            reader.onload = async (ev) => {
                try {
                    const response = await fetchJson('/upload', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ fileName: file.name, dataUrl: ev.target.result })
                    });
                    state.images.push({ image_url: response.url, is_primary: !state.images.length });
                    renderImages();
                } catch (error) { alert(error.message); }
            };
            reader.readAsDataURL(file);
        }
    }

    if(elements.openNewProductBtn) elements.openNewProductBtn.addEventListener('click', () => { resetForm(); showSection('product-editor'); });
    if(elements.cancelEditBtn) elements.cancelEditBtn.addEventListener('click', () => { if(confirm('Atšaukti?')) showSection('catalog-summary'); });
    if(elements.refreshOrdersBtn) elements.refreshOrdersBtn.addEventListener('click', loadOrders);

    document.getElementById('category-form').addEventListener('submit', async (e) => { e.preventDefault(); await upsertCategory(new FormData(e.target)); e.target.reset(); });
    document.getElementById('subcategory-form').addEventListener('submit', async (e) => { e.preventDefault(); await upsertCategory(new FormData(e.target), true); e.target.reset(); });
    document.getElementById('variation-attribute-form').addEventListener('submit', async (e) => { e.preventDefault(); 
        const name = document.getElementById('variation-attribute-name').value.trim();
        if (!name) return;
        await fetchJson('/variations/attributes', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({name}) });
        e.target.reset(); await loadCollections();
    });
    
    document.getElementById('add-variation-value').addEventListener('click', async () => {
         const value = document.getElementById('new-variation-value').value.trim();
         const attributeId = Number(elements.variationSelect.value);
         if (!value || !attributeId) return;
         await fetchJson(`/variations/attributes/${attributeId}/values`, { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({value}) });
         document.getElementById('new-variation-value').value = ''; await loadCollections();
    });

    elements.form.addEventListener('submit', async (event) => {
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
            categories: Array.from(state.categoryIds),
            subcategories: Array.from(state.subcategoryIds),
            variation_value_ids: Array.from(state.variationIds),
            related_product_ids: Array.from(state.relatedIds),
            images: state.images.map((img, index) => ({ image_url: img.image_url, is_primary: img.is_primary || index === 0 }))
        };

        try {
            const endpoint = state.editingId ? `/products/${state.editingId}` : '/products';
            const method = state.editingId ? 'PUT' : 'POST';
            await fetchJson(endpoint, { method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
            pushMessage('Išsaugota', 'success');
            await loadCollections();
            showSection('catalog-summary');
        } catch (error) { pushMessage(error.message, 'error'); }
    });
    
    elements.categorySelect.addEventListener('change', (e) => {
        const { type } = e.target.dataset;
        const value = Number(e.target.value);
        if (!type) return;
        const set = type === 'category' ? state.categoryIds : state.subcategoryIds;
        e.target.checked ? set.add(value) : set.delete(value);
    });

    elements.variationPicker.addEventListener('change', (e) => {
        if(e.target.type !== 'checkbox') return;
        const val = Number(e.target.value);
        e.target.checked ? state.variationIds.add(val) : state.variationIds.delete(val);
    });

    elements.categoryManager.addEventListener('click', (e) => {
       const btn = e.target.closest('button');
       if(!btn) return;
       const { action, id } = btn.dataset;
       if (action === 'edit') editCategory(id);
       if (action === 'delete') deleteCategory(id);
    });
    
    document.getElementById('image-upload').addEventListener('change', (e) => { uploadFiles(Array.from(e.target.files || [])); e.target.value = ''; });
    document.getElementById('add-image').addEventListener('click', () => {
        const url = document.getElementById('image-url').value.trim();
        if(!url) return;
        const isPrimary = document.getElementById('image-primary').checked;
        if(isPrimary) state.images.forEach(i => i.is_primary = false);
        state.images.push({ image_url: url, is_primary: isPrimary });
        renderImages();
        document.getElementById('image-url').value = '';
    });
    elements.imageList.addEventListener('click', (e) => {
        if(e.target.classList.contains('remove')) state.images.splice(Number(e.target.dataset.index), 1);
        if(e.target.classList.contains('set-primary')) state.images.forEach((img, i) => img.is_primary = i === Number(e.target.dataset.index));
        renderImages();
    });
    
    const searchInput = document.getElementById('related-search');
    if(searchInput) searchInput.addEventListener('input', (e) => renderRelated(e.target.value));
    elements.relatedResults.addEventListener('click', (e) => { const btn = e.target.closest('button'); if(btn) { state.relatedIds.add(Number(btn.dataset.id)); renderRelated(searchInput.value); } });
    elements.relatedSelected.addEventListener('click', (e) => { const btn = e.target.closest('button'); if(btn) { state.relatedIds.delete(Number(btn.dataset.id)); renderRelated(searchInput.value); } });

    renderTabs();
    setupCollapsibles();
    setupAdminNavigation();
    loadCollections();
});
