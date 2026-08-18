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
        <h1 class="uk-heading-hero uk-text-center">Заказ</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php    
} else {

    $order_id = $input->get->int('id');
    if (!$order_id) {
        $order_id = 1;
    }

    if (!function_exists('orderClean')) {
        function orderClean($value) {
            return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        }
    }

    $order = [
        'id' => $order_id,
        'date' => $today,
        'worker' => $operator,
        'client' => 'Клиент 1',
        'car' => 'Авто 1',
        'status' => 'Новая',
        'payment_type' => 'Наличный расчет',
        'works_price' => 2500,
        'parts_price' => 4400,
        'total_price' => 6900,
        'works' => [
            [
                'name' => 'Замена масла',
                'price' => 1500
            ],
            [
                'name' => 'Диагностика',
                'price' => 1000
            ]
        ],
        'parts' => [
            [
                'name' => 'Масляный фильтр',
                'price' => 900
            ],
            [
                'name' => 'Масло 5W-40',
                'price' => 3500
            ]
        ]
    ];

?>

<div id="content">
    <h1 class="uk-margin-remove uk-heading-hero uk-text-center">Заказ №<?php echo orderClean($order['id']); ?></h1>
    <div>

        <div>
            <div class="pagemenu uk-width-1-1 uk-flex">
                <a class="menu-link" href="/">На главную</a>
            </div>
        </div>

        <div>
            <div class="uk-card uk-card-default uk-card-body uk-flex uk-flex-column">

                <div class="order-view-top">
                    <div>
                        <div class="order-view-number">Заказ №<?php echo orderClean($order['id']); ?></div>
                        <div class="order-view-subtitle">Карточка зарегистрированной заявки</div>
                    </div>

                    <div class="order-status-badge" id="order_status_badge">
                        <?php echo orderClean($order['status']); ?>
                    </div>
                </div>

                <form class="uk-flex uk-flex-column" id="order_view_form" action="" method="post">
                    <input type="hidden" name="order_id" value="<?php echo orderClean($order['id']); ?>">

                    <div class="order-view-grid uk-margin-small-top">
                        <div class="order-info-box">
                            <div class="order-info-label">Дата заказа</div>
                            <div class="order-info-value"><?php echo orderClean($order['date']); ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">Оператор</div>
                            <div class="order-info-value"><?php echo orderClean($order['worker']); ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">Клиент</div>
                            <div class="order-info-value"><?php echo orderClean($order['client']); ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">Автомобиль</div>
                            <div class="order-info-value"><?php echo orderClean($order['car']); ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">Вид платежа</div>
                            <div class="order-info-value"><?php echo orderClean($order['payment_type']); ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">Текущий статус</div>
                            <div class="order-info-value"><?php echo orderClean($order['status']); ?></div>
                        </div>
                    </div>

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
                                <?php if (!empty($order['works'])) { ?>
                                    <?php foreach ($order['works'] as $work) { ?>
                                    <?php $work_item_id = time() . rand(1000, 9999); ?>
                                        <div class="order-cart-item uk-flex uk-flex-between uk-flex-middle" data-id="<?php echo $work_item_id; ?>">
                                            <div>
                                                <strong><?php echo orderClean($work['name']); ?></strong>
                                                <div class="order-cart-price">
                                                    <?php echo orderClean($work['price']); ?> ₽
                                                </div>
                                            </div>

                                            <button type="button" class="uk-button uk-button-danger uk-button-small remove-work" data-id="<?php echo $work_item_id; ?>" data-type="work">

                                            <input type="hidden" name="works[]" value="<?php echo orderClean($work['name']); ?> - <?php echo orderClean($work['price']); ?>">
                                            <input type="hidden" name="works_prices[]" value="<?php echo orderClean($work['price']); ?>">
                                        </div>
                                    <?php } ?>
                                <?php } else { ?>
                                    <div id="works_empty" class="order-cart-empty">
                                        Работы пока не выбраны
                                    </div>
                                <?php } ?>
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
                                <?php if (!empty($order['parts'])) { ?>
                                    <?php foreach ($order['parts'] as $part_index => $part) { ?>
                                        <?php $part_item_id = 'part_' . $part_index . '_' . time(); ?>

                                        <div class="order-cart-item uk-flex uk-flex-between uk-flex-middle" data-id="<?php echo orderClean($part_item_id); ?>">
                                            <div>
                                                <strong><?php echo orderClean($part['name']); ?></strong>
                                                <div class="order-cart-price">
                                                    <?php echo orderClean($part['price']); ?> ₽
                                                </div>
                                            </div>

                                            <button type="button" class="uk-button uk-button-danger uk-button-small remove-part" data-id="<?php echo orderClean($part_item_id); ?>" data-type="part">
                                                ✕
                                            </button>

                                            <input type="hidden" name="parts[]" value="<?php echo orderClean($part['name']); ?> - <?php echo orderClean($part['price']); ?>">
                                            <input type="hidden" name="parts_prices[]" value="<?php echo orderClean($part['price']); ?>">
                                        </div>
                                    <?php } ?>
                                <?php } else { ?>
                                    <div id="parts_empty" class="order-cart-empty">
                                        Запчасти пока не выбраны
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <!--КОРЗИНА ЗАПЧАСТЕЙ-->

                    <div class="uk-margin-small-top">
                        <label for="selected_price">Стоимость работ</label>
                        <input class="uk-input" id="selected_price" type="text" name="selected_price" value="<?php echo orderClean($order['works_price']); ?>" autocomplete="off" required readonly>
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="parts_price">Стоимость запчастей</label>
                        <input class="uk-input" id="parts_price" type="text" name="parts_price" value="<?php echo orderClean($order['parts_price']); ?>" autocomplete="off" required readonly>
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="total_price">Общая стоимость</label>
                        <input class="uk-input" id="total_price" type="text" name="total_price" value="<?php echo orderClean($order['total_price']); ?>" autocomplete="off" required readonly>
                    </div>
                    <div class="uk-margin-small-top">
                        <label for="payment_type">Вид платежа</label>
                        <select class="uk-select" id="payment_type" name="payment_type" required>
                            <option value="Наличный расчет" <?php if ($order['payment_type'] == 'Наличный расчет') echo 'selected'; ?>>Наличный расчет</option>
                            <option value="Безналичный расчет" <?php if ($order['payment_type'] == 'Безналичный расчет') echo 'selected'; ?>>Безналичный расчет</option>
                        </select>
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="order_status">Статус заявки</label>
                        <select class="uk-select" id="order_status" name="order_status" required>
                            <option value="Новая" <?php if ($order['status'] == 'Новая') echo 'selected'; ?>>Новая</option>
                            <option value="В работе" <?php if ($order['status'] == 'В работе') echo 'selected'; ?>>В работе</option>
                            <option value="Ожидает запчасти" <?php if ($order['status'] == 'Ожидает запчасти') echo 'selected'; ?>>Ожидает запчасти</option>
                            <option value="Завершена" <?php if ($order['status'] == 'Завершена') echo 'selected'; ?>>Завершена</option>
                            <option value="Отменена" <?php if ($order['status'] == 'Отменена') echo 'selected'; ?>>Отменена</option>
                        </select>
                    </div>

                    <div class="uk-margin-small-top uk-flex uk-flex-column">
                        <button type="submit" class="uk-margin-small-top uk-button uk-button-default" name="save_order_changes" value="1">
                            Изменить
                        </button>
                        <br>
                        <button type="button" class="uk-button uk-button-default order-print-btn" name="print_order">
                            Распечатать
                        </button>
                    </div>
                </form>

            </div>
        </div>
        
    </div>
</div>

<?php   
}
?>