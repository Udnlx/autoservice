<?php namespace ProcessWire;

if (isset($_SESSION['operator'])) {
    $operator = $_SESSION['operator'];
} else {
    $operator = 'no_operator';
}

if ($operator == 'no_operator') {
?>
    <div id="content" style="max-width: 700px;">
        <h1 class="uk-heading-hero uk-text-center">Движение заказов</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php
} else {

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

    $search_client = trim($input->get->text('search_client'));
    $search_auto = trim($input->get->text('search_auto'));

    $orders_page = $pages->get('template=orders');

    $selector = "template=order_item, parent=$orders_page, sort=-created";

    $is_search = false;

    if ($search_client !== '' || $search_auto !== '') {
        $is_search = true;

        if ($search_client !== '') {
            $selector .= ", client%=" . $sanitizer->selectorValue($search_client);
        }

        if ($search_auto !== '') {
            $selector .= ", auto%=" . $sanitizer->selectorValue($search_auto);
        }
    } else {
        $selector .= ", limit=30";
    }

    $orders_list = $pages->find($selector);

?>

<div id="content">
    <h1 class="uk-margin-remove uk-heading-hero uk-text-center">Движение заказов</h1>
    <div>

        <div>
            <div class="pagemenu uk-width-1-1 uk-flex">
                <a class="menu-link" href="/">На главную</a>
                <a class="menu-link" href="/zakaz-novyi/">Новая заявка</a>
            </div>
        </div>

        <div>
            <div class="uk-card uk-card-default uk-card-body uk-flex uk-flex-column">

                <div class="order-view-top">
                    <div>
                        <div class="order-view-number">Список заказов</div>
                        <div class="order-view-subtitle">
                            <?php if ($is_search) { ?>
                                Результаты поиска: найдено <?php echo count($orders_list); ?>
                            <?php } else { ?>
                                Последние 30 заявок
                            <?php } ?>
                        </div>
                    </div>

                    <div class="order-status-badge">
                        Всего: <?php echo count($orders_list); ?>
                    </div>
                </div>

                <!--ФОРМА ПОИСКА-->
                <form class="uk-flex uk-flex-column uk-margin-small-top" id="orders_search_form" action="" method="get">
                    <div class="order-view-grid">
                        <div>
                            <label for="search_client">Клиент</label>
                            <input class="uk-input" id="search_client" type="text" name="search_client" value="<?php echo orderClean($search_client); ?>" placeholder="Введите имя клиента" autocomplete="off">
                        </div>

                        <div>
                            <label for="search_auto">Автомобиль</label>
                            <input class="uk-input" id="search_auto" type="text" name="search_auto" value="<?php echo orderClean($search_auto); ?>" placeholder="Введите автомобиль" autocomplete="off">
                        </div>
                    </div>

                    <div class="uk-flex uk-flex-column">
                        <button type="submit" class="uk-button uk-button-default">Найти</button>

                        <?php if ($is_search) { ?>
                            <a class="uk-margin-small-top uk-button uk-button-default" href="/zakaz-dvizhenie/">Сбросить поиск</a>
                        <?php } ?>
                    </div>
                </form>
                <!--ФОРМА ПОИСКА-->

                <!--СПИСОК ЗАЯВОК-->
                <div class="uk-margin-small-top">
                    <?php if (count($orders_list)) { ?>
                        <div class="orders-list uk-flex uk-flex-column">
                            <?php foreach ($orders_list as $orderItem) { ?>
                                <a class="order-list-item <?php echo orderStatusClass($orderItem->status_order); ?>" href="/zakaz-prosmotr/?idorder=<?php echo $orderItem->id; ?>">
                                    <div class="order-list-item-top">
                                        <div class="order-list-item-title"><?php echo orderClean($orderItem->title); ?></div>
                                        <div class="order-status-badge <?php echo orderStatusClass($orderItem->status_order); ?>">
                                            <?php echo orderClean($orderItem->status_order); ?>
                                        </div>
                                    </div>

                                    <div class="order-list-item-grid">
                                        <div class="order-list-item-cell">
                                            <div class="order-list-item-label">Дата</div>
                                            <div class="order-list-item-value"><?php echo orderClean($orderItem->date_order); ?></div>
                                        </div>

                                        <div class="order-list-item-cell">
                                            <div class="order-list-item-label">Клиент</div>
                                            <div class="order-list-item-value"><?php echo orderClean($orderItem->client); ?></div>
                                        </div>

                                        <div class="order-list-item-cell">
                                            <div class="order-list-item-label">Автомобиль</div>
                                            <div class="order-list-item-value"><?php echo orderClean($orderItem->auto); ?></div>
                                        </div>

                                        <div class="order-list-item-cell">
                                            <div class="order-list-item-label">Итого</div>
                                            <div class="order-list-item-value order-list-item-total"><?php echo orderClean($orderItem->cost_total); ?> ₽</div>
                                        </div>
                                    </div>
                                </a>
                            <?php } ?>
                        </div>
                    <?php } else { ?>
                        <div class="order-cart-empty uk-text-center">
                            <?php if ($is_search) { ?>
                                По вашему запросу ничего не найдено
                            <?php } else { ?>
                                Заявок пока нет
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
                <!--СПИСОК ЗАЯВОК-->

            </div>
        </div>

    </div>
</div>

<?php
}
?>