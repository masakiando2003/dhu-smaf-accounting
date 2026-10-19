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
# .env の APP_CODE_PATH_HOST を ../dhu-smaf-accounting/ に変更
```

### 2. コンテナの起動
```bash
docker compose up -d nginx mysql
```

### 3. Laravel の初期設定
```bash
docker compose exec workspace bash
# 以下はコンテナ内（/var/www）で実行
composer install
cp .env.example .env
php artisan key:generate
# .env の DB_HOST=mysql、DB_DATABASE / DB_USERNAME / DB_PASSWORD を Laradock の設定に合わせる
php artisan migrate --seed
npm install && npm run dev
```

http://localhost にアクセスすると動作を確認できます。

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

### 参考
- [Laradock環境構築](https://qiita.com/maejima_f/items/cb35327d0ae8c78a5bb9)
- [Laradockでmysql設定](https://qiita.com/maejima_f/items/34cf3b8eb5ffbe256ad9)
- [Laradockのmysqlが起動できない時の対応](https://qiita.com/bunty/items/681a74de001b7ed1c022)
