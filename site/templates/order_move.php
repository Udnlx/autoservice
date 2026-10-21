<?php namespace ProcessWire;

if (isset($_SESSION['operator'])) {
    $operator = $_SESSION['operator'];
} else {
    $operator = 'no_operator';
}

if ($operator == 'no_operator') {
?>
    <div id="content" style="max-width: 700px;">
        <h1 class="uk-heading-hero uk-text-center">Движение</h1>
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

    // --- СПРАВОЧНИК ТИПОВ РАБОТ ---
    // Хардкодим зеркально тому, что уже стоит в HTML селектах на других страницах.
    $work_type_labels = [
        1 => 'Антикор',
        2 => 'Фильтры',
        3 => 'Турбины',
    ];

    // --- КАРТА ВСЕХ РАБОТ ИЗ СПРАВОЧНИКА: [название => тип] ---
    // Один раз читаем из базы, дальше внутри цикла заявок — только массив, без запросов.
    $works_catalog = [];
    foreach ($pages->find("template=work_item, include=all") as $workPage) {
        // work_type — поле Select Options: получаем объект, у которого есть id (1/2/3)
        $wtObj = $workPage->work_type;
        $wt = 0;

        if ($wtObj) {
            if (is_object($wtObj) && isset($wtObj->id)) {
                $wt = (int)$wtObj->id;                               // одиночное значение
            } elseif ($wtObj instanceof \ProcessWire\WireArray && count($wtObj)) {
                $wt = (int)$wtObj->first()->id;                      // мультиселект — берём первое
            }
        }

        if ($wt > 0) {
            $works_catalog[(string)$workPage->title] = $wt;
        }
    }
    // --- КАРТА ВСЕХ РАБОТ ИЗ СПРАВОЧНИКА: [название => тип] ---

    $search_client = trim($input->get->text('search_client'));
    $search_auto = trim($input->get->text('search_auto'));

    $orders_page = $pages->get('template=orders');

    $selector = "template=order_item, parent=$orders_page, sort=-created";

    $is_search = false;

    if ($search_client !== '' || $search_auto !== '') {
        $is_search = true;

        if ($search_client !== '') {
            $value  = $sanitizer->selectorValue($search_client);
            $digits = $sanitizer->digits($search_client);
            // ---------- 1. Собираем имена владельцев ----------
            $ownerNames = [];
            // по телефону — как ввёл пользователь
            $byPhone = $pages->find("template=owner, phone%=$value");
            // если пусто и есть хотя бы 3 цифры — пробуем только цифрами
            if (!count($byPhone) && strlen($digits) >= 3) {
                $byPhone = $pages->find("template=owner, phone%=$digits");
            }
            foreach ($byPhone as $ownerItem) {
                if ($ownerItem->title) {
                    $ownerNames[] = $ownerItem->title;
                }
            }
            // по имени владельца — чтобы поиск по фамилии тоже работал
            foreach ($pages->find("template=owner, title%=$value") as $ownerItem) {
                if ($ownerItem->title) {
                    $ownerNames[] = $ownerItem->title;
                }
            }
            $ownerNames = array_values(array_unique($ownerNames));
            // ---------- 2. Собираем ID подходящих заявок ----------
            $orderIds = [];
            // напрямую по введённому тексту
            foreach ($pages->find("template=order_item, parent=$orders_page, client%=$value") as $orderItem) {
                $orderIds[] = $orderItem->id;
            }
            // по именам владельцев, найденных через телефон
            foreach ($ownerNames as $ownerName) {
                $nameValue = $sanitizer->selectorValue($ownerName);
                foreach ($pages->find("template=order_item, parent=$orders_page, client%=$nameValue") as $orderItem) {
                    $orderIds[] = $orderItem->id;
                }
            }
            $orderIds = array_values(array_unique($orderIds));
            // ---------- 3. Ограничение выборки ----------
            if (count($orderIds)) {
                $selector .= ", id=" . implode('|', $orderIds);
            } else {
                $selector .= ", id=-1"; // ничего не нашли — пустой результат
            }
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
    <h1 class="uk-margin-remove uk-heading-hero uk-text-center">Движение</h1>
    <div>

        <div>
            <div class="pagemenu uk-width-1-1 uk-flex">
                <a class="menu-link" href="/">На главную</a>
                <a class="menu-link" href="/zakaz-novyi/">Новый заказ-наряд</a>
            </div>
        </div>

        <div>
            <div class="uk-card uk-card-default uk-card-body uk-flex uk-flex-column">

                <div class="order-view-top">
                    <div>
                        <div class="order-view-number">Список заказ-нарядов</div>
                        <div class="order-view-subtitle">
                            <?php if ($is_search) { ?>
                                Результаты поиска: найдено <?php echo count($orders_list); ?>
                            <?php } else { ?>
                                Последние 30 записей
                            <?php } ?>
                        </div>
                    </div>

                    <div class="order-status-badge">
                        Всего: <span id="orders_visible_count_num"><?php echo count($orders_list); ?></span>
                    </div>
                </div>

                <!--ФОРМА ПОИСКА-->
                <form class="uk-flex uk-flex-column uk-margin-small-top" id="orders_search_form" action="" method="get">
                    <div class="order-view-grid">
                        <div>
                            <label for="search_client">Клиент</label>
                            <input class="uk-input" id="search_client" type="text" name="search_client" value="<?php echo orderClean($search_client); ?>" placeholder="Введите имя клиента или номер телефона" autocomplete="off">
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

                        <!-- КНОПКА ФИЛЬТРА -->
                        <button type="button" id="toggle_filter_btn" class="uk-margin-small-top uk-button uk-button-default">
                            Фильтр по статусам
                        </button>

                        <!-- ПАНЕЛЬ ФИЛЬТРА -->
                        <div id="filter_panel" style="display:none; margin-top:10px; padding:12px; background:#f8f8f8; border-radius:4px;">
                            <div style="display:flex; gap:28px; flex-wrap:wrap;">

                                <!-- КОЛОНКА 1: СТАТУСЫ -->
                                <div style="flex:1; min-width:190px;">
                                    <div style="font-weight:600; margin-bottom:8px;">Показывать статусы:</div>

                                    <label style="display:block; margin-bottom:6px; cursor:pointer;">
                                        <input type="checkbox" class="status-filter-checkbox" data-status="status-new" checked>
                                        Новая
                                    </label>

                                    <label style="display:block; margin-bottom:6px; cursor:pointer;">
                                        <input type="checkbox" class="status-filter-checkbox" data-status="status-progress" checked>
                                        В работе
                                    </label>

                                    <label style="display:block; margin-bottom:6px; cursor:pointer;">
                                        <input type="checkbox" class="status-filter-checkbox" data-status="status-waiting" checked>
                                        Ожидает запчасти
                                    </label>

                                    <label style="display:block; margin-bottom:6px; cursor:pointer;">
                                        <input type="checkbox" class="status-filter-checkbox" data-status="status-done">
                                        Завершена
                                    </label>

                                    <label style="display:block; cursor:pointer;">
                                        <input type="checkbox" class="status-filter-checkbox" data-status="status-canceled" checked>
                                        Отменена
                                    </label>
                                </div>

                                <!-- КОЛОНКА 2: ТИПЫ РАБОТ -->
                                <div style="flex:1; min-width:190px;">
                                    <div style="font-weight:600; margin-bottom:8px;">Показывать типы работ:</div>

                                    <label class="filter-type-label">
                                        <input type="checkbox" class="type-filter-checkbox" data-type="1" checked>
                                        <span class="order-type-badge type-1">Антикор</span>
                                    </label>

                                    <label class="filter-type-label">
                                        <input type="checkbox" class="type-filter-checkbox" data-type="2" checked>
                                        <span class="order-type-badge type-2">Фильтры</span>
                                    </label>

                                    <label class="filter-type-label">
                                        <input type="checkbox" class="type-filter-checkbox" data-type="3" checked>
                                        <span class="order-type-badge type-3">Турбины</span>
                                    </label>
                                </div>

                            </div>
                        </div>
                    </div>
                </form>
                <!--ФОРМА ПОИСКА-->

                <!--СПИСОК ЗАЯВОК-->
                <div class="uk-margin-small-top">
                    <?php if (count($orders_list)) { ?>
                        <div class="orders-list uk-flex uk-flex-column">
                            <?php foreach ($orders_list as $orderItem) { ?>
                                <?php
                                    // --- СБОР УНИКАЛЬНЫХ ТИПОВ РАБОТ ДЛЯ ЭТОЙ ЗАЯВКИ ---
                                    $order_type_ids = [];   // будет [1 => true, 3 => true] и т.п.

                                    if (count($orderItem->works)) {
                                        foreach ($orderItem->works as $workRow) {
                                            $name = (string)$workRow->work;
                                            if ($name === '') continue;

                                            // Работы нет в справочнике ИЛИ у неё пустой work_type → пропускаем
                                            if (!isset($works_catalog[$name])) continue;

                                            $tid = (int)$works_catalog[$name];
                                            if (!isset($work_type_labels[$tid])) continue;   // мусорный тип

                                            $order_type_ids[$tid] = true;
                                        }
                                    }
                                    // Собираем классы для карточки — пригодятся на этапе фильтра
                                    $type_classes = '';
                                    foreach ($order_type_ids as $tid => $_) {
                                        $type_classes .= ' has-type-' . $tid;
                                    }
                                ?>
                                <a class="order-list-item <?php echo orderStatusClass($orderItem->status_order); ?><?php echo $type_classes; ?>" href="/zakaz-prosmotr/?idorder=<?php echo $orderItem->id; ?>">
                                    <div class="order-list-item-top">
                                        <div class="order-list-item-title"><?php echo orderClean($orderItem->title); ?></div>
                                        <div class="order-status-badge <?php echo orderStatusClass($orderItem->status_order); ?>">
                                            <?php echo orderClean($orderItem->status_order); ?>
                                        </div>
                                    </div>

                                    <?php if (!empty($order_type_ids)) { ?>
                                        <div class="order-list-item-types">
                                            <?php foreach ($order_type_ids as $tid => $_) { ?>
                                                <span class="order-type-badge type-<?php echo (int)$tid; ?>">
                                                    <?php echo orderClean($work_type_labels[$tid]); ?>
                                                </span>
                                            <?php } ?>
                                        </div>
                                    <?php } ?>

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

                                        <div class="order-list-item-cell">
                                            <div class="order-list-item-label">Комментарий</div>
                                            <div class="order-list-item-value"><?php echo orderClean($orderItem->comment); ?></div>
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