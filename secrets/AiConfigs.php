<?php 

// Provide API keys for each model you want to use in the array below
// Where 'apiKey' is empty, the models will not be available for use
// Comment out models that you don't want to use
// Add other models as needed

$AI_MODELS = [
    /* OPENAI - https://openai.com/api/ */

    'openai' => [
        'apiKey' => '',

        'models' => [
            // Flagship GPT‑4.1 family
            'gpt-4.1'            => 'gpt-4.1',
            'gpt-4.1-mini'       => 'gpt-4.1-mini',
            'gpt-4.1-nano'       => 'gpt-4.1-nano',

            // GPT‑4o (“omni”) family
            'gpt-4o'             => 'gpt-4o',
            'gpt-4o-mini'        => 'gpt-4o-mini',
            'gpt-4o-latest'      => 'gpt-4o-latest',   // dynamic alias that always tracks the newest 4o model

            // Earlier GPT‑4 releases still served
            'gpt-4-turbo'        => 'gpt-4-turbo',
            'gpt-4-turbo-32k'    => 'gpt-4-turbo-32k',
            'gpt-4'              => 'gpt-4',
            'gpt-4-32k'          => 'gpt-4-32k',

            // GPT‑3.5 family (now default 16 k context, but legacy ID still works)
            'gpt-3.5-turbo'      => 'gpt-3.5-turbo',
            'gpt-3.5-turbo-16k'  => 'gpt-3.5-turbo-16k',

            // “o‑series” reasoning models (Chat Completions–compatible)
            'o4-mini'            => 'o4-mini',
            'o3'                 => 'o3',
            'o3-mini'            => 'o3-mini',
            'o1'                 => 'o1',
            'o1-mini'            => 'o1-mini'
        ]
    ],

    /* DEEPSEEK - https://api-docs.deepseek.com/ */

    'deepseek' => [
        'apiKey' => '',

        'models' => [
            'deepseek-chat' => 'deepseek-chat',
            'deepseek-reasoner' => 'deepseek-reasoner'
        ]
    ],

    /* ANTHROPIC - https://docs.anthropic.com/claude-api/docs/quickstart */

    'anthropic' => [
        'apiKey' => '',

        'models' => [
            /* Claude 3.7 (hybrid reasoning) */
            'claude-3-7-sonnet-20250219' => 'claude-3-7-sonnet-20250219',
            'claude-3-7-sonnet-latest'   => 'claude-3-7-sonnet-latest',

            /* Claude 3.5 Haiku (fastest) */
            'claude-3-5-haiku-20241022'  => 'claude-3-5-haiku-20241022',
            'claude-3-5-haiku-latest'    => 'claude-3-5-haiku-latest',

            /* Claude 3.5 Sonnet (v2 + legacy snapshot) */
            'claude-3-5-sonnet-20241022' => 'claude-3-5-sonnet-20241022',
            'claude-3-5-sonnet-latest'   => 'claude-3-5-sonnet-latest',
            'claude-3-5-sonnet-20240620' => 'claude-3-5-sonnet-20240620',

            /* Claude 3 foundation models */
            'claude-3-opus-20240229'     => 'claude-3-opus-20240229',
            'claude-3-opus-latest'       => 'claude-3-opus-latest',
            'claude-3-sonnet-20240229'   => 'claude-3-sonnet-20240229',
            'claude-3-haiku-20240307'    => 'claude-3-haiku-20240307'
        ]
    ]
];
