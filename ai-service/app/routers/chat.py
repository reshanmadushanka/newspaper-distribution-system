from __future__ import annotations

import logging
from typing import Any

from fastapi import APIRouter, Depends, Header, HTTPException, status
from openai import APIError, APIStatusError, AuthenticationError, NotFoundError, PermissionDeniedError, RateLimitError
from pydantic import BaseModel, Field

from app.config import Settings, get_settings
from app.services.chat_service import ChatService
from app.services.laravel_tools import LaravelToolClient
from app.services.llm_client import LlmClient

logger = logging.getLogger(__name__)

router = APIRouter(prefix="/v1", tags=["chat"])


class HistoryMessage(BaseModel):
    role: str
    content: str


class ChatContext(BaseModel):
    permissions: list[str] = Field(default_factory=list)
    role: str | None = None


class ChatRequest(BaseModel):
    user_id: int
    conversation_id: str | None = None
    message: str = Field(min_length=1, max_length=4000)
    locale: str = "en"
    history: list[HistoryMessage] = Field(default_factory=list)
    context: ChatContext = Field(default_factory=ChatContext)


class ChatResponse(BaseModel):
    conversation_id: str | None = None
    reply: str
    actions: list[dict[str, Any]] = Field(default_factory=list)


def verify_service_token(
    authorization: str | None = Header(default=None),
    settings: Settings = Depends(get_settings),
) -> None:
    if not authorization or not authorization.startswith("Bearer "):
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Missing bearer token")
    token = authorization.removeprefix("Bearer ").strip()
    if token != settings.ai_service_token:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Invalid service token")


def get_chat_service(settings: Settings = Depends(get_settings)) -> ChatService:
    return ChatService(LlmClient(settings), LaravelToolClient(settings))


def _openai_error_detail(exc: APIError) -> str:
    """Extract a human-readable message from an OpenAI/OpenRouter API error."""
    body = getattr(exc, "body", None)
    if isinstance(body, dict):
        err = body.get("error")
        if isinstance(err, dict) and err.get("message"):
            return str(err["message"])
        if body.get("message"):
            return str(body["message"])
    message = str(getattr(exc, "message", None) or exc)
    return message


@router.post("/chat", response_model=ChatResponse, dependencies=[Depends(verify_service_token)])
async def chat(
    body: ChatRequest,
    service: ChatService = Depends(get_chat_service),
    settings: Settings = Depends(get_settings),
) -> ChatResponse:
    if not body.message.strip():
        raise HTTPException(status_code=status.HTTP_422_UNPROCESSABLE_ENTITY, detail="Message is empty")

    if not settings.openrouter_api_key:
        raise HTTPException(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            detail="OPENROUTER_API_KEY is not configured on the AI service.",
        )

    try:
        result = await service.reply(
            user_id=body.user_id,
            message=body.message.strip(),
            history=[m.model_dump() for m in body.history],
            locale=body.locale,
            permissions=body.context.permissions,
            conversation_id=body.conversation_id,
        )
    except AuthenticationError as exc:
        detail = _openai_error_detail(exc)
        logger.warning("OpenRouter auth error: %s", detail)
        raise HTTPException(
            status_code=status.HTTP_502_BAD_GATEWAY,
            detail=f"OpenRouter authentication failed: {detail}",
        ) from exc
    except PermissionDeniedError as exc:
        detail = _openai_error_detail(exc)
        logger.warning("OpenRouter permission error: %s", detail)
        raise HTTPException(
            status_code=status.HTTP_502_BAD_GATEWAY,
            detail=f"OpenRouter permission denied: {detail}",
        ) from exc
    except NotFoundError as exc:
        detail = _openai_error_detail(exc)
        logger.warning("OpenRouter model/not-found error (model=%s): %s", settings.openrouter_model, detail)
        raise HTTPException(
            status_code=status.HTTP_502_BAD_GATEWAY,
            detail=(
                f"OpenRouter model error for '{settings.openrouter_model}': {detail}. "
                "Check OPENROUTER_MODEL in ai-service/.env."
            ),
        ) from exc
    except RateLimitError as exc:
        detail = _openai_error_detail(exc)
        logger.warning("OpenRouter rate limit: %s", detail)
        raise HTTPException(
            status_code=status.HTTP_429_TOO_MANY_REQUESTS,
            detail=f"OpenRouter rate limit: {detail}",
        ) from exc
    except APIStatusError as exc:
        detail = _openai_error_detail(exc)
        # 402 insufficient credits etc.
        code = getattr(exc, "status_code", 502) or 502
        mapped = status.HTTP_402_PAYMENT_REQUIRED if code == 402 else status.HTTP_502_BAD_GATEWAY
        logger.warning("OpenRouter API status error (%s): %s", code, detail)
        raise HTTPException(
            status_code=mapped,
            detail=f"OpenRouter error ({code}): {detail}",
        ) from exc
    except APIError as exc:
        detail = _openai_error_detail(exc)
        logger.exception("OpenRouter API error: %s", detail)
        raise HTTPException(
            status_code=status.HTTP_502_BAD_GATEWAY,
            detail=f"OpenRouter request failed: {detail}",
        ) from exc
    except Exception as exc:
        logger.exception("Unhandled chat error")
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"AI service failed: {exc}",
        ) from exc

    return ChatResponse(**result)
