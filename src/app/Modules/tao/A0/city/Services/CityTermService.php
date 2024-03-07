<?php

namespace app\Modules\tao\A0\city\Services;

use app\Modules\tao\A0\city\Models\CityTerm;
use Phax\Utils\Data;

class CityTermService
{
    /**
     * 通过名称查询球队
     * @param string $name 名称或关键字
     * @param bool $like 是否使用 like 查询
     * @param array $columns 查询的字段
     * @return array|null like=true 时返回多维数组
     * @throws \Exception
     */
    public static function findByName(string $name, array $columns = [], bool $like = true): array|null
    {
        if (empty($name)) {
            throw new \Exception('必须指定待查询的球队名称');
        }
        $qb = CityTerm::queryBuilder()->columns($columns);
        if ($like) {
            return $qb->like('name', $name)->find();
        } else {
            return $qb->string('name', $name)->findFirst();
        }
    }

    /**
     * 可选球队列表
     * @return array
     * @throws \Exception
     */
    public static function options(): array
    {
        $rows = CityTerm::queryBuilder()->columns(['id', 'name'])
            ->find();
        return $rows
            ? array_column($rows, 'name', 'id')
            : [];
    }

    /**
     * 追加球隊信息
     * @param array $rows
     * @param string $name
     * @return void
     * @throws \Exception
     */
    public static function appendTerm(array &$rows, string $name = 'term_id'): void
    {
        if (!empty($rows)) {
            $termIds = array_column($rows, $name);
            if ($terms = CityTerm::queryBuilder()->inInt('id', $termIds)
                ->findColumn(['id', 'avatar', 'name', 'nickname', 'address', 'leader'])) {
                foreach ($rows as $index => $row) {
                    foreach ($terms as $term) {
                        if ($term['id'] == $row[$name]) {
                            $rows[$index]['term'] = $term;
                            break;
                        }
                    }
                }
            }
        }
    }
}