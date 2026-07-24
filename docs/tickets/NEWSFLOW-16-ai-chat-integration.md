# NEWSFLOW-16 — AI Common Chat Integration

**Epic:** AI Common Chat + Auto-Invoice Assistant  
**Child tickets:** NEWSFLOW-14 (UI), NEWSFLOW-15 (Backend / Python)  
**Status:** Implemented (foundation) — July 2026  
**Stack:** Laravel + Inertia + Vue 3 · Python FastAPI · OpenRouter

---

## 1. Overview

Add a **global AI chat assistant** across the admin app so operators can:

- Ask operational questions (shops, invoices, payments, reports)
- Run **automatic invoice generation** (same business rules as the existing Auto-Generate modal) via natural language
- See **preview → confirm → progress → summary** without leaving the current page

### Goals

- Smooth, low-friction chat UX on every admin page
- Keep financial logic and permissions in Laravel
- Isolate LLM/prompts/tools in a separate Python service
- Reuse existing auto-generate APIs and jobs (no duplicate generation logic)

### Non-goals

- Browser talking directly to Python or xAI
- Free-form database / SQL access for the model
- Replacing the existing Auto-Generate modal (chat is an alternate entry point)
- Full document RAG in the first release

---

## 2. Architecture

```text
┌─────────────────────────────────────────────────────────────────┐
│  Browser (Vue 3 + Inertia)                                      │
│  AdminLayout → AiChatWidget (floating FAB + panel)              │
└────────────────────────────┬────────────────────────────────────┘
                             │ HTTPS (session cookie + CSRF)
                             │ POST /admin/chat/messages
                             │ POST /admin/chat/messages/stream
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│  Laravel (Newsflow)                                             │
│  · Auth / Spatie permissions / throttle / audit                 │
│  · ChatController → ChatService / AiGateway                     │
│  · Owns: users, shops, invoices, money, conversation history    │
│  · Tool endpoints for Python (service token + user context)     │
│  · Existing auto-generate: preview / start / progress / clear   │
│  · Job: GenerateInvoiceFromLastWeek                             │
└────────────────────────────┬────────────────────────────────────┘
                             │ HTTP + AI_SERVICE_TOKEN
                             │ POST /v1/chat  |  /v1/chat/stream
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│  Python AI Service (FastAPI)  ·  ai-service/                    │
│  · System prompt + conversation handling                        │
│  · OpenRouter client (OPENROUTER_API_KEY)                       │
│  · Tool planning: preview / start / progress auto-generate      │
│  · Streaming (SSE)                                              │
└────────────────────────────┬────────────────────────────────────┘
                             │ OpenAI-compatible API
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│  OpenRouter                                                     │
│  Base URL: https://openrouter.ai/api/v1                         │
│  Model:    openai/gpt-4o-mini (or any OpenRouter model slug)  │
│  Env:      OPENROUTER_API_KEY                                   │
└─────────────────────────────────────────────────────────────────┘
```

### Ownership matrix

| Layer | Owns |
|-------|------|
| **Vue** | Floating chat UI, message rendering, action cards, progress display |
| **Laravel** | Auth, permissions, rate limits, invoice/shop domain, job queue, chat history (preferred), tool execution |
| **Python** | Prompts, model calls, tool selection, streaming format, optional short-term agent memory |
| **OpenRouter** | LLM inference only (routes to many models) |

### Security rules

1. Browser **never** calls Python or OpenRouter.
2. `OPENROUTER_API_KEY` lives only on the Python service.
3. Laravel ↔ Python authenticated with `AI_SERVICE_TOKEN` (or mTLS in production).
4. Auto-generate tools re-check user permission (`auto generate invoice` / `manage invoices`).
5. Rate-limit chat routes (e.g. `throttle:30,1`).
6. Cap message length and history window (e.g. last 20 messages).

---

## 3. End-to-end flows

### 3.1 Common chat (Q&A)

```text
User types message
  → Vue AiChatWidget
  → POST /admin/chat/messages  (Laravel auth + permission)
  → AiGateway → Python POST /v1/chat
  → Python builds messages [system + history + user]
  → OpenRouter completion
  → Reply returned to Laravel → Vue
  → Optional: persist user + assistant messages in Laravel DB
```

### 3.2 Auto-invoice generation (happy path)

```text
1. User: "Generate invoices for 2026-07-17 from last week"
2. Vue → Laravel chat gateway → Python
3. Python detects intent → tool: preview_auto_generate_invoices(date)
4. Python → Laravel internal tool route
5. Laravel runs existing preview logic
     (shops with last-week invoice, missing target-date invoice)
6. Preview payload returned to chat
7. UI shows Preview action card:
     · target date
     · last week date
     · eligible shops count
     · Confirm / Cancel
8. User confirms
9. Python/Laravel tool: start_auto_generate_invoices(date)
10. Laravel InvoiceService::dispatchInvoiceGeneration(...)
11. Queue: GenerateInvoiceFromLastWeek per shop
12. UI Progress card polls GET progress (or stream events)
13. Result card: created / skipped / failed + link to invoices
```

### 3.3 Auto-invoice — edge cases

| Case | Behaviour |
|------|-----------|
| Eligible shops = 0 | Explain: already generated or no last-week source; no start |
| Missing permission | Refuse start; tell user they need auto-generate permission |
| User cancels confirm | No jobs dispatched; session continues |
| Partial job failures | Progress still completes; chat summarizes created/skipped/failed |
| Invalid date | Ask user to clarify date; do not call start |

### 3.4 Sequence diagram (auto-generate)

```text
User          Vue              Laravel             Python            xAI          Queue
 │             │                  │                   │               │             │
 │─ message ──▶│                  │                   │               │             │
 │             │── POST /chat ───▶│                   │               │             │
 │             │                  │── /v1/chat ──────▶│               │             │
 │             │                  │                   │── complete ──▶│             │
 │             │                  │                   │◀─ tool call ──│             │
 │             │                  │◀─ preview tool ───│               │             │
 │             │                  │── preview data ──▶│               │             │
 │             │                  │◀── reply+action ──│               │             │
 │             │◀── JSON ─────────│                   │               │             │
 │◀─ preview ──│                  │                   │               │             │
 │─ confirm ──▶│                  │                   │               │             │
 │             │── POST /chat ───▶│── /v1/chat ──────▶│               │             │
 │             │                  │◀─ start tool ─────│               │             │
 │             │                  │── dispatch jobs ───────────────────────────────▶│
 │             │                  │◀── progress ──────│ (poll)        │             │
 │◀─ progress ─│                  │                   │               │             │
 │◀─ summary ──│                  │                   │               │             │
```

---

## 4. Ticket breakdown

### NEWSFLOW-16 — Epic: AI Common Chat + Auto-Invoice Assistant

**Summary:** Deliver global AI chat and connect it to automatic invoice generation with a safe preview → confirm → progress flow.

**Acceptance criteria**

- [ ] Authenticated admin can open chat on any admin page
- [ ] Messages flow Vue → Laravel → Python → OpenRouter
- [ ] User can preview and start auto-invoice generation via chat
- [ ] Progress and final summary appear in chat
- [ ] Permissions enforced (`use ai chat` + auto-generate permission for generation)
- [ ] Rate limiting and service-to-service auth in place
- [ ] No financial writes except through existing Laravel invoice APIs/jobs
- [ ] Behaviour parity with existing Auto-Generate modal for business outcomes

**Implementation order**

1. NEWSFLOW-15 — Backend foundation  
2. NEWSFLOW-14 — UI component  
3. Epic integration polish — auto-invoice tools end-to-end  

---

### NEWSFLOW-15 — Integrate AI SDK and set up backend

**Summary:** Python AI service + Laravel chat gateway + tool contracts for auto-generate.

#### A. Python service (`ai-service/`)

Suggested layout:

```text
ai-service/
  app/
    main.py
    routers/chat.py
    services/llm_client.py
    services/chat_service.py
    services/tools.py
    prompts/system.py
  requirements.txt
  Dockerfile
  .env.example
  README.md
```

**Endpoints**

| Method | Path | Purpose |
|--------|------|---------|
| `GET` | `/health` | Health check |
| `POST` | `/v1/chat` | Non-streaming chat (MVP) |
| `POST` | `/v1/chat/stream` | SSE streaming (preferred UX) |

**Tools (Python plans; Laravel executes)**

| Tool | Description |
|------|-------------|
| `preview_auto_generate_invoices(date)` | Eligible shops / last-week date / counts |
| `start_auto_generate_invoices(date)` | Dispatch existing generation jobs |
| `get_auto_generate_progress()` | created / skipped / failed / status |

**Env (Python)**

```env
OPENROUTER_API_KEY=
OPENROUTER_BASE_URL=https://openrouter.ai/api/v1
OPENROUTER_MODEL=openai/gpt-4o-mini
AI_SERVICE_TOKEN=
LARAVEL_BASE_URL=http://localhost:8000
```

**Dependencies (indicative):** `fastapi`, `uvicorn`, `openai`, `httpx`, `pydantic`

#### B. Laravel gateway

Suggested layout:

```text
app/Domain/Chat/
  Models/Conversation.php
  Models/Message.php
  Repositories/ChatRepository.php
  Services/ChatService.php
  Services/AiGateway.php
app/Http/Controllers/Admin/ChatController.php
```

**Public routes (browser, session auth)**

| Method | Path | Middleware |
|--------|------|------------|
| `POST` | `/admin/chat/messages` | `auth`, permission `use ai chat`, throttle |
| `POST` | `/admin/chat/messages/stream` | same (optional phase 2) |
| `GET` | `/admin/chat/conversations` | same (optional) |

**Internal tool routes (Python → Laravel)**

Authenticated with `AI_SERVICE_TOKEN` + `user_id` context; re-check user permissions.

| Method | Path | Maps to existing behaviour |
|--------|------|----------------------------|
| `POST` | `/internal/ai/tools/auto-generate/preview` | Same as auto-generate preview |
| `POST` | `/internal/ai/tools/auto-generate/start` | Same as `dispatchInvoiceGeneration` |
| `GET` | `/internal/ai/tools/auto-generate/progress` | Same as generation progress cache |

**Existing auto-generate routes (keep; used by modal + tools)**

| Method | Path |
|--------|------|
| `POST` | `/admin/invoices/auto-generate/preview` |
| `POST` | `/admin/invoices/auto-generate` |
| `GET` | `/admin/invoices/auto-generate/progress` |
| `POST` | `/admin/invoices/auto-generate/clear` |

**Laravel env**

```env
AI_SERVICE_URL=http://localhost:8001
AI_SERVICE_TOKEN=
```

**Config (`config/services.php`)**

```php
'ai' => [
    'url' => env('AI_SERVICE_URL', 'http://localhost:8001'),
    'token' => env('AI_SERVICE_TOKEN'),
    'timeout' => env('AI_SERVICE_TIMEOUT', 60),
],
```

#### C. Chat request / response contract

**Request (Laravel → Python)**

```json
{
  "user_id": 1,
  "conversation_id": null,
  "message": "Generate invoices for 2026-07-17 from last week",
  "locale": "en",
  "history": [],
  "context": {
    "permissions": ["use ai chat", "auto generate invoice"]
  }
}
```

**Response (MVP)**

```json
{
  "conversation_id": "uuid-or-id",
  "reply": "I found 42 eligible shops for 2026-07-17 (source: last week 2026-07-10). Confirm to start?",
  "actions": [
    {
      "type": "auto_generate_preview",
      "payload": {
        "date": "2026-07-17",
        "last_week_date": "2026-07-10",
        "eligible_shops_count": 42
      }
    }
  ]
}
```

**Action types (UI contract)**

| `type` | UI treatment |
|--------|----------------|
| `auto_generate_preview` | Preview card + Confirm / Cancel |
| `auto_generate_progress` | Progress bar + counters |
| `auto_generate_result` | Summary + link to invoices |
| `info` | Plain informational card (optional) |

#### D. NEWSFLOW-15 acceptance criteria

- [ ] `ai-service` runs locally and returns an OpenRouter model reply for a simple message
- [ ] Laravel `POST /admin/chat/messages` requires auth + permission + throttle
- [ ] Laravel proxies to Python; errors surface cleanly
- [ ] Tool contracts exist for preview / start / progress
- [ ] Secrets never exposed to frontend
- [ ] Health check for Python service
- [ ] README: env vars, run commands, architecture

---

### NEWSFLOW-14 — Implement UI component for chat

**Summary:** Reusable floating chat UI in `AdminLayout`, wired to Laravel chat APIs, with action cards for auto-invoice preview/progress.

#### Components

```text
resources/js/Components/Chat/
  AiChatWidget.vue          # FAB + shell
  ChatPanel.vue             # open/close panel
  ChatMessageList.vue       # message list
  ChatInput.vue             # composer
  ChatActionCard.vue        # structured actions
resources/js/Composables/
  useAiChat.js              # send, history, progress poll
```

#### UX requirements

- FAB bottom-right on all admin pages (`AdminLayout.vue`)
- Panel: title, close, new chat, scrollable messages, sticky input
- States: idle, sending, streaming, error, hidden without permission
- Message types: user text, assistant text, action cards
- Enter send · Shift+Enter newline
- Disable double-submit; loading indicators
- Match existing design system (Tailwind / shadcn-vue / lucide)
- i18n: `en` + `si` for labels
- Mobile: full-height panel on small screens

#### Permission UX

- Show widget only if user has `use ai chat`
- Confirm generate only if user also has auto-generate permission; otherwise show insufficient permission

#### Frontend rules

- Call **only** Laravel routes (session + CSRF)
- Do **not** call Python or OpenRouter from the browser
- Do **not** use Inertia navigation for each message (use `fetch` / XHR)

#### Design parity with modal

Reuse wording/concepts from `AutoGenerateInvoiceModal.vue`:

- Select / mention date
- Eligible shop count
- Last week source date
- Progress: total / processed / created / skipped / failed
- Completion summary

#### NEWSFLOW-14 acceptance criteria

- [ ] Chat FAB visible on admin pages for permitted users
- [ ] User can send a message and see assistant reply
- [ ] UI renders auto-generate preview, confirm, progress, result
- [ ] No full page reload on send
- [ ] Empty / loading / error states handled
- [ ] No API keys in frontend bundle
- [ ] Mobile-friendly panel

---

## 5. Auto-invoice domain reference (existing)

Do **not** reimplement generation in Python. Call through Laravel.

| Piece | Location |
|-------|----------|
| Modal UI | `resources/js/Components/Admin/AutoGenerateInvoiceModal.vue` |
| Routes | `routes/auth.php` — `invoices/auto-generate/*` |
| Service | `InvoiceService::dispatchInvoiceGeneration`, preview helpers, progress cache |
| Job | `app/Jobs/GenerateInvoiceFromLastWeek.php` |
| Permission | `auto generate invoice` / `manage invoices` |

**Business rules (preserve)**

- Copy quantities from last week’s same weekday daily invoice
- Re-resolve prices for the target date context as current job does
- Skip if target invoice already exists
- Skip if no last-week invoice
- Create **draft** invoices only
- Financial immutability: no rewriting of historical invoices

---

## 6. Data model (recommended)

Laravel-owned history (preferred for admin audit/reporting):

### `chat_conversations`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint / uuid | PK |
| `user_id` | FK users | Owner |
| `title` | string nullable | Optional auto title |
| `created_at` / `updated_at` | timestamps | |

### `chat_messages`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint / uuid | PK |
| `conversation_id` | FK | |
| `role` | enum | `user`, `assistant`, `system` |
| `content` | text | |
| `actions` | json nullable | Structured action payloads |
| `created_at` | timestamp | |

MVP may start **without** DB (in-memory / session only) and add persistence in a follow-up.

---

## 7. System prompt (Python) — draft

```text
You are Newsflow Assistant for a newspaper distribution management system.

You help operators with shops, daily invoices, returns, payments, and reports.
You can help trigger automatic invoice generation based on last week's same-day invoices.

Rules:
- Prefer short, operational answers.
- Never invent balances, quantities, or prices. Use tools/context only.
- For auto-generate: always preview first; never start generation without explicit user confirmation.
- If the user lacks permission, explain clearly.
- Respect financial immutability: invoices are created as drafts via existing system tools only.
- Support English and Sinhala when the user writes in those languages.
```

---

## 8. Local development

```text
# Terminal 1 — Laravel
php artisan serve
php artisan queue:listen --tries=1

# Terminal 2 — Vite
npm run dev

# Terminal 3 — Python AI service
cd ai-service
python -m venv .venv
# Windows: .venv\Scripts\activate
# Unix:    source .venv/bin/activate
pip install -r requirements.txt
uvicorn app.main:app --reload --port 8001
```

**Minimum env**

```env
# Laravel .env
AI_SERVICE_URL=http://127.0.0.1:8001
AI_SERVICE_TOKEN=dev-shared-secret

# ai-service/.env
OPENROUTER_API_KEY=sk-or-...
OPENROUTER_BASE_URL=https://openrouter.ai/api/v1
OPENROUTER_MODEL=openai/gpt-4o-mini
AI_SERVICE_TOKEN=dev-shared-secret
LARAVEL_BASE_URL=http://127.0.0.1:8000
```

---

## 9. Production notes

- Run Python service on private network only; not public without auth
- Prefer container deploy (Docker Compose / same host network as Laravel)
- Health checks: Laravel app + `GET {AI_SERVICE_URL}/health`
- Log: `user_id`, conversation id, tool name, target date, outcome (no raw secrets)
- Monitor OpenRouter cost/usage; consider daily budget alerts
- Timeouts: Laravel → Python ~60s; stream preferred for long replies

---

## 10. Phased delivery

| Phase | Scope | Ticket |
|-------|--------|--------|
| **1** | Python service + Laravel gateway + non-streaming chat | NEWSFLOW-15 |
| **2** | Floating chat UI + basic Q&A | NEWSFLOW-14 |
| **3** | Auto-generate tools: preview → confirm → progress → summary | NEWSFLOW-16 |
| **4** | SSE streaming, conversation persistence, i18n polish | Follow-up |
| **5** | More tools (shop balance, daily report summary), optional RAG | Future |

---

## 11. Testing checklist

### Backend

- [ ] Unauthenticated chat request → 401/redirect
- [ ] User without `use ai chat` → 403
- [ ] Python down → Laravel returns controlled error (no 500 leak)
- [ ] Invalid service token on internal tools → 401
- [ ] Preview tool returns same counts as modal preview for same date
- [ ] Start tool dispatches jobs only when eligible > 0 and user permitted
- [ ] Progress matches cache used by modal

### Frontend

- [ ] FAB hidden without permission
- [ ] Send message shows reply
- [ ] Preview card Confirm starts generation
- [ ] Cancel does not start generation
- [ ] Progress updates until completed
- [ ] Result summary matches progress counters

### Regression

- [ ] Existing Auto-Generate modal still works unchanged
- [ ] Manual invoice create/edit unchanged
- [ ] Queue worker processes `GenerateInvoiceFromLastWeek` as before

---

## 12. Open decisions

| Topic | Recommendation | Status |
|-------|----------------|--------|
| Who owns chat history DB? | Laravel | Recommended |
| Streaming in MVP? | Non-stream first; SSE phase 4 | Recommended |
| Python tool auth model | Service token + `user_id`, Laravel re-checks permission | Recommended |
| Keep modal? | Yes — chat is alternate UX | Agreed |
| Provider | OpenRouter (`openai/gpt-4o-mini` default) | Agreed |

---

## 13. Related code (current app)

| Area | Path |
|------|------|
| Admin layout | `resources/js/Layouts/AdminLayout.vue` |
| Auto-generate modal | `resources/js/Components/Admin/AutoGenerateInvoiceModal.vue` |
| Invoice service | `app/Domain/Invoices/Services/InvoiceService.php` |
| Generation job | `app/Jobs/GenerateInvoiceFromLastWeek.php` |
| Admin routes | `routes/auth.php` |
| Permissions sync | `config/permissions-sync.php` |

---

## 14. Summary

```text
NEWSFLOW-15  →  Python + Laravel backend + tool contracts
NEWSFLOW-14  →  Global Vue chat UI
NEWSFLOW-16  →  Wire chat to auto-invoice preview/start/progress
```

**Golden path:**  
User asks in chat → AI previews generation → user confirms → existing jobs run → chat shows progress → done.

**Golden rule:**  
AI never writes money data itself; Laravel domain + existing jobs remain the only path to create invoices.

---

## 15. Implementation map (as built)

| Area | Path |
|------|------|
| Python service | `ai-service/` |
| Laravel chat domain | `app/Domain/Chat/` |
| Chat controller | `app/Http/Controllers/Admin/ChatController.php` |
| Internal tools | `app/Http/Controllers/Internal/AiToolController.php` |
| Service token middleware | `app/Http/Middleware/VerifyAiServiceToken.php` |
| Chat routes | `routes/auth.php` (`admin/chat/*`) |
| Internal routes | `routes/internal.php` |
| Migration | `database/migrations/2026_07_16_000001_create_chat_tables.php` |
| Vue widget | `resources/js/Components/Chat/AiChatWidget.vue` |
| Composable | `resources/js/Composables/useAiChat.js` |
| Mount point | `resources/js/Layouts/AdminLayout.vue` |
| Permission | `use ai chat` (synced to super-admin) |
| Feature tests | `tests/Feature/Chat/ChatMessageTest.php` |

### Run checklist

1. `php artisan migrate`
2. `php artisan permissions:sync-admin`
3. Set Laravel `.env`: `AI_SERVICE_URL`, `AI_SERVICE_TOKEN`
4. Set `ai-service/.env`: `OPENROUTER_API_KEY`, same `AI_SERVICE_TOKEN`, `LARAVEL_BASE_URL`
5. Start Python: `cd ai-service && uvicorn app.main:app --reload --port 8001`
6. Start Laravel + queue + Vite as usual
7. Log in as super-admin → open floating chat FAB
