# Dropin Script MCP Server

An MCP (Model Context Protocol) server that exposes Dropinbase tools to VS Code Copilot Chat. Developers describe what they need in natural language and the server provides the context and Dropin Script syntax for Copilot to produce the final result.

## Tools

### Database tools (backed by `/secrets/Conn.php`)

- `list_databases` — discover all configured databases with IDs, names, and types
- `list_database_tables` — list tables in a specific database
- `get_database_table_structure` — get column definitions, types, foreign keys for a table

### AI task tools (backed by `/dropinbase/dropins/dibAi/aiInfo`)

- `list_ai_tasks` — list available Dropin Script instruction files and their descriptions
- `get_ai_task_instructions` — get the full syntax reference for a specific task

### Generation tool

- `generate_dropin_script` — from a natural-language request, returns task instructions, table structure, supplementary guidance, and a template script. When no database is specified, defaults to the database index configured in `$LOGINDB` from `/configs/Dib.php`.

### App defaults tool

- `get_app_defaults` — retrieve application-level defaults (dropin_id, port item, grid/form/SQL-grid templates, menu parent) from the system database so the LLM can fill in values the user did not provide

### Planned tools

- `get_container_structure` — inspect an existing container's Dropin Script representation (not yet implemented)

## What it does

- Resolves the secrets path dynamically from `DIB::$SECRETSPATH` in `/configs/Dib.php`
- Parses `$DIBDB` and `$LOGINDB` from `/configs/Dib.php` to determine the system database and the default database index
- When no database is specified in a request, defaults to the `$LOGINDB` index
- Loads database connection definitions from the resolved `/secrets/Conn.php`
- Caches PDO connections across tool calls (with stale-connection recovery)
- Connects to MySQL, PostgreSQL, SQLite, or SQL Server through PDO
- Lists configured databases, tables, and detailed field metadata including:
  - field name, type, full type
  - required, nullable, length, precision, scale
  - default value, primary key, auto increment / identity
  - foreign key relationship details
- Reads `Main List of Ai Tasks.txt` as the task index for AI Dropin Script help
- Returns full instructions from task files such as `Create Table Grid.txt` and `Create Form.txt`
- `generate_dropin_script` bundles task instructions, table structure, supplementary guidance (e.g. FK handling), and a template script in one response so Copilot can produce accurate Dropin Script
- New task files added to `/dropinbase/dropins/dibAi/aiInfo` (and indexed in `Main List of Ai Tasks.txt`) are picked up automatically — no code changes required
- Logs diagnostic messages to STDERR for debugging

## Server entry point

Use:

- `php mcp/dropin-script-server.php`

## VS Code workspace setup

This workspace can register the MCP server directly for Copilot chat through:

- [../.vscode/mcp.json](../.vscode/mcp.json)
- [../.vscode/settings.json](../.vscode/settings.json)

Current workspace registration:

```json
{
  "servers": {
    "dropinScript": {
      "type": "stdio",
      "command": "php",
      "args": [
        "${workspaceFolder}/mcp/dropin-script-server.php"
      ]
    }
  }
}
```

Notes:

- `chat.mcp.autostart` is enabled in the workspace settings so VS Code can start the server automatically when configuration changes are detected.
- After opening the workspace or changing the MCP configuration, reload VS Code if the server does not appear immediately.
- If prompted, trust the `dropinScript` server so Copilot can use its tools.
- If tools do not refresh, run `MCP: List Servers` or `MCP: Reset Cached Tools` from the Command Palette.
- Once registered and trusted, Copilot should call `generate_dropin_script` and the other MCP tools directly instead of relying on temporary PHP scripts.

## MCP tools

### `list_databases`

Arguments: none

```json
{}
```

### `list_database_tables`

Arguments:

- `databaseId` or `databaseName`
- optional `schema`

```json
{
  "databaseId": 1
}
```

### `get_database_table_structure`

Arguments:

- `databaseId` or `databaseName`
- `tableName`
- optional `schema`

```json
{
  "databaseId": 1,
  "tableName": "users"
}
```

### `list_ai_tasks`

Arguments: none

```json
{}
```

### `get_ai_task_instructions`

Arguments:

- `taskName` or `taskFile`

```json
{
  "taskName": "Create Table Grid"
}
```

### `generate_dropin_script`

Arguments:

- required: `request`
- optional overrides: `taskType`, `databaseId`, `databaseName`, `schema`, `tableName`

```json
{
  "request": "Create a form based on table client in the couch_data database"
}
```

The response includes `instructions` (syntax reference from the task file), `tableStructure` (field metadata), `supplementaryInstructions` (e.g. FK handling rules), and `suggestedScript` (template-generated starting point).

When no `databaseId` or `databaseName` is provided (either in the arguments or parsed from the request text), `generate_dropin_script` defaults to the database index configured in `$LOGINDB` from `/configs/Dib.php`.

### `get_app_defaults`

Arguments:

- optional: `databaseId`, `databaseName`

```json
{
  "databaseName": "couch_data"
}
```

Returns application-level defaults queried from the system database (`$DIBDB`):

- `default_dropin_id` — the dropin_id field value when creating a container
- `default_port_item_id` — the default item_id for port records, e.g. `mainPage.invoiceTab`
- `default_table_grid_template` — the default template name for grids (option A)
- `default_form_template` — the default template name for forms (option A)
- `default_sql_grid_template` — the default template name for SQL-based grids (option A)
- `default_menu_item_parent` — the default parent item_id for new menu items

When neither `databaseId` nor `databaseName` is provided, `get_app_defaults` defaults to the database index configured in `$LOGINDB` from `/configs/Dib.php`.

### `get_container_structure`

Not yet implemented. Will return the Dropin Script representation of an existing container.

## Notes

- PostgreSQL uses the configured schema from `/secrets/Conn.php` by default, or `public`.
- SQL Server uses `dbo` by default unless a schema is supplied.
- SQLite reads tables through `sqlite_master` and foreign keys through `PRAGMA foreign_key_list`.
- The `required` flag means the field is non-nullable and has neither a default value nor an auto-generated value.
