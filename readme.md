quick start to use phalcon framework like Laravel and ThinkPHP

## 特性

* write test like go
* 支持 多模块/子模块/子目录 功能

## 警告

* 项目当前还处于开发阶段，请勿使用在生产环境中，后果自负


## Quick Start

`git clone https://github.com/tao996/phalcon-app.git`

为了启动项目，你需要修改相关的 `example` 配置文件（或者执行 `./deploy.sh` 以代替以下操作）

* 将 `.env.example` 复制为 `.env` 以配置 `docker-composer` 项目
* 将 `nginx/default.example.conf` 复制为 `nginx/default.conf` 以对 `nginx` 进行配置
* 将 `php/extra.example.ini` 复制为 `extra.ini` 以对 `php` 进行配置
* (开发阶段)将 `src/.env.example` 复制为 `.env` 以配置 `phalcon` 项目
* 将 `src/config/config.example.php` 复制为 `src/config/config.php` 对项目进行配置
* `chown -R 1000:1000 src/storage`

根据需要对上面的配置文件进行修改

```
docker-composer up -d
```


