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
        <h1 class="uk-heading-hero uk-text-center">Владельцы Справочник</h1>
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
        $safe_query = $sanitizer->selectorValue($search_query);
        $owners_result = $pages->find("template=owner, title|phone|email%=$safe_query, limit=100");

        foreach ($owners_result as $ownerPage) {
            $found_owners[] = [
                'id' => $ownerPage->id,
                'title' => $ownerPage->title,
                'phone' => $ownerPage->phone,
                'email' => $ownerPage->email
            ];
        }
    }

?>

<div id="content">
    <h1 class="uk-margin-remove uk-heading-hero uk-text-center">Владельцы Справочник</h1>
    <div>

        <div>
			<div class="pagemenu uk-width-1-1 uk-flex">
			    <a class="menu-link" href="/">На главную</a>
			    <a class="menu-link" href="#new_owner_modal" uk-toggle>Новый владелец</a>
			</div>
        </div>

        <div>
            <div class="uk-card uk-card-default uk-card-body uk-flex uk-flex-column">

                <form class="uk-flex uk-flex-column" action="/vladeltcy-spravochnik/" method="get">
                    <label for="q">Поиск владельца</label>

                    <div class="uk-flex uk-flex-middle order-add-row" style="gap: 10px;">
                        <input class="uk-input" id="q" type="text" name="q" value="<?php echo ownerClean($search_query); ?>" placeholder="Введите ФИО, телефон или почту" autocomplete="off">

                        <button type="submit" class="uk-button uk-button-default" style="margin: 0 !important;">
                            Найти
                        </button>
                    </div>
                </form>

                <?php if (!$search_active) { ?>
                    <div class="order-cart-empty uk-margin-small-top uk-text-center">
                        Начните поиск, чтобы увидеть владельцев
                    </div>
                <?php } elseif (empty($found_owners)) { ?>
                    <div class="order-cart-empty uk-margin-small-top uk-text-center">
                        Ничего не найдено по запросу «<?php echo ownerClean($search_query); ?>»
                    </div>
                <?php } else { ?>
                    <div class="orders-list uk-flex uk-flex-column uk-margin-small-top">
                        <?php foreach ($found_owners as $owner) { ?>
                            <a class="order-list-item" href="/spravochnik-vladelec-prosmotr/?idowner=<?php echo (int)$owner['id']; ?>">
                                <div class="order-list-item-top">
                                    <div class="order-list-item-title"><?php echo ownerClean($owner['title']); ?></div>
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
                                </div>
                            </a>
                        <?php } ?>
                    </div>
                <?php } ?>

            </div>
        </div>

    </div>

<!--МОДАЛЬНОЕ ОКНО НОВЫЙ ВЛАДЕЛЕЦ-->
<div id="new_owner_modal" uk-modal>
    <div class="uk-modal-dialog uk-modal-body">
        <button class="uk-modal-close-default" type="button" uk-close></button>

        <h3 class="uk-card-title uk-text-center">Новый владелец</h3>

        <form class="uk-flex uk-flex-column" action="/vladeletc-registratciia/" method="post">

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

            <div class="uk-margin-small-top uk-flex uk-flex-column">
                <button type="submit" class="uk-margin-small-top uk-button uk-button-default" name="save_new_owner" value="1">
                    Зарегистрировать
                </button>
            </div>

        </form>
    </div>
</div>
<!--МОДАЛЬНОЕ ОКНО НОВЫЙ ВЛАДЕЛЕЦ-->

</div>

<?php   
}
?>
