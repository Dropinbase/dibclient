<?php

// Provide API keys for each provider you want to use in the array below.
// Where 'apiKey' is empty, the provider will not be available for use.
// Comment out providers/models that you don't want to use.
// Add other models as needed.
//
// IMPORTANT SECURITY NOTE:
// Do NOT commit real API keys into repositories. In fact, do not commit anything in the /secrets/ folder!

$AI_MODELS = [

    /* OPENAI — Responses API — https://platform.openai.com/docs/models
       Endpoint used: https://api.openai.com/v1/responses
       Notes:
       - Audio is not supported in Responses API, so audio/realtime/transcribe/TTS/search-preview models are excluded.
    */
    'openai' => [
        'apiKey' => '', // getenv('OPENAI_API_KEY')
        'models' => [

            // --- GPT-5 family (primary) ---
            'gpt-5.2'        => 'gpt-5.2',
            'gpt-5.2-pro'    => 'gpt-5.2-pro',   // Responses-only advanced variant
            'gpt-5.1'        => 'gpt-5.1',
            'gpt-5'          => 'gpt-5',
            'gpt-5-pro'      => 'gpt-5-pro',     // Responses-only advanced variant
            'gpt-5-mini'     => 'gpt-5-mini',
            'gpt-5-nano'     => 'gpt-5-nano',

            // --- GPT-4.1 family (still broadly useful) ---
            'gpt-4.1'        => 'gpt-4.1',
            'gpt-4.1-mini'   => 'gpt-4.1-mini',
            'gpt-4.1-nano'   => 'gpt-4.1-nano',

            // --- GPT-4o family (text/image input → text output) ---
            'gpt-4o'         => 'gpt-4o',
            'gpt-4o-mini'    => 'gpt-4o-mini',

            // --- o-series reasoning models (Responses-compatible) ---
            'o3'             => 'o3',
            'o3-pro'         => 'o3-pro',        // Responses-only
            'o3-mini'        => 'o3-mini',
            'o4-mini'        => 'o4-mini',

            // --- older o-series still served ---
            'o1'             => 'o1',
            'o1-pro'         => 'o1-pro',

            // --- Coding-specialized (Codex) ---
            'gpt-5.2-codex'       => 'gpt-5.2-codex',
            'gpt-5.1-codex'       => 'gpt-5.1-codex',
            'gpt-5.1-codex-max'   => 'gpt-5.1-codex-max',   // Responses-only
            'gpt-5.1-codex-mini'  => 'gpt-5.1-codex-mini',
            'gpt-5-codex'         => 'gpt-5-codex',

            // --- Tool-specific (only include if your code supports the tool) ---
            // Computer Use tool model (Responses-only). Leave commented out unless you implemented that tool flow.
            // 'computer-use-preview' => 'computer-use-preview',
        ]
    ],

    /* DEEPSEEK — https://api-docs.deepseek.com/
       DeepSeek models are not OpenAI Responses API models, but you can keep them here if your code routes by provider.
    */
    'deepseek' => [
        'apiKey' => '', // getenv('DEEPSEEK_API_KEY')
        'models' => [
            'deepseek-chat'     => 'deepseek-chat',
            'deepseek-reasoner' => 'deepseek-reasoner'
        ]
    ],

    /* ANTHROPIC — Messages API — https://api.anthropic.com/v1/messages
       Docs: https://platform.claude.com/docs/en/about-claude/models/overview
    */
    'anthropic' => [
        'apiKey' => '', // getenv('ANTHROPIC_API_KEY')
        'models' => [
            // Latest generation (Claude 4.x)
            'claude-opus-4-6'            => 'claude-opus-4-6',
            'claude-sonnet-4-5'          => 'claude-sonnet-4-5',
            'claude-haiku-4-5'           => 'claude-haiku-4-5',

            // Snapshot IDs (pin exact behavior)
            'claude-sonnet-4-5-20250929' => 'claude-sonnet-4-5-20250929',
            'claude-haiku-4-5-20251001'  => 'claude-haiku-4-5-20251001',
        ]
    ],

    /* GOOGLE — Gemini API — List models endpoint:
       https://generativelanguage.googleapis.com/v1beta/models/
       Docs: https://ai.google.dev/gemini-api/docs/models
       Notes:
       - Below are TEXT-output models. Image-output / audio-output models are excluded for a text-only integration.
    */
    'gemini' => [
        'apiKey' => '', // getenv('GEMINI_API_KEY')
        'models' => [
            // Gemini 3 (preview)
            'gemini-3-pro-preview'   => 'gemini-3-pro-preview',
            'gemini-3-flash-preview' => 'gemini-3-flash-preview',

            // Gemini 2.5 (current workhorses)
            'gemini-2.5-pro'         => 'gemini-2.5-pro',
            'gemini-2.5-flash'       => 'gemini-2.5-flash',
            'gemini-2.5-flash-lite'  => 'gemini-2.5-flash-lite',

            // Optional preview pin
            'gemini-2.5-flash-lite-preview-09-2025' => 'gemini-2.5-flash-lite-preview-09-2025',

            // Older Gemini 2.0 models exist but are deprecated/shutting down Mar 31, 2026.
            // 'gemini-2.0-flash'      => 'gemini-2.0-flash',
            // 'gemini-2.0-flash-lite' => 'gemini-2.0-flash-lite',
        ]
    ],
];