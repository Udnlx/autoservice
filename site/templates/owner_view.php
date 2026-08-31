<?php namespace ProcessWire;

if(isset($_SESSION['operator'])){
    $operator = $_SESSION['operator'];
} else {
    $operator = 'no_operator';
}

if ($operator == 'no_operator') {
?>
    <div id="content" style="max-width: 700px;">
        <h1 class="uk-heading-hero uk-text-center">Клиент Просмотр</h1>
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
            <h1 class="uk-heading-hero uk-text-center">Клиент Просмотр</h1>
            <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
                <h3 class="uk-card-title uk-text-center">Не передан ID клиента</h3>
                <a class="uk-margin-small uk-button uk-button-default" href="/">На главную</a>
            </div>
        </div>';
        return;
    }

    $ownerPage = $pages->get("id=$owner_id, template=owner");

    if (!$ownerPage->id) {
        echo '<div id="content" style="max-width: 700px;">
            <h1 class="uk-heading-hero uk-text-center">Клиент Просмотр</h1>
            <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
                <h3 class="uk-card-title uk-text-center">Клиент не найден</h3>
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
        'id'      => $ownerPage->id,
        'title'   => $ownerPage->title,
        'phone'   => $ownerPage->phone,
        'email'   => $ownerPage->email,
        'type'    => $ownerPage->owner_type,
        'address' => $ownerPage->owner_address,
        'company' => $ownerPage->owner_company,
        'inn'     => $ownerPage->owner_inn,
        'notes'   => $ownerPage->owner_notes
    ];

    // Автомобили этого клиента
    $owner_cars = $pages->find("template=car, car_owner=$owner_id, sort=title");

?>

<div id="content">
    <h1 class="uk-margin-remove uk-heading-hero uk-text-center"><?php echo ownerClean($owner['title']); ?></h1>
    <div>

        <div>
            <div class="pagemenu uk-width-1-1 uk-flex">
                <a class="menu-link" href="/">На главную</a>
                <a class="menu-link" href="/klienty-spravochnik/">Справочник клиентов</a>
            </div>
        </div>

        <div>
            <div class="uk-card uk-card-default uk-card-body uk-flex uk-flex-column">

                <div class="order-view-top">
                    <div>
                        <div class="order-view-number"><?php echo ownerClean($owner['title']); ?></div>
                        <div class="order-view-subtitle">Карточка клиента ID <?php echo ownerClean($owner['id']); ?></div>
                    </div>

                    <?php if (!empty($owner['type'])) { ?>
                        <div class="order-status-badge">
                            <?php echo ownerClean($owner['type']); ?>
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
                            <div class="order-info-label">Тип</div>
                            <div class="order-info-value"><?php echo !empty($owner['type']) ? ownerClean($owner['type']) : '—'; ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">Телефон</div>
                            <div class="order-info-value"><?php echo !empty($owner['phone']) ? ownerClean($owner['phone']) : '—'; ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">Email</div>
                            <div class="order-info-value"><?php echo !empty($owner['email']) ? ownerClean($owner['email']) : '—'; ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">Адрес</div>
                            <div class="order-info-value"><?php echo !empty($owner['address']) ? ownerClean($owner['address']) : '—'; ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">Организация</div>
                            <div class="order-info-value"><?php echo !empty($owner['company']) ? ownerClean($owner['company']) : '—'; ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">ИНН</div>
                            <div class="order-info-value"><?php echo !empty($owner['inn']) ? ownerClean($owner['inn']) : '—'; ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">Примечания</div>
                            <div class="order-info-value"><?php echo !empty($owner['notes']) ? ownerClean($owner['notes']) : '—'; ?></div>
                        </div>
                    </div>

                    <!--АВТОМОБИЛИ КЛИЕНТА-->
                    <div class="uk-margin-small-top">
                        <div class="order-info-label">Автомобили</div>
                        <?php if ($owner_cars->count()) { ?>
                            <div class="orders-list uk-flex uk-flex-column uk-margin-small-top">
                                <?php foreach ($owner_cars as $carPage) { ?>
                                    <?php
                                        $car_display = trim(ownerClean($carPage->car_brand) . ' ' . ownerClean($carPage->car_model));
                                        if ($car_display === '') $car_display = ownerClean($carPage->title);
                                    ?>
                                    <a class="order-list-item" href="/spravochnik-avto-prosmotr/?idcar=<?php echo (int)$carPage->id; ?>">
                                        <div class="order-list-item-top">
                                            <div class="order-list-item-title"><?php echo $car_display; ?></div>
                                            <?php if ($carPage->car_number) { ?>
                                                <div class="order-status-badge status-new"><?php echo ownerClean($carPage->car_number); ?></div>
                                            <?php } ?>
                                        </div>
                                        <div class="order-list-item-grid">
                                            <div class="order-list-item-cell">
                                                <div class="order-list-item-label">Год</div>
                                                <div class="order-list-item-value"><?php echo $carPage->car_year ? ownerClean($carPage->car_year) : '—'; ?></div>
                                            </div>
                                            <div class="order-list-item-cell">
                                                <div class="order-list-item-label">VIN</div>
                                                <div class="order-list-item-value"><?php echo $carPage->car_vin ? ownerClean($carPage->car_vin) : '—'; ?></div>
                                            </div>
                                        </div>
                                    </a>
                                <?php } ?>
                            </div>
                        <?php } else { ?>
                            <div class="order-cart-empty uk-margin-small-top">Автомобили не привязаны</div>
                        <?php } ?>
                    </div>
                    <!--АВТОМОБИЛИ КЛИЕНТА-->

                    <div class="uk-margin-small-top uk-flex uk-flex-column">
                        <button type="button" class="uk-margin-small-top uk-button uk-button-default" id="toggle_edit_btn">
                            Изменить
                        </button>
                        <a class="uk-margin-small-top uk-button uk-button-default" href="/">Перейти на главную</a>
                    </div>
                </div>
                <!--БЛОК ПРОСМОТРА-->

                <!--БЛОК РЕДАКТИРОВАНИЯ (скрыт по умолчанию)-->
                <form id="owner_edit_form" class="uk-flex uk-flex-column" action="/klient-redaktirovanie/" method="post" hidden>
                    <input type="hidden" name="owner_id" value="<?php echo ownerClean($owner['id']); ?>">

                    <div class="uk-margin-small-top">
                        <label for="owner_type">Тип клиента</label>
                        <select class="uk-select" id="owner_type" name="owner_type">
                            <option value="Физлицо" <?php echo ($owner['type'] === 'Физлицо') ? 'selected' : ''; ?>>Физлицо</option>
                            <option value="Юрлицо" <?php echo ($owner['type'] === 'Юрлицо') ? 'selected' : ''; ?>>Юрлицо</option>
                        </select>
                    </div>

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

                    <div class="uk-margin-small-top">
                        <label for="owner_address">Адрес</label>
                        <input class="uk-input" id="owner_address" type="text" name="owner_address" value="<?php echo ownerClean($owner['address']); ?>" placeholder="г. Москва, ул. Ленина, д. 1" autocomplete="off">
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="owner_company">Организация</label>
                        <input class="uk-input" id="owner_company" type="text" name="owner_company" value="<?php echo ownerClean($owner['company']); ?>" placeholder="Название компании" autocomplete="off">
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="owner_inn">ИНН</label>
                        <input class="uk-input" id="owner_inn" type="text" name="owner_inn" value="<?php echo ownerClean($owner['inn']); ?>" placeholder="10 или 12 цифр" autocomplete="off">
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="owner_notes">Примечания</label>
                        <textarea class="uk-textarea" id="owner_notes" name="owner_notes" rows="3" placeholder="Особенности, замечания..."><?php echo ownerClean($owner['notes']); ?></textarea>
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