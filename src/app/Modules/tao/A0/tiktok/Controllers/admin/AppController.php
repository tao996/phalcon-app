<?php

namespace app\Modules\tao\A0\tiktok\Controllers\admin;

use app\Modules\tao\A0\tiktok\Models\TiktokApp;
use app\Modules\tao\A0\wechat\Helper\CertSecretHelper;
use app\Modules\tao\A0\wechat\Services\WechatPayService;
use app\Modules\tao\BaseController;
use Phax\Mvc\Request;
use Phax\Support\Validate;
use Phax\Utils\Data;

/**
 * @rbac ({title:'抖音应用'})
 */
class AppController extends BaseController
{
    protected $indexHiddenColumns = ['secret'];
    protected array|string $superAdminActions = '*';
    protected array $saveWhiteList = [
        'appid', 'title', 'kind', 'secret',
        'sandbox', 'remark'
    ];

    public function initialize(): void
    {
        $this->model = new TiktokApp();
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
            'title|应用名称' => 'required',
            'kind|应用类型' => 'required',
            'secret' => 'required'
        ]);
        $data['sandbox'] = (int)Data::getBool($data, 'sandbox');
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
        $this->model = TiktokApp::mustFindFirst($data['id']);
        $pIndexName = TiktokApp::getPIndex($data['name']);

        // 清除证书
        if (isset($data['value']) && empty($data['value'])) {
            $this->model->assign([
                $data['name'] => '', $pIndexName => 0
            ]);
            return $this->model->save()
                ? $this->success('清除证书成功')
                : $this->error($this->model->getErrors(true));
        }

        // 上传或者输入证书
        if (\request()->hasFiles()) {
            $f = \request()->getUploadedFiles()[0];
            $v = file_get_contents($f->getTempName());
        } else {
            $v = Data::getString($data, 'value');
        }

        if (strlen($v) < 100) {
            throw new \Exception('证书内容过短或不符合规范？');
        }
        $fMd5 = md5($v);
        $pIndex = rand(30, 80);
        $newContent = CertSecretHelper::encryptData($v, $pIndex, 5);

        $this->model->assign([
            $data['name'] => $fMd5,
            $pIndexName => $pIndex,
        ]);

        if ($this->model->save()) {
            $dir = WechatPayService::pathCertDir();
            if (!file_put_contents($dir . $fMd5, $newContent)) {
                return $this->error('保存证书失败');
            }
            return $this->success('保存证书成功');
        } else {
            return $this->error($this->model->getErrors(true));
        }
    }

}