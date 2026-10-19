<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication {
        createApplication as baseCreateApplication;
    }

    /**
     * テストで使ってよいデータベース名
     */
    const TESTING_DATABASE = 'smaf_testing';

    /**
     * アプリケーションを作成する
     *
     * テストではデータベースを作り直すため、テスト専用のデータベースに
     * つながっていない場合は、開発用のデータを消さないように実行を止める.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = $this->baseCreateApplication();

        $connection = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$connection}.database");
        if ($database !== self::TESTING_DATABASE) {
            throw new RuntimeException(
                "テスト用ではないデータベース \"{$database}\" につながっているため、テストを中止しました. "
                ."phpunit.xml の DB_DATABASE と、設定のキャッシュ（php artisan config:clear）を確認してください."
            );
        }

        return $app;
    }
}
