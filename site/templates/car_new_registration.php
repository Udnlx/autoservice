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
        <h1 class="uk-heading-hero uk-text-center">Новый автомобиль Регистрация</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php    
} else {

    $car_brand  = !empty($_POST['car_brand'])  ? trim($_POST['car_brand'])  : NULL;
    $car_model  = !empty($_POST['car_model'])  ? trim($_POST['car_model'])  : NULL;
    $car_number = !empty($_POST['car_number']) ? trim($_POST['car_number']) : NULL;
    $car_vin    = !empty($_POST['car_vin'])    ? trim($_POST['car_vin'])    : NULL;
    $car_year   = !empty($_POST['car_year'])   ? trim($_POST['car_year'])   : NULL;
    $car_owner  = !empty($_POST['car_owner'])  ? (int)$_POST['car_owner']  : 0;
    $car_notes  = !empty($_POST['car_notes'])  ? trim($_POST['car_notes'])  : NULL;

    // Куда возвращаться после регистрации
    $return_to = !empty($_POST['return_to']) ? trim($_POST['return_to']) : '';

    $success = '';

    if ($car_brand && $car_model && $car_number && $car_vin && $car_year && $operator != 'no_operator') {

        $cars_page = $pages->get('template=cars');
        $carPage = $pages->add('car', $cars_page);

        $carPage->of(false);

        $car_title = $car_brand . ' ' . $car_model . ' (' . $car_number . ') (VIN:' . $car_vin . ')';
        if ($car_year) {
            $car_title .= ' ' . $car_year;
        }

        $carPage->title       = $car_title;
        $carPage->car_brand   = $car_brand;
        $carPage->car_model   = $car_model;
        $carPage->car_number  = $car_number;
        $carPage->car_vin     = $car_vin;
        $carPage->car_year    = $car_year;
        $carPage->car_owner   = $car_owner > 0 ? $pages->get($car_owner) : null;
        $carPage->car_notes   = $car_notes;

        $carPage->save();

        $success = 'Автомобиль успешно зарегистрирован';

        // Разные редиректы в зависимости от контекста
        if ($return_to === 'new_order') {
            $redirect_url = '/zakaz-novyi/?car_id=' . $carPage->id . '&car_created=1';
            if ($car_owner > 0) {
                $redirect_url .= '&client_id=' . $car_owner;
            }
            $session->redirect($redirect_url);
        } else {
            $session->redirect('/spravochnik-avto-prosmotr/?idcar=' . $carPage->id);
        }

    } else {
        $success = 'Автомобиль не зарегистрирован!<br>Ошибка в данных';
    }

    $info = '';
    if ($success == 'Автомобиль успешно зарегистрирован') {
        $info .= '
            <p class="uk-margin-remove">Автомобиль успешно зарегистрирован<br>Сейчас произойдёт переход на страницу автомобиля</p>
        ';
    } else {
        $info .= '
            <p class="uk-margin-remove">Автомобиль не зарегистрирован!<br>Произошла ошибка, возможно некорректные данные или не заполнены обязательные поля.<br>Попробуйте позже или обратитесь в техподдержку</p>
            <a class="uk-margin-small-top uk-button uk-button-default" href="/avtomobili-spravochnik/">Вернуться в справочник</a>
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
                <a class="menu-link" href="/avtomobili-spravochnik/">Справочник авто</a>
            </div>
        </div>

        <div>
            <div class="uk-card uk-card-default uk-card-body uk-flex uk-flex-column">
                <?php echo $info; ?>
            </div>
        </div>

    </div>
</div>