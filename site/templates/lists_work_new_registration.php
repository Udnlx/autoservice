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
        <h1 class="uk-heading-hero uk-text-center">Новая работа Регистрация</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php    
} else {

    $work_title = !empty($_POST['work_title']) ? trim($_POST['work_title']) : NULL;
    $work_type = !empty($_POST['work_type'])   ? trim($_POST['work_type']) : NULL;
    $work_price = !empty($_POST['work_price']) ? (int)$_POST['work_price']  : 0;
    $work_time  = !empty($_POST['work_time'])  ? trim($_POST['work_time'])  : NULL;
    $work_notes = !empty($_POST['work_notes']) ? trim($_POST['work_notes']) : NULL;

    // Куда возвращаться после регистрации
    $return_to = !empty($_POST['return_to']) ? trim($_POST['return_to']) : '';

    $success = '';

    if ($work_title && $work_price > 0 && $operator != 'no_operator') {

        $works_page = $pages->get('name=raboty');
        $workPage = $pages->add('work_item', $works_page);

        $workPage->of(false);

        $workPage->title      = $work_title;
        $workPage->work_type  = $work_type;
        $workPage->work_price = $work_price;
        $workPage->work_time  = $work_time;
        $workPage->work_notes = $work_notes;

        $workPage->save();

        $success = 'Работа успешно зарегистрирована';

        // Разные редиректы в зависимости от контекста
        if ($return_to === 'new_order') {
            $redirect_url = '/zakaz-novyi/?work_created=1';
            $session->redirect($redirect_url);
        } else {
            $session->redirect('/rabota-prosmotr/?idwork=' . $workPage->id);
        }

    } else {
        $success = 'Работа не зарегистрирована!<br>Ошибка в данных';
    }

    $info = '';
    if ($success == 'Работа успешно зарегистрирована') {
        $info .= '
            <p class="uk-margin-remove">Работа успешно зарегистрирована<br>Сейчас произойдёт переход на страницу работы</p>
        ';
    } else {
        $info .= '
            <p class="uk-margin-remove">Работа не зарегистрирована!<br>Произошла ошибка, возможно некорректные данные или не заполнены обязательные поля.<br>Попробуйте позже или обратитесь в техподдержку</p>
            <a class="uk-margin-small-top uk-button uk-button-default" href="/raboty-spravochnik/">Вернуться в справочник</a>
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
                <a class="menu-link" href="/raboty-spravochnik/">Справочник работ</a>
            </div>
        </div>

        <div>
            <div class="uk-card uk-card-default uk-card-body uk-flex uk-flex-column">
                <?php echo $info; ?>
            </div>
        </div>

    </div>
</div>