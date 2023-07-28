<?php

namespace App\Repo;

use Lin\AppPhp\Server\App;
use Lin\AppPhp\Server\RestfulApp;
use App\Model\Patient;
use App\Model\PatientDetail;
use MISA\DBSMOD\DBSAction;

class PatientRepo extends RestfulApp
{
    public function OnGet()
    {
        $Data = $this->GetServerRequest()->getQueryParams();
        $AuthResult = $this->AuthorizeRequest();
        if (!$AuthResult) {
            return App::UnauthorizedResponse();
        }
        if (empty($Data['_id'])) {
            $List = $this->QueryList($Data);
            $ResponseBody = $this->Psr17Factory->createStream(json_encode($List));
            return $this->Psr17Factory->createResponse(200)->withBody($ResponseBody)->withHeader('Content-Type', 'application/json');
        } else {
            $Patient = $this->QueryDetail($Data['_id']);
            if ($Patient === null) {
                return $this->Psr17Factory->createResponse(404);
            }
            $ResponseBody = $this->Psr17Factory->createStream(json_encode($Patient));
            return $this->Psr17Factory->createResponse(200)->withBody($ResponseBody)->withHeader('Content-Type', 'application/json');
        }
        return App::NoContentResponse();
    }

    public function QueryList($Data) : array
    {
        global $Link, $DB_TABLE, $User_Tb;
        $Act = new DBSAction($Link, $DB_TABLE, $User_Tb);
        $Act->AddSelect([
            'patientId' => 'UserID',
            'fullName' => 'FullName',
            'gender' => 'Gender',
            'birthday' => 'Birthday',
        ]);
        if (!empty($Data['search'])) {
            $Act->AddPreCondition("concat(FullName,Birthday,TEL1,MobileTEL1) like concat('%',:search,'%')", ['search' => $Data['search']], 'or');
        }
        $Act->AddLimit($Data['offset'] ?? 0, $Data['limit'] ?? 10);
        $Act->AddOrder('CreateDateTime', 'DESC');
        $Act->RenderSQL('_SELECT');
        $List = $Act->ExecuteSQL();
        $Total = $Act->Count();
        if (!is_array($List)) {
            $List = [];
        }
        $List = array_map(function ($Item) {
            $Patient = Patient::fromArray($Item);
            return $Patient->toArray();
        }, $List);
        return [
            'total' => (int) $Total,
            'data' => $List,
        ];
    }

    public function QueryDetail($ID) : ?array
    {
        global $Link, $DB_TABLE, $User_Tb;
        $Act = new DBSAction($Link, $DB_TABLE, $User_Tb);
        $Act->AddSelect([
            'patientId' => 'UserID',
            'fullName' => 'FullName',
            'gender' => 'Gender',
            'birthday' => 'Birthday',
            'nationalId' => 'IDTNO',
            'tel' => 'TEL1',
            'mobile' => 'MobileTEL1',
            'email' => 'EML1',
            'address' => 'StreetADR',
            'bloodType' => 'BloodTYP',
            'married' => "Marriage",
            'height' => 'Height',
            'weight' => 'Weight',
        ]);
        $Act->AddPreCondition('UserID = :id', ['id' => $ID]);
        $Act->RenderSQL('_SELECT');
        $Patient = $Act->ExecuteSQL();
        if (!is_array($Patient)) {
            return null;
        }
        $Patient = PatientDetail::fromArray($Patient[0]);
        return $Patient->toArray();
    }
}