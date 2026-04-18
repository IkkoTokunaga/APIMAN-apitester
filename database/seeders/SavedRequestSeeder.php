<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\SavedRequest;
use Illuminate\Database\Seeder;

class SavedRequestSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'JSONPlaceholder' => [
                [
                    'title'        => 'List posts',
                    'method'       => 'GET',
                    'url'          => 'https://jsonplaceholder.typicode.com/posts',
                    'headers'      => ['Accept' => 'application/json'],
                    'body'         => null,
                    'content_type' => null,
                ],
                [
                    'title'        => 'Get post #1',
                    'method'       => 'GET',
                    'url'          => 'https://jsonplaceholder.typicode.com/posts/1',
                    'headers'      => ['Accept' => 'application/json'],
                    'body'         => null,
                    'content_type' => null,
                ],
                [
                    'title'        => 'Create post (JSON)',
                    'method'       => 'POST',
                    'url'          => 'https://jsonplaceholder.typicode.com/posts',
                    'headers'      => ['Accept' => 'application/json'],
                    'body'         => json_encode([
                        'title'  => 'Hello',
                        'body'   => 'World',
                        'userId' => 1,
                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                    'content_type' => 'application/json',
                ],
                [
                    'title'        => 'Update post #1 (PUT JSON)',
                    'method'       => 'PUT',
                    'url'          => 'https://jsonplaceholder.typicode.com/posts/1',
                    'headers'      => ['Accept' => 'application/json'],
                    'body'         => json_encode([
                        'id'     => 1,
                        'title'  => 'updated',
                        'body'   => 'updated body',
                        'userId' => 1,
                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                    'content_type' => 'application/json',
                ],
                [
                    'title'        => 'Patch post #1',
                    'method'       => 'PATCH',
                    'url'          => 'https://jsonplaceholder.typicode.com/posts/1',
                    'headers'      => ['Accept' => 'application/json'],
                    'body'         => json_encode(['title' => 'patched title'], JSON_PRETTY_PRINT),
                    'content_type' => 'application/json',
                ],
                [
                    'title'        => 'Delete post #1',
                    'method'       => 'DELETE',
                    'url'          => 'https://jsonplaceholder.typicode.com/posts/1',
                    'headers'      => [],
                    'body'         => null,
                    'content_type' => null,
                ],
            ],

            'HTTPBin' => [
                [
                    'title'        => 'GET with query params',
                    'method'       => 'GET',
                    'url'          => 'https://httpbin.org/get?foo=bar&baz=qux',
                    'headers'      => [
                        'Accept'        => 'application/json',
                        'X-Custom-Flag' => 'api-tester',
                    ],
                    'body'         => null,
                    'content_type' => null,
                ],
                [
                    'title'        => 'POST form-urlencoded',
                    'method'       => 'POST',
                    'url'          => 'https://httpbin.org/post',
                    'headers'      => [],
                    'body'         => 'username=alice&password=secret&remember=1',
                    'content_type' => 'application/x-www-form-urlencoded',
                ],
                [
                    'title'        => 'POST plain text',
                    'method'       => 'POST',
                    'url'          => 'https://httpbin.org/post',
                    'headers'      => [],
                    'body'         => "Hello from API Tester.\nThis is a plain text body.",
                    'content_type' => 'text/plain',
                ],
                [
                    'title'        => 'POST XML',
                    'method'       => 'POST',
                    'url'          => 'https://httpbin.org/post',
                    'headers'      => [],
                    'body'         => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<note>\n    <to>Tove</to>\n    <from>Jani</from>\n    <body>Don't forget me!</body>\n</note>",
                    'content_type' => 'application/xml',
                ],
                [
                    'title'        => 'HEAD request',
                    'method'       => 'HEAD',
                    'url'          => 'https://httpbin.org/get',
                    'headers'      => [],
                    'body'         => null,
                    'content_type' => null,
                ],
                [
                    'title'        => 'OPTIONS request',
                    'method'       => 'OPTIONS',
                    'url'          => 'https://httpbin.org/get',
                    'headers'      => [],
                    'body'         => null,
                    'content_type' => null,
                ],
                [
                    'title'        => 'Basic Auth',
                    'method'       => 'GET',
                    'url'          => 'https://httpbin.org/basic-auth/user/passwd',
                    'headers'      => ['Authorization' => 'Basic dXNlcjpwYXNzd2Q='],
                    'body'         => null,
                    'content_type' => null,
                ],
                [
                    'title'        => '500 Error',
                    'method'       => 'GET',
                    'url'          => 'https://httpbin.org/status/500',
                    'headers'      => [],
                    'body'         => null,
                    'content_type' => null,
                ],
            ],

            'GitHub API' => [
                [
                    'title'        => 'Get user (octocat)',
                    'method'       => 'GET',
                    'url'          => 'https://api.github.com/users/octocat',
                    'headers'      => [
                        'Accept'     => 'application/vnd.github+json',
                        'User-Agent' => 'api-tester',
                    ],
                    'body'         => null,
                    'content_type' => null,
                ],
                [
                    'title'        => 'List octocat repos',
                    'method'       => 'GET',
                    'url'          => 'https://api.github.com/users/octocat/repos',
                    'headers'      => [
                        'Accept'     => 'application/vnd.github+json',
                        'User-Agent' => 'api-tester',
                    ],
                    'body'         => null,
                    'content_type' => null,
                ],
                [
                    'title'        => 'Search Laravel repos',
                    'method'       => 'GET',
                    'url'          => 'https://api.github.com/search/repositories?q=laravel&sort=stars',
                    'headers'      => [
                        'Accept'     => 'application/vnd.github+json',
                        'User-Agent' => 'api-tester',
                    ],
                    'body'         => null,
                    'content_type' => null,
                ],
            ],

            'ReqRes (Auth flow)' => [
                [
                    'title'        => 'Register',
                    'method'       => 'POST',
                    'url'          => 'https://reqres.in/api/register',
                    'headers'      => ['Accept' => 'application/json'],
                    'body'         => json_encode([
                        'email'    => 'eve.holt@reqres.in',
                        'password' => 'pistol',
                    ], JSON_PRETTY_PRINT),
                    'content_type' => 'application/json',
                ],
                [
                    'title'        => 'Login',
                    'method'       => 'POST',
                    'url'          => 'https://reqres.in/api/login',
                    'headers'      => ['Accept' => 'application/json'],
                    'body'         => json_encode([
                        'email'    => 'eve.holt@reqres.in',
                        'password' => 'cityslicka',
                    ], JSON_PRETTY_PRINT),
                    'content_type' => 'application/json',
                ],
                [
                    'title'        => 'List users',
                    'method'       => 'GET',
                    'url'          => 'https://reqres.in/api/users?page=2',
                    'headers'      => ['Accept' => 'application/json'],
                    'body'         => null,
                    'content_type' => null,
                ],
                [
                    'title'        => 'Create user (form)',
                    'method'       => 'POST',
                    'url'          => 'https://reqres.in/api/users',
                    'headers'      => [],
                    'body'         => 'name=morpheus&job=leader',
                    'content_type' => 'application/x-www-form-urlencoded',
                ],
            ],
        ];

        foreach ($data as $collectionName => $requests) {
            $collection = Collection::firstOrCreate(['name' => $collectionName]);
            foreach ($requests as $index => $r) {
                SavedRequest::create([
                    'collection_id'   => $collection->id,
                    'title'           => $r['title'],
                    'method'          => $r['method'],
                    'url'             => $r['url'],
                    'request_headers' => $r['headers'] ?: null,
                    'request_body'    => $r['body'],
                    'content_type'    => $r['content_type'],
                    'sort_order'      => $index,
                ]);
            }
        }

        // Uncategorized (no collection)
        $uncategorized = [
            [
                'title'        => 'Quick ping - example.com',
                'method'       => 'GET',
                'url'          => 'https://example.com',
                'headers'      => [],
                'body'         => null,
                'content_type' => null,
            ],
            [
                'title'        => 'Ad-hoc JSON POST',
                'method'       => 'POST',
                'url'          => 'https://httpbin.org/anything',
                'headers'      => ['Accept' => 'application/json'],
                'body'         => json_encode(['note' => 'uncategorized request'], JSON_PRETTY_PRINT),
                'content_type' => 'application/json',
            ],
        ];

        foreach ($uncategorized as $index => $r) {
            SavedRequest::create([
                'collection_id'   => null,
                'title'           => $r['title'],
                'method'          => $r['method'],
                'url'             => $r['url'],
                'request_headers' => $r['headers'] ?: null,
                'request_body'    => $r['body'],
                'content_type'    => $r['content_type'],
                'sort_order'      => $index,
            ]);
        }
    }
}
