<?php namespace ProcessWire;

if (isset($_SESSION['operator'])) {
    $operator = $_SESSION['operator'];
} else {
    $operator = 'no_operator';
}

if ($operator == 'no_operator') {
?>
    <div id="content" style="max-width: 700px;">
        <h1 class="uk-heading-hero uk-text-center">Приход на склад</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php
} else {

    $part_id    = !empty($_POST['part_id'])    ? (int)$_POST['part_id']       : 0;
    $coming_qty = !empty($_POST['coming_qty']) ? (int)$_POST['coming_qty']    : 0;
    $coming_note = !empty($_POST['coming_note']) ? trim($_POST['coming_note']) : '';

    $success = false;

    if ($part_id > 0 && $coming_qty > 0) {

        $partPage = $pages->get("id=$part_id, template=part");

        if ($partPage->id) {
            $partPage->of(false);
            $partPage->part_qty = (int)$partPage->part_qty + $coming_qty;
            $partPage->save('part_qty');

            $success = true;
        }
    }

    if ($success) {
        $redirect = '/zapchast-prosmotr/?idpart=' . $part_id . '&coming=' . $coming_qty;
        if ($coming_note !== '') {
            $redirect .= '&coming_note=' . urlencode($coming_note);
        }
        $session->redirect($redirect);
    } else {
        $session->redirect('/zapchast-prosmotr/?idpart=' . $part_id . '&saved=0');
    }
}
?>