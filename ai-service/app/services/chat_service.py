from __future__ import annotations

import json
from typing import Any

from app.prompts.system import SYSTEM_PROMPT
from app.services.laravel_tools import LaravelToolClient
from app.services.llm_client import LlmClient

TOOLS: list[dict[str, Any]] = [
    {
        "type": "function",
        "function": {
            "name": "preview_auto_generate_invoices",
            "description": (
                "Preview automatic invoice generation for a target date. "
                "Returns eligible shop count based on last week's same-day invoices."
            ),
            "parameters": {
                "type": "object",
                "properties": {
                    "date": {
                        "type": "string",
                        "description": "Target invoice date in YYYY-MM-DD format",
                    }
                },
                "required": ["date"],
            },
        },
    },
    {
        "type": "function",
        "function": {
            "name": "start_auto_generate_invoices",
            "description": (
                "Start automatic invoice generation for a target date. "
                "ONLY call after user explicitly confirmed a previous preview."
            ),
            "parameters": {
                "type": "object",
                "properties": {
                    "date": {
                        "type": "string",
                        "description": "Target invoice date in YYYY-MM-DD format",
                    }
                },
                "required": ["date"],
            },
        },
    },
    {
        "type": "function",
        "function": {
            "name": "get_auto_generate_progress",
            "description": "Get progress of the current auto-invoice generation job for this user.",
            "parameters": {
                "type": "object",
                "properties": {},
            },
        },
    },
]


class ChatService:
    def __init__(self, llm: LlmClient, tools: LaravelToolClient):
        self.llm = llm
        self.tools = tools

    async def reply(
        self,
        *,
        user_id: int,
        message: str,
        history: list[dict[str, str]] | None = None,
        locale: str = "en",
        permissions: list[str] | None = None,
        conversation_id: str | None = None,
    ) -> dict[str, Any]:
        permissions = permissions or []
        history = history or []

        system = SYSTEM_PROMPT + f"\n\nUser locale: {locale}.\nUser permissions: {permissions}."

        messages: list[dict[str, Any]] = [{"role": "system", "content": system}]
        for item in history[-20:]:
            role = item.get("role")
            content = item.get("content")
            if role in {"user", "assistant"} and content:
                messages.append({"role": role, "content": content})
        messages.append({"role": "user", "content": message})

        actions: list[dict[str, Any]] = []
        max_tool_rounds = 4

        for _ in range(max_tool_rounds):
            completion = self.llm.chat(messages, tools=TOOLS, tool_choice="auto")
            choice = completion.choices[0]
            assistant_message = choice.message

            tool_calls = assistant_message.tool_calls or []
            if not tool_calls:
                return {
                    "conversation_id": conversation_id,
                    "reply": assistant_message.content or "",
                    "actions": actions,
                }

            messages.append(
                {
                    "role": "assistant",
                    "content": assistant_message.content,
                    "tool_calls": [
                        {
                            "id": tc.id,
                            "type": "function",
                            "function": {
                                "name": tc.function.name,
                                "arguments": tc.function.arguments or "{}",
                            },
                        }
                        for tc in tool_calls
                    ],
                }
            )

            for tool_call in tool_calls:
                name = tool_call.function.name
                try:
                    args = json.loads(tool_call.function.arguments or "{}")
                except json.JSONDecodeError:
                    args = {}

                result, action = await self._run_tool(
                    name=name,
                    args=args,
                    user_id=user_id,
                    permissions=permissions,
                )
                if action:
                    actions.append(action)

                messages.append(
                    {
                        "role": "tool",
                        "tool_call_id": tool_call.id,
                        "content": json.dumps(result),
                    }
                )

        # Fallback if tool loop exhausts
        final = self.llm.chat(messages, tools=None)
        return {
            "conversation_id": conversation_id,
            "reply": final.choices[0].message.content or "",
            "actions": actions,
        }

    async def _run_tool(
        self,
        *,
        name: str,
        args: dict[str, Any],
        user_id: int,
        permissions: list[str],
    ) -> tuple[dict[str, Any], dict[str, Any] | None]:
        can_generate = (
            "auto generate invoice" in permissions
            or "manage invoices" in permissions
        )

        if name == "preview_auto_generate_invoices":
            date = str(args.get("date", "")).strip()
            if not date:
                return {"error": "date is required (YYYY-MM-DD)"}, None
            if not can_generate:
                result = {
                    "error": "permission_denied",
                    "message": "User lacks auto generate invoice permission.",
                }
                return result, {
                    "type": "info",
                    "payload": result,
                }
            data = await self.tools.preview_auto_generate(user_id, date)
            return data, {
                "type": "auto_generate_preview",
                "payload": data,
            }

        if name == "start_auto_generate_invoices":
            date = str(args.get("date", "")).strip()
            if not date:
                return {"error": "date is required (YYYY-MM-DD)"}, None
            if not can_generate:
                result = {
                    "error": "permission_denied",
                    "message": "User lacks auto generate invoice permission.",
                }
                return result, {"type": "info", "payload": result}
            data = await self.tools.start_auto_generate(user_id, date)
            return data, {
                "type": "auto_generate_started",
                "payload": data,
            }

        if name == "get_auto_generate_progress":
            data = await self.tools.get_auto_generate_progress(user_id)
            action_type = (
                "auto_generate_result"
                if data.get("status") == "completed"
                else "auto_generate_progress"
            )
            return data, {"type": action_type, "payload": data}

        return {"error": f"Unknown tool: {name}"}, None
