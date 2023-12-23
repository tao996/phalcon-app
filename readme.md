quick start to use phalcon framework like Laravel and ThinkPHP

## 特性

* write test like go
* 支持 多模块/子模块/子目录 功能

## 警告

* 项目当前还处于开发阶段，请勿使用在生产环境中，后果自负


## Quick Start

### 半自动
下载 `wget https://raw.githubusercontent.com/tao996/phalcon-app/main/app.sh`
然后通过 `sh app.sh create xxx` 来创建项目

### 全手动

`git clone https://github.com/tao996/phalcon-app.git`

为了启动项目，你需要修改相关的 `example` 配置文件
（以下几个复制步骤可通过执行 `sh deploy.sh` 以代替）

* 将 `.env.example` 复制为 `.env` 以配置 `docker-composer` 项目
* 将 `nginx/default.example.conf` 复制为 `nginx/default.conf` 以对 `nginx` 进行配置
* 将 `php/extra.example.ini` 复制为 `extra.ini` 以对 `php` 进行配置
* (开发阶段)将 `src/.env.example` 复制为 `.env` 以配置 `phalcon` 项目
* 将 `src/config/config.example.php` 复制为 `src/config/config.php` 对项目进行配置



## 修改 

执行 `chmod 777 src/storage -R` 否则没权限写临时文件

1. `.env` 中的数据库密码
2. `src/config/config.php` 中应用配置的密钥

根据需要对上面的配置文件进行修改，然后再启动服务

```
docker-composer up -d

# 检查系统开放的端口
ss -antp
```

