# dhu-smaf-accounting
株式会社スマフー会計システム（デジタルハリウッド大学 2019学園祭）

## 概要
デジタルハリウッド大学 2019年度 3Q「アカウンティング基礎」の授業で、
学園祭出店演習のために作った会計システムです。

## 使用技術
- PHP 7.4（`composer.json` の要件は 7.2 以上。PHP 8 では動きません）
- Laravel 6.x
- MySQL
- AdminLTE 3（管理画面テンプレート / jeroennoten/laravel-adminlte）
- jQuery UI 1.12.1（オートコンプリート）
- Laravel Mix 4 / Sass / Vue.js 2
- PHPUnit 8
- Docker（Laradock）

## 工夫した点
- 商品検索にオートコンプリート機能を実装
  - 注文入力時に商品名の一部を入力すると、候補が Ajax で表示されます
  - 商品を選ぶと単価を自動で取得します
- 出店中にひと目で状況がわかるダッシュボード
  - 売上合計・注文数・現金残高・利益、在庫が少ない商品、売れ筋ランキング、最近の注文を表示します
- 役割ごとに分けた設計
  - 入力チェックはリクエストクラス、DB 操作はサービスクラスに分け、コントローラーを薄く保っています
- 自動テスト
  - Feature テストとユニットテストで、注文・在庫・現金出納帳・会社の現金の計算を確認しています
- 存在しないページやデータには、日本語の 404 画面を表示します

## 設計
### 処理の流れ
```
routes/web.php → リクエストクラス（入力チェック） → コントローラー → サービスクラス（DB 操作） → モデル
```
- コントローラーは「リクエストを受け取る・サービスを呼ぶ・画面を表示する／リダイレクトする」だけを担当します
- サービスクラスは、コントローラーにコンストラクタインジェクションで渡されます

### フォルダ構成
| フォルダ・ファイル | 役割 |
|---|---|
| `routes/web.php` | 画面のルート。`{id}` は数字だけを受け付けます |
| `app/Http/Controllers` | コントローラー |
| `app/Http/Requests` | 入力チェック（`ItemRequest`・`OrderRequest`・`CashierRequest`） |
| `app/Http/Requests/Concerns` | リクエストクラスの共通処理（日時の手入力の変換） |
| `app/Services` | DB 操作（`ItemService`・`OrderService`・`CashierService`・`DashboardService`・`ReportService`） |
| `app/Services/Concerns` | サービスクラスの共通処理（一覧のページ送り） |
| `app/Model` | モデル（レポートの集計メソッドは `Order` モデルにあります） |
| `resources/views/admin` | 管理画面のビュー |
| `resources/views/errors/404.blade.php` | 日本語の 404 画面 |
| `database/seeds/TestDataSeeder.php` | 動作確認用のテストデータ（任意で投入） |
| `database/factories` | テスト用のファクトリー |
| `tests/Feature` | 画面・ルートのテスト |
| `tests/Unit` | サービスクラス・リクエストクラス・モデルのテスト |
| `tests/Concerns` | テストの共通処理（DB エラーを起こしてロールバックを確認する `SimulatesDatabaseError`） |

### 開発時のルール
- DB 操作はコントローラーに書かず、サービスクラスに書きます
- 入力チェックはリクエストクラス（FormRequest）に書きます
- 依存するクラスはコンストラクタで受け取ります（DI）。テストでもサービスは `new` ではなく `$this->app->make()` で作ります
- ID でデータを取得するときは `findOrFail()` を使い、見つからない場合は 404 にします
- 複数のテーブルを更新する処理は `$this->db->transaction()` で囲み、途中でエラーが起きたらすべて元に戻します
- ルートに `{id}` を使うときは `->where('id', '[0-9]+')` を付けます
- ファサード（`Route`・`DB` など）は `use` で読み込みます（エディタの「Undefined type」の警告を防ぐため）
- クラス・メソッドには PHPDoc を書きます

## 主な仕様
### 注文と在庫・現金出納帳・会社の現金
| 操作 | 在庫 | 現金出納帳 | 会社の現金（`company_info.cash`） |
|---|---|---|---|
| 注文の登録 | 注文した数量だけ減らす | 支払金額が 0円より多い場合、「収入」の記録を作る | 支払金額が 0円より多い場合、合計金額を足す |
| 注文の編集 | 変更前の数量を戻してから、変更後の数量を引く | 収入の記録を更新する | 変更前の合計金額を引いて、変更後の合計金額を足す |
| 注文の削除 | 注文した数量を戻す | 収入の記録を削除する | 収入の記録がある場合、合計金額を引く |

- 合計金額は、注文明細の 単価 × 数量 の合計です。削除チェックを付けた明細は含めません
- 削除はすべて論理削除（`deleted_at`）です

### ダッシュボードの計算
- 売上合計：削除されていない注文の 単価 × 数量 の合計
- 経費：現金出納帳の「支払」の合計
- 利益：売上合計 − 経費
- 現金残高：初期資本金 ＋ 売上合計 − 経費（レポートの貸借対照表と同じ考え方）
- 在庫が少ない商品：在庫が初期在庫の 2割以下の商品

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

### テストの実行
テストは、開発用とは別のテスト専用データベース `smaf_testing` を使います。
テストのたびにデータベースを作り直すため、開発用のデータベースは使いません。
誤って開発用のデータベースにつながっている場合は、データを消さないようにテストが止まります。

初回だけ、`laradock` フォルダでテスト用のデータベースを作成します。
```bash
docker compose exec mysql mysql -uroot -proot -e "CREATE DATABASE IF NOT EXISTS smaf_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL PRIVILEGES ON smaf_testing.* TO 'default'@'%';"
```

その後、workspace コンテナ内でテストを実行します。
```bash
vendor/bin/phpunit
```

一部のテストだけを実行することもできます。
```bash
vendor/bin/phpunit --testsuite Unit                 # ユニットテストだけ
vendor/bin/phpunit tests/Feature/OrderTest.php      # ファイルを指定
vendor/bin/phpunit --filter testDeleteSoftDeletes   # テスト名で絞り込み
```

テストを書くときのポイントです。
- データの作成には、`database/factories/ModelFactory.php` のファクトリーを使います（`factory(Item::class)->create()`）
- 同梱の Faker は古く、PHP 7.4 では `sentence()` などの文章生成がエラーになります。ファクトリーでは固定の文字列を使ってください
- 現在日時に依存する処理は、`Carbon::setTestNow()` で日時を固定してテストします
- トランザクションのロールバックは、`Tests\Concerns\SimulatesDatabaseError` トレイトの `failOnSaving()` と `assertThrowsSimulatedDatabaseError()` で確認できます

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

#### ログファイルに書き込めず、エラーの原因がわからない
画面やテストで、次のようなエラーが出る場合があります。
```
The stream or file "/var/www/storage/logs/laravel-YYYY-MM-DD.log" could not be opened in append mode: failed to open stream: Permission denied
```
`root` で `php artisan` などを実行したときに、ログファイルが `root` の持ち物として作られた状態です。
`laradock` フォルダで次のコマンドを実行し、持ち主を戻してください。
```bash
docker compose exec workspace chown -R laradock:laradock /var/www/storage/logs
```
今後は、`docker compose exec -u laradock workspace bash` のように `-u laradock` を付けてコンテナに入ると防げます。

#### テストが「テスト用ではないデータベース」で止まる
テストが、開発用のデータベースにつながろうとしています。
`phpunit.xml` の `DB_DATABASE` が `smaf_testing` になっているかを確認してください。
`php artisan config:cache` で設定をキャッシュしている場合は、`php artisan config:clear` でキャッシュを消してください。

### 参考
- [Laradock環境構築](https://qiita.com/maejima_f/items/cb35327d0ae8c78a5bb9)
- [Laradockでmysql設定](https://qiita.com/maejima_f/items/34cf3b8eb5ffbe256ad9)
- [Laradockのmysqlが起動できない時の対応](https://qiita.com/bunty/items/681a74de001b7ed1c022)
