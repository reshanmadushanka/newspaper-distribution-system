# Newsflow AI Service

Python FastAPI service for common chat and auto-invoice tool orchestration.

## Provider

**OpenRouter** (OpenAI-compatible API)

| Setting | Value |
|---------|--------|
| Base URL | `https://openrouter.ai/api/v1` |
| Env key | `OPENROUTER_API_KEY` |
| Default model | `openrouter/free` (no credits required) |

Pick any model slug from [openrouter.ai/models](https://openrouter.ai/models), e.g.:

- `openrouter/free` (free router — works without purchased credits)
- `openai/gpt-4o-mini` (cheap paid, good for tools — needs OpenRouter credits)
- `openai/gpt-4o`
- `anthropic/claude-3.5-sonnet`
- `inclusionai/ling-3.0-flash:free`
- `openai/gpt-oss-20b:free`

## Stack

- FastAPI + Uvicorn
- OpenAI Python SDK → OpenRouter
- Calls Laravel internal tool routes for auto-generate preview/start/progress

## Run locally

```bash
cd ai-service
python -m venv .venv
# Windows
.venv\Scripts\activate
# Unix
source .venv/bin/activate

pip install -r requirements.txt
copy .env.example .env   # or cp on Unix
# set OPENROUTER_API_KEY and AI_SERVICE_TOKEN in .env

uvicorn app.main:app --reload --port 8001
```

Health: `GET http://127.0.0.1:8001/health`

## Auth

All `/v1/*` routes require:

```http
Authorization: Bearer {AI_SERVICE_TOKEN}
```

Laravel and this service must share the same token.

## Main endpoint

`POST /v1/chat`

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

Response may include `actions` such as `auto_generate_preview`, `auto_generate_started`, `auto_generate_progress`.

## Important

- Browser must **not** call this service.
- Flow: Vue → Laravel → this service → OpenRouter (+ Laravel tools).
