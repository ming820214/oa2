<?php
namespace Home\Controller;

class FinanceSysController extends HomeController {


    public function basicDataEntry(){
        $this -> display('basicDataEntry');
    }

	public function achievementStatistics(){
        $this -> display('AchievementStatistics');
    }
	
	public function schoolPerformanceEntry(){
        $this -> display('schoolPerformanceEntry');
    }
	
    public function schoolSubmitData(){
        $this -> display('schoolSubmitData');
    }


    public function PersonalTarget(){
        $this -> display('PersonalTarget');
    }
	
	
	public function upLevelAndDownLevelAchievement(){
        $this -> display('UpLevelAndDownLevelAchievement');
    }


    public function upLevelAndDownLevelAchievementCharge(){
        $this -> display('UpLevelAndDownLevelAchievementCharge');
    }
	
	
	public function groupQuerySchoolSubmitData(){
        $this -> display('groupQuerySchoolSubmitData');
    }

}
?>
