<?php namespace ProcessWire;

if (isset($_SESSION['operator'])) {
    $operator = $_SESSION['operator'];
} else {
    $operator = 'no_operator';
}

if ($operator == 'no_operator') {
?>
    <div id="content" style="max-width: 700px;">
        <h1 class="uk-heading-hero uk-text-center">Изменение мастера</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php
} else {

    $master_id = !empty($_POST['master_id']) ? (int)$_POST['master_id'] : 0;

    $master_title   = !empty($_POST['master_title']) ? trim($_POST['master_title']) : '';
    $master_post    = !empty($_POST['master_post'])  ? trim($_POST['master_post'])  : '';
    $master_percent = $input->post->int('master_percent');   // 0 допустим

    $success = false;

    if ($master_id && $master_title && $master_percent >= 0 && $master_percent <= 100) {

        $masterPage = $pages->get("id=$master_id, template=master");

        if ($masterPage->id) {

            $masterPage->of(false);
            $masterPage->title   = $master_title;
            $masterPage->post    = $master_post;
            $masterPage->percent = $master_percent;
            $masterPage->save();

            $success = true;
        }
    }

    if ($success) {
        $session->redirect('/master-prosmotr/?idmaster=' . $master_id . '&saved=1');
    } else {
        $session->redirect('/master-prosmotr/?idmaster=' . $master_id . '&saved=0');
    }
}
?>