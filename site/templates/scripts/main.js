// СКРИПТ ДОБАВЛЕНИЯ РАБОТ И ЗАПЧАСТЕЙ
document.addEventListener('DOMContentLoaded', function () {

    // =========================
    // РАБОТЫ
    // =========================

    const workTypeSelect = document.getElementById('work_type');
    const workSelect = document.getElementById('work_select');
    const workFilter = document.getElementById('work_filter');
    const addWorkBtn = document.getElementById('add_work');

    const worksCart = document.getElementById('works_cart');
    let worksEmpty = document.getElementById('works_empty');

    const allWorkOptions = Array.from(workSelect.options).filter(function (option) {
        return option.value !== '';
    });

    function updateWorkOptions() {
        const selectedType = String(workTypeSelect.value || '').trim();
        const query = workFilter.value.trim().toLowerCase();
        const currentValue = workSelect.value;

        workSelect.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.disabled = true;
        placeholder.selected = true;
        placeholder.textContent = 'Выберите работу';
        workSelect.appendChild(placeholder);

        const filtered = allWorkOptions.filter(function (option) {
            const optionText = option.textContent.toLowerCase();
            const optionType = String(option.dataset.workType || '').trim();

            const matchesText =
                query === '' || optionText.includes(query);

            const matchesType =
                selectedType === '' || optionType === selectedType;

            return matchesText && matchesType;
        });

        filtered.forEach(function (option) {
            workSelect.appendChild(option.cloneNode(true));
        });

        if (
            currentValue &&
            filtered.some(function (option) {
                return option.value === currentValue;
            })
        ) {
            workSelect.value = currentValue;
        } else {
            workSelect.selectedIndex = 0;
        }
    }

    workFilter.addEventListener('input', function () {
        updateWorkOptions();
    });

    workTypeSelect.addEventListener('change', function () {
        if (workFilter) {
            workFilter.value = '';
        }
        updateWorkOptions();
    });

    updateWorkOptions();


    // =========================
    // ЗАПЧАСТИ
    // =========================

    const partSelect = document.getElementById('part_select');
    const partFilter = document.getElementById('part_filter');
    const addPartBtn = document.getElementById('add_part');

    const partsCart = document.getElementById('parts_cart');
    let partsEmpty = document.getElementById('parts_empty');

    const allPartOptions = Array.from(partSelect.options);

    partFilter.addEventListener('input', function () {
        const query = this.value.trim().toLowerCase();
        const currentValue = partSelect.value;

        partSelect.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.disabled = true;
        placeholder.selected = true;
        placeholder.textContent = 'Выберите запчасть';
        partSelect.appendChild(placeholder);

        const filtered = allPartOptions.filter(function (option) {
            return option.value !== '' &&
                (
                    query === '' ||
                    option.textContent.toLowerCase().includes(query)
                );
        });

        filtered.forEach(function (option) {
            partSelect.appendChild(option.cloneNode(true));
        });

        if (
            currentValue &&
            filtered.some(function (option) {
                return option.value === currentValue;
            })
        ) {
            partSelect.value = currentValue;
        } else {
            partSelect.selectedIndex = 0;
        }
    });


    // =========================
    // ОБЩИЕ ДАННЫЕ
    // =========================

    const worksPriceInput = document.getElementById('works_price');
    const partsPriceInput = document.getElementById('parts_price');
    const totalPriceInput = document.getElementById('total_price');

    let selectedWorks = [];
    let selectedParts = [];
    let itemIdCounter = 1;


    // =========================
    // ПУСТЫЕ СОСТОЯНИЯ КОРЗИН
    // =========================

    if (!worksEmpty && worksCart) {
        worksEmpty = document.createElement('div');
        worksEmpty.id = 'works_empty';
        worksEmpty.className = 'order-cart-empty';
        worksEmpty.textContent = 'Работы пока не выбраны';
        worksEmpty.style.display = 'none';
    }

    if (!partsEmpty && partsCart) {
        partsEmpty = document.createElement('div');
        partsEmpty.id = 'parts_empty';
        partsEmpty.className = 'order-cart-empty';
        partsEmpty.textContent = 'Запчасти пока не выбраны';
        partsEmpty.style.display = 'none';
    }


    // =========================
    // ЗАГРУЗКА УЖЕ ДОБАВЛЕННЫХ РАБОТ
    // =========================

    function loadExistingWorksFromCart() {
        if (!worksCart) {
            return;
        }

        const existingItems = worksCart.querySelectorAll('.order-cart-item');

        existingItems.forEach(function (cartItem) {
            const nameBlock = cartItem.querySelector('strong');
            const priceInput = cartItem.querySelector('input[name="works_prices[]"]');

            if (!nameBlock || !priceInput) {
                return;
            }

            const itemName = nameBlock.textContent.trim();
            const itemPrice = Number(priceInput.value) || 0;

            // Кол-во из базы (если страница редактирования его отдаёт), иначе 1
            const qtyInput = cartItem.querySelector('input[name="works_qty[]"]');
            const itemQty  = qtyInput ? (Number(qtyInput.value) || 1) : 1;

            if (!itemName) {
                return;
            }

            selectedWorks.push({
                id: itemIdCounter++,
                name: itemName,
                price: itemPrice,
                basePrice: itemPrice,
                qty: itemQty,
                partId: 0
            });
        });
    }


    // =========================
    // ЗАГРУЗКА УЖЕ ДОБАВЛЕННЫХ ЗАПЧАСТЕЙ
    // =========================

    function loadExistingPartsFromCart() {
        if (!partsCart) {
            return;
        }

        const existingItems = partsCart.querySelectorAll('.order-cart-item');

        existingItems.forEach(function (cartItem) {
            const nameBlock = cartItem.querySelector('strong');
            const priceInput = cartItem.querySelector('input[name="parts_prices[]"]');
            const idInput = cartItem.querySelector('input[name="parts_ids[]"]');

            if (!nameBlock || !priceInput) {
                return;
            }

            const itemName = nameBlock.textContent.trim();
            const itemPrice = Number(priceInput.value) || 0;
            const itemPartId = idInput
                ? (Number(idInput.value) || 0)
                : 0;

            if (!itemName) {
                return;
            }

            // Кол-во из базы (если страница редактирования его отдаёт), иначе 1
            const qtyInput = cartItem.querySelector('input[name="parts_qty[]"]');
            const itemQty  = qtyInput ? (Number(qtyInput.value) || 1) : 1;

            selectedParts.push({
                id: itemIdCounter++,
                name: itemName,
                price: itemPrice,
                basePrice: itemPrice,
                qty: itemQty,
                partId: itemPartId
            });
        });
    }


    // =========================
    // ДОБАВЛЕНИЕ РАБОТЫ
    // =========================

    addWorkBtn.addEventListener('click', function () {
        const selectedOption = workSelect.options[workSelect.selectedIndex];

        if (!selectedOption || !selectedOption.value) {
            alert('Выберите работу');
            return;
        }

        addItem({
            select: workSelect,
            list: selectedWorks,
            type: 'work'
        });

        workSelect.selectedIndex = 0;
        workFilter.value = '';

        // Принудительно вызываем событие input для обновления автодополнения
        workFilter.dispatchEvent(new Event('input'));
    });


    // =========================
    // ДОБАВЛЕНИЕ ЗАПЧАСТИ
    // =========================

    addPartBtn.addEventListener('click', function () {
        addItem({
            select: partSelect,
            list: selectedParts,
            type: 'part'
        });
    });


    // =========================
    // ИНИЦИАЛИЗАЦИЯ
    // =========================

    loadExistingWorksFromCart();
    loadExistingPartsFromCart();
    renderCarts();
    updatePrices();


    // =========================
    // ДОБАВЛЕНИЕ ЭЛЕМЕНТА
    // =========================

    function addItem(options) {
        const select = options.select;
        const list = options.list;
        const type = options.type;

        const selectedOption = select.options[select.selectedIndex];

        if (!selectedOption || !selectedOption.value) {
            if (type === 'work') {
                alert('Выберите работу');
            } else {
                alert('Выберите запчасть');
            }

            return;
        }

        const itemName = selectedOption.value;
        const itemPrice = Number(selectedOption.dataset.price) || 0;
        const itemPartId = Number(selectedOption.dataset.id) || 0;

        const item = {
            id: itemIdCounter++,
            name: itemName,
            price: itemPrice,
            basePrice: itemPrice,
            qty: 1,
            partId: itemPartId
        };

        list.push(item);

        renderCarts();
        updatePrices();

        select.selectedIndex = 0;
    }


    // =========================
    // ОТРИСОВКА ОБЕИХ КОРЗИН
    // =========================

    function renderCarts() {
        renderCart({
            list: selectedWorks,
            cart: worksCart,
            emptyBlock: worksEmpty,
            type: 'work',
            hiddenName: 'works[]',
            hiddenPriceName: 'works_prices[]',
            hiddenQtyName: 'works_qty[]',
            hiddenIdName: '',
            removeClass: 'remove-work'
        });

        renderCart({
            list: selectedParts,
            cart: partsCart,
            emptyBlock: partsEmpty,
            type: 'part',
            hiddenName: 'parts[]',
            hiddenPriceName: 'parts_prices[]',
            hiddenQtyName: 'parts_qty[]',
            hiddenIdName: 'parts_ids[]',
            removeClass: 'remove-part'
        });
    }


    // =========================
    // ОТРИСОВКА ОДНОЙ КОРЗИНЫ
    // =========================

    function renderCart(options) {
        const list = options.list;
        const cart = options.cart;
        const emptyBlock = options.emptyBlock;
        const type = options.type;
        const hiddenName = options.hiddenName;
        const hiddenPriceName = options.hiddenPriceName;
        const hiddenQtyName = options.hiddenQtyName;
        const hiddenIdName = options.hiddenIdName;
        const removeClass = options.removeClass;

        if (!cart) {
            return;
        }

        cart.innerHTML = '';

        if (list.length === 0) {
            if (emptyBlock) {
                emptyBlock.style.display = 'block';
                cart.appendChild(emptyBlock);
            }

            return;
        }

        if (emptyBlock) {
            emptyBlock.style.display = 'none';
        }

        list.forEach(function (item) {
            const cartItem = document.createElement('div');

            cartItem.className =
                'order-cart-item uk-flex uk-flex-between uk-flex-middle';

            cartItem.dataset.id = item.id;

            cartItem.innerHTML = `
                <div class="order-cart-item-main">
                    <strong>${escapeHtml(item.name)}</strong>
                    <div class="order-cart-price">
                        <input
                            type="text"
                            class="uk-input uk-form-small cart-price-input${item.price !== item.basePrice ? ' cart-price-changed' : ''}"
                            data-id="${item.id}"
                            data-type="${type}"
                            data-base-price="${item.basePrice}"
                            value="${item.price}"
                            inputmode="numeric"
                            autocomplete="off"
                        >
                        <span class="cart-price-unit">₽</span>
                        <span class="cart-price-times">×</span>
                        <input
                            type="text"
                            class="uk-input uk-form-small cart-qty-input"
                            data-id="${item.id}"
                            data-type="${type}"
                            value="${item.qty || 1}"
                            inputmode="numeric"
                            autocomplete="off"
                        >
                        <span class="cart-row-sum-wrap">= <span class="cart-row-sum" data-id="${item.id}" data-type="${type}">${item.price * (item.qty || 1)}</span> ₽</span>
                    </div>
                </div>

                <button
                    type="button"
                    class="uk-button uk-button-danger uk-button-small ${removeClass}"
                    data-id="${item.id}"
                    data-type="${type}"
                >
                    ✕
                </button>

                <input
                    type="hidden"
                    name="${hiddenName}"
                    value="${escapeHtml(item.name)}"
                >

                <input
                    type="hidden"
                    name="${hiddenPriceName}"
                    value="${item.price}"
                >

                ${
                    hiddenQtyName
                        ? `<input
                            type="hidden"
                            name="${hiddenQtyName}"
                            value="${item.qty || 1}"
                        >`
                        : ''
                }

                ${
                    hiddenIdName
                        ? `<input
                            type="hidden"
                            name="${hiddenIdName}"
                            value="${item.partId || 0}"
                        >`
                        : ''
                }
            `;

            cart.appendChild(cartItem);
        });

        document.querySelectorAll('.' + removeClass).forEach(function (button) {
            button.addEventListener('click', function () {
                const itemId = Number(this.dataset.id);
                const itemType = this.dataset.type;

                if (itemType === 'work') {
                    selectedWorks = selectedWorks.filter(function (item) {
                        return item.id !== itemId;
                    });
                }

                if (itemType === 'part') {
                    selectedParts = selectedParts.filter(function (item) {
                        return item.id !== itemId;
                    });
                }

                renderCarts();
                updatePrices();
            });
        });
    }


    // =========================
    // ПЕРЕСЧЁТ СТОИМОСТИ
    // =========================

    function updatePrices() {
        const worksTotal = selectedWorks.reduce(function (sum, item) {
            return sum + item.price * (item.qty || 1);
        }, 0);

        const partsTotal = selectedParts.reduce(function (sum, item) {
            return sum + item.price * (item.qty || 1);
        }, 0);

        const total = worksTotal + partsTotal;

        worksPriceInput.value = worksTotal;
        partsPriceInput.value = partsTotal;
        totalPriceInput.value = total;

        // Построчные суммы работ и запчастей
        document.querySelectorAll('.cart-row-sum').forEach(function (el) {
            const id   = Number(el.dataset.id);
            const type = el.dataset.type;
            const list = type === 'work' ? selectedWorks : selectedParts;
            const item = list.find(function (x) { return x.id === id; });
            if (item) {
                el.textContent = item.price * (item.qty || 1);
            }
        });
    }


    // =========================
    // ЭКРАНИРОВАНИЕ HTML
    // =========================

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // =========================
    // ПРАВКА ЦЕНЫ РАБОТ В КОРЗИНЕ
    // =========================

    // Один обработчик на обе корзины — работы и запчасти ведут себя одинаково
    function handleCartInput(cart) {
        if (!cart) return;

        cart.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' &&
                (e.target.classList.contains('cart-price-input') ||
                 e.target.classList.contains('cart-qty-input'))) {
                e.preventDefault();   // чтобы не отправить форму
                e.target.blur();
            }
        });

        cart.addEventListener('change', function (e) {
            const input = e.target;
            if (!input) return;

            const isPrice = input.classList.contains('cart-price-input');
            const isQty   = input.classList.contains('cart-qty-input');
            if (!isPrice && !isQty) return;

            const itemId   = Number(input.dataset.id);
            const itemType = input.dataset.type;   // 'work' или 'part'

            const list = itemType === 'work' ? selectedWorks : selectedParts;
            const item = list.find(function (x) { return x.id === itemId; });
            if (!item) return;

            const raw = String(input.value).replace(/\s/g, '').replace(',', '.');

            if (isPrice) {
                if (raw === '') { input.value = item.price; return; }   // пусто ≠ 0
                let price = Number(raw);
                if (isNaN(price) || price < 0) { input.value = item.price; return; }
                item.price = Math.round(price);
                input.value = item.price;
            }

            if (isQty) {
                if (raw === '') { input.value = item.qty; return; }
                let qty = Number(raw);
                if (isNaN(qty)) { input.value = item.qty; return; }
                qty = Math.round(qty);
                if (qty < 1) { input.value = item.qty; return; }        // минимум 1
                item.qty = qty;
                input.value = item.qty;
            }

            // Синхронизируем скрытые поля строки
            const row = input.closest('.order-cart-item');
            if (row) {
                const priceFieldName = itemType === 'work' ? 'works_prices[]' : 'parts_prices[]';
                const qtyFieldName   = itemType === 'work' ? 'works_qty[]'    : 'parts_qty[]';

                const hiddenPrice = row.querySelector('input[name="' + priceFieldName + '"]');
                if (hiddenPrice) hiddenPrice.value = item.price;

                const hiddenQty = row.querySelector('input[name="' + qtyFieldName + '"]');
                if (hiddenQty) hiddenQty.value = item.qty;
            }

            // Пересчёт — корзину целиком НЕ перерисовываем, иначе инпут потеряет фокус
            updatePrices();

            // Метка «цена изменена»
            const priceInput = row ? row.querySelector('.cart-price-input') : null;
            if (priceInput) {
                if (item.price === item.basePrice) {
                    priceInput.classList.remove('cart-price-changed');
                } else {
                    priceInput.classList.add('cart-price-changed');
                }
            }
        });
    }

    handleCartInput(worksCart);
    handleCartInput(partsCart);

});
// СКРИПТ ДОБАВЛЕНИЯ РАБОТ И ЗАПЧАСТЕЙ





// АВТОДОПОЛНЕНИЕ РАБОТ
document.addEventListener('DOMContentLoaded', function () {
    const workFilter = document.getElementById('work_filter');
    const workSelect = document.getElementById('work_select');
    const workTypeSelect = document.getElementById('work_type');

    if (!workFilter || !workSelect) {
        return;
    }

    let workMatches = [];
    let activeWorkIndex = -1;
    let workResultsList = null;

    function hideWorkList() {
        if (workResultsList) {
            workResultsList.style.display = 'none';
            workResultsList.innerHTML = '';
        }
        workMatches = [];
        activeWorkIndex = -1;
    }

    function setActiveWorkItem(index) {
        if (!workResultsList) return;

        const items = workResultsList.querySelectorAll('li');
        items.forEach(function (item) {
            item.style.background = '#fff';
        });

        if (index < 0 || index >= items.length) {
            activeWorkIndex = -1;
            return;
        }

        activeWorkIndex = index;
        items[index].style.background = '#f5f5f5';
        items[index].scrollIntoView({ block: 'nearest' });
    }

    function selectWorkFromList(workValue) {
        const selectedOption = Array.from(workSelect.options).find(function (opt) {
            return opt.value === workValue;
        });

        if (selectedOption) {
            workFilter.value = selectedOption.textContent.replace(/\s+/g, ' ').trim();
            workSelect.value = selectedOption.value;
        }

        hideWorkList();
        workFilter.focus();
    }

    function showWorkMatches(query) {
        const q = query.trim().toLowerCase();

        // Берём все опции из текущего work_select (они уже отфильтрованы по типу работ)
        const allOptions = Array.from(workSelect.options).filter(function (opt) {
            return opt.value !== '';
        });

        if (q === '') {
            // При пустом поле показываем все работы из select
            workMatches = allOptions;
        } else {
            // При вводе — фильтруем по тексту
            workMatches = allOptions.filter(function (opt) {
                return opt.textContent.toLowerCase().indexOf(q) !== -1;
            });
        }

        if (workMatches.length === 0) {
            hideWorkList();
            return;
        }

        if (!workResultsList) {
            workResultsList = document.createElement('ul');
            workResultsList.id = 'work_filter_results';
            workResultsList.style.cssText = 'display:none; position:absolute; z-index:1100; top:100%; left:0; right:0; margin-top:4px; padding:0; list-style:none; background:#fff; border:1px solid #e0e0e0; border-radius:4px; max-height:220px; overflow-y:auto; box-shadow:0 4px 12px rgba(0,0,0,.1);';

            const parent = workFilter.parentElement;
            if (parent.style.position !== 'relative' && parent.style.position !== 'absolute') {
                parent.style.position = 'relative';
            }
            parent.appendChild(workResultsList);
        }

        workResultsList.innerHTML = '';
        activeWorkIndex = -1;

        workMatches.forEach(function (opt, index) {
            const li = document.createElement('li');
            li.textContent = opt.textContent.replace(/\s+/g, ' ').trim();
            li.style.padding = '8px 12px';
            li.style.cursor = 'pointer';
            li.style.borderBottom = '1px solid #f0f0f0';

            li.addEventListener('mouseenter', function () {
                setActiveWorkItem(index);
            });

            li.addEventListener('mousedown', function (event) {
                event.preventDefault();
                selectWorkFromList(opt.value);
            });

            workResultsList.appendChild(li);
        });

        workResultsList.style.display = 'block';
    }

    workFilter.addEventListener('input', function () {
        showWorkMatches(this.value);
    });

    workFilter.addEventListener('click', function () {
        showWorkMatches(this.value);
    });

    workFilter.addEventListener('keydown', function (event) {
        if (!workResultsList || workResultsList.style.display !== 'block') {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActiveWorkItem(activeWorkIndex + 1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActiveWorkItem(activeWorkIndex - 1);
        } else if (event.key === 'Enter') {
            if (activeWorkIndex >= 0 && workMatches[activeWorkIndex]) {
                event.preventDefault();
                selectWorkFromList(workMatches[activeWorkIndex].value);
            }
        } else if (event.key === 'Escape') {
            hideWorkList();
        }
    });

    document.addEventListener('click', function (event) {
        if (workFilter && workResultsList &&
            !workFilter.contains(event.target) &&
            !workResultsList.contains(event.target)) {
            hideWorkList();
        }
    });
});
// АВТОДОПОЛНЕНИЕ РАБОТ





// АВТОДОПОЛНЕНИЕ МАРКИ АВТОМОБИЛЯ
const CAR_BRANDS = [
    'Acura', 'Alfa Romeo', 'Audi', 'BAIC', 'Belgee', 'Bentley', 'BMW',
    'Brilliance', 'Buick', 'BYD', 'Cadillac', 'Changan', 'Chery',
    'Chevrolet', 'Chrysler', 'Citroen', 'Dacia', 'Daewoo', 'Daihatsu',
    'Datsun', 'Dodge', 'Dongfeng', 'DS', 'Evolute', 'Exeed', 'FAW',
    'Fiat', 'Ford', 'Foton', 'GAZ', 'Geely', 'Genesis', 'GMC',
    'Great Wall', 'Haval', 'Honda', 'Hummer', 'Hyundai', 'Infiniti',
    'Isuzu', 'Iveco', 'Jaguar', 'Jeep', 'Jetour', 'Kia', 'KGM', 'Lada',
    'Lamborghini', 'Land Rover', 'Lexus', 'Li Auto', 'Lifan', 'Lincoln',
    'Lotus', 'Maserati', 'Mazda', 'Mercedes-Benz', 'MG', 'Mini',
    'Mitsubishi', 'Moskvich', 'Nissan', 'Omoda', 'Opel', 'Peugeot',
    'Porsche', 'Ravon', 'Renault', 'Rolls-Royce', 'Saab', 'Seat',
    'Skoda', 'Smart', 'Sollers', 'SsangYong', 'Subaru', 'Suzuki',
    'Tank', 'Tesla', 'Toyota', 'UAZ', 'Volkswagen', 'Volvo', 'Voyah',
    'Xcite', 'Zeekr', 'Zotye'
];

document.addEventListener('DOMContentLoaded', function () {
    const brandInput   = document.getElementById('car_brand');
    const brandResults = document.getElementById('car_brand_results');

    if (!brandInput || !brandResults) {
        return;
    }

    let matches = [];
    let activeIndex = -1;

    function hideBrandList() {
        brandResults.style.display = 'none';
        brandResults.innerHTML = '';
        matches = [];
        activeIndex = -1;
    }

    function setActiveItem(index) {
        const items = brandResults.querySelectorAll('li');

        items.forEach(function (item) {
            item.style.background = '#fff';
        });

        if (index < 0 || index >= items.length) {
            activeIndex = -1;
            return;
        }

        activeIndex = index;
        items[index].style.background = '#f5f5f5';
        items[index].scrollIntoView({ block: 'nearest' });
    }

    function selectBrand(brand) {
        brandInput.value = brand;
        hideBrandList();
        brandInput.focus();
    }

    function showBrandMatches(query) {
        const q = query.trim().toLowerCase();

        if (q === '') {
            hideBrandList();
            return;
        }

        matches = CAR_BRANDS.filter(function (brand) {
            return brand.toLowerCase().indexOf(q) !== -1;
        }).slice(0, 20); // не больше 20 пунктов в списке

        if (matches.length === 0) {
            hideBrandList();
            return;
        }

        brandResults.innerHTML = '';
        activeIndex = -1;

        matches.forEach(function (brand, index) {
            const li = document.createElement('li');

            li.textContent = brand;
            li.style.padding = '8px 12px';
            li.style.cursor = 'pointer';
            li.style.borderBottom = '1px solid #f0f0f0';

            li.addEventListener('mouseenter', function () {
                setActiveItem(index);
            });

            // mousedown + preventDefault, чтобы выбор сработал
            // раньше, чем поле потеряет фокус
            li.addEventListener('mousedown', function (event) {
                event.preventDefault();
                selectBrand(brand);
            });

            brandResults.appendChild(li);
        });

        brandResults.style.display = 'block';
    }

    brandInput.addEventListener('input', function () {
        showBrandMatches(this.value);
    });

    brandInput.addEventListener('focus', function () {
        if (this.value.trim() !== '') {
            showBrandMatches(this.value);
        }
    });

    brandInput.addEventListener('keydown', function (event) {
        if (brandResults.style.display !== 'block') {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActiveItem(activeIndex + 1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActiveItem(activeIndex - 1);
        } else if (event.key === 'Enter') {
            if (activeIndex >= 0 && matches[activeIndex]) {
                event.preventDefault();
                selectBrand(matches[activeIndex]);
            }
        } else if (event.key === 'Escape') {
            hideBrandList();
        }
    });

    // Закрытие по клику вне поля
    document.addEventListener('click', function (event) {
        if (!brandInput.contains(event.target) && !brandResults.contains(event.target)) {
            hideBrandList();
        }
    });
});
// АВТОДОПОЛНЕНИЕ МАРКИ АВТОМОБИЛЯ






// ФИЛЬТР ПО СТАТУСАМ И ТИПАМ РАБОТ
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn        = document.getElementById('toggle_filter_btn');
    const filterPanel      = document.getElementById('filter_panel');
    const statusCheckboxes = document.querySelectorAll('.status-filter-checkbox');
    const typeCheckboxes   = document.querySelectorAll('.type-filter-checkbox');
    const orderItems       = document.querySelectorAll('.order-list-item');
    const countNum         = document.getElementById('orders_visible_count_num');

    if (!toggleBtn || !filterPanel || !orderItems.length) {
        return;
    }

    // Открыть/закрыть панель фильтра
    toggleBtn.addEventListener('click', function () {
        if (filterPanel.style.display === 'none') {
            filterPanel.style.display = 'block';
        } else {
            filterPanel.style.display = 'none';
        }
    });

    const TOTAL_TYPES = typeCheckboxes.length;   // сколько типов вообще есть в панели

    function applyFilter() {
        // --- 1. Активные статусы ---
        const activeStatuses = [];
        statusCheckboxes.forEach(function (cb) {
            if (cb.checked) {
                activeStatuses.push(cb.dataset.status);
            }
        });

        // --- 2. Активные типы работ ---
        const activeTypes = [];
        typeCheckboxes.forEach(function (cb) {
            if (cb.checked) {
                activeTypes.push(String(cb.dataset.type));
            }
        });

        // Типовой фильтр "спит", пока стоят ВСЕ галочки.
        // Как только сняли хоть одну — начинает фильтровать.
        const typeFilterActive = TOTAL_TYPES > 0 && activeTypes.length < TOTAL_TYPES;

        // Сняли вообще все типы — не показываем ничего (как и со статусами)
        const noTypesAtAll = TOTAL_TYPES > 0 && activeTypes.length === 0;

        let visibleCount = 0;

        orderItems.forEach(function (item) {
            // --- Статус ---
            // Если чекбоксов статусов на странице нет — считаем, что статус подходит
            let statusOK = statusCheckboxes.length === 0;

            activeStatuses.forEach(function (statusClass) {
                if (item.classList.contains(statusClass)) {
                    statusOK = true;
                }
            });

            // --- Тип работ ---
            let typeOK = true;

            if (noTypesAtAll) {
                // Ни одного типа не выбрано — скрываем всё
                typeOK = false;
            } else if (typeFilterActive) {
                // Ищем хотя бы один выбранный тип у карточки
                typeOK = false;

                activeTypes.forEach(function (tid) {
                    if (item.classList.contains('has-type-' + tid)) {
                        typeOK = true;
                    }
                });
            }
            // иначе (все типы стоят) typeOK остаётся true —
            // заявки без работ тоже видны, ничего не пропадает

            // --- Итог: карточка видна, только если прошла ОБА фильтра ---
            const visible = statusOK && typeOK;

            item.style.display = visible ? '' : 'none';

            if (visible) {
                visibleCount++;
            }
        });

        // Пересчитываем счётчик в шапке
        if (countNum) {
            countNum.textContent = visibleCount;
        }
    }

    statusCheckboxes.forEach(function (cb) {
        cb.addEventListener('change', applyFilter);
    });

    typeCheckboxes.forEach(function (cb) {
        cb.addEventListener('change', applyFilter);
    });

    applyFilter();
});
// ФИЛЬТР ПО СТАТУСАМ И ТИПАМ РАБОТ