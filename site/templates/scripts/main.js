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

            if (!itemName) {
                return;
            }

            selectedWorks.push({
                id: itemIdCounter++,
                name: itemName,
                price: itemPrice,
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

            selectedParts.push({
                id: itemIdCounter++,
                name: itemName,
                price: itemPrice,
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
                <div>
                    <strong>${escapeHtml(item.name)}</strong>
                    <div class="order-cart-price">
                        ${item.price} ₽
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
            return sum + item.price;
        }, 0);

        const partsTotal = selectedParts.reduce(function (sum, item) {
            return sum + item.price;
        }, 0);

        const total = worksTotal + partsTotal;

        worksPriceInput.value = worksTotal;
        partsPriceInput.value = partsTotal;
        totalPriceInput.value = total;
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

});
// СКРИПТ ДОБАВЛЕНИЯ РАБОТ И ЗАПЧАСТЕЙ