// ... (viršus nesikeičia) ...

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

    // NAUJA: Funkcija statuso atnaujinimui
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
            
            // Atnaujiname originalią reikšmę, kad kitą kartą žinotume
            selectElement.setAttribute('data-original', newStatus);
            pushMessage(`Užsakymas #${orderId} atnaujintas`, 'success');
            
            // Perkrauname, kad atsinaujintų duomenys (pvz. jei ateityje bus spalvų pasikeitimai)
            loadOrders(); 
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

// ... (likusi dalis ta pati) ...
