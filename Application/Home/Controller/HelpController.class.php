<?php
namespace Home\Controller;

class HelpController extends HomeController {

    public function index($condition=null){
        $this->show("<extend name='Layout/ins_page' />");
    }

    // public function charge(){
    // 	// var_dump(C());
    // 	// echo(THEME_NAME);
    // }

} 