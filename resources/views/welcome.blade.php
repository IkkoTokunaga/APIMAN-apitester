<!DOCTYPE html>
<html lang="ja" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>API Tester</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full text-stone-700 font-sans antialiased" x-data="apiTester()" x-init="init()">

<div class="flex h-full">

    {{-- ======== Sidebar ======== --}}
    <aside class="w-72 bg-orange-50 border-r border-orange-300 flex flex-col shrink-0">

        {{-- Sidebar tabs --}}
        <div class="flex border-b border-orange-300">
            <button @click="sideTab = 'history'"
                    :class="sideTab === 'history' ? 'bg-white text-orange-600 border-b-2 border-orange-500' : 'text-stone-400 hover:text-stone-600 border-b-2 border-transparent'"
                    class="flex-1 text-xs font-bold uppercase tracking-widest py-3 transition-all flex items-center justify-center gap-1.5">
                HISTORY
            </button>
            <button @click="sideTab = 'saved'"
                    :class="sideTab === 'saved' ? 'bg-white text-orange-600 border-b-2 border-orange-500' : 'text-stone-400 hover:text-stone-600 border-b-2 border-transparent'"
                    class="flex-1 text-xs font-bold uppercase tracking-widest py-3 transition-all flex items-center justify-center gap-1.5">
                SAVED
            </button>
        </div>

        {{-- ======== HISTORY ======== --}}
        <div x-show="sideTab === 'history'" class="flex-1 flex flex-col min-h-0">
            <div class="overflow-y-auto flex-1 scrollbar-warm px-2 py-2">
                <template x-if="histories.length === 0">
                    <p class="px-4 py-8 text-xs text-stone-400 text-center">NO HISTORY YET</p>
                </template>

                <template x-for="h in histories" :key="h.id">
                    <div @click="loadFromHistory(h.id)"
                         class="flex items-start gap-2 px-3 py-2.5 my-1 rounded-xl hover:bg-white cursor-pointer transition-all group border border-transparent hover:border-orange-300">
                        <span :class="methodBadgeClass(h.method)"
                              class="shrink-0 text-[10px] font-bold px-2 py-0.5 rounded-full mt-0.5 w-14 text-center"
                              x-text="h.method"></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs text-stone-700 truncate" x-text="h.url"></p>
                            <div class="flex gap-2 mt-0.5 items-center">
                                <span :class="statusBadgeClass(h.status_code)"
                                      class="text-[10px] font-bold"
                                      x-text="h.status_code || 'ERR'"></span>
                                <span class="text-[10px] text-stone-400"
                                      x-text="h.duration_ms ? h.duration_ms + 'ms' : ''"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- ======== SAVED (Collections) ======== --}}
        <div x-show="sideTab === 'saved'" class="flex-1 flex flex-col min-h-0">

            <div class="px-3 py-2 border-b border-orange-200 flex items-center gap-2">
                <button @click="openNewCollection()"
                        class="flex-1 text-[10px] px-2 py-1.5 rounded-md border border-orange-300 text-orange-700 hover:bg-orange-100 transition-colors font-bold">
                    + NEW COLLECTION
                </button>
            </div>

            <div class="overflow-y-auto flex-1 scrollbar-warm px-2 py-2">

                <template x-if="collections.length === 0 && uncategorized.length === 0">
                    <p class="px-4 py-8 text-xs text-stone-400 text-center">NO SAVED REQUESTS</p>
                </template>

                {{-- Each collection --}}
                <template x-for="c in collections" :key="c.id">
                    <div class="mb-1">
                        <div class="flex items-center gap-1 px-2 py-1.5 rounded-lg hover:bg-white transition-all">
                            <button @click="toggleCollection(c.id)"
                                    class="shrink-0 text-stone-400 hover:text-orange-500 w-4 text-center text-xs">
                                <span x-text="collapsed[c.id] ? '▸' : '▾'"></span>
                            </button>
                            <span class="text-xs font-bold text-stone-600 truncate flex-1" x-text="c.name"></span>
                            <span class="text-[10px] text-stone-400 shrink-0" x-text="(c.saved_requests?.length ?? 0)"></span>
                            <button @click.stop="renameCollection(c)"
                                    class="opacity-0 group-hover:opacity-100 hover:opacity-100 text-[10px] text-stone-400 hover:text-orange-600 px-1 font-bold">RENAME</button>
                            <button @click.stop="deleteCollection(c)"
                                    class="opacity-0 group-hover:opacity-100 text-[10px] text-stone-400 hover:text-rose-500 px-1 font-bold">DEL</button>
                        </div>

                        <div x-show="!collapsed[c.id]" class="pl-3">
                            <template x-if="(c.saved_requests?.length ?? 0) === 0">
                                <p class="px-3 py-2 text-[10px] text-stone-300">EMPTY</p>
                            </template>
                            <template x-for="r in c.saved_requests" :key="r.id">
                                <div @click="loadFromSaved(r.id)"
                                     class="flex items-start gap-2 px-2 py-1.5 my-0.5 rounded-lg hover:bg-white cursor-pointer transition-all group border border-transparent hover:border-orange-300">
                                    <span :class="methodBadgeClass(r.method)"
                                          class="shrink-0 text-[10px] font-bold px-1.5 py-0.5 rounded-full mt-0.5 w-14 text-center"
                                          x-text="r.method"></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs text-stone-700 truncate font-semibold" x-text="r.title"></p>
                                        <p class="text-[10px] text-stone-400 truncate" x-text="r.url"></p>
                                    </div>
                                    <button @click.stop="deleteSaved(r)"
                                            class="opacity-0 group-hover:opacity-100 text-[10px] text-stone-400 hover:text-rose-500 px-1 font-bold">DEL</button>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Uncategorized --}}
                <template x-if="uncategorized.length > 0">
                    <div class="mb-1">
                        <div class="flex items-center gap-1 px-2 py-1.5 rounded-lg hover:bg-white transition-all">
                            <button @click="toggleCollection('__uncat__')"
                                    class="shrink-0 text-stone-400 hover:text-orange-500 w-4 text-center text-xs">
                                <span x-text="collapsed['__uncat__'] ? '▸' : '▾'"></span>
                            </button>
                            <span class="text-xs font-bold text-stone-600 truncate flex-1">UNCATEGORIZED</span>
                            <span class="text-[10px] text-stone-400 shrink-0" x-text="uncategorized.length"></span>
                        </div>

                        <div x-show="!collapsed['__uncat__']" class="pl-3">
                            <template x-for="r in uncategorized" :key="r.id">
                                <div @click="loadFromSaved(r.id)"
                                     class="flex items-start gap-2 px-2 py-1.5 my-0.5 rounded-lg hover:bg-white cursor-pointer transition-all group border border-transparent hover:border-orange-300">
                                    <span :class="methodBadgeClass(r.method)"
                                          class="shrink-0 text-[10px] font-bold px-1.5 py-0.5 rounded-full mt-0.5 w-14 text-center"
                                          x-text="r.method"></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs text-stone-700 truncate font-semibold" x-text="r.title"></p>
                                        <p class="text-[10px] text-stone-400 truncate" x-text="r.url"></p>
                                    </div>
                                    <button @click.stop="deleteSaved(r)"
                                            class="opacity-0 group-hover:opacity-100 text-[10px] text-stone-400 hover:text-rose-500 px-1 font-bold">DEL</button>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

            </div>
        </div>
    </aside>

    {{-- ======== Main ======== --}}
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">

        {{-- Header --}}
        <header class="px-7 py-4 border-b border-orange-300 flex items-center bg-white">
            <h1 class="text-3xl uppercase tracking-wide leading-none text-orange-700 [font-family:'Impact','Arial_Black','Helvetica_Neue',sans-serif] [font-weight:900]">
                API - TESTER
            </h1>
            <div class="ml-auto flex items-center gap-2">
                <template x-if="currentSaved">
                    <span class="text-xs px-2.5 py-1 rounded-full bg-orange-100 text-orange-700 font-bold"
                          x-text="currentSaved.title"></span>
                </template>
                <button @click="openSaveModal()"
                        class="text-xs px-3 py-1.5 rounded-md border border-orange-300 text-orange-700 hover:bg-orange-50 transition-colors font-bold"
                        x-text="currentSaved ? 'UPDATE' : 'SAVE'"></button>
                <button x-show="currentSaved"
                        @click="clearCurrentSaved()"
                        class="text-xs px-3 py-1.5 rounded-md border border-stone-300 text-stone-500 hover:bg-stone-50 transition-colors font-bold">
                    NEW
                </button>
            </div>
        </header>

        {{-- Request form --}}
        <section class="px-7 py-5 border-b border-orange-300 space-y-4 bg-orange-50/40">

            {{-- Method + URL --}}
            <div class="flex gap-2">
                <select x-model="form.method"
                        @change="handleMethodChange()"
                        class="bg-white border border-orange-300 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 text-orange-700 font-bold w-28 transition-all">
                    <option>GET</option>
                    <option>POST</option>
                    <option>PUT</option>
                    <option>PATCH</option>
                    <option>DELETE</option>
                    <option>HEAD</option>
                    <option>OPTIONS</option>
                </select>
                <input x-model="form.url"
                       @keydown.enter="sendRequest()"
                       type="url"
                       placeholder="https://api.example.com/endpoint"
                       class="flex-1 bg-white border border-orange-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 placeholder-stone-300 text-stone-700 transition-all">
                <button @click="sendRequest()"
                        :disabled="loading"
                        class="bg-orange-500 hover:bg-orange-600 disabled:opacity-50 disabled:cursor-not-allowed text-white px-6 py-2.5 rounded-xl text-sm font-bold transition-all"
                        x-text="loading ? 'SENDING...' : 'SEND'">
                </button>
            </div>

            {{-- Tabs --}}
            <div class="flex gap-1 border-b border-orange-300">
                <button @click="tab = 'headers'"
                        :class="tab === 'headers' ? 'border-orange-400 text-orange-600' : 'border-transparent text-stone-400 hover:text-stone-600'"
                        class="text-xs px-3 py-2 border-b-2 transition-colors font-bold">HEADERS</button>
                <button @click="tab = 'body'"
                        x-show="canHaveBody()"
                        :class="tab === 'body' ? 'border-orange-400 text-orange-600' : 'border-transparent text-stone-400 hover:text-stone-600'"
                        class="text-xs px-3 py-2 border-b-2 transition-colors font-bold">
                    <span>BODY</span>
                </button>
            </div>

            {{-- Headers tab --}}
            <div x-show="tab === 'headers'" class="space-y-2">
                <div x-show="shouldShowContentType()" class="flex gap-2 items-center">
                    <label class="text-xs text-stone-500 font-semibold w-32">CONTENT-TYPE</label>
                    <select x-model="form.contentType"
                            class="flex-1 bg-white border border-orange-300 rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition-all">
                        <option value="application/json">application/json</option>
                        <option value="application/x-www-form-urlencoded">application/x-www-form-urlencoded</option>
                        <option value="multipart/form-data">multipart/form-data</option>
                        <option value="text/plain">text/plain</option>
                        <option value="application/xml">application/xml</option>
                    </select>
                </div>
                <template x-for="(header, index) in form.headers" :key="index">
                    <div class="flex gap-2">
                        <input x-model="header.key"
                               placeholder="key"
                               class="flex-1 bg-white border border-orange-300 rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 placeholder-stone-300 transition-all">
                        <input x-model="header.value"
                               placeholder="value"
                               class="flex-1 bg-white border border-orange-300 rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 placeholder-stone-300 transition-all">
                        <button @click="removeHeader(index)"
                                class="text-[10px] px-2 py-1 rounded-md border border-rose-300 text-rose-600 hover:bg-rose-50 transition-colors">DELETE</button>
                    </div>
                </template>
                <button @click="addHeader()"
                        class="text-xs px-3 py-1.5 rounded-md border border-orange-300 text-orange-700 hover:bg-orange-50 transition-colors font-bold">ADD</button>
            </div>

            {{-- Body tab --}}
            <div x-show="tab === 'body' && canHaveBody()">
                {{-- JSON / plain text, etc. --}}
                <div x-show="!isFormUrlEncoded()">
                    <textarea x-model="form.body"
                              placeholder='{"key": "value"}'
                              rows="5"
                              class="w-full bg-white border border-orange-300 rounded-xl px-4 py-3 text-xs focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 placeholder-stone-300 resize-y font-mono text-stone-700 transition-all"></textarea>
                    <div class="flex gap-2 mt-2">
                        <button @click="formatBody()"
                                class="text-xs text-stone-500 hover:text-orange-600 hover:bg-orange-50 px-3 py-1 rounded-full transition-colors font-semibold">FORMAT JSON</button>
                    </div>
                </div>

                {{-- application/x-www-form-urlencoded --}}
                <div x-show="isFormUrlEncoded()" class="space-y-2">
                    <template x-if="form.formFields.length === 0">
                        <p class="text-xs text-stone-400 py-2">ADD KEY / VALUE PAIRS</p>
                    </template>
                    <template x-for="(field, index) in form.formFields" :key="index">
                        <div class="flex gap-2">
                            <input x-model="field.key"
                                   placeholder="key"
                                   class="flex-1 bg-white border border-orange-300 rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 placeholder-stone-300 transition-all">
                            <input x-model="field.value"
                                   placeholder="value"
                                   class="flex-1 bg-white border border-orange-300 rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 placeholder-stone-300 transition-all">
                            <button @click="removeFormField(index)"
                                    class="text-[10px] px-2 py-1 rounded-md border border-rose-300 text-rose-600 hover:bg-rose-50 transition-colors">DELETE</button>
                        </div>
                    </template>
                    <div class="flex gap-2">
                        <button @click="addFormField()"
                                class="text-xs px-3 py-1.5 rounded-md border border-orange-300 text-orange-700 hover:bg-orange-50 transition-colors font-bold">ADD</button>
                    </div>
                </div>
            </div>

        </section>

        {{-- Response --}}
        <section class="flex-1 overflow-hidden flex flex-col min-h-0 px-7 py-5">
            <div class="mb-3 flex items-center gap-3">
                <h2 class="text-xs font-bold uppercase tracking-widest text-stone-500">RESPONSE</h2>
                <template x-if="response || error">
                    <div class="flex items-center gap-2 flex-1 min-w-0">
                        <span x-show="error" class="text-rose-500 text-xs font-semibold truncate" x-text="error"></span>
                        <div class="ml-auto flex items-center gap-2 shrink-0">
                            <span :class="statusBadgeClass(response?.status_code)"
                                  class="px-2.5 py-1 rounded-full border border-orange-300 bg-white text-xs font-bold"
                                  x-text="response?.status_code ? 'HTTP ' + response.status_code : 'CONNECTION ERROR'"></span>
                            <span x-show="response?.duration_ms"
                                  class="px-2.5 py-1 rounded-full border border-orange-300 bg-white text-stone-600 text-xs font-bold"
                                  x-text="response.duration_ms + ' MS'"></span>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="!response && !error && !loading"
                 class="flex-1 flex items-center justify-center text-stone-300 text-sm">
                <span>SEND A REQUEST TO SEE THE RESPONSE HERE</span>
            </div>

            <div x-show="loading"
                 class="flex-1 flex items-center justify-center text-orange-500 text-sm animate-pulse font-semibold">
                SENDING REQUEST...
            </div>

            <template x-if="response || error">
                <div class="flex-1 flex flex-col min-h-0 space-y-3">

                    {{-- Response tabs --}}
                    <div class="flex gap-1 border-b border-orange-300">
                        <button @click="resTab = 'body'"
                                :class="resTab === 'body' ? 'border-orange-400 text-orange-600' : 'border-transparent text-stone-400'"
                                class="text-xs px-3 py-2 border-b-2 transition-colors hover:text-stone-600 font-bold">BODY</button>
                        <button @click="resTab = 'headers'"
                                :class="resTab === 'headers' ? 'border-orange-400 text-orange-600' : 'border-transparent text-stone-400'"
                                class="text-xs px-3 py-2 border-b-2 transition-colors hover:text-stone-600 font-bold">HEADERS</button>
                    </div>

                    {{-- Response body --}}
                    <div x-show="resTab === 'body'" class="flex-1 overflow-auto min-h-0 scrollbar-warm">
                        <pre class="text-xs text-stone-700 bg-white border border-orange-300 rounded-2xl p-4 overflow-auto h-full whitespace-pre-wrap break-all font-mono scrollbar-warm"
                             x-text="prettyBody(response?.response_body)"></pre>
                    </div>

                    {{-- Response headers --}}
                    <div x-show="resTab === 'headers'" class="flex-1 overflow-auto min-h-0 scrollbar-warm">
                        <pre class="text-xs text-stone-600 bg-white border border-orange-300 rounded-2xl p-4 overflow-auto h-full whitespace-pre-wrap font-mono scrollbar-warm"
                             x-text="prettyHeaders(response?.response_headers)"></pre>
                    </div>
                </div>
            </template>

        </section>

    </main>
</div>

{{-- ======== Save Modal ======== --}}
<div x-show="saveModal.open"
     x-cloak
     @keydown.escape.window="saveModal.open = false"
     class="fixed inset-0 z-40 flex items-center justify-center bg-stone-900/40 backdrop-blur-sm px-4"
     x-transition.opacity>
    <div class="bg-white border border-orange-300 rounded-2xl shadow-xl w-full max-w-md p-6 space-y-4">
        <h3 class="text-lg font-bold text-orange-700 uppercase tracking-wider"
            x-text="saveModal.id ? 'UPDATE REQUEST' : 'SAVE REQUEST'"></h3>

        <div class="space-y-1">
            <label class="text-xs text-stone-500 font-semibold uppercase">TITLE</label>
            <input x-model="saveModal.title"
                   type="text"
                   placeholder="My awesome request"
                   class="w-full bg-white border border-orange-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 placeholder-stone-300 transition-all">
        </div>

        <div class="space-y-1">
            <label class="text-xs text-stone-500 font-semibold uppercase">COLLECTION</label>
            <div class="flex gap-2">
                <select x-model="saveModal.collectionId"
                        x-show="!saveModal.newCollection"
                        class="flex-1 bg-white border border-orange-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition-all">
                    <option value="">(UNCATEGORIZED)</option>
                    <template x-for="c in collections" :key="c.id">
                        <option :value="c.id" x-text="c.name"></option>
                    </template>
                </select>
                <input x-show="saveModal.newCollection"
                       x-model="saveModal.newCollectionName"
                       type="text"
                       placeholder="New collection name"
                       class="flex-1 bg-white border border-orange-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 placeholder-stone-300 transition-all">
                <button @click="saveModal.newCollection = !saveModal.newCollection; saveModal.newCollectionName = ''"
                        class="text-xs px-3 py-2 rounded-md border border-orange-300 text-orange-700 hover:bg-orange-50 transition-colors font-bold shrink-0"
                        x-text="saveModal.newCollection ? 'CANCEL' : '+ NEW'"></button>
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <button @click="saveModal.open = false"
                    class="text-xs px-4 py-2 rounded-md border border-stone-300 text-stone-600 hover:bg-stone-50 transition-colors font-bold">
                CANCEL
            </button>
            <button @click="submitSave()"
                    :disabled="!saveModal.title.trim()"
                    class="text-xs px-4 py-2 rounded-md bg-orange-500 hover:bg-orange-600 disabled:opacity-50 disabled:cursor-not-allowed text-white transition-colors font-bold">
                <span x-text="saveModal.id ? 'UPDATE' : 'SAVE'"></span>
            </button>
        </div>
    </div>
</div>

<script>
function apiTester() {
    return {
        tab: 'body',
        resTab: 'body',
        sideTab: 'history',
        loading: false,
        form: {
            method: 'GET',
            url: '',
            contentType: 'application/x-www-form-urlencoded',
            headers: [],
            body: '',
            formFields: [],
        },
        response: null,
        error: null,
        histories: [],

        // Collections / saved requests
        collections: [],
        uncategorized: [],
        collapsed: {},
        currentSaved: null, // currently loaded saved request (for UPDATE)

        saveModal: {
            open: false,
            id: null,
            title: '',
            collectionId: '',
            newCollection: false,
            newCollectionName: '',
        },

        init() {
            this.loadHistory();
            this.loadCollections();
        },

        addHeader() {
            this.form.headers.push({ key: '', value: '' });
        },
        removeHeader(index) {
            this.form.headers.splice(index, 1);
        },
        addFormField() {
            this.form.formFields.push({ key: '', value: '' });
        },
        removeFormField(index) {
            this.form.formFields.splice(index, 1);
        },
        formatBody() {
            try {
                this.form.body = JSON.stringify(JSON.parse(this.form.body), null, 2);
            } catch {}
        },

        isFormUrlEncoded() {
            return this.shouldShowContentType()
                && this.form.contentType === 'application/x-www-form-urlencoded';
        },

        buildBody() {
            if (this.isFormUrlEncoded()) {
                const params = new URLSearchParams();
                for (const f of this.form.formFields) {
                    const key = (f.key ?? '').trim();
                    if (!key) continue;
                    params.append(key, f.value ?? '');
                }
                return params.toString();
            }
            return this.form.body;
        },

        parseUrlEncodedBody(body) {
            if (typeof body !== 'string' || body === '') return [];
            try {
                const params = new URLSearchParams(body);
                const fields = [];
                for (const [k, v] of params.entries()) {
                    fields.push({ key: k, value: v });
                }
                return fields;
            } catch {
                return [];
            }
        },

        headersToObject() {
            const obj = {};
            for (const h of this.form.headers) {
                const key = h.key.trim();
                if (!key) continue;
                if (key.toLowerCase() === 'content-type') continue;
                obj[key] = h.value.trim();
            }
            if (this.shouldShowContentType()) {
                obj['Content-Type'] = this.form.contentType;
            }
            return obj;
        },

        shouldShowContentType() {
            return this.form.method !== 'GET';
        },

        canHaveBody() {
            return this.form.method !== 'GET';
        },

        handleMethodChange() {
            if (!this.canHaveBody() && this.tab === 'body') {
                this.tab = 'headers';
            }
        },

        csrf() {
            return document.querySelector('meta[name="csrf-token"]').content;
        },

        async sendRequest() {
            if (!this.form.url) return;
            this.loading = true;
            this.response = null;
            this.error = null;
            try {
                const res = await fetch('/api/proxy', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                    },
                    body: JSON.stringify({
                        method:  this.form.method,
                        url:     this.form.url,
                        headers: this.headersToObject(),
                        body:    this.buildBody(),
                    }),
                });
                const data = await res.json();
                if (!res.ok && data.message) {
                    this.error = data.message;
                } else {
                    this.response = data;
                    this.resTab = 'body';
                }
                this.loadHistory();
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },

        async loadHistory() {
            const res = await fetch('/api/history');
            this.histories = await res.json();
        },

        applyFormFromData(h) {
            this.form.method = h.method;
            this.form.url    = h.url;
            this.form.body   = h.request_body ?? '';
            const hdrs = h.request_headers ?? {};
            const savedContentType = h.content_type
                ?? hdrs['Content-Type']
                ?? hdrs['content-type']
                ?? null;
            this.form.contentType = typeof savedContentType === 'string' && savedContentType.trim()
                ? savedContentType
                : 'application/x-www-form-urlencoded';
            this.form.headers = Object.entries(hdrs)
                .filter(([k]) => k.toLowerCase() !== 'content-type')
                .map(([k, v]) => ({ key: k, value: v }));
            this.form.formFields = this.isFormUrlEncoded()
                ? this.parseUrlEncodedBody(this.form.body)
                : [];
        },

        async loadFromHistory(id) {
            const res = await fetch('/api/history/' + id);
            const h = await res.json();
            this.applyFormFromData(h);
            this.response = {
                status_code:      h.status_code,
                response_body:    h.response_body,
                response_headers: h.response_headers,
                duration_ms:      h.duration_ms,
            };
            this.currentSaved = null;
        },

        // ===== Collections / saved requests =====

        async loadCollections() {
            const [colsRes, savedRes] = await Promise.all([
                fetch('/api/collections'),
                fetch('/api/saved-requests'),
            ]);
            this.collections = await colsRes.json();
            const saved = await savedRes.json();
            this.uncategorized = saved.filter(r => r.collection_id === null);
        },

        toggleCollection(id) {
            this.collapsed[id] = !this.collapsed[id];
        },

        async openNewCollection() {
            const name = prompt('Collection name:');
            if (!name || !name.trim()) return;
            await fetch('/api/collections', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrf(),
                },
                body: JSON.stringify({ name: name.trim() }),
            });
            await this.loadCollections();
        },

        async renameCollection(c) {
            const name = prompt('Rename collection:', c.name);
            if (!name || !name.trim() || name.trim() === c.name) return;
            await fetch('/api/collections/' + c.id, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrf(),
                },
                body: JSON.stringify({ name: name.trim() }),
            });
            await this.loadCollections();
        },

        async deleteCollection(c) {
            if (!confirm(`Delete collection "${c.name}"? Its saved requests will be moved to UNCATEGORIZED.`)) return;
            await fetch('/api/collections/' + c.id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': this.csrf() },
            });
            await this.loadCollections();
        },

        async deleteSaved(r) {
            if (!confirm(`Delete saved request "${r.title}"?`)) return;
            await fetch('/api/saved-requests/' + r.id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': this.csrf() },
            });
            if (this.currentSaved && this.currentSaved.id === r.id) {
                this.currentSaved = null;
            }
            await this.loadCollections();
        },

        async loadFromSaved(id) {
            const res = await fetch('/api/saved-requests/' + id);
            const r = await res.json();
            this.applyFormFromData(r);
            this.response = null;
            this.error = null;
            this.currentSaved = {
                id: r.id,
                title: r.title,
                collection_id: r.collection_id,
            };
        },

        clearCurrentSaved() {
            this.currentSaved = null;
            this.form = {
                method: 'GET',
                url: '',
                contentType: 'application/x-www-form-urlencoded',
                headers: [],
                body: '',
                formFields: [],
            };
            this.response = null;
            this.error = null;
        },

        openSaveModal() {
            if (this.currentSaved) {
                this.saveModal.id    = this.currentSaved.id;
                this.saveModal.title = this.currentSaved.title;
                this.saveModal.collectionId = this.currentSaved.collection_id ?? '';
            } else {
                this.saveModal.id    = null;
                this.saveModal.title = this.form.url
                    ? this.form.method + ' ' + this.form.url.replace(/^https?:\/\//, '').slice(0, 40)
                    : '';
                this.saveModal.collectionId = '';
            }
            this.saveModal.newCollection = false;
            this.saveModal.newCollectionName = '';
            this.saveModal.open = true;
        },

        async submitSave() {
            const title = this.saveModal.title.trim();
            if (!title) return;
            if (!this.form.url) {
                alert('URL is required.');
                return;
            }

            let collectionId = this.saveModal.collectionId || null;

            if (this.saveModal.newCollection) {
                const name = this.saveModal.newCollectionName.trim();
                if (!name) {
                    alert('Collection name is required.');
                    return;
                }
                const createRes = await fetch('/api/collections', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                    },
                    body: JSON.stringify({ name }),
                });
                const created = await createRes.json();
                collectionId = created.id;
            }

            const payload = {
                collection_id:   collectionId,
                title,
                method:          this.form.method,
                url:             this.form.url,
                request_headers: this.headersToObject(),
                request_body:    this.buildBody(),
                content_type:    this.shouldShowContentType() ? this.form.contentType : null,
            };

            let saved;
            if (this.saveModal.id) {
                const res = await fetch('/api/saved-requests/' + this.saveModal.id, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                    },
                    body: JSON.stringify(payload),
                });
                saved = await res.json();
            } else {
                const res = await fetch('/api/saved-requests', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                    },
                    body: JSON.stringify(payload),
                });
                saved = await res.json();
            }

            this.currentSaved = {
                id: saved.id,
                title: saved.title,
                collection_id: saved.collection_id,
            };
            this.saveModal.open = false;
            this.sideTab = 'saved';
            await this.loadCollections();
        },

        prettyBody(body) {
            if (!body) return '';
            try {
                return JSON.stringify(JSON.parse(body), null, 2);
            } catch {
                return body;
            }
        },

        prettyHeaders(headers) {
            if (!headers) return '';
            if (typeof headers === 'string') {
                try {
                    headers = JSON.parse(headers);
                } catch {
                    return headers;
                }
            }
            return Object.entries(headers)
                .map(([k, v]) => `${k}: ${Array.isArray(v) ? v.join(', ') : v}`)
                .join('\n');
        },

        methodBadgeClass(method) {
            const map = {
                GET:     'bg-emerald-100 text-emerald-700',
                POST:    'bg-sky-100 text-sky-700',
                PUT:     'bg-amber-100 text-amber-700',
                PATCH:   'bg-orange-100 text-orange-700',
                DELETE:  'bg-rose-100 text-rose-700',
                HEAD:    'bg-violet-100 text-violet-700',
                OPTIONS: 'bg-stone-100 text-stone-600',
            };
            return map[method] ?? 'bg-stone-100 text-stone-600';
        },

        statusBadgeClass(code) {
            if (!code || code === 0) return 'text-rose-500';
            if (code < 300) return 'text-emerald-600';
            if (code < 400) return 'text-amber-600';
            if (code < 500) return 'text-orange-600';
            return 'text-rose-500';
        },
    };
}
</script>
</body>
</html>
