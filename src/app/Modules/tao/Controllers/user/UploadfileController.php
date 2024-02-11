<?php

namespace app\Modules\tao\Controllers\user;

use app\Modules\tao\BaseController;
use app\Modules\tao\Models\SystemUploadfile;
use Phax\Db\QueryBuilder;

/**
 * 文件管理
 */
class UploadfileController extends BaseController
{
    protected array $allowModifyFields = ['summary'];
    public array $enableActions = ['index', 'modify'];
    public bool $disableUpdateActions = true;

    public function initialize(): void
    {
        $this->model = new SystemUploadfile();
        parent::initialize();
    }

    protected function indexActionQueryBuilder(QueryBuilder $queryBuilder): void
    {
        if (!$this->loginUser->isSuperAdmin()) {
            $queryBuilder->int('user_id', $this->loginUser->userId());
        }
        if ($keyword = $this->request->getQuery('title')) {
            $queryBuilder->like('summary', $keyword);
        }

        parent::indexActionQueryBuilder($queryBuilder);
    }
}