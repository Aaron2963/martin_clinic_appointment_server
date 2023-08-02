<?php

namespace App\Repo;

use Lin\AppPhp\Server\App;
use Lin\AppPhp\Server\RestfulApp;
use App\Model\Patient;
use App\Model\PatientDetail;
use MISA\DBSMOD\DBSAction;
use MISA\DBSMOD\DBSMOD_User;
use MISA\DBSMOD\Utility;

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
            return App::JsonResponse($List);
        } else {
            $Patient = $this->QueryDetail($Data['_id']);
            if ($Patient === null) {
                return $this->Psr17Factory->createResponse(404);
            }
            return App::JsonResponse($Patient);
        }
    }

    public function OnPost()
    {
        global $Link, $DB_TABLE;
        $Data = $this->GetServerRequest()->getParsedBody();
        $AuthResult = $this->AuthorizeRequest();
        if (!$AuthResult) {
            return App::UnauthorizedResponse();
        }
        try {
            // Validate Required Fields
            $RequiredFields = [
                'fullName', 'gender', 'birthday', 'nationalId', 'tel', 'mobile', 'email',
                'address', 'bloodType', 'married', 'height', 'weight','emergencyContacts'
            ];
            $InvalidFields = [];
            foreach ($RequiredFields as $Field) {
                if (empty($Data[$Field])) {
                    $InvalidFields[] = $Field;
                }
            }
            if (count($InvalidFields) > 0) {
                throw new \Exception('Invalid Fields: ' . implode(',', $InvalidFields));
            }
            // Purify Data
            $Data = Utility::SafeScript($Data);
            $Data = Utility::SafeSQL($Data, $Link);
            $Data['UserID'] = Utility::GetDBSID();
            $Patient = PatientDetail::fromArray($Data);
            // Check Duplicate
            $DuplicateFields = $this->CheckDuplicate($Patient);
            if (count($DuplicateFields) > 0) {
                throw new \Exception('Duplicate Patient: ' . implode(', ', $DuplicateFields));
            }
            // Create Patient
            $Mod = new DBSMOD_User($Link, $DB_TABLE);
            $CreateResult = $Mod->Create($Patient->toDBArray());
            if ($CreateResult === false) {
                throw new \Exception('Create Patient Failed: ' . $Mod->Error->getMessage());
            }
            return App::NoContentResponse();
        } catch (\Throwable $th) {
            return App::JsonResponse(['message' => $th->getMessage()], 400);
        }
    }

    public function OnPut()
    {
        global $Link, $DB_TABLE;
        $Data = $this->GetServerRequest()->getParsedBody();
        $AuthResult = $this->AuthorizeRequest();
        if (!$AuthResult) {
            return App::UnauthorizedResponse();
        }
        try {
            // Purify Data
            $Data = Utility::SafeScript($Data);
            $Data = Utility::SafeSQL($Data, $Link);
            $PatientId = $this->GetServerRequest()->getQueryParams()['_id'];
            $OriData = $this->QueryDetail($PatientId);
            $Patient = PatientDetail::fromArray($OriData);
            foreach ($Data as $Key => $Value) {
                $Patient->$Key = $Value;
            }
            // Check Duplicate
            $DuplicateFields = $this->CheckDuplicate($Patient, $PatientId);
            if (count($DuplicateFields) > 0) {
                throw new \Exception('Duplicate Patient: ' . implode(', ', $DuplicateFields));
            }
            // Update Patient
            $Mod = new DBSMOD_User($Link, $DB_TABLE);
            $ValuePairs = $Patient->toDBArray(array_keys($Data));
            $UpdateResult = $Mod->Update($PatientId, $ValuePairs);
            if ($UpdateResult === false) {
                throw new \Exception('Update Patient Failed: ' . $Mod->Error->getMessage());
            }
            return App::NoContentResponse();
        } catch (\Throwable $th) {
            return App::JsonResponse(['message' => $th->getMessage()], 400);
        }
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
            $Act->AddPreCondition("concat(FullName,Birthday,REPLACE(TEL1,'-',''),MobileTEL1) like concat('%',:search,'%')", ['search' => $Data['search']], 'or');
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

    public function CheckDuplicate($Patient, $ID = null)
    {
        global $Link, $DB_TABLE, $User_Tb;
        $Act = new DBSAction($Link, $DB_TABLE, $User_Tb);
        $Act->AddSelect(['IDTNO', 'TEL1', 'MobileTEL1', 'EML1']);
        $Act->AddPreCondition('IDTNO = :nationalId', ['nationalId' => $Patient->nationalId], 'or');
        $Act->AddPreCondition('TEL1 = :tel', ['tel' => $Patient->tel], 'or');
        $Act->AddPreCondition('MobileTEL1 = :mobile', ['mobile' => $Patient->mobile], 'or');
        $Act->AddPreCondition('EML1 = :email', ['email' => $Patient->email], 'or');
        if (isset($ID)) {
            $Act->AddPreCondition('UserID != :id', ['id' => $ID], 'and');
        }
        $Act->RenderSQL('_SELECT');
        $DuplicateUsers = $Act->ExecuteSQL();
        $DuplicateFields = [];
        if ($DuplicateUsers !== false && count($DuplicateUsers) > 0) {
            foreach ($DuplicateUsers as $User) {
                if ($User['TEL1'] === $Patient->tel) {
                    $DuplicateFields[] = 'tel=' . $Patient->tel;
                }
                if ($User['MobileTEL1'] === $Patient->mobile) {
                    $DuplicateFields[] = 'mobile=' . $Patient->mobile;
                }
                if ($User['EML1'] === $Patient->email) {
                    $DuplicateFields[] = 'email=' . $Patient->email;
                }
                if ($User['IDTNO'] === $Patient->nationalId) {
                    $DuplicateFields[] = 'nationalId=' . $Patient->nationalId;
                }
            }
            $DuplicateFields = array_unique($DuplicateFields);
        }
        return $DuplicateFields;
    }
}