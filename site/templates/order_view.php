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
        <h1 class="uk-heading-hero uk-text-center">Заказ-наряд</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php    
} else {

    $order_id = $input->get->int('idorder');

    if (!$order_id) {
        echo '<div id="content" style="max-width: 700px;">
            <h1 class="uk-heading-hero uk-text-center">Заказ-наряд</h1>
            <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
                <h3 class="uk-card-title uk-text-center">Не передан ID заказа</h3>
                <a class="uk-margin-small uk-button uk-button-default" href="/">На главную</a>
            </div>
        </div>';
        return;
    }

    $orderPage = $pages->get("id=$order_id, template=order_item");

    if (!$orderPage->id) {
        echo '<div id="content" style="max-width: 700px;">
            <h1 class="uk-heading-hero uk-text-center">Заказ-наряд</h1>
            <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
                <h3 class="uk-card-title uk-text-center">Заказ не найден</h3>
                <a class="uk-margin-small uk-button uk-button-default" href="/">На главную</a>
            </div>
        </div>';
        return;
    }

    if (!function_exists('orderClean')) {
        function orderClean($value) {
            return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        }
    }

    if (!function_exists('orderStatusClass')) {
        function orderStatusClass($status) {
            switch ($status) {
                case 'Новая':             return 'status-new';
                case 'В работе':          return 'status-progress';
                case 'Ожидает запчасти':  return 'status-waiting';
                case 'Завершена':         return 'status-done';
                case 'Отменена':          return 'status-canceled';
                default:                  return 'status-new';
            }
        }
    }

    $works = [];
    if (count($orderPage->works)) {
        foreach ($orderPage->works as $item) {
            $works[] = [
                'name' => $item->work,
                'price' => $item->price
            ];
        }
    }

    $parts = [];
    if (count($orderPage->autoparts)) {
        foreach ($orderPage->autoparts as $item) {
            $parts[] = [
                'name' => $item->autopart,
                'price' => $item->price,
                'part_id' => (int)$item->part_id
            ];
        }
    }

    $order = [
        'id' => $orderPage->id,
        'date' => $orderPage->date_order,
        'worker' => $orderPage->operator,
        'client' => $orderPage->client,
        'car' => $orderPage->auto,
        'status' => $orderPage->status_order,
        'payment_type' => $orderPage->payment_type,
        'works_price' => $orderPage->cost_works,
        'parts_price' => $orderPage->cost_autoparts,
        'total_price' => $orderPage->cost_total,
        'works' => $works,
        'parts' => $parts
    ];

    // $order = [
    //     'id' => $order_id,
    //     'date' => $today,
    //     'worker' => $operator,
    //     'client' => 'Клиент 1',
    //     'car' => 'Авто 1',
    //     'status' => 'Новая',
    //     'payment_type' => 'Наличный расчет',
    //     'works_price' => 2500,
    //     'parts_price' => 4400,
    //     'total_price' => 6900,
    //     'works' => [
    //         [
    //             'name' => 'Замена масла',
    //             'price' => 1500
    //         ],
    //         [
    //             'name' => 'Диагностика',
    //             'price' => 1000
    //         ]
    //     ],
    //     'parts' => [
    //         [
    //             'name' => 'Масляный фильтр',
    //             'price' => 900
    //         ],
    //         [
    //             'name' => 'Масло 5W-40',
    //             'price' => 3500
    //         ]
    //     ]
    // ];

    // Все работы для списка
    $all_works = $pages->find("parent.name=raboty, template=work_item, sort=title");

    // Все запчасти для списка
    $all_parts = $pages->find("parent.name=zapchasti, template=part, sort=title");

    // Сообщение о нехватке запчастей (приходит из order_edit.php через сессию)
    $stock_notice = $session->get('order_stock_errors');
    $session->remove('order_stock_errors');

    $has_stock_errors = false;
    $stock_error_items = [];

    if ($stock_notice && (int)($stock_notice['order_id'] ?? 0) === (int)$order['id']) {
        $has_stock_errors = true;
        $stock_error_items = $stock_notice['items'] ?? [];
    }

    // Флаги из адресной строки
    $was_cancelled = ($input->get->int('cancelled') === 1);
    $was_blocked   = ($input->get->int('blocked') === 1);

?>

<div id="content">
    <h1 class="uk-margin-remove uk-heading-hero uk-text-center"><?php echo $orderPage->title ?></h1>
    <div>

        <div>
            <div class="pagemenu uk-width-1-1 uk-flex">
                <a class="menu-link" href="/">На главную</a>
                <a class="menu-link" href="/zakaz-dvizhenie/">Движение</a>
            </div>
        </div>

        <div>
            <div class="uk-card uk-card-default uk-card-body uk-flex uk-flex-column">

                <div class="order-view-top">
                    <div>
                        <div class="order-view-number"><?php echo $orderPage->title ?></div>
                        <div class="order-view-subtitle">Карточка зарегистрированной заявки ID <?php echo orderClean($order['id']); ?></div>
                    </div>

                    <div class="order-status-badge <?php echo orderStatusClass($order['status']); ?>">
                        <?php echo orderClean($order['status']); ?>
                    </div>
                </div>

                <?php if ($has_stock_errors) { ?>
                    <div class="uk-alert-danger uk-margin-small-top" uk-alert>
                        <a class="uk-alert-close" uk-close></a>
                        <p class="uk-margin-remove"><strong>Изменения не сохранены — на складе не хватает запчастей</strong></p>
                        <ul class="uk-list uk-list-divider uk-margin-small-top uk-margin-remove-bottom">
                            <?php foreach ($stock_error_items as $err) { ?>
                                <li>
                                    <strong><?php echo orderClean($err['name']); ?></strong> —
                                    нужно добавить <?php echo (int)$err['need']; ?> шт,
                                    на складе <?php echo (int)$err['have']; ?> шт
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                <?php } elseif ($was_blocked) { ?>
                    <div class="uk-alert-danger uk-margin-small-top" uk-alert>
                        <a class="uk-alert-close" uk-close></a>
                        <p class="uk-margin-remove">Заявка «<?php echo orderClean($order['status']); ?>» — изменение невозможно</p>
                    </div>
                <?php } elseif ($was_cancelled) { ?>
                    <div class="uk-alert-success uk-margin-small-top" uk-alert>
                        <a class="uk-alert-close" uk-close></a>
                        <p class="uk-margin-remove">Заявка отменена. Запчасти возвращены на склад</p>
                    </div>
                <?php } elseif ($input->get->int('saved') === 1) { ?>
                    <div class="uk-alert-success uk-margin-small-top" uk-alert>
                        <a class="uk-alert-close" uk-close></a>
                        <p class="uk-margin-remove">Изменения сохранены</p>
                    </div>
                <?php } elseif ($input->get('saved') !== null && $input->get->int('saved') === 0) { ?>
                    <div class="uk-alert-danger uk-margin-small-top" uk-alert>
                        <a class="uk-alert-close" uk-close></a>
                        <p class="uk-margin-remove">Не удалось сохранить изменения</p>
                    </div>
                <?php } ?>

                <form class="uk-flex uk-flex-column" id="order_view_form" action="/zakaz-redaktirovanie/" method="post">
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

                        <div class="uk-flex uk-flex-middle order-add-row" style="gap: 10px; flex-wrap: wrap;">
                            <div style="flex: 1; display: flex; flex-direction: column; gap: 6px;">
                                <input
                                    class="uk-input"
                                    id="work_filter"
                                    type="text"
                                    placeholder="Фильтр по названию работы..."
                                    autocomplete="off"
                                >
                                <select class="uk-select" id="work_select">
                                    <option value="" disabled selected>Выберите работу</option>
                                    <?php foreach ($all_works as $workPage) { ?>
                                        <option value="<?php echo htmlspecialchars($workPage->title, ENT_QUOTES, 'UTF-8'); ?>" data-price="<?php echo (int)$workPage->work_price; ?>">
                                            <?php echo htmlspecialchars($workPage->title, ENT_QUOTES, 'UTF-8'); ?> — <?php echo (int)$workPage->work_price; ?> ₽
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
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
                                            </button>

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

                        <div class="uk-flex uk-flex-middle order-add-row" style="gap: 10px; flex-wrap: wrap;">
                            <div style="flex: 1; display: flex; flex-direction: column; gap: 6px;">
                                <input
                                    class="uk-input"
                                    id="part_filter"
                                    type="text"
                                    placeholder="Фильтр по названию запчасти..."
                                    autocomplete="off"
                                >
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
                            </div>
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
                                            <input type="hidden" name="parts_ids[]" value="<?php echo (int)$part['part_id']; ?>">
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
                        <label for="works_price">Стоимость работ</label>
                        <input class="uk-input" id="works_price" type="text" name="works_price" value="<?php echo orderClean($order['works_price']); ?>" autocomplete="off" required readonly>
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
                        <?php if (!in_array($order['status'], ['Отменена', 'Завершена'], true)) { ?>
                            <button type="submit" class="uk-margin-small-top uk-button uk-button-default" name="save_order_changes" value="1">
                                Изменить
                            </button>
                        <?php } else { ?>
                            <div class="uk-alert-warning uk-margin-small-top" uk-alert>
                                <p class="uk-margin-remove" style="font-weight: 700;">Заявка «<?php echo orderClean($order['status']); ?>» — изменение недоступно</p>
                            </div>
                        <?php } ?>
                        <br>
                        <button type="button" class="uk-margin-small-top uk-button uk-button-default" name="print_order">
                            Распечатать
                        </button>
                        <a class="uk-margin-small-top uk-button uk-button-default" href="/">Перейти на главную</a>
                    </div>
                </form>

            </div>
        </div>
        
    </div>
</div>

<?php   
}
?>