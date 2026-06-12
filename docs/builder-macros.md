# Builder 宏方法

在 Model 中使用 `ModelTrait` 获得 IDE 自动补全：

```php
use LaravelDev\App\Traits\ModelTrait;

class User extends BaseUser
{
    use ModelTrait;
}
```

## 条件查询宏

这些宏只有在参数中**存在且非空**时才会生效，非常适合处理前端传来的筛选条件：

```php
// 假设 $params 是 request()->validate() 的结果
$params = request()->validate([
    'name' => 'nullable|string',
    'status' => 'nullable|integer',
    'keyword' => 'nullable|string',
    'amount' => 'nullable|array',
    'created_at' => 'nullable|array',
]);

User::query()
    ->ifWhere($params, 'status')                          // status = ?
    ->ifWhereLike($params, 'name')                        // name LIKE %?%
    ->ifWhereLikeKeyword($params, 'keyword', ['name', 'email']) // name LIKE %?% OR email LIKE %?%
    ->ifWhereNumberRange($params, 'amount')               // amount BETWEEN ? AND ?
    ->ifWhereDateRange($params, 'created_at')             // created_at BETWEEN ? AND ?
    ->ifIsNull($params, 'is_deleted', 'deleted_at')       // deleted_at IS NULL
    ->ifHasWhereLike($params, 'role_name', 'roles', 'name') // 关联表 roles.name LIKE %?%
    ->order()                                              // 按 sorter 参数排序（兼容 Ant Design）
    ->page();                                              // 分页
```

## 完整宏列表

| 宏 | 用法 | 说明 |
|-----|------|------|
| `ifWhere($params, $key, $field)` | 等值查询 | `$params[$key]` 非空时 `where($field, $value)` |
| `ifWhereLike($params, $key, $field)` | 模糊查询 | 自动加 `%` 通配符 |
| `ifWhereLikeKeyword($params, $key, $fields)` | 多字段关键词 | OR 搜索多个字段 |
| `ifWhereNumberRange($params, $key, $field)` | 数值范围 | 传入 `[min, max]` 二元数组 |
| `ifWhereDateRange($params, $key, $field, $type)` | 日期范围 | 传入 `[start, end]`，支持 `date` 和 `datetime` 类型 |
| `ifIsNull($params, $key, $field)` | NULL 判断 | `true` → `whereNull`，`false` → `whereNotNull` |
| `ifIsNotNull($params, $key, $field)` | NOT NULL 判断 | 与 `ifIsNull` 相反 |
| `ifHasWhereLike($params, $key, $relation, $field)` | 关联模糊查询 | `whereHas` + LIKE |
| `order($key, $default)` | 排序 | 兼容 Ant Design 的 `sorter` 参数，防 SQL 注入 |
| `page()` | 分页 | 自动读取 `perPage` 参数，校验合法值 |
| `getById($id, $throw, $lock, $msg)` | ID 查找 | 找不到时抛异常，支持行锁 |
| `forSelect($key1, $key2, $orderBy)` | 下拉选项 | 返回 `[[id, name], ...]` 格式 |
| `unique($params, $keys, $label, $field)` | 唯一性校验 | 重复时抛异常，排除自身（编辑场景） |
