<?php namespace ProcessWire;

if (isset($_SESSION['operator'])) {
    $operator = $_SESSION['operator'];
} else {
    $operator = 'no_operator';
}

if ($operator == 'no_operator') {
?>
    <div id="content" style="max-width: 700px;">
        <h1 class="uk-heading-hero uk-text-center">Изменение заказа</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php
} else {

    $order_id = !empty($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

    $works = $_POST['works'] ?? [];
    $works_prices = $_POST['works_prices'] ?? [];
    $parts = $_POST['parts'] ?? [];
    $parts_prices = $_POST['parts_prices'] ?? [];

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
            'name' => $part_name,
            'price' => $parts_prices[$index] ?? 0
        ];
    }

    $success = false;

    if ($order_id && $payment_type && $order_status) {

        $orderPage = $pages->get("id=$order_id, template=order_item");

        if ($orderPage->id) {

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
                $item->price = $row['price'];
                $item->save();
                $orderPage->autoparts->add($item);
            }
            $orderPage->of(false);
            $orderPage->save('autoparts');
            //ОБНОВЛЯЕМ ЗАПЧАСТИ

            $orderPage->save();
            $success = true;
        }
    }

    if ($success) {
        $session->redirect('/zakaz-prosmotr/?idorder=' . $order_id . '&saved=1');
    } else {
        $session->redirect('/zakaz-prosmotr/?idorder=' . $order_id . '&saved=0');
    }
}
?>