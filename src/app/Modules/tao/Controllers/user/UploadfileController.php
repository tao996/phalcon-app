<?php

namespace App\Modules\tao\Controllers\user;

use App\Modules\tao\BaseController;
use App\Modules\tao\Models\SystemUploadfile;
use Phax\Db\QueryBuilder;

/**
 * 文件管理
 */
class UploadfileController extends BaseController
{
    protected array|string $userActions = '*';
    public array $enableActions = ['index', 'modify', 'add'];

    protected array $allowModifyFields = ['summary'];
    protected $indexQueryColumns = ['id', 'upload_type', 'summary', 'url', 'width', 'height', 'file_size', 'created_at'];

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