from functools import lru_cache

from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_file=".env", env_file_encoding="utf-8", extra="ignore")

    # OpenRouter (OpenAI-compatible)
    openrouter_api_key: str = ""
    openrouter_base_url: str = "https://openrouter.ai/api/v1"
    # Pick any model slug from https://openrouter.ai/models
    # openrouter/free works without credits; use openai/gpt-4o-mini when you have credits.
    openrouter_model: str = "openrouter/free"

    # Optional OpenRouter ranking headers
    app_url: str = "http://localhost"
    app_name: str = "Newsflow"

    # Service-to-service auth (must match Laravel AI_SERVICE_TOKEN)
    ai_service_token: str = "dev-shared-secret"
    laravel_base_url: str = "http://127.0.0.1:8000"
    host: str = "0.0.0.0"
    port: int = 8001


@lru_cache
def get_settings() -> Settings:
    return Settings()
