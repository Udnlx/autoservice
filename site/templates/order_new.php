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

    // Предвыбранный клиент из GET (после регистрации из модалки)
    $preselect_client_id = (int)$input->get('client_id');
    $preselect_client_title = '';
    if ($preselect_client_id) {
        $preClientPage = $pages->get("id=$preselect_client_id, template=owner");
        if ($preClientPage->id) {
            $preselect_client_title = $preClientPage->title;
        } else {
            $preselect_client_id = 0;
        }
    }

    // Предвыбранный автомобиль из GET (после регистрации из модалки авто)
    $preselect_car_id = (int)$input->get('car_id');

    // Все работы для списка
    $all_works = $pages->find("parent.name=raboty, template=work_item, sort=title");

    // Все запчасти для списка
    $all_parts = $pages->find("parent.name=zapchasti, template=part, sort=title");

    // Сообщение о нехватке запчастей (приходит из order_new_registration.php через сессию)
    $new_stock_notice = $session->get('new_order_stock_errors');
    $session->remove('new_order_stock_errors');

    $has_new_stock_errors = false;
    $new_stock_error_items = [];

    if (!empty($new_stock_notice) && is_array($new_stock_notice)) {
        $has_new_stock_errors = true;
        $new_stock_error_items = $new_stock_notice;
    }

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

                <?php if ($has_new_stock_errors) { ?>
                    <div class="uk-alert-danger" uk-alert>
                        <a class="uk-alert-close" uk-close></a>
                        <p class="uk-margin-remove"><strong>Заказ не зарегистрирован — на складе не хватает запчастей</strong></p>
                        <ul class="uk-list uk-list-divider uk-margin-small-top uk-margin-remove-bottom">
                            <?php foreach ($new_stock_error_items as $err) { ?>
                                <li>
                                    <strong><?php echo htmlspecialchars($err['name'], ENT_QUOTES, 'UTF-8'); ?></strong> —
                                    нужно <?php echo (int)$err['need']; ?> шт,
                                    на складе <?php echo (int)$err['have']; ?> шт
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                <?php } ?>

                <?php if ($input->get->int('client_created') === 1) { ?>
                    <div class="uk-alert-success" uk-alert>
                        <a class="uk-alert-close" uk-close></a>
                        <p class="uk-margin-remove">Новый клиент зарегистрирован и выбран</p>
                    </div>
                <?php } ?>

                <?php if ($input->get->int('car_created') === 1) { ?>
                    <div class="uk-alert-success" uk-alert>
                        <a class="uk-alert-close" uk-close></a>
                        <p class="uk-margin-remove">Новый автомобиль зарегистрирован и выбран</p>
                    </div>
                <?php } ?>

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
                        <div class="uk-flex uk-flex-between uk-flex-middle" style="gap: 10px;">
                            <label for="client_search" style="margin: 0;">Клиент</label>
                            <a href="#new_owner_modal" uk-toggle style="font-size: 0.85em;">+ Новый клиент</a>
                        </div>

                        <input type="hidden" id="client" name="client" value="<?php echo (int)$preselect_client_id; ?>">

                        <div class="uk-margin-small-top" style="position: relative;">
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
                            <div id="client_selected" style="<?php echo $preselect_client_id ? '' : 'display:none;'; ?> margin-top: 6px; padding: 6px 10px; background: #f8f8f8; border-radius: 4px; font-size: 0.9em;">
                                <span id="client_selected_name" style="font-weight: 700;"><?php echo htmlspecialchars($preselect_client_title, ENT_QUOTES, 'UTF-8'); ?></span>
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

                        <div class="uk-flex uk-flex-between uk-flex-middle" style="gap: 10px;">
                            <label for="car_select" style="margin: 0;">Автомобиль</label>
                            <a href="#new_car_modal" uk-toggle style="font-size: 0.85em;" id="new_car_link">+ Новый автомобиль</a>
                        </div>

                        <input type="hidden" id="car" name="car" value="0">

                        <div class="uk-margin-small-top">
                            <select class="uk-select" id="car_select">
                                <option value="0">— Выберите автомобиль —</option>
                            </select>
                        </div>

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
                                <?php foreach ($all_works as $workPage) { ?>
                                    <option value="<?php echo htmlspecialchars($workPage->title, ENT_QUOTES, 'UTF-8'); ?>" data-price="<?php echo (int)$workPage->work_price; ?>">
                                        <?php echo htmlspecialchars($workPage->title, ENT_QUOTES, 'UTF-8'); ?> — <?php echo (int)$workPage->work_price; ?> ₽
                                    </option>
                                <?php } ?>
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
                                <?php foreach ($all_parts as $partPage) { ?>
                                    <option
                                        value="<?php echo htmlspecialchars($partPage->title, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-price="<?php echo (int)$partPage->part_price; ?>"
                                        data-id="<?php echo (int)$partPage->id; ?>"
                                        data-qty="<?php echo (int)$partPage->part_qty; ?>">
                                        <?php echo htmlspecialchars($partPage->title, ENT_QUOTES, 'UTF-8'); ?> — <?php echo (int)$partPage->part_price; ?> ₽ · остаток <?php echo (int)$partPage->part_qty; ?> шт
                                    </option>
                                <?php } ?>
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

    <!--МОДАЛЬНОЕ ОКНО НОВЫЙ КЛИЕНТ-->
    <div id="new_owner_modal" uk-modal>
        <div class="uk-modal-dialog uk-modal-body">
            <button class="uk-modal-close-default" type="button" uk-close></button>

            <h3 class="uk-card-title uk-text-center">Новый клиент</h3>

            <form class="uk-flex uk-flex-column" action="/klient-registratciia/" method="post">

                <input type="hidden" name="return_to" value="new_order">

                <div class="uk-margin-small-top">
                    <label for="owner_type">Тип клиента</label>
                    <select class="uk-select" id="owner_type" name="owner_type">
                        <option value="Физлицо">Физлицо</option>
                        <option value="Юрлицо">Юрлицо</option>
                    </select>
                </div>

                <div class="uk-margin-small-top">
                    <label for="owner_name">ФИО (Фамилия Имя Отчество)</label>
                    <input class="uk-input" id="owner_name" type="text" name="owner_name" placeholder="Например: Иванов Иван Иванович" autocomplete="off" required>
                </div>

                <div class="uk-margin-small-top">
                    <label for="owner_phone">Телефон</label>
                    <input class="uk-input" id="owner_phone" type="text" name="owner_phone" placeholder="Например: +7 900 123-45-67" autocomplete="off" required>
                </div>

                <div class="uk-margin-small-top">
                    <label for="owner_email">Почта</label>
                    <input class="uk-input" id="owner_email" type="email" name="owner_email" placeholder="Например: ivanov@mail.ru" autocomplete="off">
                </div>

                <div class="uk-margin-small-top">
                    <label for="owner_address">Адрес</label>
                    <input class="uk-input" id="owner_address" type="text" name="owner_address" placeholder="Например: г. Москва, ул. Ленина, д. 1" autocomplete="off">
                </div>

                <div class="uk-margin-small-top">
                    <label for="owner_company">Организация</label>
                    <input class="uk-input" id="owner_company" type="text" name="owner_company" placeholder="Название компании" autocomplete="off">
                </div>

                <div class="uk-margin-small-top">
                    <label for="owner_inn">ИНН</label>
                    <input class="uk-input" id="owner_inn" type="text" name="owner_inn" placeholder="10 или 12 цифр" autocomplete="off">
                </div>

                <div class="uk-margin-small-top">
                    <label for="owner_notes">Примечания</label>
                    <textarea class="uk-textarea" id="owner_notes" name="owner_notes" rows="3" placeholder="Особенности, замечания..."></textarea>
                </div>

                <div class="uk-margin-small-top uk-flex uk-flex-column">
                    <button type="submit" class="uk-margin-small-top uk-button uk-button-default" name="save_new_owner" value="1">
                        Зарегистрировать
                    </button>
                </div>

            </form>
        </div>
    </div>
    <!--МОДАЛЬНОЕ ОКНО НОВЫЙ КЛИЕНТ-->

    <!--МОДАЛЬНОЕ ОКНО НОВЫЙ АВТОМОБИЛЬ-->
    <div id="new_car_modal" uk-modal>
        <div class="uk-modal-dialog uk-modal-body">
            <button class="uk-modal-close-default" type="button" uk-close></button>

            <h3 class="uk-card-title uk-text-center">Новый автомобиль</h3>

            <form class="uk-flex uk-flex-column" action="/avtomobil-registratciia/" method="post">

                <!-- Возврат обратно на страницу нового заказа -->
                <input type="hidden" name="return_to" value="new_order">

                <div class="uk-margin-small-top">
                    <label for="modal_car_brand">Марка</label>
                    <input class="uk-input" id="modal_car_brand" type="text" name="car_brand" placeholder="Например: Renault" autocomplete="off" required>
                </div>

                <div class="uk-margin-small-top">
                    <label for="modal_car_model">Модель</label>
                    <input class="uk-input" id="modal_car_model" type="text" name="car_model" placeholder="Например: Sandero" autocomplete="off" required>
                </div>

                <div class="uk-margin-small-top">
                    <label for="modal_car_number">Гос. номер</label>
                    <input class="uk-input" id="modal_car_number" type="text" name="car_number" placeholder="Например: О123ХХ58" autocomplete="off" required>
                </div>

                <div class="uk-margin-small-top">
                    <label for="modal_car_vin">VIN</label>
                    <input class="uk-input" id="modal_car_vin" type="text" name="car_vin" placeholder="17 символов" autocomplete="off" required>
                </div>

                <div class="uk-margin-small-top">
                    <label for="modal_car_year">Год выпуска</label>
                    <input class="uk-input" id="modal_car_year" type="text" name="car_year" placeholder="Например: 2022" autocomplete="off" required>
                </div>

                <!-- ПОИСК КЛИЕНТА В МОДАЛКЕ АВТО -->
                <div class="uk-margin-small-top">
                    <label for="modal_car_owner_search">Клиент</label>

                    <input type="hidden" id="modal_car_owner" name="car_owner" value="0">

                    <div style="position: relative;">
                        <div class="uk-flex" style="gap: 8px;">
                            <input
                                class="uk-input"
                                id="modal_car_owner_search"
                                type="text"
                                placeholder="Введите имя клиента..."
                                autocomplete="off"
                            >
                            <button
                                type="button"
                                class="uk-button uk-button-default"
                                id="modal_car_owner_search_btn"
                                style="white-space: nowrap;"
                            >Найти</button>
                        </div>

                        <!-- Выбранный клиент -->
                        <div id="modal_car_owner_selected" style="display:none; margin-top: 6px; padding: 6px 10px; background: #f8f8f8; border-radius: 4px; font-size: 0.9em;">
                            <span id="modal_car_owner_selected_name" style="font-weight: 700;"></span>
                            <a href="#" id="modal_car_owner_clear" style="margin-left: 10px; font-size: 0.85em; color: #999;">✕ сбросить</a>
                        </div>

                        <!-- Список результатов -->
                        <ul
                            id="modal_car_owner_results"
                            style="display:none; position:absolute; z-index:1200; left:0; right:0; margin:0; padding:0;
                                   list-style:none; background:#fff; border:1px solid #e0e0e0; border-radius:4px;
                                   max-height:220px; overflow-y:auto; box-shadow:0 4px 12px rgba(0,0,0,.1);"
                        ></ul>
                    </div>
                </div>
                <!-- /ПОИСК КЛИЕНТА В МОДАЛКЕ АВТО -->

                <div class="uk-margin-small-top">
                    <label for="modal_car_notes">Примечания</label>
                    <textarea class="uk-textarea" id="modal_car_notes" name="car_notes" rows="3" placeholder="Особенности, замечания..."></textarea>
                </div>

                <div class="uk-margin-small-top uk-flex uk-flex-column">
                    <button type="submit" class="uk-margin-small-top uk-button uk-button-default" name="save_new_car" value="1">
                        Зарегистрировать
                    </button>
                </div>

            </form>
        </div>
    </div>
    <!--МОДАЛЬНОЕ ОКНО НОВЫЙ АВТОМОБИЛЬ-->

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    var owners  = <?php echo $owners_json_encoded; ?>;
    var allCars = <?php echo $cars_json_encoded; ?>;

    // Предвыбранный автомобиль из GET (после регистрации)
    var preselectCarId = <?php echo (int)$preselect_car_id; ?>;

    // --- Элементы клиента ---
    var clientSearchInput  = document.getElementById('client_search');
    var clientSearchBtn    = document.getElementById('client_search_btn');
    var clientResultsList  = document.getElementById('client_results');
    var clientHiddenInput  = document.getElementById('client');
    var clientSelectedBox  = document.getElementById('client_selected');
    var clientSelectedName = document.getElementById('client_selected_name');
    var clientClearBtn     = document.getElementById('client_clear');

    // --- Элементы автомобиля ---
    var carSection     = document.getElementById('car_section');
    var carSelect      = document.getElementById('car_select');
    var carHiddenInput = document.getElementById('car');
    var carNoCars      = document.getElementById('car_no_cars');

    // --- Кнопка отправки ---
    var submitBtn  = document.getElementById('submit_btn');
    var submitHint = document.getElementById('submit_hint');

    // --- Элементы модалки авто (поиск владельца) ---
    var modalCarOwnerSearch      = document.getElementById('modal_car_owner_search');
    var modalCarOwnerSearchBtn   = document.getElementById('modal_car_owner_search_btn');
    var modalCarOwnerResults     = document.getElementById('modal_car_owner_results');
    var modalCarOwnerHidden      = document.getElementById('modal_car_owner');
    var modalCarOwnerSelectedBox = document.getElementById('modal_car_owner_selected');
    var modalCarOwnerSelectedName= document.getElementById('modal_car_owner_selected_name');
    var modalCarOwnerClear       = document.getElementById('modal_car_owner_clear');

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

            // Предвыбор автомобиля после возврата из модалки регистрации
            if (preselectCarId > 0) {
                carSelect.value = preselectCarId;
                if (carSelect.value == preselectCarId) {
                    carHiddenInput.value = preselectCarId;
                }
                preselectCarId = 0; // сбрасываем, чтобы не повторялось
            }
        }

        carSection.style.display = 'block';
        updateSubmitState();
    }

    function selectClient(id, title) {
        clientHiddenInput.value = id;
        clientSelectedName.textContent = title;
        clientSelectedBox.style.display = 'block';
        clientResultsList.style.display = 'none';
        clientSearchInput.value = '';
        loadCarsForClient(id);
    }

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

    carSelect.addEventListener('change', function () {
        carHiddenInput.value = this.value;
        updateSubmitState();
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('#client_search') &&
            !e.target.closest('#client_search_btn') &&
            !e.target.closest('#client_results')) {
            clientResultsList.style.display = 'none';
        }
    });

    document.getElementById('select_seat').addEventListener('submit', function (e) {
        var clientOk = clientHiddenInput.value !== '0' && clientHiddenInput.value !== '';
        var carOk    = carHiddenInput.value !== '0' && carHiddenInput.value !== '';
        if (!clientOk || !carOk) {
            e.preventDefault();
            updateSubmitState();
        }
    });

    // --- Инициализация с предвыбранным клиентом ---
    var preselectedId = parseInt(clientHiddenInput.value, 10);
    if (preselectedId > 0) {
        loadCarsForClient(preselectedId);
    }

    updateSubmitState();

    // =============================================
    // МОДАЛКА НОВОГО АВТОМОБИЛЯ — поиск владельца
    // =============================================

    if (modalCarOwnerSearch && modalCarOwnerSearchBtn) {

        function modalShowOwnerResults(items) {
            modalCarOwnerResults.innerHTML = '';
            if (items.length === 0) {
                var li = document.createElement('li');
                li.textContent = 'Ничего не найдено';
                li.style.cssText = 'padding:8px 12px; color:#999; font-size:.9em;';
                modalCarOwnerResults.appendChild(li);
            } else {
                items.forEach(function (owner) {
                    var li = document.createElement('li');
                    li.textContent = owner.title;
                    li.style.cssText = 'padding:8px 12px; cursor:pointer; border-bottom:1px solid #f0f0f0; font-size:.9em;';
                    li.addEventListener('mouseenter', function () { this.style.background = '#f5f5f5'; });
                    li.addEventListener('mouseleave', function () { this.style.background = ''; });
                    li.addEventListener('click', function () {
                        modalSelectOwner(owner.id, owner.title);
                    });
                    modalCarOwnerResults.appendChild(li);
                });
            }
            modalCarOwnerResults.style.display = 'block';
        }

        function modalSelectOwner(id, title) {
            modalCarOwnerHidden.value = id;
            modalCarOwnerSelectedName.textContent = title;
            modalCarOwnerSelectedBox.style.display = 'block';
            modalCarOwnerResults.style.display = 'none';
            modalCarOwnerSearch.value = '';
        }

        function modalResetOwner() {
            modalCarOwnerHidden.value = 0;
            modalCarOwnerSelectedBox.style.display = 'none';
            modalCarOwnerSearch.value = '';
            modalCarOwnerResults.style.display = 'none';
        }

        function modalDoOwnerSearch() {
            var q = modalCarOwnerSearch.value.trim().toLowerCase();
            if (q.length < 2) {
                modalCarOwnerResults.style.display = 'none';
                return;
            }
            var filtered = owners.filter(function (o) {
                return o.title.toLowerCase().indexOf(q) !== -1;
            });
            modalShowOwnerResults(filtered.slice(0, 50));
        }

        modalCarOwnerSearchBtn.addEventListener('click', modalDoOwnerSearch);
        modalCarOwnerSearch.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); modalDoOwnerSearch(); }
        });

        modalCarOwnerClear.addEventListener('click', function (e) {
            e.preventDefault();
            modalResetOwner();
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('#modal_car_owner_search') &&
                !e.target.closest('#modal_car_owner_search_btn') &&
                !e.target.closest('#modal_car_owner_results')) {
                modalCarOwnerResults.style.display = 'none';
            }
        });

        // При открытии модалки — автоматически подставляем текущего выбранного клиента
        UIkit.util.on('#new_car_modal', 'show', function () {
            var currentClientId    = parseInt(clientHiddenInput.value, 10);
            var currentClientTitle = clientSelectedName.textContent.trim();

            if (currentClientId > 0 && currentClientTitle !== '') {
                modalSelectOwner(currentClientId, currentClientTitle);
            }
        });

        // При закрытии модалки — сбрасываем форму авто
        UIkit.util.on('#new_car_modal', 'hidden', function () {
            modalResetOwner();
            // Сбрасываем поля формы
            var form = document.querySelector('#new_car_modal form');
            if (form) {
                form.querySelectorAll('input[type=text], input[type=email], textarea').forEach(function(el) {
                    el.value = '';
                });
            }
        });
    }

});
</script>

<?php   
}
?>