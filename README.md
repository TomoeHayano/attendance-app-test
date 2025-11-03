# attendance-app-test 環境構築

## Dokerビルド

- git clone git@github.com:TomoeHayano/attendance-app-test.git
- docker-compose up -d --build

## laravel環境構築

- docker-compose exec php bash

- composer install

- cp .env.example .env

- php artisan key:generate

- php artisan migrate

- php artisan db:seed

- php artisan storage:link

## URL一覧
- 会員登録画面（一般ユーザー）:http://register
- ログイン画面（一般ユーザー）:http://login
- 出勤登録画面（一般ユーザー）:http://attendance
- 勤怠登録画面（一般ユーザー）:http://attendance/action
- 勤怠一覧画面（一般ユーザー）:http://attendance/list
- 勤怠詳細画面（一般ユーザー）:http://attendance/detail/{id}
- 勤怠詳細画面（一般ユーザー）:http://stamp_correction_request/list
- ログイン画面（管理者）     :http://admin/login
- 勤怠一覧画面（管理者）     :http://admin/attendance/list
- 勤怠詳細画面（管理者）     :http://admin/attendance/{id}
- スタッフ一覧画面（管理者）  :http://admin/staff/list
- スタッフ別勤怠一覧画面（管理者）:http://admin/attendance/staff/{id}
- 申請一覧画面（管理者）     :http://stamp_correction_request/list
- 修正申請承認画面（管理者）  :http://stamp_correction_request/approve/{attendance_correct_request_id}
- phpMyAdmin:http://localhost:8080
- mailHog:http://localhost:8025

## 使用技術（実行環境）
- nginx:1.21.1
- mysql:8.0.26
- docker:3.8
- php:8.1

## ER図
## テーブル仕様書


## 環境変数
- .envとenv.testingに<br>
STRIPE_KEY・STRIPE_SECRET　は未設定のため、KEYの取得をお願いいたします。

## 環境変数

## Unitテスト実行方法
- このプロジェクトでは、LaravelのFeatureテストを一部実装しています。<br>
テスト実行環境はDockerコンテナ内で完結します。

### 初期設定
- docker-compose exec php bash
- mysql -u root -p<br>
※ パスワード: root
- CREATE DATABASE demo_test;
- exit
- exit
- docker-compose exec php bash
- php artisan key:generate --env=testing
- php artisan migrate:fresh --env=testing

### 実行手順
- docker-compose exec php bash
- php artisan test tests/Feature/＊各ファイル名＊
