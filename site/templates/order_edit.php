<?php namespace ProcessWire;

if (isset($_SESSION['operator'])) {
    $operator = $_SESSION['operator'];
} else {
    $operator = 'no_operator';
}

if ($operator == 'no_operator') {
?>
    <div id="content" style="max-width: 700px;">
        <h1 class="uk-heading-hero uk-text-center">Изменение заказ-наряда</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php
} else {

    // Чистим прошлое сообщение, чтобы оно не всплыло повторно
    $session->remove('order_stock_errors');

    $order_id = !empty($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

    $works = $_POST['works'] ?? [];
    $works_prices = $_POST['works_prices'] ?? [];
    $parts = $_POST['parts'] ?? [];
    $parts_prices = $_POST['parts_prices'] ?? [];
    $parts_ids = $_POST['parts_ids'] ?? [];

    $works_price = !empty($_POST['works_price']) ? $_POST['works_price'] : 0;
    $parts_price = !empty($_POST['parts_price']) ? $_POST['parts_price'] : 0;
    $total_price = !empty($_POST['total_price']) ? $_POST['total_price'] : 0;
    $payment_type = !empty($_POST['payment_type']) ? $_POST['payment_type'] : NULL;
    $order_status = !empty($_POST['order_status']) ? $_POST['order_status'] : NULL;

    $order_works = [];
    foreach ($works as $index => $work_name) {
        $order_works[] = [
            'name' => $work_name,
            'price' => $works_prices[$index] ?? 0
        ];
    }

    $order_parts = [];
    foreach ($parts as $index => $part_name) {
        $order_parts[] = [
            'name'    => $part_name,
            'price'   => $parts_prices[$index] ?? 0,
            'part_id' => (int)($parts_ids[$index] ?? 0)
        ];
    }

    // Сколько раз каждая запчасть встречается в НОВОЙ корзине
    $new_part_counts = [];
    foreach ($order_parts as $row) {
        $pid = (int)$row['part_id'];
        if ($pid > 0) {
            $new_part_counts[$pid] = ($new_part_counts[$pid] ?? 0) + 1;
        }
    }

    $success = false;
    $stock_errors = [];

    if ($order_id && $payment_type && $order_status) {

        $orderPage = $pages->get("id=$order_id, template=order_item");

        if ($orderPage->id) {

            // --- СЛЕПОК ЗАКАЗА ДО ПРАВКИ ---
            // Читаем ДО того, как повторитель будет перезаписан
            $old_part_counts = [];
            foreach ($orderPage->autoparts as $oldItem) {
                $pid = (int)$oldItem->part_id;
                if ($pid > 0) {
                    $old_part_counts[$pid] = ($old_part_counts[$pid] ?? 0) + 1;
                }
            }
            // --- /СЛЕПОК ЗАКАЗА ДО ПРАВКИ ---

            // Список всех запчастей, которых коснулась правка
            $touched_ids = array_unique(array_merge(
                array_keys($old_part_counts),
                array_keys($new_part_counts)
            ));

            // --- ПРОВЕРКА НАЛИЧИЯ НА СКЛАДЕ (до любых сохранений) ---
            foreach ($touched_ids as $pid) {
                $before = $old_part_counts[$pid] ?? 0;
                $after  = $new_part_counts[$pid] ?? 0;
                $delta  = $after - $before;   // >0 добавляем, <0 убираем

                if ($delta <= 0) {
                    continue;   // убираем или ничего не меняем — склад не нужен
                }

                $partPage = $pages->get("id=$pid, template=part");
                if (!$partPage->id) {
                    continue;   // запчасть удалена из справочника — остаток неизвестен
                }

                $qty_now = (int)$partPage->part_qty;

                if ($qty_now < $delta) {
                    $stock_errors[] = [
                        'name' => (string)$partPage->title,
                        'need' => $delta,
                        'have' => $qty_now
                    ];
                }
            }
            // --- /ПРОВЕРКА НАЛИЧИЯ НА СКЛАДЕ ---

            if (empty($stock_errors)) {

                $orderPage->of(false);
                $orderPage->status_order = $order_status;
                $orderPage->payment_type = $payment_type;
                $orderPage->cost_works = $works_price;
                $orderPage->cost_autoparts = $parts_price;
                $orderPage->cost_total = $total_price;
                $orderPage->save();

                //ОБНОВЛЯЕМ РАБОТЫ
                foreach ($orderPage->works as $oldItem) {
                    $orderPage->works->remove($oldItem);
                    $oldItem->delete();
                }
                $orderPage->of(false);
                foreach ($order_works as $row) {
                    $item = $orderPage->works->getNew();
                    $item->of(false);
                    $item->work = $row['name'];
                    $item->price = $row['price'];
                    $item->save();
                    $orderPage->works->add($item);
                }
                $orderPage->of(false);
                $orderPage->save('works');
                //ОБНОВЛЯЕМ РАБОТЫ

                //ОБНОВЛЯЕМ ЗАПЧАСТИ
                foreach ($orderPage->autoparts as $oldItem) {
                    $orderPage->autoparts->remove($oldItem);
                    $oldItem->delete();
                }
                $orderPage->of(false);
                foreach ($order_parts as $row) {
                    $item = $orderPage->autoparts->getNew();
                    $item->of(false);
                    $item->autopart = $row['name'];
                    $item->price    = $row['price'];
                    $item->part_id  = (int)$row['part_id'];
                    $item->save();
                    $orderPage->autoparts->add($item);
                }
                $orderPage->of(false);
                $orderPage->save('autoparts');
                //ОБНОВЛЯЕМ ЗАПЧАСТИ

                $orderPage->save();

                // --- ДВИЖЕНИЕ ПО СКЛАДУ ---
                foreach ($touched_ids as $pid) {
                    $before = $old_part_counts[$pid] ?? 0;
                    $after  = $new_part_counts[$pid] ?? 0;
                    $delta  = $after - $before;   // >0 добавили в заказ, <0 убрали

                    if ($delta === 0) {
                        continue;
                    }

                    $partPage = $pages->get("id=$pid, template=part");
                    if (!$partPage->id) {
                        continue;   // запчасть удалена из справочника — прибавлять некуда
                    }

                    $partPage->of(false);
                    $qty_now = (int)$partPage->part_qty;
                    $partPage->part_qty = max(0, $qty_now - $delta);
                    $partPage->save('part_qty');
                }
                // --- /ДВИЖЕНИЕ ПО СКЛАДУ ---

                $success = true;
            }
        }
    }

    // Не хватило запчастей — кладём сообщение в сессию, его покажет карточка заказа
    if (!empty($stock_errors)) {
        $session->set('order_stock_errors', [
            'order_id' => $order_id,
            'items'    => $stock_errors
        ]);
    }

    if ($success) {
        $session->redirect('/zakaz-prosmotr/?idorder=' . $order_id . '&saved=1');
    } else {
        $session->redirect('/zakaz-prosmotr/?idorder=' . $order_id . '&saved=0');
    }
}
?>