<?php namespace ProcessWire;

if(isset($_GET['logout'])) {
    session_unset();
}

$login = 'off';
$_SESSION['operator'] = 'no_operator';
//echo $_SESSION['operator'];

$user_login = !empty($_POST['user_login'])?$_POST['user_login']:NULL;  
$user_password = !empty($_POST['user_password'])?$_POST['user_password']:NULL;

if($user_login === 'admin' && $user_password === '123') {
    $login = 'on';
    $_SESSION['operator'] = 'admin';
}

$content = '';
if ($login == 'on') {
    $content = '
    <div id="content" style="max-width: 700px;">
    	<h1 class="uk-heading-hero uk-text-center">Добро пожаловать<br>'. $_SESSION['operator'] .'</h1>
    	
    	<div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <div class="uk-margin-small-top uk-flex uk-flex-column">
                <a class="uk-margin-small-top uk-button uk-button-default" href="/">Перейти на главную</a>
                <a class="uk-margin-small-top uk-button uk-button-default" href="?logout">Выход</a>
            </div>
        </div>
    </div>
    ';
} else {
    $content = '
    <div id="content" style="max-width: 700px;">
    	<h1 class="uk-heading-hero uk-text-center">Вход</h1>
    	
    	            
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <form class="uk-flex uk-flex-column" id="select_bus" action="/login/" method="post">
                <div class="uk-margin-small-top">
                    <input class="uk-input" id="user_login" type="text" name="user_login" placeholder="Логин" required>
                </div>
                <div class="uk-margin-small-top">
                    <input class="uk-input" id="user_password" type="password" name="user_password" placeholder="Пароль" required>
                </div>
                
                <div class="uk-margin-small-top uk-flex uk-flex-column">
                <button class="uk-margin-small-top uk-button uk-button-default" type="submit">Войти</button>
                </div>
            </form>
        </div>
    </div>
    ';
}

echo $content;

?>