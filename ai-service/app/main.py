from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from app.config import get_settings
from app.routers.chat import router as chat_router

settings = get_settings()

app = FastAPI(
    title="Newsflow AI Service",
    version="1.0.0",
    description="Python AI gateway for Newsflow common chat (OpenRouter).",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=[],  # browser should not call this service
    allow_credentials=False,
    allow_methods=["POST", "GET"],
    allow_headers=["*"],
)

app.include_router(chat_router)


@app.get("/health")
def health() -> dict[str, str]:
    return {
        "status": "ok",
        "model": settings.openrouter_model,
        "provider": "openrouter",
        "base_url": settings.openrouter_base_url,
    }
