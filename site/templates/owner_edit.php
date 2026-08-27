<?php namespace ProcessWire;

if (isset($_SESSION['operator'])) {
    $operator = $_SESSION['operator'];
} else {
    $operator = 'no_operator';
}

if ($operator == 'no_operator') {
?>
    <div id="content" style="max-width: 700px;">
        <h1 class="uk-heading-hero uk-text-center">Изменение владельца</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php
} else {

    $owner_id = !empty($_POST['owner_id']) ? (int)$_POST['owner_id'] : 0;

    $owner_title = !empty($_POST['owner_title']) ? trim($_POST['owner_title']) : '';
    $owner_phone = !empty($_POST['owner_phone']) ? trim($_POST['owner_phone']) : '';
    $owner_email = !empty($_POST['owner_email']) ? trim($_POST['owner_email']) : '';

    $success = false;

    if ($owner_id && $owner_title && $owner_phone) {

        $ownerPage = $pages->get("id=$owner_id, template=owner");

        if ($ownerPage->id) {

            $ownerPage->of(false);
            $ownerPage->title = $owner_title;
            $ownerPage->phone = $owner_phone;
            $ownerPage->email = $owner_email;
            $ownerPage->save();

            $success = true;
        }
    }

    if ($success) {
        $session->redirect('/spravochnik-vladelec-prosmotr/?idowner=' . $owner_id . '&saved=1');
    } else {
        $session->redirect('/spravochnik-vladelec-prosmotr/?idowner=' . $owner_id . '&saved=0');
    }
}
?>