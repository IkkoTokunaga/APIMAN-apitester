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
<body class="h-full text-stone-700 font-sans antialiased" x-data="apiTester()" x-init="loadHistory()">

<div class="flex h-full">

    {{-- ======== Sidebar: History ======== --}}
    <aside class="w-72 bg-orange-50 border-r border-orange-300 flex flex-col shrink-0">
        <div class="flex items-center justify-between px-5 py-4 border-b border-orange-300">
            <h2 class="text-xs font-bold uppercase tracking-widest text-orange-500 flex items-center gap-1.5">
                <span class="text-sm">🕒</span> HISTORY
            </h2>
        </div>

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
    </aside>

    {{-- ======== Main ======== --}}
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">

        {{-- Header --}}
        <header class="px-7 py-4 border-b border-orange-300 flex items-center bg-white">
            <h1 class="text-3xl uppercase tracking-wide leading-none text-orange-700 [font-family:'Impact','Arial_Black','Helvetica_Neue',sans-serif] [font-weight:900]">
                API - TESTER
            </h1>
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
                        class="bg-orange-500 hover:bg-orange-600 disabled:opacity-50 disabled:cursor-not-allowed text-white px-6 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
                    <span x-show="loading" class="animate-spin text-base">⟳</span>
                    <span x-text="loading ? 'SENDING...' : 'SEND'"></span>
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
            <div class="mb-3">
                <h2 class="text-xs font-bold uppercase tracking-widest text-stone-500">RESPONSE</h2>
            </div>

            <div x-show="!response && !error && !loading"
                 class="flex-1 flex flex-col items-center justify-center text-stone-300 text-sm gap-2">
                <span class="text-5xl opacity-50">☕</span>
                <span>SEND A REQUEST TO SEE THE RESPONSE HERE</span>
            </div>

            <div x-show="loading"
                 class="flex-1 flex items-center justify-center text-orange-500 text-sm animate-pulse font-semibold">
                SENDING REQUEST...
            </div>

            <template x-if="response || error">
                <div class="flex-1 flex flex-col min-h-0 space-y-3">

                    {{-- Status bar --}}
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <span x-show="error" class="text-rose-500 text-xs font-semibold" x-text="error"></span>
                        <div class="ml-auto flex items-center gap-2">
                            <span :class="statusBadgeClass(response?.status_code)"
                                  class="px-2.5 py-1 rounded-full border border-orange-300 bg-white text-xs font-bold"
                                  x-text="response?.status_code ? 'HTTP ' + response.status_code : 'CONNECTION ERROR'"></span>
                            <span x-show="response?.duration_ms"
                                  class="px-2.5 py-1 rounded-full border border-orange-300 bg-white text-stone-600 text-xs font-bold"
                                  x-text="response.duration_ms + ' MS'"></span>
                        </div>
                    </div>

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

<script>
function apiTester() {
    return {
        tab: 'body',
        resTab: 'body',
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
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
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

        async loadFromHistory(id) {
            const res = await fetch('/api/history/' + id);
            const h = await res.json();
            this.form.method = h.method;
            this.form.url    = h.url;
            this.form.body   = h.request_body ?? '';
            const hdrs = h.request_headers ?? {};
            const savedContentType = hdrs['Content-Type'] ?? hdrs['content-type'] ?? null;
            this.form.contentType = typeof savedContentType === 'string' && savedContentType.trim()
                ? savedContentType
                : 'application/x-www-form-urlencoded';
            this.form.headers = Object.entries(hdrs)
                .filter(([k]) => k.toLowerCase() !== 'content-type')
                .map(([k, v]) => ({ key: k, value: v }));
            this.form.formFields = this.isFormUrlEncoded()
                ? this.parseUrlEncodedBody(this.form.body)
                : [];
            this.response = {
                status_code:      h.status_code,
                response_body:    h.response_body,
                response_headers: h.response_headers,
                duration_ms:      h.duration_ms,
            };
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
