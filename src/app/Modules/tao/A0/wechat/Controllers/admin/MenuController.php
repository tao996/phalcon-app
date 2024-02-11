<?php

namespace app\Modules\tao\A0\wechat\Controllers\admin;

use app\Modules\tao\BaseController;
use app\Modules\tao\A0\wechat\Models\WechatApp;
use app\Modules\tao\A0\wechat\Models\WechatMenu;
use app\Modules\tao\A0\wechat\Services\WechatAppService;
use app\Modules\tao\A0\wechat\Services\WechatApplicationService;
use Phax\Mvc\Request;
use Phax\Utils\Data;

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
            $rows = WechatAppService::rows();

            $gzh = array_filter($rows, function ($item) {
                return WechatApp::isGzh($item['kind']);
            });
            $menus = WechatMenu::queryBuilder()
                ->findColumn(['id', 'sync', 'sync_at', 'app_id'], 'app_id');
            $records = [];

            foreach ($gzh as $item) {
                $row = [
                    'app_id' => $item['app_id'],
                    'kind' => $item['kind'],
                    'title' => $item['title'],
                    'sync' => 0,
                    'sync_at' => 0,
                ];
                if (isset($menus[$item['app_id']])) {
                    $row['sync'] = $menus[$item['app_id']]['sync'];
                    $row['sync_at'] = $menus[$item['app_id']]['sync_at'];
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
     * @return \app\Modules\tao\A0\wechat\Models\WechatMenu
     * @throws \Exception
     */
    private function getModel($appID): WechatMenu
    {
        $wc = WechatAppService::getWith($appID);
        if (!WechatApp::isGzh($wc['kind'])) {
            throw new \Exception('当前 appID 不是公众号');
        }
        /**
         * @var $model \app\Modules\tao\A0\wechat\Models\WechatMenu
         */
        $model = WechatMenu::queryBuilder()
            ->where('app_id', $appID)->findFirst(false);
        if (empty($model)) {
            $model = new WechatMenu();
            $model->app_id = $appID;
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
        Data::mustHasSet($data, ['app_id', 'field', 'value'], ['value']);
        if (!in_array($data['field'], ['sync'])) {
            throw new \Exception('不允许修改的字段');
        }
        $model = $this->getModel($data['app_id']);
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
        $appID = $this->request->getQuery('app_id');
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