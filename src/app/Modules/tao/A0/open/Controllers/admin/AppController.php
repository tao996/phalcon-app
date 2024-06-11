<?php

namespace App\Modules\tao\A0\open\Controllers\admin;

use App\Modules\tao\A0\open\Models\OpenApp;
use App\Modules\tao\A0\open\Services\OpenAppService;
use App\Modules\tao\BaseController;
use Phax\Mvc\Request;
use Phax\Support\Validate;
use Phax\Utils\MyData;

/**
 * @rbac ({title:'开放平台应用管理'})
 */
class AppController extends BaseController
{

    protected array $saveWhiteList = [
        'platform', 'kind',
        'appid', 'title', 'secret',
        'token', 'enc_method', 'aes_key', 'crop_id',
        'sandbox', 'remark',
    ];

    protected $indexHiddenColumns = ['secret'];
    protected string $indexOrder = 'sort desc,id desc';
    protected array $allowModifyFields = ['status', 'sort', 'online','sandbox'];

    public function initialize(): void
    {
        $this->model = new OpenApp();
        parent::initialize();
    }

    protected function indexActionGetResult(int $count, \Phax\Db\QueryBuilder $queryBuilder): array
    {
        $rows = parent::indexActionGetResult($count, $queryBuilder);
        foreach ($rows as $index => $row) {
            $rows[$index]['public_key'] = !empty($row['public_key']);
            $rows[$index]['rsa_public_key'] = !empty($row['rsa_public_key']);
            $rows[$index]['rsa_private_key'] = !empty($row['rsa_private_key']);
        }
        return $rows;
    }

    protected function beforeModelSaveAssign($data)
    {
        Validate::check($data, [
            'appid' => 'required',
            'platform|平台' => 'required',
            'title|应用名称' => 'required',
            'kind|应用类型' => 'required',
            'secret' => 'required'
        ]);
        $data['sandbox'] = (int)MyData::getBool($data, 'sandbox');
        return $data;
    }

    /**
     * @rbac ({title:'修改证书'})
     */
    public function certAction()
    {
        Request::mustPost();
        $data = Request::getData();

        Validate::check($data, [
            'id' => 'required|int',
            'name' => 'required|in:public_key,rsa_public_key,rsa_private_key',
        ]);
        $this->model = OpenApp::mustFindFirst($data['id']);
        $pIndexName = OpenApp::getPIndex($data['name']);

        // 清除证书
        if (isset($data['value']) && empty($data['value'])) {
            $this->model->assign([
                $data['name'] => '', $pIndexName => 0
            ]);
            return $this->model->save()
                ? $this->success('清除证书成功')
                : $this->error($this->model->getFirstError());
        }

        // 上传证书
        if (\request()->hasFiles()) {
            $f = \request()->getUploadedFiles()[0];
            $v = file_get_contents($f->getTempName());
        } else { // 输入证书
            $v = MyData::getString($data, 'value');
        }

        if (OpenAppService::encrypt($this->model, $data['name'], $v)) {
            OpenAppService::cache();
            return $this->success('保存证书成功');
        } else {
            return $this->error($this->model->getFirstError());
        }
    }

    protected function afterModelChange(string $action)
    {
        OpenAppService::cache();
    }

}