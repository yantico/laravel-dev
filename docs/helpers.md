# 辅助工具类

## SchemaHelper —— Migration 辅助方法

```php
use LaravelDev\App\Helpers\SchemaHelper;

Schema::create('orders', function (Blueprint $table) {
    $table->id();

    // 枚举列（自动读取枚举值，注释中标记枚举类）
    SchemaHelper::Enum($table, 'status', OrderStatusEnum::class, '订单状态');

    // 外键列（注释中标记关联表）
    SchemaHelper::ForeignId($table, 'user_id', '用户', 'users');

    // 地址字段组（生成 province_code、city_code、area_code、street_code + 4个名称字段）
    SchemaHelper::Cities($table, 'receiver');

    $table->timestamps();
});
```

## FastExcelHelper —— Excel 流式导入导出

```php
use LaravelDev\App\Helpers\FastExcelHelper;

// 导出（基于 cursor，内存友好，适合大数据量）
FastExcelHelper::Export(
    query: Order::query()->where('status', 'completed'),
    filename: '订单列表',
    callback: fn($order) => [
        '订单号' => $order->no,
        '金额' => $order->amount,
        '创建时间' => $order->created_at->toDateString(),
    ],
    ext: 'xlsx'  // 支持 csv / xlsx
);

// 导入（自动事务包裹，任意一行失败全部回滚）
FastExcelHelper::Import($file, function ($row) {
    User::create([
        'name' => $row['姓名'],
        'email' => $row['邮箱'],
    ]);
});
```

## AwsS3Helper —— S3 文件上传

```php
use LaravelDev\App\Helpers\AwsS3Helper;

// 生成预签名上传 URL（前端直传，自动生成 ULID 文件名）
$uploadInfo = AwsS3Helper::PreUpload('avatars', 'photo.jpg');
// 返回: ['url' => 'https://s3.../presigned-url', 'headers' => ['Content-Type' => ...]]

// 生成临时访问 URL
$url = AwsS3Helper::TemporaryUrl('avatars/01HXXX.jpg', 30);

// 富文本中的 Base64 图片 → 上传到 S3 → 替换为 URL（引用传递，直接修改原数组）
AwsS3Helper::ReplaceImageToOss($params, ['content'], 'editor', 800, 80);

// 生成缩略图（引用传递，直接在 $images 中添加 thumbUrl 字段）
AwsS3Helper::CreateThumbUrl($images, 200, 80);
// 支持 阿里云 OSS 和 腾讯云 COS 图片处理参数
```

## IpHelper

```php
use LaravelDev\App\Helpers\IpHelper;

$ip = IpHelper::GetIp(); // 等同于 request()->ip()
```

## LaravelPermissionHelper —— 权限树构建

```php
use LaravelDev\App\Helpers\LaravelPermissionHelper;

// 获取用户的权限树（用于前端菜单/权限展示）
$tree = LaravelPermissionHelper::getPermissionByUser($user);
```
