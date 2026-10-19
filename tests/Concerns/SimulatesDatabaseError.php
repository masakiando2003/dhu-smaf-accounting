<?php

namespace Tests\Concerns;

use PDOException;

/**
 * DB エラーを起こして、トランザクションのロールバックを確認するためのテスト用トレイト
 */
trait SimulatesDatabaseError
{
    /**
     * 起こす DB エラーのメッセージ
     *
     * @var string
     */
    private static $simulatedDatabaseErrorMessage = 'テスト用の DB エラー';

    /**
     * 指定したモデルを保存するときに DB エラー（PDOException）を起こす
     *
     * @param  string  $model_class  モデルのクラス名
     * @return void
     */
    protected function failOnSaving(string $model_class)
    {
        $message = self::$simulatedDatabaseErrorMessage;
        $model_class::saving(function () use ($message) {
            throw new PDOException($message);
        });
    }

    /**
     * 処理を実行し、起こした DB エラーがそのまま投げられることを確認する
     *
     * @param  callable  $callback  実行する処理
     * @return void
     */
    protected function assertThrowsSimulatedDatabaseError(callable $callback)
    {
        try {
            $callback();
            $this->fail('例外が投げられませんでした');
        } catch (PDOException $e) {
            $this->assertSame(self::$simulatedDatabaseErrorMessage, $e->getMessage());
        }
    }
}
