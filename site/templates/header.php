<?php namespace ProcessWire;

if(isset($_SESSION['operator'])){
    $operator = $_SESSION['operator'];
} else {
    $operator = 'no_operator';
}

$url = $_SERVER['REQUEST_URI'];
$url = explode('?', $url);
$url = $url[0];
//echo $url;
$menu = '
    <div class="uk-navbar-left" style="gap: 10px;">
        <a href="#offcanvas-usage" uk-toggle><i class="fa-solid fa-bars"></i></a>

        <div id="offcanvas-usage" uk-offcanvas>
            <div class="uk-offcanvas-bar">
                <button class="uk-offcanvas-close" type="button" uk-close></button>
                <br>
                <a class="uk-margin-small uk-button uk-button-default" href="/">Домашняя страница</a>
                <a class="uk-margin-small uk-button uk-button-default" href="/zakaz-novyi/">Новый заказ</a>
                <a class="uk-margin-small uk-button uk-button-default" href="/zakaz-dvizhenie/">Движение</a>
                <a class="uk-margin-small uk-button uk-button-default" href="">Автомобили</a>
                <a class="uk-margin-small uk-button uk-button-default" href="">Клиенты</a>
                <a class="uk-margin-small uk-button uk-button-default" href="">Склад</a>
                <a class="uk-margin-small uk-button uk-button-default" href="">Справочники</a>
            </div>
        </div>

        <a href="/"><i class="fa-solid fa-house"></i></a>
        <p class="uk-margin-remove uk-text-bold" style="font-size: 12px;">Оператор: ' . $operator . '</p>
        <a href="/login/?logout" title="Выход из системы"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
   ';
if ($url == '/login/') {
   $menu = '';
}

?>

    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Автосервис</title>
        <meta name="Description" content="Программа для ведения учета автомастерских">
        
        <link rel="stylesheet" href="<?php echo $config->urls->templates; ?>styles/uikit.min.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo $config->urls->templates; ?>styles/main.css?v=<?php echo uniqid(); ?>">
        
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v6.1.2/css/all.css">
        
        <script src="<?php echo $config->urls->templates; ?>scripts/uikit.min.js"></script>
    </head>
    <body>
        
        
        
        
    <div class="uk-container">
        <nav class="uk-navbar-container uk-padding-small" uk-navbar>
            <?php echo $menu; ?>
        </nav>
    </div>