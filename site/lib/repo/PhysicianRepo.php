<?php

namespace App\Repo;

use App\Model\Physician;
use Lin\AppPhp\Server\App;
use Lin\AppPhp\Server\RestfulApp;
use MISA\DBSMOD\DBSAction;

class PhysicianRepo extends RestfulApp
{
    public function OnGet()
    {
        
        $Data = $this->GetServerRequest()->getQueryParams();
        $AuthResult = $this->AuthorizeRequest();
        if (!$AuthResult) {
            return App::UnauthorizedResponse();
        }
        try {
            if (empty($Data['clinic']) || empty($Data['department'])) {
                throw new \Exception('Clinic and department are required');
            }
            return App::JsonResponse($this->QueryList($Data));
        } catch (\Throwable $th) {
            return App::JsonResponse(['message' => $th->getMessage()], 400);
        }
    }

    public function QueryList($Data): array
    {
        global $Link, $DB_TABLE, $User_Tb, $RT_CUS_User_Tb;
        $PhysicianGroupId = Physician::$groupId;
        $Act = new DBSAction($Link, $DB_TABLE, $User_Tb);
        $Act->AddSelect([
            'physicianId' => "$User_Tb.UserID",
            'fullName' => "$User_Tb.FullName"
        ]);
        $Act->AddJoinOn($RT_CUS_User_Tb, "$RT_CUS_User_Tb.UserID = $User_Tb.UserID");
        $Act->AddCondition("$User_Tb.ClientPMSGRPID = '$PhysicianGroupId'");
        $Act->AddCondition("$RT_CUS_User_Tb.CUSID = '{$Data['clinic']}'");
        $Act->AddCondition("$RT_CUS_User_Tb.DPTID = '{$Data['department']}'");
        $Act->RenderSQL('_SELECT');
        $Total = $Act->Count();
        $Physicians = $Act->ExecuteSQL();
        if ($Act->Error != null) {
            throw $Act->Error;
        }
        $Physicians = $Physicians === false ? [] : $Physicians;
        $Physicians = array_map(function ($Physician) {
            $Obj = Physician::fromArray($Physician);
            return $Obj->toArray();
        }, $Physicians);
        return [
            'total' => (int) $Total,
            'data' => $Physicians,
        ];
    }
}
