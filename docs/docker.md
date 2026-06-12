# Docker 容器化部署

本工具包生成的定时任务和队列在容器化环境中需要常驻进程。推荐使用 **Supervisor** 管理。

## 定时任务（Schedule）

创建 `docker/laravel-schedule.conf`：

```ini
[program:laravel-schedule]
command=php artisan schedule:work
autostart=true
autorestart=true
priority=10
stdout_events_enabled=true
stderr_events_enabled=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
stopsignal=QUIT
```

> `schedule:work` 会在前台持续运行，每分钟检查一次是否有到期任务。开发环境也可用 `schedule:run` 配合 cron。

## 队列（Queue）

创建 `docker/laravel-queue.conf`：

```ini
[program:laravel-queue]
command=php artisan queue:work
autostart=true
autorestart=true
priority=10
stdout_events_enabled=true
stderr_events_enabled=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
stopsignal=QUIT
```

## Docker Compose 集成示例

```yaml
services:
  app:
    build: .
    volumes:
      - ./:/var/www/html
    # 如果使用 Supervisor 管理多个进程：
    # command: supervisord -n -c /etc/supervisor/supervisord.conf
```

将 `.conf` 文件放到容器内的 `/etc/supervisor/conf.d/` 目录下，Supervisor 会自动加载。
