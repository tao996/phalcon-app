<?php

namespace App\Modules\tao\A0\wechat\Controllers\admin;

use App\Modules\tao\A0\wechat\Models\WechatPayApp;
use App\Modules\tao\A0\wechat\Services\WechatPayService;
use App\Modules\tao\BaseController;
use Phax\Mvc\Request;
use Phax\Support\Validate;
use Phax\Utils\MyData;

/**
 * @rbac ({title:'微信支付'})
 * @property \App\Modules\tao\A0\open\Models\WechatPayApp $model
 */
class PayController extends BaseController
{
    protected $indexHiddenColumns = ['secret_key'];
    protected array|string $superAdminActions = '*';

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new WechatPayApp();
    }

    protected function indexActionGetResult(int $count, \Phax\Db\QueryBuilder $queryBuilder): array
    {
        $rows = parent::indexActionGetResult($count, $queryBuilder);
        foreach ($rows as $index => $row) {
            $rows[$index]['private_key'] = !empty($row['private_key']);
            $rows[$index]['platform_cert'] = !empty($row['platform_cert']);
            $rows[$index]['certificate'] = !empty($row['certificate']);
        }
        return $rows;
    }

    protected function beforeModelSaveAssign($data)
    {
        Validate::check($data, [
            'appid|公众号ID' => 'required',
            'mchid|商户号ID' => 'required',
            'secret_key|V3 api 秘钥' => 'required',
        ]);
        return $data;
    }

    /**
     * @rbac ({title:'上传证书'})
     */
    public function certAction()
    {
        Request::mustPost();
        $data = Request::getData();
        MyData::mustHasSet($data, ['id', 'name']);
        if (!in_array($data['name'], ['private_key', 'certificate', 'platform_cert'])) {
            return $this->error('不支持上传的证书类型');
        }

        $this->model = WechatPayApp::mustFindFirst($data['id']);
        if (\request()->hasFiles()) {
            $f = \request()->getUploadedFiles()[0];
            $saveName = md5_file($f->getTempName());

            $dir = WechatPayService::pathCertDir();
            if ($f->moveTo($dir . $saveName)) {
                $this->model->assign([
                    $data['name'] => $saveName
                ]);
            } else {
                return $this->error('保存上传证书失败');
            }
        } else {
            $this->model->assign([$data['name'] => '']);
        }
        return $this->model->save()
            ? $this->success('保存成功')
            : $this->error($this->model->getFirstError());
    }
}