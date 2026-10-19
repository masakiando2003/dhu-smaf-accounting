# dhu-smaf-accounting
株式会社スマフー会計システム（デジタルハリウッド大学 2019学園祭）

## 概要
デジタルハリウッド大学 2019年度 3Q「アカウンティング基礎」の授業で、
学園祭出店演習のために作った会計システムです。

## 使用技術
- PHP 7.2以上
- Laravel 6.x
- MySQL
- AdminLTE 3（管理画面テンプレート / jeroennoten/laravel-adminlte）
- jQuery UI 1.12.1（オートコンプリート）
- Laravel Mix 4 / Sass / Vue.js 2
- Docker（Laradock）

## 工夫した点
- 商品検索にオートコンプリート機能を実装
  - 注文入力時に商品名の一部を入力すると、候補が Ajax で表示されます
  - 商品を選ぶと単価を自動で取得します

## 動かし方
### 1. リポジトリと Laradock の取得
Laradock はリポジトリと同じ階層に clone します。
```bash
git clone https://github.com/masakiando2003/dhu-smaf-accounting.git
git clone https://github.com/laradock/laradock.git
cd laradock
cp .env.example .env
```

`laradock/.env` の次の2か所を変更します。
このアプリは Laravel 6 なので、PHP 8 では動きません。必ず PHP 7.4 にしてください。
```
APP_CODE_PATH_HOST=../dhu-smaf-accounting/
PHP_VERSION=7.4
```

### 2. php-fpm の Dockerfile の修正
PHP 7.4 の php-fpm のベースイメージは Debian 11（bullseye）です。
サポートが終了しているため、そのままではパッケージのダウンロードが 404 になり、ビルドに失敗します。
`laradock/php-fpm/Dockerfile` の `LABEL maintainer=...` の行の後に、次の処理を追加してください。
パッケージの取得先が Debian のアーカイブに切り替わります。
```dockerfile
# Debian 11 (bullseye) はサポート終了のため、パッケージ取得先をアーカイブに切り替える
RUN if grep -q bullseye /etc/os-release; then \
      printf '%s\n' \
        'deb http://archive.debian.org/debian bullseye main' \
        'deb http://archive.debian.org/debian-security bullseye-security main' \
        'deb http://archive.debian.org/debian bullseye-updates main' \
        > /etc/apt/sources.list; \
      rm -f /etc/apt/sources.list.d/*; \
    fi
```

### 3. コンテナのビルドと起動
```bash
docker compose build workspace php-fpm
docker compose up -d nginx mysql
```

### 4. Laravel の初期設定
```bash
docker compose exec -u laradock workspace bash
# 以下はコンテナ内（/var/www）で実行
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

`php artisan migrate` の前に、`.env` の DB 設定を Laradock の初期値に合わせてください。
```
DB_HOST=mysql
DB_DATABASE=default
DB_USERNAME=default
DB_PASSWORD=secret
```

ビルド済みの CSS・JS（`public/css/app.css`・`public/js/app.js`）はリポジトリに含まれています。
そのため、画面を表示するだけなら `npm install` や `npm run dev` は不要です。

http://localhost にアクセスすると、管理画面のダッシュボードが表示されます。

#### テストデータの投入（任意）
動作確認用の商品・注文・現金出納帳のデータを入れたい場合は、workspace コンテナ内で次のコマンドを実行します。
会社情報とメンバー情報は変更されません。テストデータの商品がすでにある場合は、何もせずにスキップします。
```bash
php artisan db:seed --class=TestDataSeeder
```

### トラブルシューティング
#### nginx コンテナがポート80で起動できない
`docker compose up` で次のエラーが出る場合は、ホスト側（WSL など）の nginx や Apache が先にポート80を使っています。
```
failed to bind host port 0.0.0.0:80/tcp: address already in use
```

ポート80を使っているプロセスは、次のコマンドで確認できます。
```bash
sudo ss -ltnp | grep ':80 '
```

ホスト側の nginx を使っていなければ、停止して自動起動もオフにします。
```bash
sudo systemctl stop nginx
sudo systemctl disable nginx
```

ホスト側の nginx を止められない場合は、`laradock/.env` に次の1行を追加すると、Laradock の nginx のポートを変えられます。
この場合のアクセス先は http://localhost:8081 になります。
```
NGINX_HOST_HTTP_PORT=8081
```

#### nginx コンテナが再起動を繰り返す
`docker ps` で nginx が `Restarting` のままになり、`docker logs laradock-nginx-1` に次のエラーが出る場合があります。
```
nginx: [emerg] host not found in upstream "php-fpm:9000"
```
nginx コンテナが、php-fpm のいるネットワークにつながっていない状態です。
ポート80のエラーなどで一度起動に失敗すると、この状態になることがあります。
nginx コンテナを作り直すと直ります。
```bash
docker compose up -d --force-recreate --no-deps nginx
```

#### `vendor/autoload.php` が見つからず 500 エラーになる
「4. Laravel の初期設定」の `composer install` がまだ済んでいません。
workspace コンテナに入って実行してください。

### 参考
- [Laradock環境構築](https://qiita.com/maejima_f/items/cb35327d0ae8c78a5bb9)
- [Laradockでmysql設定](https://qiita.com/maejima_f/items/34cf3b8eb5ffbe256ad9)
- [Laradockのmysqlが起動できない時の対応](https://qiita.com/bunty/items/681a74de001b7ed1c022)
