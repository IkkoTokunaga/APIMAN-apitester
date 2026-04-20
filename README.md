# API Tester

Laravel 12 (PHP 8.4) + Alpine.js + Tailwind CSS v4 で構築された、**単一コンテナ型の APIテスター**です。
ホスト OS を汚さずに `docker compose up` 一発で起動でき、開発時はホットリロード、配布時はそのまま実行可能なオールインワン構成になっています。

ブラウザから HTTP リクエスト（GET / POST / PUT / PATCH / DELETE / HEAD / OPTIONS）を発行し、レスポンス・実行履歴・保存済みリクエスト・コレクションを SQLite で管理できます。

---

## 目次
1. [機能概要](#1-機能概要)
2. [動作要件](#2-動作要件)
3. [クイックスタート](#3-クイックスタート)
4. [画面の使い方](#4-画面の使い方)
5. [API エンドポイント一覧](#5-api-エンドポイント一覧)
6. [データの永続化とバックアップ](#6-データの永続化とバックアップ)
7. [開発者向け情報](#7-開発者向け情報)
8. [Docker Hub への配布](#8-docker-hub-への配布)
9. [トラブルシューティング](#9-トラブルシューティング)

---

## 1. 機能概要

| 機能 | 説明 |
| --- | --- |
| APIプロキシ | サーバー側 (`Http` ファサード) からリクエストを送信するため、CORS の制約を受けずに外部 API を叩けます。 |
| 履歴管理 | 送信したリクエスト/レスポンス本文・ヘッダー・ステータス・所要時間を SQLite に自動保存。最新50件を一覧表示。 |
| 保存リクエスト | よく使うリクエストを名前を付けて保存。ワンクリックで再利用可能。 |
| コレクション | 保存リクエストをコレクション単位（プロジェクト・API ごと等）で整理。 |
| インポート / エクスポート | 保存リクエストを JSON で一括バックアップ／復元できます。 |
| 単一コンテナ | Nginx + PHP-FPM + Supervisor + SQLite を 1 コンテナに同梱。配布も簡単。 |

---

## 2. 動作要件

- Docker Engine 24 以降
- Docker Compose v2 以降
- ブラウザ（Chrome / Edge / Firefox / Safari の最新版を推奨）

> ホスト側に PHP / Node.js / Composer をインストールする必要は **ありません**。

---

## 3. クイックスタート

### 3.1 リポジトリを取得

```bash
git clone <このリポジトリのURL> api-tester
cd api-tester
```

### 3.2 起動（初回ビルド込み）

```bash
docker compose up --build
```

初回起動時に以下が自動で行われます。

1. `.env` の生成（`.env.example` をコピー）と `APP_KEY` の発行
2. `database/database.sqlite` の作成
3. `php artisan migrate --force` によるテーブル作成
4. 開発モード時は `npm install` と Vite Dev Server (5173) の起動

### 3.3 ブラウザでアクセス

| URL | 用途 |
| --- | --- |
| http://localhost:19876 | API テスター本体（UI） |
| http://localhost:5173 | Vite Dev Server（開発時のみ。ホットリロード用） |

### 3.4 停止

```bash
docker compose down
```

`./database` ディレクトリを残したままなので、再度 `up` すれば履歴・保存リクエストはそのまま復元されます。

### 3.5 ホスト側 UID/GID を合わせて起動したい場合

バインドマウントしたファイルの所有権をホストユーザーと揃えたいときは、ビルド時に UID/GID を渡してください。

```bash
UID=$(id -u) GID=$(id -g) docker compose build
docker compose up
```

---

## 4. 画面の使い方

メイン画面 (`http://localhost:19876`) は大きく **左サイドバー** と **中央のリクエスト/レスポンスエリア** で構成されています。

### 4.1 リクエストを送信する

1. **メソッド** を選択（GET / POST / PUT / PATCH / DELETE / HEAD / OPTIONS）。
2. **URL** に対象のエンドポイントを入力（例: `https://jsonplaceholder.typicode.com/posts/1`）。
3. 必要に応じて **Headers** を `Key: Value` 形式で追加。
   - 例: `Authorization: Bearer xxxxx`
   - 例: `Content-Type: application/json`
4. POST/PUT/PATCH の場合は **Body** に JSON などを入力。
5. **Send** ボタンを押すとサーバー経由でリクエストが送信されます。
6. 結果が **Response** エリアに表示されます。
   - ステータスコード（色分け表示）
   - 所要時間（ms）
   - レスポンスヘッダー
   - レスポンスボディ（JSON は整形表示）

> リクエストはサーバー側の `Http` ファサードから送信されるため、ブラウザの CORS 制約は受けません。

### 4.2 履歴を見る・再送する

- 画面下部または履歴パネルに **直近 50 件** の実行履歴が表示されます。
- 履歴の行をクリックすると、そのリクエスト内容が入力欄に展開されます。
- 不要な履歴は個別削除、もしくは「全削除」で一括削除できます。

### 4.3 リクエストを保存する

1. 入力欄に保存したいリクエスト内容を準備。
2. **Save** ボタンから「タイトル」と「コレクション（任意）」を指定して保存。
3. 左サイドバーの保存リクエスト一覧から、いつでもクリック 1 つで読み込めます。

### 4.4 コレクションで整理する

- 「コレクション」は保存リクエストをまとめるフォルダのような単位です。
- API ごと・プロジェクトごとに作成して整理できます。
- リクエスト保存時にコレクション名を指定すると、自動的に作成・紐付けされます。

### 4.5 インポート / エクスポート

保存リクエストとコレクション情報を JSON で書き出し／読み込みできます。

- **エクスポート**:
  ```bash
  curl http://localhost:19876/api/saved-requests/export -o saved-requests.json
  ```
- **インポート**:
  ```bash
  curl -X POST http://localhost:19876/api/saved-requests/import \
       -H "Content-Type: application/json" \
       -d @saved-requests.json
  ```

JSON フォーマット例:

```json
{
  "items": [
    {
      "title": "ユーザー一覧取得",
      "method": "GET",
      "url": "https://example.com/api/users",
      "request_headers": { "Authorization": "Bearer xxx" },
      "request_body": null,
      "content_type": "application/json",
      "collection": "Example API"
    }
  ]
}
```

`collection` は文字列または `null`。存在しないコレクション名を指定した場合は自動で作成されます。

---

## 5. API エンドポイント一覧

UI から呼ばれている内部 API です。CLI や別ツールから操作したい場合に利用できます。
ベース URL: `http://localhost:19876`

### 5.1 プロキシ / 履歴

| メソッド | パス | 説明 |
| --- | --- | --- |
| `POST`   | `/api/proxy` | リクエストを送信し、結果を返す（履歴にも保存） |
| `GET`    | `/api/history` | 直近 50 件の履歴を返す |
| `GET`    | `/api/history/{id}` | 履歴 1 件の詳細 |
| `DELETE` | `/api/history/{id}` | 履歴を 1 件削除 |
| `DELETE` | `/api/history` | 履歴を全削除 |

`POST /api/proxy` のリクエスト例:

```json
{
  "method": "POST",
  "url": "https://example.com/api/login",
  "headers": { "Content-Type": "application/json" },
  "body": "{\"email\":\"a@example.com\",\"password\":\"secret\"}"
}
```

### 5.2 コレクション

| メソッド | パス | 説明 |
| --- | --- | --- |
| `GET`    | `/api/collections` | コレクション一覧（保存リクエストを含む） |
| `POST`   | `/api/collections` | 新規作成 (`{ "name": "..." }`) |
| `PUT`    | `/api/collections/{id}` | 名称変更 |
| `DELETE` | `/api/collections/{id}` | 削除 |

### 5.3 保存リクエスト

| メソッド | パス | 説明 |
| --- | --- | --- |
| `GET`    | `/api/saved-requests` | 一覧 |
| `POST`   | `/api/saved-requests` | 新規保存 |
| `GET`    | `/api/saved-requests/{id}` | 詳細 |
| `PUT`    | `/api/saved-requests/{id}` | 更新 |
| `DELETE` | `/api/saved-requests/{id}` | 削除 |
| `POST`   | `/api/saved-requests/import` | 一括インポート |
| `GET`    | `/api/saved-requests/export` | 一括エクスポート |

---

## 6. データの永続化とバックアップ

- データはすべて **SQLite ファイル** (`./database/database.sqlite`) に保存されます。
- `docker-compose.yml` で `./database` をバインドマウントしているため、コンテナを削除しても消えません。
- バックアップしたい場合はこのファイルをコピーするだけで完結します。

```bash
cp database/database.sqlite database/database.$(date +%Y%m%d).sqlite.bak
```

リセットしたいときは、停止後にファイルを削除すれば次回起動時に再作成されます。

```bash
docker compose down
rm database/database.sqlite
docker compose up
```

---

## 7. 開発者向け情報

### 7.1 ディレクトリ構成（抜粋）

```
api-tester/
├── app/Http/Controllers/    # ApiProxy / Collection / SavedRequest コントローラ
├── database/                # SQLite 本体（永続化）+ migrations
├── docker/                  # Nginx, Supervisor, entrypoint
├── resources/views/         # welcome.blade.php (Alpine.js UI)
├── resources/css, resources/js
├── routes/web.php           # API ルーティング
├── Dockerfile               # マルチステージビルド (Node→PHP)
└── docker-compose.yml
```

### 7.2 開発モード（ホットリロード）

`docker-compose.yml` の `APP_ENV` は `local` を既定値としています。
この場合、`entrypoint.sh` が **dev 用 Supervisor 設定** を読み込み、Vite Dev Server (5173) を起動します。

ソースコードを編集すると：
- PHP / Blade: 即時反映（OPcache のタイムスタンプ検証 ON）
- CSS / JS: Vite が自動で再ビルドしブラウザに HMR

### 7.3 本番モードで動かす

`APP_ENV=production` で起動すると Vite は起動せず、Dockerfile でビルド済みのアセット (`public/build/`) を配信します。

```bash
APP_ENV=production docker compose up -d
```

`config:cache` / `route:cache` / `view:cache` も自動実行されます。

### 7.4 よく使うコマンド

```bash
# コンテナに入る
docker compose exec app sh

# Artisan
docker compose exec app php artisan migrate
docker compose exec app php artisan tinker

# Composer / npm
docker compose exec app composer install
docker compose exec app npm install
```

### 7.5 Git 運用

`.cursorrules` に記載のとおり、Cursor へ「Push」と指示すると以下が自動実行されます。

1. `git status` で差分を確認
2. 作業内容を要約したコミットメッセージを生成
3. `git add .` → `git commit`
4. 現在のブランチを `origin` へ `git push`

---

## 8. Docker Hub への配布

本イメージは **`tokuppee15/api-tester:latest`** として Docker Hub に配布する想定です。
配布側（メンテナ）と利用側（エンドユーザー）のそれぞれの手順を示します。

### 8.1 利用者向け（Docker Hub から pull して使う）

ソースコードは不要です。**`docker-compose.dist.yml` 1 ファイル**だけあれば起動できます。

```bash
# 1. 配布用 Compose ファイルを取得（リポジトリからダウンロード or コピー）
curl -O https://raw.githubusercontent.com/<OWNER>/<REPO>/main/docker-compose.dist.yml

# 2. 起動（初回は pull が走ります）
docker compose -f docker-compose.dist.yml up -d

# 3. ブラウザで http://localhost:19876 を開く
```

更新が出たときは次のコマンドで最新版に差し替えできます。

```bash
docker compose -f docker-compose.dist.yml pull
docker compose -f docker-compose.dist.yml up -d
```

停止・完全削除：

```bash
docker compose -f docker-compose.dist.yml down          # 停止 (DBは残る)
docker compose -f docker-compose.dist.yml down -v       # ボリュームごと削除
```

> SQLite データはホストの `./database/` に保存されます。バックアップはこのフォルダをコピーするだけです。

### 8.2 メンテナ向け（GitHub Actions で Docker Hub に push する）

本リポジトリには `.github/workflows/release.yml` を用意しており、**Git タグを push するだけでマルチアーキ (amd64 + arm64) ビルド → Docker Hub push → Docker Hub README 同期 → GitHub Release 作成**まで自動で行われます。

#### 8.2.1 初回セットアップ（1回だけ）

1. [Docker Hub](https://hub.docker.com/) でリポジトリ `tokuppee15/api-tester` を作成（Public 推奨）。
2. Docker Hub の [Account Settings → Personal access tokens](https://hub.docker.com/settings/personal-access-tokens) で **Read, Write, Delete** 権限の PAT を発行。
3. GitHub の該当リポジトリで **Settings → Secrets and variables → Actions → New repository secret** から以下 2 件を登録：
   - `DOCKERHUB_USERNAME`: `tokuppee15`
   - `DOCKERHUB_TOKEN`: 上で発行した PAT
4. 機密情報がイメージに混入していないか `.dockerignore` を確認（`.env` / `database.sqlite` などが除外されていること）。

#### 8.2.2 リリース手順（毎回）

```bash
# 1. 変更を main に反映
git switch main
git pull --rebase
# ... 変更を commit ...
git push

# 2. バージョンタグを切って push するだけ
git tag v1.2.3
git push origin v1.2.3
```

タグ push をトリガに GitHub Actions が自動で以下を実行します。

1. `linux/amd64` → `ubuntu-latest` ランナーでネイティブビルド
2. `linux/arm64` → `ubuntu-24.04-arm` ランナーでネイティブビルド（並列）
3. 2つの digest をマニフェストリストにまとめて push
   - `tokuppee15/api-tester:latest`
   - `tokuppee15/api-tester:1.2.3`
   - `tokuppee15/api-tester:1.2`（マイナー）
4. Docker Hub の README を本リポジトリの `README.md` で自動同期
5. GitHub Release をコミット差分から自動生成

> キャッシュは GitHub Actions Cache (`type=gha`) に保存されるため、2回目以降のビルドは数分で完了します。

#### 8.2.3 手動実行（workflow_dispatch）

タグを付けずに最新 `main` を `:edge` タグで push したいときは、Actions タブの **Release to Docker Hub → Run workflow** から手動実行できます（Docker Hub README 同期と GitHub Release 作成はスキップされます）。

#### 8.2.4 ローカルから push したい場合（非推奨・緊急時のみ）

Actions が使えない状況でローカルからマルチアーキ push したいときは次の通りですが、Docker Desktop の `credsStore: desktop.exe` 環境だと buildx コンテナから認証情報を参照できないため、`DOCKER_CONFIG` を一時ディレクトリで分離する必要があります。

```bash
# 初回のみ: docker-container driver のビルダーを作成
docker buildx create --name api-tester-builder --driver docker-container --use

# 一時的な認証ディレクトリを作成（~/.docker/config.json は変更しない）
TMPCFG=$(mktemp -d)
cp -r ~/.docker/buildx "$TMPCFG/buildx"
echo -n "https://index.docker.io/v1/" | docker-credential-desktop.exe get | \
  python3 -c "import sys,json,base64,os; d=json.load(sys.stdin); a=base64.b64encode(f\"{d['Username']}:{d['Secret']}\".encode()).decode(); json.dump({'auths':{'https://index.docker.io/v1/':{'auth':a}}}, open(os.environ['TMPCFG']+'/config.json','w'))" TMPCFG="$TMPCFG"

# ビルド & push
VERSION=1.0.0
DOCKER_CONFIG="$TMPCFG" docker buildx build \
  --builder api-tester-builder \
  --platform linux/amd64,linux/arm64 \
  -t tokuppee15/api-tester:latest \
  -t tokuppee15/api-tester:${VERSION} \
  --push .

# 後片付け
rm -rf "$TMPCFG"
```

> `linux/arm64` は QEMU エミュレーションで動くため 10〜20 分かかります。GitHub Actions ならネイティブ arm64 ランナーで並列実行されるため数分で完了します。

#### 8.2.5 バージョニングの指針

| タグ | 用途 |
| --- | --- |
| `latest` | 常に最新の安定版を指す |
| `1.2.3` | セマンティックバージョン（リリースごとに固定） |
| `1.2`   | マイナーバージョンの最新 |
| `edge` / `dev` | 開発版（任意） |

GitHub Actions は `v1.2.3` というタグから `latest` / `1.2.3` / `1.2` を自動で付けます。

#### 8.2.6 配布物に含めないもの

`.dockerignore` で除外済みですが、念のため確認してください。

- `.env`（ローカルの秘密情報）
- `database/database.sqlite`（ユーザーデータ）
- `node_modules` / `vendor`（イメージ内で再インストール）
- `.git` / `tests` / `README.md` 等の開発用ファイル

#### 8.2.7 push 後の動作確認

```bash
# ローカルのビルド成果物を一度消してから pull してみる
docker rmi tokuppee15/api-tester:latest
docker compose -f docker-compose.dist.yml pull
docker compose -f docker-compose.dist.yml up -d
curl -I http://localhost:19876
```

---

## 9. トラブルシューティング

| 症状 | 対処 |
| --- | --- |
| `19876` がすでに使用中で起動できない | `docker-compose.yml` の `ports` を `"29876:80"` 等、任意の空きポートに変更する。 |
| 履歴 / 保存リクエストが消えた | `./database/database.sqlite` が存在するか確認。バックアップから復元するか、削除して再作成。 |
| ホストから `database/` を編集できない | UID/GID を揃えて再ビルド： `UID=$(id -u) GID=$(id -g) docker compose build` |
| 画面が真っ白 / アセットが読み込めない | 開発時は Vite (5173) が起動しているか確認。本番時は `npm run build` 済みかを確認。 |
| マイグレーションが失敗する | `docker compose exec app php artisan migrate:status` で状態を確認。必要に応じて `migrate:fresh` を実行（**全データ消去**）。 |
| 外部 API へのリクエストが `0` (タイムアウト) になる | コンテナから対象ホストへ到達できるか `docker compose exec app curl -I <URL>` で確認。 |

---

## ライセンス

本プロジェクトは MIT ライセンスです。Laravel 本体のライセンスについては [Laravel](https://laravel.com) を参照してください。
