<?php namespace ProcessWire;

if (isset($_SESSION['operator'])) {
    $operator = $_SESSION['operator'];
} else {
    $operator = 'no_operator';
}

if ($operator == 'no_operator') {
?>
    <div id="content" style="max-width: 700px;">
        <h1 class="uk-heading-hero uk-text-center">Отчёт по заказ-нарядам</h1>
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

    if (!function_exists('orderParseDate')) {
        function orderParseDate($value) {
            $value = trim((string)$value);
            if ($value === '') {
                return null;
            }

            foreach (['d-m-Y', 'd.m.Y', 'Y-m-d', 'd/m/Y'] as $fmt) {
                $dt = \DateTime::createFromFormat('!' . $fmt, $value);
                if ($dt && $dt->format($fmt) === $value) {
                    return $dt->getTimestamp();
                }
            }

            return null;
        }
    }

    if (!function_exists('reportMoney')) {
        function reportMoney($value) {
            return number_format((float)$value, 0, '.', ' ');
        }
    }

    // --- СПРАВОЧНИК ТИПОВ РАБОТ ---
    $work_type_labels = [
        1 => 'Антикор',
        2 => 'Фильтры',
        3 => 'Турбины',
    ];

    // --- КАРТА ВСЕХ РАБОТ ИЗ СПРАВОЧНИКА: [название => тип] ---
    // Один раз читаем из базы, дальше внутри цикла заявок — только массив.
    $works_catalog = [];
    foreach ($pages->find("template=work_item, include=all") as $workPage) {
        $wtObj = $workPage->work_type;
        $wt = 0;

        if ($wtObj) {
            if (is_object($wtObj) && isset($wtObj->id)) {
                $wt = (int)$wtObj->id;
            } elseif ($wtObj instanceof \ProcessWire\WireArray && count($wtObj)) {
                $wt = (int)$wtObj->first()->id;
            }
        }

        if ($wt > 0) {
            $works_catalog[(string)$workPage->title] = $wt;
        }
    }

    // --- ВХОДНЫЕ ПАРАМЕТРЫ ---
    $search_client    = trim($input->get->text('search_client'));
    $search_auto      = trim($input->get->text('search_auto'));
    $search_date_from = trim($input->get->text('search_date_from'));
    $search_date_to   = trim($input->get->text('search_date_to'));

    // rf=1 — форма была отправлена. Нужен, чтобы отличить «сняли все галочки»
    // от «страница открыта впервые»: иначе снятые статусы вернулись бы к дефолту.
    $form_submitted = ($input->get->int('rf') === 1);

    $all_statuses = ['Новая', 'В работе', 'Ожидает запчасти', 'Завершена', 'Отменена'];

    // Статусы по умолчанию — только «Завершена»
    if (!$form_submitted) {
        $active_statuses = ['Завершена'];
    } else {
        $active_statuses = [];
        foreach ((array)$input->get->array('status_filter') as $st) {
            $st = (string)$st;
            if (in_array($st, $all_statuses, true)) {
                $active_statuses[] = $st;
            }
        }
    }

    // Типы работ по умолчанию — все
    if (!$form_submitted) {
        $active_types = array_keys($work_type_labels);
    } else {
        $active_types = [];
        foreach ((array)$input->get->array('type_filter') as $tp) {
            $tp = (int)$tp;
            if (isset($work_type_labels[$tp])) {
                $active_types[] = $tp;
            }
        }
    }

    $all_types_active = (count($active_types) === count($work_type_labels));

    // --- РАЗБОР И ПРОВЕРКА ДАТ ---
    $date_from_ts = orderParseDate($search_date_from);
    $date_to_ts   = orderParseDate($search_date_to);

    $date_error = '';
    if ($search_date_from === '' && $search_date_to === '') {
        $date_error = '';   // период не задан — это не ошибка, просто ещё не искали
    } elseif ($search_date_from !== '' && $search_date_to === '') {
        $date_error = 'Укажите дату «по» — период задаётся двумя датами.';
    } elseif ($search_date_from === '' && $search_date_to !== '') {
        $date_error = 'Укажите дату «с» — период задаётся двумя датами.';
    } elseif ($date_from_ts === null) {
        $date_error = 'Дата «с» не распознана.';
    } elseif ($date_to_ts === null) {
        $date_error = 'Дата «по» не распознана.';
    } elseif ($date_from_ts > $date_to_ts) {
        $date_error = 'Дата «с» позже даты «по» — проверьте период.';
    }

    // «по» включается целиком: берём самый конец того дня
    if ($date_to_ts !== null) {
        $date_to_ts = strtotime('tomorrow', $date_to_ts) - 1;
    }

    $period_set = ($date_from_ts !== null && $date_to_ts !== null);
    $is_search  = ($period_set && $date_error === '');

    // --- ВЫБОРКА ---
    $orders_page = $pages->get('template=orders');

    $rows        = [];
    $total_sum   = 0;
    $found_count = 0;
    $shown_limit = 100;

    if ($is_search) {

        $selector = "template=order_item, parent=$orders_page";

        if ($search_client !== '') {
            $value  = $sanitizer->selectorValue($search_client);
            $digits = $sanitizer->digits($search_client);

            $ownerIds = [];
            foreach ($pages->find("template=owner, title%=$value") as $ownerItem) {
                $ownerIds[$ownerItem->id] = true;
            }
            foreach ($pages->find("template=owner, phone%=$value") as $ownerItem) {
                $ownerIds[$ownerItem->id] = true;
            }
            if (strlen($digits) >= 3) {
                foreach ($pages->find("template=owner, phone%=$digits") as $ownerItem) {
                    $ownerIds[$ownerItem->id] = true;
                }
            }

            if (count($ownerIds)) {
                $selector .= ", client=" . implode('|', array_keys($ownerIds));
            } else {
                $selector .= ", id=-1";
            }
        }

        if ($search_auto !== '') {
            $selector .= ", auto%=" . $sanitizer->selectorValue($search_auto);
        }

        // Нет выбранных статусов или типов — отчёт пуст по определению
        if (empty($active_statuses) || empty($active_types)) {
            $selector .= ", id=-1";
        }

        $found = $pages->find($selector);

        // --- ОТБОР В PHP: статусы, период, типы работ ---
        // status_order и date_order — не те поля, по которым селектор сравнивает надёжно:
        // первое Select Options, второе текст дд-мм-гггг. Поэтому и то, и другое
        // проверяем здесь, а не в селекторе.
        foreach ($found as $candidate) {

            $row_status = (string)$candidate->status_order;
            if (!in_array($row_status, $active_statuses, true)) {
                continue;
            }

            $row_ts = orderParseDate($candidate->date_order);
            if ($row_ts === null) {
                continue;   // дата не разобралась — в период не попадает
            }
            if ($row_ts < $date_from_ts || $row_ts > $date_to_ts) {
                continue;
            }

            // Типы работ заявки
            $row_type_ids = [];
            foreach ($candidate->works as $workRow) {
                $wname = (string)$workRow->work;
                if ($wname === '' || !isset($works_catalog[$wname])) {
                    continue;
                }
                $row_type_ids[(int)$works_catalog[$wname]] = true;
            }

            if (!$all_types_active) {
                $type_ok = false;
                foreach (array_keys($row_type_ids) as $tid) {
                    if (in_array($tid, $active_types, true)) {
                        $type_ok = true;
                        break;
                    }
                }
                if (!$type_ok) {
                    continue;   // ни одной работы нужного типа
                }
            }

            // --- ПРИБЫЛЬ ПО ЗАПЧАСТЯМ ---
            // (цена клиента − закупка) × количество. Пустая и нулевая закупка
            // считаются нулём — тогда вся цена клиента идёт в прибыль.
            // Строки без part_id и с удалённой из справочника запчастью не считаем вовсе.
            $parts_profit  = 0;
            $parts_skipped = 0;

            foreach ($candidate->autoparts as $partRow) {
                $pid = (int)$partRow->part_id;

                if ($pid <= 0) {
                    $parts_skipped++;
                    continue;
                }

                $partRef = $pages->get("id=$pid, template=part");
                if (!$partRef->id) {
                    $parts_skipped++;
                    continue;
                }

                $purchase = (int)$partRef->getUnformatted('part_price_purchase');
                $sale     = (int)$partRow->price;
                $qty      = max(1, (int)$partRow->qty);

                $parts_profit += ($sale - $purchase) * $qty;
            }

            $works_sum = (int)$candidate->cost_works;

            $rows[] = [
                'page'          => $candidate,
                'ts'            => $row_ts,
                'status'        => $row_status,
                'types'         => array_keys($row_type_ids),
                'parts_profit'  => $parts_profit,
                'parts_skipped' => $parts_skipped,
                'profit_total'  => $works_sum + $parts_profit
            ];
        }

        // --- СОРТИРОВКА ПО ДАТЕ ЗАКАЗА (новые сверху) ---
        usort($rows, function ($a, $b) {
            if ($a['ts'] === $b['ts']) {
                return $b['page']->id - $a['page']->id;   // равные даты — по id
            }
            return $b['ts'] - $a['ts'];
        });

        $found_count = count($rows);

        // Итоги считаем по ВСЕМ найденным, до обрезки до 100
        $works_sum_all        = 0;
        $parts_cost_sum_all   = 0;
        $parts_profit_sum_all = 0;
        $profit_sum_all       = 0;
        $skipped_rows_total   = 0;

        foreach ($rows as $row) {
            $total_sum            += (float)$row['page']->cost_total;
            $works_sum_all        += (float)$row['page']->cost_works;
            $parts_cost_sum_all   += (float)$row['page']->cost_autoparts;
            $parts_profit_sum_all += (float)$row['parts_profit'];
            $profit_sum_all       += (float)$row['profit_total'];
            $skipped_rows_total   += (int)$row['parts_skipped'];
        }

        if ($found_count > $shown_limit) {
            $rows = array_slice($rows, 0, $shown_limit);
        }
    }

?>

<div id="content">
    <h1 class="uk-margin-remove uk-heading-hero uk-text-center">Отчёт по заказ-нарядам</h1>
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
                        <div class="order-view-number">Отчёт по заказ-нарядам</div>
                        <div class="order-view-subtitle">
                            <?php if ($is_search) { ?>
                                Найдено заявок: <?php echo (int)$found_count; ?>
                                <?php if ($found_count > $shown_limit) { ?>
                                    · показаны первые <?php echo (int)$shown_limit; ?>
                                <?php } ?>
                            <?php } else { ?>
                                Задайте период и нажмите «Сформировать отчёт»
                            <?php } ?>
                        </div>
                    </div>

                    <div class="order-status-badge">
                        <span style="margin-right:5px;">В отчёте:</span><span id="report_rows_count"><?php echo $is_search ? (int)count($rows) : 0; ?></span>
                    </div>
                </div>

                <!--ФОРМА ОТЧЁТА-->
                <form class="uk-flex uk-flex-column uk-margin-small-top" id="report_form" action="" method="get">
                    <input type="hidden" name="rf" value="1">

                    <?php if ($date_error !== '') { ?>
                        <div class="uk-alert-danger uk-margin-small-top" uk-alert>
                            <a class="uk-alert-close" uk-close></a>
                            <p class="uk-margin-remove"><?php echo orderClean($date_error); ?></p>
                        </div>
                    <?php } ?>

                    <div class="order-view-grid">
                        <div>
                            <label for="search_client">Клиент</label>
                            <input class="uk-input" id="search_client" type="text" name="search_client" value="<?php echo orderClean($search_client); ?>" placeholder="Введите имя клиента или номер телефона" autocomplete="off">
                        </div>

                        <div>
                            <label for="search_auto">Автомобиль</label>
                            <input class="uk-input" id="search_auto" type="text" name="search_auto" value="<?php echo orderClean($search_auto); ?>" placeholder="Введите автомобиль" autocomplete="off">
                        </div>

                        <div>
                            <label for="search_date_from">Дата заказа с <span style="color:#c00;">*</span></label>
                            <input class="uk-input" id="search_date_from" type="date" name="search_date_from" value="<?php echo $date_from_ts ? date('Y-m-d', $date_from_ts) : ''; ?>" required>
                        </div>

                        <div>
                            <label for="search_date_to">Дата заказа по <span style="color:#c00;">*</span></label>
                            <input class="uk-input" id="search_date_to" type="date" name="search_date_to" value="<?php echo $date_to_ts ? date('Y-m-d', $date_to_ts) : ''; ?>" required>
                        </div>
                    </div>

                    <div class="uk-margin-small-top" style="padding:12px; background:#f8f8f8; border-radius:4px;">
                        <div style="display:flex; gap:28px; flex-wrap:wrap;">

                            <!-- КОЛОНКА 1: СТАТУСЫ -->
                            <div style="flex:1; min-width:190px;">
                                <div style="font-weight:600; margin-bottom:8px;">Статусы заявок:</div>

                                <?php foreach ($all_statuses as $st) { ?>
                                    <label style="display:block; margin-bottom:6px; cursor:pointer;">
                                        <input type="checkbox" name="status_filter[]" value="<?php echo orderClean($st); ?>"<?php echo in_array($st, $active_statuses, true) ? ' checked' : ''; ?>>
                                        <?php echo orderClean($st); ?>
                                    </label>
                                <?php } ?>
                            </div>

                            <!-- КОЛОНКА 2: ТИПЫ РАБОТ -->
                            <div style="flex:1; min-width:190px;">
                                <div style="font-weight:600; margin-bottom:8px;">Типы работ:</div>

                                <?php foreach ($work_type_labels as $tid => $tlabel) { ?>
                                    <label class="filter-type-label">
                                        <input type="checkbox" name="type_filter[]" value="<?php echo (int)$tid; ?>"<?php echo in_array((int)$tid, $active_types, true) ? ' checked' : ''; ?>>
                                        <span class="order-type-badge type-<?php echo (int)$tid; ?>"><?php echo orderClean($tlabel); ?></span>
                                    </label>
                                <?php } ?>

                                <div style="margin-top:8px; font-size:0.85em; color:#999;">
                                    Сняты все типы — отчёт пуст
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="uk-flex uk-flex-column">
                        <button type="submit" class="uk-margin-small-top uk-button uk-button-default">Сформировать отчёт</button>

                        <?php if ($form_submitted) { ?>
                            <a class="uk-margin-small-top uk-button uk-button-default" href="/otchet-po-zakaz-nariadam/">Сбросить</a>
                        <?php } ?>
                    </div>
                </form>
                <!--ФОРМА ОТЧЁТА-->

                <!--РЕЗУЛЬТАТЫ-->
                <div class="uk-margin-small-top">
                    <?php if ($is_search && count($rows)) { ?>

                        <style>
                        .report-scroll {
                            max-height: 70vh;
                            overflow: auto;
                        }
                        .report-table thead th {
                            position: sticky;
                            top: 0px;
                            z-index: 5;
                            background: #f0f0f0;
                            box-shadow: inset 0 0 0 1px #ddd, 0 1px 0 #bbb;
                        }
                        </style>

                        <div class="report-scroll">
                            <table class="report-table" style="width:100%; border-collapse:collapse; font-size:0.85em;">
                                <thead>
                                    <tr style="background:#f0f0f0;">
                                        <th style="border:0px solid #ddd; padding:6px 8px; text-align:center;">№</th>
                                        <th style="border:0px solid #ddd; padding:6px 8px; text-align:center;">Дата</th>
                                        <th style="border:0px solid #ddd; padding:6px 8px; text-align:center;">Заказ</th>
                                        <th style="border:0px solid #ddd; padding:6px 8px; text-align:center;">Клиент</th>
                                        <th style="border:0px solid #ddd; padding:6px 8px; text-align:center;">Автомобиль</th>
                                        <th style="border:0px solid #ddd; padding:6px 8px; text-align:center;">Типы работ</th>
                                        <th style="border:0px solid #ddd; padding:6px 8px; text-align:center;">Работы</th>
                                        <th style="border:0px solid #ddd; padding:6px 8px; text-align:center;">Запчасти</th>
                                        <th style="border:0px solid #ddd; padding:6px 8px; text-align:center;">Итого</th>
                                        <th style="border:0px solid #ddd; padding:6px 8px; text-align:center;">Запчасти прибыль</th>
                                        <th style="border:0px solid #ddd; padding:6px 8px; text-align:center;">Итого прибыль</th>
                                        <th style="border:0px solid #ddd; padding:6px 8px; text-align:center;">Статус</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $n = 0; foreach ($rows as $row) { ?>
                                        <?php $rowPage = $row['page']; $n++; ?>
                                        <tr>
                                            <td style="border:1px solid #ddd; padding:6px 8px;"><?php echo $n; ?></td>
                                            <td style="border:1px solid #ddd; padding:6px 8px; white-space:nowrap;"><?php echo orderClean($rowPage->date_order); ?></td>
                                            <td style="border:1px solid #ddd; padding:6px 8px; white-space:nowrap;">
                                                <a href="/zakaz-prosmotr/?idorder=<?php echo (int)$rowPage->id; ?>"><?php echo orderClean($rowPage->title); ?></a>
                                            </td>
                                            <td style="border:1px solid #ddd; padding:6px 8px;"><?php echo $rowPage->client->id ? orderClean($rowPage->client->title) : '—'; ?></td>
                                            <td style="border:1px solid #ddd; padding:6px 8px;"><?php echo orderClean($rowPage->auto); ?></td>
                                            <td style="border:1px solid #ddd; padding:6px 8px;">
                                                <?php foreach ($row['types'] as $tid) { ?>
                                                    <span class="order-type-badge type-<?php echo (int)$tid; ?>"><?php echo orderClean($work_type_labels[$tid]); ?></span>
                                                <?php } ?>
                                            </td>
                                            <td style="border:1px solid #ddd; padding:6px 8px; text-align:right; white-space:nowrap;"><?php echo reportMoney($rowPage->cost_works); ?> ₽</td>
                                            <td style="border:1px solid #ddd; padding:6px 8px; text-align:right; white-space:nowrap; color:#777;"><?php echo reportMoney($rowPage->cost_autoparts); ?> ₽</td>
                                            <td style="border:1px solid #ddd; padding:6px 8px; text-align:right; white-space:nowrap; color:#777;"><?php echo reportMoney($rowPage->cost_total); ?> ₽</td>
                                            <td style="border:1px solid #ddd; padding:6px 8px; text-align:right; white-space:nowrap;<?php echo $row['parts_profit'] < 0 ? ' color:#c00;' : ''; ?>">
                                                <?php echo reportMoney($row['parts_profit']); ?> ₽
                                                <?php if ($row['parts_skipped'] > 0) { ?>
                                                    <span title="Строк без привязки к справочнику: <?php echo (int)$row['parts_skipped']; ?> — в прибыль не вошли" style="color:#e8a33d; cursor:help;">⚠</span>
                                                <?php } ?>
                                            </td>
                                            <td style="border:1px solid #ddd; padding:6px 8px; text-align:right; white-space:nowrap; font-weight:700;<?php echo $row['profit_total'] < 0 ? ' color:#c00;' : ''; ?>"><?php echo reportMoney($row['profit_total']); ?> ₽</td>
                                            <td style="border:1px solid #ddd; padding:6px 8px; white-space:nowrap;">
                                                <span class="order-status-badge <?php echo orderStatusClass($row['status']); ?>"><?php echo orderClean($row['status']); ?></span>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                                <tfoot>
                                    <tr style="background:#f8f8f8; font-weight:700;">
                                        <td colspan="6" style="border:1px solid #ddd; padding:8px; text-align:right;">
                                            ИТОГО по <?php echo (int)$found_count; ?> заявкам<?php echo $found_count > $shown_limit ? ' (за все найденные)' : ''; ?>:
                                        </td>
                                        <td style="border:1px solid #ddd; padding:8px; text-align:right; white-space:nowrap;"><?php echo reportMoney($works_sum_all); ?> ₽</td>
                                        <td style="border:1px solid #ddd; padding:8px; text-align:right; white-space:nowrap; color:#777;"><?php echo reportMoney($parts_cost_sum_all); ?> ₽</td>
                                        <td style="border:1px solid #ddd; padding:8px; text-align:right; white-space:nowrap; color:#777;"><?php echo reportMoney($total_sum); ?> ₽</td>
                                        <td style="border:1px solid #ddd; padding:8px; text-align:right; white-space:nowrap;<?php echo $parts_profit_sum_all < 0 ? ' color:#c00;' : ''; ?>"><?php echo reportMoney($parts_profit_sum_all); ?> ₽</td>
                                        <td style="border:1px solid #ddd; padding:8px; text-align:right; white-space:nowrap; font-size:1.15em;<?php echo $profit_sum_all < 0 ? ' color:#c00;' : ''; ?>"><?php echo reportMoney($profit_sum_all); ?> ₽</td>
                                        <td style="border:1px solid #ddd; padding:8px;"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <?php if ($found_count > $shown_limit) { ?>
                            <div class="uk-alert-warning uk-margin-small-top" uk-alert>
                                <p class="uk-margin-remove">
                                    Найдено <?php echo (int)$found_count; ?> заявок — показаны первые <?php echo (int)$shown_limit; ?>.
                                    Итоговая сумма посчитана по всем найденным. Сузьте период, чтобы увидеть остальные.
                                </p>
                            </div>
                        <?php } ?>

                    <?php } elseif (!$is_search) { ?>
                        <div class="order-cart-empty uk-text-center">
                            <?php if ($date_error !== '') { ?>
                                Исправьте период — и нажмите «Сформировать отчёт»
                            <?php } else { ?>
                                Укажите период дат, чтобы сформировать отчёт
                            <?php } ?>
                        </div>
                    <?php } else { ?>
                        <div class="order-cart-empty uk-text-center">
                            По заданным условиям заявок не найдено
                        </div>
                    <?php } ?>
                </div>
                <!--РЕЗУЛЬТАТЫ-->

                <div class="uk-margin-small-top uk-flex uk-flex-column">
                    <?php if ($is_search && count($rows)) { ?>
                        <a class="uk-margin-small-top uk-button uk-button-default" href="/otchet-po-zakaz-nariadam-pechat/?<?php echo orderClean($_SERVER['QUERY_STRING'] ?? ''); ?>">Скачать отчёт в CSV</a>
                    <?php } ?>
                    <a class="uk-margin-small-top uk-button uk-button-default" href="/">Перейти на главную</a>
                </div>

            </div>
        </div>

    </div>
</div>

<?php
}
?>