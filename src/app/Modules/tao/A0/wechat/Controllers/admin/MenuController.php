<?php

namespace App\Modules\tao\A0\wechat\Controllers\admin;

use App\Modules\tao\A0\open\Models\OpenApp;
use App\Modules\tao\A0\open\Services\OpenAppService;
use App\Modules\tao\A0\open\Services\WechatApplicationService;
use App\Modules\tao\A0\wechat\Models\WechatMenu;
use App\Modules\tao\BaseController;
use Phax\Mvc\Request;
use Phax\Utils\MyData;

/**
 * @property WechatMenu $model
 */
class MenuController extends BaseController
{
    public function initialize(): void
    {
        die('暂不开放，直接到微信公众号后台设置');
        $this->model = new WechatMenu();
        parent::initialize();
    }

    /**
     * @rbac ({title:'菜单列表'})
     * @return array|\Phalcon\Http\Response|string[]
     * @throws \Exception
     */
    public function indexAction()
    {
        if (Request::isApiRequest()) {
            $rows = OpenAppService::rows();

            $gzh = array_filter($rows, function ($item) {
                return OpenApp::isGzh($item['kind']);
            });
            $menus = WechatMenu::queryBuilder()
                ->findColumn(['id', 'sync', 'sync_at', 'appid'], 'appid');
            $records = [];

            foreach ($gzh as $item) {
                $row = [
                    'appid' => $item['appid'],
                    'kind' => $item['kind'],
                    'title' => $item['title'],
                    'sync' => 0,
                    'sync_at' => 0,
                ];
                if (isset($menus[$item['appid']])) {
                    $row['sync'] = $menus[$item['appid']]['sync'];
                    $row['sync_at'] = $menus[$item['appid']]['sync_at'];
                }
                $records[] = $row;
            }
            return $this->successPagination(count($records), $records);
        }
        return [
        ];
    }

    /**
     * @param $appID
     * @return \App\Modules\tao\A0\wechat\Models\WechatMenu
     * @throws \Exception
     */
    private function getModel($appID): WechatMenu
    {
        $wc = OpenAppService::getWith($appID);
        if (!OpenApp::isGzh($wc['kind'])) {
            throw new \Exception('当前 appID 不是公众号');
        }
        /**
         * @var $model \App\Modules\tao\A0\wechat\Models\WechatMenu
         */
        $model = WechatMenu::queryBuilder()
            ->where('appid', $appID)->findFirst(false);
        if (empty($model)) {
            $model = new WechatMenu();
            $model->appid = $appID;
            if ($model->save() === false) {
                throw new \Exception('创建菜单模型失败');
            }
        }
        return $model;
    }

    /**
     * @rbac ({title:'菜单属性修改‘})
     */
    public function modifyAction()
    {
        Request::mustPost();
        $data = Request::getData();
        MyData::mustHasSet($data, ['appid', 'field', 'value'], ['value']);
        if (!in_array($data['field'], ['sync'])) {
            throw new \Exception('不允许修改的字段');
        }
        $model = $this->getModel($data['appid']);
        $model->sync = intval($data['value']);
        return $model->save()
            ? $this->success('保存成功')
            : $this->error($model->getErrors());
    }

    /**
     * @rbac ({title:"编辑菜单"})
     * @throws \Exception
     */
    public function editAction()
    {
        $appID = $this->request->getQuery('appid');
        if (empty($appID)) {
            throw new \Exception('必须提供 appID');
        }
        $model = $this->getModel($appID);
        if ($this->request->isPost()) {
            $data = Request::getData();
            switch ($data['action']) {
                case 'getCurrent':
                    $app = WechatApplicationService::getOfficialApplication($appID);
                    $api = $app->getClient();
                    $response = $api->get('/cgi-bin/get_current_selfmenu_info');
                    if ($response->isFailed()) {
                        return $this->error($response->getContent(false));
                    } else {
                        return $this->success($response->getContent(false));
                    }
                    break;
                case 'sync':
                    break;
            }
            dd($data);
        }
        return [
            'mo' => $model->toArray(),
        ];
    }
}