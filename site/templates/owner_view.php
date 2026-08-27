<?php namespace ProcessWire;

if(isset($_SESSION['operator'])){
    $operator = $_SESSION['operator'];
} else {
    $operator = 'no_operator';
}

if ($operator == 'no_operator') {
?>
    <div id="content" style="max-width: 700px;">
        <h1 class="uk-heading-hero uk-text-center">Владелец Просмотр</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php    
} else {

    $owner_id = $input->get->int('idowner');

    if (!$owner_id) {
        echo '<div id="content" style="max-width: 700px;">
            <h1 class="uk-heading-hero uk-text-center">Владелец Просмотр</h1>
            <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
                <h3 class="uk-card-title uk-text-center">Не передан ID владельца</h3>
                <a class="uk-margin-small uk-button uk-button-default" href="/">На главную</a>
            </div>
        </div>';
        return;
    }

    $ownerPage = $pages->get("id=$owner_id, template=owner");

    if (!$ownerPage->id) {
        echo '<div id="content" style="max-width: 700px;">
            <h1 class="uk-heading-hero uk-text-center">Владелец Просмотр</h1>
            <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
                <h3 class="uk-card-title uk-text-center">Владелец не найден</h3>
                <a class="uk-margin-small uk-button uk-button-default" href="/">На главную</a>
            </div>
        </div>';
        return;
    }

    if (!function_exists('ownerClean')) {
        function ownerClean($value) {
            return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        }
    }

    $owner = [
        'id'    => $ownerPage->id,
        'title' => $ownerPage->title,
        'phone' => $ownerPage->phone,
        'email' => $ownerPage->email
    ];

?>

<div id="content">
    <h1 class="uk-margin-remove uk-heading-hero uk-text-center"><?php echo ownerClean($owner['title']); ?></h1>
    <div>

        <div>
            <div class="pagemenu uk-width-1-1 uk-flex">
                <a class="menu-link" href="/">На главную</a>
                <a class="menu-link" href="/vladeltcy-spravochnik/">Справочник владельцев</a>
            </div>
        </div>

        <div>
            <div class="uk-card uk-card-default uk-card-body uk-flex uk-flex-column">

                <div class="order-view-top">
                    <div>
                        <div class="order-view-number"><?php echo ownerClean($owner['title']); ?></div>
                        <div class="order-view-subtitle">Карточка владельца ID <?php echo ownerClean($owner['id']); ?></div>
                    </div>

                    <?php if (!empty($owner['phone'])) { ?>
                        <div class="order-status-badge">
                            <?php echo ownerClean($owner['phone']); ?>
                        </div>
                    <?php } ?>
                </div>

                <?php if ($input->get->int('saved') === 1) { ?>
                    <div class="uk-alert-success uk-margin-small-top" uk-alert>
                        <a class="uk-alert-close" uk-close></a>
                        <p class="uk-margin-remove">Изменения сохранены</p>
                    </div>
                <?php } elseif ($input->get('saved') !== null && $input->get->int('saved') === 0) { ?>
                    <div class="uk-alert-danger uk-margin-small-top" uk-alert>
                        <a class="uk-alert-close" uk-close></a>
                        <p class="uk-margin-remove">Не удалось сохранить изменения</p>
                    </div>
                <?php } ?>

                <!--БЛОК ПРОСМОТРА-->
                <div id="owner_view_block">
                    <div class="order-view-grid uk-margin-small-top">
                        <div class="order-info-box">
                            <div class="order-info-label">ФИО</div>
                            <div class="order-info-value"><?php echo !empty($owner['title']) ? ownerClean($owner['title']) : '—'; ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">Телефон</div>
                            <div class="order-info-value"><?php echo !empty($owner['phone']) ? ownerClean($owner['phone']) : '—'; ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">Email</div>
                            <div class="order-info-value"><?php echo !empty($owner['email']) ? ownerClean($owner['email']) : '—'; ?></div>
                        </div>
                    </div>

                    <div class="uk-margin-small-top uk-flex uk-flex-column">
                        <button type="button" class="uk-margin-small-top uk-button uk-button-default" id="toggle_edit_btn">
                            Изменить
                        </button>
                        <a class="uk-margin-small-top uk-button uk-button-default" href="/">Перейти на главную</a>
                    </div>
                </div>
                <!--БЛОК ПРОСМОТРА-->

                <!--БЛОК РЕДАКТИРОВАНИЯ (скрыт по умолчанию)-->
                <form id="owner_edit_form" class="uk-flex uk-flex-column" action="/vladelec-redaktirovanie/" method="post" hidden>
                    <input type="hidden" name="owner_id" value="<?php echo ownerClean($owner['id']); ?>">

                    <div class="uk-margin-small-top">
                        <label for="owner_title">ФИО</label>
                        <input class="uk-input" id="owner_title" type="text" name="owner_title" value="<?php echo ownerClean($owner['title']); ?>" placeholder="Иванов Иван Иванович" autocomplete="off" required>
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="owner_phone">Телефон</label>
                        <input class="uk-input" id="owner_phone" type="text" name="owner_phone" value="<?php echo ownerClean($owner['phone']); ?>" placeholder="+7 (___) ___-__-__" autocomplete="off" required>
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="owner_email">Email</label>
                        <input class="uk-input" id="owner_email" type="email" name="owner_email" value="<?php echo ownerClean($owner['email']); ?>" placeholder="example@mail.ru" autocomplete="off">
                    </div>

                    <div class="uk-margin-small-top uk-flex uk-flex-column">
                        <button type="submit" class="uk-margin-small-top uk-button uk-button-default" name="save_owner_changes" value="1">
                            Сохранить изменения
                        </button>
                        <button type="button" class="uk-margin-small-top uk-button uk-button-default" id="cancel_edit_btn">
                            Отмена
                        </button>
                    </div>
                </form>
                <!--БЛОК РЕДАКТИРОВАНИЯ-->

            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var viewBlock = document.getElementById('owner_view_block');
    var editForm  = document.getElementById('owner_edit_form');
    var toggleBtn = document.getElementById('toggle_edit_btn');
    var cancelBtn = document.getElementById('cancel_edit_btn');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            viewBlock.hidden = true;
            editForm.hidden  = false;
            editForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            editForm.hidden  = true;
            viewBlock.hidden = false;
            viewBlock.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }
});
</script>

<?php   
}
?>