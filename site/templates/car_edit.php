<?php namespace ProcessWire;

if (isset($_SESSION['operator'])) {
    $operator = $_SESSION['operator'];
} else {
    $operator = 'no_operator';
}

if ($operator == 'no_operator') {
?>
    <div id="content" style="max-width: 700px;">
        <h1 class="uk-heading-hero uk-text-center">Изменение автомобиля</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php
} else {

    $car_id = !empty($_POST['car_id']) ? (int)$_POST['car_id'] : 0;

    $car_brand  = !empty($_POST['car_brand'])  ? trim($_POST['car_brand'])  : '';
    $car_model  = !empty($_POST['car_model'])  ? trim($_POST['car_model'])  : '';
    $car_number = !empty($_POST['car_number']) ? trim($_POST['car_number']) : '';
    $car_vin    = !empty($_POST['car_vin'])    ? trim($_POST['car_vin'])    : '';
    $car_year   = !empty($_POST['car_year'])   ? trim($_POST['car_year'])   : '';
    $car_owner  = !empty($_POST['car_owner'])  ? (int)$_POST['car_owner']  : 0;
    $car_notes  = !empty($_POST['car_notes'])  ? trim($_POST['car_notes'])  : '';

    $success = false;

    if ($car_id && $car_brand && $car_model && $car_number && $car_vin && $car_year) {

        $carPage = $pages->get("id=$car_id, template=car");

        if ($carPage->id) {

            //СОБИРАЕМ TITLE В ФОРМАТЕ: Renault Sandero (ОХ123Х58) (VIN:234567) 2022
            $new_title = $car_brand . ' ' . $car_model
                . ' (' . $car_number . ')'
                . ' (VIN:' . $car_vin . ')'
                . ' ' . $car_year;

            $carPage->of(false);
            $carPage->title      = $new_title;
            $carPage->car_brand  = $car_brand;
            $carPage->car_model  = $car_model;
            $carPage->car_number = $car_number;
            $carPage->car_vin    = $car_vin;
            $carPage->car_year   = $car_year;
            $carPage->car_owner  = $car_owner > 0 ? $pages->get($car_owner) : null;
            $carPage->car_notes  = $car_notes;
            $carPage->save();

            $success = true;
        }
    }

    if ($success) {
        $session->redirect('/spravochnik-avto-prosmotr/?idcar=' . $car_id . '&saved=1');
    } else {
        $session->redirect('/spravochnik-avto-prosmotr/?idcar=' . $car_id . '&saved=0');
    }
}
?>