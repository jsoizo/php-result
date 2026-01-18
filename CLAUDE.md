# php-result

PHP 8.2+ 向けの型安全な Result 型ライブラリ（PHPStan対応）

## コマンド

```bash
composer test       # Pest テスト実行
composer analyse    # PHPStan 静的解析
composer cs-check   # コードスタイルチェック
composer cs-fix     # コードスタイル自動修正
```

## コーディングルール

- 修正後は `composer cs-fix` → `composer analyse` → `composer test` の順で実行