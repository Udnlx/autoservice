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
        <h1 class="uk-heading-hero uk-text-center">Клиенты Справочник</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php    
} else {

    if (!function_exists('ownerClean')) {
        function ownerClean($value) {
            return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        }
    }

    $search_query = trim((string)$input->get('q'));
    $search_active = $search_query !== '';

    $found_owners = [];

    if ($search_active) {
        // Поиск по запросу
        $safe_query = $sanitizer->selectorValue($search_query);
        $owners_result = $pages->find("template=owner, title|phone|email|owner_company|owner_inn%=$safe_query, limit=100");
    } else {
        // Последние 30 клиентов, новые вверху
        $owners_result = $pages->find("template=owner, sort=-created, limit=30");
    }

    foreach ($owners_result as $ownerPage) {
        $found_owners[] = [
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
    }

?>

<div id="content">
    <h1 class="uk-margin-remove uk-heading-hero uk-text-center">Клиенты Справочник</h1>
    <div>

        <div>
            <div class="pagemenu uk-width-1-1 uk-flex">
                <a class="menu-link" href="/">На главную</a>
                <a class="menu-link" href="#new_owner_modal" uk-toggle>Новый клиент</a>
            </div>
        </div>

        <div>
            <div class="uk-card uk-card-default uk-card-body uk-flex uk-flex-column">

                <form class="uk-flex uk-flex-column" action="/klienty-spravochnik/" method="get" style="margin:0;">
                    <label for="q">Поиск клиента</label>

                    <div class="uk-flex uk-flex-middle order-add-row" style="gap: 10px;">
                        <input class="uk-input" id="q" type="text" name="q" value="<?php echo ownerClean($search_query); ?>" placeholder="Введите ФИО, телефон, почту или организацию" autocomplete="off">

                        <button type="submit" class="uk-button uk-button-default" style="margin: 0 !important;">
                            Найти
                        </button>
                    </div>
                </form>

                <?php if (empty($found_owners)) { ?>
                    <div class="order-cart-empty uk-margin-small-top uk-text-center">
                        <?php if ($search_active) { ?>
                            Ничего не найдено по запросу «<?php echo ownerClean($search_query); ?>»
                        <?php } else { ?>
                            Пока нет клиентов в справочнике
                        <?php } ?>
                    </div>
                <?php } else { ?>
                    <div class="orders-list uk-flex uk-flex-column uk-margin-small-top">
                        <?php foreach ($found_owners as $owner) { ?>
                            <a class="order-list-item" href="/klient-prosmotr/?idowner=<?php echo (int)$owner['id']; ?>">
                                <div class="order-list-item-top">
                                    <div class="order-list-item-title"><?php echo ownerClean($owner['title']); ?></div>
                                    <?php if (!empty($owner['type'])) { ?>
                                        <div class="order-status-badge status-new"><?php echo ownerClean($owner['type']); ?></div>
                                    <?php } ?>
                                </div>

                                <div class="order-list-item-grid">
                                    <div class="order-list-item-cell">
                                        <div class="order-list-item-label">Телефон</div>
                                        <div class="order-list-item-value"><?php echo !empty($owner['phone']) ? ownerClean($owner['phone']) : '—'; ?></div>
                                    </div>

                                    <div class="order-list-item-cell">
                                        <div class="order-list-item-label">Почта</div>
                                        <div class="order-list-item-value"><?php echo !empty($owner['email']) ? ownerClean($owner['email']) : '—'; ?></div>
                                    </div>

                                    <?php if (!empty($owner['company'])) { ?>
                                    <div class="order-list-item-cell">
                                        <div class="order-list-item-label">Организация</div>
                                        <div class="order-list-item-value"><?php echo ownerClean($owner['company']); ?></div>
                                    </div>
                                    <?php } ?>

                                    <?php if (!empty($owner['inn'])) { ?>
                                    <div class="order-list-item-cell">
                                        <div class="order-list-item-label">ИНН</div>
                                        <div class="order-list-item-value"><?php echo ownerClean($owner['inn']); ?></div>
                                    </div>
                                    <?php } ?>
                                </div>
                            </a>
                        <?php } ?>
                    </div>
                <?php } ?>

            </div>
        </div>

    </div>

<!--МОДАЛЬНОЕ ОКНО НОВЫЙ КЛИЕНТ-->
<div id="new_owner_modal" uk-modal>
    <div class="uk-modal-dialog uk-modal-body">
        <button class="uk-modal-close-default" type="button" uk-close></button>

        <h3 class="uk-card-title uk-text-center">Новый клиент</h3>

        <form class="uk-flex uk-flex-column" action="/klient-registratciia/" method="post">

            <div class="uk-margin-small-top">
                <label for="owner_type">Тип клиента</label>
                <select class="uk-select" id="owner_type" name="owner_type">
                    <option value="Физлицо">Физлицо</option>
                    <option value="Юрлицо">Юрлицо</option>
                </select>
            </div>

            <div class="uk-margin-small-top">
                <label for="owner_name">ФИО (Фамилия Имя Отчество)</label>
                <input class="uk-input" id="owner_name" type="text" name="owner_name" placeholder="Например: Иванов Иван Иванович" autocomplete="off" required>
            </div>

            <div class="uk-margin-small-top">
                <label for="owner_phone">Телефон</label>
                <input class="uk-input" id="owner_phone" type="text" name="owner_phone" placeholder="Например: +7 900 123-45-67" autocomplete="off" required>
            </div>

            <div class="uk-margin-small-top">
                <label for="owner_email">Почта</label>
                <input class="uk-input" id="owner_email" type="email" name="owner_email" placeholder="Например: ivanov@mail.ru" autocomplete="off">
            </div>

            <div class="uk-margin-small-top">
                <label for="owner_address">Адрес</label>
                <input class="uk-input" id="owner_address" type="text" name="owner_address" placeholder="Например: г. Москва, ул. Ленина, д. 1" autocomplete="off">
            </div>

            <div class="uk-margin-small-top">
                <label for="owner_company">Организация</label>
                <input class="uk-input" id="owner_company" type="text" name="owner_company" placeholder="Название компании" autocomplete="off">
            </div>

            <div class="uk-margin-small-top">
                <label for="owner_inn">ИНН</label>
                <input class="uk-input" id="owner_inn" type="text" name="owner_inn" placeholder="10 или 12 цифр" autocomplete="off">
            </div>

            <div class="uk-margin-small-top">
                <label for="owner_notes">Примечания</label>
                <textarea class="uk-textarea" id="owner_notes" name="owner_notes" rows="3" placeholder="Особенности, замечания..."></textarea>
            </div>

            <div class="uk-margin-small-top uk-flex uk-flex-column">
                <button type="submit" class="uk-margin-small-top uk-button uk-button-default" name="save_new_owner" value="1">
                    Зарегистрировать
                </button>
            </div>

        </form>
    </div>
</div>
<!--МОДАЛЬНОЕ ОКНО НОВЫЙ КЛИЕНТ-->

</div>

<?php   
}
?>