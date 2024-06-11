<?php

namespace App\Modules\tao\Controllers\admin;

use App\Modules\tao\BaseController;
use App\Modules\tao\Helper\RbacAnnotation;
use App\Modules\tao\Models\SystemNode;
use App\Modules\tao\Models\SystemRoleNode;
use App\Modules\tao\Services\NodeService;
use Phax\Db\QueryBuilder;
use Phax\Support\Config;
use Phax\Utils\MyFileSystem;

/**
 * @rbac ({title:'节点管理'})
 * @property SystemNode $model
 */
class NodeController extends BaseController
{
    public array $enableActions = [
        'index', 'reload', 'modify'
    ];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new SystemNode();
    }

    protected array $allowModifyFields = [
        'title'
    ];

    protected function indexActionGetResult(int $count, QueryBuilder $queryBuilder): array
    {
        $rows = $this->getSystemNodes($queryBuilder);
        return NodeService::nodeTree($rows);
    }

    private function getSystemNodes(QueryBuilder $queryBuilder = null): array
    {
        if (is_null($queryBuilder)) {
            $queryBuilder = SystemNode::queryBuilder();
        }
        return $queryBuilder
            ->columns(['id', 'kind', 'type', 'module', 'node', 'title', 'is_auth', 'ac'])
            ->disabledPagination()
            ->find();
    }

    /**
     * @rbac({title:'更新节点'})
     */
    public function reloadAction($todb = false)
    {
        $nodes = [];

        // 当前项目
        $project = Config::currentProject();
        if (!empty($project)) {
            $baseInfo = RbacAnnotation::projectBaseInfo($project);
            $nodes = array_merge($nodes, RbacAnnotation::getNodes($baseInfo));
        }
//dd($project,$nodes);
        $modules = MyFileSystem::findInDirs(PATH_APP_MODULES, 'dir');

        foreach ($modules as $module) {
            if (in_array($module, ['demo'])) {
                continue;
            }
            $baseInfo = RbacAnnotation::moduleBaseInfo($module);
            $nodes = array_merge($nodes, RbacAnnotation::getNodes($baseInfo));
        }
//        dd($modules, $nodes);


        $dbNodes = $this->getSystemNodes(); // 原节点
        $rows = NodeService::compare($dbNodes, $nodes);

        if ($todb) {
            // append, update, delete
            $deleteIds = []; // 删除的节点
            foreach ($rows['delete'] as $row) {
                $deleteIds[] = $row['id'];
            }
            $pdo = pdo();
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('UPDATE ' . SystemNode::getObject()->getSource() . ' SET title=:title, is_auth=:is_auth, ac=2 WHERE id=:id');
                foreach ($rows['update'] as $row) {
                    $stmt->execute([
                        'title' => $row['title'], 'is_auth' => $row['is_auth'], 'id' => $row['id']
                    ]);
                }
                // 添加新的记录
                foreach ($rows['append'] as $index => $row) {
                    $rows['append'][$index]['ac'] = SystemNode::AC_INSERT;
                }
                SystemNode::batchInsert($rows['append'], [], false);

                // 移除旧的记录
                if ($deleteIds) {
                    $sql = 'DELETE FROM ' . SystemNode::getObject()->getSource() . ' WHERE id IN (' . join(',', $deleteIds) . ')';
                    $pdo->exec($sql);

                    // 移除角色绑定
                    $roleNodeSQL = 'DELETE FROM ' . SystemRoleNode::getObject()->getSource() . ' WHERE node_id IN (' . join(',', $deleteIds) . ')';
                    $pdo->exec($roleNodeSQL);
                }

                $pdo->commit();
            } catch (\Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            $qb = SystemNode::queryBuilder();
            $rows = $this->indexActionGetResult(0, $qb);
        } else {
            foreach ($rows['append'] as $row) {
                foreach ($nodes as $index => $node) {
                    if (NodeService::sameNode($row, $node)) {
                        $nodes[$index]['ac'] = SystemNode::AC_INSERT;
                        break;
                    }
                }
            }
            foreach ($rows['update'] as $row) {
                foreach ($nodes as $index => $node) {
                    if (NodeService::sameNode($row, $node)) {
                        $nodes[$index]['ac'] = SystemNode::AC_UPDATE;
                        break;
                    }
                }
            }
            $rows = NodeService::nodeTree($nodes);
        }
        return $this->successPagination(count($rows), $rows);
    }
}