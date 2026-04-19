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
    <script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js" defer></script>
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

            {{-- Collection run progress panel --}}
            <template x-if="collectionRun.active || collectionRun.results.length > 0">
                <div class="border-b border-orange-300 bg-white px-3 py-3 space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-bold uppercase tracking-widest text-orange-600 truncate flex-1"
                              x-text="'RUN: ' + collectionRun.collectionName"></span>
                        <button x-show="collectionRun.active"
                                @click="cancelCollectionRun()"
                                class="text-[10px] px-2 py-0.5 rounded-md border border-rose-300 text-rose-600 hover:bg-rose-50 transition-colors font-bold">
                            CANCEL
                        </button>
                        <button x-show="!collectionRun.active"
                                @click="clearCollectionRun()"
                                class="text-[10px] px-2 py-0.5 rounded-md border border-stone-300 text-stone-500 hover:bg-stone-50 transition-colors font-bold">
                            CLEAR
                        </button>
                    </div>

                    {{-- Progress bar --}}
                    <div class="w-full h-1.5 bg-orange-100 rounded-full overflow-hidden">
                        <div class="h-full transition-all duration-300"
                             :class="collectionRun.cancelled ? 'bg-rose-400' : (collectionRun.active ? 'bg-orange-400' : 'bg-emerald-400')"
                             :style="'width:' + (collectionRun.total > 0 ? Math.round((collectionRun.currentIndex / collectionRun.total) * 100) : 0) + '%'"></div>
                    </div>

                    <div class="flex items-center justify-between text-[10px]">
                        <span class="text-stone-500 font-bold"
                              x-text="collectionRun.currentIndex + ' / ' + collectionRun.total"></span>
                        <span class="text-stone-400 truncate ml-2"
                              x-text="collectionRun.currentTitle"></span>
                    </div>
                </div>
            </template>

            <div class="overflow-y-auto flex-1 scrollbar-warm px-2 py-2">
                <template x-if="histories.length === 0">
                    <p class="px-4 py-8 text-xs text-stone-400 text-center">NO HISTORY YET</p>
                </template>

                <template x-for="h in histories" :key="h.id">
                    <div @click="loadFromHistory(h.id)"
                         :class="historyItemClass(h.status_code)"
                         class="flex items-start gap-2 px-3 py-2.5 my-1 rounded-xl cursor-pointer transition-all group border">
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
                    <div class="mb-1" x-data="{ menuOpen: false }">
                        <div @click="toggleCollection(c.id)"
                             class="flex items-center gap-1 px-2 py-1.5 rounded-lg hover:bg-orange-100/70 transition-all cursor-pointer select-none">
                            <span class="shrink-0 text-stone-400 w-4 text-center text-xs"
                                  x-text="collapsed[c.id] ? '▸' : '▾'"></span>
                            <span class="text-xs font-bold text-stone-600 truncate flex-1" x-text="c.name"></span>
                            <div class="relative shrink-0" @click.stop>
                                <button @click="menuOpen = !menuOpen"
                                        class="w-6 h-6 flex items-center justify-center rounded-md text-stone-400 hover:text-orange-600 hover:bg-orange-100 transition-colors leading-none text-base font-bold"
                                        title="MORE">⋯</button>
                                <div x-show="menuOpen"
                                     x-cloak
                                     x-transition.opacity
                                     @click.outside="menuOpen = false"
                                     @keydown.escape.window="menuOpen = false"
                                     class="absolute right-0 top-full mt-1 z-30 w-32 bg-white border border-orange-300 rounded-lg shadow-lg overflow-hidden">
                                    <button @click="menuOpen = false; openRunModal(c)"
                                            :disabled="collectionRun.active || (c.saved_requests?.length ?? 0) === 0"
                                            class="block w-full text-left text-[11px] px-3 py-2 text-emerald-600 hover:bg-emerald-50 disabled:text-stone-300 disabled:cursor-not-allowed font-bold uppercase tracking-wider">
                                        RUN
                                    </button>
                                    <button @click="menuOpen = false; renameCollection(c)"
                                            class="block w-full text-left text-[11px] px-3 py-2 text-stone-600 hover:bg-orange-50 hover:text-orange-700 font-bold uppercase tracking-wider">
                                        RENAME
                                    </button>
                                    <button @click="menuOpen = false; deleteCollection(c)"
                                            class="block w-full text-left text-[11px] px-3 py-2 text-rose-600 hover:bg-rose-50 font-bold uppercase tracking-wider">
                                        DELETE
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div x-show="!collapsed[c.id]" class="pl-3">
                            <template x-if="(c.saved_requests?.length ?? 0) === 0">
                                <p class="px-3 py-2 text-[10px] text-stone-300">EMPTY</p>
                            </template>
                            <template x-for="r in c.saved_requests" :key="r.id">
                                <div @click="loadFromSaved(r.id)"
                                     :class="isCurrentSaved(r.id)
                                         ? 'bg-orange-200/70 border-orange-400 ring-1 ring-orange-400 shadow-sm'
                                         : 'border-transparent hover:bg-orange-100/70 hover:border-orange-300'"
                                     class="flex items-start gap-2 px-2 py-1.5 my-0.5 rounded-lg cursor-pointer transition-all group border">
                                    <span :class="methodBadgeClass(r.method)"
                                          class="shrink-0 text-[10px] font-bold px-1.5 py-0.5 rounded-full mt-0.5 w-14 text-center"
                                          x-text="r.method"></span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1">
                                            <span x-show="isCurrentSaved(r.id)"
                                                  class="shrink-0 text-orange-600 text-[10px] leading-none">●</span>
                                            <p class="text-xs truncate font-semibold"
                                               :class="isCurrentSaved(r.id) ? 'text-orange-700' : 'text-stone-700'"
                                               x-text="r.title"></p>
                                        </div>
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
                        <div @click="toggleCollection('__uncat__')"
                             class="flex items-center gap-1 px-2 py-1.5 rounded-lg hover:bg-orange-100/70 transition-all cursor-pointer select-none">
                            <span class="shrink-0 text-stone-400 w-4 text-center text-xs"
                                  x-text="collapsed['__uncat__'] ? '▸' : '▾'"></span>
                            <span class="text-xs font-bold text-stone-600 truncate flex-1">UNCATEGORIZED</span>
                        </div>

                        <div x-show="!collapsed['__uncat__']" class="pl-3">
                            <template x-for="r in uncategorized" :key="r.id">
                                <div @click="loadFromSaved(r.id)"
                                     :class="isCurrentSaved(r.id)
                                         ? 'bg-orange-200/70 border-orange-400 ring-1 ring-orange-400 shadow-sm'
                                         : 'border-transparent hover:bg-orange-100/70 hover:border-orange-300'"
                                     class="flex items-start gap-2 px-2 py-1.5 my-0.5 rounded-lg cursor-pointer transition-all group border">
                                    <span :class="methodBadgeClass(r.method)"
                                          class="shrink-0 text-[10px] font-bold px-1.5 py-0.5 rounded-full mt-0.5 w-14 text-center"
                                          x-text="r.method"></span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1">
                                            <span x-show="isCurrentSaved(r.id)"
                                                  class="shrink-0 text-orange-600 text-[10px] leading-none">●</span>
                                            <p class="text-xs truncate font-semibold"
                                               :class="isCurrentSaved(r.id) ? 'text-orange-700' : 'text-stone-700'"
                                               x-text="r.title"></p>
                                        </div>
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
                <button @click="openSettings()"
                        title="SETTINGS"
                        class="ml-1 w-9 h-9 flex items-center justify-center rounded-full border border-orange-300 text-orange-700 hover:bg-orange-50 hover:rotate-45 transition-all duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
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

{{-- ======== Run Modal ======== --}}
<div x-show="runModal.open"
     x-cloak
     @keydown.escape.window="runModal.open = false"
     class="fixed inset-0 z-40 flex items-center justify-center bg-stone-900/40 backdrop-blur-sm px-4"
     x-transition.opacity>
    <div class="bg-white border border-orange-300 rounded-2xl shadow-xl w-full max-w-md p-6 space-y-4">
        <h3 class="text-lg font-bold text-orange-700 uppercase tracking-wider">RUN OPTIONS</h3>

        <div class="text-xs text-stone-500 leading-relaxed">
            <span class="font-bold text-stone-700" x-text="runModal.collectionName"></span>
            の保存済みリクエスト
            <span class="font-bold text-stone-700" x-text="runModal.requestCount"></span>
            件を順番に実行します。
        </div>

        <div class="space-y-1">
            <label class="text-xs text-stone-500 font-semibold uppercase">REPEAT (回数)</label>
            <input x-model.number="runModal.repeat"
                   type="number"
                   min="1"
                   max="1000"
                   class="w-full bg-white border border-orange-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition-all">
            <p class="text-[10px] text-stone-400">コレクション全体を何回繰り返すか（デフォルト 1）</p>
        </div>

        <div class="space-y-1">
            <label class="text-xs text-stone-500 font-semibold uppercase">DELAY (ms)</label>
            <input x-model.number="runModal.delay"
                   type="number"
                   min="0"
                   max="600000"
                   step="100"
                   class="w-full bg-white border border-orange-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 transition-all">
            <p class="text-[10px] text-stone-400">各リクエスト間の待機時間（ミリ秒、デフォルト 0）</p>
        </div>

        <div class="text-[11px] text-stone-500 bg-orange-50 border border-orange-200 rounded-lg px-3 py-2">
            合計実行回数:
            <span class="font-bold text-orange-700"
                  x-text="Math.max(1, runModal.repeat || 1) * runModal.requestCount"></span>
            <template x-if="runModal.delay > 0">
                <span>
                    / 推定最短時間:
                    <span class="font-bold text-orange-700"
                          x-text="formatDuration(Math.max(0, runModal.delay) * (Math.max(1, runModal.repeat || 1) * runModal.requestCount - 1))"></span>
                </span>
            </template>
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <button @click="runModal.open = false"
                    class="text-xs px-4 py-2 rounded-md border border-stone-300 text-stone-600 hover:bg-stone-50 transition-colors font-bold">
                CANCEL
            </button>
            <button @click="startCollectionRun()"
                    :disabled="runModal.requestCount === 0"
                    class="text-xs px-4 py-2 rounded-md bg-orange-500 hover:bg-orange-600 disabled:opacity-50 disabled:cursor-not-allowed text-white transition-colors font-bold">
                START
            </button>
        </div>
    </div>
</div>

{{-- ======== Settings Modal ======== --}}
<div x-show="settingsModal.open"
     x-cloak
     @keydown.escape.window="settingsModal.open = false"
     class="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/40 backdrop-blur-sm px-4"
     x-transition.opacity>
    <div class="bg-white border border-orange-300 rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden">

        {{-- Modal header --}}
        <div class="px-6 py-4 border-b border-orange-300 flex items-center">
            <h3 class="text-lg font-bold text-orange-700 uppercase tracking-wider">SETTINGS</h3>
            <button @click="settingsModal.open = false"
                    class="ml-auto text-stone-400 hover:text-stone-600 text-xl leading-none">×</button>
        </div>

        {{-- Tabs --}}
        <div class="flex border-b border-orange-300 px-4">
            <button @click="settingsModal.tab = 'variables'"
                    :class="settingsModal.tab === 'variables' ? 'border-orange-500 text-orange-600' : 'border-transparent text-stone-400 hover:text-stone-600'"
                    class="text-xs font-bold uppercase tracking-widest py-3 px-4 border-b-2 transition-all">
                VARIABLES
            </button>
            <button @click="settingsModal.tab = 'import'"
                    :class="settingsModal.tab === 'import' ? 'border-orange-500 text-orange-600' : 'border-transparent text-stone-400 hover:text-stone-600'"
                    class="text-xs font-bold uppercase tracking-widest py-3 px-4 border-b-2 transition-all">
                IMPORT
            </button>
            <button @click="settingsModal.tab = 'export'"
                    :class="settingsModal.tab === 'export' ? 'border-orange-500 text-orange-600' : 'border-transparent text-stone-400 hover:text-stone-600'"
                    class="text-xs font-bold uppercase tracking-widest py-3 px-4 border-b-2 transition-all">
                EXPORT
            </button>
        </div>

        {{-- Tab body --}}
        <div class="flex-1 overflow-y-auto scrollbar-warm px-6 py-5">

            {{-- Variables tab --}}
            <div x-show="settingsModal.tab === 'variables'" class="space-y-3">
                <p class="text-xs text-stone-500 leading-relaxed">
                    URL/HEADER/BODY 内で <code class="bg-orange-100 text-orange-700 px-1.5 py-0.5 rounded text-[11px] font-mono">&#123;&#123;NAME&#125;&#125;</code> と書くと値で置換されます。<br>
                    （変数はブラウザの localStorage に保存されます）
                </p>

                <template x-if="variables.length === 0">
                    <p class="text-xs text-stone-400 py-4 text-center">NO VARIABLES YET</p>
                </template>

                <template x-for="(v, index) in variables" :key="index">
                    <div class="flex gap-2 items-center">
                        <input x-model="v.key"
                               @input="saveVariables()"
                               placeholder="name"
                               class="flex-1 bg-white border border-orange-300 rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 placeholder-stone-300 font-mono transition-all">
                        <span class="text-stone-300 text-xs">=</span>
                        <input x-model="v.value"
                               @input="saveVariables()"
                               placeholder="value"
                               class="flex-1 bg-white border border-orange-300 rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 placeholder-stone-300 font-mono transition-all">
                        <button @click="removeVariable(index)"
                                class="text-[10px] px-2 py-1 rounded-md border border-rose-300 text-rose-600 hover:bg-rose-50 transition-colors font-bold">DELETE</button>
                    </div>
                </template>

                <button @click="addVariable()"
                        class="text-xs px-3 py-1.5 rounded-md border border-orange-300 text-orange-700 hover:bg-orange-50 transition-colors font-bold">
                    + ADD VARIABLE
                </button>
            </div>

            {{-- Import tab --}}
            <div x-show="settingsModal.tab === 'import'" class="space-y-4">
                <p class="text-xs text-stone-500 leading-relaxed">
                    エクスポートした JSON ファイル（複数選択可）または ZIP ファイルから一括インポートします。<br>
                    JSON の <code class="bg-orange-100 text-orange-700 px-1.5 py-0.5 rounded text-[11px] font-mono">collection</code> 項目に基づいてコレクションも自動作成されます。
                </p>

                <label class="block">
                    <span class="text-xs text-stone-500 font-semibold uppercase">SELECT FILES</span>
                    <input type="file"
                           multiple
                           accept=".json,.zip,application/json,application/zip"
                           @change="handleImportFiles($event)"
                           class="mt-2 block w-full text-xs text-stone-600
                                  file:mr-3 file:py-2 file:px-4
                                  file:rounded-md file:border file:border-orange-300
                                  file:text-xs file:font-bold file:uppercase
                                  file:bg-orange-50 file:text-orange-700
                                  hover:file:bg-orange-100 file:cursor-pointer cursor-pointer">
                </label>

                <template x-if="importStatus.message">
                    <div :class="importStatus.error
                                  ? 'bg-rose-50 border-rose-300 text-rose-700'
                                  : 'bg-emerald-50 border-emerald-300 text-emerald-700'"
                         class="border rounded-lg px-3 py-2 text-xs"
                         x-text="importStatus.message"></div>
                </template>
            </div>

            {{-- Export tab --}}
            <div x-show="settingsModal.tab === 'export'" class="space-y-4">
                <p class="text-xs text-stone-500 leading-relaxed">
                    保存済みのリクエストを全て ZIP ファイルとしてダウンロードします。<br>
                    1リクエスト = 1 JSON ファイルとして格納され、そのままインポートに利用できます。
                </p>

                <button @click="exportAll()"
                        :disabled="exportStatus.loading"
                        class="text-sm px-4 py-2.5 rounded-md bg-orange-500 hover:bg-orange-600 disabled:opacity-50 disabled:cursor-not-allowed text-white transition-colors font-bold">
                    <span x-text="exportStatus.loading ? 'EXPORTING...' : 'DOWNLOAD ALL AS ZIP'"></span>
                </button>

                <template x-if="exportStatus.message">
                    <div :class="exportStatus.error
                                  ? 'bg-rose-50 border-rose-300 text-rose-700'
                                  : 'bg-emerald-50 border-emerald-300 text-emerald-700'"
                         class="border rounded-lg px-3 py-2 text-xs"
                         x-text="exportStatus.message"></div>
                </template>
            </div>
        </div>

        {{-- Modal footer --}}
        <div class="px-6 py-3 border-t border-orange-300 flex justify-end bg-orange-50/40">
            <button @click="settingsModal.open = false"
                    class="text-xs px-4 py-2 rounded-md border border-stone-300 text-stone-600 hover:bg-white transition-colors font-bold">
                CLOSE
            </button>
        </div>
    </div>
</div>

<script>
function apiTester() {
    return {
        tab: 'headers',
        resTab: 'body',
        sideTab: 'saved',
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

        // ===== Settings (gear icon) =====
        settingsModal: {
            open: false,
            tab: 'variables',
        },
        variables: [], // [{ key, value }]
        importStatus: { message: '', error: false },
        exportStatus: { message: '', error: false, loading: false },

        // Collection run state
        collectionRun: {
            active: false,
            cancelled: false,
            collectionName: '',
            total: 0,
            currentIndex: 0,
            currentTitle: '',
            results: [],
        },

        // Run options modal
        runModal: {
            open: false,
            collection: null,
            collectionName: '',
            requestCount: 0,
            repeat: 1,
            delay: 0,
        },

        init() {
            this.loadHistory();
            this.loadCollections();
            this.loadVariables();
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

        buildBody(substitute = false) {
            const sub = (s) => substitute ? this.substituteVars(s) : s;
            if (this.isFormUrlEncoded()) {
                const params = new URLSearchParams();
                for (const f of this.form.formFields) {
                    const key = (f.key ?? '').trim();
                    if (!key) continue;
                    params.append(sub(key), sub(f.value ?? ''));
                }
                return params.toString();
            }
            return sub(this.form.body);
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
                const headersObj = this.headersToObject();
                const substitutedHeaders = {};
                for (const [k, v] of Object.entries(headersObj)) {
                    substitutedHeaders[this.substituteVars(k)] = this.substituteVars(v);
                }

                const res = await fetch('/api/proxy', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                    },
                    body: JSON.stringify({
                        method:  this.form.method,
                        url:     this.substituteVars(this.form.url),
                        headers: substitutedHeaders,
                        body:    this.buildBody(true),
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
            this.tab = this.canHaveBody() ? 'body' : 'headers';
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

            const openCollectionId = this.currentSaved
                ? (this.currentSaved.collection_id ?? '__uncat__')
                : null;
            const newCollapsed = {};
            for (const c of this.collections) {
                newCollapsed[c.id] = (openCollectionId !== c.id);
            }
            if (this.uncategorized.length > 0) {
                newCollapsed['__uncat__'] = (openCollectionId !== '__uncat__');
            }
            this.collapsed = newCollapsed;
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

        isCurrentSaved(id) {
            return !!(this.currentSaved && this.currentSaved.id === id);
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
            const key = r.collection_id ?? '__uncat__';
            this.collapsed[key] = false;
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

        historyItemClass(code) {
            if (code && code >= 200 && code < 300) {
                return 'border-transparent hover:bg-orange-100/70 hover:border-orange-300';
            }
            return 'bg-rose-50 border-rose-200 hover:bg-rose-100 hover:border-rose-300';
        },

        // ===== Collection run =====

        openRunModal(c) {
            if (this.collectionRun.active) return;
            const requests = c.saved_requests ?? [];
            if (requests.length === 0) {
                alert('This collection has no saved requests.');
                return;
            }
            this.runModal = {
                open: true,
                collection: c,
                collectionName: c.name,
                requestCount: requests.length,
                repeat: this.runModal.repeat || 1,
                delay: this.runModal.delay || 0,
            };
        },

        formatDuration(ms) {
            if (!ms || ms <= 0) return '0ms';
            if (ms < 1000) return ms + 'ms';
            const sec = ms / 1000;
            if (sec < 60) return sec.toFixed(sec < 10 ? 1 : 0) + 's';
            const min = Math.floor(sec / 60);
            const rem = Math.round(sec - min * 60);
            return min + 'm' + (rem > 0 ? ' ' + rem + 's' : '');
        },

        sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        },

        async startCollectionRun() {
            const c = this.runModal.collection;
            if (!c) return;
            const repeat = Math.max(1, parseInt(this.runModal.repeat, 10) || 1);
            const delay  = Math.max(0, parseInt(this.runModal.delay, 10) || 0);
            this.runModal.open = false;
            await this.runCollection(c, { repeat, delay });
        },

        async runCollection(c, options = {}) {
            if (this.collectionRun.active) return;
            const requests = c.saved_requests ?? [];
            if (requests.length === 0) {
                alert('This collection has no saved requests.');
                return;
            }

            const repeat = Math.max(1, parseInt(options.repeat, 10) || 1);
            const delay  = Math.max(0, parseInt(options.delay, 10) || 0);
            const total  = requests.length * repeat;

            this.sideTab = 'history';
            this.collectionRun = {
                active: true,
                cancelled: false,
                collectionName: repeat > 1 ? `${c.name} (×${repeat})` : c.name,
                total,
                currentIndex: 0,
                currentTitle: '',
                results: [],
            };

            let step = 0;
            outer:
            for (let loop = 0; loop < repeat; loop++) {
                for (let i = 0; i < requests.length; i++) {
                    if (this.collectionRun.cancelled) break outer;

                    if (delay > 0 && step > 0) {
                        const slept = await this.sleepInterruptible(delay);
                        if (!slept) break outer;
                    }

                    const summary = requests[i];
                    step++;
                    this.collectionRun.currentIndex = step;
                    this.collectionRun.currentTitle = repeat > 1
                        ? `[${loop + 1}/${repeat}] ${summary.title}`
                        : summary.title;

                    let result = {
                        id:          summary.id,
                        title:       summary.title,
                        method:      summary.method,
                        url:         summary.url,
                        status_code: 0,
                        duration_ms: 0,
                        error:       null,
                    };

                    try {
                        const detailRes = await fetch('/api/saved-requests/' + summary.id);
                        if (!detailRes.ok) throw new Error('failed to load saved request');
                        const r = await detailRes.json();

                        const headers = (r.request_headers && typeof r.request_headers === 'object') ? r.request_headers : {};
                        const substitutedHeaders = {};
                        for (const [k, v] of Object.entries(headers)) {
                            substitutedHeaders[this.substituteVars(k)] =
                                this.substituteVars(typeof v === 'string' ? v : String(v ?? ''));
                        }

                        const proxyRes = await fetch('/api/proxy', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrf(),
                            },
                            body: JSON.stringify({
                                method:  r.method,
                                url:     this.substituteVars(r.url),
                                headers: substitutedHeaders,
                                body:    this.substituteVars(r.request_body ?? ''),
                            }),
                        });

                        const data = await proxyRes.json().catch(() => ({}));
                        if (!proxyRes.ok) {
                            result.error = data.message || ('HTTP ' + proxyRes.status);
                        } else {
                            result.status_code = data.status_code;
                            result.duration_ms = data.duration_ms;
                        }
                    } catch (e) {
                        result.error = e.message;
                    }

                    this.collectionRun.results.push(result);
                    await this.loadHistory();
                }
            }

            this.collectionRun.active = false;
            this.collectionRun.currentTitle = this.collectionRun.cancelled
                ? 'CANCELLED (' + this.collectionRun.results.length + '/' + this.collectionRun.total + ')'
                : 'COMPLETED (' + this.collectionRun.results.length + '/' + this.collectionRun.total + ')';
        },

        async sleepInterruptible(ms) {
            const stepMs = 100;
            let waited = 0;
            while (waited < ms) {
                if (this.collectionRun.cancelled) return false;
                const chunk = Math.min(stepMs, ms - waited);
                await this.sleep(chunk);
                waited += chunk;
            }
            return !this.collectionRun.cancelled;
        },

        cancelCollectionRun() {
            if (this.collectionRun.active) {
                this.collectionRun.cancelled = true;
            }
        },

        clearCollectionRun() {
            this.collectionRun = {
                active: false,
                cancelled: false,
                collectionName: '',
                total: 0,
                currentIndex: 0,
                currentTitle: '',
                results: [],
            };
        },

        // ===== Settings: open/close =====

        openSettings() {
            this.importStatus = { message: '', error: false };
            this.exportStatus = { message: '', error: false, loading: false };
            this.settingsModal.open = true;
        },

        // ===== Variables (localStorage) =====

        loadVariables() {
            try {
                const raw = localStorage.getItem('apiTester.variables');
                const parsed = raw ? JSON.parse(raw) : [];
                this.variables = Array.isArray(parsed) ? parsed : [];
            } catch {
                this.variables = [];
            }
        },

        saveVariables() {
            try {
                localStorage.setItem('apiTester.variables', JSON.stringify(this.variables));
            } catch {}
        },

        addVariable() {
            this.variables.push({ key: '', value: '' });
            this.saveVariables();
        },

        removeVariable(index) {
            this.variables.splice(index, 1);
            this.saveVariables();
        },

        substituteVars(input) {
            if (typeof input !== 'string' || input === '') return input;
            const map = {};
            for (const v of this.variables) {
                const k = (v.key ?? '').trim();
                if (!k) continue;
                map[k] = v.value ?? '';
            }
            return input.replace(/\{\{\s*([^{}\s]+)\s*\}\}/g, (match, name) => {
                return Object.prototype.hasOwnProperty.call(map, name) ? map[name] : match;
            });
        },

        // ===== Import =====

        async handleImportFiles(event) {
            const files = Array.from(event.target.files || []);
            event.target.value = '';
            if (files.length === 0) return;

            this.importStatus = { message: 'READING FILES...', error: false };

            const items = [];
            const errors = [];

            try {
                for (const file of files) {
                    const lower = file.name.toLowerCase();
                    if (lower.endsWith('.zip')) {
                        if (typeof JSZip === 'undefined') {
                            throw new Error('JSZip is not loaded yet. Please retry.');
                        }
                        const buf = await file.arrayBuffer();
                        const zip = await JSZip.loadAsync(buf);
                        const entries = Object.values(zip.files).filter(
                            f => !f.dir && f.name.toLowerCase().endsWith('.json')
                        );
                        for (const entry of entries) {
                            try {
                                const text = await entry.async('string');
                                const obj = JSON.parse(text);
                                items.push(this.normalizeImportItem(obj, entry.name));
                            } catch (e) {
                                errors.push(`${file.name}/${entry.name}: ${e.message}`);
                            }
                        }
                    } else {
                        try {
                            const text = await file.text();
                            const obj = JSON.parse(text);
                            items.push(this.normalizeImportItem(obj, file.name));
                        } catch (e) {
                            errors.push(`${file.name}: ${e.message}`);
                        }
                    }
                }

                if (items.length === 0) {
                    this.importStatus = {
                        message: 'NO VALID REQUESTS FOUND.' + (errors.length ? ' (' + errors.length + ' ERRORS)' : ''),
                        error: true,
                    };
                    return;
                }

                const res = await fetch('/api/saved-requests/import', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                    },
                    body: JSON.stringify({ items }),
                });

                if (!res.ok) {
                    const data = await res.json().catch(() => ({}));
                    throw new Error(data.message || ('HTTP ' + res.status));
                }

                const data = await res.json();
                let msg = `IMPORTED ${data.imported} REQUEST(S).`;
                if (errors.length) msg += ` (${errors.length} FILE(S) SKIPPED)`;
                this.importStatus = { message: msg, error: false };

                await this.loadCollections();
            } catch (e) {
                this.importStatus = { message: 'IMPORT FAILED: ' + e.message, error: true };
            }
        },

        normalizeImportItem(obj, sourceName) {
            if (!obj || typeof obj !== 'object') {
                throw new Error('not a JSON object');
            }
            if (!obj.method || !obj.url) {
                throw new Error('missing method or url');
            }
            return {
                title:           obj.title || sourceName.replace(/\.json$/i, ''),
                method:          String(obj.method).toUpperCase(),
                url:             String(obj.url),
                request_headers: (obj.request_headers && typeof obj.request_headers === 'object') ? obj.request_headers : null,
                request_body:    obj.request_body ?? null,
                content_type:    obj.content_type ?? null,
                collection:      obj.collection ?? null,
            };
        },

        // ===== Export =====

        async exportAll() {
            this.exportStatus = { message: '', error: false, loading: true };
            try {
                if (typeof JSZip === 'undefined') {
                    throw new Error('JSZip is not loaded yet. Please retry.');
                }

                const res = await fetch('/api/saved-requests/export');
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                const items = data.items || [];

                if (items.length === 0) {
                    this.exportStatus = { message: 'NO SAVED REQUESTS TO EXPORT.', error: true, loading: false };
                    return;
                }

                const zip = new JSZip();
                const usedNames = new Set();

                for (const item of items) {
                    const colDir = item.collection ? this.slugify(item.collection) : '_uncategorized';
                    const baseName = this.slugify(item.title || 'request') || 'request';

                    let fileName = `${colDir}/${baseName}.json`;
                    let counter = 2;
                    while (usedNames.has(fileName)) {
                        fileName = `${colDir}/${baseName}-${counter}.json`;
                        counter++;
                    }
                    usedNames.add(fileName);

                    zip.file(fileName, JSON.stringify(item, null, 2));
                }

                const blob = await zip.generateAsync({ type: 'blob' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                const ts = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
                a.href = url;
                a.download = `api-tester-export-${ts}.zip`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);

                this.exportStatus = {
                    message: `EXPORTED ${items.length} REQUEST(S).`,
                    error: false,
                    loading: false,
                };
            } catch (e) {
                this.exportStatus = { message: 'EXPORT FAILED: ' + e.message, error: true, loading: false };
            }
        },

        slugify(s) {
            return String(s)
                .normalize('NFKD')
                .replace(/[^\w\s\-\.]/g, '')
                .trim()
                .replace(/\s+/g, '_')
                .slice(0, 80);
        },
    };
}
</script>
</body>
</html>
