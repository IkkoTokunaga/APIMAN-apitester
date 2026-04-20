# API Tester

ブラウザから HTTP リクエスト（GET / POST / PUT / PATCH / DELETE / HEAD / OPTIONS）を発行し、レスポンス・実行履歴・保存済みリクエスト・コレクションを SQLite で管理できる、**単一コンテナ型の APIテスター**です。

`docker compose up` 一発で起動でき、ホスト OS に PHP / Node.js / Composer 等をインストールする必要はありません。

---

## 目次
1. [できること](#1-できること)
2. [動作要件](#2-動作要件)
3. [インストールと起動](#3-インストールと起動)
4. [画面の使い方](#4-画面の使い方)
5. [データの永続化とバックアップ](#5-データの永続化とバックアップ)
6. [API エンドポイント一覧](#6-api-エンドポイント一覧)
7. [トラブルシューティング](#7-トラブルシューティング)

---

## 1. できること

| 機能 | 説明 |
| --- | --- |
| APIプロキシ | サーバー側（Laravel の `Http` ファサード）からリクエストを送信するため、ブラウザの CORS 制約を受けずに外部 API を叩けます。 |
| 履歴管理 | 送信したリクエスト／レスポンス本文・ヘッダー・ステータス・所要時間を自動保存。最新50件を一覧表示。 |
| 保存リクエスト | よく使うリクエストに名前を付けて保存。ワンクリックで再利用できます。 |
| コレクション | 保存リクエストをプロジェクト・API 単位で整理できます。 |
| インポート / エクスポート | 保存リクエストとコレクションを JSON で一括バックアップ／復元できます。 |
| 単一コンテナ | Nginx + PHP-FPM + SQLite を 1 コンテナに同梱。面倒な環境構築不要。 |

---

## 2. 動作要件

- Docker Engine 24 以降
- Docker Compose v2 以降
- ブラウザ（Chrome / Edge / Firefox / Safari の最新版を推奨）

---

## 3. インストールと起動

Docker Hub に公開済みのイメージを利用します。**ソースコードの取得は不要**で、`docker-compose.dist.yml` 1 ファイルだけあれば起動できます。

### 3.1 初回起動

```bash
# 1. 配布用 Compose ファイルを取得
curl -O https://raw.githubusercontent.com/IkkoTokunaga/APIMAN-apitester/main/docker-compose.dist.yml

# 2. 起動（初回はイメージ pull が走ります）
docker compose -f docker-compose.dist.yml up -d

# 3. ブラウザで以下を開く
#    http://localhost:19876
```

### 3.2 更新（新しいバージョンに差し替える）

```bash
docker compose -f docker-compose.dist.yml pull
docker compose -f docker-compose.dist.yml up -d
```

### 3.3 停止

```bash
# 停止（データは残る）
docker compose -f docker-compose.dist.yml down

# 停止＋ボリューム削除（データも消える）
docker compose -f docker-compose.dist.yml down -v
```

SQLite のデータファイル（履歴・保存リクエスト）はホストの `./database/` に保存されるため、`down` だけなら次回 `up` 時にそのまま復元されます。

### 3.4 ポートを変更したい場合

`19876` がすでに使われている場合は、`docker-compose.dist.yml` の `ports` を `"29876:80"` のように任意の空きポートへ書き換えてください。

---

## 4. 画面の使い方

メイン画面（ http://localhost:19876 ）は、左サイドバー（保存リクエスト・コレクション）と中央のリクエスト／レスポンスエリアで構成されています。

### 4.1 リクエストを送信する

1. **メソッド** を選択（GET / POST / PUT / PATCH / DELETE / HEAD / OPTIONS）。
2. **URL** に対象のエンドポイントを入力。
   - 例: `https://jsonplaceholder.typicode.com/posts/1`
3. 必要に応じて **Headers** を `Key: Value` 形式で追加。
   - 例: `Authorization: Bearer xxxxx`
   - 例: `Content-Type: application/json`
4. POST / PUT / PATCH の場合は **Body** に JSON などを入力。
5. **Send** ボタンを押すとサーバー経由でリクエストが送信されます。
6. 結果が **Response** エリアに表示されます。
   - ステータスコード（色分け表示）
   - 所要時間（ms）
   - レスポンスヘッダー
   - レスポンスボディ（JSON は自動で整形）

> リクエストはサーバー側から送信されるため、ブラウザの CORS 制約は受けません。

### 4.2 履歴を見る・再送する

- 履歴パネルに **直近 50 件** の実行履歴が表示されます。
- 履歴の行をクリックすると、そのリクエスト内容が入力欄に展開されます。
- 不要な履歴は個別削除、または「全削除」で一括削除できます。

### 4.3 リクエストを保存する

1. 入力欄に保存したいリクエスト内容を準備。
2. **Save** ボタンから「タイトル」と「コレクション（任意）」を指定して保存。
3. 左サイドバーの保存リクエスト一覧から、いつでもクリック 1 つで読み込めます。

### 4.4 コレクションで整理する

- 「コレクション」は保存リクエストをまとめるフォルダのような単位です。
- API ごと・プロジェクトごとに作成して整理できます。
- リクエスト保存時にコレクション名を指定すると、存在しない場合は自動で作成・紐付けされます。

### 4.5 インポート / エクスポート

保存リクエストとコレクション情報を JSON で書き出し／読み込みできます。

エクスポート:

```bash
curl http://localhost:19876/api/saved-requests/export -o saved-requests.json
```

インポート:

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

## 5. データの永続化とバックアップ

- データはすべて **SQLite ファイル** (`./database/database.sqlite`) に保存されます。
- `docker-compose.dist.yml` で `./database` をバインドマウントしているため、コンテナを削除しても消えません。
- バックアップはこのファイルをコピーするだけで完結します。

```bash
cp database/database.sqlite database/database.$(date +%Y%m%d).sqlite.bak
```

リセットしたいときは、停止後にファイルを削除すれば次回起動時に再作成されます。

```bash
docker compose -f docker-compose.dist.yml down
rm database/database.sqlite
docker compose -f docker-compose.dist.yml up -d
```

---

## 6. API エンドポイント一覧

UI から呼ばれている内部 API です。CLI や別ツールから直接操作したい場合に利用できます。
ベース URL: `http://localhost:19876`

### 6.1 プロキシ / 履歴

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

### 6.2 コレクション

| メソッド | パス | 説明 |
| --- | --- | --- |
| `GET`    | `/api/collections` | コレクション一覧（保存リクエストを含む） |
| `POST`   | `/api/collections` | 新規作成 (`{ "name": "..." }`) |
| `PUT`    | `/api/collections/{id}` | 名称変更 |
| `DELETE` | `/api/collections/{id}` | 削除 |

### 6.3 保存リクエスト

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

## 7. トラブルシューティング

| 症状 | 対処 |
| --- | --- |
| `19876` がすでに使用中で起動できない | `docker-compose.dist.yml` の `ports` を `"29876:80"` 等、任意の空きポートに変更する。 |
| 履歴 / 保存リクエストが消えた | `./database/database.sqlite` が存在するか確認。バックアップから復元するか、削除して再作成。 |
| 画面が真っ白 / アセットが読み込めない | `docker compose -f docker-compose.dist.yml pull` で最新イメージを取得し、再起動する。 |
| 外部 API へのリクエストが `0` (タイムアウト) になる | コンテナから対象ホストへ到達できるか確認： `docker compose -f docker-compose.dist.yml exec app curl -I <URL>` |
| 最新版に更新したい | `docker compose -f docker-compose.dist.yml pull && docker compose -f docker-compose.dist.yml up -d` |

---

## ライセンス

本プロジェクトは MIT ライセンスです。Laravel 本体のライセンスについては [Laravel](https://laravel.com) を参照してください。
