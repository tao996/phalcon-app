<?php

namespace app\Modules\tao\A0\wechat\Controllers\admin;

use app\Modules\tao\A0\wechat\Models\WechatConfig;
use app\Modules\tao\A0\wechat\Models\WechatPayApp;
use app\Modules\tao\A0\wechat\Services\WechatConfigService;
use app\Modules\tao\A0\wechat\Services\WechatPayService;
use app\Modules\tao\BaseController;
use Phax\Mvc\Request;
use Phax\Support\Validate;
use Phax\Utils\Data;

/**
 * @property WechatPayApp $model
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
            'app_id|公众号ID' => 'required',
            'mch_id|商户号ID' => 'required',
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
        Data::mustHasSet($data, ['id', 'name']);
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
            : $this->error($this->model->getErrors(true));
    }
}