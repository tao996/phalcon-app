<?php

namespace App\Modules\tao\A0\open\Controllers;

use App\Modules\tao\A0\open\BaseDeveloperOpenController;
use App\Modules\tao\A0\open\Models\OpenUserOpenid;
use Phax\Support\Validate;

class UserController extends BaseDeveloperOpenController
{
    protected array|string $userActions = '*';

    /**
     * 更新用户资料
     */
    public function infoAction()
    {
        $record = OpenUserOpenid::queryBuilder()
            ->int('user_id', $this->loginUser->userId())
            ->string('appid', $this->getAppid())
            ->findFirst(false);
        if (!$record) {
            throw new \Exception('没有找到 userOpenid 记录');
        }
        if ($this->request->isGet()) {
            return $record->toArray([
                'user_id', 'avatar_url', 'nickname', 'openid'
            ]);
        } elseif ($this->request->isPost()) {
            $data = $this->requestMiniData();
            Validate::check($data, ['name' => 'required', 'value' => 'required']);
            if (!in_array($data['name'], ['avatar_url', 'nickname'])) {
                throw new \Exception('不允许修改的字段');
            }

            $record->assign([
                $data['name'] => $data['value']
            ],
                ['avatar_url', 'nickname', 'gender', 'city', 'province', 'country']
            );
            return $this->saveModelResponse($record->save(), false);
        }
        return [];
    }

    public function logoutAction()
    {
//        $this->loginUser->logout();
        return '退出成功';
    }
}