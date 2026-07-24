from __future__ import annotations

from typing import Any

import httpx

from app.config import Settings


class LaravelToolClient:
    """Calls Laravel internal AI tool endpoints (service-token auth)."""

    def __init__(self, settings: Settings):
        self.settings = settings
        self.base_url = settings.laravel_base_url.rstrip("/")

    def _headers(self) -> dict[str, str]:
        return {
            "Authorization": f"Bearer {self.settings.ai_service_token}",
            "Accept": "application/json",
            "Content-Type": "application/json",
        }

    async def preview_auto_generate(self, user_id: int, date: str) -> dict[str, Any]:
        async with httpx.AsyncClient(timeout=60.0) as client:
            response = await client.post(
                f"{self.base_url}/internal/ai/tools/auto-generate/preview",
                headers=self._headers(),
                json={"user_id": user_id, "date": date},
            )
            response.raise_for_status()
            return response.json()

    async def start_auto_generate(self, user_id: int, date: str) -> dict[str, Any]:
        async with httpx.AsyncClient(timeout=60.0) as client:
            response = await client.post(
                f"{self.base_url}/internal/ai/tools/auto-generate/start",
                headers=self._headers(),
                json={"user_id": user_id, "date": date},
            )
            response.raise_for_status()
            return response.json()

    async def get_auto_generate_progress(self, user_id: int) -> dict[str, Any]:
        async with httpx.AsyncClient(timeout=30.0) as client:
            response = await client.get(
                f"{self.base_url}/internal/ai/tools/auto-generate/progress",
                headers=self._headers(),
                params={"user_id": user_id},
            )
            response.raise_for_status()
            return response.json()
