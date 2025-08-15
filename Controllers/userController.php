<?php

    require_once "Model/userModel.php";

    require_once "Functions/auth.php";

    $uri = $_SERVER["REQUEST_URI"];

    switch($uri) {
        case "/account":
            if(isset($_SESSION['user'])) {


                require_once "Views/user/account.php";
            } else {

                require_once "Views/user/login.php";
            }

        case "/dashboard":

        case "/register";

            require_once "Views/user/register.php";
        case "/login";


    }
