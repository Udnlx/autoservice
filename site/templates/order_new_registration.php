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
    	<h1 class="uk-heading-hero uk-text-center">Новый заказ-наряд Регистрация</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php    
} else {

    $selected_date = !empty($_POST['selected_date'])?$_POST['selected_date']:NULL;  
    $selected_worker = !empty($_POST['selected_worker'])?$_POST['selected_worker']:NULL;
    $client = !empty($_POST['client'])?$_POST['client']:NULL;
    $car = !empty($_POST['car'])?$_POST['car']:NULL;

    $works = $_POST['works'] ?? [];
    $works_prices = $_POST['works_prices'] ?? [];
    $parts = $_POST['parts'] ?? [];
    $parts_prices = $_POST['parts_prices'] ?? [];
    $parts_ids = $_POST['parts_ids'] ?? [];

    $works_price = !empty($_POST['works_price'])?$_POST['works_price']:NULL;
    $parts_price = !empty($_POST['parts_price'])?$_POST['parts_price']:NULL;
    $total_price = !empty($_POST['total_price'])?$_POST['total_price']:NULL;
    $payment_type = !empty($_POST['payment_type'])?$_POST['payment_type']:NULL;

    $client_page = $pages->get("template=owner, id=" . $client);
    $client_name = $client_page->title;

    $car_page = $pages->get("template=car, id=" . $car);
    $car_name = $car_page->title;

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
            'price' => $parts_prices[$index] ?? 0,
            'part_id' => (int)($parts_ids[$index] ?? 0)
        ];
    }

    $order = [
        'date' => $selected_date,
        'worker' => $selected_worker,
        'client' => $client_name,
        'car' => $car_name,
        'status' => 'Новая',
        'payment_type' => $payment_type,

        'works_price' => $works_price ?? 0,
        'parts_price' => $parts_price ?? 0,
        'total_price' => $total_price ?? 0,

        'works' => $order_works,
        'parts' => $order_parts
    ];

    // echo '<pre>';
    // print_r($order);
    // echo '</pre>';

	if ($selected_worker && $client && $car && $total_price && $payment_type && $operator != 'no_operator') {
        $orders_page = $pages->get('template=orders');
        // $orderPage = $pages->get('id=1106');
        $orderPage = $pages->add('order_item', $orders_page);

        $orderPage->of(false);
        $orderPage->title = 'Заказ ' . date('ymd-His');
        $orderPage->date_order = $order['date'];
        $orderPage->status_order = $order['status'];
        $orderPage->operator = $order['worker'];
        $orderPage->client = $order['client'];
        $orderPage->auto = $order['car'];
        $orderPage->payment_type = $order['payment_type'];
        $orderPage->cost_works = $order['works_price'];
        $orderPage->cost_autoparts = $order['parts_price'];
        $orderPage->cost_total = $order['total_price'];
        $orderPage->save();

        //ДОБАВЛЯЕМ РАБОТЫ
        // очистить
        foreach($orderPage->works as $oldItem) {
            $orderPage->works->remove($oldItem);
            $oldItem->delete();
        }
        // на всякий случай снова выключаем formatting
        $orderPage->of(false);
        foreach($order['works'] as $row) {
            $item = $orderPage->works->getNew();
            $item->of(false);
            $item->work = $row['name'];
            $item->price = $row['price'];
            $item->save();
            $orderPage->works->add($item);
        }
        // еще раз перед сохранением
        $orderPage->of(false);
        $orderPage->save('works');
        //ДОБАВЛЯЕМ РАБОТЫ

        //ДОБАВЛЯЕМ ЗАПЧАСТИ
        // очистить
        foreach($orderPage->autoparts as $oldItem) {
            $orderPage->autoparts->remove($oldItem);
            $oldItem->delete();
        }
        // на всякий случай снова выключаем formatting
        $orderPage->of(false);
        foreach($order['parts'] as $row) {
            $item = $orderPage->autoparts->getNew();
            $item->of(false);
            $item->autopart = $row['name'];
            $item->price = $row['price'];
            $item->part_id = (int)$row['part_id'];
            $item->save();
            $orderPage->autoparts->add($item);
        }
        // еще раз перед сохранением
        $orderPage->of(false);
        $orderPage->save('autoparts');
        //ДОБАВЛЯЕМ ЗАПЧАСТИ

        //СПИСАНИЕ ЗАПЧАСТЕЙ СО СКЛАДА
        $parts_ids = $_POST['parts_ids'] ?? [];

        foreach ($parts_ids as $pid) {
            $pid = (int)$pid;
            if ($pid <= 0) continue;

            $partPage = $pages->get("id=$pid, template=part");
            if (!$partPage->id) continue;

            $partPage->of(false);
            $current_qty = (int)$partPage->part_qty;
            $partPage->part_qty = max(0, $current_qty - 1); // не уходим в минус
            $partPage->save('part_qty');
        }
        //СПИСАНИЕ ЗАПЧАСТЕЙ СО СКЛАДА

        $orderPage->save();
        $success = 'Заказ успешно зарегистрирован';
        $session->redirect('/zakaz-prosmotr/?idorder=' . $orderPage->id);
	} else {
        $success = 'Заказ не зарегистрирован!<br>Ошибка в данных';
    }

    $info = '';
    if ($success == 'Заказ успешно зарегистрирован') {
        $info .= '
            <p class="uk-margin-remove">Заказ успешно зарегистрирован?<br>Вы можете перейти на страницу заказа</p>
        ';
    } else {
        $info .= '
            <p class="uk-margin-remove">Заказ не зарегистрирован?<br>Произошла ошибка, возможно некорректные данные или ошибка.<br>Попробкйте позже или обратитесь в техподдержку</p>
            <a class="uk-margin-small-top uk-button uk-button-default" href="/">Перейти на главную</a>
        ';
    }

}

?>

<div id="content">
	<h1 class="uk-margin-remove uk-heading-hero uk-text-center"><?php echo $success; ?></h1>
	<div>

        <div>
            <div class="pagemenu uk-width-1-1 uk-flex">
                <a class="menu-link" href="/">На главную</a>
            </div>
        </div>

        <div>
            <div class="uk-card uk-card-default uk-card-body uk-flex uk-flex-column">
                <?php echo $info; ?>
            </div>
        </div>
        
    </div>
</div>