@if(($chatVariant ?? 'admin') === 'admin')
<button id="admin-chat-toggle" type="button" class="admin-chat-float-toggle billing-float-chat-toggle" aria-label="Open admin chat">
    <svg class="admin-chat-float-icon billing-float-chat-icon is-chat" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M6.6 18.4L3.8 20l.8-3.1A7.5 7.5 0 1 1 12 20a7.4 7.4 0 0 1-5.4-1.6Z" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M8.5 11.4h7M8.5 8.9h5.4" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
    </svg>
    <svg class="admin-chat-float-icon billing-float-chat-icon is-close" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
    </svg>
    <span id="admin-chat-unread" class="admin-chat-float-badge billing-float-chat-unread is-hidden">0</span>
    <span id="admin-chat-pending" class="admin-chat-float-badge billing-float-chat-unread is-warning is-hidden">0</span>
</button>

<div id="admin-chat-card" class="admin-chat-float-card billing-float-chat-card is-admin" aria-live="polite">
    <div class="admin-chat-float-head billing-float-chat-head">
        <div class="billing-float-chat-head-row">
            <div>
                <div class="fw-semibold">Live Chat Billing</div>
                <div class="small text-secondary">Admin Inbox</div>
                <div id="admin-chat-head-status" class="small text-secondary">Pantau unread chat dan pending billing dari sini.</div>
            </div>
            <button id="admin-chat-close" type="button" class="admin-chat-float-close billing-float-chat-close" aria-label="Close admin chat">x</button>
        </div>
    </div>
    <div class="admin-chat-float-body billing-float-chat-shell h-100 p-0 border-0">
        <div class="admin-chat-inbox billing-admin-inbox p-2">
            <div class="admin-chat-search-top billing-admin-search-top">
                <input id="admin-chat-search" type="text" class="form-control form-control-sm" placeholder="Cari user / email...">
            </div>
            <div class="admin-chat-sidebar billing-admin-sidebar">
                <div id="admin-chat-threads" class="admin-chat-user-list billing-admin-user-list h-100"></div>
            </div>
            <div class="admin-chat-main billing-admin-main">
                <div class="admin-chat-main-head billing-admin-head">
                    <div class="admin-chat-main-head-row billing-admin-head-row">
                        <div class="admin-chat-main-title-wrap billing-admin-thread-info">
                            <div id="admin-chat-title" class="fw-semibold">Pilih percakapan</div>
                            <div id="admin-chat-subtitle" class="small text-secondary mt-1">Thread chat billing user akan tampil di sini.</div>
                        </div>
                        <button id="admin-chat-clear-thread" type="button" class="admin-chat-thread-clear billing-admin-thread-clear" aria-label="Tutup chat user" title="Tutup chat user">x</button>
                    </div>
                </div>
                <div id="admin-chat-messages" class="chat-messages billing-float-chat-messages"></div>
                <div id="admin-chat-pending-list" class="admin-chat-pending-list billing-admin-pending-list"></div>
                <form id="admin-chat-form" class="chat-window-form billing-float-chat-form">
                    <label for="admin-chat-input" class="form-label mb-2">Balas ke user</label>
                    <div class="admin-chat-compose billing-chat-compose">
                        <textarea id="admin-chat-input" class="form-control" rows="2" placeholder="Tulis balasan admin..."></textarea>
                        <button id="admin-chat-send" type="submit" class="admin-chat-send-icon billing-chat-send-icon" aria-label="Kirim balasan">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M5 12h12M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                    <div id="admin-chat-status" class="small text-secondary mt-2 billing-admin-help">Pilih user dulu untuk mulai chat.</div>
                </form>
            </div>
        </div>
    </div>
</div>
@else
<button id="billing-float-chat-toggle" type="button" class="billing-float-chat-toggle" aria-label="Open chat">
    <svg class="billing-float-chat-icon is-chat" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M6.6 18.4L3.8 20l.8-3.1A7.5 7.5 0 1 1 12 20a7.4 7.4 0 0 1-5.4-1.6Z" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M8.5 11.4h7M8.5 8.9h5.4" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
    </svg>
    <svg class="billing-float-chat-icon is-close" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
    </svg>
    <span id="billing-float-chat-unread" class="billing-float-chat-unread is-hidden">0</span>
    <span id="billing-float-chat-pending" class="billing-float-chat-unread is-warning is-hidden">0</span>
</button>
<div id="billing-float-chat-card" class="billing-float-chat-card{{ $isAdmin ? ' is-admin' : '' }}" aria-live="polite">
    <div class="billing-float-chat-head">
        <div class="billing-float-chat-head-row">
            <div>
                <div class="fw-semibold">Live Chat Billing</div>
                <div class="small text-secondary">{{ $isAdmin ? 'Admin Inbox' : 'Admin Billing' }}</div>
            </div>
            <button id="billing-float-chat-close" type="button" class="billing-float-chat-close" aria-label="Close chat">x</button>
        </div>
        <div id="billing-float-chat-status" class="small text-secondary mt-2">{{ $isAdmin ? 'Pilih user untuk lihat chat dan proses pending billing.' : 'Klik ikon chat untuk mulai percakapan.' }}</div>
    </div>
    @if($isAdmin)
    <div class="billing-float-chat-shell h-100 p-0 border-0">
        <div class="billing-admin-inbox p-2">
            <div class="billing-admin-search-top">
                <input id="billing-admin-user-search" type="text" class="form-control form-control-sm" placeholder="Cari user / email...">
            </div>
            <div class="billing-admin-sidebar">
                <div id="billing-admin-user-list" class="billing-admin-user-list"></div>
            </div>
            <div class="billing-admin-main">
                <div class="billing-admin-head">
                    <div class="billing-admin-head-row">
                        <div class="billing-admin-thread-info">
                            <div id="billing-admin-thread-title" class="fw-semibold">Pilih user</div>
                            <div id="billing-admin-thread-subtitle" class="small text-secondary">Thread chat user muncul di sini.</div>
                        </div>
                        <button id="billing-admin-clear-thread" type="button" class="billing-admin-thread-clear" title="Tutup chat user" aria-label="Tutup chat user">x</button>
                    </div>
                </div>
                <div id="billing-float-chat-messages" class="billing-float-chat-messages"></div>
                <div id="billing-admin-pending-list" class="billing-admin-pending-list"></div>
                <form id="billing-float-chat-form" class="billing-float-chat-form">
                    <label for="billing-float-chat-input" class="form-label mb-2">Balas ke user</label>
                    <div class="billing-chat-compose">
                        <textarea id="billing-float-chat-input" class="form-control" rows="2" placeholder="Tulis balasan admin..."></textarea>
                        <button id="billing-float-chat-send" type="submit" class="billing-chat-send-icon" aria-label="Kirim balasan">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M5 12h12M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                    <div class="d-flex justify-content-between align-items-center gap-2 mt-2">
                        <div class="small text-secondary billing-admin-help">Accept/Reject pending pakai icon di atas.</div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @else
    <div id="billing-float-chat-messages" class="billing-float-chat-messages"></div>
    <form id="billing-float-chat-form" class="billing-float-chat-form">
        <label for="billing-float-chat-input" class="form-label mb-2">Tulis pesan</label>
        <div class="billing-chat-compose">
            <textarea id="billing-float-chat-input" class="form-control" rows="3" placeholder="Contoh: saya butuh follow up billing account saya."></textarea>
            <button id="billing-float-chat-send" type="submit" class="billing-chat-send-icon" aria-label="Kirim pesan">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M5 12h12M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </div>
        <div class="small text-secondary mt-2">Pesan auto refresh.</div>
    </form>
    @endif
</div>
@endif
