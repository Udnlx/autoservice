<?php namespace ProcessWire;

$today = date("d-m-Y");

if(isset($_SESSION['operator'])){
    $operator = $_SESSION['operator'];
} else {
    $operator = 'no_operator';
}

if ($operator == 'no_operator') {
?>
    <div id="content" style="max-width: 700px;">
        <h1 class="uk-heading-hero uk-text-center">Новый заказ-наряд</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php    
} else {

    // Все клиенты для поиска
    $all_owners = $pages->find("template=owner, sort=title, limit=1000");
    $owners_json = [];
    foreach ($all_owners as $o) {
        $owners_json[] = ['id' => (int)$o->id, 'title' => $o->title];
    }
    $owners_json_encoded = json_encode($owners_json, JSON_UNESCAPED_UNICODE);

    // Все автомобили — фильтрация на клиенте по owner_id
    $all_cars = $pages->find("template=car, sort=title, limit=2000");
    $cars_json = [];
    foreach ($all_cars as $c) {
        $ownerPage = $c->car_owner;
        $car_display = trim($c->car_brand . ' ' . $c->car_model);
        if (!empty($c->car_number)) {
            $car_display .= ' (' . $c->car_number . ')';
        }
        if ($car_display === '') {
            $car_display = $c->title;
        }
        $cars_json[] = [
            'id'       => (int)$c->id,
            'title'    => $car_display,
            'owner_id' => ($ownerPage && $ownerPage->id) ? (int)$ownerPage->id : 0
        ];
    }
    $cars_json_encoded = json_encode($cars_json, JSON_UNESCAPED_UNICODE);

?>

<div id="content">
    <h1 class="uk-margin-remove uk-heading-hero uk-text-center">Новый заказ-наряд</h1>
    <div>

        <div>
            <div class="pagemenu uk-width-1-1 uk-flex">
                <a class="menu-link" href="/">На главную</a>
            </div>
        </div>

        <div>
            <div class="uk-card uk-card-default uk-card-body uk-flex uk-flex-column">
                <form class="uk-flex uk-flex-column" id="select_seat" action="/zakaz-registratciia/" method="post">

                    <label>Дата и оператор заказа</label>
                    <div class="uk-margin-small-top">
                        <input class="uk-input" id="selected_date" type="text" name="selected_date" value="<?php echo $today; ?>" readonly>
                    </div>
                    <div class="uk-margin-small-top">
                        <input class="uk-input" id="selected_worker" type="text" name="selected_worker" value="<?php echo $operator; ?>" readonly>
                    </div>

                    <!-- ПОИСК КЛИЕНТА -->
                    <div class="uk-margin-small-top">
                        <label for="client_search">Клиент</label>

                        <input type="hidden" id="client" name="client" value="0">

                        <div style="position: relative;">
                            <div class="uk-flex" style="gap: 8px;">
                                <input
                                    class="uk-input"
                                    id="client_search"
                                    type="text"
                                    placeholder="Введите имя клиента..."
                                    autocomplete="off"
                                >
                                <button
                                    type="button"
                                    class="uk-button uk-button-default"
                                    id="client_search_btn"
                                    style="white-space: nowrap;"
                                >Найти</button>
                            </div>

                            <!-- Выбранный клиент -->
                            <div id="client_selected" style="display:none; margin-top: 6px; padding: 6px 10px; background: #f8f8f8; border-radius: 4px; font-size: 0.9em;">
                                <span id="client_selected_name" style="font-weight: 700;"></span>
                                <a href="#" id="client_clear" style="margin-left: 10px; font-size: 0.85em; color: #999;">✕ сбросить</a>
                            </div>

                            <!-- Список результатов -->
                            <ul
                                id="client_results"
                                style="display:none; position:absolute; z-index:1100; left:0; right:0; margin:0; padding:0;
                                       list-style:none; background:#fff; border:1px solid #e0e0e0; border-radius:4px;
                                       max-height:220px; overflow-y:auto; box-shadow:0 4px 12px rgba(0,0,0,.1);"
                            ></ul>
                        </div>
                    </div>
                    <!-- /ПОИСК КЛИЕНТА -->

                    <!-- АВТОМОБИЛЬ — появляется после выбора клиента -->
                    <div class="uk-margin-small-top" id="car_section" style="display:none;">
                        <label for="car_select">Автомобиль</label>

                        <input type="hidden" id="car" name="car" value="0">

                        <select class="uk-select" id="car_select">
                            <option value="0">— Выберите автомобиль —</option>
                        </select>

                        <div id="car_no_cars" style="display:none; margin-top: 6px; font-size: 0.9em; color: #999;">
                            У этого клиента нет автомобилей в базе
                        </div>
                    </div>
                    <!-- /АВТОМОБИЛЬ -->

                    <!--КОРЗИНА РАБОТ-->
                    <div class="uk-margin-small-top">
                        <label for="work_select">Работы</label>

                        <div class="uk-flex uk-flex-middle order-add-row" style="gap: 10px;">
                            <select class="uk-select" id="work_select">
                                <option value="" disabled selected>Выберите работу</option>
                                <option value="Замена масла" data-price="1500">Замена масла — 1500 ₽</option>
                                <option value="Замена фильтра" data-price="700">Замена фильтра — 700 ₽</option>
                                <option value="Диагностика" data-price="1000">Диагностика — 1000 ₽</option>
                                <option value="Шиномонтаж" data-price="2500">Шиномонтаж — 2500 ₽</option>
                                <option value="Развал-схождение" data-price="3000">Развал-схождение — 3000 ₽</option>
                            </select>

                            <button type="button" class="uk-button uk-button-default" id="add_work">
                                Добавить
                            </button>
                        </div>
                    </div>

                    <div class="uk-margin-small-top">
                        <div class="order-cart-box works-cart-box">
                            <div class="order-cart-header">
                                <div class="order-cart-header-title">
                                    <span>🛠</span>
                                    <span>Корзина работ</span>
                                </div>
                                <span class="uk-badge order-cart-badge-work">Работы</span>
                            </div>
                            <div id="works_cart" class="order-cart-body">
                                <div id="works_empty" class="order-cart-empty">
                                    Работы пока не выбраны
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--КОРЗИНА РАБОТ-->

                    <!--КОРЗИНА ЗАПЧАСТЕЙ-->
                    <div class="uk-margin-small-top">
                        <label for="part_select">Запчасти</label>

                        <div class="uk-flex uk-flex-middle order-add-row" style="gap: 10px;">
                            <select class="uk-select" id="part_select">
                                <option value="" disabled selected>Выберите запчасть</option>
                                <option value="Масляный фильтр" data-price="900">Масляный фильтр — 900 ₽</option>
                                <option value="Воздушный фильтр" data-price="1200">Воздушный фильтр — 1200 ₽</option>
                                <option value="Салонный фильтр" data-price="1100">Салонный фильтр — 1100 ₽</option>
                                <option value="Масло 5W-40" data-price="3500">Масло 5W-40 — 3500 ₽</option>
                                <option value="Свечи зажигания" data-price="2400">Свечи зажигания — 2400 ₽</option>
                            </select>

                            <button type="button" class="uk-button uk-button-default" id="add_part">
                                Добавить
                            </button>
                        </div>
                    </div>

                    <div class="uk-margin-small-top">
                        <div class="order-cart-box parts-cart-box">
                            <div class="order-cart-header">
                                <div class="order-cart-header-title">
                                    <span>⚙️</span>
                                    <span>Корзина запчастей</span>
                                </div>
                                <span class="uk-badge order-cart-badge-part">Запчасти</span>
                            </div>
                            <div id="parts_cart" class="order-cart-body">
                                <div id="parts_empty" class="order-cart-empty">
                                    Запчасти пока не выбраны
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--КОРЗИНА ЗАПЧАСТЕЙ-->

                    <div class="uk-margin-small-top">
                        <label for="works_price">Стоимость работ</label>
                        <input class="uk-input" id="works_price" type="text" name="works_price" value="0" autocomplete="off" required readonly>
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="parts_price">Стоимость запчастей</label>
                        <input class="uk-input" id="parts_price" type="text" name="parts_price" value="0" autocomplete="off" required readonly>
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="total_price">Общая стоимость</label>
                        <input class="uk-input" id="total_price" type="text" name="total_price" value="0" autocomplete="off" required readonly>
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="payment_type">Вид платежа</label>
                        <select class="uk-select" id="payment_type" name="payment_type" required>
                            <option value="Наличный расчет">Наличный расчет</option>
                            <option value="Безналичный расчет">Безналичный расчет</option>
                        </select>
                    </div>

                    <div class="uk-margin-small-top uk-flex uk-flex-column">
                        <button class="uk-margin-small-top uk-button uk-button-default" type="submit" id="submit_btn" disabled>
                            Зарегистрировать
                        </button>
                        <div id="submit_hint" style="margin-top: 6px; font-size: 0.85em; color: #999; text-align: center;">
                            Выберите клиента и автомобиль для регистрации
                        </div>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    var owners  = <?php echo $owners_json_encoded; ?>;
    var allCars = <?php echo $cars_json_encoded; ?>;

    // --- Элементы клиента ---
    var clientSearchInput  = document.getElementById('client_search');
    var clientSearchBtn    = document.getElementById('client_search_btn');
    var clientResultsList  = document.getElementById('client_results');
    var clientHiddenInput  = document.getElementById('client');
    var clientSelectedBox  = document.getElementById('client_selected');
    var clientSelectedName = document.getElementById('client_selected_name');
    var clientClearBtn     = document.getElementById('client_clear');

    // --- Элементы автомобиля ---
    var carSection    = document.getElementById('car_section');
    var carSelect     = document.getElementById('car_select');
    var carHiddenInput = document.getElementById('car');
    var carNoCars     = document.getElementById('car_no_cars');

    // --- Кнопка отправки ---
    var submitBtn  = document.getElementById('submit_btn');
    var submitHint = document.getElementById('submit_hint');

    // Обновляем состояние кнопки
    function updateSubmitState() {
        var clientOk = clientHiddenInput.value !== '0' && clientHiddenInput.value !== '';
        var carOk    = carHiddenInput.value !== '0' && carHiddenInput.value !== '';

        if (clientOk && carOk) {
            submitBtn.disabled = false;
            submitHint.style.display = 'none';
        } else {
            submitBtn.disabled = true;
            submitHint.style.display = 'block';
            if (!clientOk) {
                submitHint.textContent = 'Выберите клиента и автомобиль для регистрации';
            } else {
                submitHint.textContent = 'Выберите автомобиль для регистрации';
            }
        }
    }

    // Показываем автомобили выбранного клиента
    function loadCarsForClient(clientId) {
        var filtered = allCars.filter(function (c) { return c.owner_id === clientId; });

        // Сбрасываем select и скрытый инпут
        carHiddenInput.value = '0';
        carSelect.innerHTML = '<option value="0">— Выберите автомобиль —</option>';
        carSelect.style.display = 'block';
        carNoCars.style.display = 'none';

        if (filtered.length === 0) {
            carSelect.style.display = 'none';
            carNoCars.style.display = 'block';
        } else {
            filtered.forEach(function (car) {
                var opt = document.createElement('option');
                opt.value = car.id;
                opt.textContent = car.title;
                carSelect.appendChild(opt);
            });
        }

        carSection.style.display = 'block';
        updateSubmitState();
    }

    // Выбор клиента
    function selectClient(id, title) {
        clientHiddenInput.value = id;
        clientSelectedName.textContent = title;
        clientSelectedBox.style.display = 'block';
        clientResultsList.style.display = 'none';
        clientSearchInput.value = '';
        loadCarsForClient(id);
    }

    // Сброс клиента — скрываем и сбрасываем автомобиль
    function resetClient() {
        clientHiddenInput.value = '0';
        clientSelectedBox.style.display = 'none';
        clientSearchInput.value = '';
        clientResultsList.style.display = 'none';
        carSection.style.display = 'none';
        carHiddenInput.value = '0';
        carSelect.innerHTML = '<option value="0">— Выберите автомобиль —</option>';
        updateSubmitState();
    }

    // Поиск клиентов
    function showClientResults(items) {
        clientResultsList.innerHTML = '';
        if (items.length === 0) {
            var li = document.createElement('li');
            li.textContent = 'Ничего не найдено';
            li.style.cssText = 'padding:8px 12px; color:#999; font-size:.9em;';
            clientResultsList.appendChild(li);
        } else {
            items.forEach(function (owner) {
                var li = document.createElement('li');
                li.textContent = owner.title;
                li.style.cssText = 'padding:8px 12px; cursor:pointer; border-bottom:1px solid #f0f0f0; font-size:.9em;';
                li.addEventListener('mouseenter', function () { this.style.background = '#f5f5f5'; });
                li.addEventListener('mouseleave', function () { this.style.background = ''; });
                li.addEventListener('click', function () { selectClient(owner.id, owner.title); });
                clientResultsList.appendChild(li);
            });
        }
        clientResultsList.style.display = 'block';
    }

    function doClientSearch() {
        var q = clientSearchInput.value.trim().toLowerCase();
        if (q.length < 2) {
            clientResultsList.style.display = 'none';
            return;
        }
        var filtered = owners.filter(function (o) {
            return o.title.toLowerCase().indexOf(q) !== -1;
        });
        showClientResults(filtered.slice(0, 50));
    }

    clientSearchBtn.addEventListener('click', doClientSearch);
    clientSearchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); doClientSearch(); }
    });

    clientClearBtn.addEventListener('click', function (e) {
        e.preventDefault();
        resetClient();
    });

    // Выбор автомобиля из select
    carSelect.addEventListener('change', function () {
        carHiddenInput.value = this.value;
        updateSubmitState();
    });

    // Закрываем список клиентов при клике вне
    document.addEventListener('click', function (e) {
        if (!e.target.closest('#client_search') &&
            !e.target.closest('#client_search_btn') &&
            !e.target.closest('#client_results')) {
            clientResultsList.style.display = 'none';
        }
    });

    // Страховая проверка при отправке формы
    document.getElementById('select_seat').addEventListener('submit', function (e) {
        var clientOk = clientHiddenInput.value !== '0' && clientHiddenInput.value !== '';
        var carOk    = carHiddenInput.value !== '0' && carHiddenInput.value !== '';
        if (!clientOk || !carOk) {
            e.preventDefault();
            updateSubmitState();
        }
    });

    // Инициализация
    updateSubmitState();
});
</script>

<?php   
}
?>