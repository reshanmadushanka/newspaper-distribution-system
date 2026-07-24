from __future__ import annotations

from typing import Any

from openai import OpenAI

from app.config import Settings


class LlmClient:
    """OpenAI-compatible client (OpenRouter by default)."""

    def __init__(self, settings: Settings):
        self.settings = settings
        self.client = OpenAI(
            api_key=settings.openrouter_api_key or "missing-key",
            base_url=settings.openrouter_base_url,
            default_headers={
                "HTTP-Referer": settings.app_url,
                "X-Title": settings.app_name,
            },
        )

    def chat(
        self,
        messages: list[dict[str, Any]],
        tools: list[dict[str, Any]] | None = None,
        tool_choice: str | dict[str, Any] | None = "auto",
    ) -> Any:
        kwargs: dict[str, Any] = {
            "model": self.settings.openrouter_model,
            "messages": messages,
        }
        if tools:
            kwargs["tools"] = tools
            kwargs["tool_choice"] = tool_choice

        return self.client.chat.completions.create(**kwargs)
