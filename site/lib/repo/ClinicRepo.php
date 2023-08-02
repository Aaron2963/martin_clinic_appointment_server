<?php

namespace App\Repo;

use App\Model\Clinic;
use Lin\AppPhp\Server\App;
use Lin\AppPhp\Server\RestfulApp;
use MISA\DBSMOD\DBSAction;

class ClinicRepo extends RestfulApp
{
    public function OnGet()
    {
        global $Link, $DB_TABLE, $CUSIFO_Tb, $DPT_Tb, $RT_CUS_DPT_Tb;
        $Data = $this->GetServerRequest()->getQueryParams();
        $AuthResult = $this->AuthorizeRequest();
        if (!$AuthResult) {
            return App::UnauthorizedResponse();
        }
        try {
            $Offset = $Data['offset'] ?? 0;
            $Limit = $Data['limit'] ?? 10;
            $Act = new DBSAction($Link, $DB_TABLE, $CUSIFO_Tb);
            $Act->AddSelect([
                'clinicId' => 'CUSIFOID',
                'name' => 'Name',
            ]);
            $Act->AddOrder('CUSIFOID', 'DESC');
            $Act->AddLimit($Offset, $Limit);
            $Act->RenderSQL('_SELECT');
            $Count = $Act->Count();
            $Clinics = $Act->ExecuteSQL();
            if ($Act->Error != null) {
                throw $Act->Error;
            }
            $Clinics = $Clinics === false ? [] : $Clinics;
            foreach ($Clinics as &$Clinic) {
                $Act = new DBSAction($Link, $DB_TABLE, $RT_CUS_DPT_Tb);
                $Act->AddSelect([
                    'departmentId' => 'DPTID',
                    'name' => "(select Name from $DPT_Tb where $DPT_Tb.DPTID = $RT_CUS_DPT_Tb.DPTID)",
                ]);
                $Act->AddCondition("$RT_CUS_DPT_Tb.CUSID = '{$Clinic['clinicId']}'", 'and');
                $Act->RenderSQL('_SELECT');
                $Clinic['departments'] = $Act->ExecuteSQL();
                if ($Act->Error != null) {
                    throw $Act->Error;
                }
                $Clinic['departments'] = $Clinic['departments'] === false ? [] : $Clinic['departments'];
            }
            $Clinics = array_map(function($Clinic) {
                $Obj = Clinic::fromArray($Clinic);
                return $Obj->toArray();
            }, $Clinics);
            return App::JsonResponse([
                'total' => (int) $Count,
                'data' => $Clinics,
            ]);
        } catch (\Throwable $th) {
            return App::JsonResponse(['message' => $th->getMessage()], 400);
        }
    }
}