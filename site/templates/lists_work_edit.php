<?php namespace ProcessWire;

if (isset($_SESSION['operator'])) {
    $operator = $_SESSION['operator'];
} else {
    $operator = 'no_operator';
}

if ($operator == 'no_operator') {
?>
    <div id="content" style="max-width: 700px;">
        <h1 class="uk-heading-hero uk-text-center">Изменение работы</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php
} else {

    $work_id = !empty($_POST['work_id']) ? (int)$_POST['work_id'] : 0;

    $work_title = !empty($_POST['work_title']) ? trim($_POST['work_title']) : '';
    $work_type  = !empty($_POST['work_type'])  ? trim($_POST['work_type']) : '';
    $work_price = !empty($_POST['work_price']) ? (int)$_POST['work_price']  : 0;
    $work_time  = !empty($_POST['work_time'])  ? trim($_POST['work_time'])  : '';
    $work_notes = !empty($_POST['work_notes']) ? trim($_POST['work_notes']) : '';

    $success = false;

    if ($work_id && $work_title && $work_price > 0) {

        $workPage = $pages->get("id=$work_id, template=work_item");

        if ($workPage->id) {

            $workPage->of(false);
            $workPage->title      = $work_title;
            $workPage->work_type  = $work_type;
            $workPage->work_price = $work_price;
            $workPage->work_time  = $work_time;
            $workPage->work_notes = $work_notes;
            $workPage->save();

            $success = true;
        }
    }

    if ($success) {
        $session->redirect('/rabota-prosmotr/?idwork=' . $work_id . '&saved=1');
    } else {
        $session->redirect('/rabota-prosmotr/?idwork=' . $work_id . '&saved=0');
    }
}
?>